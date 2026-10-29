<?php

use App\Domain\Identity\Exceptions\InvalidEmail;
use App\Domain\Identity\ValueObjects\Email;

test('it is created from a valid email address', function () {
    $email = new Email('john@example.com');

    expect($email->value())->toBe('john@example.com');
});

test('it normalizes the address to lowercase without surrounding whitespace', function () {
    $email = new Email('  John.Doe@Example.COM ');

    expect($email->value())->toBe('john.doe@example.com');
});

test('it rejects invalid email addresses', function (string $invalid) {
    new Email($invalid);
})->throws(InvalidEmail::class, 'The email address is invalid.')
    ->with(['', 'not-an-email', 'john@', '@example.com', 'john doe@example.com']);

test('two emails with the same normalized value are equal', function () {
    expect((new Email('John@Example.com'))->equals(new Email('john@example.com')))->toBeTrue();
});

test('two emails with different values are not equal', function () {
    expect((new Email('john@example.com'))->equals(new Email('jane@example.com')))->toBeFalse();
});
