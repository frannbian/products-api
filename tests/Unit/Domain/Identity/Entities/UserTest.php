<?php

use App\Domain\Identity\Entities\User;
use App\Domain\Identity\ValueObjects\Email;
use App\Domain\Identity\ValueObjects\HashedPassword;
use App\Domain\Identity\ValueObjects\UserId;
use App\Domain\Identity\ValueObjects\Username;

function registeredUser(): User
{
    return User::register(
        new Username('John Doe'),
        new Email('john@example.com'),
        new HashedPassword('hashed-password'),
    );
}

function persistedUser(?DateTimeImmutable $emailVerifiedAt = null): User
{
    return User::reconstitute(
        id: new UserId(1),
        name: new Username('John Doe'),
        email: new Email('john@example.com'),
        password: new HashedPassword('hashed-password'),
        emailVerifiedAt: $emailVerifiedAt,
        createdAt: new DateTimeImmutable('2026-01-01 10:00:00'),
    );
}

test('a registered user has no identity until it is persisted', function () {
    expect(registeredUser()->id())->toBeNull();
});

test('a registered user keeps its data', function () {
    $user = registeredUser();

    expect($user->name()->value())->toBe('John Doe')
        ->and($user->email()->value())->toBe('john@example.com')
        ->and($user->password()->value())->toBe('hashed-password')
        ->and($user->createdAt())->toBeNull();
});

test('a registered user starts with the email unverified', function () {
    expect(registeredUser()->hasVerifiedEmail())->toBeFalse()
        ->and(registeredUser()->emailVerifiedAt())->toBeNull();
});

test('a user can be reconstituted from persisted data', function () {
    $verifiedAt = new DateTimeImmutable('2026-01-02 10:00:00');
    $user = persistedUser($verifiedAt);

    expect($user->id()->value())->toBe(1)
        ->and($user->emailVerifiedAt())->toBe($verifiedAt)
        ->and($user->createdAt()->format('Y-m-d H:i:s'))->toBe('2026-01-01 10:00:00')
        ->and($user->hasVerifiedEmail())->toBeTrue();
});

test('verifying the email records when it happened', function () {
    $user = persistedUser();
    $now = new DateTimeImmutable('2026-01-03 10:00:00');

    expect($user->verifyEmail($now))->toBeTrue()
        ->and($user->hasVerifiedEmail())->toBeTrue()
        ->and($user->emailVerifiedAt())->toBe($now);
});

test('verifying an already verified email does nothing', function () {
    $verifiedAt = new DateTimeImmutable('2026-01-02 10:00:00');
    $user = persistedUser($verifiedAt);

    expect($user->verifyEmail(new DateTimeImmutable('2026-01-03 10:00:00')))->toBeFalse()
        ->and($user->emailVerifiedAt())->toBe($verifiedAt);
});

test('the password can be changed', function () {
    $user = persistedUser();

    $user->changePassword(new HashedPassword('new-hashed-password'));

    expect($user->password()->value())->toBe('new-hashed-password');
});
