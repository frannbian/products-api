<?php

use App\Application\Currency\DTOs\CurrencyData;
use App\Application\Currency\UseCases\CreateCurrency;
use App\Application\Currency\UseCases\DeleteCurrency;
use App\Application\Currency\UseCases\GetCurrency;
use App\Application\Currency\UseCases\ListCurrencies;
use App\Application\Currency\UseCases\UpdateCurrency;
use App\Application\Shared\Exceptions\StaleVersion;
use App\Domain\Catalog\Entities\Product;
use App\Domain\Catalog\ValueObjects\Amount;
use App\Domain\Catalog\ValueObjects\ProductDescription;
use App\Domain\Catalog\ValueObjects\ProductName;
use App\Domain\Currency\Exceptions\CurrencyInUse;
use App\Domain\Currency\Exceptions\CurrencyNameAlreadyTaken;
use App\Domain\Currency\Exceptions\CurrencyNotFound;
use App\Domain\Currency\ValueObjects\CurrencyId;
use App\Domain\Shared\Exceptions\ConcurrencyConflict;
use Tests\Support\InMemoryCurrencyRepository;
use Tests\Support\InMemoryProductRepository;

beforeEach(function () {
    $this->currencies = new InMemoryCurrencyRepository;
    $this->products = new InMemoryProductRepository;
});

function euroData(array $overrides = []): CurrencyData
{
    return CurrencyData::fromArray([...['name' => 'Euro', 'symbol' => '€', 'exchange_rate' => '0.92'], ...$overrides]);
}

test('it lists currencies page by page', function () {
    $this->currencies->add('US Dollar');
    $this->currencies->add('Euro');
    $ars = $this->currencies->add('Peso argentino');

    $page = (new ListCurrencies($this->currencies))->execute(page: 2, perPage: 2);

    expect($page->total)->toBe(3)
        ->and($page->items)->toBe([$ars]);
});

test('it creates a currency', function () {
    $currency = (new CreateCurrency($this->currencies))->execute(euroData());

    expect($currency->id())->not->toBeNull()
        ->and($this->currencies->findById($currency->id()))->toBe($currency)
        ->and($currency->exchangeRate()->value())->toBe('0.920000');
});

test('it rejects a currency name that is already taken', function () {
    $this->currencies->add('Euro');

    (new CreateCurrency($this->currencies))->execute(euroData());
})->throws(CurrencyNameAlreadyTaken::class);

test('it gets a currency by id', function () {
    $eur = $this->currencies->add('Euro');

    expect((new GetCurrency($this->currencies))->execute($eur->id()))->toBe($eur);
});

test('getting an unknown currency fails', function () {
    (new GetCurrency($this->currencies))->execute(new CurrencyId(99));
})->throws(CurrencyNotFound::class);

test('it updates a currency', function () {
    $eur = $this->currencies->add('Euro', '€', '0.92');

    $updated = (new UpdateCurrency($this->currencies))->execute($eur->id(), euroData(['exchange_rate' => '0.95']));

    expect($updated->exchangeRate()->value())->toBe('0.950000')
        ->and($this->currencies->findById($eur->id())->exchangeRate()->value())->toBe('0.950000');
});

test('a currency keeps its own name when updated', function () {
    $eur = $this->currencies->add('Euro');

    expect((new UpdateCurrency($this->currencies))->execute($eur->id(), euroData())->name()->value())->toBe('Euro');
});

test('a currency can not take the name of another one', function () {
    $this->currencies->add('US Dollar');
    $eur = $this->currencies->add('Euro');

    (new UpdateCurrency($this->currencies))->execute($eur->id(), euroData(['name' => 'US Dollar']));
})->throws(CurrencyNameAlreadyTaken::class);

test('updating an unknown currency fails', function () {
    (new UpdateCurrency($this->currencies))->execute(new CurrencyId(99), euroData());
})->throws(CurrencyNotFound::class);

test('it deletes an unused currency', function () {
    $eur = $this->currencies->add('Euro');

    (new DeleteCurrency($this->currencies, $this->products))->execute($eur->id());

    expect($this->currencies->findById($eur->id()))->toBeNull();
});

test('a currency used by a product can not be deleted', function () {
    $usd = $this->currencies->add('US Dollar');
    $this->products->save(Product::create(
        new ProductName('Keyboard'), new ProductDescription('Mechanical'), new Amount('10'),
        $usd->id(), new Amount('1'), new Amount('2'),
    ));

    (new DeleteCurrency($this->currencies, $this->products))->execute($usd->id());
})->throws(CurrencyInUse::class);

test('deleting an unknown currency fails', function () {
    (new DeleteCurrency($this->currencies, $this->products))->execute(new CurrencyId(99));
})->throws(CurrencyNotFound::class);

test('a currency can not be updated from a stale version', function () {
    $eur = $this->currencies->add('Euro');
    $useCase = new UpdateCurrency($this->currencies);
    $useCase->execute($eur->id(), euroData(['exchange_rate' => '0.95']));

    $useCase->execute($eur->id(), euroData(['exchange_rate' => '0.99']), expectedVersion: 1);
})->throws(StaleVersion::class);

test('a currency can be updated from its current version', function () {
    $eur = $this->currencies->add('Euro');

    $updated = (new UpdateCurrency($this->currencies))->execute($eur->id(), euroData(['exchange_rate' => '0.95']), expectedVersion: 1);

    expect($updated->version())->toBe(2);
});

test('a concurrent currency modification is detected', function () {
    $eur = $this->currencies->add('Euro');
    $copyA = $this->currencies->findById($eur->id());
    $copyB = $this->currencies->findById($eur->id());

    $this->currencies->save($copyA);
    $this->currencies->save($copyB);
})->throws(ConcurrencyConflict::class);
