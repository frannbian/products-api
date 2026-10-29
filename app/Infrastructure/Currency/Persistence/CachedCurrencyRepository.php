<?php

namespace App\Infrastructure\Currency\Persistence;

use App\Domain\Currency\Entities\Currency;
use App\Domain\Currency\Repositories\CurrencyRepository;
use App\Domain\Currency\ValueObjects\CurrencyId;
use App\Domain\Currency\ValueObjects\CurrencyName;
use App\Domain\Shared\Paginated;
use Illuminate\Contracts\Cache\Repository as Cache;

/**
 * Caches the existence check that every product and price write performs.
 *
 * Only that check is cached, and only positive answers: currencies are
 * rarely deleted, and if the cache lags behind a deletion the foreign key
 * still rejects the write. Entities are never cached, so optimistic locking
 * always compares against the real stored version.
 */
final class CachedCurrencyRepository implements CurrencyRepository
{
    private const TTL_SECONDS = 3600;

    public function __construct(
        private CurrencyRepository $inner,
        private Cache $cache,
    ) {}

    public function exists(CurrencyId $id): bool
    {
        if ($this->cache->has($this->key($id))) {
            return true;
        }

        $exists = $this->inner->exists($id);

        if ($exists) {
            $this->cache->put($this->key($id), true, self::TTL_SECONDS);
        }

        return $exists;
    }

    public function delete(CurrencyId $id): void
    {
        $this->inner->delete($id);

        $this->cache->forget($this->key($id));
    }

    public function paginate(int $page, int $perPage): Paginated
    {
        return $this->inner->paginate($page, $perPage);
    }

    public function findById(CurrencyId $id): ?Currency
    {
        return $this->inner->findById($id);
    }

    public function existsByName(CurrencyName $name, ?CurrencyId $except = null): bool
    {
        return $this->inner->existsByName($name, $except);
    }

    public function save(Currency $currency): Currency
    {
        return $this->inner->save($currency);
    }

    private function key(CurrencyId $id): string
    {
        return "currencies:{$id->value()}:exists";
    }
}
