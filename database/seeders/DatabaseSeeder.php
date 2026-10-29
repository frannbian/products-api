<?php

namespace Database\Seeders;

use App\Infrastructure\Identity\Persistence\Eloquent\UserModel;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        UserModel::query()->firstOrCreate(
            ['email' => 'test@example.com'],
            UserModel::factory()->raw(['name' => 'Test User', 'email' => 'test@example.com']),
        );

        $this->call([
            CurrencySeeder::class,
            ProductSeeder::class,
        ]);
    }
}
