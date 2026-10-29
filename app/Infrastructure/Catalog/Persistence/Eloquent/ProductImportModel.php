<?php

namespace App\Infrastructure\Catalog\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\WithoutIncrementing;
use Illuminate\Database\Eloquent\Attributes\WithoutTimestamps;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property int $owner_id
 * @property string $status
 * @property list<array<string, mixed>> $rows
 * @property int $processed
 * @property int $succeeded
 * @property list<array{row: int|null, message: string}> $errors
 * @property Carbon $created_at
 * @property Carbon|null $finished_at
 */
#[Table('product_imports', keyType: 'string')]
#[WithoutIncrementing]
#[WithoutTimestamps]
class ProductImportModel extends Model
{
    protected $dateFormat = 'Y-m-d H:i:s.uP';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'rows' => 'array',
            'errors' => 'array',
            'created_at' => 'immutable_datetime',
            'finished_at' => 'immutable_datetime',
        ];
    }
}
