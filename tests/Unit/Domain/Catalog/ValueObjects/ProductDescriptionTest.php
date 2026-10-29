<?php

use App\Domain\Catalog\Exceptions\InvalidProduct;
use App\Domain\Catalog\ValueObjects\ProductDescription;

test('it trims the description', function () {
    expect((new ProductDescription("  Mechanical keyboard.\n"))->value())->toBe('Mechanical keyboard.');
});

test('it rejects empty descriptions', function () {
    new ProductDescription('   ');
})->throws(InvalidProduct::class, 'The product description cannot be empty.');

test('it rejects descriptions longer than 2000 characters', function () {
    new ProductDescription(str_repeat('a', 2001));
})->throws(InvalidProduct::class, 'The product description cannot exceed 2000 characters.');
