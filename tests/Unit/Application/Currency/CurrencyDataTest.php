<?php

use App\Application\Currency\DTOs\CurrencyData;

test('it builds value objects from raw input', function () {
    $data = CurrencyData::fromArray(['name' => ' Euro ', 'symbol' => '€', 'exchange_rate' => '0.92']);

    expect($data->name->value())->toBe('Euro')
        ->and($data->symbol->value())->toBe('€')
        ->and($data->exchangeRate->value())->toBe('0.920000');
});
