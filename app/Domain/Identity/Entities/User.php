<?php

namespace App\Domain\Identity\Entities;

use App\Domain\Identity\ValueObjects\Email;
use App\Domain\Identity\ValueObjects\HashedPassword;
use App\Domain\Identity\ValueObjects\UserId;
use App\Domain\Identity\ValueObjects\Username;
use DateTimeImmutable;

final class User
{
    private function __construct(
        private ?UserId $id,
        private Username $name,
        private Email $email,
        private HashedPassword $password,
        private ?DateTimeImmutable $emailVerifiedAt,
        private ?DateTimeImmutable $createdAt,
    ) {}

    public static function register(Username $name, Email $email, HashedPassword $password): self
    {
        return new self(null, $name, $email, $password, null, null);
    }

    public static function reconstitute(
        UserId $id,
        Username $name,
        Email $email,
        HashedPassword $password,
        ?DateTimeImmutable $emailVerifiedAt,
        ?DateTimeImmutable $createdAt,
    ): self {
        return new self($id, $name, $email, $password, $emailVerifiedAt, $createdAt);
    }

    public function id(): ?UserId
    {
        return $this->id;
    }

    public function name(): Username
    {
        return $this->name;
    }

    public function email(): Email
    {
        return $this->email;
    }

    public function password(): HashedPassword
    {
        return $this->password;
    }

    public function emailVerifiedAt(): ?DateTimeImmutable
    {
        return $this->emailVerifiedAt;
    }

    public function createdAt(): ?DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function hasVerifiedEmail(): bool
    {
        return $this->emailVerifiedAt !== null;
    }

    /**
     * Returns false when the email was already verified.
     */
    public function verifyEmail(DateTimeImmutable $at): bool
    {
        if ($this->hasVerifiedEmail()) {
            return false;
        }

        $this->emailVerifiedAt = $at;

        return true;
    }

    public function changePassword(HashedPassword $password): void
    {
        $this->password = $password;
    }
}
