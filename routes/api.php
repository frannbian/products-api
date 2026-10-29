<?php

use App\Presentation\Http\Controllers\Auth\AuthController;
use App\Presentation\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Presentation\Http\Controllers\Auth\NewPasswordController;
use App\Presentation\Http\Controllers\Auth\PasswordResetLinkController;
use App\Presentation\Http\Controllers\Auth\VerifyEmailController;
use Illuminate\Support\Facades\Route;

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
