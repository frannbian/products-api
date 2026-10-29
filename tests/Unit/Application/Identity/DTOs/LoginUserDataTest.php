<?php

use App\Application\Identity\DTOs\LoginUserData;
use App\Domain\Identity\ValueObjects\Email;

test('it builds the email value object from raw input', function () {
    $data = LoginUserData::fromArray([
        'email' => 'John@Example.com',
        'password' => 'secret-password',
    ]);

    expect($data->email)->toBeInstanceOf(Email::class)
        ->and($data->email->value())->toBe('john@example.com')
        ->and($data->password)->toBe('secret-password');
});

test('it does not apply the password policy to login attempts', function () {
    $data = LoginUserData::fromArray([
        'email' => 'john@example.com',
        'password' => 'short',
    ]);

    expect($data->password)->toBe('short');
});
