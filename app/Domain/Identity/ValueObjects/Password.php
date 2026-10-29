<?php

namespace App\Domain\Identity\ValueObjects;

use App\Domain\Identity\Exceptions\InvalidPassword;

final readonly class Password
{
    public function __construct(
        private string $value
    ) {
        if (strlen($this->value) < 8) {
            throw new InvalidPassword(
                'The password must contain at least 8 characters.'
            );
        }
    }

    public function value(): string
    {
        return $this->value;
    }
}
