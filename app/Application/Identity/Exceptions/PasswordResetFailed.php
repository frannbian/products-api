<?php

namespace App\Application\Identity\Exceptions;

use App\Application\Identity\Enums\PasswordResetStatus;
use RuntimeException;

final class PasswordResetFailed extends RuntimeException
{
    public function __construct(
        public readonly PasswordResetStatus $status,
    ) {
        parent::__construct("The password reset failed: {$status->name}.");
    }
}
