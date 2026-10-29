<?php

namespace App\Infrastructure\Identity\Notifications;

use App\Application\Identity\Contracts\EmailVerificationNotifier;
use App\Domain\Identity\Entities\User;
use App\Infrastructure\Identity\Persistence\Eloquent\UserModel;

final class LaravelEmailVerificationNotifier implements EmailVerificationNotifier
{
    public function send(User $user): void
    {
        UserModel::findOrFail($user->id()->value())->sendEmailVerificationNotification();
    }
}
