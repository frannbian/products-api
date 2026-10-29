<?php

use App\Domain\Currency\Entities\Currency;
use App\Domain\Currency\Repositories\CurrencyRepository;
use App\Domain\Currency\ValueObjects\CurrencyName;
use App\Domain\Currency\ValueObjects\CurrencySymbol;
use App\Domain\Currency\ValueObjects\ExchangeRate;
use App\Infrastructure\Currency\Persistence\CachedCurrencyRepository;
use App\Infrastructure\Currency\Persistence\EloquentCurrencyRepository;
use App\Infrastructure\Shared\Cache\GenerationalCache;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->repository = new CachedCurrencyRepository(new EloquentCurrencyRepository, app(GenerationalCache::class), listingsTtl: 300, resourcesTtl: 600);
});

function queriesDuring(Closure $callback): int
{
    DB::flushQueryLog();
    DB::enableQueryLog();
    $callback();
    DB::disableQueryLog();

    return count(DB::getQueryLog());
}

function savedCurrency(object $test, string $name = 'Euro', string $rate = '0.92'): Currency
{
    return $test->repository->save(Currency::create(new CurrencyName($name), new CurrencySymbol('€'), new ExchangeRate($rate)));
}

test('the container resolves the cached repository', function () {
    expect(app(CurrencyRepository::class))->toBeInstanceOf(CachedCurrencyRepository::class);
});

test('listings, single currencies and existence checks are read from the database only once', function () {
    $euro = savedCurrency($this);
    $this->repository->paginate(1, 15);
    $this->repository->findById($euro->id());
    $this->repository->exists($euro->id());

    expect(queriesDuring(function () use ($euro) {
        $this->repository->paginate(1, 15);
        $this->repository->findById($euro->id());
        $this->repository->exists($euro->id());
    }))->toBe(0);
});

test('a cached currency is rebuilt with all its data', function () {
    $euro = savedCurrency($this, 'Euro', '0.92');
    $this->repository->findById($euro->id());

    $cached = $this->repository->findById($euro->id());

    expect($cached->id()->value())->toBe($euro->id()->value())
        ->and($cached->name()->value())->toBe('Euro')
        ->and($cached->symbol()->value())->toBe('€')
        ->and($cached->exchangeRate()->value())->toBe('0.920000')
        ->and($this->repository->paginate(1, 15)->items[0]->name()->value())->toBe('Euro');
});

test('creating a currency renews the listings', function () {
    savedCurrency($this, 'Euro');
    $this->repository->paginate(1, 15);

    savedCurrency($this, 'Yen');

    expect($this->repository->paginate(1, 15)->total)->toBe(2);
});

test('updating a currency renews it', function () {
    $euro = savedCurrency($this, 'Euro', '0.92');
    $this->repository->findById($euro->id());

    $euro->update($euro->name(), $euro->symbol(), new ExchangeRate('0.95'));
    $this->repository->save($euro);

    expect($this->repository->findById($euro->id())->exchangeRate()->value())->toBe('0.950000');
});

test('deleting a currency renews its existence check', function () {
    $euro = savedCurrency($this);
    $this->repository->exists($euro->id());

    $this->repository->delete($euro->id());

    expect($this->repository->exists($euro->id()))->toBeFalse();
});

test('loading for update and checking name uniqueness always go to the database', function () {
    $euro = savedCurrency($this);
    $this->repository->findById($euro->id());

    expect(queriesDuring(fn () => $this->repository->findByIdForUpdate($euro->id())))->toBeGreaterThan(0)
        ->and(queriesDuring(fn () => $this->repository->existsByName(new CurrencyName('Euro'))))->toBeGreaterThan(0);
});
