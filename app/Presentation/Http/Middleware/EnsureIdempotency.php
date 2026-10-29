<?php

namespace App\Presentation\Http\Middleware;

use Closure;
use Illuminate\Contracts\Cache\LockProvider;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

/**
 * Makes POST requests safe to retry. When the client sends an
 * Idempotency-Key header, the first successful response is stored for 24
 * hours and replayed for every retry with the same key and payload, so a
 * request whose response got lost never creates a duplicate.
 */
class EnsureIdempotency
{
    public const HEADER = 'Idempotency-Key';

    private const TTL_SECONDS = 86400;

    /** Longest a request may hold the key before another one can take over. */
    private const LOCK_SECONDS = 30;

    /** Response headers that are part of the stored response. */
    private const REPLAYED_HEADERS = ['Content-Type', 'ETag', 'Location'];

    public function __construct(
        private Cache $cache,
    ) {}

    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->isMethod('POST') || ! $request->headers->has(self::HEADER)) {
            return $next($request);
        }

        $key = (string) $request->header(self::HEADER);

        if ($key === '' || strlen($key) > 255) {
            throw new BadRequestHttpException(__('http.idempotency_key_invalid'));
        }

        $cacheKey = self::cacheKey((string) ($request->user()?->getAuthIdentifier() ?? $request->ip()), $key);

        // A key identifies one request: same endpoint and same payload.
        $fingerprint = hash('sha256', implode('|', [$request->method(), $request->path(), $request->getContent()]));

        if ($stored = $this->cache->get($cacheKey)) {
            return $this->replay($stored, $fingerprint);
        }

        $store = $this->cache->getStore();
        $lock = $store instanceof LockProvider ? $store->lock($cacheKey.':lock', self::LOCK_SECONDS) : null;

        if ($lock && ! $lock->get()) {
            throw new ConflictHttpException(__('http.idempotency_key_in_progress'));
        }

        try {
            // A concurrent request may have finished while we waited for the lock.
            if ($stored = $this->cache->get($cacheKey)) {
                return $this->replay($stored, $fingerprint);
            }

            $response = $next($request);

            // Only successful responses are stored: errors can be retried.
            if ($response->isSuccessful()) {
                $this->cache->put($cacheKey, [
                    'fingerprint' => $fingerprint,
                    'status' => $response->getStatusCode(),
                    'headers' => array_filter(array_map(
                        fn (string $header) => $response->headers->get($header),
                        array_combine(self::REPLAYED_HEADERS, self::REPLAYED_HEADERS),
                    )),
                    'content' => $response->getContent(),
                ], self::TTL_SECONDS);
            }

            return $response;
        } finally {
            $lock?->release();
        }
    }

    /**
     * Keys are scoped to whoever sends them.
     */
    public static function cacheKey(string $owner, string $key): string
    {
        return 'idempotency:'.hash('sha256', $owner.'|'.$key);
    }

    /**
     * @param  array{fingerprint: string, status: int, headers: array<string, string>, content: string}  $stored
     */
    private function replay(array $stored, string $fingerprint): Response
    {
        if (! hash_equals($stored['fingerprint'], $fingerprint)) {
            throw new UnprocessableEntityHttpException(__('http.idempotency_key_reused'));
        }

        return new Response($stored['content'], $stored['status'], [
            ...$stored['headers'],
            'Idempotent-Replayed' => 'true',
        ]);
    }
}
