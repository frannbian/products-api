<?php

namespace App\Infrastructure\Identity\Security;

use App\Domain\Identity\Services\PasswordHasher;
use App\Domain\Identity\ValueObjects\HashedPassword;
use App\Domain\Identity\ValueObjects\Password;
use Illuminate\Contracts\Hashing\Hasher;

final class LaravelPasswordHasher implements PasswordHasher
{
    public function __construct(
        private Hasher $hasher,
    ) {}

    public function hash(Password $password): HashedPassword
    {
        return new HashedPassword($this->hasher->make($password->value()));
    }

    public function verify(string $password, HashedPassword $hash): bool
    {
        return $this->hasher->check($password, $hash->value());
    }
}
