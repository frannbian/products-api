<?php

namespace App\Infrastructure\Identity\Persistence\Eloquent;

use App\Domain\Identity\Entities\User;
use App\Domain\Identity\ValueObjects\Email;
use App\Domain\Identity\ValueObjects\HashedPassword;
use App\Domain\Identity\ValueObjects\UserId;
use App\Domain\Identity\ValueObjects\Username;

final class UserMapper
{
    public static function toDomain(UserModel $model): User
    {
        return User::reconstitute(
            id: new UserId($model->id),
            name: new Username($model->name),
            email: new Email($model->email),
            password: new HashedPassword($model->password),
            emailVerifiedAt: $model->email_verified_at?->toDateTimeImmutable(),
            createdAt: $model->created_at?->toDateTimeImmutable(),
        );
    }

    public static function fill(UserModel $model, User $user): UserModel
    {
        return $model->forceFill([
            'name' => $user->name()->value(),
            'email' => $user->email()->value(),
            'password' => $user->password()->value(),
            'email_verified_at' => $user->emailVerifiedAt(),
        ]);
    }
}
