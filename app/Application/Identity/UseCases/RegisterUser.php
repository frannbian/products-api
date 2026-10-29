<?php

namespace App\Application\Identity\UseCases;

use App\Domain\Identity\Repositories\UserRepository;
use App\Domain\Identity\Services\PasswordHasher;
use App\Domain\Identity\Services\TokenService;
use App\Models\User;

final class RegisterUser
{
    public function __construct(
        private UserRepository $users,
        private PasswordHasher $hasher,
        private TokenService $tokens,
    ) {}

    /**
     * @return array{user: User, access_token: string}
     */
    public function execute(array $data): array
    {
        $user = $this->users->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $this->hasher->hash($data['password']),
        ]);

        return [
            'user' => $user,
            'access_token' => $this->tokens->create($user),
        ];
    }
}