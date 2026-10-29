<?php

namespace App\Domain\Catalog\Repositories;

use App\Domain\Catalog\Entities\Product;
use App\Domain\Catalog\Entities\ProductPrice;
use App\Domain\Catalog\ValueObjects\ProductId;
use App\Domain\Currency\ValueObjects\CurrencyId;
use App\Domain\Shared\Exceptions\ConcurrencyConflict;
use App\Domain\Shared\Paginated;

interface ProductRepository
{
    /**
     * @return Paginated<Product>
     */
    public function paginate(int $page, int $perPage): Paginated;

    public function findById(ProductId $id): ?Product;

    public function exists(ProductId $id): bool;

    /**
     * @return Paginated<ProductPrice>
     */
    public function paginatePrices(ProductId $id, int $page, int $perPage): Paginated;

    /**
     * Persists the product with its prices and returns it with every
     * identity assigned and its version incremented.
     *
     * @throws ConcurrencyConflict when the stored version is not the one that was read.
     */
    public function save(Product $product): Product;

    public function delete(ProductId $id): void;

    /**
     * Whether any product, deleted ones included, uses the currency as its
     * base currency or has a price in it.
     */
    public function existsWithCurrency(CurrencyId $id): bool;
}
