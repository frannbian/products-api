<?php

use App\Application\Catalog\DTOs\ProductData;
use App\Application\Catalog\DTOs\ProductPriceData;

test('product data builds value objects from raw input', function () {
    $data = ProductData::fromArray([
        'name' => ' Keyboard ',
        'description' => 'Mechanical keyboard',
        'price' => '100.5',
        'currency_id' => 1,
        'tax_cost' => 21,
        'manufacturing_cost' => '40.00',
    ]);

    expect($data->name->value())->toBe('Keyboard')
        ->and($data->description->value())->toBe('Mechanical keyboard')
        ->and($data->price->value())->toBe('100.50')
        ->and($data->currencyId->value())->toBe(1)
        ->and($data->taxCost->value())->toBe('21.00')
        ->and($data->manufacturingCost->value())->toBe('40.00');
});

test('product price data builds value objects from raw input', function () {
    $data = ProductPriceData::fromArray(['currency_id' => '2', 'price' => '92.5']);

    expect($data->currencyId->value())->toBe(2)
        ->and($data->price->value())->toBe('92.50');
});
