<?php

namespace Tests\Support;

use App\Domain\Identity\Services\PasswordHasher;
use App\Domain\Identity\ValueObjects\HashedPassword;
use App\Domain\Identity\ValueObjects\Password;

final class FakePasswordHasher implements PasswordHasher
{
    public function hash(Password $password): HashedPassword
    {
        return new HashedPassword('hashed:'.$password->value());
    }

    public function verify(string $password, HashedPassword $hash): bool
    {
        return $hash->value() === 'hashed:'.$password;
    }
}
