<?php

use App\Domain\Identity\Exceptions\InvalidPassword;
use App\Domain\Identity\ValueObjects\Password;

test('it is created from a password with at least 8 characters', function () {
    $password = new Password('12345678');

    expect($password->value())->toBe('12345678');
});

test('it rejects passwords shorter than 8 characters', function (string $short) {
    new Password($short);
})->throws(InvalidPassword::class, 'The password must contain at least 8 characters.')
    ->with(['', '1234567']);

test('it keeps the password exactly as given', function () {
    $password = new Password('  Secret Pass ');

    expect($password->value())->toBe('  Secret Pass ');
});
