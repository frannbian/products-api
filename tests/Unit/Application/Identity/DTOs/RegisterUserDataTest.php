<?php

use App\Application\Identity\DTOs\RegisterUserData;
use App\Domain\Identity\Exceptions\InvalidPassword;
use App\Domain\Identity\ValueObjects\Email;
use App\Domain\Identity\ValueObjects\Password;
use App\Domain\Identity\ValueObjects\Username;

test('it builds value objects from raw input', function () {
    $data = RegisterUserData::fromArray([
        'name' => ' John Doe ',
        'email' => 'John@Example.com',
        'password' => 'secret-password',
    ]);

    expect($data->name)->toBeInstanceOf(Username::class)
        ->and($data->name->value())->toBe('John Doe')
        ->and($data->email)->toBeInstanceOf(Email::class)
        ->and($data->email->value())->toBe('john@example.com')
        ->and($data->password)->toBeInstanceOf(Password::class)
        ->and($data->password->value())->toBe('secret-password');
});

test('it rejects invalid input through its value objects', function () {
    RegisterUserData::fromArray([
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'password' => 'short',
    ]);
})->throws(InvalidPassword::class);
