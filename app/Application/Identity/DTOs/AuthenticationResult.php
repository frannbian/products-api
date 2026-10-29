<?php

namespace App\Application\Identity\DTOs;

use App\Domain\Identity\Entities\User;

final readonly class AuthenticationResult
{
    public function __construct(
        public User $user,
        public AccessToken $token,
    ) {}
}
