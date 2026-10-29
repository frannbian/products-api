<?php

namespace App\Application\Shared\Contracts;

use App\Domain\Shared\Events\DomainEvent;

/**
 * Publishes domain events for other parts of the system to react to,
 * asynchronously. Called inside the use case transaction: events are only
 * delivered if it commits.
 */
interface EventPublisher
{
    public function publish(DomainEvent ...$events): void;
}
