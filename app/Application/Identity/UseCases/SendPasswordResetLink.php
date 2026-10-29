<?php

namespace App\Application\Identity\UseCases;

use App\Application\Identity\Contracts\PasswordResetBroker;
use App\Application\Identity\Enums\PasswordResetStatus;
use App\Application\Identity\Exceptions\PasswordResetFailed;
use App\Domain\Identity\ValueObjects\Email;

final class SendPasswordResetLink
{
    public function __construct(
        private PasswordResetBroker $broker,
    ) {}

    /**
     * @throws PasswordResetFailed
     */
    public function execute(Email $email): PasswordResetStatus
    {
        $status = $this->broker->sendResetLink($email);

        if ($status !== PasswordResetStatus::LinkSent) {
            throw new PasswordResetFailed($status);
        }

        return $status;
    }
}
