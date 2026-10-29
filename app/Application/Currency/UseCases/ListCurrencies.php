<?php

namespace App\Application\Currency\UseCases;

use App\Domain\Currency\Entities\Currency;
use App\Domain\Currency\Repositories\CurrencyRepository;

final class ListCurrencies
{
    public function __construct(
        private CurrencyRepository $currencies,
    ) {}

    /**
     * @return list<Currency>
     */
    public function execute(): array
    {
        return $this->currencies->all();
    }
}
