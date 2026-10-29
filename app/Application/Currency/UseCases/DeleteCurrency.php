<?php

namespace App\Application\Currency\UseCases;

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
    ) {}

    /**
     * @throws CurrencyNotFound
     * @throws CurrencyInUse
     */
    public function execute(CurrencyId $id): void
    {
        if ($this->currencies->findById($id) === null) {
            throw new CurrencyNotFound($id);
        }

        if ($this->products->existsWithCurrency($id)) {
            throw new CurrencyInUse($id);
        }

        $this->currencies->delete($id);
    }
}
