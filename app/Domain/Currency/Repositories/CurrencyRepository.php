<?php

namespace App\Domain\Currency\Repositories;

use App\Domain\Currency\Entities\Currency;
use App\Domain\Currency\ValueObjects\CurrencyId;
use App\Domain\Currency\ValueObjects\CurrencyName;
use App\Domain\Shared\Paginated;

interface CurrencyRepository
{
    /**
     * @return Paginated<Currency>
     */
    public function paginate(int $page, int $perPage): Paginated;

    public function findById(CurrencyId $id): ?Currency;

    /**
     * Loads the currency and locks it until the current transaction ends.
     */
    public function findByIdForUpdate(CurrencyId $id): ?Currency;

    public function exists(CurrencyId $id): bool;

    public function existsByName(CurrencyName $name, ?CurrencyId $except = null): bool;

    /**
     * Persists the currency and returns it with its identity assigned.
     */
    public function save(Currency $currency): Currency;

    public function delete(CurrencyId $id): void;
}
