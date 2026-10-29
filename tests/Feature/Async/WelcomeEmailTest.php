<?php

use App\Infrastructure\Identity\Listeners\SendWelcomeEmail;
use App\Infrastructure\Identity\Notifications\WelcomeNotification;
use App\Infrastructure\Identity\Persistence\Eloquent\UserModel;
use Illuminate\Events\CallQueuedListener;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;

function registerUser(object $test): void
{
    $test->postJson('/api/auth/register', [
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertCreated();
}

test('registering queues the welcome email on the mail queue', function () {
    Queue::fake();

    registerUser($this);

    Queue::assertPushedOn('mail', CallQueuedListener::class, fn (CallQueuedListener $job) => $job->class === SendWelcomeEmail::class);
});

test('the registration responds without waiting for the email', function () {
    Queue::fake();
    Notification::fake();

    registerUser($this);

    Notification::assertNothingSent();
});

test('the queued listener emails the new user', function () {
    Notification::fake();

    registerUser($this);

    Notification::assertSentTo(UserModel::firstWhere('email', 'jane@example.com'), WelcomeNotification::class);
});

test('the welcome email greets the user by name', function () {
    $user = UserModel::factory()->create(['name' => 'Jane Doe']);

    $mail = (new WelcomeNotification)->toMail($user);

    expect($mail->subject)->toBe(__('mail.welcome.subject', ['app' => config('app.name')]))
        ->and($mail->greeting)->toBe(__('mail.welcome.greeting', ['name' => 'Jane Doe']));
});

test('a user deleted before the job runs is skipped without failing', function () {
    Notification::fake();
    Queue::fake();
    registerUser($this);
    UserModel::query()->delete();

    // Run the listener with the event exactly as it was queued.
    $queued = Queue::pushedJobs()[CallQueuedListener::class][0]['job'];
    (new SendWelcomeEmail)->handle(...$queued->data);

    Notification::assertNothingSent();
});

test('the listener retries with backoff', function () {
    $listener = new SendWelcomeEmail;

    expect($listener->tries)->toBe(5)
        ->and($listener->backoff())->toBe([10, 60, 300]);
});
