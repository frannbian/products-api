<?php

namespace Tests\Support;

use App\Domain\Catalog\Entities\Product;
use App\Domain\Catalog\Repositories\ProductRepository;
use App\Domain\Catalog\ValueObjects\ProductId;
use App\Domain\Currency\ValueObjects\CurrencyId;
use App\Domain\Shared\Exceptions\ConcurrencyConflict;
use App\Domain\Shared\Paginated;

/**
 * Simulates other requests modifying the product: the first saves fail with
 * a concurrency conflict, then it behaves like the wrapped repository.
 */
final class ConflictingProductRepository implements ProductRepository
{
    public int $attempts = 0;

    public function __construct(
        private ProductRepository $inner,
        private int $conflicts,
    ) {}

    public function save(Product $product): Product
    {
        if (++$this->attempts <= $this->conflicts) {
            throw new ConcurrencyConflict('product', $product->id()->value());
        }

        return $this->inner->save($product);
    }

    public function paginate(int $page, int $perPage): Paginated
    {
        return $this->inner->paginate($page, $perPage);
    }

    public function findById(ProductId $id): ?Product
    {
        return $this->inner->findById($id);
    }

    public function exists(ProductId $id): bool
    {
        return $this->inner->exists($id);
    }

    public function paginatePrices(ProductId $id, int $page, int $perPage): Paginated
    {
        return $this->inner->paginatePrices($id, $page, $perPage);
    }

    public function delete(ProductId $id): void
    {
        $this->inner->delete($id);
    }

    public function existsWithCurrency(CurrencyId $id): bool
    {
        return $this->inner->existsWithCurrency($id);
    }
}
