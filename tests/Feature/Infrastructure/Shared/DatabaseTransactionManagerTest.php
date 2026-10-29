<?php

use App\Application\Shared\Contracts\TransactionManager;
use App\Infrastructure\Currency\Persistence\Eloquent\CurrencyModel;
use App\Infrastructure\Shared\Persistence\DatabaseTransactionManager;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->transactions = app(TransactionManager::class);
});

test('the container resolves the database transaction manager', function () {
    expect($this->transactions)->toBeInstanceOf(DatabaseTransactionManager::class);
});

test('it runs the callback in a transaction and returns its result', function () {
    $level = DB::transactionLevel();

    $result = $this->transactions->run(fn () => DB::transactionLevel());

    expect($result)->toBe($level + 1);
});

test('it caps how long a statement waits for a lock', function () {
    $timeout = $this->transactions->run(fn () => DB::selectOne('show lock_timeout')->lock_timeout);

    expect($timeout)->toBe('5s');
});

test('it rolls back everything when the callback fails', function () {
    try {
        $this->transactions->run(function () {
            CurrencyModel::factory()->create(['name' => 'Rolled back']);

            throw new RuntimeException('boom');
        });
    } catch (RuntimeException) {
    }

    expect(CurrencyModel::where('name', 'Rolled back')->exists())->toBeFalse();
});
