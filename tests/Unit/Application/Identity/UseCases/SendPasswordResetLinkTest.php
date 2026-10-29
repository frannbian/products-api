<?php

use App\Application\Identity\Contracts\PasswordResetBroker;
use App\Application\Identity\Enums\PasswordResetStatus;
use App\Application\Identity\Exceptions\PasswordResetFailed;
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

    expect($this->useCase->execute(new Email('john@example.com')))
        ->toBe(PasswordResetStatus::LinkSent);
});

test('it throws when the link could not be sent', function (PasswordResetStatus $status) {
    $this->broker->allows('sendResetLink')->andReturn($status);

    try {
        $this->useCase->execute(new Email('john@example.com'));
        $this->fail('Expected PasswordResetFailed to be thrown.');
    } catch (PasswordResetFailed $e) {
        expect($e->status)->toBe($status);
    }
})->with([PasswordResetStatus::InvalidUser, PasswordResetStatus::Throttled]);
