<?php

namespace App\Application\Catalog\UseCases;

use App\Application\Catalog\Concerns\EnsuresCurrencyExists;
use App\Application\Catalog\DTOs\ProductData;
use App\Application\Catalog\Exceptions\UnknownCurrency;
use App\Domain\Catalog\Entities\Product;
use App\Domain\Catalog\Repositories\ProductRepository;
use App\Domain\Currency\Repositories\CurrencyRepository;

final class CreateProduct
{
    use EnsuresCurrencyExists;

    public function __construct(
        private ProductRepository $products,
        private CurrencyRepository $currencies,
    ) {}

    /**
     * @throws UnknownCurrency
     */
    public function execute(ProductData $data): Product
    {
        $this->ensureCurrencyExists($data->currencyId);

        return $this->products->save(Product::create(
            $data->name,
            $data->description,
            $data->price,
            $data->currencyId,
            $data->taxCost,
            $data->manufacturingCost,
        ));
    }
}
