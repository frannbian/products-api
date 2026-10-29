<?php

use App\Domain\Currency\Exceptions\InvalidCurrency;
use App\Domain\Currency\ValueObjects\CurrencyId;

test('it wraps a positive integer', function () {
    expect((new CurrencyId(3))->value())->toBe(3);
});

test('it rejects non positive identifiers', function (int $invalid) {
    new CurrencyId($invalid);
})->throws(InvalidCurrency::class, 'The currency id must be a positive integer.')
    ->with([0, -5]);

test('two ids with the same value are equal', function () {
    expect((new CurrencyId(1))->equals(new CurrencyId(1)))->toBeTrue()
        ->and((new CurrencyId(1))->equals(new CurrencyId(2)))->toBeFalse();
});
