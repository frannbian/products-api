<?php

namespace App\Providers;

use App\Domain\Currency\Repositories\CurrencyRepository;
use App\Infrastructure\Currency\Persistence\EloquentCurrencyRepository;
use Illuminate\Support\ServiceProvider;

/**
 * Wires the Currency bounded context.
 */
class CurrencyServiceProvider extends ServiceProvider
{
    /**
     * All of the container bindings that should be registered.
     *
     * @var array<class-string, class-string>
     */
    public array $bindings = [
        CurrencyRepository::class => EloquentCurrencyRepository::class,
    ];
}
