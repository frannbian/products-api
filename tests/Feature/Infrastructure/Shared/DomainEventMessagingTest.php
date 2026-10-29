<?php

use App\Application\Shared\Contracts\EventPublisher;
use App\Domain\Catalog\Events\ProductDeleted;
use App\Domain\Catalog\Events\ProductPriceSet;
use App\Domain\Catalog\ValueObjects\Amount;
use App\Domain\Catalog\ValueObjects\ProductId;
use App\Domain\Currency\ValueObjects\CurrencyId;
use App\Infrastructure\Identity\Persistence\Eloquent\UserModel;
use App\Infrastructure\Shared\Audit\AuditEntryModel;
use App\Infrastructure\Shared\Audit\RecordAuditEntry;
use App\Infrastructure\Shared\Messaging\DomainEventMessage;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Support\Facades\Event;

test('the publisher wraps every event in a message with the acting user', function () {
    Event::fake([DomainEventMessage::class]);
    $user = UserModel::factory()->create();
    auth('api')->setUser($user);
    $event = new ProductPriceSet(new ProductId(7), new CurrencyId(2), new Amount('9.5'), created: true);

    app(EventPublisher::class)->publish($event);

    Event::assertDispatched(DomainEventMessage::class, fn (DomainEventMessage $message) => $message->eventId === $event->eventId()
        && $message->name === 'product.price_set'
        && $message->aggregateType === 'product'
        && $message->aggregateId === 7
        && $message->payload === ['currency_id' => 2, 'price' => '9.50', 'created' => true]
        && $message->occurredAt === $event->occurredAt()->format(DATE_RFC3339_EXTENDED)
        && $message->actorId === $user->id);
});

test('events published without an authenticated user have no actor', function () {
    Event::fake([DomainEventMessage::class]);

    app(EventPublisher::class)->publish(new ProductDeleted(new ProductId(7)));

    Event::assertDispatched(DomainEventMessage::class, fn (DomainEventMessage $message) => $message->actorId === null);
});

test('the audit consumer is queued on catalog-events, after commit, with retries', function () {
    $listener = new RecordAuditEntry;

    expect($listener)->toBeInstanceOf(ShouldQueueAfterCommit::class)
        ->and($listener->queue)->toBe('catalog-events')
        ->and($listener->tries)->toBe(5)
        ->and($listener->backoff())->toBe([5, 30, 120]);
});

function deletedMessage(?int $actor = null): DomainEventMessage
{
    $event = new ProductDeleted(new ProductId(7));

    return new DomainEventMessage(
        $event->eventId(), $event->name(), $event->aggregateType(), $event->aggregateId(),
        $event->payload(), $event->occurredAt()->format(DATE_RFC3339_EXTENDED), $actor,
    );
}

test('the audit consumer records the message', function () {
    $user = UserModel::factory()->create();
    $message = deletedMessage($user->id);

    (new RecordAuditEntry)->handle($message);

    $entry = AuditEntryModel::sole();
    expect($entry->event_id)->toBe($message->eventId)
        ->and($entry->event)->toBe('product.deleted')
        ->and($entry->aggregate_type)->toBe('product')
        ->and($entry->aggregate_id)->toBe(7)
        ->and($entry->actor_id)->toBe($user->id)
        ->and($entry->payload)->toBe([])
        ->and($entry->occurred_at->format(DATE_RFC3339_EXTENDED))->toBe($message->occurredAt);
});

test('a redelivered message is recorded only once', function () {
    $message = deletedMessage();

    (new RecordAuditEntry)->handle($message);
    (new RecordAuditEntry)->handle($message);

    expect(AuditEntryModel::count())->toBe(1);
});
