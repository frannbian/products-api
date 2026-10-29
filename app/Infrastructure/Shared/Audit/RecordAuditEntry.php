<?php

namespace App\Infrastructure\Shared\Audit;

use App\Infrastructure\Shared\Messaging\DomainEventMessage;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Support\Carbon;

/**
 * Consumer of domain event messages that keeps an audit trail.
 *
 * SQS delivers at least once, so the same message may arrive twice: the
 * entry is inserted by its event id and duplicates are ignored, which makes
 * processing a message more than once harmless.
 */
class RecordAuditEntry implements ShouldQueueAfterCommit
{
    public string $queue = 'catalog-events';

    public int $tries = 5;

    /**
     * @return list<int>
     */
    public function backoff(): array
    {
        return [5, 30, 120];
    }

    public function handle(DomainEventMessage $message): void
    {
        AuditEntryModel::query()->insertOrIgnore([
            'event_id' => $message->eventId,
            'event' => $message->name,
            'aggregate_type' => $message->aggregateType,
            'aggregate_id' => $message->aggregateId,
            'actor_id' => $message->actorId,
            'payload' => json_encode($message->payload, JSON_THROW_ON_ERROR),
            // Explicit format: the query builder would drop the fraction of
            // a second, and two changes in the same second would lose order.
            'occurred_at' => Carbon::parse($message->occurredAt)->format(AuditEntryModel::DATE_FORMAT),
            'recorded_at' => Carbon::now()->format(AuditEntryModel::DATE_FORMAT),
        ]);
    }
}
