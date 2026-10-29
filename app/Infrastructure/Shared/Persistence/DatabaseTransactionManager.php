<?php

namespace App\Infrastructure\Shared\Persistence;

use App\Application\Shared\Contracts\TransactionManager;
use Closure;
use Illuminate\Database\ConnectionResolverInterface;

/**
 * Database transactions for pessimistic locking.
 *
 * Every transaction caps how long a statement may wait for a row lock, so a
 * request stuck behind a slow one fails fast (and can be retried) instead of
 * holding a worker. Deadlocks, which PostgreSQL resolves by aborting one of
 * the transactions, are retried from the start.
 */
final class DatabaseTransactionManager implements TransactionManager
{
    private const LOCK_TIMEOUT = '5s';

    private const ATTEMPTS_ON_DEADLOCK = 3;

    public function __construct(
        private ConnectionResolverInterface $db,
    ) {}

    public function run(Closure $callback): mixed
    {
        $connection = $this->db->connection();

        return $connection->transaction(function () use ($connection, $callback) {
            // SET LOCAL lasts until the end of this transaction only.
            $connection->statement("SET LOCAL lock_timeout = '".self::LOCK_TIMEOUT."'");

            return $callback();
        }, self::ATTEMPTS_ON_DEADLOCK);
    }
}
