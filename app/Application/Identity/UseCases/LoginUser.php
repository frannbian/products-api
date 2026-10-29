<?php

namespace App\Application\Identity\UseCases;

use App\Domain\Identity\Repositories\UserRepository;
use App\Domain\Identity\Services\PasswordHasher;
use App\Domain\Identity\Services\TokenService;
use App\Models\User;
use Illuminate\Auth\AuthenticationException;

final class LoginUser
{
    public function __construct(
        private UserRepository $users,
        private PasswordHasher $hasher,
        private TokenService $tokens,
    ) {}

    /**
     * @return array{user: User, access_token: string}
     */
    public function execute(string $email, string $password): array
    {
        $user = $this->users->findByEmail($email);

        if (
            ! $user ||
            ! $this->hasher->verify($password, $user->password)
        ) {
            throw new AuthenticationException(
                'Las credenciales son incorrectas.'
            );
        }

        return [
            'user' => $user,
            'access_token' => $this->tokens->create($user),
        ];
    }
}