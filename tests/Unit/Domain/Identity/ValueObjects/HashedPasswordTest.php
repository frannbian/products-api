<?php

use App\Domain\Identity\Exceptions\InvalidPassword;
use App\Domain\Identity\ValueObjects\HashedPassword;

test('it wraps a hashed password', function () {
    expect((new HashedPassword('$2y$04$hash'))->value())->toBe('$2y$04$hash');
});

test('it rejects an empty hash', function () {
    new HashedPassword('');
})->throws(InvalidPassword::class, 'The hashed password cannot be empty.');
