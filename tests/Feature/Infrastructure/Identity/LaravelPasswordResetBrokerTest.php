<?php

use App\Application\Identity\Enums\PasswordResetStatus;
use App\Domain\Identity\Entities\User;
use App\Domain\Identity\ValueObjects\Email;
use App\Infrastructure\Identity\Authentication\LaravelPasswordResetBroker;
use App\Infrastructure\Identity\Persistence\Eloquent\UserModel;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;

beforeEach(function () {
    Notification::fake();
    $this->broker = app(LaravelPasswordResetBroker::class);
});

test('it sends a reset link to an existing user', function () {
    $model = UserModel::factory()->create(['email' => 'john@example.com']);

    expect($this->broker->sendResetLink(new Email('john@example.com')))->toBe(PasswordResetStatus::LinkSent);
    Notification::assertSentTo($model, ResetPassword::class);
});

test('it reports an unknown user', function () {
    expect($this->broker->sendResetLink(new Email('ghost@example.com')))->toBe(PasswordResetStatus::InvalidUser);
});

test('it reports throttled requests', function () {
    UserModel::factory()->create(['email' => 'john@example.com']);

    $this->broker->sendResetLink(new Email('john@example.com'));

    expect($this->broker->sendResetLink(new Email('john@example.com')))->toBe(PasswordResetStatus::Throttled);
});

test('it runs the callback with the domain user when the token is valid', function () {
    $model = UserModel::factory()->create(['email' => 'john@example.com']);
    $token = Password::createToken($model);
    $resetUser = null;

    $status = $this->broker->reset(new Email('john@example.com'), $token, function (User $user) use (&$resetUser) {
        $resetUser = $user;
    });

    expect($status)->toBe(PasswordResetStatus::PasswordReset)
        ->and($resetUser)->toBeInstanceOf(User::class)
        ->and($resetUser->id()->value())->toBe($model->id);
});

test('it reports an invalid token without running the callback', function () {
    UserModel::factory()->create(['email' => 'john@example.com']);
    $called = false;

    $status = $this->broker->reset(new Email('john@example.com'), 'invalid-token', function () use (&$called) {
        $called = true;
    });

    expect($status)->toBe(PasswordResetStatus::InvalidToken)
        ->and($called)->toBeFalse();
});
