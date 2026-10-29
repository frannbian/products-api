<?php

use App\Application\Identity\UseCases\GetUser;
use App\Domain\Identity\Exceptions\UserNotFound;
use App\Domain\Identity\ValueObjects\UserId;
use Tests\Support\InMemoryUserRepository;

beforeEach(function () {
    $this->users = new InMemoryUserRepository;
    $this->useCase = new GetUser($this->users);
});

test('it returns the user with the given id', function () {
    $user = storedUserIn($this->users);

    expect($this->useCase->execute($user->id()))->toBe($user);
});

test('it fails for an unknown user', function () {
    $this->useCase->execute(new UserId(999));
})->throws(UserNotFound::class);
