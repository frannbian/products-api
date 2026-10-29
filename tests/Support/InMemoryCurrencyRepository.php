<?php

namespace Tests\Support;

use App\Domain\Currency\Entities\Currency;
use App\Domain\Currency\Repositories\CurrencyRepository;
use App\Domain\Currency\ValueObjects\CurrencyId;
use App\Domain\Currency\ValueObjects\CurrencyName;
use App\Domain\Currency\ValueObjects\CurrencySymbol;
use App\Domain\Currency\ValueObjects\ExchangeRate;

final class InMemoryCurrencyRepository implements CurrencyRepository
{
    /** @var array<int, Currency> */
    private array $currencies = [];

    private int $nextId = 1;

    public function all(): array
    {
        return array_values($this->currencies);
    }

    public function findById(CurrencyId $id): ?Currency
    {
        return $this->currencies[$id->value()] ?? null;
    }

    public function existsByName(CurrencyName $name, ?CurrencyId $except = null): bool
    {
        foreach ($this->currencies as $currency) {
            if ($currency->name()->value() === $name->value()
                && ($except === null || ! $currency->id()->equals($except))) {
                return true;
            }
        }

        return false;
    }

    public function save(Currency $currency): Currency
    {
        if ($currency->id() === null) {
            $currency = Currency::reconstitute(
                new CurrencyId($this->nextId++),
                $currency->name(),
                $currency->symbol(),
                $currency->exchangeRate(),
            );
        }

        return $this->currencies[$currency->id()->value()] = $currency;
    }

    public function delete(CurrencyId $id): void
    {
        unset($this->currencies[$id->value()]);
    }

    public function add(string $name, string $symbol = '$', string $rate = '1'): Currency
    {
        return $this->save(Currency::create(new CurrencyName($name), new CurrencySymbol($symbol), new ExchangeRate($rate)));
    }
}
