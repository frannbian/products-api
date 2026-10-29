<?php

namespace App\Application\Identity\UseCases;

use App\Application\Identity\Contracts\TokenService;
use App\Application\Identity\DTOs\AccessToken;

final class RefreshToken
{
    public function __construct(
        private TokenService $tokens,
    ) {}

    public function execute(): AccessToken
    {
        return $this->tokens->refresh();
    }
}
