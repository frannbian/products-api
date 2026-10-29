<?php

namespace App\Domain\Identity\ValueObjects;

use App\Domain\Identity\Exceptions\InvalidEmail;

final readonly class Email
{
    private string $value;

    public function __construct(string $value)
    {
        $value = strtolower(trim($value));

        if (! filter_var($value, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidEmail(
                'The email address is invalid.'
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
