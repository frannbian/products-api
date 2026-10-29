<?php

use App\Application\Identity\Contracts\EmailVerificationNotifier;
use App\Application\Identity\UseCases\SendEmailVerificationNotification;
use App\Domain\Identity\Exceptions\UserNotFound;
use App\Domain\Identity\ValueObjects\UserId;
use Tests\Support\InMemoryUserRepository;

beforeEach(function () {
    $this->users = new InMemoryUserRepository;
    $this->notifier = Mockery::spy(EmailVerificationNotifier::class);
    $this->useCase = new SendEmailVerificationNotification($this->users, $this->notifier);
});

test('it sends the verification notification to unverified users', function () {
    $user = storedUserIn($this->users);

    expect($this->useCase->execute($user->id()))->toBeTrue();
    $this->notifier->shouldHaveReceived('send')->once()->with($user);
});

test('it does not send the notification when the email is already verified', function () {
    $user = storedUserIn($this->users, verified: true);

    expect($this->useCase->execute($user->id()))->toBeFalse();
    $this->notifier->shouldNotHaveReceived('send');
});

test('it fails for an unknown user', function () {
    $this->useCase->execute(new UserId(999));
})->throws(UserNotFound::class);
