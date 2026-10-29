<?php

namespace Database\Factories;

use App\Infrastructure\Currency\Persistence\Eloquent\CurrencyModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CurrencyModel>
 */
class CurrencyFactory extends Factory
{
    /**
     * @var class-string<CurrencyModel>
     */
    protected $model = CurrencyModel::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->currencyCode().' '.fake()->unique()->numberBetween(1, 99999),
            'symbol' => fake()->randomElement(['$', '€', '£', '¥', 'R$']),
            'exchange_rate' => fake()->randomFloat(6, 0.1, 1500),
        ];
    }
}
