<?php

namespace App\Infrastructure\Currency\Persistence\Cache;

use App\Domain\Currency\Entities\Currency;
use App\Domain\Currency\ValueObjects\CurrencyId;
use App\Domain\Currency\ValueObjects\CurrencyName;
use App\Domain\Currency\ValueObjects\CurrencySymbol;
use App\Domain\Currency\ValueObjects\ExchangeRate;

/**
 * Plain array form of the Currency aggregate, for the cache.
 */
final class CurrencySnapshot
{
    /**
     * @return array{id: int, name: string, symbol: string, exchange_rate: string}
     */
    public static function toArray(Currency $currency): array
    {
        return [
            'id' => $currency->id()->value(),
            'name' => $currency->name()->value(),
            'symbol' => $currency->symbol()->value(),
            'exchange_rate' => $currency->exchangeRate()->value(),
        ];
    }

    /**
     * @param  array{id: int, name: string, symbol: string, exchange_rate: string}  $data
     */
    public static function fromArray(array $data): Currency
    {
        return Currency::reconstitute(
            new CurrencyId($data['id']),
            new CurrencyName($data['name']),
            new CurrencySymbol($data['symbol']),
            new ExchangeRate($data['exchange_rate']),
        );
    }
}
