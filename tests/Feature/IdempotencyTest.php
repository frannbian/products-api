<?php

use App\Infrastructure\Catalog\Persistence\Eloquent\ProductModel;
use App\Infrastructure\Currency\Persistence\Eloquent\CurrencyModel;
use App\Infrastructure\Identity\Persistence\Eloquent\UserModel;
use App\Presentation\Http\Middleware\EnsureIdempotency;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    $this->user = UserModel::factory()->create();
    $this->withToken(jwtFor($this->user));
    $this->usd = CurrencyModel::factory()->create();
});

function newProductPayload(int $currencyId, string $name = 'Keyboard'): array
{
    return ['name' => $name, 'description' => 'Desc', 'price' => 10, 'currency_id' => $currencyId, 'tax_cost' => 1, 'manufacturing_cost' => 2];
}

test('retrying a POST with the same key replays the first response', function () {
    $first = $this->postJson('/api/products', newProductPayload($this->usd->id), ['Idempotency-Key' => 'key-1'])
        ->assertCreated()
        ->assertHeaderMissing('Idempotent-Replayed');

    $second = $this->postJson('/api/products', newProductPayload($this->usd->id), ['Idempotency-Key' => 'key-1'])
        ->assertCreated()
        ->assertHeader('Idempotent-Replayed', 'true');

    expect($second->json())->toBe($first->json())
        ->and($second->headers->get('ETag'))->toBe($first->headers->get('ETag'))
        ->and(ProductModel::count())->toBe(1);
});

test('without a key every POST is processed', function () {
    $this->postJson('/api/products', newProductPayload($this->usd->id))->assertCreated();
    $this->postJson('/api/products', newProductPayload($this->usd->id))->assertCreated();

    expect(ProductModel::count())->toBe(2);
});

test('reusing a key with a different payload is rejected', function () {
    $this->postJson('/api/products', newProductPayload($this->usd->id, 'Keyboard'), ['Idempotency-Key' => 'key-1'])->assertCreated();

    $this->postJson('/api/products', newProductPayload($this->usd->id, 'Mouse'), ['Idempotency-Key' => 'key-1'])
        ->assertUnprocessable()
        ->assertJson(['message' => __('http.idempotency_key_reused')]);

    expect(ProductModel::count())->toBe(1);
});

test('reusing a key on another endpoint is rejected', function () {
    $this->postJson('/api/products', newProductPayload($this->usd->id), ['Idempotency-Key' => 'key-1'])->assertCreated();

    $this->postJson('/api/currencies', ['name' => 'Euro', 'symbol' => '€', 'exchange_rate' => 1], ['Idempotency-Key' => 'key-1'])
        ->assertUnprocessable();
});

test('keys are scoped to the user', function () {
    $this->postJson('/api/products', newProductPayload($this->usd->id), ['Idempotency-Key' => 'shared-key'])->assertCreated();

    resetAuth();
    $this->withToken(apiToken())
        ->postJson('/api/products', newProductPayload($this->usd->id), ['Idempotency-Key' => 'shared-key'])
        ->assertCreated()
        ->assertHeaderMissing('Idempotent-Replayed');

    expect(ProductModel::count())->toBe(2);
});

test('failed requests are not stored so they can be retried with the same key', function () {
    $this->postJson('/api/products', newProductPayload(999), ['Idempotency-Key' => 'key-1'])->assertUnprocessable();

    $this->postJson('/api/products', newProductPayload($this->usd->id), ['Idempotency-Key' => 'key-1'])
        ->assertCreated()
        ->assertHeaderMissing('Idempotent-Replayed');
});

test('a request with a key that is still being processed is rejected', function () {
    $lock = Cache::lock(EnsureIdempotency::cacheKey((string) $this->user->id, 'key-1').':lock', 10);
    $lock->get();

    $this->postJson('/api/products', newProductPayload($this->usd->id), ['Idempotency-Key' => 'key-1'])
        ->assertConflict()
        ->assertJson(['message' => __('http.idempotency_key_in_progress')]);

    $lock->release();
    expect(ProductModel::count())->toBe(0);
});

test('invalid keys are rejected', function (string $key) {
    $this->postJson('/api/products', newProductPayload($this->usd->id), ['Idempotency-Key' => $key])
        ->assertBadRequest()
        ->assertJson(['message' => __('http.idempotency_key_invalid')]);
})->with(['', str_repeat('k', 256)]);

test('the key is ignored on safe methods', function () {
    $this->getJson('/api/products', ['Idempotency-Key' => 'key-1'])->assertOk()->assertHeaderMissing('Idempotent-Replayed');
});

test('it also protects price registration', function () {
    $product = ProductModel::factory()->for($this->usd, 'currency')->create();
    $eur = CurrencyModel::factory()->create();

    $this->postJson("/api/products/{$product->id}/prices", ['currency_id' => $eur->id, 'price' => 9], ['Idempotency-Key' => 'p-1'])->assertCreated();
    $this->postJson("/api/products/{$product->id}/prices", ['currency_id' => $eur->id, 'price' => 9], ['Idempotency-Key' => 'p-1'])
        ->assertCreated()
        ->assertHeader('Idempotent-Replayed', 'true');
});
