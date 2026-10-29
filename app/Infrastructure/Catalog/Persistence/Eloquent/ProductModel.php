<?php

namespace App\Infrastructure\Catalog\Persistence\Eloquent;

use App\Infrastructure\Currency\Persistence\Eloquent\CurrencyModel;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $name
 * @property string $description
 * @property string $price
 * @property int $currency_id
 * @property string $tax_cost
 * @property string $manufacturing_cost
 * @property-read Collection<int, ProductPriceModel> $prices
 */
#[Table('products')]
#[Fillable(['name', 'description', 'price', 'currency_id', 'tax_cost', 'manufacturing_cost'])]
#[UseFactory(ProductFactory::class)]
class ProductModel extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory, SoftDeletes;

    /**
     * @return BelongsTo<CurrencyModel, $this>
     */
    public function currency(): BelongsTo
    {
        return $this->belongsTo(CurrencyModel::class);
    }

    /**
     * @return HasMany<ProductPriceModel, $this>
     */
    public function prices(): HasMany
    {
        return $this->hasMany(ProductPriceModel::class, 'product_id')->orderBy('id');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'tax_cost' => 'decimal:2',
            'manufacturing_cost' => 'decimal:2',
        ];
    }
}
