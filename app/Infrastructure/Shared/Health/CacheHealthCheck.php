<?php

namespace App\Infrastructure\Shared\Health;

use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Foundation\Events\DiagnosingHealth;

/**
 * Makes Laravel's /up health check fail when the cache store (Redis) is
 * unreachable: rate limiting and idempotency depend on it.
 */
final class CacheHealthCheck
{
    public function __construct(
        private Cache $cache,
    ) {}

    public function handle(DiagnosingHealth $event): void
    {
        $this->cache->put('health-check', true, 10);
    }
}
