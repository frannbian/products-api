<?php

use App\Infrastructure\Catalog\Persistence\Eloquent\ProductModel;
use App\Infrastructure\Catalog\Persistence\Eloquent\ProductPriceModel;
use App\Infrastructure\Currency\Persistence\Eloquent\CurrencyModel;

beforeEach(function () {
    $this->withToken(apiToken());
    $this->usd = CurrencyModel::factory()->create(['name' => 'US Dollar', 'symbol' => '$', 'exchange_rate' => 1]);
    $this->eur = CurrencyModel::factory()->create(['name' => 'Euro', 'symbol' => '€', 'exchange_rate' => 0.92]);
});

function productPayload(int $currencyId, array $overrides = []): array
{
    return [
        'name' => 'Mechanical keyboard',
        'description' => 'Hot-swappable mechanical keyboard.',
        'price' => 150.5,
        'currency_id' => $currencyId,
        'tax_cost' => '31.61',
        'manufacturing_cost' => 70,
        ...$overrides,
    ];
}

test('it lists products paginated', function () {
    ProductModel::factory()->count(3)->for($this->usd, 'currency')->create();

    $this->getJson('/api/products?per_page=2')
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('meta.total', 3)
        ->assertJsonPath('meta.per_page', 2)
        ->assertJsonPath('meta.last_page', 2)
        ->assertJsonStructure([
            'data' => [['id', 'name', 'description', 'price', 'currency_id', 'tax_cost', 'manufacturing_cost']],
            'links' => ['first', 'last', 'prev', 'next'],
            'meta',
        ]);
});

test('it lists products with 15 per page by default', function () {
    ProductModel::factory()->count(16)->for($this->usd, 'currency')->create();

    $this->getJson('/api/products')->assertOk()->assertJsonCount(15, 'data');
});

test('it validates the pagination parameters', function (array $query, string $field) {
    $this->getJson('/api/products?'.http_build_query($query))
        ->assertUnprocessable()
        ->assertJsonValidationErrors($field);
})->with([
    [['per_page' => 0], 'per_page'],
    [['per_page' => 101], 'per_page'],
    [['page' => 0], 'page'],
    [['page' => 'abc'], 'page'],
]);

test('it creates a product', function () {
    $response = $this->postJson('/api/products', productPayload($this->usd->id))
        ->assertCreated()
        ->assertJson(['data' => [
            'name' => 'Mechanical keyboard',
            'description' => 'Hot-swappable mechanical keyboard.',
            'price' => '150.50',
            'currency_id' => $this->usd->id,
            'tax_cost' => '31.61',
            'manufacturing_cost' => '70.00',
        ]]);

    $this->assertDatabaseHas('products', [
        'id' => $response->json('data.id'),
        'name' => 'Mechanical keyboard',
        'price' => '150.50',
    ]);
});

test('it validates the product fields', function (array $overrides, string $field) {
    $this->postJson('/api/products', productPayload($this->usd->id, $overrides))
        ->assertUnprocessable()
        ->assertJsonValidationErrors($field);
})->with([
    'missing name' => [['name' => null], 'name'],
    'long name' => [['name' => str_repeat('a', 256)], 'name'],
    'missing description' => [['description' => ''], 'description'],
    'long description' => [['description' => str_repeat('a', 2001)], 'description'],
    'missing price' => [['price' => null], 'price'],
    'negative price' => [['price' => -1], 'price'],
    'price with 3 decimals' => [['price' => '10.123'], 'price'],
    'price too big' => [['price' => '10000000000'], 'price'],
    'text price' => [['price' => 'abc'], 'price'],
    'missing currency' => [['currency_id' => null], 'currency_id'],
    'text currency' => [['currency_id' => 'usd'], 'currency_id'],
    'negative tax cost' => [['tax_cost' => -5], 'tax_cost'],
    'missing manufacturing cost' => [['manufacturing_cost' => null], 'manufacturing_cost'],
]);

test('a product can not be created with an unknown currency', function () {
    $this->postJson('/api/products', productPayload(999))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['currency_id' => __('validation.exists', ['attribute' => 'currency id'])]);

    $this->assertDatabaseCount('products', 0);
});

test('it shows a product', function () {
    $product = ProductModel::factory()->for($this->usd, 'currency')->create(['price' => '99.90']);

    $this->getJson("/api/products/{$product->id}")
        ->assertOk()
        ->assertExactJson(['data' => [
            'id' => $product->id,
            'name' => $product->name,
            'description' => $product->description,
            'price' => '99.90',
            'currency_id' => $this->usd->id,
            'tax_cost' => $product->tax_cost,
            'manufacturing_cost' => $product->manufacturing_cost,
        ]]);
});

test('unknown products return 404', function (string $method, string $suffix) {
    $this->json($method, "/api/products/999{$suffix}", productPayload($this->usd->id))
        ->assertNotFound()
        ->assertJson(['message' => __('catalog.product_not_found')]);
})->with([
    ['GET', ''],
    ['PUT', ''],
    ['DELETE', ''],
    ['GET', '/prices'],
    ['POST', '/prices'],
]);

test('ids that are not valid integers return 404', function (string $id) {
    $this->getJson("/api/products/{$id}")->assertNotFound();
    $this->getJson("/api/products/{$id}/prices")->assertNotFound();
    $this->getJson("/api/currencies/{$id}")->assertNotFound();
})->with(['abc', '1.5', '-1', '0', '99999999999999999999']);

test('it updates a product', function () {
    $product = ProductModel::factory()->for($this->usd, 'currency')->create();

    $this->putJson("/api/products/{$product->id}", productPayload($this->eur->id, ['name' => 'Wireless mouse', 'price' => '45']))
        ->assertOk()
        ->assertJsonPath('data.name', 'Wireless mouse')
        ->assertJsonPath('data.price', '45.00')
        ->assertJsonPath('data.currency_id', $this->eur->id);

    expect($product->fresh())
        ->name->toBe('Wireless mouse')
        ->currency_id->toBe($this->eur->id);
});

test('updating a product requires every field', function () {
    $product = ProductModel::factory()->for($this->usd, 'currency')->create();

    $this->putJson("/api/products/{$product->id}", ['name' => 'Only the name'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['description', 'price', 'currency_id', 'tax_cost', 'manufacturing_cost']);
});

test('the base currency can not change to a currency that already has a price', function () {
    $product = ProductModel::factory()->for($this->usd, 'currency')->create();
    ProductPriceModel::factory()->for($product, 'product')->for($this->eur, 'currency')->create();

    $this->putJson("/api/products/{$product->id}", productPayload($this->eur->id))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['currency_id' => __('catalog.price_in_base_currency')]);
});

test('it deletes a product', function () {
    $product = ProductModel::factory()->for($this->usd, 'currency')->create();

    $this->deleteJson("/api/products/{$product->id}")->assertNoContent();

    $this->assertSoftDeleted($product);
    $this->getJson("/api/products/{$product->id}")->assertNotFound();
});

test('deleted products are not listed', function () {
    ProductModel::factory()->for($this->usd, 'currency')->create()->delete();

    $this->getJson('/api/products')->assertOk()->assertJsonCount(0, 'data');
});
