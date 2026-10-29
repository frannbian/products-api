<?php

namespace App\Application\Identity\UseCases;

use App\Application\Identity\Contracts\EmailVerificationNotifier;
use App\Domain\Identity\Exceptions\UserNotFound;
use App\Domain\Identity\Repositories\UserRepository;
use App\Domain\Identity\ValueObjects\UserId;

final class SendEmailVerificationNotification
{
    public function __construct(
        private UserRepository $users,
        private EmailVerificationNotifier $notifier,
    ) {}

    /**
     * Returns false when the email was already verified and nothing was sent.
     *
     * @throws UserNotFound
     */
    public function execute(UserId $id): bool
    {
        $user = $this->users->findById($id) ?? throw new UserNotFound($id);

        if ($user->hasVerifiedEmail()) {
            return false;
        }

        $this->notifier->send($user);

        return true;
    }
}
