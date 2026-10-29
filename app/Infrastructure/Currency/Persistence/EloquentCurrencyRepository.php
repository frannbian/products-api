<?php

namespace App\Infrastructure\Currency\Persistence;

use App\Domain\Currency\Entities\Currency;
use App\Domain\Currency\Repositories\CurrencyRepository;
use App\Domain\Currency\ValueObjects\CurrencyId;
use App\Domain\Currency\ValueObjects\CurrencyName;
use App\Infrastructure\Currency\Persistence\Eloquent\CurrencyMapper;
use App\Infrastructure\Currency\Persistence\Eloquent\CurrencyModel;

final class EloquentCurrencyRepository implements CurrencyRepository
{
    public function all(): array
    {
        return CurrencyModel::query()
            ->orderBy('id')
            ->get()
            ->map(CurrencyMapper::toDomain(...))
            ->all();
    }

    public function findById(CurrencyId $id): ?Currency
    {
        $model = CurrencyModel::find($id->value());

        return $model ? CurrencyMapper::toDomain($model) : null;
    }

    public function existsByName(CurrencyName $name, ?CurrencyId $except = null): bool
    {
        return CurrencyModel::query()
            ->where('name', $name->value())
            ->when($except, fn ($query) => $query->whereKeyNot($except->value()))
            ->exists();
    }

    public function save(Currency $currency): Currency
    {
        $model = $currency->id() === null
            ? new CurrencyModel
            : CurrencyModel::findOrFail($currency->id()->value());

        CurrencyMapper::fill($model, $currency)->save();

        return $currency->id() === null ? CurrencyMapper::toDomain($model) : $currency;
    }

    public function delete(CurrencyId $id): void
    {
        CurrencyModel::query()->whereKey($id->value())->delete();
    }
}
