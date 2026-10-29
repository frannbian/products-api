<?php

namespace App\Domain\Identity\Services;

use App\Domain\Identity\Entities\User;

interface TokenService
{
    public function create(User $user): string;

    public function revokeCurrent(User $user): void;
}