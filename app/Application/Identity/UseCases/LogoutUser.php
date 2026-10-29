<?php

namespace App\Application\Identity\UseCases;

use App\Application\Identity\Contracts\TokenService;

final class LogoutUser
{
    public function __construct(
        private TokenService $tokens,
    ) {}

    public function execute(): void
    {
        $this->tokens->revoke();
    }
}
