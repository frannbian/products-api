<?php

namespace App\Application\Catalog\UseCases;

use App\Domain\Catalog\Entities\Product;
use App\Domain\Catalog\Repositories\ProductRepository;
use App\Domain\Shared\Paginated;

final class ListProducts
{
    public function __construct(
        private ProductRepository $products,
    ) {}

    /**
     * @return Paginated<Product>
     */
    public function execute(int $page, int $perPage): Paginated
    {
        return $this->products->paginate($page, $perPage);
    }
}
