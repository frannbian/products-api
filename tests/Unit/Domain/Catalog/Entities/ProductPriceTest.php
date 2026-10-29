<?php

use App\Domain\Catalog\Entities\ProductPrice;
use App\Domain\Catalog\ValueObjects\Amount;
use App\Domain\Catalog\ValueObjects\ProductPriceId;
use App\Domain\Currency\ValueObjects\CurrencyId;

test('a new price has no identity until it is persisted', function () {
    $price = ProductPrice::create(new CurrencyId(2), new Amount('10'));

    expect($price->id())->toBeNull()
        ->and($price->currencyId()->value())->toBe(2)
        ->and($price->amount()->value())->toBe('10.00');
});

test('its amount can be changed', function () {
    $price = ProductPrice::reconstitute(new ProductPriceId(1), new CurrencyId(2), new Amount('10'));

    $price->changeAmount(new Amount('12.30'));

    expect($price->id()->value())->toBe(1)
        ->and($price->amount()->value())->toBe('12.30');
});
