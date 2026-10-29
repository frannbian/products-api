<?php

namespace Tests\Support;

use App\Domain\Currency\Entities\Currency;
use App\Domain\Currency\Repositories\CurrencyRepository;
use App\Domain\Currency\ValueObjects\CurrencyId;
use App\Domain\Currency\ValueObjects\CurrencyName;
use App\Domain\Currency\ValueObjects\CurrencySymbol;
use App\Domain\Currency\ValueObjects\ExchangeRate;
use App\Domain\Shared\Exceptions\ConcurrencyConflict;
use App\Domain\Shared\Paginated;

final class InMemoryCurrencyRepository implements CurrencyRepository
{
    /** @var array<int, Currency> */
    private array $currencies = [];

    private int $nextId = 1;

    public function paginate(int $page, int $perPage): Paginated
    {
        $all = array_values($this->currencies);

        return new Paginated(array_slice($all, ($page - 1) * $perPage, $perPage), count($all), $page, $perPage);
    }

    public function findById(CurrencyId $id): ?Currency
    {
        return $this->currencies[$id->value()] ?? null;
    }

    public function exists(CurrencyId $id): bool
    {
        return isset($this->currencies[$id->value()]);
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
        if ($currency->id() !== null && $this->currencies[$currency->id()->value()]?->version() !== $currency->version()) {
            throw new ConcurrencyConflict('currency', $currency->id()->value());
        }

        return $this->currencies[$currency->id()?->value() ?? $this->nextId] = Currency::reconstitute(
            $currency->id() ?? new CurrencyId($this->nextId++),
            $currency->name(),
            $currency->symbol(),
            $currency->exchangeRate(),
            $currency->id() === null ? 1 : $currency->version() + 1,
        );
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
