<?php

use App\Application\Identity\DTOs\RegisterUserData;
use App\Application\Identity\UseCases\RegisterUser;
use App\Domain\Identity\Exceptions\EmailAlreadyRegistered;
use Illuminate\Auth\Events\Registered;
use Illuminate\Contracts\Events\Dispatcher;
use Tests\Support\FakePasswordHasher;
use Tests\Support\FakeTokenService;
use Tests\Support\InMemoryUserRepository;

beforeEach(function () {
    $this->users = new InMemoryUserRepository;
    $this->events = Mockery::spy(Dispatcher::class);

    $this->useCase = new RegisterUser($this->users, new FakePasswordHasher, new FakeTokenService, $this->events);

    $this->data = RegisterUserData::fromArray([
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'password' => 'secret-password',
    ]);
});

test('it persists the new user with the hashed password', function () {
    $user = $this->useCase->execute($this->data)->user;

    expect($user->id())->not->toBeNull()
        ->and($this->users->findById($user->id()))->toBe($user)
        ->and($user->name()->value())->toBe('John Doe')
        ->and($user->email()->value())->toBe('john@example.com')
        ->and($user->password()->value())->toBe('hashed:secret-password')
        ->and($user->hasVerifiedEmail())->toBeFalse();
});

test('it issues an access token for the new user', function () {
    $result = $this->useCase->execute($this->data);

    expect($result->token->token)->toBe('token-for-'.$result->user->id()->value());
});

test('it rejects an email that is already registered', function () {
    $this->useCase->execute($this->data);

    $this->useCase->execute(RegisterUserData::fromArray([
        'name' => 'Another John',
        'email' => 'John@Example.com',
        'password' => 'another-password',
    ]));
})->throws(EmailAlreadyRegistered::class);

test('it does not persist a user with a taken email', function () {
    $this->useCase->execute($this->data);

    try {
        $this->useCase->execute($this->data);
    } catch (EmailAlreadyRegistered) {
    }

    expect($this->users->all())->toHaveCount(1);
});

test('it dispatches the registered event', function () {
    $user = $this->useCase->execute($this->data)->user;

    $this->events->shouldHaveReceived('dispatch')
        ->once()
        ->with(Mockery::on(fn ($event) => $event instanceof Registered && $event->user === $user));
});
