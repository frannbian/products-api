<?php

namespace App\Infrastructure\Identity\Security;

use App\Domain\Identity\Services\PasswordHasher;
use Illuminate\Support\Facades\Hash;

final class LaravelPasswordHasher implements PasswordHasher
{
    public function hash(string $password): string
    {
        return Hash::make($password);
    }

    public function verify(
        string $password,
        string $hashedPassword
    ): bool {
        return Hash::check($password, $hashedPassword);
    }
}