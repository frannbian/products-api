<?php

use App\Infrastructure\Catalog\Persistence\Eloquent\ProductModel;
use App\Infrastructure\Catalog\Persistence\Eloquent\ProductPriceModel;
use App\Infrastructure\Currency\Persistence\Eloquent\CurrencyModel;

beforeEach(function () {
    $this->withToken(apiToken());
    $this->usd = CurrencyModel::factory()->create(['name' => 'US Dollar']);
    $this->eur = CurrencyModel::factory()->create(['name' => 'Euro']);
    $this->ars = CurrencyModel::factory()->create(['name' => 'Peso argentino']);
    $this->product = ProductModel::factory()->for($this->usd, 'currency')->create();
});

test('it lists the prices of a product', function () {
    $eur = ProductPriceModel::factory()->for($this->product, 'product')->for($this->eur, 'currency')->create(['price' => '92.00']);
    $ars = ProductPriceModel::factory()->for($this->product, 'product')->for($this->ars, 'currency')->create(['price' => '150000.50']);

    $this->getJson("/api/products/{$this->product->id}/prices")
        ->assertOk()
        ->assertExactJson(['data' => [
            ['id' => $eur->id, 'product_id' => $this->product->id, 'currency_id' => $this->eur->id, 'price' => '92.00'],
            ['id' => $ars->id, 'product_id' => $this->product->id, 'currency_id' => $this->ars->id, 'price' => '150000.50'],
        ]]);
});

test('a product without prices returns an empty list', function () {
    $this->getJson("/api/products/{$this->product->id}/prices")
        ->assertOk()
        ->assertExactJson(['data' => []]);
});

test('it registers a price in another currency', function () {
    $response = $this->postJson("/api/products/{$this->product->id}/prices", [
        'currency_id' => $this->eur->id,
        'price' => 92.5,
    ])->assertCreated()
        ->assertJson(['data' => [
            'product_id' => $this->product->id,
            'currency_id' => $this->eur->id,
            'price' => '92.50',
        ]]);

    $this->assertDatabaseHas('product_prices', [
        'id' => $response->json('data.id'),
        'product_id' => $this->product->id,
        'currency_id' => $this->eur->id,
        'price' => '92.50',
    ]);
});

test('registering a price again in the same currency updates it', function () {
    $existing = ProductPriceModel::factory()->for($this->product, 'product')->for($this->eur, 'currency')->create(['price' => '92.00']);

    $this->postJson("/api/products/{$this->product->id}/prices", [
        'currency_id' => $this->eur->id,
        'price' => '95',
    ])->assertOk()
        ->assertJsonPath('data.id', $existing->id)
        ->assertJsonPath('data.price', '95.00');

    $this->assertDatabaseCount('product_prices', 1);
});

test('a price can not be registered in the base currency', function () {
    $this->postJson("/api/products/{$this->product->id}/prices", [
        'currency_id' => $this->usd->id,
        'price' => '10',
    ])->assertUnprocessable()
        ->assertJsonValidationErrors(['currency_id' => __('catalog.price_in_base_currency')]);
});

test('a price can not be registered in an unknown currency', function () {
    $this->postJson("/api/products/{$this->product->id}/prices", [
        'currency_id' => 999,
        'price' => '10',
    ])->assertUnprocessable()->assertJsonValidationErrors('currency_id');
});

test('it validates the price fields', function (array $payload, string $field) {
    $this->postJson("/api/products/{$this->product->id}/prices", $payload)
        ->assertUnprocessable()
        ->assertJsonValidationErrors($field);
})->with([
    [['price' => '10'], 'currency_id'],
    [['currency_id' => 2], 'price'],
    [['currency_id' => 2, 'price' => '-1'], 'price'],
    [['currency_id' => 2, 'price' => '1.999'], 'price'],
]);

test('prices of deleted products are not available', function () {
    $this->product->delete();

    $this->getJson("/api/products/{$this->product->id}/prices")->assertNotFound();
});
