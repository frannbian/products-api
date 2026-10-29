<?php

use App\Domain\Identity\Entities\User;
use App\Domain\Identity\ValueObjects\Email;
use App\Domain\Identity\ValueObjects\HashedPassword;
use App\Domain\Identity\ValueObjects\UserId;
use App\Domain\Identity\ValueObjects\Username;
use App\Infrastructure\Identity\Persistence\Eloquent\UserModel;
use App\Infrastructure\Identity\Persistence\EloquentUserRepository;

beforeEach(function () {
    $this->repository = new EloquentUserRepository;
});

function newUser(): User
{
    return User::register(
        new Username('John Doe'),
        new Email('john@example.com'),
        new HashedPassword('already-hashed-password'),
    );
}

test('it persists a new user and assigns its identity', function () {
    $user = $this->repository->save(newUser());

    $model = UserModel::find($user->id()->value());

    expect($user->id())->not->toBeNull()
        ->and($user->createdAt())->not->toBeNull()
        ->and($model->name)->toBe('John Doe')
        ->and($model->email)->toBe('john@example.com')
        ->and($model->password)->toBe('already-hashed-password')
        ->and($model->email_verified_at)->toBeNull();
});

test('it finds a user by id as a domain entity', function () {
    $model = UserModel::factory()->create(['name' => 'Jane', 'email' => 'jane@example.com']);

    $user = $this->repository->findById(new UserId($model->id));

    expect($user)->toBeInstanceOf(User::class)
        ->and($user->id()->value())->toBe($model->id)
        ->and($user->name()->value())->toBe('Jane')
        ->and($user->email()->value())->toBe('jane@example.com')
        ->and($user->password()->value())->toBe($model->password)
        ->and($user->hasVerifiedEmail())->toBeTrue();
});

test('it finds a user by email as a domain entity', function () {
    $model = UserModel::factory()->create(['email' => 'john@example.com']);

    $user = $this->repository->findByEmail(new Email('john@example.com'));

    expect($user)->toBeInstanceOf(User::class)
        ->and($user->id()->value())->toBe($model->id);
});

test('it returns null when no user has the email', function () {
    expect($this->repository->findByEmail(new Email('ghost@example.com')))->toBeNull();
});

test('it returns null when the id does not exist', function () {
    expect($this->repository->findById(new UserId(999)))->toBeNull();
});

test('it knows whether an email is registered', function () {
    UserModel::factory()->create(['email' => 'john@example.com']);

    expect($this->repository->existsByEmail(new Email('john@example.com')))->toBeTrue()
        ->and($this->repository->existsByEmail(new Email('ghost@example.com')))->toBeFalse();
});

test('it updates an existing user', function () {
    $user = $this->repository->save(newUser());

    $user->verifyEmail(new DateTimeImmutable('2026-01-03 10:00:00'));
    $this->repository->save($user);

    expect(UserModel::count())->toBe(1)
        ->and(UserModel::find($user->id()->value())->email_verified_at->format('Y-m-d H:i:s'))
        ->toBe('2026-01-03 10:00:00');
});

test('it persists a changed password', function () {
    $model = UserModel::factory()->create();
    $user = $this->repository->findById(new UserId($model->id));

    $user->changePassword(new HashedPassword('new-hashed-password'));
    $this->repository->save($user);

    expect($model->fresh()->password)->toBe('new-hashed-password');
});
