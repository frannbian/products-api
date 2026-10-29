<?php

use App\Application\Identity\UseCases\LogoutUser;
use Tests\Support\FakeTokenService;

test('it revokes the current access token', function () {
    $tokens = new FakeTokenService;

    (new LogoutUser($tokens))->execute();

    expect($tokens->revoked)->toBeTrue();
});
