<?php

namespace App\Application\Identity\UseCases;

use App\Domain\Identity\Entities\User;
use App\Domain\Identity\Exceptions\UserNotFound;
use App\Domain\Identity\Repositories\UserRepository;
use App\Domain\Identity\ValueObjects\UserId;

final class GetUser
{
    public function __construct(
        private UserRepository $users,
    ) {}

    /**
     * @throws UserNotFound
     */
    public function execute(UserId $id): User
    {
        return $this->users->findById($id) ?? throw new UserNotFound($id);
    }
}
