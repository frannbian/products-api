<?php

namespace App\Application\Currency\UseCases;

use App\Application\Shared\Contracts\TransactionManager;
use App\Domain\Catalog\Repositories\ProductRepository;
use App\Domain\Currency\Exceptions\CurrencyInUse;
use App\Domain\Currency\Exceptions\CurrencyNotFound;
use App\Domain\Currency\Repositories\CurrencyRepository;
use App\Domain\Currency\ValueObjects\CurrencyId;

final class DeleteCurrency
{
    public function __construct(
        private CurrencyRepository $currencies,
        private ProductRepository $products,
        private TransactionManager $transactions,
    ) {}

    /**
     * The currency is locked before checking whether it is in use: a product
     * that starts using it at the same time has to wait for the foreign key
     * check on this row, so the check and the delete can not be interleaved.
     *
     * @throws CurrencyNotFound
     * @throws CurrencyInUse
     */
    public function execute(CurrencyId $id): void
    {
        $this->transactions->run(function () use ($id) {
            if ($this->currencies->findByIdForUpdate($id) === null) {
                throw new CurrencyNotFound($id);
            }

            if ($this->products->existsWithCurrency($id)) {
                throw new CurrencyInUse($id);
            }

            $this->currencies->delete($id);
        });
    }
}
