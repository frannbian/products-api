<?php

namespace App\Application\Identity\DTOs;

final readonly class AccessToken
{
    /**
     * @param  int  $expiresIn  Seconds until the token expires.
     */
    public function __construct(
        public string $token,
        public int $expiresIn,
        public string $type = 'Bearer',
    ) {}
}
