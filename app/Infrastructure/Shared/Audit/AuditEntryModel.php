<?php

namespace App\Infrastructure\Shared\Audit;

use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\WithoutTimestamps;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $event_id
 * @property string $event
 * @property string $aggregate_type
 * @property int $aggregate_id
 * @property int|null $actor_id
 * @property array<string, scalar|null> $payload
 * @property Carbon $occurred_at
 * @property Carbon $recorded_at
 */
#[Table('audit_log')]
#[WithoutTimestamps]
class AuditEntryModel extends Model
{
    /** Microseconds and time zone, matching the timestamptz(6) columns. */
    public const DATE_FORMAT = 'Y-m-d H:i:s.uP';

    protected $dateFormat = self::DATE_FORMAT;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'occurred_at' => 'immutable_datetime',
            'recorded_at' => 'immutable_datetime',
        ];
    }
}
