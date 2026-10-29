<?php

use App\Domain\Currency\Entities\Currency;
use App\Domain\Currency\ValueObjects\CurrencyId;
use App\Domain\Currency\ValueObjects\CurrencyName;
use App\Domain\Currency\ValueObjects\CurrencySymbol;
use App\Domain\Currency\ValueObjects\ExchangeRate;

test('a new currency has no identity until it is persisted', function () {
    $currency = Currency::create(new CurrencyName('Euro'), new CurrencySymbol('€'), new ExchangeRate('0.92'));

    expect($currency->id())->toBeNull()
        ->and($currency->name()->value())->toBe('Euro')
        ->and($currency->symbol()->value())->toBe('€')
        ->and($currency->exchangeRate()->value())->toBe('0.920000');
});

test('a currency can be reconstituted and updated', function () {
    $currency = Currency::reconstitute(
        new CurrencyId(2),
        new CurrencyName('Euro'),
        new CurrencySymbol('€'),
        new ExchangeRate('0.92'),
    );

    $currency->update(new CurrencyName('Euro (EU)'), new CurrencySymbol('EUR'), new ExchangeRate('0.95'));

    expect($currency->id()->value())->toBe(2)
        ->and($currency->name()->value())->toBe('Euro (EU)')
        ->and($currency->symbol()->value())->toBe('EUR')
        ->and($currency->exchangeRate()->value())->toBe('0.950000');
});
