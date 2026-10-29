<?php

namespace App\Application\Identity\Exceptions;

use RuntimeException;

final class InvalidVerificationLink extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('The email verification link is invalid.');
    }
}
