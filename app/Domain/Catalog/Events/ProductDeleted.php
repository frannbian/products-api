<?php

namespace App\Domain\Catalog\Events;

use App\Domain\Catalog\ValueObjects\ProductId;
use App\Domain\Shared\Events\DomainEvent;
use App\Domain\Shared\Events\RecordsOccurrence;

final class ProductDeleted implements DomainEvent
{
    use RecordsOccurrence;

    private int $productId;

    public function __construct(ProductId $id)
    {
        $this->recordOccurrence();
        $this->productId = $id->value();
    }

    public function name(): string
    {
        return 'product.deleted';
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
        return [];
    }
}
