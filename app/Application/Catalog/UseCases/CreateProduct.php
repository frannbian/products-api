<?php

namespace App\Application\Catalog\UseCases;

use App\Application\Catalog\Concerns\EnsuresCurrencyExists;
use App\Application\Catalog\DTOs\ProductData;
use App\Application\Catalog\Exceptions\UnknownCurrency;
use App\Application\Shared\Contracts\EventPublisher;
use App\Domain\Catalog\Entities\Product;
use App\Domain\Catalog\Events\ProductCreated;
use App\Domain\Catalog\Repositories\ProductRepository;
use App\Domain\Currency\Repositories\CurrencyRepository;

final class CreateProduct
{
    use EnsuresCurrencyExists;

    public function __construct(
        private ProductRepository $products,
        private CurrencyRepository $currencies,
        private EventPublisher $events,
    ) {}

    /**
     * @throws UnknownCurrency
     */
    public function execute(ProductData $data): Product
    {
        $this->ensureCurrencyExists($data->currencyId);

        $product = $this->products->save(Product::create(
            $data->name,
            $data->description,
            $data->price,
            $data->currencyId,
            $data->taxCost,
            $data->manufacturingCost,
        ));

        $this->events->publish(new ProductCreated($product));

        return $product;
    }
}
