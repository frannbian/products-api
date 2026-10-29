<?php

namespace Tests\Support;

use App\Application\Shared\Contracts\EventPublisher;
use App\Domain\Shared\Events\DomainEvent;

final class FakeEventPublisher implements EventPublisher
{
    /** @var list<DomainEvent> */
    public array $published = [];

    public function publish(DomainEvent ...$events): void
    {
        array_push($this->published, ...$events);
    }

    /**
     * @return list<string>
     */
    public function names(): array
    {
        return array_map(fn (DomainEvent $event) => $event->name(), $this->published);
    }
}
