<?php

namespace App\Domain\Catalog\Events;

use App\Domain\Catalog\Entities\Product;
use App\Domain\Shared\Events\DomainEvent;
use App\Domain\Shared\Events\RecordsOccurrence;

/**
 * Base of the events that carry the full state of the product after a change.
 */
abstract class ProductStateChanged implements DomainEvent
{
    use RecordsOccurrence;

    private int $productId;

    /** @var array<string, scalar> */
    private array $state;

    public function __construct(Product $product)
    {
        $this->recordOccurrence();
        $this->productId = $product->id()->value();
        $this->state = [
            'name' => $product->name()->value(),
            'description' => $product->description()->value(),
            'price' => $product->price()->value(),
            'currency_id' => $product->currencyId()->value(),
            'tax_cost' => $product->taxCost()->value(),
            'manufacturing_cost' => $product->manufacturingCost()->value(),
        ];
    }

    public function aggregateType(): string
    {
        return 'product';
    }

    public function aggregateId(): int
    {
        return $this->productId;
    }

    public function payload(): array
    {
        return $this->state;
    }
}
