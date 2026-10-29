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
use App\Application\Shared\Contracts\EventPublisher;
use App\Domain\Catalog\Exceptions\PriceInBaseCurrency;
use App\Domain\Catalog\Exceptions\ProductNotFound;
use App\Domain\Catalog\ValueObjects\ProductId;
use App\Domain\Shared\Events\DomainEvent;
use Tests\Support\FakeEventPublisher;
use Tests\Support\FakeTransactionManager;
use Tests\Support\InMemoryCurrencyRepository;
use Tests\Support\InMemoryProductRepository;

beforeEach(function () {
    $this->tx = new FakeTransactionManager;
    $this->events = new FakeEventPublisher;
    $this->products = new InMemoryProductRepository($this->tx);
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
    return (new CreateProduct($test->products, $test->currencies, $test->events))
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
    (new CreateProduct($this->products, $this->currencies, $this->events))->execute(productData(99));
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

    $updated = (new UpdateProduct($this->products, $this->currencies, $this->tx, $this->events))->execute(
        $product->id(),
        productData($this->eur->id()->value(), ['name' => 'Mouse', 'price' => '50']),
    );

    expect($updated->name()->value())->toBe('Mouse')
        ->and($updated->currencyId()->equals($this->eur->id()))->toBeTrue()
        ->and($this->products->findById($product->id())->price()->value())->toBe('50.00');
});

test('updating an unknown product fails', function () {
    (new UpdateProduct($this->products, $this->currencies, $this->tx, $this->events))->execute(new ProductId(99), productData($this->usd->id()->value()));
})->throws(ProductNotFound::class);

test('a product can not be updated with an unknown currency', function () {
    $product = createProduct($this);

    (new UpdateProduct($this->products, $this->currencies, $this->tx, $this->events))->execute($product->id(), productData(99));
})->throws(UnknownCurrency::class);

test('it deletes a product', function () {
    $product = createProduct($this);

    (new DeleteProduct($this->products, $this->tx, $this->events))->execute($product->id());

    expect($this->products->findById($product->id()))->toBeNull();
});

test('deleting an unknown product fails', function () {
    (new DeleteProduct($this->products, $this->tx, $this->events))->execute(new ProductId(99));
})->throws(ProductNotFound::class);

test('it registers a price in another currency', function () {
    $product = createProduct($this);

    $result = (new SetProductPrice($this->products, $this->currencies, $this->tx, $this->events))->execute(
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
    $useCase = new SetProductPrice($this->products, $this->currencies, $this->tx, $this->events);
    $first = $useCase->execute($product->id(), ProductPriceData::fromArray(['currency_id' => $this->eur->id()->value(), 'price' => '92']));

    $second = $useCase->execute($product->id(), ProductPriceData::fromArray(['currency_id' => $this->eur->id()->value(), 'price' => '95']));

    expect($second->created)->toBeFalse()
        ->and($second->price->id()->equals($first->price->id()))->toBeTrue()
        ->and($second->price->amount()->value())->toBe('95.00')
        ->and($this->products->findById($product->id())->prices())->toHaveCount(1);
});

test('a price can not be registered in an unknown currency', function () {
    $product = createProduct($this);

    (new SetProductPrice($this->products, $this->currencies, $this->tx, $this->events))->execute(
        $product->id(),
        ProductPriceData::fromArray(['currency_id' => 99, 'price' => '92']),
    );
})->throws(UnknownCurrency::class);

test('a price can not be registered in the base currency', function () {
    $product = createProduct($this);

    (new SetProductPrice($this->products, $this->currencies, $this->tx, $this->events))->execute(
        $product->id(),
        ProductPriceData::fromArray(['currency_id' => $this->usd->id()->value(), 'price' => '92']),
    );
})->throws(PriceInBaseCurrency::class);

test('a price can not be registered for an unknown product', function () {
    (new SetProductPrice($this->products, $this->currencies, $this->tx, $this->events))->execute(
        new ProductId(99),
        ProductPriceData::fromArray(['currency_id' => $this->eur->id()->value(), 'price' => '92']),
    );
})->throws(ProductNotFound::class);

test('it lists the prices of a product', function () {
    $product = createProduct($this);
    (new SetProductPrice($this->products, $this->currencies, $this->tx, $this->events))->execute(
        $product->id(),
        ProductPriceData::fromArray(['currency_id' => $this->eur->id()->value(), 'price' => '92']),
    );

    $page = (new ListProductPrices($this->products))->execute($product->id(), page: 1, perPage: 15);

    expect($page->total)->toBe(1)
        ->and($page->items[0]->currencyId()->equals($this->eur->id()))->toBeTrue();
});

test('it lists the prices of a product page by page', function () {
    $product = createProduct($this);
    $ars = $this->currencies->add('Peso argentino');
    $useCase = new SetProductPrice($this->products, $this->currencies, $this->tx, $this->events);
    $useCase->execute($product->id(), ProductPriceData::fromArray(['currency_id' => $this->eur->id()->value(), 'price' => '92']));
    $useCase->execute($product->id(), ProductPriceData::fromArray(['currency_id' => $ars->id()->value(), 'price' => '1000']));

    $page = (new ListProductPrices($this->products))->execute($product->id(), page: 2, perPage: 1);

    expect($page->total)->toBe(2)
        ->and($page->items)->toHaveCount(1)
        ->and($page->items[0]->currencyId()->equals($ars->id()))->toBeTrue();
});

test('listing the prices of an unknown product fails', function () {
    (new ListProductPrices($this->products))->execute(new ProductId(99), page: 1, perPage: 15);
})->throws(ProductNotFound::class);

test('updating a product locks it inside a transaction', function () {
    $product = createProduct($this);

    (new UpdateProduct($this->products, $this->currencies, $this->tx, $this->events))
        ->execute($product->id(), productData($this->usd->id()->value(), ['name' => 'Mouse']));

    expect($this->products->locks)->toBe([['id' => $product->id()->value(), 'inTransaction' => true]]);
});

test('registering a price locks the product inside a transaction', function () {
    $product = createProduct($this);

    (new SetProductPrice($this->products, $this->currencies, $this->tx, $this->events))->execute(
        $product->id(),
        ProductPriceData::fromArray(['currency_id' => $this->eur->id()->value(), 'price' => '92']),
    );

    expect($this->products->locks)->toBe([['id' => $product->id()->value(), 'inTransaction' => true]]);
});

test('deleting a product locks it inside a transaction', function () {
    $product = createProduct($this);

    (new DeleteProduct($this->products, $this->tx, $this->events))->execute($product->id());

    expect($this->products->locks)->toBe([['id' => $product->id()->value(), 'inTransaction' => true]]);
});

test('reading a product takes no lock and opens no transaction', function () {
    $product = createProduct($this);

    (new GetProduct($this->products))->execute($product->id());

    expect($this->products->locks)->toBe([])
        ->and($this->tx->transactions)->toBe(0);
});

test('creating a product publishes product.created with its id', function () {
    $product = createProduct($this);

    expect($this->events->names())->toBe(['product.created'])
        ->and($this->events->published[0]->aggregateId())->toBe($product->id()->value());
});

test('updating a product publishes product.updated with the new state', function () {
    $product = createProduct($this);

    (new UpdateProduct($this->products, $this->currencies, $this->tx, $this->events))
        ->execute($product->id(), productData($this->usd->id()->value(), ['name' => 'Mouse']));

    expect($this->events->names())->toBe(['product.created', 'product.updated'])
        ->and($this->events->published[1]->payload()['name'])->toBe('Mouse');
});

test('registering a price publishes product.price_set', function () {
    $product = createProduct($this);

    (new SetProductPrice($this->products, $this->currencies, $this->tx, $this->events))->execute(
        $product->id(),
        ProductPriceData::fromArray(['currency_id' => $this->eur->id()->value(), 'price' => '92']),
    );

    expect($this->events->names())->toBe(['product.created', 'product.price_set'])
        ->and($this->events->published[1]->payload())->toBe(['currency_id' => $this->eur->id()->value(), 'price' => '92.00', 'created' => true]);
});

test('deleting a product publishes product.deleted', function () {
    $product = createProduct($this);

    (new DeleteProduct($this->products, $this->tx, $this->events))->execute($product->id());

    expect($this->events->names())->toBe(['product.created', 'product.deleted']);
});

test('changes are published inside the transaction so they are only sent if it commits', function () {
    $product = createProduct($this);
    $publishedInTransaction = null;
    $events = new class($this->tx, $publishedInTransaction) implements EventPublisher
    {
        public function __construct(private FakeTransactionManager $tx, public ?bool &$inTransaction) {}

        public function publish(DomainEvent ...$events): void
        {
            $this->inTransaction = $this->tx->active();
        }
    };

    (new UpdateProduct($this->products, $this->currencies, $this->tx, $events))
        ->execute($product->id(), productData($this->usd->id()->value()));

    expect($publishedInTransaction)->toBeTrue();
});

test('a rejected change publishes nothing', function () {
    $product = createProduct($this);

    try {
        (new SetProductPrice($this->products, $this->currencies, $this->tx, $this->events))->execute(
            $product->id(),
            ProductPriceData::fromArray(['currency_id' => $this->usd->id()->value(), 'price' => '1']),
        );
    } catch (PriceInBaseCurrency) {
    }

    expect($this->events->names())->toBe(['product.created']);
});
