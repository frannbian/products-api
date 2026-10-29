<?php

use App\Application\Identity\UseCases\RefreshToken;
use Tests\Support\FakeTokenService;

test('it exchanges the current token for a new one', function () {
    $token = (new RefreshToken(new FakeTokenService))->execute();

    expect($token->token)->toBe('refreshed-token');
});
