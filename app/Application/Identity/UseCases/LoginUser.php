<?php

namespace App\Application\Identity\UseCases;

use App\Application\Identity\Contracts\TokenService;
use App\Application\Identity\DTOs\AuthenticationResult;
use App\Application\Identity\DTOs\LoginUserData;
use App\Domain\Identity\Exceptions\InvalidCredentials;
use App\Domain\Identity\Repositories\UserRepository;
use App\Domain\Identity\Services\PasswordHasher;

final class LoginUser
{
    public function __construct(
        private UserRepository $users,
        private PasswordHasher $hasher,
        private TokenService $tokens,
    ) {}

    /**
     * @throws InvalidCredentials
     */
    public function execute(LoginUserData $data): AuthenticationResult
    {
        $user = $this->users->findByEmail($data->email);

        if (! $user || ! $this->hasher->verify($data->password, $user->password())) {
            throw new InvalidCredentials;
        }

        return new AuthenticationResult($user, $this->tokens->issueFor($user));
    }
}
