<?php

use App\Domain\Identity\ValueObjects\UserId;
use App\Infrastructure\Identity\Notifications\LaravelEmailVerificationNotifier;
use App\Infrastructure\Identity\Persistence\Eloquent\UserModel;
use App\Infrastructure\Identity\Persistence\EloquentUserRepository;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Notification;

test('it sends the verification email to the user', function () {
    Notification::fake();
    $model = UserModel::factory()->unverified()->create();
    $user = (new EloquentUserRepository)->findById(new UserId($model->id));

    (new LaravelEmailVerificationNotifier)->send($user);

    Notification::assertSentTo($model, VerifyEmail::class);
});
