<?php

namespace App\Infrastructure\Shared\Audit;

use App\Application\Shared\Contracts\AuditTrail;
use App\Application\Shared\DTOs\AuditEntry;
use App\Domain\Shared\Paginated;

final class EloquentAuditTrail implements AuditTrail
{
    public function forAggregate(string $type, int $id, int $page, int $perPage): Paginated
    {
        $paginator = AuditEntryModel::query()
            ->where('aggregate_type', $type)
            ->where('aggregate_id', $id)
            ->orderBy('occurred_at')
            ->orderBy('id')
            ->paginate(perPage: $perPage, page: $page);

        return new Paginated(
            $paginator->getCollection()->map(fn (AuditEntryModel $entry) => new AuditEntry(
                $entry->event_id,
                $entry->event,
                $entry->actor_id,
                $entry->payload,
                $entry->occurred_at,
            ))->all(),
            $paginator->total(),
            $paginator->currentPage(),
            $paginator->perPage(),
        );
    }
}
