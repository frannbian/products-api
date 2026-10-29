<?php

namespace App\Infrastructure\Catalog\Persistence;

use App\Domain\Catalog\Entities\Product;
use App\Domain\Catalog\Repositories\ProductRepository;
use App\Domain\Catalog\ValueObjects\ProductId;
use App\Domain\Currency\ValueObjects\CurrencyId;
use App\Domain\Shared\Exceptions\ConcurrencyConflict;
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
        // Retried by Laravel when the database aborts it on a deadlock.
        return DB::transaction(function () use ($product) {
            $model = $product->id() === null
                ? ProductModel::query()->forceCreate([...ProductMapper::attributes($product), 'version' => 1])
                : $this->updateIfUnchanged($product);

            foreach ($product->prices() as $price) {
                ProductPriceModel::query()->updateOrCreate(
                    ['product_id' => $model->id, 'currency_id' => $price->currencyId()->value()],
                    ['price' => $price->amount()->value()],
                );
            }

            return ProductMapper::toDomain($model->load('prices'));
        }, attempts: 3);
    }

    /**
     * Optimistic locking: the row is only written if it still has the version
     * that was read, in a single atomic statement.
     *
     * @throws ConcurrencyConflict
     */
    private function updateIfUnchanged(Product $product): ProductModel
    {
        $id = $product->id()->value();

        $updated = ProductModel::query()
            ->whereKey($id)
            ->where('version', $product->version())
            ->update([...ProductMapper::attributes($product), 'version' => $product->version() + 1]);

        if ($updated === 0) {
            throw new ConcurrencyConflict('product', $id);
        }

        return ProductModel::query()->findOrFail($id);
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
