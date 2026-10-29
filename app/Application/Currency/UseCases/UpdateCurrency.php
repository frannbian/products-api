<?php

namespace App\Application\Currency\UseCases;

use App\Application\Currency\DTOs\CurrencyData;
use App\Application\Shared\Exceptions\StaleVersion;
use App\Domain\Currency\Entities\Currency;
use App\Domain\Currency\Exceptions\CurrencyNameAlreadyTaken;
use App\Domain\Currency\Exceptions\CurrencyNotFound;
use App\Domain\Currency\Repositories\CurrencyRepository;
use App\Domain\Currency\ValueObjects\CurrencyId;
use App\Domain\Shared\Exceptions\ConcurrencyConflict;

final class UpdateCurrency
{
    public function __construct(
        private CurrencyRepository $currencies,
    ) {}

    /**
     * @param  int|null  $expectedVersion  Version the client read; null skips the check.
     *
     * @throws CurrencyNotFound
     * @throws StaleVersion
     * @throws CurrencyNameAlreadyTaken
     * @throws ConcurrencyConflict
     */
    public function execute(CurrencyId $id, CurrencyData $data, ?int $expectedVersion = null): Currency
    {
        $currency = $this->currencies->findById($id) ?? throw new CurrencyNotFound($id);

        StaleVersion::check($expectedVersion, $currency->version());

        if ($this->currencies->existsByName($data->name, except: $id)) {
            throw new CurrencyNameAlreadyTaken($data->name);
        }

        $currency->update($data->name, $data->symbol, $data->exchangeRate);

        return $this->currencies->save($currency);
    }
}
