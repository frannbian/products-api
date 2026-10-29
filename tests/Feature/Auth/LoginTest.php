<?php

use App\Infrastructure\Identity\Persistence\Eloquent\UserModel;

test('users can login and receive an access token', function () {
    $user = UserModel::factory()->create();

    $this->postJson('/api/auth/login', [
        'email' => $user->email,
        'password' => 'password',
    ])->assertOk()
        ->assertJson([
            'message' => __('auth.logged_in'),
            'user' => ['id' => $user->id, 'email' => $user->email],
            'token_type' => 'Bearer',
            'expires_in' => config('jwt.ttl') * 60,
        ])
        ->assertJsonStructure(['access_token']);
});

test('the issued token authenticates the user', function () {
    $user = UserModel::factory()->create();

    $token = $this->postJson('/api/auth/login', [
        'email' => $user->email,
        'password' => 'password',
    ])->json('access_token');

    resetAuth();

    $this->withToken($token)->getJson('/api/auth/me')
        ->assertOk()
        ->assertJsonPath('data.id', $user->id);
});

test('users can login with an email in a different case', function () {
    UserModel::factory()->create(['email' => 'john@example.com']);

    $this->postJson('/api/auth/login', [
        'email' => 'John@Example.com',
        'password' => 'password',
    ])->assertOk();
});

test('invalid credentials return a validation error on the email field', function () {
    $user = UserModel::factory()->create();

    $this->postJson('/api/auth/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ])->assertUnprocessable()
        ->assertJsonValidationErrors(['email' => __('auth.failed')])
        ->assertJsonMissingPath('access_token');
});

test('an unknown email returns the same error as a wrong password', function () {
    $this->postJson('/api/auth/login', [
        'email' => 'ghost@example.com',
        'password' => 'password',
    ])->assertUnprocessable()->assertJsonValidationErrors(['email' => __('auth.failed')]);
});

test('login is rate limited after too many failed attempts', function () {
    $user = UserModel::factory()->create();

    foreach (range(1, 5) as $attempt) {
        $this->postJson('/api/auth/login', ['email' => $user->email, 'password' => 'wrong-password']);
    }

    $this->postJson('/api/auth/login', ['email' => $user->email, 'password' => 'password'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('email')
        ->assertJsonMissingPath('access_token');
});
