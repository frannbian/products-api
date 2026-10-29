<?php

use App\Infrastructure\Catalog\Jobs\ProcessProductImportJob;
use App\Infrastructure\Catalog\Persistence\Eloquent\ProductModel;
use App\Infrastructure\Currency\Persistence\Eloquent\CurrencyModel;
use App\Infrastructure\Identity\Persistence\Eloquent\UserModel;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    $this->user = UserModel::factory()->create();
    $this->withToken(jwtFor($this->user));
    $this->usd = CurrencyModel::factory()->create();
});

function importPayload(int $currencyId, int $rows = 2): array
{
    return ['products' => array_map(fn (int $i) => [
        'name' => "Product {$i}",
        'description' => 'Imported',
        'price' => 10 + $i,
        'currency_id' => $currencyId,
        'tax_cost' => 1,
        'manufacturing_cost' => 2,
    ], range(1, $rows))];
}

test('an import is accepted right away and processed in the background', function () {
    Queue::fake();

    $response = $this->postJson('/api/products/import', importPayload($this->usd->id))
        ->assertAccepted()
        ->assertHeader('Retry-After', '2')
        ->assertJsonPath('data.status', 'pending')
        ->assertJsonPath('data.total', 2)
        ->assertJsonPath('data.processed', 0);

    $id = $response->json('data.id');
    $response->assertHeader('Location', url("/api/imports/{$id}"));
    expect(ProductModel::count())->toBe(0);
});

test('the queued message carries only the import id', function () {
    Queue::fake();

    $id = $this->postJson('/api/products/import', importPayload($this->usd->id))->json('data.id');

    Queue::assertPushedOn('imports', ProcessProductImportJob::class, function (ProcessProductImportJob $job) use ($id) {
        return $job->importId === $id
            && array_keys(get_object_vars($job)) === array_keys(get_object_vars(new ProcessProductImportJob($id)));
    });
});

test('the import status shows the result once processed', function () {
    $id = $this->postJson('/api/products/import', importPayload($this->usd->id, rows: 3))->json('data.id');

    $this->getJson("/api/imports/{$id}")
        ->assertOk()
        ->assertHeaderMissing('Retry-After')
        ->assertJsonPath('data.status', 'completed')
        ->assertJsonPath('data.total', 3)
        ->assertJsonPath('data.processed', 3)
        ->assertJsonPath('data.succeeded', 3)
        ->assertJsonPath('data.failed', 0)
        ->assertJsonStructure(['data' => ['id', 'status', 'total', 'processed', 'succeeded', 'failed', 'errors', 'created_at', 'finished_at']]);

    expect(ProductModel::count())->toBe(3);
    $this->getJson('/api/products')->assertJsonPath('meta.total', 3);
});

test('rows that break a business rule are reported without stopping the import', function () {
    $payload = importPayload($this->usd->id, rows: 3);
    $payload['products'][1]['currency_id'] = 999;

    $id = $this->postJson('/api/products/import', $payload)->json('data.id');

    $this->getJson("/api/imports/{$id}")
        ->assertJsonPath('data.status', 'completed')
        ->assertJsonPath('data.succeeded', 2)
        ->assertJsonPath('data.failed', 1)
        ->assertJsonPath('data.errors', [['row' => 1, 'message' => 'The currency 999 does not exist.']]);
});

test('every row is validated before accepting the import', function (array $payload, string $field) {
    $this->postJson('/api/products/import', $payload)
        ->assertUnprocessable()
        ->assertJsonValidationErrors($field);
})->with([
    'no products' => [['products' => []], 'products'],
    'not a list' => [['products' => 'abc'], 'products'],
    'too many rows' => [fn () => importPayload(1, rows: 1001), 'products'],
    'bad price in a row' => [['products' => [['name' => 'A', 'description' => 'D', 'price' => '-1', 'currency_id' => 1, 'tax_cost' => 1, 'manufacturing_cost' => 1]]], 'products.0.price'],
    'missing name in a row' => [['products' => [['description' => 'D', 'price' => 1, 'currency_id' => 1, 'tax_cost' => 1, 'manufacturing_cost' => 1]]], 'products.0.name'],
]);

test('products created by an import are audited as changes of its owner', function () {
    $this->postJson('/api/products/import', importPayload($this->usd->id, rows: 1))->assertAccepted();
    $product = ProductModel::sole();

    $this->getJson("/api/products/{$product->id}/history")
        ->assertJsonPath('data.0.event', 'product.created')
        ->assertJsonPath('data.0.actor_id', $this->user->id);
});

test('other users can not see an import', function () {
    $id = $this->postJson('/api/products/import', importPayload($this->usd->id))->json('data.id');

    resetAuth();
    $this->withToken(apiToken())->getJson("/api/imports/{$id}")
        ->assertNotFound()
        ->assertJson(['message' => __('catalog.import_not_found')]);
});

test('unknown or malformed import ids return 404', function (string $id) {
    $this->getJson("/api/imports/{$id}")->assertNotFound();
})->with(['0f8fad5b-d9cb-469f-a165-70867728950e', 'not-a-uuid', '123']);

test('importing requires a token', function () {
    resetAuth();

    $this->withHeaders(['Authorization' => ''])->postJson('/api/products/import', importPayload($this->usd->id))->assertUnauthorized();
});
