<?php

namespace Tests\Support;

use App\Domain\Catalog\Entities\Product;
use App\Domain\Catalog\Entities\ProductPrice;
use App\Domain\Catalog\Repositories\ProductRepository;
use App\Domain\Catalog\ValueObjects\ProductId;
use App\Domain\Catalog\ValueObjects\ProductPriceId;
use App\Domain\Currency\ValueObjects\CurrencyId;
use App\Domain\Shared\Paginated;

final class InMemoryProductRepository implements ProductRepository
{
    /** @var array<int, Product> */
    private array $products = [];

    /** @var array<int, Product> */
    private array $deleted = [];

    private int $nextId = 1;

    private int $nextPriceId = 1;

    public function paginate(int $page, int $perPage): Paginated
    {
        $all = array_values($this->products);

        return new Paginated(
            array_slice($all, ($page - 1) * $perPage, $perPage),
            count($all),
            $page,
            $perPage,
        );
    }

    public function findById(ProductId $id): ?Product
    {
        return $this->products[$id->value()] ?? null;
    }

    public function save(Product $product): Product
    {
        $product = Product::reconstitute(
            $product->id() ?? new ProductId($this->nextId++),
            $product->name(),
            $product->description(),
            $product->price(),
            $product->currencyId(),
            $product->taxCost(),
            $product->manufacturingCost(),
            array_map(fn (ProductPrice $price) => $price->id() === null
                ? ProductPrice::reconstitute(new ProductPriceId($this->nextPriceId++), $price->currencyId(), $price->amount())
                : $price, $product->prices()),
        );

        return $this->products[$product->id()->value()] = $product;
    }

    public function delete(ProductId $id): void
    {
        $this->deleted[$id->value()] = $this->products[$id->value()];
        unset($this->products[$id->value()]);
    }

    public function existsWithCurrency(CurrencyId $id): bool
    {
        foreach ([...$this->products, ...$this->deleted] as $product) {
            if ($product->currencyId()->equals($id) || $product->priceIn($id) !== null) {
                return true;
            }
        }

        return false;
    }
}
