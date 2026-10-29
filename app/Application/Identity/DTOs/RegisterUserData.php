<?php

namespace App\Application\Identity\DTOs;

use App\Domain\Identity\ValueObjects\Email;
use App\Domain\Identity\ValueObjects\Password;
use App\Domain\Identity\ValueObjects\Username;

final readonly class RegisterUserData
{
    public function __construct(
        public Username $name,
        public Email $email,
        public Password $password,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            name: new Username($data['name']),
            email: new Email($data['email']),
            password: new Password($data['password']),
        );
    }
}
