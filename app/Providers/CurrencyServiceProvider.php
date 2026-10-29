<?php

namespace App\Providers;

use App\Domain\Currency\Repositories\CurrencyRepository;
use App\Infrastructure\Currency\Persistence\CachedCurrencyRepository;
use App\Infrastructure\Currency\Persistence\EloquentCurrencyRepository;
use App\Infrastructure\Shared\Cache\GenerationalCache;
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
            $app->make(GenerationalCache::class),
            listingsTtl: (int) config('cache.ttl.listings'),
            resourcesTtl: (int) config('cache.ttl.resources'),
        ));
    }
}
