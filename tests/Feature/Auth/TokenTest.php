<?php

use App\Infrastructure\Identity\Persistence\Eloquent\UserModel;

test('the authenticated user can be retrieved', function () {
    $user = UserModel::factory()->create();

    $this->withToken(jwtFor($user))
        ->getJson('/api/auth/me')
        ->assertOk()
        ->assertExactJson([
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'email_verified_at' => $user->email_verified_at->toJSON(),
                'created_at' => $user->created_at->toJSON(),
            ],
        ]);
});

test('guests can not access protected endpoints', function (string $method, string $uri) {
    $this->json($method, $uri)
        ->assertUnauthorized()
        ->assertJson(['message' => 'Unauthenticated.']);
})->with([
    ['GET', '/api/auth/me'],
    ['POST', '/api/auth/logout'],
    ['POST', '/api/auth/refresh'],
    ['POST', '/api/auth/email/verification-notification'],
]);

test('guests get a 401 even without asking for json', function () {
    $this->get('/api/auth/me')->assertUnauthorized();
});

test('an invalid token is rejected', function () {
    $this->withToken('not-a-jwt')->getJson('/api/auth/me')->assertUnauthorized();
});

test('users can logout', function () {
    $token = jwtFor(UserModel::factory()->create());

    $this->withToken($token)->postJson('/api/auth/logout')
        ->assertOk()
        ->assertJson(['message' => __('auth.logged_out')]);
});

test('the token can not be used after logout', function () {
    $token = jwtFor(UserModel::factory()->create());

    $this->withToken($token)->postJson('/api/auth/logout');
    resetAuth();

    $this->withToken($token)->getJson('/api/auth/me')->assertUnauthorized();
});

test('the token can be refreshed', function () {
    $user = UserModel::factory()->create();
    $old = jwtFor($user);

    $response = $this->withToken($old)->postJson('/api/auth/refresh')
        ->assertOk()
        ->assertJson([
            'message' => __('auth.token_refreshed'),
            'token_type' => 'Bearer',
            'expires_in' => config('jwt.ttl') * 60,
        ]);

    $new = $response->json('access_token');
    resetAuth();

    expect($new)->not->toBe($old);
    $this->withToken($new)->getJson('/api/auth/me')->assertOk()->assertJsonPath('data.id', $user->id);

    resetAuth();
    $this->withToken($old)->getJson('/api/auth/me')->assertUnauthorized();
});
