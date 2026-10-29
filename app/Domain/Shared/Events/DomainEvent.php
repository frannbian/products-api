<?php

namespace App\Domain\Shared\Events;

use DateTimeImmutable;

/**
 * Something that happened in the domain. Carries primitives only, so it can
 * be published as a message and read by anyone, in any language.
 */
interface DomainEvent
{
    /**
     * Unique per occurrence: consumers use it to ignore redelivered messages.
     */
    public function eventId(): string;

    /**
     * Stable name consumers subscribe to, e.g. "product.created".
     */
    public function name(): string;

    public function aggregateType(): string;

    public function aggregateId(): int;

    public function occurredAt(): DateTimeImmutable;

    /**
     * @return array<string, scalar|null>
     */
    public function payload(): array;
}
