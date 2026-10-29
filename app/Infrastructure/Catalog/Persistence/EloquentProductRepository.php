<?php

namespace App\Infrastructure\Catalog\Persistence;

use App\Domain\Catalog\Entities\Product;
use App\Domain\Catalog\Repositories\ProductRepository;
use App\Domain\Catalog\ValueObjects\ProductId;
use App\Domain\Currency\ValueObjects\CurrencyId;
use App\Domain\Shared\Paginated;
use App\Infrastructure\Catalog\Persistence\Eloquent\ProductMapper;
use App\Infrastructure\Catalog\Persistence\Eloquent\ProductModel;
use App\Infrastructure\Catalog\Persistence\Eloquent\ProductPriceModel;
use Illuminate\Support\Facades\DB;

final class EloquentProductRepository implements ProductRepository
{
    public function paginate(int $page, int $perPage): Paginated
    {
        $paginator = ProductModel::query()
            ->with('prices')
            ->orderBy('id')
            ->paginate(perPage: $perPage, page: $page);

        return new Paginated(
            $paginator->getCollection()->map(ProductMapper::toDomain(...))->all(),
            $paginator->total(),
            $paginator->currentPage(),
            $paginator->perPage(),
        );
    }

    public function findById(ProductId $id): ?Product
    {
        $model = ProductModel::with('prices')->find($id->value());

        return $model ? ProductMapper::toDomain($model) : null;
    }

    public function findByIdForUpdate(ProductId $id): ?Product
    {
        // SELECT ... FOR UPDATE on the product row only: the aggregate root lock.
        $model = ProductModel::query()->whereKey($id->value())->lockForUpdate()->first();

        return $model ? ProductMapper::toDomain($model->load('prices')) : null;
    }

    public function exists(ProductId $id): bool
    {
        return ProductModel::query()->whereKey($id->value())->exists();
    }

    public function paginatePrices(ProductId $id, int $page, int $perPage): Paginated
    {
        $paginator = ProductPriceModel::query()
            ->where('product_id', $id->value())
            ->orderBy('id')
            ->paginate(perPage: $perPage, page: $page);

        return new Paginated(
            $paginator->getCollection()->map(ProductMapper::priceToDomain(...))->all(),
            $paginator->total(),
            $paginator->currentPage(),
            $paginator->perPage(),
        );
    }

    public function save(Product $product): Product
    {
        // Product and prices are written atomically. Inside a use case this
        // joins its transaction, keeping the lock taken by findByIdForUpdate.
        return DB::transaction(function () use ($product) {
            $model = $product->id() === null
                ? new ProductModel
                : ProductModel::query()->findOrFail($product->id()->value());

            $model->fill(ProductMapper::attributes($product))->save();

            foreach ($product->prices() as $price) {
                ProductPriceModel::query()->updateOrCreate(
                    ['product_id' => $model->id, 'currency_id' => $price->currencyId()->value()],
                    ['price' => $price->amount()->value()],
                );
            }

            return ProductMapper::toDomain($model->load('prices'));
        });
    }

    public function delete(ProductId $id): void
    {
        ProductModel::query()->whereKey($id->value())->delete();
    }

    public function existsWithCurrency(CurrencyId $id): bool
    {
        return ProductModel::withTrashed()->where('currency_id', $id->value())->exists()
            || ProductPriceModel::query()->where('currency_id', $id->value())->exists();
    }
}
