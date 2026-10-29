<?php

namespace App\Domain\Catalog\Events;

final class ProductCreated extends ProductStateChanged
{
    public function name(): string
    {
        return 'product.created';
    }
}
