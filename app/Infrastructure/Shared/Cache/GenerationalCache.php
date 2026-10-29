<?php

namespace App\Infrastructure\Shared\Cache;

use Closure;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Database\ConnectionResolverInterface;

/**
 * Cache organized in namespaces that are invalidated as a whole.
 *
 * Every key embeds the current generation of its namespace, a random token.
 * Invalidating replaces the token: all previous entries stop being read at
 * once, atomically, and expire on their own through their TTL. This avoids
 * having to know which keys exist (every page of every listing) or scanning
 * Redis. Random tokens never repeat, so if Redis evicts the current one a new
 * generation starts and old entries can never be read again.
 */
final class GenerationalCache
{
    public function __construct(
        private Cache $cache,
        private ConnectionResolverInterface $db,
    ) {}

    /**
     * @template T
     *
     * @param  Closure(): T  $callback
     * @return T
     */
    public function remember(string $namespace, string $key, int $ttlSeconds, Closure $callback): mixed
    {
        return $this->cache->remember(
            "{$namespace}:g{$this->generation($namespace)}:{$key}",
            $ttlSeconds,
            $callback,
        );
    }

    /**
     * Renews every key of the namespace once the current transaction commits
     * (right away when there is none). Invalidating before the commit would
     * let a concurrent reader cache the old data again; a rollback changes
     * nothing, so it invalidates nothing.
     */
    public function invalidate(string $namespace): void
    {
        $this->db->connection()->afterCommit(function () use ($namespace) {
            $this->cache->forever($this->generationKey($namespace), $this->newGeneration());
        });
    }

    private function generation(string $namespace): string
    {
        // add() only writes when the key is missing, atomically: concurrent
        // readers of a fresh namespace all end up with the same generation.
        $this->cache->add($this->generationKey($namespace), $this->newGeneration());

        return (string) $this->cache->get($this->generationKey($namespace));
    }

    private function newGeneration(): string
    {
        return bin2hex(random_bytes(8));
    }

    private function generationKey(string $namespace): string
    {
        return "{$namespace}:generation";
    }
}
