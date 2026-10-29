<?php

use App\Infrastructure\Identity\Persistence\Eloquent\UserModel;
use Illuminate\Support\Facades\Hash;

function registrationPayload(array $overrides = []): array
{
    return [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        ...$overrides,
    ];
}

test('new users can register and receive an access token', function () {
    $response = $this->postJson('/api/auth/register', registrationPayload());

    $user = UserModel::firstWhere('email', 'test@example.com');

    $response->assertCreated()
        ->assertJson([
            'message' => __('auth.registered'),
            'user' => ['id' => $user->id, 'name' => 'Test User', 'email' => 'test@example.com'],
            'token_type' => 'Bearer',
            'expires_in' => config('jwt.ttl') * 60,
        ])
        ->assertJsonStructure(['access_token']);
});

test('the issued token authenticates the new user', function () {
    $token = $this->postJson('/api/auth/register', registrationPayload())->json('access_token');

    resetAuth();

    $this->withToken($token)->getJson('/api/auth/me')
        ->assertOk()
        ->assertJsonPath('data.email', 'test@example.com');
});

test('the password is stored hashed', function () {
    $this->postJson('/api/auth/register', registrationPayload());

    $user = UserModel::firstWhere('email', 'test@example.com');

    expect($user->password)->not->toBe('password')
        ->and(Hash::check('password', $user->password))->toBeTrue();
});

test('the email is stored normalized', function () {
    $this->postJson('/api/auth/register', registrationPayload(['email' => 'Test@Example.com']))
        ->assertCreated();

    expect(UserModel::where('email', 'test@example.com')->exists())->toBeTrue();
});

test('users can not register with an email that is already taken', function (string $email) {
    UserModel::factory()->create(['email' => 'test@example.com']);

    $this->postJson('/api/auth/register', registrationPayload(['email' => $email]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['email' => __('validation.unique', ['attribute' => 'email'])]);
})->with(['test@example.com', 'Test@Example.com']);

test('registration requires a password of at least 8 characters', function () {
    $this->postJson('/api/auth/register', registrationPayload([
        'password' => 'short',
        'password_confirmation' => 'short',
    ]))->assertUnprocessable()->assertJsonValidationErrors('password');
});

test('registration requires the password to be confirmed', function () {
    $this->postJson('/api/auth/register', registrationPayload(['password_confirmation' => 'different']))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('password');
});

test('registration rejects names longer than 100 characters', function () {
    $this->postJson('/api/auth/register', registrationPayload(['name' => str_repeat('a', 101)]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('name');
});
