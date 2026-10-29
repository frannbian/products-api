<?php

namespace App\Application\Catalog\UseCases;

use App\Domain\Catalog\Entities\ProductPrice;
use App\Domain\Catalog\Exceptions\ProductNotFound;
use App\Domain\Catalog\Repositories\ProductRepository;
use App\Domain\Catalog\ValueObjects\ProductId;
use App\Domain\Shared\Paginated;

final class ListProductPrices
{
    public function __construct(
        private ProductRepository $products,
    ) {}

    /**
     * Pages through the prices without loading the whole aggregate.
     *
     * @return Paginated<ProductPrice>
     *
     * @throws ProductNotFound
     */
    public function execute(ProductId $id, int $page, int $perPage): Paginated
    {
        if (! $this->products->exists($id)) {
            throw new ProductNotFound($id);
        }

        return $this->products->paginatePrices($id, $page, $perPage);
    }
}
