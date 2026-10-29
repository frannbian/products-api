<?php

use App\Infrastructure\Catalog\Persistence\Eloquent\ProductModel;
use App\Infrastructure\Currency\Persistence\Eloquent\CurrencyModel;

beforeEach(function () {
    $this->withToken(apiToken());
    $this->usd = CurrencyModel::factory()->create();
    $this->product = ProductModel::factory()->for($this->usd, 'currency')->create();
});

function productUpdate(int $currencyId, string $name = 'Updated'): array
{
    return ['name' => $name, 'description' => 'Desc', 'price' => 10, 'currency_id' => $currencyId, 'tax_cost' => 1, 'manufacturing_cost' => 2];
}

test('single resources carry their version as ETag', function () {
    $this->getJson("/api/products/{$this->product->id}")->assertOk()->assertHeader('ETag', '"1"');
    $this->getJson("/api/currencies/{$this->usd->id}")->assertOk()->assertHeader('ETag', '"1"');
});

test('updating returns the new ETag', function () {
    $this->putJson("/api/products/{$this->product->id}", productUpdate($this->usd->id))
        ->assertOk()
        ->assertHeader('ETag', '"2"');
});

test('a matching If-None-Match responds 304 without a body', function () {
    $this->getJson("/api/products/{$this->product->id}", ['If-None-Match' => '"1"'])
        ->assertStatus(304)
        ->assertContent('');
});

test('a stale If-None-Match returns the resource', function () {
    $this->getJson("/api/currencies/{$this->usd->id}", ['If-None-Match' => '"0"'])->assertOk();
});

test('an update with the current If-Match succeeds', function () {
    $this->putJson("/api/products/{$this->product->id}", productUpdate($this->usd->id), ['If-Match' => '"1"'])
        ->assertOk();
});

test('an update with a stale If-Match is rejected and changes nothing', function () {
    $this->putJson("/api/products/{$this->product->id}", productUpdate($this->usd->id, 'First'))->assertOk();

    $this->putJson("/api/products/{$this->product->id}", productUpdate($this->usd->id, 'Second'), ['If-Match' => '"1"'])
        ->assertStatus(412)
        ->assertJson(['message' => __('http.stale_version')]);

    expect($this->product->fresh()->name)->toBe('First');
});

test('a currency update with a stale If-Match is rejected', function () {
    $this->putJson("/api/currencies/{$this->usd->id}", ['name' => 'US Dollar', 'symbol' => '$', 'exchange_rate' => 1], ['If-Match' => '"9"'])
        ->assertStatus(412);
});

test('an If-Match that is not a version is rejected', function (string $header) {
    $this->putJson("/api/products/{$this->product->id}", productUpdate($this->usd->id), ['If-Match' => $header])
        ->assertStatus(412);
})->with(['abc', '"abc"', '"1", "2"']);

test('weak and wildcard If-Match values are accepted', function (string $header) {
    $this->putJson("/api/products/{$this->product->id}", productUpdate($this->usd->id), ['If-Match' => $header])
        ->assertOk();
})->with(['W/"1"', '*']);
