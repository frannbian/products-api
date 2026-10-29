<?php

use App\Infrastructure\Identity\Persistence\Eloquent\UserModel;
use Illuminate\Auth\Events\Verified;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;

function verificationUrlFor(UserModel $user, ?string $hash = null): string
{
    return URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        ['id' => $user->id, 'hash' => $hash ?? sha1($user->email)],
    );
}

test('email can be verified from the signed link without a token', function () {
    $user = UserModel::factory()->unverified()->create();

    Event::fake();

    $response = $this->get(verificationUrlFor($user));

    Event::assertDispatched(Verified::class);
    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
    $response->assertRedirect('http://frontend.test/dashboard?verified=1');
});

test('email is not verified with an invalid hash', function () {
    $user = UserModel::factory()->unverified()->create();

    $this->getJson(verificationUrlFor($user, sha1('wrong-email')))
        ->assertForbidden()
        ->assertJson(['message' => __('auth.verification_link_invalid')]);

    expect($user->fresh()->hasVerifiedEmail())->toBeFalse();
});

test('email is not verified with an unsigned link', function () {
    $user = UserModel::factory()->unverified()->create();

    $this->getJson("/api/auth/email/verify/{$user->id}/".sha1($user->email))->assertForbidden();

    expect($user->fresh()->hasVerifiedEmail())->toBeFalse();
});

test('verification notification can be resent', function () {
    Notification::fake();

    $user = UserModel::factory()->unverified()->create();

    $this->withToken(jwtFor($user))
        ->postJson('/api/auth/email/verification-notification')
        ->assertOk()
        ->assertJson(['message' => __('auth.verification_link_sent')]);

    Notification::assertSentTo($user, VerifyEmail::class);
});

test('the verification link sent by email points to the api', function () {
    Notification::fake();

    $user = UserModel::factory()->unverified()->create();

    $this->withToken(jwtFor($user))->postJson('/api/auth/email/verification-notification');

    Notification::assertSentTo($user, VerifyEmail::class, function (VerifyEmail $notification) use ($user) {
        expect($notification->toMail($user)->actionUrl)
            ->toStartWith(url("/api/auth/email/verify/{$user->id}/".sha1($user->email)));

        return true;
    });
});

test('verification notification is not sent to verified users', function () {
    Notification::fake();

    $user = UserModel::factory()->create();

    $this->withToken(jwtFor($user))
        ->postJson('/api/auth/email/verification-notification')
        ->assertOk()
        ->assertJson(['message' => __('auth.email_already_verified')]);

    Notification::assertNothingSent();
});
