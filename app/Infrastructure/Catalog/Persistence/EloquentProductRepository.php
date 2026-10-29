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

    public function save(Product $product): Product
    {
        return DB::transaction(function () use ($product) {
            $model = $product->id() === null
                ? new ProductModel
                : ProductModel::findOrFail($product->id()->value());

            ProductMapper::fill($model, $product)->save();

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
