<?php

namespace App\Domain\Identity\Repositories;

use App\Models\User;

interface UserRepository
{
    public function findByEmail(string $email): ?User;

    public function create(array $attributes): User;
}