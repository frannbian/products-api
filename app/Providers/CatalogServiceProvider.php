<?php

namespace App\Providers;

use App\Domain\Catalog\Repositories\ProductRepository;
use App\Infrastructure\Catalog\Persistence\CachedProductRepository;
use App\Infrastructure\Catalog\Persistence\EloquentProductRepository;
use App\Infrastructure\Shared\Cache\GenerationalCache;
use Illuminate\Support\ServiceProvider;

/**
 * Wires the Catalog bounded context.
 */
class CatalogServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->bind(ProductRepository::class, fn ($app) => new CachedProductRepository(
            $app->make(EloquentProductRepository::class),
            $app->make(GenerationalCache::class),
            listingsTtl: (int) config('cache.ttl.listings'),
            resourcesTtl: (int) config('cache.ttl.resources'),
        ));
    }
}
