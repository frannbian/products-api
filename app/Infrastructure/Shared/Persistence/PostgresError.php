<?php

namespace App\Infrastructure\Shared\Persistence;

use Illuminate\Database\QueryException;
use PDOException;
use Throwable;

/**
 * Classifies database errors by their SQLSTATE code.
 *
 * @see https://www.postgresql.org/docs/current/errcodes-appendix.html
 */
final class PostgresError
{
    public static function isForeignKeyViolation(QueryException $e): bool
    {
        return self::sqlState($e) === '23503';
    }

    /**
     * The statement waited longer than lock_timeout for a row lock.
     */
    public static function isLockTimeout(Throwable $e): bool
    {
        return self::sqlState($e) === '55P03';
    }

    /**
     * Class 08 (connection exception) and 57P0x (server shutting down): the
     * database is unreachable, not the request at fault.
     */
    public static function isConnectionFailure(Throwable $e): bool
    {
        $state = self::sqlState($e);

        return $state !== null && (str_starts_with($state, '08') || str_starts_with($state, '57P0'));
    }

    private static function sqlState(Throwable $e): ?string
    {
        $pdo = $e instanceof PDOException ? $e : $e->getPrevious();

        if ($pdo instanceof PDOException && isset($pdo->errorInfo[0])) {
            return (string) $pdo->errorInfo[0];
        }

        if (is_string($e->getCode()) && strlen($e->getCode()) === 5) {
            return $e->getCode();
        }

        // Connection errors carry the SQLSTATE only in the message.
        return preg_match('/SQLSTATE\[([0-9A-Z]{5})\]/', $e->getMessage(), $matches) ? $matches[1] : null;
    }
}
