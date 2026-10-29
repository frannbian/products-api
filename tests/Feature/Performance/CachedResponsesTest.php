<?php

use App\Infrastructure\Catalog\Persistence\Eloquent\ProductModel;
use App\Infrastructure\Currency\Persistence\Eloquent\CurrencyModel;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->withToken(apiToken());
    $this->usd = CurrencyModel::factory()->create(['name' => 'US Dollar']);
    $this->eur = CurrencyModel::factory()->create(['name' => 'Euro']);
    $this->product = ProductModel::factory()->for($this->usd, 'currency')->create();
});

function catalogQueries(Closure $callback): int
{
    DB::flushQueryLog();
    DB::enableQueryLog();
    $callback();
    DB::disableQueryLog();

    // Authentication looks the user up on every request: only catalog tables count.
    return collect(DB::getQueryLog())
        ->filter(fn (array $query) => preg_match('/"(products|product_prices|currencies)"/', $query['query']))
        ->count();
}

test('repeated reads are served without touching the catalog tables', function (string $uri) {
    $this->getJson($uri)->assertOk();

    expect(catalogQueries(fn () => $this->getJson($uri)->assertOk()))->toBe(0);
})->with([
    '/api/products',
    '/api/currencies',
    fn () => "/api/products/{$this->product->id}",
    fn () => "/api/products/{$this->product->id}/prices",
    fn () => "/api/currencies/{$this->usd->id}",
]);

test('a new product shows up in the cached listing right away', function () {
    $this->getJson('/api/products')->assertJsonPath('meta.total', 1);

    $this->postJson('/api/products', ['name' => 'Mouse', 'description' => 'Desc', 'price' => 5, 'currency_id' => $this->usd->id, 'tax_cost' => 0, 'manufacturing_cost' => 0])
        ->assertCreated();

    $this->getJson('/api/products')->assertJsonPath('meta.total', 2);
});

test('a new price shows up in the cached price listing right away', function () {
    $this->getJson("/api/products/{$this->product->id}/prices")->assertJsonPath('meta.total', 0);

    $this->postJson("/api/products/{$this->product->id}/prices", ['currency_id' => $this->eur->id, 'price' => 9])->assertCreated();

    $this->getJson("/api/products/{$this->product->id}/prices")->assertJsonPath('meta.total', 1);
});

test('an updated currency shows up in the cached listing right away', function () {
    $this->getJson('/api/currencies')->assertJsonPath('data.1.name', 'Euro');

    $this->putJson("/api/currencies/{$this->eur->id}", ['name' => 'Euro (EU)', 'symbol' => '€', 'exchange_rate' => 1])->assertOk();

    $this->getJson('/api/currencies')->assertJsonPath('data.1.name', 'Euro (EU)');
});

test('a deleted product disappears from the cached reads right away', function () {
    $this->getJson("/api/products/{$this->product->id}")->assertOk();

    $this->deleteJson("/api/products/{$this->product->id}")->assertNoContent();

    $this->getJson("/api/products/{$this->product->id}")->assertNotFound();
    $this->getJson('/api/products')->assertJsonPath('meta.total', 0);
});

test('changes made outside the api show up once the ttl expires', function () {
    config(['cache.ttl.listings' => 300]);
    $this->getJson('/api/products')->assertJsonPath('meta.total', 1);

    ProductModel::factory()->for($this->usd, 'currency')->create();
    $this->getJson('/api/products')->assertJsonPath('meta.total', 1);

    $this->travel(301)->seconds();
    $this->getJson('/api/products')->assertJsonPath('meta.total', 2);
});
