<?php

namespace App\Infrastructure\Identity\Listeners;

use App\Domain\Identity\Entities\User;
use App\Infrastructure\Identity\Notifications\WelcomeNotification;
use App\Infrastructure\Identity\Persistence\Eloquent\UserModel;
use Illuminate\Auth\Events\Registered;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;

/**
 * Sends the welcome email in the background: registering answers right away
 * and a slow or failing mail server never affects it.
 */
class SendWelcomeEmail implements ShouldQueueAfterCommit
{
    public string $queue = 'mail';

    public int $tries = 5;

    /**
     * Seconds to wait before each retry, growing to give the mail server time.
     *
     * @return list<int>
     */
    public function backoff(): array
    {
        return [10, 60, 300];
    }

    public function handle(Registered $event): void
    {
        /** @var User $user */
        $user = $event->user;

        // The user may be gone by the time the message is processed: nothing
        // to send, and failing would only retry the same outcome.
        UserModel::query()->find($user->id()->value())?->notify(new WelcomeNotification);
    }
}
