<?php

use App\Domain\Identity\Exceptions\InvalidUsername;
use App\Domain\Identity\ValueObjects\Username;

test('it is created from a non empty name', function () {
    $name = new Username('John Doe');

    expect($name->value())->toBe('John Doe');
});

test('it trims surrounding whitespace', function () {
    $name = new Username('  John Doe  ');

    expect($name->value())->toBe('John Doe');
});

test('it rejects empty names', function (string $empty) {
    new Username($empty);
})->throws(InvalidUsername::class, 'The user name cannot be empty.')
    ->with(['', '   ']);

test('it accepts names of exactly 100 characters', function () {
    $name = new Username(str_repeat('a', 100));

    expect(mb_strlen($name->value()))->toBe(100);
});

test('it rejects names longer than 100 characters', function () {
    new Username(str_repeat('a', 101));
})->throws(InvalidUsername::class, 'The user name cannot exceed 100 characters.');

test('it counts multibyte characters as a single character', function () {
    $name = new Username(str_repeat('ñ', 100));

    expect($name->value())->toBe(str_repeat('ñ', 100));
});

test('two names with the same value are equal', function () {
    expect((new Username('John'))->equals(new Username(' John ')))->toBeTrue();
});
