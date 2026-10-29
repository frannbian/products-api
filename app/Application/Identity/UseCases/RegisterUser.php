<?php

namespace App\Application\Identity\UseCases;

use App\Application\Identity\Contracts\TokenService;
use App\Application\Identity\DTOs\AuthenticationResult;
use App\Application\Identity\DTOs\RegisterUserData;
use App\Domain\Identity\Entities\User;
use App\Domain\Identity\Exceptions\EmailAlreadyRegistered;
use App\Domain\Identity\Repositories\UserRepository;
use App\Domain\Identity\Services\PasswordHasher;
use Illuminate\Auth\Events\Registered;
use Illuminate\Contracts\Events\Dispatcher;

final class RegisterUser
{
    public function __construct(
        private UserRepository $users,
        private PasswordHasher $hasher,
        private TokenService $tokens,
        private Dispatcher $events,
    ) {}

    /**
     * @throws EmailAlreadyRegistered
     */
    public function execute(RegisterUserData $data): AuthenticationResult
    {
        if ($this->users->existsByEmail($data->email)) {
            throw new EmailAlreadyRegistered($data->email);
        }

        $user = $this->users->save(User::register(
            $data->name,
            $data->email,
            $this->hasher->hash($data->password),
        ));

        $this->events->dispatch(new Registered($user));

        return new AuthenticationResult($user, $this->tokens->issueFor($user));
    }
}
