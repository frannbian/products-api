<?php

namespace App\Infrastructure\Identity\Authentication;

use App\Application\Identity\Contracts\PasswordResetBroker;
use App\Application\Identity\Enums\PasswordResetStatus;
use App\Domain\Identity\ValueObjects\Email;
use App\Infrastructure\Identity\Persistence\Eloquent\UserMapper;
use App\Infrastructure\Identity\Persistence\Eloquent\UserModel;
use Closure;
use Illuminate\Contracts\Auth\PasswordBroker;
use Illuminate\Contracts\Auth\PasswordBrokerFactory;
use UnexpectedValueException;

final class LaravelPasswordResetBroker implements PasswordResetBroker
{
    public function __construct(
        private PasswordBrokerFactory $passwords,
    ) {}

    public function sendResetLink(Email $email): PasswordResetStatus
    {
        return $this->toStatus(
            $this->passwords->broker()->sendResetLink(['email' => $email->value()])
        );
    }

    public function reset(Email $email, string $token, Closure $callback): PasswordResetStatus
    {
        // The broker requires a password in the credentials even though the
        // callback is the one storing it, so an empty placeholder is enough.
        return $this->toStatus(
            $this->passwords->broker()->reset([
                'email' => $email->value(),
                'token' => $token,
                'password' => '',
            ], fn (UserModel $model) => $callback(UserMapper::toDomain($model)))
        );
    }

    private function toStatus(string $status): PasswordResetStatus
    {
        return match ($status) {
            PasswordBroker::RESET_LINK_SENT => PasswordResetStatus::LinkSent,
            PasswordBroker::PASSWORD_RESET => PasswordResetStatus::PasswordReset,
            PasswordBroker::INVALID_USER => PasswordResetStatus::InvalidUser,
            PasswordBroker::INVALID_TOKEN => PasswordResetStatus::InvalidToken,
            PasswordBroker::RESET_THROTTLED => PasswordResetStatus::Throttled,
            default => throw new UnexpectedValueException("Unknown password broker status [{$status}]."),
        };
    }
}
