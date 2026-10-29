<?php

namespace App\Application\Shared\Contracts;

use App\Application\Shared\DTOs\AuditEntry;
use App\Domain\Shared\Paginated;

/**
 * Read side of the audit trail, filled asynchronously from domain events.
 */
interface AuditTrail
{
    /**
     * Entries of one aggregate, oldest first.
     *
     * @return Paginated<AuditEntry>
     */
    public function forAggregate(string $type, int $id, int $page, int $perPage): Paginated;
}
