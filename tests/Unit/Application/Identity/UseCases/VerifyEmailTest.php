<?php

use App\Application\Identity\Exceptions\InvalidVerificationLink;
use App\Application\Identity\UseCases\VerifyEmail;
use App\Domain\Identity\Exceptions\UserNotFound;
use App\Domain\Identity\ValueObjects\UserId;
use Illuminate\Auth\Events\Verified;
use Illuminate\Contracts\Events\Dispatcher;
use Tests\Support\InMemoryUserRepository;

beforeEach(function () {
    $this->users = new InMemoryUserRepository;
    $this->events = Mockery::spy(Dispatcher::class);
    $this->useCase = new VerifyEmail($this->users, $this->events);
    $this->validHash = sha1('john@example.com');
});

test('it marks the email as verified and persists it', function () {
    $user = storedUserIn($this->users);

    $this->useCase->execute($user->id(), $this->validHash);

    expect($this->users->findById($user->id())->hasVerifiedEmail())->toBeTrue();
});

test('it dispatches the verified event', function () {
    $user = storedUserIn($this->users);

    $this->useCase->execute($user->id(), $this->validHash);

    $this->events->shouldHaveReceived('dispatch')
        ->once()
        ->with(Mockery::on(fn ($event) => $event instanceof Verified && $event->user === $user));
});

test('it rejects a hash that does not match the user email', function () {
    $user = storedUserIn($this->users);

    try {
        $this->useCase->execute($user->id(), sha1('another@example.com'));
        $this->fail('Expected InvalidVerificationLink to be thrown.');
    } catch (InvalidVerificationLink) {
    }

    expect($this->users->findById($user->id())->hasVerifiedEmail())->toBeFalse();
    $this->events->shouldNotHaveReceived('dispatch');
});

test('it does nothing when the email is already verified', function () {
    $user = storedUserIn($this->users, verified: true);
    $verifiedAt = $user->emailVerifiedAt();

    $this->useCase->execute($user->id(), $this->validHash);

    expect($this->users->findById($user->id())->emailVerifiedAt())->toBe($verifiedAt);
    $this->events->shouldNotHaveReceived('dispatch');
});

test('it fails for an unknown user', function () {
    $this->useCase->execute(new UserId(999), $this->validHash);
})->throws(UserNotFound::class);
