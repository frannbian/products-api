<?php

namespace App\Infrastructure\Identity\Authentication;

use App\Application\Identity\Contracts\TokenService;
use App\Application\Identity\DTOs\AccessToken;
use App\Domain\Identity\Entities\User;
use App\Infrastructure\Identity\Persistence\Eloquent\UserModel;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use PHPOpenSourceSaver\JWTAuth\JWTGuard;

final class JwtTokenService implements TokenService
{
    public function __construct(
        private AuthFactory $auth,
    ) {}

    public function issueFor(User $user): AccessToken
    {
        return $this->toAccessToken(
            $this->guard()->login(UserModel::findOrFail($user->id()->value()))
        );
    }

    public function refresh(): AccessToken
    {
        return $this->toAccessToken($this->guard()->refresh());
    }

    public function revoke(): void
    {
        $this->guard()->logout();
    }

    private function toAccessToken(string $token): AccessToken
    {
        return new AccessToken(
            token: $token,
            expiresIn: $this->guard()->factory()->getTTL() * 60,
        );
    }

    private function guard(): JWTGuard
    {
        return $this->auth->guard('api');
    }
}
