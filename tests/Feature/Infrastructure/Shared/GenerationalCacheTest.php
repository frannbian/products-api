<?php

use App\Infrastructure\Shared\Cache\GenerationalCache;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->cache = app(GenerationalCache::class);
    $this->calls = 0;
    $this->compute = function () {
        return ++$this->calls;
    };
});

test('it computes a value once and serves it from the cache afterwards', function () {
    expect($this->cache->remember('products', 'page:1', 60, $this->compute))->toBe(1)
        ->and($this->cache->remember('products', 'page:1', 60, $this->compute))->toBe(1)
        ->and($this->calls)->toBe(1);
});

test('different keys are cached separately', function () {
    $this->cache->remember('products', 'page:1', 60, $this->compute);
    $this->cache->remember('products', 'page:2', 60, $this->compute);

    expect($this->calls)->toBe(2);
});

test('values expire after their ttl', function () {
    $this->cache->remember('products', 'page:1', 60, $this->compute);

    $this->travel(61)->seconds();

    expect($this->cache->remember('products', 'page:1', 60, $this->compute))->toBe(2);
});

test('invalidating a namespace renews every key in it', function () {
    $this->cache->remember('products', 'page:1', 60, $this->compute);
    $this->cache->remember('products', 'page:2', 60, $this->compute);

    $this->cache->invalidate('products');

    expect($this->cache->remember('products', 'page:1', 60, $this->compute))->toBe(3)
        ->and($this->cache->remember('products', 'page:2', 60, $this->compute))->toBe(4);
});

test('invalidating a namespace keeps the others', function () {
    $this->cache->remember('currencies', 'page:1', 60, $this->compute);

    $this->cache->invalidate('products');

    expect($this->cache->remember('currencies', 'page:1', 60, $this->compute))->toBe(1);
});

test('inside a transaction the invalidation waits for the commit', function () {
    $this->cache->remember('products', 'page:1', 60, $this->compute);

    DB::transaction(function () {
        $this->cache->invalidate('products');

        // Not committed yet: other readers must keep seeing the cached value.
        expect($this->cache->remember('products', 'page:1', 60, $this->compute))->toBe(1);
    });

    expect($this->cache->remember('products', 'page:1', 60, $this->compute))->toBe(2);
});

test('a rolled back transaction does not invalidate anything', function () {
    $this->cache->remember('products', 'page:1', 60, $this->compute);

    try {
        DB::transaction(function () {
            $this->cache->invalidate('products');

            throw new RuntimeException('rollback');
        });
    } catch (RuntimeException) {
    }

    expect($this->cache->remember('products', 'page:1', 60, $this->compute))->toBe(1);
});

test('an evicted generation counter never brings old entries back', function () {
    $this->cache->remember('products', 'page:1', 60, $this->compute);
    $this->cache->invalidate('products');
    $this->cache->remember('products', 'page:1', 60, $this->compute);

    // Redis evicts keys when it runs out of memory: the counter may vanish.
    Cache::forget('products:generation');

    expect($this->cache->remember('products', 'page:1', 60, $this->compute))->toBe(3);
});
