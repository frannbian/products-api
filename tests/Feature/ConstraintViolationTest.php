<?php

use Illuminate\Database\QueryException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Route;

/**
 * Last line of defense: a constraint violation that no repository translated
 * must never surface as a 500 with SQL details.
 */
beforeEach(function () {
    config(['app.debug' => false]);
});

function routeThrowing(Throwable $exception): string
{
    Route::middleware('api')->get('/api/_test/throws', fn () => throw $exception);

    return '/api/_test/throws';
}

function queryException(string $sqlState, string $class = QueryException::class): QueryException
{
    $pdo = new PDOException("SQLSTATE[{$sqlState}]: constraint violation");
    $pdo->errorInfo = [$sqlState, 7, 'constraint violation'];

    return new $class('pgsql', 'insert into secrets values (?)', ['x'], $pdo);
}

test('an untranslated unique violation responds 409', function () {
    $this->getJson(routeThrowing(queryException('23505', UniqueConstraintViolationException::class)))
        ->assertConflict()
        ->assertJson(['message' => __('http.constraint_conflict')])
        ->assertJsonMissingPath('exception');
});

test('an untranslated foreign key violation responds 409', function () {
    $this->getJson(routeThrowing(queryException('23503')))
        ->assertConflict()
        ->assertJson(['message' => __('http.constraint_conflict')]);
});
