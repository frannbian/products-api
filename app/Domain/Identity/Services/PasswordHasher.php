<?php

namespace App\Domain\Identity\Services;

use App\Domain\Identity\ValueObjects\HashedPassword;
use App\Domain\Identity\ValueObjects\Password;

interface PasswordHasher
{
    public function hash(Password $password): HashedPassword;

    public function verify(string $password, HashedPassword $hash): bool;
}
