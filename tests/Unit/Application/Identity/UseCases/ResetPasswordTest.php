<?php

use App\Application\Identity\Contracts\PasswordResetBroker;
use App\Application\Identity\DTOs\ResetPasswordData;
use App\Application\Identity\Enums\PasswordResetStatus;
use App\Application\Identity\Exceptions\PasswordResetFailed;
use App\Application\Identity\UseCases\ResetPassword;
use App\Domain\Identity\Entities\User;
use App\Domain\Identity\ValueObjects\Email;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Contracts\Events\Dispatcher;
use Tests\Support\FakePasswordHasher;
use Tests\Support\InMemoryUserRepository;

beforeEach(function () {
    $this->broker = Mockery::mock(PasswordResetBroker::class);
    $this->users = new InMemoryUserRepository;
    $this->events = Mockery::spy(Dispatcher::class);

    $this->useCase = new ResetPassword($this->broker, $this->users, new FakePasswordHasher, $this->events);

    $this->data = ResetPasswordData::fromArray([
        'email' => 'john@example.com',
        'token' => 'reset-token',
        'password' => 'new-password',
    ]);

    $this->user = storedUserIn($this->users);
});

function brokerResetsPasswordOf(User $user): Closure
{
    return fn (Email $email, string $token, Closure $callback) => tap(
        PasswordResetStatus::PasswordReset,
        fn () => $callback($user),
    );
}

test('it resets the password using the given email and token', function () {
    $this->broker->expects('reset')
        ->withArgs(fn (Email $email, string $token) => $email->value() === 'john@example.com'
            && $token === 'reset-token')
        ->andReturnUsing(brokerResetsPasswordOf($this->user));

    expect($this->useCase->execute($this->data))->toBe(PasswordResetStatus::PasswordReset);
});

test('it stores the new password hashed', function () {
    $this->broker->allows('reset')->andReturnUsing(brokerResetsPasswordOf($this->user));

    $this->useCase->execute($this->data);

    expect($this->users->findById($this->user->id())->password()->value())->toBe('hashed:new-password');
});

test('it dispatches the password reset event', function () {
    $this->broker->allows('reset')->andReturnUsing(brokerResetsPasswordOf($this->user));

    $this->useCase->execute($this->data);

    $this->events->shouldHaveReceived('dispatch')
        ->once()
        ->with(Mockery::on(fn ($event) => $event instanceof PasswordReset && $event->user === $this->user));
});

test('it throws when the password could not be reset', function () {
    $this->broker->allows('reset')->andReturn(PasswordResetStatus::InvalidToken);

    try {
        $this->useCase->execute($this->data);
        $this->fail('Expected PasswordResetFailed to be thrown.');
    } catch (PasswordResetFailed $e) {
        expect($e->status)->toBe(PasswordResetStatus::InvalidToken);
    }

    expect($this->users->findById($this->user->id())->password()->value())->toBe('hashed-password');
});
