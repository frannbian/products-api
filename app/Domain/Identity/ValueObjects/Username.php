<?php

namespace App\Domain\Identity\ValueObjects;

use InvalidArgumentException;

final readonly class Username
{
    private string $value;

    public function __construct(string $value)
    {
        $value = trim($value);

        if ($value === '') {
            throw new InvalidArgumentException(
                'The user name cannot be empty.'
            );
        }

        if (mb_strlen($value) > 100) {
            throw new InvalidArgumentException(
                'The user name cannot exceed 100 characters.'
            );
        }

        $this->value = $value;
    }

    public function value(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}