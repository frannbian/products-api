<?php

use App\Domain\Catalog\Entities\Product;
use App\Domain\Catalog\Entities\ProductPrice;
use App\Domain\Catalog\Exceptions\PriceInBaseCurrency;
use App\Domain\Catalog\ValueObjects\Amount;
use App\Domain\Catalog\ValueObjects\ProductDescription;
use App\Domain\Catalog\ValueObjects\ProductId;
use App\Domain\Catalog\ValueObjects\ProductName;
use App\Domain\Catalog\ValueObjects\ProductPriceId;
use App\Domain\Currency\ValueObjects\CurrencyId;

function newProduct(int $baseCurrency = 1): Product
{
    return Product::create(
        new ProductName('Keyboard'),
        new ProductDescription('Mechanical keyboard'),
        new Amount('100.00'),
        new CurrencyId($baseCurrency),
        new Amount('21.00'),
        new Amount('40.00'),
    );
}

test('a new product has no identity and no prices in other currencies', function () {
    $product = newProduct();

    expect($product->id())->toBeNull()
        ->and($product->name()->value())->toBe('Keyboard')
        ->and($product->description()->value())->toBe('Mechanical keyboard')
        ->and($product->price()->value())->toBe('100.00')
        ->and($product->currencyId()->value())->toBe(1)
        ->and($product->taxCost()->value())->toBe('21.00')
        ->and($product->manufacturingCost()->value())->toBe('40.00')
        ->and($product->prices())->toBe([]);
});

test('a product can be reconstituted with its prices', function () {
    $price = ProductPrice::reconstitute(new ProductPriceId(9), new CurrencyId(2), new Amount('92.00'));

    $product = Product::reconstitute(
        new ProductId(5),
        new ProductName('Keyboard'),
        new ProductDescription('Mechanical keyboard'),
        new Amount('100'),
        new CurrencyId(1),
        new Amount('21'),
        new Amount('40'),
        [$price],
    );

    expect($product->id()->value())->toBe(5)
        ->and($product->prices())->toBe([$price])
        ->and($product->priceIn(new CurrencyId(2)))->toBe($price)
        ->and($product->priceIn(new CurrencyId(3)))->toBeNull();
});

test('its details can be updated', function () {
    $product = newProduct();

    $product->update(
        new ProductName('Mouse'),
        new ProductDescription('Wireless mouse'),
        new Amount('50'),
        new CurrencyId(2),
        new Amount('10.50'),
        new Amount('20'),
    );

    expect($product->name()->value())->toBe('Mouse')
        ->and($product->description()->value())->toBe('Wireless mouse')
        ->and($product->price()->value())->toBe('50.00')
        ->and($product->currencyId()->value())->toBe(2)
        ->and($product->taxCost()->value())->toBe('10.50')
        ->and($product->manufacturingCost()->value())->toBe('20.00');
});

test('setting a price in a new currency registers it', function () {
    $product = newProduct();

    $created = $product->setPriceIn(new CurrencyId(2), new Amount('92.50'));

    expect($created)->toBeTrue()
        ->and($product->prices())->toHaveCount(1)
        ->and($product->priceIn(new CurrencyId(2))->id())->toBeNull()
        ->and($product->priceIn(new CurrencyId(2))->amount()->value())->toBe('92.50');
});

test('setting a price in an already priced currency replaces the amount', function () {
    $product = newProduct();
    $product->setPriceIn(new CurrencyId(2), new Amount('92.50'));

    $created = $product->setPriceIn(new CurrencyId(2), new Amount('95'));

    expect($created)->toBeFalse()
        ->and($product->prices())->toHaveCount(1)
        ->and($product->priceIn(new CurrencyId(2))->amount()->value())->toBe('95.00');
});

test('a price can not be set in the base currency', function () {
    newProduct(baseCurrency: 1)->setPriceIn(new CurrencyId(1), new Amount('10'));
})->throws(PriceInBaseCurrency::class);

test('the base currency can not change to a currency that already has a price', function () {
    $product = newProduct(baseCurrency: 1);
    $product->setPriceIn(new CurrencyId(2), new Amount('92'));

    $product->update(
        $product->name(),
        $product->description(),
        new Amount('92'),
        new CurrencyId(2),
        $product->taxCost(),
        $product->manufacturingCost(),
    );
})->throws(PriceInBaseCurrency::class);
