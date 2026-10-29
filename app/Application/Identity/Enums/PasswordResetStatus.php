<?php

namespace App\Application\Identity\Enums;

enum PasswordResetStatus
{
    case LinkSent;
    case PasswordReset;
    case InvalidUser;
    case InvalidToken;
    case Throttled;
}
