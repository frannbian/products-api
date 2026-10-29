<?php

use App\Domain\Currency\Exceptions\InvalidCurrency;
use App\Domain\Currency\ValueObjects\CurrencySymbol;

test('it trims the symbol', function () {
    expect((new CurrencySymbol(' US$ '))->value())->toBe('US$');
});

test('it accepts multibyte symbols', function () {
    expect((new CurrencySymbol('€'))->value())->toBe('€');
});

test('it rejects empty symbols', function () {
    new CurrencySymbol(' ');
})->throws(InvalidCurrency::class, 'The currency symbol cannot be empty.');

test('it rejects symbols longer than 10 characters', function () {
    new CurrencySymbol(str_repeat('$', 11));
})->throws(InvalidCurrency::class, 'The currency symbol cannot exceed 10 characters.');
