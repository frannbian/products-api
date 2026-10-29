<?php

use App\Application\Identity\DTOs\LoginUserData;
use App\Application\Identity\UseCases\LoginUser;
use App\Domain\Identity\Entities\User;
use App\Domain\Identity\Exceptions\InvalidCredentials;
use App\Domain\Identity\ValueObjects\Email;
use App\Domain\Identity\ValueObjects\HashedPassword;
use App\Domain\Identity\ValueObjects\Username;
use Tests\Support\FakePasswordHasher;
use Tests\Support\FakeTokenService;
use Tests\Support\InMemoryUserRepository;

beforeEach(function () {
    $this->users = new InMemoryUserRepository;
    $this->useCase = new LoginUser($this->users, new FakePasswordHasher, new FakeTokenService);

    $this->user = $this->users->save(User::register(
        new Username('John Doe'),
        new Email('john@example.com'),
        new HashedPassword('hashed:secret-password'),
    ));
});

test('it returns the user and an access token for valid credentials', function () {
    $result = $this->useCase->execute(LoginUserData::fromArray([
        'email' => 'John@Example.com',
        'password' => 'secret-password',
    ]));

    expect($result->user)->toBe($this->user)
        ->and($result->token->token)->toBe('token-for-'.$this->user->id()->value());
});

test('it throws when the password is wrong', function () {
    $this->useCase->execute(LoginUserData::fromArray([
        'email' => 'john@example.com',
        'password' => 'wrong-password',
    ]));
})->throws(InvalidCredentials::class);

test('it throws when the email is not registered', function () {
    $this->useCase->execute(LoginUserData::fromArray([
        'email' => 'ghost@example.com',
        'password' => 'secret-password',
    ]));
})->throws(InvalidCredentials::class);
