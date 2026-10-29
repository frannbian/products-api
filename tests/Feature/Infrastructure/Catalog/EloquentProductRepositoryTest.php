<?php

use App\Domain\Catalog\Entities\Product;
use App\Domain\Catalog\ValueObjects\Amount;
use App\Domain\Catalog\ValueObjects\ProductDescription;
use App\Domain\Catalog\ValueObjects\ProductId;
use App\Domain\Catalog\ValueObjects\ProductName;
use App\Domain\Currency\ValueObjects\CurrencyId;
use App\Infrastructure\Catalog\Persistence\Eloquent\ProductModel;
use App\Infrastructure\Catalog\Persistence\Eloquent\ProductPriceModel;
use App\Infrastructure\Catalog\Persistence\EloquentProductRepository;
use App\Infrastructure\Currency\Persistence\Eloquent\CurrencyModel;

beforeEach(function () {
    $this->repository = new EloquentProductRepository;
    $this->usd = CurrencyModel::factory()->create(['name' => 'US Dollar']);
    $this->eur = CurrencyModel::factory()->create(['name' => 'Euro']);
});

function draftProduct(int $currencyId): Product
{
    return Product::create(
        new ProductName('Keyboard'),
        new ProductDescription('Mechanical keyboard'),
        new Amount('100.50'),
        new CurrencyId($currencyId),
        new Amount('21'),
        new Amount('40'),
    );
}

test('it persists a new product and assigns its identity', function () {
    $product = $this->repository->save(draftProduct($this->usd->id));

    $model = ProductModel::find($product->id()->value());

    expect($model->name)->toBe('Keyboard')
        ->and($model->description)->toBe('Mechanical keyboard')
        ->and($model->price)->toBe('100.50')
        ->and($model->currency_id)->toBe($this->usd->id)
        ->and($model->tax_cost)->toBe('21.00')
        ->and($model->manufacturing_cost)->toBe('40.00');
});

test('it persists new prices and assigns their identity', function () {
    $product = draftProduct($this->usd->id);
    $product->setPriceIn(new CurrencyId($this->eur->id), new Amount('92.30'));

    $saved = $this->repository->save($product);

    $price = $saved->priceIn(new CurrencyId($this->eur->id));
    expect($price->id())->not->toBeNull()
        ->and(ProductPriceModel::find($price->id()->value())->price)->toBe('92.30');
});

test('it updates an existing price instead of duplicating it', function () {
    $product = draftProduct($this->usd->id);
    $product->setPriceIn(new CurrencyId($this->eur->id), new Amount('92'));
    $product = $this->repository->save($product);

    $product->setPriceIn(new CurrencyId($this->eur->id), new Amount('95'));
    $this->repository->save($product);

    expect(ProductPriceModel::count())->toBe(1)
        ->and(ProductPriceModel::first()->price)->toBe('95.00');
});

test('it finds a product with its prices as a domain entity', function () {
    $model = ProductModel::factory()->for($this->usd, 'currency')->create(['price' => '10']);
    ProductPriceModel::factory()->for($model, 'product')->for($this->eur, 'currency')->create(['price' => '9.20']);

    $product = $this->repository->findById(new ProductId($model->id));

    expect($product->price()->value())->toBe('10.00')
        ->and($product->prices())->toHaveCount(1)
        ->and($product->priceIn(new CurrencyId($this->eur->id))->amount()->value())->toBe('9.20');
});

test('it returns null for an unknown or deleted product', function () {
    $model = ProductModel::factory()->for($this->usd, 'currency')->create();
    $model->delete();

    expect($this->repository->findById(new ProductId($model->id)))->toBeNull()
        ->and($this->repository->findById(new ProductId(999)))->toBeNull();
});

test('it paginates products ordered by id', function () {
    $models = ProductModel::factory()->count(3)->for($this->usd, 'currency')->create();

    $page = $this->repository->paginate(page: 2, perPage: 2);

    expect($page->total)->toBe(3)
        ->and($page->page)->toBe(2)
        ->and($page->perPage)->toBe(2)
        ->and($page->items)->toHaveCount(1)
        ->and($page->items[0]->id()->value())->toBe($models[2]->id);
});

test('it soft deletes a product', function () {
    $model = ProductModel::factory()->for($this->usd, 'currency')->create();

    $this->repository->delete(new ProductId($model->id));

    expect(ProductModel::find($model->id))->toBeNull()
        ->and(ProductModel::withTrashed()->find($model->id)->trashed())->toBeTrue();
});

test('it knows whether a currency is used by any product', function () {
    $ars = CurrencyModel::factory()->create();
    $unused = CurrencyModel::factory()->create();
    $product = ProductModel::factory()->for($this->usd, 'currency')->create();
    ProductPriceModel::factory()->for($product, 'product')->for($ars, 'currency')->create();

    expect($this->repository->existsWithCurrency(new CurrencyId($this->usd->id)))->toBeTrue()
        ->and($this->repository->existsWithCurrency(new CurrencyId($ars->id)))->toBeTrue()
        ->and($this->repository->existsWithCurrency(new CurrencyId($unused->id)))->toBeFalse();
});

test('a deleted product still counts as using its currency', function () {
    ProductModel::factory()->for($this->usd, 'currency')->create()->delete();

    expect($this->repository->existsWithCurrency(new CurrencyId($this->usd->id)))->toBeTrue();
});
