<?php

namespace Tests\Support;

use App\Application\Identity\Contracts\TokenService;
use App\Application\Identity\DTOs\AccessToken;
use App\Domain\Identity\Entities\User;

final class FakeTokenService implements TokenService
{
    public bool $revoked = false;

    public function issueFor(User $user): AccessToken
    {
        return new AccessToken('token-for-'.$user->id()->value(), 3600);
    }

    public function refresh(): AccessToken
    {
        return new AccessToken('refreshed-token', 3600);
    }

    public function revoke(): void
    {
        $this->revoked = true;
    }
}
