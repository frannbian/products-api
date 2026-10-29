<?php

namespace App\Domain\Shared\Events;

use App\Domain\Shared\Uuid;
use DateTimeImmutable;

/**
 * Identity and time of an event occurrence.
 */
trait RecordsOccurrence
{
    private string $eventId;

    private DateTimeImmutable $occurredAt;

    private function recordOccurrence(): void
    {
        $this->eventId = Uuid::v4();
        $this->occurredAt = new DateTimeImmutable;
    }

    public function eventId(): string
    {
        return $this->eventId;
    }

    public function occurredAt(): DateTimeImmutable
    {
        return $this->occurredAt;
    }
}
