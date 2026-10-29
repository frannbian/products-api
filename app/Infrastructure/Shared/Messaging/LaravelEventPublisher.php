<?php

namespace App\Infrastructure\Shared\Messaging;

use App\Application\Shared\Contracts\EventPublisher;
use App\Domain\Shared\Events\DomainEvent;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Contracts\Events\Dispatcher;

/**
 * Publishes domain events as messages through Laravel's event dispatcher.
 * Their consumers are queued listeners, so publishing only enqueues work:
 * nothing slows down or breaks the request that caused the event.
 */
final class LaravelEventPublisher implements EventPublisher
{
    public function __construct(
        private Dispatcher $dispatcher,
        private AuthFactory $auth,
    ) {}

    public function publish(DomainEvent ...$events): void
    {
        $actorId = $this->auth->guard('api')->id();

        foreach ($events as $event) {
            $this->dispatcher->dispatch(new DomainEventMessage(
                eventId: $event->eventId(),
                name: $event->name(),
                aggregateType: $event->aggregateType(),
                aggregateId: $event->aggregateId(),
                payload: $event->payload(),
                occurredAt: $event->occurredAt()->format(DATE_RFC3339_EXTENDED),
                actorId: $actorId === null ? null : (int) $actorId,
            ));
        }
    }
}
