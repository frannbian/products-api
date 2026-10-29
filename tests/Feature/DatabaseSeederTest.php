<?php

use App\Infrastructure\Catalog\Persistence\Eloquent\ProductModel;
use App\Infrastructure\Catalog\Persistence\Eloquent\ProductPriceModel;
use App\Infrastructure\Currency\Persistence\Eloquent\CurrencyModel;
use App\Infrastructure\Identity\Persistence\Eloquent\UserModel;

test('the database seeder loads demo data and can run more than once', function () {
    $this->seed();
    $this->seed();

    expect(UserModel::where('email', 'test@example.com')->count())->toBe(1)
        ->and(CurrencyModel::count())->toBe(5)
        ->and(ProductModel::count())->toBe(3)
        ->and(ProductPriceModel::count())->toBe(5);
});

test('the seeded user can log in', function () {
    $this->seed();

    $this->postJson('/api/auth/login', ['email' => 'test@example.com', 'password' => 'password'])->assertOk();
});
