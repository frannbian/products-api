<?php

namespace Database\Factories;

use App\Infrastructure\Catalog\Persistence\Eloquent\ProductModel;
use App\Infrastructure\Currency\Persistence\Eloquent\CurrencyModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductModel>
 */
class ProductFactory extends Factory
{
    /**
     * @var class-string<ProductModel>
     */
    protected $model = ProductModel::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => ucfirst(fake()->words(3, true)),
            'description' => fake()->sentence(12),
            'price' => fake()->randomFloat(2, 10, 5000),
            'currency_id' => CurrencyModel::factory(),
            'tax_cost' => fake()->randomFloat(2, 1, 500),
            'manufacturing_cost' => fake()->randomFloat(2, 5, 2500),
        ];
    }
}
