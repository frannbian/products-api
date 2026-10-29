<?php

namespace Tests\Support;

use App\Domain\Identity\Entities\User;
use App\Domain\Identity\Repositories\UserRepository;
use App\Domain\Identity\ValueObjects\Email;
use App\Domain\Identity\ValueObjects\UserId;
use DateTimeImmutable;

final class InMemoryUserRepository implements UserRepository
{
    /** @var array<int, User> */
    private array $users = [];

    private int $nextId = 1;

    public function existsByEmail(Email $email): bool
    {
        return $this->findByEmail($email) !== null;
    }

    public function findByEmail(Email $email): ?User
    {
        foreach ($this->users as $user) {
            if ($user->email()->equals($email)) {
                return $user;
            }
        }

        return null;
    }

    public function findById(UserId $id): ?User
    {
        return $this->users[$id->value()] ?? null;
    }

    public function save(User $user): User
    {
        if ($user->id() === null) {
            $user = User::reconstitute(
                new UserId($this->nextId++),
                $user->name(),
                $user->email(),
                $user->password(),
                $user->emailVerifiedAt(),
                new DateTimeImmutable,
            );
        }

        return $this->users[$user->id()->value()] = $user;
    }

    /** @return list<User> */
    public function all(): array
    {
        return array_values($this->users);
    }
}
