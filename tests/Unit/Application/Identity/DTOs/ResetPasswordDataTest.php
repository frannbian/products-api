<?php

use App\Application\Identity\DTOs\ResetPasswordData;
use App\Domain\Identity\ValueObjects\Email;
use App\Domain\Identity\ValueObjects\Password;

test('it builds value objects from raw input', function () {
    $data = ResetPasswordData::fromArray([
        'email' => 'John@Example.com',
        'token' => 'reset-token',
        'password' => 'new-password',
    ]);

    expect($data->email)->toBeInstanceOf(Email::class)
        ->and($data->email->value())->toBe('john@example.com')
        ->and($data->token)->toBe('reset-token')
        ->and($data->password)->toBeInstanceOf(Password::class)
        ->and($data->password->value())->toBe('new-password');
});
