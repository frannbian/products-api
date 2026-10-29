<?php

namespace App\Domain\Identity\Exceptions;

use App\Domain\Identity\ValueObjects\Email;
use DomainException;

final class EmailAlreadyRegistered extends DomainException
{
    public function __construct(Email $email)
    {
        parent::__construct("The email {$email->value()} is already registered.");
    }
}
