<?php

use App\Domain\Currency\Entities\Currency;
use App\Domain\Currency\Exceptions\CurrencyInUse;
use App\Domain\Currency\Exceptions\CurrencyNameAlreadyTaken;
use App\Domain\Currency\ValueObjects\CurrencyId;
use App\Domain\Currency\ValueObjects\CurrencyName;
use App\Domain\Currency\ValueObjects\CurrencySymbol;
use App\Domain\Currency\ValueObjects\ExchangeRate;
use App\Domain\Shared\Exceptions\ConcurrencyConflict;
use App\Infrastructure\Catalog\Persistence\Eloquent\ProductModel;
use App\Infrastructure\Currency\Persistence\Eloquent\CurrencyModel;
use App\Infrastructure\Currency\Persistence\EloquentCurrencyRepository;

beforeEach(function () {
    $this->repository = new EloquentCurrencyRepository;
});

test('it persists a new currency and assigns its identity', function () {
    $currency = $this->repository->save(
        Currency::create(new CurrencyName('Euro'), new CurrencySymbol('€'), new ExchangeRate('0.92'))
    );

    $model = CurrencyModel::find($currency->id()->value());

    expect($model->name)->toBe('Euro')
        ->and($model->symbol)->toBe('€')
        ->and($model->exchange_rate)->toBe('0.920000');
});

test('it finds a currency by id as a domain entity', function () {
    $model = CurrencyModel::factory()->create(['name' => 'Euro', 'exchange_rate' => '0.92']);

    $currency = $this->repository->findById(new CurrencyId($model->id));

    expect($currency)->toBeInstanceOf(Currency::class)
        ->and($currency->name()->value())->toBe('Euro')
        ->and($currency->exchangeRate()->value())->toBe('0.920000');
});

test('it returns null for an unknown id', function () {
    expect($this->repository->findById(new CurrencyId(999)))->toBeNull();
});

test('it paginates currencies ordered by id', function () {
    $models = CurrencyModel::factory()->count(3)->create();

    $page = $this->repository->paginate(page: 1, perPage: 2);

    expect($page->total)->toBe(3)
        ->and(array_map(fn (Currency $c) => $c->id()->value(), $page->items))
        ->toBe([$models[0]->id, $models[1]->id]);
});

test('it knows whether a name is taken, optionally ignoring a currency', function () {
    $euro = CurrencyModel::factory()->create(['name' => 'Euro']);

    expect($this->repository->existsByName(new CurrencyName('Euro')))->toBeTrue()
        ->and($this->repository->existsByName(new CurrencyName('Yen')))->toBeFalse()
        ->and($this->repository->existsByName(new CurrencyName('Euro'), new CurrencyId($euro->id)))->toBeFalse();
});

test('it updates an existing currency', function () {
    $model = CurrencyModel::factory()->create(['exchange_rate' => '1']);
    $currency = $this->repository->findById(new CurrencyId($model->id));

    $currency->update($currency->name(), $currency->symbol(), new ExchangeRate('2.5'));
    $this->repository->save($currency);

    expect($model->fresh()->exchange_rate)->toBe('2.500000')
        ->and(CurrencyModel::count())->toBe(1);
});

test('it deletes a currency', function () {
    $model = CurrencyModel::factory()->create();

    $this->repository->delete(new CurrencyId($model->id));

    expect(CurrencyModel::find($model->id))->toBeNull();
});

test('saving an existing currency bumps its version and rejects stale copies', function () {
    $model = CurrencyModel::factory()->create();
    $current = $this->repository->findById(new CurrencyId($model->id));
    $stale = $this->repository->findById(new CurrencyId($model->id));

    $saved = $this->repository->save($current);

    expect($saved->version())->toBe(2)
        ->and(fn () => $this->repository->save($stale))->toThrow(ConcurrencyConflict::class);
});

test('a duplicate name that slips past the check is reported as taken', function () {
    CurrencyModel::factory()->create(['name' => 'Euro']);

    // Simulates two requests that both checked the name before either saved.
    $this->repository->save(Currency::create(new CurrencyName('Euro'), new CurrencySymbol('€'), new ExchangeRate('1')));
})->throws(CurrencyNameAlreadyTaken::class);

test('renaming to a taken name that slips past the check is reported as taken', function () {
    CurrencyModel::factory()->create(['name' => 'Euro']);
    $other = $this->repository->findById(new CurrencyId(CurrencyModel::factory()->create(['name' => 'Yen'])->id));

    $other->update(new CurrencyName('Euro'), $other->symbol(), $other->exchangeRate());
    $this->repository->save($other);
})->throws(CurrencyNameAlreadyTaken::class);

test('deleting a currency that became used in the meantime is reported as in use', function () {
    $currency = CurrencyModel::factory()->create();
    ProductModel::factory()->for($currency, 'currency')->create();

    $this->repository->delete(new CurrencyId($currency->id));
})->throws(CurrencyInUse::class);

test('it knows whether a currency exists', function () {
    $model = CurrencyModel::factory()->create();

    expect($this->repository->exists(new CurrencyId($model->id)))->toBeTrue()
        ->and($this->repository->exists(new CurrencyId(999)))->toBeFalse();
});
