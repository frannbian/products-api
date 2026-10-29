<?php

use App\Application\Catalog\DTOs\ProductData;
use App\Application\Catalog\DTOs\ProductPriceData;
use App\Application\Catalog\Exceptions\UnknownCurrency;
use App\Application\Catalog\UseCases\CreateProduct;
use App\Application\Catalog\UseCases\DeleteProduct;
use App\Application\Catalog\UseCases\GetProduct;
use App\Application\Catalog\UseCases\ListProductPrices;
use App\Application\Catalog\UseCases\ListProducts;
use App\Application\Catalog\UseCases\SetProductPrice;
use App\Application\Catalog\UseCases\UpdateProduct;
use App\Domain\Catalog\Exceptions\PriceInBaseCurrency;
use App\Domain\Catalog\Exceptions\ProductNotFound;
use App\Domain\Catalog\ValueObjects\ProductId;
use Tests\Support\InMemoryCurrencyRepository;
use Tests\Support\InMemoryProductRepository;

beforeEach(function () {
    $this->products = new InMemoryProductRepository;
    $this->currencies = new InMemoryCurrencyRepository;
    $this->usd = $this->currencies->add('US Dollar', '$', '1');
    $this->eur = $this->currencies->add('Euro', '€', '0.92');
});

function productData(int $currencyId, array $overrides = []): ProductData
{
    return ProductData::fromArray([...[
        'name' => 'Keyboard',
        'description' => 'Mechanical keyboard',
        'price' => '100',
        'currency_id' => $currencyId,
        'tax_cost' => '21',
        'manufacturing_cost' => '40',
    ], ...$overrides]);
}

function createProduct(object $test, array $overrides = [])
{
    return (new CreateProduct($test->products, $test->currencies))
        ->execute(productData($test->usd->id()->value(), $overrides));
}

test('it creates a product', function () {
    $product = createProduct($this);

    expect($product->id())->not->toBeNull()
        ->and($this->products->findById($product->id()))->toBe($product)
        ->and($product->price()->value())->toBe('100.00')
        ->and($product->currencyId()->equals($this->usd->id()))->toBeTrue();
});

test('a product can not be created with an unknown currency', function () {
    (new CreateProduct($this->products, $this->currencies))->execute(productData(99));
})->throws(UnknownCurrency::class);

test('it lists products page by page', function () {
    foreach (range(1, 3) as $i) {
        createProduct($this, ['name' => "Product {$i}"]);
    }

    $page = (new ListProducts($this->products))->execute(page: 2, perPage: 2);

    expect($page->total)->toBe(3)
        ->and($page->items)->toHaveCount(1)
        ->and($page->items[0]->name()->value())->toBe('Product 3');
});

test('it gets a product by id', function () {
    $product = createProduct($this);

    expect((new GetProduct($this->products))->execute($product->id()))->toBe($product);
});

test('getting an unknown product fails', function () {
    (new GetProduct($this->products))->execute(new ProductId(99));
})->throws(ProductNotFound::class);

test('it updates a product', function () {
    $product = createProduct($this);

    $updated = (new UpdateProduct($this->products, $this->currencies))->execute(
        $product->id(),
        productData($this->eur->id()->value(), ['name' => 'Mouse', 'price' => '50']),
    );

    expect($updated->name()->value())->toBe('Mouse')
        ->and($updated->currencyId()->equals($this->eur->id()))->toBeTrue()
        ->and($this->products->findById($product->id())->price()->value())->toBe('50.00');
});

test('updating an unknown product fails', function () {
    (new UpdateProduct($this->products, $this->currencies))->execute(new ProductId(99), productData($this->usd->id()->value()));
})->throws(ProductNotFound::class);

test('a product can not be updated with an unknown currency', function () {
    $product = createProduct($this);

    (new UpdateProduct($this->products, $this->currencies))->execute($product->id(), productData(99));
})->throws(UnknownCurrency::class);

test('it deletes a product', function () {
    $product = createProduct($this);

    (new DeleteProduct($this->products))->execute($product->id());

    expect($this->products->findById($product->id()))->toBeNull();
});

test('deleting an unknown product fails', function () {
    (new DeleteProduct($this->products))->execute(new ProductId(99));
})->throws(ProductNotFound::class);

test('it registers a price in another currency', function () {
    $product = createProduct($this);

    $result = (new SetProductPrice($this->products, $this->currencies))->execute(
        $product->id(),
        ProductPriceData::fromArray(['currency_id' => $this->eur->id()->value(), 'price' => '92']),
    );

    expect($result->created)->toBeTrue()
        ->and($result->price->id())->not->toBeNull()
        ->and($result->price->amount()->value())->toBe('92.00')
        ->and($this->products->findById($product->id())->priceIn($this->eur->id())->amount()->value())->toBe('92.00');
});

test('registering a price again in the same currency updates it', function () {
    $product = createProduct($this);
    $useCase = new SetProductPrice($this->products, $this->currencies);
    $first = $useCase->execute($product->id(), ProductPriceData::fromArray(['currency_id' => $this->eur->id()->value(), 'price' => '92']));

    $second = $useCase->execute($product->id(), ProductPriceData::fromArray(['currency_id' => $this->eur->id()->value(), 'price' => '95']));

    expect($second->created)->toBeFalse()
        ->and($second->price->id()->equals($first->price->id()))->toBeTrue()
        ->and($second->price->amount()->value())->toBe('95.00')
        ->and($this->products->findById($product->id())->prices())->toHaveCount(1);
});

test('a price can not be registered in an unknown currency', function () {
    $product = createProduct($this);

    (new SetProductPrice($this->products, $this->currencies))->execute(
        $product->id(),
        ProductPriceData::fromArray(['currency_id' => 99, 'price' => '92']),
    );
})->throws(UnknownCurrency::class);

test('a price can not be registered in the base currency', function () {
    $product = createProduct($this);

    (new SetProductPrice($this->products, $this->currencies))->execute(
        $product->id(),
        ProductPriceData::fromArray(['currency_id' => $this->usd->id()->value(), 'price' => '92']),
    );
})->throws(PriceInBaseCurrency::class);

test('a price can not be registered for an unknown product', function () {
    (new SetProductPrice($this->products, $this->currencies))->execute(
        new ProductId(99),
        ProductPriceData::fromArray(['currency_id' => $this->eur->id()->value(), 'price' => '92']),
    );
})->throws(ProductNotFound::class);

test('it lists the prices of a product', function () {
    $product = createProduct($this);
    (new SetProductPrice($this->products, $this->currencies))->execute(
        $product->id(),
        ProductPriceData::fromArray(['currency_id' => $this->eur->id()->value(), 'price' => '92']),
    );

    $prices = (new ListProductPrices($this->products))->execute($product->id());

    expect($prices)->toHaveCount(1)
        ->and($prices[0]->currencyId()->equals($this->eur->id()))->toBeTrue();
});

test('listing the prices of an unknown product fails', function () {
    (new ListProductPrices($this->products))->execute(new ProductId(99));
})->throws(ProductNotFound::class);
