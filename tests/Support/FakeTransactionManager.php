<?php

namespace Tests\Support;

use App\Application\Shared\Contracts\TransactionManager;
use Closure;

/**
 * Runs the callback directly and tells whether code is running inside it,
 * so tests can check that locks are taken within a transaction.
 */
final class FakeTransactionManager implements TransactionManager
{
    private int $depth = 0;

    public int $transactions = 0;

    public function run(Closure $callback): mixed
    {
        $this->depth++;
        $this->transactions++;

        try {
            return $callback();
        } finally {
            $this->depth--;
        }
    }

    public function active(): bool
    {
        return $this->depth > 0;
    }
}
