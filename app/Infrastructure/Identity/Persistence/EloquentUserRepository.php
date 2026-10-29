<?php

namespace App\Infrastructure\Identity\Persistence;

use App\Domain\Identity\Entities\User;
use App\Domain\Identity\Exceptions\EmailAlreadyRegistered;
use App\Domain\Identity\Repositories\UserRepository;
use App\Domain\Identity\ValueObjects\Email;
use App\Domain\Identity\ValueObjects\UserId;
use App\Infrastructure\Identity\Persistence\Eloquent\UserMapper;
use App\Infrastructure\Identity\Persistence\Eloquent\UserModel;
use Illuminate\Database\UniqueConstraintViolationException;

final class EloquentUserRepository implements UserRepository
{
    public function existsByEmail(Email $email): bool
    {
        return UserModel::query()
            ->where('email', $email->value())
            ->exists();
    }

    public function findByEmail(Email $email): ?User
    {
        $model = UserModel::query()
            ->where('email', $email->value())
            ->first();

        return $model ? UserMapper::toDomain($model) : null;
    }

    public function findById(UserId $id): ?User
    {
        $model = UserModel::find($id->value());

        return $model ? UserMapper::toDomain($model) : null;
    }

    public function save(User $user): User
    {
        $model = $user->id() === null
            ? new UserModel
            : UserModel::findOrFail($user->id()->value());

        try {
            UserMapper::fill($model, $user)->save();
        } catch (UniqueConstraintViolationException) {
            // Another registration with the same email won the race.
            throw new EmailAlreadyRegistered($user->email());
        }

        return $user->id() === null ? UserMapper::toDomain($model) : $user;
    }
}
