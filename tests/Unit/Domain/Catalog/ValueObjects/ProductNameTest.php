<?php

use App\Domain\Catalog\Exceptions\InvalidProduct;
use App\Domain\Catalog\ValueObjects\ProductName;

test('it trims the name', function () {
    expect((new ProductName('  Keyboard  '))->value())->toBe('Keyboard');
});

test('it rejects empty names', function (string $empty) {
    new ProductName($empty);
})->throws(InvalidProduct::class, 'The product name cannot be empty.')
    ->with(['', '  ']);

test('it accepts names of exactly 255 characters', function () {
    expect(mb_strlen((new ProductName(str_repeat('ñ', 255)))->value()))->toBe(255);
});

test('it rejects names longer than 255 characters', function () {
    new ProductName(str_repeat('a', 256));
})->throws(InvalidProduct::class, 'The product name cannot exceed 255 characters.');
