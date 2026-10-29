<?php

namespace App\Application\Identity\DTOs;

use App\Domain\Identity\ValueObjects\Email;

final readonly class LoginUserData
{
    /**
     * The password is kept as a plain string: the password policy applies
     * when a password is chosen, not when someone tries to log in.
     */
    public function __construct(
        public Email $email,
        public string $password,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            email: new Email($data['email']),
            password: $data['password'],
        );
    }
}
