<?php

use App\Application\Identity\Contracts\PasswordResetBroker;
use App\Application\Identity\Enums\PasswordResetStatus;
use App\Application\Identity\UseCases\SendPasswordResetLink;
use App\Domain\Identity\ValueObjects\Email;

beforeEach(function () {
    $this->broker = Mockery::mock(PasswordResetBroker::class);
    $this->useCase = new SendPasswordResetLink($this->broker);
});

test('it sends the reset link to the given email', function () {
    $this->broker->expects('sendResetLink')
        ->withArgs(fn (Email $email) => $email->value() === 'john@example.com')
        ->andReturn(PasswordResetStatus::LinkSent);

    $this->useCase->execute(new Email('john@example.com'));
});

test('it does not reveal whether the email is registered or throttled', function (PasswordResetStatus $status) {
    $this->broker->allows('sendResetLink')->andReturn($status);

    // No exception: the caller can not tell an unknown email from a known one.
    $this->useCase->execute(new Email('john@example.com'));

    expect(true)->toBeTrue();
})->with([PasswordResetStatus::InvalidUser, PasswordResetStatus::Throttled]);
