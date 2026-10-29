<?php

namespace App\Application\Identity\Contracts;

use App\Application\Identity\Enums\PasswordResetStatus;
use App\Domain\Identity\Entities\User;
use App\Domain\Identity\ValueObjects\Email;
use Closure;

interface PasswordResetBroker
{
    public function sendResetLink(Email $email): PasswordResetStatus;

    /**
     * @param  Closure(User): void  $callback  Invoked once the token is validated.
     */
    public function reset(Email $email, string $token, Closure $callback): PasswordResetStatus;
}
