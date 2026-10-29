<?php

namespace App\Domain\Currency\Entities;

use App\Domain\Currency\ValueObjects\CurrencyId;
use App\Domain\Currency\ValueObjects\CurrencyName;
use App\Domain\Currency\ValueObjects\CurrencySymbol;
use App\Domain\Currency\ValueObjects\ExchangeRate;

final class Currency
{
    private function __construct(
        private ?CurrencyId $id,
        private CurrencyName $name,
        private CurrencySymbol $symbol,
        private ExchangeRate $exchangeRate,
    ) {}

    public static function create(CurrencyName $name, CurrencySymbol $symbol, ExchangeRate $exchangeRate): self
    {
        return new self(null, $name, $symbol, $exchangeRate);
    }

    public static function reconstitute(
        CurrencyId $id,
        CurrencyName $name,
        CurrencySymbol $symbol,
        ExchangeRate $exchangeRate,
    ): self {
        return new self($id, $name, $symbol, $exchangeRate);
    }

    public function update(CurrencyName $name, CurrencySymbol $symbol, ExchangeRate $exchangeRate): void
    {
        $this->name = $name;
        $this->symbol = $symbol;
        $this->exchangeRate = $exchangeRate;
    }

    public function id(): ?CurrencyId
    {
        return $this->id;
    }

    public function name(): CurrencyName
    {
        return $this->name;
    }

    public function symbol(): CurrencySymbol
    {
        return $this->symbol;
    }

    public function exchangeRate(): ExchangeRate
    {
        return $this->exchangeRate;
    }
}
