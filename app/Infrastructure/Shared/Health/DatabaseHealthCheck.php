<?php

namespace App\Infrastructure\Shared\Health;

use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Foundation\Events\DiagnosingHealth;

/**
 * Makes Laravel's /up health check fail when the database is unreachable,
 * so load balancers stop routing traffic to an instance that can not work.
 */
final class DatabaseHealthCheck
{
    public function __construct(
        private ConnectionResolverInterface $db,
    ) {}

    public function handle(DiagnosingHealth $event): void
    {
        $this->db->connection()->select('select 1');
    }
}
