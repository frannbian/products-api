<?php

namespace App\Domain\Identity\Exceptions;

use DomainException;

final class InvalidCredentials extends DomainException
{
    public function __construct()
    {
        parent::__construct('The provided credentials are incorrect.');
    }
}
