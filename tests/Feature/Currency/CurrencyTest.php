<?php

use App\Infrastructure\Catalog\Persistence\Eloquent\ProductModel;
use App\Infrastructure\Currency\Persistence\Eloquent\CurrencyModel;

beforeEach(function () {
    $this->withToken(apiToken());
});

test('it lists currencies', function () {
    $usd = CurrencyModel::factory()->create(['name' => 'US Dollar', 'symbol' => '$', 'exchange_rate' => 1]);

    $this->getJson('/api/currencies')
        ->assertOk()
        ->assertExactJson(['data' => [
            ['id' => $usd->id, 'name' => 'US Dollar', 'symbol' => '$', 'exchange_rate' => '1.000000'],
        ]]);
});

test('it creates a currency', function () {
    $this->postJson('/api/currencies', ['name' => 'Euro', 'symbol' => '€', 'exchange_rate' => 0.92])
        ->assertCreated()
        ->assertJson(['data' => ['name' => 'Euro', 'symbol' => '€', 'exchange_rate' => '0.920000']]);

    $this->assertDatabaseHas('currencies', ['name' => 'Euro', 'exchange_rate' => '0.920000']);
});

test('currency names are unique', function () {
    CurrencyModel::factory()->create(['name' => 'Euro']);

    $this->postJson('/api/currencies', ['name' => 'Euro', 'symbol' => '€', 'exchange_rate' => 1])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['name' => __('validation.unique', ['attribute' => 'name'])]);
});

test('it validates the currency fields', function (array $overrides, string $field) {
    $this->postJson('/api/currencies', [...['name' => 'Euro', 'symbol' => '€', 'exchange_rate' => 1], ...$overrides])
        ->assertUnprocessable()
        ->assertJsonValidationErrors($field);
})->with([
    [['name' => ''], 'name'],
    [['name' => str_repeat('a', 101)], 'name'],
    [['symbol' => ''], 'symbol'],
    [['symbol' => str_repeat('$', 11)], 'symbol'],
    [['exchange_rate' => 0], 'exchange_rate'],
    [['exchange_rate' => -1], 'exchange_rate'],
    [['exchange_rate' => '1.1234567'], 'exchange_rate'],
    [['exchange_rate' => 'abc'], 'exchange_rate'],
]);

test('it shows a currency', function () {
    $eur = CurrencyModel::factory()->create(['name' => 'Euro', 'symbol' => '€', 'exchange_rate' => 0.92]);

    $this->getJson("/api/currencies/{$eur->id}")
        ->assertOk()
        ->assertExactJson(['data' => ['id' => $eur->id, 'name' => 'Euro', 'symbol' => '€', 'exchange_rate' => '0.920000']]);
});

test('unknown currencies return 404', function (string $method) {
    $this->json($method, '/api/currencies/999', ['name' => 'Euro', 'symbol' => '€', 'exchange_rate' => 1])
        ->assertNotFound()
        ->assertJson(['message' => __('currency.not_found')]);
})->with(['GET', 'PUT', 'DELETE']);

test('it updates a currency', function () {
    $eur = CurrencyModel::factory()->create(['name' => 'Euro', 'exchange_rate' => 0.92]);

    $this->putJson("/api/currencies/{$eur->id}", ['name' => 'Euro', 'symbol' => 'EUR', 'exchange_rate' => '0.95'])
        ->assertOk()
        ->assertJsonPath('data.symbol', 'EUR')
        ->assertJsonPath('data.exchange_rate', '0.950000');
});

test('a currency can not take the name of another one', function () {
    CurrencyModel::factory()->create(['name' => 'US Dollar']);
    $eur = CurrencyModel::factory()->create(['name' => 'Euro']);

    $this->putJson("/api/currencies/{$eur->id}", ['name' => 'US Dollar', 'symbol' => '€', 'exchange_rate' => 1])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('name');
});

test('it deletes an unused currency', function () {
    $eur = CurrencyModel::factory()->create();

    $this->deleteJson("/api/currencies/{$eur->id}")->assertNoContent();

    $this->assertModelMissing($eur);
});

test('a currency used by a product can not be deleted', function () {
    $usd = CurrencyModel::factory()->create();
    ProductModel::factory()->for($usd, 'currency')->create();

    $this->deleteJson("/api/currencies/{$usd->id}")
        ->assertConflict()
        ->assertJson(['message' => __('currency.in_use')]);

    $this->assertModelExists($usd);
});
