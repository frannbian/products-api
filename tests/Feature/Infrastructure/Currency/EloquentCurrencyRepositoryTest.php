<?php

use App\Domain\Currency\Entities\Currency;
use App\Domain\Currency\ValueObjects\CurrencyId;
use App\Domain\Currency\ValueObjects\CurrencyName;
use App\Domain\Currency\ValueObjects\CurrencySymbol;
use App\Domain\Currency\ValueObjects\ExchangeRate;
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

test('it lists every currency ordered by id', function () {
    $first = CurrencyModel::factory()->create();
    $second = CurrencyModel::factory()->create();

    expect(array_map(fn (Currency $c) => $c->id()->value(), $this->repository->all()))
        ->toBe([$first->id, $second->id]);
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
