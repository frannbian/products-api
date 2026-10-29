<?php

use App\Domain\Currency\Exceptions\InvalidCurrency;
use App\Domain\Currency\ValueObjects\CurrencyName;

test('it trims the name', function () {
    expect((new CurrencyName('  US Dollar '))->value())->toBe('US Dollar');
});

test('it rejects empty names', function (string $empty) {
    new CurrencyName($empty);
})->throws(InvalidCurrency::class, 'The currency name cannot be empty.')
    ->with(['', '   ']);

test('it rejects names longer than 100 characters', function () {
    new CurrencyName(str_repeat('a', 101));
})->throws(InvalidCurrency::class, 'The currency name cannot exceed 100 characters.');
