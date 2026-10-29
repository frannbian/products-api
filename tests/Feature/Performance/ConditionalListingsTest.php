<?php

use App\Infrastructure\Catalog\Persistence\Eloquent\ProductModel;
use App\Infrastructure\Currency\Persistence\Eloquent\CurrencyModel;

beforeEach(function () {
    $this->withToken(apiToken());
    $this->usd = CurrencyModel::factory()->create();
    $this->product = ProductModel::factory()->for($this->usd, 'currency')->create();
});

test('listings respond 304 while their content does not change', function (string $uri) {
    $etag = $this->getJson($uri)->assertOk()->headers->get('ETag');

    expect($etag)->not->toBeNull();

    $this->getJson($uri, ['If-None-Match' => $etag])->assertStatus(304)->assertContent('');
})->with([
    '/api/products',
    '/api/currencies',
    fn () => "/api/products/{$this->product->id}/prices",
]);

test('a listing returns content again when it changed', function () {
    $etag = $this->getJson('/api/products')->headers->get('ETag');

    ProductModel::factory()->for($this->usd, 'currency')->create();

    $this->getJson('/api/products', ['If-None-Match' => $etag])->assertOk()->assertJsonCount(2, 'data');
});

test('authenticated listings are never stored by shared caches', function () {
    expect($this->getJson('/api/products')->headers->get('Cache-Control'))->toContain('private');
});
