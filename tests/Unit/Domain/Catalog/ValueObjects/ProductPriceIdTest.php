<?php

use App\Domain\Catalog\Exceptions\InvalidProduct;
use App\Domain\Catalog\ValueObjects\ProductPriceId;

test('it wraps a positive integer', function () {
    expect((new ProductPriceId(4))->value())->toBe(4);
});

test('it rejects non positive identifiers', function () {
    new ProductPriceId(0);
})->throws(InvalidProduct::class, 'The product price id must be a positive integer.');
