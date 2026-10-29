<?php

namespace App\Application\Currency\UseCases;

use App\Application\Currency\DTOs\CurrencyData;
use App\Domain\Currency\Entities\Currency;
use App\Domain\Currency\Exceptions\CurrencyNameAlreadyTaken;
use App\Domain\Currency\Exceptions\CurrencyNotFound;
use App\Domain\Currency\Repositories\CurrencyRepository;
use App\Domain\Currency\ValueObjects\CurrencyId;

final class UpdateCurrency
{
    public function __construct(
        private CurrencyRepository $currencies,
    ) {}

    /**
     * @throws CurrencyNotFound
     * @throws CurrencyNameAlreadyTaken
     */
    public function execute(CurrencyId $id, CurrencyData $data): Currency
    {
        $currency = $this->currencies->findById($id) ?? throw new CurrencyNotFound($id);

        if ($this->currencies->existsByName($data->name, except: $id)) {
            throw new CurrencyNameAlreadyTaken($data->name);
        }

        $currency->update($data->name, $data->symbol, $data->exchangeRate);

        return $this->currencies->save($currency);
    }
}
