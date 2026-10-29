<?php

namespace Database\Seeders;

use App\Infrastructure\Currency\Persistence\Eloquent\CurrencyModel;
use Illuminate\Database\Seeder;

class CurrencySeeder extends Seeder
{
    /**
     * Sample currencies. Rates are units per US Dollar and only illustrative.
     */
    public function run(): void
    {
        $currencies = [
            ['name' => 'US Dollar', 'symbol' => 'US$', 'exchange_rate' => '1.000000'],
            ['name' => 'Euro', 'symbol' => '€', 'exchange_rate' => '0.920000'],
            ['name' => 'Peso argentino', 'symbol' => 'AR$', 'exchange_rate' => '1050.000000'],
            ['name' => 'Real brasileño', 'symbol' => 'R$', 'exchange_rate' => '5.600000'],
            ['name' => 'Pound sterling', 'symbol' => '£', 'exchange_rate' => '0.790000'],
        ];

        foreach ($currencies as $currency) {
            CurrencyModel::query()->updateOrCreate(['name' => $currency['name']], $currency);
        }
    }
}
