<?php

namespace App\Application\Identity\DTOs;

use App\Domain\Identity\ValueObjects\Email;
use App\Domain\Identity\ValueObjects\Password;

final readonly class ResetPasswordData
{
    public function __construct(
        public Email $email,
        public string $token,
        public Password $password,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            email: new Email($data['email']),
            token: $data['token'],
            password: new Password($data['password']),
        );
    }
}
