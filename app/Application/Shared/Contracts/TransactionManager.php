<?php

namespace App\Application\Shared\Contracts;

use Closure;

/**
 * Unit of work boundary for use cases that read, modify and write data.
 * Locks taken inside the callback are held until it returns.
 */
interface TransactionManager
{
    /**
     * @template T
     *
     * @param  Closure(): T  $callback
     * @return T
     */
    public function run(Closure $callback): mixed;
}
