<?php

use Illuminate\Support\Facades\Schema;

function indexedColumns(string $table): array
{
    return collect(Schema::getIndexes($table))->pluck('columns')->all();
}

test('foreign keys used in lookups are indexed', function (string $table, array $columns) {
    expect(indexedColumns($table))->toContain($columns);
})->with([
    'products by currency' => ['products', ['currency_id']],
    'prices by currency' => ['product_prices', ['currency_id']],
    'prices by product and currency' => ['product_prices', ['product_id', 'currency_id']],
]);
