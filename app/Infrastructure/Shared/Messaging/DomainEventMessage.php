<?php

namespace App\Infrastructure\Shared\Messaging;

/**
 * Envelope in which a domain event travels through the queue: primitives
 * only, plus who caused it. Queued listeners of this message are its
 * consumers; each one receives its own copy.
 */
final readonly class DomainEventMessage
{
    /**
     * @param  array<string, scalar|null>  $payload
     */
    public function __construct(
        public string $eventId,
        public string $name,
        public string $aggregateType,
        public int $aggregateId,
        public array $payload,
        public string $occurredAt,
        public ?int $actorId,
    ) {}
}
