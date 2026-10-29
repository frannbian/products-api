<?php

namespace App\Providers;

use App\Domain\Catalog\Repositories\ProductRepository;
use App\Infrastructure\Catalog\Persistence\EloquentProductRepository;
use Illuminate\Support\ServiceProvider;

/**
 * Wires the Catalog bounded context.
 */
class CatalogServiceProvider extends ServiceProvider
{
    /**
     * All of the container bindings that should be registered.
     *
     * @var array<class-string, class-string>
     */
    public array $bindings = [
        ProductRepository::class => EloquentProductRepository::class,
    ];
}
