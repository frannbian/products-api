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
        private int $version,
    ) {}

    public static function create(CurrencyName $name, CurrencySymbol $symbol, ExchangeRate $exchangeRate): self
    {
        return new self(null, $name, $symbol, $exchangeRate, 1);
    }

    public static function reconstitute(
        CurrencyId $id,
        CurrencyName $name,
        CurrencySymbol $symbol,
        ExchangeRate $exchangeRate,
        int $version = 1,
    ): self {
        return new self($id, $name, $symbol, $exchangeRate, $version);
    }

    public function update(CurrencyName $name, CurrencySymbol $symbol, ExchangeRate $exchangeRate): void
    {
        $this->name = $name;
        $this->symbol = $symbol;
        $this->exchangeRate = $exchangeRate;
    }

    /**
     * Optimistic locking version: incremented every time the currency is saved.
     */
    public function version(): int
    {
        return $this->version;
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
