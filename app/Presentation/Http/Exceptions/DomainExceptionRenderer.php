<?php

namespace App\Presentation\Http\Exceptions;

use App\Application\Catalog\Exceptions\UnknownCurrency;
use App\Application\Identity\Exceptions\InvalidVerificationLink;
use App\Application\Identity\Exceptions\PasswordResetFailed;
use App\Domain\Catalog\Exceptions\PriceInBaseCurrency;
use App\Domain\Catalog\Exceptions\ProductNotFound;
use App\Domain\Currency\Exceptions\CurrencyInUse;
use App\Domain\Currency\Exceptions\CurrencyNameAlreadyTaken;
use App\Domain\Currency\Exceptions\CurrencyNotFound;
use App\Domain\Identity\Exceptions\EmailAlreadyRegistered;
use App\Presentation\Http\Messages\PasswordResetMessage;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Translates domain and application exceptions into HTTP errors. The inner
 * layers know nothing about HTTP; this is the only place where they meet.
 */
final class DomainExceptionRenderer
{
    public static function register(Exceptions $exceptions): void
    {
        // 404 Not Found
        $exceptions->map(ProductNotFound::class, fn (ProductNotFound $e) => new NotFoundHttpException(__('catalog.product_not_found'), $e));
        $exceptions->map(CurrencyNotFound::class, fn (CurrencyNotFound $e) => new NotFoundHttpException(__('currency.not_found'), $e));

        // 409 Conflict
        $exceptions->map(CurrencyInUse::class, fn (CurrencyInUse $e) => new ConflictHttpException(__('currency.in_use'), $e));

        // 403 Forbidden
        $exceptions->map(InvalidVerificationLink::class, fn (InvalidVerificationLink $e) => new AccessDeniedHttpException(__('auth.verification_link_invalid'), $e));

        // 422 Unprocessable: business rules reported on the field that broke them
        $exceptions->map(UnknownCurrency::class, fn () => ValidationException::withMessages([
            'currency_id' => [__('validation.exists', ['attribute' => 'currency id'])],
        ]));
        $exceptions->map(PriceInBaseCurrency::class, fn () => ValidationException::withMessages([
            'currency_id' => [__('catalog.price_in_base_currency')],
        ]));
        $exceptions->map(CurrencyNameAlreadyTaken::class, fn () => ValidationException::withMessages([
            'name' => [__('validation.unique', ['attribute' => 'name'])],
        ]));
        $exceptions->map(EmailAlreadyRegistered::class, fn () => ValidationException::withMessages([
            'email' => [__('validation.unique', ['attribute' => 'email'])],
        ]));
        $exceptions->map(PasswordResetFailed::class, fn (PasswordResetFailed $e) => ValidationException::withMessages([
            'email' => [PasswordResetMessage::for($e->status)],
        ]));
    }
}
