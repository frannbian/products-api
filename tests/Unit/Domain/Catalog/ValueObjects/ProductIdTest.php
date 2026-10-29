<?php

use App\Domain\Catalog\Exceptions\InvalidProduct;
use App\Domain\Catalog\ValueObjects\ProductId;

test('it wraps a positive integer', function () {
    expect((new ProductId(7))->value())->toBe(7);
});

test('it rejects non positive identifiers', function (int $invalid) {
    new ProductId($invalid);
})->throws(InvalidProduct::class, 'The product id must be a positive integer.')
    ->with([0, -1]);
