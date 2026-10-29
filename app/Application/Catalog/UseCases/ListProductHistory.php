<?php

namespace App\Application\Catalog\UseCases;

use App\Application\Shared\Contracts\AuditTrail;
use App\Application\Shared\DTOs\AuditEntry;
use App\Domain\Catalog\ValueObjects\ProductId;
use App\Domain\Shared\Paginated;

/**
 * Changes made to a product, oldest first. Deleted products keep their
 * history. Entries are recorded asynchronously, so the latest change may
 * take a moment to show up.
 */
final class ListProductHistory
{
    public function __construct(
        private AuditTrail $auditTrail,
    ) {}

    /**
     * @return Paginated<AuditEntry>
     */
    public function execute(ProductId $id, int $page, int $perPage): Paginated
    {
        return $this->auditTrail->forAggregate('product', $id->value(), $page, $perPage);
    }
}
