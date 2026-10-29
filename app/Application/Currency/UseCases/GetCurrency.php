<?php

namespace App\Application\Currency\UseCases;

use App\Domain\Currency\Entities\Currency;
use App\Domain\Currency\Exceptions\CurrencyNotFound;
use App\Domain\Currency\Repositories\CurrencyRepository;
use App\Domain\Currency\ValueObjects\CurrencyId;

final class GetCurrency
{
    public function __construct(
        private CurrencyRepository $currencies,
    ) {}

    /**
     * @throws CurrencyNotFound
     */
    public function execute(CurrencyId $id): Currency
    {
        return $this->currencies->findById($id) ?? throw new CurrencyNotFound($id);
    }
}
