<?php

namespace App\Application\Currency\UseCases;

use App\Domain\Currency\Entities\Currency;
use App\Domain\Currency\Repositories\CurrencyRepository;
use App\Domain\Shared\Paginated;

final class ListCurrencies
{
    public function __construct(
        private CurrencyRepository $currencies,
    ) {}

    /**
     * @return Paginated<Currency>
     */
    public function execute(int $page, int $perPage): Paginated
    {
        return $this->currencies->paginate($page, $perPage);
    }
}
