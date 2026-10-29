<?php

use App\Domain\Catalog\Exceptions\InvalidProduct;
use App\Domain\Catalog\ValueObjects\Amount;

test('it normalizes the amount to two decimals', function (string|int|float $amount, string $expected) {
    expect((new Amount($amount))->value())->toBe($expected);
})->with([
    ['0', '0.00'],
    ['10', '10.00'],
    ['10.5', '10.50'],
    ['1234.56', '1234.56'],
    [99, '99.00'],
    ['9999999999.99', '9999999999.99'],
]);

test('it rejects negative amounts', function () {
    new Amount('-0.01');
})->throws(InvalidProduct::class, 'The amount cannot be negative.');

test('it rejects amounts with more than two decimals', function () {
    new Amount('10.123');
})->throws(InvalidProduct::class, 'The amount cannot have more than 2 decimals.');

test('it rejects values that are not numbers', function (string $invalid) {
    new Amount($invalid);
})->throws(InvalidProduct::class, 'The amount must be a number.')
    ->with(['abc', '', '1,50']);

test('it rejects amounts that do not fit in the database column', function () {
    new Amount('10000000000.00');
})->throws(InvalidProduct::class, 'The amount cannot exceed 9999999999.99.');

test('two amounts with the same value are equal', function () {
    expect((new Amount('10'))->equals(new Amount('10.00')))->toBeTrue()
        ->and((new Amount('10'))->equals(new Amount('10.01')))->toBeFalse();
});
