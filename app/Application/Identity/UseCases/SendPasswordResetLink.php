<?php

namespace App\Application\Identity\UseCases;

use App\Application\Identity\Contracts\PasswordResetBroker;
use App\Domain\Identity\ValueObjects\Email;

final class SendPasswordResetLink
{
    public function __construct(
        private PasswordResetBroker $broker,
    ) {}

    /**
     * Sends the link when the email belongs to a user. The outcome is not
     * returned on purpose: answering differently for unknown or throttled
     * emails would let anyone find out which emails are registered.
     */
    public function execute(Email $email): void
    {
        $this->broker->sendResetLink($email);
    }
}
