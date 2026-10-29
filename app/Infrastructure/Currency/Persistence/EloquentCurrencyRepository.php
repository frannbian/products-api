<?php

namespace App\Infrastructure\Currency\Persistence;

use App\Domain\Currency\Entities\Currency;
use App\Domain\Currency\Exceptions\CurrencyInUse;
use App\Domain\Currency\Exceptions\CurrencyNameAlreadyTaken;
use App\Domain\Currency\Repositories\CurrencyRepository;
use App\Domain\Currency\ValueObjects\CurrencyId;
use App\Domain\Currency\ValueObjects\CurrencyName;
use App\Domain\Shared\Exceptions\ConcurrencyConflict;
use App\Domain\Shared\Paginated;
use App\Infrastructure\Currency\Persistence\Eloquent\CurrencyMapper;
use App\Infrastructure\Currency\Persistence\Eloquent\CurrencyModel;
use App\Infrastructure\Shared\Persistence\PostgresError;
use Illuminate\Database\QueryException;
use Illuminate\Database\UniqueConstraintViolationException;

final class EloquentCurrencyRepository implements CurrencyRepository
{
    public function paginate(int $page, int $perPage): Paginated
    {
        $paginator = CurrencyModel::query()
            ->orderBy('id')
            ->paginate(perPage: $perPage, page: $page);

        return new Paginated(
            $paginator->getCollection()->map(CurrencyMapper::toDomain(...))->all(),
            $paginator->total(),
            $paginator->currentPage(),
            $paginator->perPage(),
        );
    }

    public function findById(CurrencyId $id): ?Currency
    {
        $model = CurrencyModel::find($id->value());

        return $model ? CurrencyMapper::toDomain($model) : null;
    }

    public function exists(CurrencyId $id): bool
    {
        return CurrencyModel::query()->whereKey($id->value())->exists();
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
        try {
            return $this->write($currency);
        } catch (UniqueConstraintViolationException) {
            // Another request took the name between the check and the write.
            throw new CurrencyNameAlreadyTaken($currency->name());
        }
    }

    /**
     * @throws ConcurrencyConflict
     */
    private function write(Currency $currency): Currency
    {
        if ($currency->id() === null) {
            return CurrencyMapper::toDomain(
                CurrencyModel::query()->forceCreate([...CurrencyMapper::attributes($currency), 'version' => 1])
            );
        }

        $id = $currency->id()->value();

        // Optimistic locking: only written if the row still has the version that was read.
        $updated = CurrencyModel::query()
            ->whereKey($id)
            ->where('version', $currency->version())
            ->update([...CurrencyMapper::attributes($currency), 'version' => $currency->version() + 1]);

        if ($updated === 0) {
            throw new ConcurrencyConflict('currency', $id);
        }

        return CurrencyMapper::toDomain(CurrencyModel::query()->findOrFail($id));
    }

    public function delete(CurrencyId $id): void
    {
        try {
            CurrencyModel::query()->whereKey($id->value())->delete();
        } catch (QueryException $e) {
            // A product started using it after the in-use check: the foreign key wins.
            throw PostgresError::isForeignKeyViolation($e) ? new CurrencyInUse($id) : $e;
        }
    }
}
