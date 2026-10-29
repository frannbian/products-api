<?php

use App\Application\Identity\Exceptions\InvalidVerificationLink;
use App\Application\Identity\Exceptions\PasswordResetFailed;
use App\Domain\Identity\Exceptions\EmailAlreadyRegistered;
use App\Presentation\Http\Messages\PasswordResetMessage;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        $exceptions->map(
            PasswordResetFailed::class,
            fn (PasswordResetFailed $e) => ValidationException::withMessages([
                'email' => [PasswordResetMessage::for($e->status)],
            ]),
        );

        $exceptions->map(
            EmailAlreadyRegistered::class,
            fn () => ValidationException::withMessages([
                'email' => [__('validation.unique', ['attribute' => 'email'])],
            ]),
        );

        $exceptions->map(
            InvalidVerificationLink::class,
            fn (InvalidVerificationLink $e) => new AccessDeniedHttpException(__('auth.verification_link_invalid'), $e),
        );
    })->create();
