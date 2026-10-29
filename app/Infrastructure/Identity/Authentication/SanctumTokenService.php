<?php

namespace App\Infrastructure\Identity\Authentication;

use App\Domain\Identity\Entities\User;
use App\Domain\Identity\Services\TokenService;
use RuntimeException;

final class SanctumTokenService implements TokenService
{
    public function create(User $user): string
    {
        return $user
            ->createToken('api-token')
            ->plainTextToken;
    }

    public function revokeCurrent(User $user): void
    {
        $token = $user->currentAccessToken();

        if (! $token || ! method_exists($token, 'delete')) {
            throw new RuntimeException(
                'No hay un token personal de Sanctum para revocar.'
            );
        }

        $token->delete();
    }
}