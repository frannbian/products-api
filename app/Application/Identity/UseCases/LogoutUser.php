<?php

namespace App\Application\Identity\UseCases;

use App\Domain\Identity\Services\TokenService;
use App\Models\User;

final class LogoutUser
{
    public function __construct(
        private TokenService $tokens,
    ) {}

    public function execute(User $user): void
    {
        $this->tokens->revokeCurrent($user);
    }
}