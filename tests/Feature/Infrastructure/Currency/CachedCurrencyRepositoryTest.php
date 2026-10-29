<?php

use App\Domain\Currency\Repositories\CurrencyRepository;
use App\Domain\Currency\ValueObjects\CurrencyId;
use App\Infrastructure\Currency\Persistence\CachedCurrencyRepository;
use App\Infrastructure\Currency\Persistence\Eloquent\CurrencyModel;
use App\Infrastructure\Currency\Persistence\EloquentCurrencyRepository;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->repository = new CachedCurrencyRepository(new EloquentCurrencyRepository, cache()->store());
});

function queriesDuring(Closure $callback): int
{
    DB::flushQueryLog();
    DB::enableQueryLog();
    $callback();
    DB::disableQueryLog();

    return count(DB::getQueryLog());
}

test('the container resolves the cached repository', function () {
    expect(app(CurrencyRepository::class))->toBeInstanceOf(CachedCurrencyRepository::class);
});

test('an existing currency is looked up in the database only once', function () {
    $id = new CurrencyId(CurrencyModel::factory()->create()->id);

    expect(queriesDuring(fn () => expect($this->repository->exists($id))->toBeTrue()))->toBe(1)
        ->and(queriesDuring(fn () => expect($this->repository->exists($id))->toBeTrue()))->toBe(0);
});

test('a missing currency is not cached, so it is found once created', function () {
    expect($this->repository->exists(new CurrencyId(1234)))->toBeFalse();

    CurrencyModel::factory()->create(['id' => 1234]);

    expect($this->repository->exists(new CurrencyId(1234)))->toBeTrue();
});

test('deleting a currency invalidates its cached existence', function () {
    $id = new CurrencyId(CurrencyModel::factory()->create()->id);
    $this->repository->exists($id);

    $this->repository->delete($id);

    expect($this->repository->exists($id))->toBeFalse();
});

test('reads that need fresh data always go to the database', function () {
    $model = CurrencyModel::factory()->create(['name' => 'Euro']);
    $this->repository->exists(new CurrencyId($model->id));
    $model->update(['name' => 'Euro (EU)']);

    expect($this->repository->findById(new CurrencyId($model->id))->name()->value())->toBe('Euro (EU)');
});

test('loading for update always goes to the database', function () {
    $model = CurrencyModel::factory()->create();
    $this->repository->exists(new CurrencyId($model->id));

    expect(queriesDuring(fn () => $this->repository->findByIdForUpdate(new CurrencyId($model->id))))->toBe(1);
});
