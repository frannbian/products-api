<?php

namespace App\Application\Catalog\UseCases;

use App\Domain\Catalog\Entities\Product;
use App\Domain\Catalog\Exceptions\ProductNotFound;
use App\Domain\Catalog\Repositories\ProductRepository;
use App\Domain\Catalog\ValueObjects\ProductId;

final class GetProduct
{
    public function __construct(
        private ProductRepository $products,
    ) {}

    /**
     * @throws ProductNotFound
     */
    public function execute(ProductId $id): Product
    {
        return $this->products->findById($id) ?? throw new ProductNotFound($id);
    }
}
