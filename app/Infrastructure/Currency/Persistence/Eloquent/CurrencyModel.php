<?php

namespace App\Infrastructure\Currency\Persistence\Eloquent;

use Database\Factories\CurrencyFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $version
 * @property string $name
 * @property string $symbol
 * @property string $exchange_rate
 */
#[Table('currencies')]
#[Fillable(['name', 'symbol', 'exchange_rate'])]
#[UseFactory(CurrencyFactory::class)]
class CurrencyModel extends Model
{
    /** @use HasFactory<CurrencyFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'exchange_rate' => 'decimal:6',
        ];
    }
}
