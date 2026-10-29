<?php

namespace App\Presentation\Http\Messages;

use App\Application\Identity\Enums\PasswordResetStatus;

final class PasswordResetMessage
{
    public static function for(PasswordResetStatus $status): string
    {
        return __(match ($status) {
            PasswordResetStatus::LinkSent => 'passwords.sent',
            PasswordResetStatus::PasswordReset => 'passwords.reset',
            PasswordResetStatus::InvalidUser => 'passwords.user',
            PasswordResetStatus::InvalidToken => 'passwords.token',
            PasswordResetStatus::Throttled => 'passwords.throttled',
        });
    }
}
