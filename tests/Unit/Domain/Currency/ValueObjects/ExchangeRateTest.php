<?php

use App\Domain\Currency\Exceptions\InvalidCurrency;
use App\Domain\Currency\ValueObjects\ExchangeRate;

test('it normalizes the rate to six decimals', function (string|int|float $rate, string $expected) {
    expect((new ExchangeRate($rate))->value())->toBe($expected);
})->with([
    ['1', '1.000000'],
    ['0.92', '0.920000'],
    ['1050.123456', '1050.123456'],
    [1, '1.000000'],
]);

test('it rejects rates that are not positive', function (string $rate) {
    new ExchangeRate($rate);
})->throws(InvalidCurrency::class, 'The exchange rate must be greater than zero.')
    ->with(['0', '-1', '0.000000']);

test('it rejects rates with more than six decimals', function () {
    new ExchangeRate('1.1234567');
})->throws(InvalidCurrency::class, 'The exchange rate cannot have more than 6 decimals.');

test('it rejects values that are not numbers', function () {
    new ExchangeRate('abc');
})->throws(InvalidCurrency::class, 'The exchange rate must be a number.');

test('it rejects rates that do not fit in the database column', function () {
    new ExchangeRate('1000000000000');
})->throws(InvalidCurrency::class, 'The exchange rate cannot exceed 999999999999.999999.');
