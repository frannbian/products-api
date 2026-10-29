<?php

use App\Domain\Identity\ValueObjects\HashedPassword;
use App\Domain\Identity\ValueObjects\Password;
use App\Infrastructure\Identity\Security\LaravelPasswordHasher;

test('it hashes the password', function () {
    $hash = app(LaravelPasswordHasher::class)->hash(new Password('secret-password'));

    expect($hash)->toBeInstanceOf(HashedPassword::class)
        ->and($hash->value())->not->toBe('secret-password')
        ->and(password_verify('secret-password', $hash->value()))->toBeTrue();
});

test('it verifies a plain password against its hash', function () {
    $hasher = app(LaravelPasswordHasher::class);
    $hash = $hasher->hash(new Password('secret-password'));

    expect($hasher->verify('secret-password', $hash))->toBeTrue()
        ->and($hasher->verify('wrong-password', $hash))->toBeFalse();
});
