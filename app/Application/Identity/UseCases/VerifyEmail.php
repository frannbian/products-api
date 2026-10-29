<?php

namespace App\Application\Identity\UseCases;

use App\Application\Identity\Exceptions\InvalidVerificationLink;
use App\Domain\Identity\Exceptions\UserNotFound;
use App\Domain\Identity\Repositories\UserRepository;
use App\Domain\Identity\ValueObjects\UserId;
use DateTimeImmutable;
use Illuminate\Auth\Events\Verified;
use Illuminate\Contracts\Events\Dispatcher;

final class VerifyEmail
{
    public function __construct(
        private UserRepository $users,
        private Dispatcher $events,
    ) {}

    /**
     * The hash comes from the signed link sent by email and must match the
     * address the user currently has.
     *
     * @throws UserNotFound
     * @throws InvalidVerificationLink
     */
    public function execute(UserId $id, string $hash): void
    {
        $user = $this->users->findById($id) ?? throw new UserNotFound($id);

        if (! hash_equals(sha1($user->email()->value()), $hash)) {
            throw new InvalidVerificationLink;
        }

        if (! $user->verifyEmail(new DateTimeImmutable)) {
            return;
        }

        $this->users->save($user);

        $this->events->dispatch(new Verified($user));
    }
}
