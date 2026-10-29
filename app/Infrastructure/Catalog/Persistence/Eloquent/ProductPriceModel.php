<?php

namespace App\Infrastructure\Catalog\Persistence\Eloquent;

use App\Infrastructure\Currency\Persistence\Eloquent\CurrencyModel;
use Database\Factories\ProductPriceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $product_id
 * @property int $currency_id
 * @property string $price
 */
#[Table('product_prices')]
#[Fillable(['product_id', 'currency_id', 'price'])]
#[UseFactory(ProductPriceFactory::class)]
class ProductPriceModel extends Model
{
    /** @use HasFactory<ProductPriceFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<ProductModel, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(ProductModel::class, 'product_id');
    }

    /**
     * @return BelongsTo<CurrencyModel, $this>
     */
    public function currency(): BelongsTo
    {
        return $this->belongsTo(CurrencyModel::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
        ];
    }
}
