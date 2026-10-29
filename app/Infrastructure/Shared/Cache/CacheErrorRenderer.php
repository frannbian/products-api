<?php

namespace App\Infrastructure\Shared\Cache;

use Illuminate\Foundation\Configuration\Exceptions;
use Predis\CommunicationException;
use Predis\Connection\Resource\Exception\StreamInitException;
use RedisException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;
use Throwable;

/**
 * Redis backs the cache, rate limiting and idempotency keys, so it is a hard
 * dependency like the database: when it can not be reached the API answers
 * 503 with Retry-After instead of a 500 with connection details.
 *
 * Only connectivity errors are mapped; a Redis error caused by a bad command
 * is a bug and stays a 500.
 */
final class CacheErrorRenderer
{
    private const RETRY_AFTER_SECONDS = 30;

    public static function register(Exceptions $exceptions): void
    {
        $unavailable = fn (Throwable $e) => new ServiceUnavailableHttpException(
            self::RETRY_AFTER_SECONDS,
            __('http.service_unavailable'),
            $e,
        );

        // predis: connection refused / could not be opened, or lost mid-command.
        $exceptions->map(StreamInitException::class, $unavailable);
        $exceptions->map(CommunicationException::class, $unavailable);

        // phpredis, when REDIS_CLIENT=phpredis.
        if (class_exists(RedisException::class)) {
            $exceptions->map(RedisException::class, $unavailable);
        }
    }
}
