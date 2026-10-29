<?php

use App\Presentation\Http\Controllers\Auth\AuthController;
use App\Presentation\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Presentation\Http\Controllers\Auth\NewPasswordController;
use App\Presentation\Http\Controllers\Auth\PasswordResetLinkController;
use App\Presentation\Http\Controllers\Auth\VerifyEmailController;
use App\Presentation\Http\Controllers\Catalog\ProductController;
use App\Presentation\Http\Controllers\Catalog\ProductPriceController;
use App\Presentation\Http\Controllers\Currency\CurrencyController;
use Illuminate\Support\Facades\Route;

// Route ids are positive integers that fit in a 64-bit int; anything else is a 404.
Route::pattern('product', '[1-9][0-9]{0,17}');
Route::pattern('currency', '[1-9][0-9]{0,17}');

Route::prefix('auth')->group(function () {
    // Public
    Route::post('/register', [AuthController::class, 'register'])->name('register');
    Route::post('/login', [AuthController::class, 'login'])->name('login');

    Route::post('/forgot-password', [PasswordResetLinkController::class, 'store'])
        ->name('password.email');

    Route::post('/reset-password', [NewPasswordController::class, 'store'])
        ->name('password.store');

    // Opened from the email in a browser: authorized by its signature, not a token.
    Route::get('/email/verify/{id}/{hash}', VerifyEmailController::class)
        ->whereNumber('id')
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');

    // Require a valid JWT
    Route::middleware('auth:api')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
        Route::post('/refresh', [AuthController::class, 'refresh'])->name('token.refresh');
        Route::get('/me', [AuthController::class, 'me'])->name('me');

        Route::post('/email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
            ->middleware('throttle:6,1')
            ->name('verification.send');
    });
});

Route::middleware('auth:api')->group(function () {
    Route::controller(ProductController::class)->prefix('products')->name('products.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/', 'store')->name('store');
        Route::get('/{product}', 'show')->name('show');
        Route::put('/{product}', 'update')->name('update');
        Route::delete('/{product}', 'destroy')->name('destroy');
    });

    Route::controller(ProductPriceController::class)->prefix('products/{product}/prices')->name('products.prices.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/', 'store')->name('store');
    });

    Route::controller(CurrencyController::class)->prefix('currencies')->name('currencies.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/', 'store')->name('store');
        Route::get('/{currency}', 'show')->name('show');
        Route::put('/{currency}', 'update')->name('update');
        Route::delete('/{currency}', 'destroy')->name('destroy');
    });
});
