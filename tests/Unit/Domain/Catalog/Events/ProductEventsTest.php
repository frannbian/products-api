<?php

use App\Domain\Catalog\Entities\Product;
use App\Domain\Catalog\Events\ProductCreated;
use App\Domain\Catalog\Events\ProductDeleted;
use App\Domain\Catalog\Events\ProductPriceSet;
use App\Domain\Catalog\Events\ProductUpdated;
use App\Domain\Catalog\ValueObjects\Amount;
use App\Domain\Catalog\ValueObjects\ProductDescription;
use App\Domain\Catalog\ValueObjects\ProductId;
use App\Domain\Catalog\ValueObjects\ProductName;
use App\Domain\Currency\ValueObjects\CurrencyId;

function persistedKeyboard(): Product
{
    return Product::reconstitute(
        new ProductId(7), new ProductName('Keyboard'), new ProductDescription('Mechanical'),
        new Amount('100'), new CurrencyId(1), new Amount('21'), new Amount('40'), [],
    );
}

test('product events carry the state of the product as primitives', function (string $class, string $name) {
    $event = new $class(persistedKeyboard());

    expect($event->name())->toBe($name)
        ->and($event->aggregateType())->toBe('product')
        ->and($event->aggregateId())->toBe(7)
        ->and($event->payload())->toBe([
            'name' => 'Keyboard',
            'description' => 'Mechanical',
            'price' => '100.00',
            'currency_id' => 1,
            'tax_cost' => '21.00',
            'manufacturing_cost' => '40.00',
        ]);
})->with([
    [ProductCreated::class, 'product.created'],
    [ProductUpdated::class, 'product.updated'],
]);

test('a deleted product event only carries the id', function () {
    $event = new ProductDeleted(new ProductId(7));

    expect($event->name())->toBe('product.deleted')
        ->and($event->aggregateId())->toBe(7)
        ->and($event->payload())->toBe([]);
});

test('a price event carries the currency, the amount and whether it was new', function () {
    $event = new ProductPriceSet(new ProductId(7), new CurrencyId(2), new Amount('92.5'), created: true);

    expect($event->name())->toBe('product.price_set')
        ->and($event->aggregateId())->toBe(7)
        ->and($event->payload())->toBe(['currency_id' => 2, 'price' => '92.50', 'created' => true]);
});

test('every event gets a unique uuid and the time it happened', function () {
    $before = new DateTimeImmutable;
    $first = new ProductDeleted(new ProductId(7));
    $second = new ProductDeleted(new ProductId(7));

    expect($first->eventId())->toMatch('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/')
        ->and($first->eventId())->not->toBe($second->eventId())
        ->and($first->occurredAt() >= $before)->toBeTrue();
});
