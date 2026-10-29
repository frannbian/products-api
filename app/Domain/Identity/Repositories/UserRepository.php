<?php

namespace App\Domain\Identity\Repositories;

use App\Domain\Identity\Entities\User;
use App\Domain\Identity\ValueObjects\Email;
use App\Domain\Identity\ValueObjects\UserId;

interface UserRepository
{
    public function existsByEmail(Email $email): bool;

    public function findByEmail(Email $email): ?User;

    public function findById(UserId $id): ?User;

    /**
     * Persists the user and returns it with its identity assigned.
     */
    public function save(User $user): User;
}
