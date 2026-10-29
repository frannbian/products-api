<?php

namespace App\Domain\Identity\ValueObjects;

use App\Domain\Identity\Exceptions\InvalidUserId;

final readonly class UserId
{
    public function __construct(
        private int $value
    ) {
        if ($this->value < 1) {
            throw new InvalidUserId(
                'The user id must be a positive integer.'
            );
        }
    }

    public function value(): int
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
