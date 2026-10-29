<?php

namespace App\Providers;

use App\Domain\Currency\Repositories\CurrencyRepository;
use App\Infrastructure\Currency\Persistence\CachedCurrencyRepository;
use App\Infrastructure\Currency\Persistence\EloquentCurrencyRepository;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Support\ServiceProvider;

/**
 * Wires the Currency bounded context.
 */
class CurrencyServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->bind(CurrencyRepository::class, fn ($app) => new CachedCurrencyRepository(
            $app->make(EloquentCurrencyRepository::class),
            $app->make(Cache::class),
        ));
    }
}
