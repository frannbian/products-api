<?php

namespace App\Infrastructure\Currency\Persistence;

use App\Domain\Currency\Entities\Currency;
use App\Domain\Currency\Repositories\CurrencyRepository;
use App\Domain\Currency\ValueObjects\CurrencyId;
use App\Domain\Currency\ValueObjects\CurrencyName;
use App\Domain\Shared\Paginated;
use App\Infrastructure\Currency\Persistence\Cache\CurrencySnapshot;
use App\Infrastructure\Shared\Cache\GenerationalCache;
use App\Infrastructure\Shared\Cache\PaginatedSnapshot;

/**
 * Serves currency reads from the cache and renews them on every write.
 *
 * Currencies change rarely and are read on every product write (to check the
 * currency exists), so they are a good fit for the cache. Locking reads and
 * the name uniqueness check always hit the database.
 */
final class CachedCurrencyRepository implements CurrencyRepository
{
    private const NAMESPACE = 'currencies';

    public function __construct(
        private CurrencyRepository $inner,
        private GenerationalCache $cache,
        private int $listingsTtl,
        private int $resourcesTtl,
    ) {}

    public function paginate(int $page, int $perPage): Paginated
    {
        return PaginatedSnapshot::fromArray(
            $this->cache->remember(self::NAMESPACE, "page:{$page}:{$perPage}", $this->listingsTtl, fn () => PaginatedSnapshot::toArray(
                $this->inner->paginate($page, $perPage),
                CurrencySnapshot::toArray(...),
            )),
            CurrencySnapshot::fromArray(...),
        );
    }

    public function findById(CurrencyId $id): ?Currency
    {
        $data = $this->cache->remember(self::NAMESPACE, "currency:{$id->value()}", $this->resourcesTtl, function () use ($id) {
            $currency = $this->inner->findById($id);

            return $currency ? CurrencySnapshot::toArray($currency) : null;
        });

        return $data ? CurrencySnapshot::fromArray($data) : null;
    }

    public function findByIdForUpdate(CurrencyId $id): ?Currency
    {
        return $this->inner->findByIdForUpdate($id);
    }

    public function exists(CurrencyId $id): bool
    {
        return $this->cache->remember(self::NAMESPACE, "exists:{$id->value()}", $this->resourcesTtl, fn () => $this->inner->exists($id));
    }

    public function existsByName(CurrencyName $name, ?CurrencyId $except = null): bool
    {
        // Uniqueness must be checked against the current data.
        return $this->inner->existsByName($name, $except);
    }

    public function save(Currency $currency): Currency
    {
        $saved = $this->inner->save($currency);

        $this->cache->invalidate(self::NAMESPACE);

        return $saved;
    }

    public function delete(CurrencyId $id): void
    {
        $this->inner->delete($id);

        $this->cache->invalidate(self::NAMESPACE);
    }
}
