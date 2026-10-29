<?php

namespace App\Application\Currency\DTOs;

use App\Domain\Currency\ValueObjects\CurrencyName;
use App\Domain\Currency\ValueObjects\CurrencySymbol;
use App\Domain\Currency\ValueObjects\ExchangeRate;

final readonly class CurrencyData
{
    public function __construct(
        public CurrencyName $name,
        public CurrencySymbol $symbol,
        public ExchangeRate $exchangeRate,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            name: new CurrencyName($data['name']),
            symbol: new CurrencySymbol($data['symbol']),
            exchangeRate: new ExchangeRate($data['exchange_rate']),
        );
    }
}
