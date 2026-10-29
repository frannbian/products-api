<?php

namespace App\Application\Catalog\UseCases;

use App\Application\Shared\Contracts\EventPublisher;
use App\Application\Shared\Contracts\TransactionManager;
use App\Domain\Catalog\Events\ProductDeleted;
use App\Domain\Catalog\Exceptions\ProductNotFound;
use App\Domain\Catalog\Repositories\ProductRepository;
use App\Domain\Catalog\ValueObjects\ProductId;

final class DeleteProduct
{
    public function __construct(
        private ProductRepository $products,
        private TransactionManager $transactions,
        private EventPublisher $events,
    ) {}

    /**
     * @throws ProductNotFound
     */
    public function execute(ProductId $id): void
    {
        $this->transactions->run(function () use ($id) {
            if ($this->products->findByIdForUpdate($id) === null) {
                throw new ProductNotFound($id);
            }

            $this->products->delete($id);

            $this->events->publish(new ProductDeleted($id));
        });
    }
}
