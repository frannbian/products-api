<?php

namespace App\Domain\Catalog\Exceptions;

use App\Domain\Catalog\ValueObjects\ProductId;
use DomainException;

final class ProductNotFound extends DomainException
{
    public function __construct(ProductId $id)
    {
        parent::__construct("The product {$id->value()} does not exist.");
    }
}
