<?php

use App\Infrastructure\Catalog\Persistence\Eloquent\ProductModel;
use App\Infrastructure\Currency\Persistence\Eloquent\CurrencyModel;
use App\Infrastructure\Identity\Persistence\Eloquent\UserModel;
use App\Infrastructure\Shared\Audit\RecordAuditEntry;
use Illuminate\Events\CallQueuedListener;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    $this->user = UserModel::factory()->create();
    $this->withToken(jwtFor($this->user));
    $this->usd = CurrencyModel::factory()->create();
    $this->eur = CurrencyModel::factory()->create();
});

function createViaApi(object $test, string $name = 'Keyboard'): int
{
    return $test->postJson('/api/products', ['name' => $name, 'description' => 'Desc', 'price' => 10, 'currency_id' => $test->usd->id, 'tax_cost' => 1, 'manufacturing_cost' => 2])
        ->assertCreated()
        ->json('data.id');
}

test('changing a product queues an audit message on catalog-events', function () {
    Queue::fake();

    createViaApi($this);

    Queue::assertPushedOn('catalog-events', CallQueuedListener::class, fn (CallQueuedListener $job) => $job->class === RecordAuditEntry::class);
});

test('the history of a product shows every change with who made it', function () {
    $id = createViaApi($this);
    $this->putJson("/api/products/{$id}", ['name' => 'Mouse', 'description' => 'Desc', 'price' => 12, 'currency_id' => $this->usd->id, 'tax_cost' => 1, 'manufacturing_cost' => 2])->assertOk();
    $this->postJson("/api/products/{$id}/prices", ['currency_id' => $this->eur->id, 'price' => 11])->assertCreated();

    $this->getJson("/api/products/{$id}/history")
        ->assertOk()
        ->assertJsonPath('meta.total', 3)
        ->assertJsonPath('data.0.event', 'product.created')
        ->assertJsonPath('data.0.actor_id', $this->user->id)
        ->assertJsonPath('data.0.payload.name', 'Keyboard')
        ->assertJsonPath('data.1.event', 'product.updated')
        ->assertJsonPath('data.1.payload.name', 'Mouse')
        ->assertJsonPath('data.2.event', 'product.price_set')
        ->assertJsonPath('data.2.payload.currency_id', $this->eur->id)
        ->assertJsonPath('data.2.payload.price', '11.00')
        ->assertJsonPath('data.2.payload.created', true)
        ->assertJsonStructure(['data' => [['event_id', 'event', 'actor_id', 'payload', 'occurred_at']], 'links', 'meta']);
});

test('the history of a deleted product is still available', function () {
    $id = createViaApi($this);
    $this->deleteJson("/api/products/{$id}")->assertNoContent();

    $this->getJson("/api/products/{$id}/history")
        ->assertOk()
        ->assertJsonPath('data.1.event', 'product.deleted');
});

test('a rejected change leaves no trace', function () {
    $id = createViaApi($this);

    $this->postJson("/api/products/{$id}/prices", ['currency_id' => $this->usd->id, 'price' => 1])->assertUnprocessable();

    $this->getJson("/api/products/{$id}/history")->assertJsonPath('meta.total', 1);
});

test('the history only shows entries of the requested product', function () {
    createViaApi($this, 'Other');
    $id = createViaApi($this);

    $this->getJson("/api/products/{$id}/history")->assertJsonPath('meta.total', 1);
});

test('the history is paginated', function () {
    $id = createViaApi($this);
    $this->postJson("/api/products/{$id}/prices", ['currency_id' => $this->eur->id, 'price' => 11])->assertCreated();

    $this->getJson("/api/products/{$id}/history?per_page=1&page=2")
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.event', 'product.price_set');
});

test('the history requires a token', function () {
    resetAuth();
    $product = ProductModel::factory()->for($this->usd, 'currency')->create();

    $this->withHeaders(['Authorization' => ''])->getJson("/api/products/{$product->id}/history")->assertUnauthorized();
});
