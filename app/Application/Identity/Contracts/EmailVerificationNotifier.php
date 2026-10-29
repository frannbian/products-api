<?php

namespace App\Application\Identity\Contracts;

use App\Domain\Identity\Entities\User;

interface EmailVerificationNotifier
{
    public function send(User $user): void;
}
