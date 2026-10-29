<?php

use App\Infrastructure\Identity\Persistence\Eloquent\UserModel;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    Notification::fake();
});

test('reset password link can be requested', function () {
    $user = UserModel::factory()->create();

    $this->postJson('/api/auth/forgot-password', ['email' => $user->email])
        ->assertOk()
        ->assertJson(['message' => __('passwords.sent')]);

    Notification::assertSentTo($user, ResetPassword::class);
});

test('the reset link points to the frontend', function () {
    $user = UserModel::factory()->create(['email' => 'john@example.com']);

    $this->postJson('/api/auth/forgot-password', ['email' => $user->email]);

    Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use ($user) {
        expect($notification->toMail($user)->actionUrl)
            ->toBe("http://frontend.test/password-reset/{$notification->token}?email=john@example.com");

        return true;
    });
});

test('reset link request fails for an unknown email', function () {
    $this->postJson('/api/auth/forgot-password', ['email' => 'ghost@example.com'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['email' => __('passwords.user')]);
});

test('password can be reset with a valid token', function () {
    $user = UserModel::factory()->create();

    $this->postJson('/api/auth/forgot-password', ['email' => $user->email]);

    Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use ($user) {
        $this->postJson('/api/auth/reset-password', [
            'token' => $notification->token,
            'email' => $user->email,
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])->assertOk()->assertJson(['message' => __('passwords.reset')]);

        expect(Hash::check('new-password', $user->fresh()->password))->toBeTrue();

        return true;
    });
});

test('the user can login with the new password after resetting it', function () {
    $user = UserModel::factory()->create();

    $this->postJson('/api/auth/forgot-password', ['email' => $user->email]);

    Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use ($user) {
        $this->postJson('/api/auth/reset-password', [
            'token' => $notification->token,
            'email' => $user->email,
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ]);

        $this->postJson('/api/auth/login', ['email' => $user->email, 'password' => 'new-password'])
            ->assertOk();

        return true;
    });
});

test('password can not be reset with an invalid token', function () {
    $user = UserModel::factory()->create();

    $this->postJson('/api/auth/reset-password', [
        'token' => 'invalid-token',
        'email' => $user->email,
        'password' => 'new-password',
        'password_confirmation' => 'new-password',
    ])->assertUnprocessable()->assertJsonValidationErrors(['email' => __('passwords.token')]);

    expect(Hash::check('password', $user->fresh()->password))->toBeTrue();
});
