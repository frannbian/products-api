<?php

namespace App\Domain\Identity\ValueObjects;

use App\Domain\Identity\Exceptions\InvalidPassword;

final readonly class HashedPassword
{
    public function __construct(
        private string $value
    ) {
        if ($this->value === '') {
            throw new InvalidPassword(
                'The hashed password cannot be empty.'
            );
        }
    }

    public function value(): string
    {
        return $this->value;
    }
}
