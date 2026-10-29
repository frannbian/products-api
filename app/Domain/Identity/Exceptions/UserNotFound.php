<?php

namespace App\Domain\Identity\Exceptions;

use App\Domain\Identity\ValueObjects\UserId;
use DomainException;

final class UserNotFound extends DomainException
{
    public function __construct(UserId $id)
    {
        parent::__construct("The user {$id->value()} does not exist.");
    }
}
