<?php

namespace App\Application\Identity\UseCases;

use App\Application\Identity\Contracts\PasswordResetBroker;
use App\Application\Identity\DTOs\ResetPasswordData;
use App\Application\Identity\Enums\PasswordResetStatus;
use App\Application\Identity\Exceptions\PasswordResetFailed;
use App\Domain\Identity\Entities\User;
use App\Domain\Identity\Repositories\UserRepository;
use App\Domain\Identity\Services\PasswordHasher;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Contracts\Events\Dispatcher;

final class ResetPassword
{
    public function __construct(
        private PasswordResetBroker $broker,
        private UserRepository $users,
        private PasswordHasher $hasher,
        private Dispatcher $events,
    ) {}

    /**
     * @throws PasswordResetFailed
     */
    public function execute(ResetPasswordData $data): PasswordResetStatus
    {
        $status = $this->broker->reset(
            $data->email,
            $data->token,
            function (User $user) use ($data) {
                $user->changePassword($this->hasher->hash($data->password));

                $this->users->save($user);

                $this->events->dispatch(new PasswordReset($user));
            },
        );

        if ($status !== PasswordResetStatus::PasswordReset) {
            throw new PasswordResetFailed($status);
        }

        return $status;
    }
}
