<?php

namespace App\Infrastructure\Currency\Persistence\Eloquent;

use App\Domain\Currency\Entities\Currency;
use App\Domain\Currency\ValueObjects\CurrencyId;
use App\Domain\Currency\ValueObjects\CurrencyName;
use App\Domain\Currency\ValueObjects\CurrencySymbol;
use App\Domain\Currency\ValueObjects\ExchangeRate;

final class CurrencyMapper
{
    public static function toDomain(CurrencyModel $model): Currency
    {
        return Currency::reconstitute(
            new CurrencyId($model->id),
            new CurrencyName($model->name),
            new CurrencySymbol($model->symbol),
            new ExchangeRate($model->exchange_rate),
        );
    }

    /**
     * @return array<string, string>
     */
    public static function attributes(Currency $currency): array
    {
        return [
            'name' => $currency->name()->value(),
            'symbol' => $currency->symbol()->value(),
            'exchange_rate' => $currency->exchangeRate()->value(),
        ];
    }
}
