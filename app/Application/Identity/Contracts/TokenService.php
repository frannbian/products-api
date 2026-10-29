<?php

namespace App\Application\Identity\Contracts;

use App\Application\Identity\DTOs\AccessToken;
use App\Domain\Identity\Entities\User;

interface TokenService
{
    public function issueFor(User $user): AccessToken;

    /**
     * Exchanges the token of the current request for a new one.
     */
    public function refresh(): AccessToken;

    /**
     * Invalidates the token of the current request.
     */
    public function revoke(): void;
}
