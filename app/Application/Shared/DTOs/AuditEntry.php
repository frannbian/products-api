<?php

namespace App\Application\Shared\DTOs;

use DateTimeImmutable;

final readonly class AuditEntry
{
    /**
     * @param  array<string, scalar|null>  $payload
     */
    public function __construct(
        public string $eventId,
        public string $event,
        public ?int $actorId,
        public array $payload,
        public DateTimeImmutable $occurredAt,
    ) {}
}
