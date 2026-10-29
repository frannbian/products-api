<?php

namespace App\Domain\Catalog\Events;

use App\Domain\Catalog\ValueObjects\Amount;
use App\Domain\Catalog\ValueObjects\ProductId;
use App\Domain\Currency\ValueObjects\CurrencyId;
use App\Domain\Shared\Events\DomainEvent;
use App\Domain\Shared\Events\RecordsOccurrence;

final class ProductPriceSet implements DomainEvent
{
    use RecordsOccurrence;

    private int $productId;

    /** @var array{currency_id: int, price: string, created: bool} */
    private array $price;

    /**
     * @param  bool  $created  False when it replaced an existing price.
     */
    public function __construct(ProductId $id, CurrencyId $currencyId, Amount $amount, bool $created)
    {
        $this->recordOccurrence();
        $this->productId = $id->value();
        $this->price = ['currency_id' => $currencyId->value(), 'price' => $amount->value(), 'created' => $created];
    }

    public function name(): string
    {
        return 'product.price_set';
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
        return $this->price;
    }
}
