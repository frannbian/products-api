<?php

namespace Database\Seeders;

use App\Infrastructure\Catalog\Persistence\Eloquent\ProductModel;
use App\Infrastructure\Currency\Persistence\Eloquent\CurrencyModel;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Demo products priced in US Dollars, with prices in other currencies.
     */
    public function run(): void
    {
        $currencies = CurrencyModel::query()->pluck('id', 'name');

        $products = [
            [
                'name' => 'Mechanical keyboard',
                'description' => 'Hot-swappable mechanical keyboard with PBT keycaps.',
                'price' => '150.00', 'tax_cost' => '31.50', 'manufacturing_cost' => '70.00',
                'prices' => ['Euro' => '138.00', 'Peso argentino' => '157500.00'],
            ],
            [
                'name' => 'Wireless mouse',
                'description' => 'Ergonomic wireless mouse with USB-C charging.',
                'price' => '45.00', 'tax_cost' => '9.45', 'manufacturing_cost' => '18.00',
                'prices' => ['Euro' => '41.40', 'Real brasileño' => '252.00'],
            ],
            [
                'name' => '27" 4K monitor',
                'description' => 'IPS monitor, 27 inches, 4K resolution and 99% sRGB.',
                'price' => '399.99', 'tax_cost' => '84.00', 'manufacturing_cost' => '210.00',
                'prices' => ['Pound sterling' => '315.99'],
            ],
        ];

        foreach ($products as $data) {
            $product = ProductModel::query()->updateOrCreate(
                ['name' => $data['name']],
                [
                    'description' => $data['description'],
                    'price' => $data['price'],
                    'currency_id' => $currencies['US Dollar'],
                    'tax_cost' => $data['tax_cost'],
                    'manufacturing_cost' => $data['manufacturing_cost'],
                ],
            );

            foreach ($data['prices'] as $currency => $price) {
                $product->prices()->updateOrCreate(['currency_id' => $currencies[$currency]], ['price' => $price]);
            }
        }
    }
}
