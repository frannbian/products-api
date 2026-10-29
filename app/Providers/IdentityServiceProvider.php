<?php

namespace App\Providers;

use App\Application\Identity\Contracts\EmailVerificationNotifier;
use App\Application\Identity\Contracts\PasswordResetBroker;
use App\Application\Identity\Contracts\TokenService;
use App\Domain\Identity\Repositories\UserRepository;
use App\Domain\Identity\Services\PasswordHasher;
use App\Infrastructure\Identity\Authentication\JwtTokenService;
use App\Infrastructure\Identity\Authentication\LaravelPasswordResetBroker;
use App\Infrastructure\Identity\Notifications\LaravelEmailVerificationNotifier;
use App\Infrastructure\Identity\Persistence\EloquentUserRepository;
use App\Infrastructure\Identity\Security\LaravelPasswordHasher;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\ServiceProvider;

/**
 * Wires the Identity bounded context: binds the domain and application
 * ports to their infrastructure adapters.
 */
class IdentityServiceProvider extends ServiceProvider
{
    /**
     * All of the container bindings that should be registered.
     *
     * @var array<class-string, class-string>
     */
    public array $bindings = [
        UserRepository::class => EloquentUserRepository::class,
        PasswordHasher::class => LaravelPasswordHasher::class,
        TokenService::class => JwtTokenService::class,
        PasswordResetBroker::class => LaravelPasswordResetBroker::class,
        EmailVerificationNotifier::class => LaravelEmailVerificationNotifier::class,
    ];

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        ResetPassword::createUrlUsing(function (object $notifiable, string $token) {
            return config('app.frontend_url')."/password-reset/$token?email={$notifiable->getEmailForPasswordReset()}";
        });
    }
}
