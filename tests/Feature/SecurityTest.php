<?php

use App\Infrastructure\Catalog\Persistence\Eloquent\ProductModel;
use App\Infrastructure\Currency\Persistence\Eloquent\CurrencyModel;

test('every product and currency endpoint requires a valid token', function (string $method, string $uri) {
    $this->json($method, $uri)
        ->assertUnauthorized()
        ->assertJson(['message' => 'Unauthenticated.']);
})->with([
    ['GET', '/api/products'],
    ['POST', '/api/products'],
    ['GET', '/api/products/1'],
    ['PUT', '/api/products/1'],
    ['DELETE', '/api/products/1'],
    ['GET', '/api/products/1/prices'],
    ['POST', '/api/products/1/prices'],
    ['GET', '/api/currencies'],
    ['POST', '/api/currencies'],
    ['GET', '/api/currencies/1'],
    ['PUT', '/api/currencies/1'],
    ['DELETE', '/api/currencies/1'],
]);

test('api responses are json even when the client does not ask for it', function () {
    $this->get('/api/products')
        ->assertUnauthorized()
        ->assertHeader('Content-Type', 'application/json');
});

test('api responses carry security headers', function () {
    $this->withToken(apiToken())->getJson('/api/products')
        ->assertOk()
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('X-Frame-Options', 'DENY')
        ->assertHeader('Referrer-Policy', 'no-referrer');
});

test('authenticated requests are rate limited', function () {
    $this->withToken(apiToken());

    foreach (range(1, 60) as $request) {
        $this->getJson('/api/currencies')->assertOk();
    }

    $this->getJson('/api/currencies')->assertTooManyRequests();
});

test('mass assignment can not set unexpected attributes', function () {
    $usd = CurrencyModel::factory()->create();

    $id = $this->withToken(apiToken())->postJson('/api/products', [
        'name' => 'Keyboard',
        'description' => 'Mechanical',
        'price' => 10,
        'currency_id' => $usd->id,
        'tax_cost' => 1,
        'manufacturing_cost' => 2,
        'id' => 999,
        'deleted_at' => now()->toJSON(),
    ])->assertCreated()->json('data.id');

    expect($id)->not->toBe(999)
        ->and(ProductModel::find($id)->trashed())->toBeFalse();
});
