<?php

use App\Domain\Catalog\Entities\Product;
use App\Domain\Catalog\Repositories\ProductRepository;
use App\Domain\Catalog\ValueObjects\Amount;
use App\Domain\Catalog\ValueObjects\ProductDescription;
use App\Domain\Catalog\ValueObjects\ProductId;
use App\Domain\Catalog\ValueObjects\ProductName;
use App\Domain\Currency\ValueObjects\CurrencyId;
use App\Infrastructure\Catalog\Persistence\CachedProductRepository;
use App\Infrastructure\Catalog\Persistence\Eloquent\ProductModel;
use App\Infrastructure\Catalog\Persistence\EloquentProductRepository;
use App\Infrastructure\Currency\Persistence\Eloquent\CurrencyModel;
use App\Infrastructure\Shared\Cache\GenerationalCache;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->repository = new CachedProductRepository(new EloquentProductRepository, app(GenerationalCache::class), listingsTtl: 300, resourcesTtl: 600);
    $this->usd = CurrencyModel::factory()->create();
    $this->eur = CurrencyModel::factory()->create();
});

function countQueries(Closure $callback): int
{
    DB::flushQueryLog();
    DB::enableQueryLog();
    $callback();
    DB::disableQueryLog();

    return count(DB::getQueryLog());
}

function storedProduct(object $test, string $name = 'Keyboard'): Product
{
    $product = Product::create(
        new ProductName($name), new ProductDescription('Mechanical'), new Amount('100.50'),
        new CurrencyId($test->usd->id), new Amount('21'), new Amount('40'),
    );
    $product->setPriceIn(new CurrencyId($test->eur->id), new Amount('92.30'));

    return $test->repository->save($product);
}

test('the container resolves the cached repository', function () {
    expect(app(ProductRepository::class))->toBeInstanceOf(CachedProductRepository::class);
});

test('listings are read from the database only once', function () {
    storedProduct($this);

    expect(countQueries(fn () => $this->repository->paginate(1, 15)))->toBeGreaterThan(0)
        ->and(countQueries(fn () => $this->repository->paginate(1, 15)))->toBe(0);
});

test('a cached product is rebuilt with all its data and prices', function () {
    $stored = storedProduct($this);
    $this->repository->findById($stored->id());

    $cached = null;
    $queries = countQueries(function () use ($stored, &$cached) {
        $cached = $this->repository->findById($stored->id());
    });

    expect($queries)->toBe(0)
        ->and($cached->id()->value())->toBe($stored->id()->value())
        ->and($cached->name()->value())->toBe('Keyboard')
        ->and($cached->description()->value())->toBe('Mechanical')
        ->and($cached->price()->value())->toBe('100.50')
        ->and($cached->currencyId()->value())->toBe($this->usd->id)
        ->and($cached->taxCost()->value())->toBe('21.00')
        ->and($cached->manufacturingCost()->value())->toBe('40.00')
        ->and($cached->prices())->toHaveCount(1)
        ->and($cached->priceIn(new CurrencyId($this->eur->id))->id()->value())->toBe($stored->prices()[0]->id()->value())
        ->and($cached->priceIn(new CurrencyId($this->eur->id))->amount()->value())->toBe('92.30');
});

test('a cached page keeps its items and pagination data', function () {
    storedProduct($this, 'A');
    storedProduct($this, 'B');
    $this->repository->paginate(2, 1);

    $page = $this->repository->paginate(2, 1);

    expect($page->total)->toBe(2)
        ->and($page->page)->toBe(2)
        ->and($page->perPage)->toBe(1)
        ->and($page->items[0]->name()->value())->toBe('B');
});

test('price listings and existence checks are cached', function () {
    $stored = storedProduct($this);
    $this->repository->paginatePrices($stored->id(), 1, 15);
    $this->repository->exists($stored->id());

    expect(countQueries(fn () => $this->repository->paginatePrices($stored->id(), 1, 15)))->toBe(0)
        ->and(countQueries(fn () => $this->repository->exists($stored->id())))->toBe(0)
        ->and($this->repository->paginatePrices($stored->id(), 1, 15)->items[0]->amount()->value())->toBe('92.30');
});

test('creating a product renews the listings', function () {
    storedProduct($this, 'First');
    $this->repository->paginate(1, 15);

    storedProduct($this, 'Second');

    expect($this->repository->paginate(1, 15)->total)->toBe(2);
});

test('updating a product renews it', function () {
    $product = storedProduct($this);
    $this->repository->findById($product->id());

    $product->update(new ProductName('Renamed'), $product->description(), $product->price(), $product->currencyId(), $product->taxCost(), $product->manufacturingCost());
    $this->repository->save($product);

    expect($this->repository->findById($product->id())->name()->value())->toBe('Renamed');
});

test('registering a price renews the price listing', function () {
    $product = storedProduct($this);
    $this->repository->paginatePrices($product->id(), 1, 15);
    $ars = CurrencyModel::factory()->create();

    $product->setPriceIn(new CurrencyId($ars->id), new Amount('1000'));
    $this->repository->save($product);

    expect($this->repository->paginatePrices($product->id(), 1, 15)->total)->toBe(2);
});

test('deleting a product renews it and the listings', function () {
    $product = storedProduct($this);
    $this->repository->findById($product->id());
    $this->repository->exists($product->id());
    $this->repository->paginate(1, 15);

    $this->repository->delete($product->id());

    expect($this->repository->findById($product->id()))->toBeNull()
        ->and($this->repository->exists($product->id()))->toBeFalse()
        ->and($this->repository->paginate(1, 15)->total)->toBe(0);
});

test('cached listings are renewed after their ttl even without writes through the api', function () {
    storedProduct($this);
    $this->repository->paginate(1, 15);
    ProductModel::factory()->for($this->usd, 'currency')->create();

    expect($this->repository->paginate(1, 15)->total)->toBe(1);

    $this->travel(301)->seconds();

    expect($this->repository->paginate(1, 15)->total)->toBe(2);
});

test('loading for update and checking currency usage always go to the database', function () {
    $product = storedProduct($this);
    $this->repository->findById($product->id());

    expect(countQueries(fn () => $this->repository->findByIdForUpdate($product->id())))->toBeGreaterThan(0)
        ->and(countQueries(fn () => $this->repository->existsWithCurrency(new CurrencyId($this->usd->id))))->toBeGreaterThan(0);
});

test('missing products are not cached', function () {
    $this->repository->findById(new ProductId(999));

    expect(countQueries(fn () => $this->repository->findById(new ProductId(999))))->toBeGreaterThan(0);
});
