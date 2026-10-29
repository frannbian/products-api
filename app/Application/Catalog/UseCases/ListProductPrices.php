<?php

namespace App\Application\Catalog\UseCases;

use App\Domain\Catalog\Entities\ProductPrice;
use App\Domain\Catalog\Exceptions\ProductNotFound;
use App\Domain\Catalog\Repositories\ProductRepository;
use App\Domain\Catalog\ValueObjects\ProductId;

final class ListProductPrices
{
    public function __construct(
        private ProductRepository $products,
    ) {}

    /**
     * @return list<ProductPrice>
     *
     * @throws ProductNotFound
     */
    public function execute(ProductId $id): array
    {
        $product = $this->products->findById($id) ?? throw new ProductNotFound($id);

        return $product->prices();
    }
}
