<?php

namespace App\Domain\Currency\Repositories;

use App\Domain\Currency\Entities\Currency;
use App\Domain\Currency\ValueObjects\CurrencyId;
use App\Domain\Currency\ValueObjects\CurrencyName;

interface CurrencyRepository
{
    /**
     * @return list<Currency>
     */
    public function all(): array;

    public function findById(CurrencyId $id): ?Currency;

    public function existsByName(CurrencyName $name, ?CurrencyId $except = null): bool;

    /**
     * Persists the currency and returns it with its identity assigned.
     */
    public function save(Currency $currency): Currency;

    public function delete(CurrencyId $id): void;
}
