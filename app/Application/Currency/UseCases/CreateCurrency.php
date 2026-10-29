<?php

namespace App\Application\Currency\UseCases;

use App\Application\Currency\DTOs\CurrencyData;
use App\Domain\Currency\Entities\Currency;
use App\Domain\Currency\Exceptions\CurrencyNameAlreadyTaken;
use App\Domain\Currency\Repositories\CurrencyRepository;

final class CreateCurrency
{
    public function __construct(
        private CurrencyRepository $currencies,
    ) {}

    /**
     * @throws CurrencyNameAlreadyTaken
     */
    public function execute(CurrencyData $data): Currency
    {
        if ($this->currencies->existsByName($data->name)) {
            throw new CurrencyNameAlreadyTaken($data->name);
        }

        return $this->currencies->save(
            Currency::create($data->name, $data->symbol, $data->exchangeRate)
        );
    }
}
