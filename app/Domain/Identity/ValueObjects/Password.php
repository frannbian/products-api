<?php

namespace App\Domain\Identity\ValueObjects;

use InvalidArgumentException;

final readonly class Password
{
    public function __construct(
        private string $value
    ) {
        if (strlen($this->value) < 8) {
            throw new InvalidArgumentException(
                'The password must contain at least 8 characters.'
            );
        }
    }

    public function value(): string
    {
        return $this->value;
    }
}
