<?php

use App\Application\Identity\DTOs\AccessToken;

test('it is a bearer token by default', function () {
    $token = new AccessToken('jwt-token', 3600);

    expect($token->token)->toBe('jwt-token')
        ->and($token->type)->toBe('Bearer')
        ->and($token->expiresIn)->toBe(3600);
});
