<?php

use App\Domain\Identity\ValueObjects\UserId;
use App\Infrastructure\Identity\Authentication\JwtTokenService;
use App\Infrastructure\Identity\Persistence\Eloquent\UserModel;
use App\Infrastructure\Identity\Persistence\EloquentUserRepository;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

beforeEach(function () {
    $this->tokens = app(JwtTokenService::class);
    $this->model = UserModel::factory()->create();
    $this->user = (new EloquentUserRepository)->findById(new UserId($this->model->id));
});

test('it issues a bearer token whose subject is the user', function () {
    $token = $this->tokens->issueFor($this->user);

    expect($token->type)->toBe('Bearer')
        ->and(JWTAuth::setToken($token->token)->getPayload()->get('sub'))->toBe((string) $this->model->id);
});

test('the token expires according to the configured ttl', function () {
    config(['jwt.ttl' => 15]);

    expect($this->tokens->issueFor($this->user)->expiresIn)->toBe(15 * 60);
});

test('it refreshes the current token and invalidates the old one', function () {
    $old = $this->tokens->issueFor($this->user)->token;
    JWTAuth::setToken($old);

    $new = $this->tokens->refresh()->token;

    expect($new)->not->toBe($old)
        ->and(JWTAuth::setToken($new)->check())->toBeTrue()
        ->and(JWTAuth::setToken($old)->check())->toBeFalse();
});

test('it revokes the current token', function () {
    $token = $this->tokens->issueFor($this->user)->token;
    JWTAuth::setToken($token);

    $this->tokens->revoke();

    expect(JWTAuth::setToken($token)->check())->toBeFalse();
});
