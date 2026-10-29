<?php

namespace App\Infrastructure\Shared\Persistence;

use Illuminate\Database\QueryException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Configuration\Exceptions;
use PDOException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;
use Throwable;

/**
 * Last line of defense for database errors that no repository translated
 * into a domain exception: they never surface as a 500 with SQL in it.
 */
final class DatabaseErrorRenderer
{
    /** Seconds a client should wait before retrying while the database is down. */
    private const RETRY_AFTER_SECONDS = 30;

    public static function register(Exceptions $exceptions): void
    {
        // A single mapping: QueryException extends PDOException and Laravel
        // only applies the first mapping that matches an exception.
        $exceptions->map(PDOException::class, fn (PDOException $e) => match (true) {
            // The database is unreachable: a temporary condition the client can retry.
            PostgresError::isConnectionFailure($e) => self::unavailable($e),
            // Another request held the row lock for too long: safe to retry.
            PostgresError::isLockTimeout($e) => new ConflictHttpException(__('http.lock_timeout'), $e),
            $e instanceof UniqueConstraintViolationException,
            $e instanceof QueryException && PostgresError::isForeignKeyViolation($e) => new ConflictHttpException(__('http.constraint_conflict'), $e),
            default => $e,
        });
    }

    private static function unavailable(Throwable $e): ServiceUnavailableHttpException
    {
        return new ServiceUnavailableHttpException(self::RETRY_AFTER_SECONDS, __('http.service_unavailable'), $e);
    }
}
