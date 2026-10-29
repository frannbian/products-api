<?php

namespace App\Application\Catalog\Concerns;

use App\Application\Catalog\Exceptions\UnknownCurrency;
use App\Domain\Currency\Repositories\CurrencyRepository;
use App\Domain\Currency\ValueObjects\CurrencyId;

/**
 * @property CurrencyRepository $currencies
 */
trait EnsuresCurrencyExists
{
    /**
     * @throws UnknownCurrency
     */
    private function ensureCurrencyExists(CurrencyId $id): void
    {
        if ($this->currencies->findById($id) === null) {
            throw new UnknownCurrency($id);
        }
    }
}
