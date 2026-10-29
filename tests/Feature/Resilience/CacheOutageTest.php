<?php

use App\Providers\AppServiceProvider;
use Illuminate\Cache\RateLimiter;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Facades\Redis;

/**
 * Points the cache to a Redis port where nothing listens.
 */
function simulateCacheOutage(): void
{
    config([
        'cache.default' => 'redis',
        'database.redis.client' => 'predis',
        'database.redis.cache.host' => '127.0.0.1',
        'database.redis.cache.port' => 1,
    ]);

    // Instances resolved while booting still hold the testing array store.
    Redis::purge('cache');
    Cache::forgetDriver('redis');
    app()->forgetInstance('cache.store');
    app()->forgetInstance(RateLimiter::class);
    Facade::clearResolvedInstances();

    // A fresh RateLimiter needs the application's limiters registered again.
    (new AppServiceProvider(app()))->boot();
}

beforeEach(function () {
    config(['app.debug' => false]);
});

test('the health check fails when the cache is unreachable', function () {
    simulateCacheOutage();

    $this->get('/up')->assertServerError();
});

test('api requests respond 503 with Retry-After when the cache is down', function () {
    $token = apiToken();
    simulateCacheOutage();

    $this->withToken($token)->getJson('/api/currencies')
        ->assertServiceUnavailable()
        ->assertHeader('Retry-After', '30')
        ->assertExactJson(['message' => __('http.service_unavailable')]);
});
