<?php

namespace App\Infrastructure\Catalog\Persistence;

use App\Domain\Catalog\Entities\Product;
use App\Domain\Catalog\Repositories\ProductRepository;
use App\Domain\Catalog\ValueObjects\ProductId;
use App\Domain\Currency\ValueObjects\CurrencyId;
use App\Domain\Shared\Paginated;
use App\Infrastructure\Catalog\Persistence\Cache\ProductSnapshot;
use App\Infrastructure\Shared\Cache\GenerationalCache;
use App\Infrastructure\Shared\Cache\PaginatedSnapshot;

/**
 * Serves product reads from the cache and renews them on every write.
 *
 * Entries live until their TTL expires or until any product is written: a
 * write renews the whole namespace once its transaction commits, since a
 * single product appears in listing pages that can not be enumerated.
 * Locking reads and the currency usage check always hit the database.
 */
final class CachedProductRepository implements ProductRepository
{
    private const NAMESPACE = 'products';

    public function __construct(
        private ProductRepository $inner,
        private GenerationalCache $cache,
        private int $listingsTtl,
        private int $resourcesTtl,
    ) {}

    public function paginate(int $page, int $perPage): Paginated
    {
        return PaginatedSnapshot::fromArray(
            $this->cache->remember(self::NAMESPACE, "page:{$page}:{$perPage}", $this->listingsTtl, fn () => PaginatedSnapshot::toArray(
                $this->inner->paginate($page, $perPage),
                ProductSnapshot::toArray(...),
            )),
            ProductSnapshot::fromArray(...),
        );
    }

    public function findById(ProductId $id): ?Product
    {
        // Missing products are not cached: remember() skips null values.
        $data = $this->cache->remember(self::NAMESPACE, "product:{$id->value()}", $this->resourcesTtl, function () use ($id) {
            $product = $this->inner->findById($id);

            return $product ? ProductSnapshot::toArray($product) : null;
        });

        return $data ? ProductSnapshot::fromArray($data) : null;
    }

    public function findByIdForUpdate(ProductId $id): ?Product
    {
        return $this->inner->findByIdForUpdate($id);
    }

    public function exists(ProductId $id): bool
    {
        return $this->cache->remember(self::NAMESPACE, "exists:{$id->value()}", $this->resourcesTtl, fn () => $this->inner->exists($id));
    }

    public function paginatePrices(ProductId $id, int $page, int $perPage): Paginated
    {
        return PaginatedSnapshot::fromArray(
            $this->cache->remember(self::NAMESPACE, "prices:{$id->value()}:{$page}:{$perPage}", $this->listingsTtl, fn () => PaginatedSnapshot::toArray(
                $this->inner->paginatePrices($id, $page, $perPage),
                ProductSnapshot::priceToArray(...),
            )),
            ProductSnapshot::priceFromArray(...),
        );
    }

    public function save(Product $product): Product
    {
        $saved = $this->inner->save($product);

        $this->cache->invalidate(self::NAMESPACE);

        return $saved;
    }

    public function delete(ProductId $id): void
    {
        $this->inner->delete($id);

        $this->cache->invalidate(self::NAMESPACE);
    }

    public function existsWithCurrency(CurrencyId $id): bool
    {
        // Guards deleting a currency, under its row lock: must be current.
        return $this->inner->existsWithCurrency($id);
    }
}
