<?php

namespace App\Application\Catalog\UseCases;

use App\Domain\Catalog\Exceptions\ProductNotFound;
use App\Domain\Catalog\Repositories\ProductRepository;
use App\Domain\Catalog\ValueObjects\ProductId;

final class DeleteProduct
{
    public function __construct(
        private ProductRepository $products,
    ) {}

    /**
     * @throws ProductNotFound
     */
    public function execute(ProductId $id): void
    {
        if ($this->products->findById($id) === null) {
            throw new ProductNotFound($id);
        }

        $this->products->delete($id);
    }
}
