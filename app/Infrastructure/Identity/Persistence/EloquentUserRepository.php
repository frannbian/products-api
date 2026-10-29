<?php

namespace App\Infrastructure\Identity\Persistence;

use App\Domain\Identity\Entities\User;
use App\Domain\Identity\Repositories\UserRepository;

final class EloquentUserRepository implements UserRepository
{
    public function findByEmail(string $email): ?User
    {
        return User::query()
            ->where('email', $email)
            ->first();
    }

    public function create(array $attributes): User
    {
        return User::create($attributes);
    }
}