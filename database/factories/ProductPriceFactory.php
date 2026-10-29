<?php

namespace Database\Factories;

use App\Infrastructure\Catalog\Persistence\Eloquent\ProductModel;
use App\Infrastructure\Catalog\Persistence\Eloquent\ProductPriceModel;
use App\Infrastructure\Currency\Persistence\Eloquent\CurrencyModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductPriceModel>
 */
class ProductPriceFactory extends Factory
{
    /**
     * @var class-string<ProductPriceModel>
     */
    protected $model = ProductPriceModel::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_id' => ProductModel::factory(),
            'currency_id' => CurrencyModel::factory(),
            'price' => fake()->randomFloat(2, 10, 5000),
        ];
    }
}
