<?php

namespace App\Providers;

use App\Domain\Identity\Repositories\UserRepository;
use App\Domain\Identity\Services\PasswordHasher;
use App\Domain\Identity\Services\TokenService;
use App\Infrastructure\Identity\Authentication\SanctumTokenService;
use App\Infrastructure\Identity\Persistence\EloquentUserRepository;
use App\Infrastructure\Identity\Security\LaravelPasswordHasher;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(
            UserRepository::class,
            EloquentUserRepository::class,
        );

        $this->app->bind(
            PasswordHasher::class,
            LaravelPasswordHasher::class,
        );

        $this->app->bind(
            TokenService::class,
            SanctumTokenService::class,
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        ResetPassword::createUrlUsing(function (object $notifiable, string $token) {
            return config('app.frontend_url')."/password-reset/$token?email={$notifiable->getEmailForPasswordReset()}";
        });
    
    }
}
