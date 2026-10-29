<?php

use App\Domain\Identity\Exceptions\InvalidUserId;
use App\Domain\Identity\ValueObjects\UserId;

test('it wraps a positive integer', function () {
    expect((new UserId(42))->value())->toBe(42);
});

test('it rejects non positive identifiers', function (int $invalid) {
    new UserId($invalid);
})->throws(InvalidUserId::class, 'The user id must be a positive integer.')
    ->with([0, -1]);

test('two ids with the same value are equal', function () {
    expect((new UserId(1))->equals(new UserId(1)))->toBeTrue()
        ->and((new UserId(1))->equals(new UserId(2)))->toBeFalse();
});
