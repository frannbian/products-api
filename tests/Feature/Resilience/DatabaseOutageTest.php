<?php

use App\Infrastructure\Currency\Persistence\Eloquent\CurrencyModel;

/**
 * Points the default connection to a port where nothing listens. The test
 * transaction stays on the real connection, so it can still be rolled back.
 */
function simulateDatabaseOutage(): void
{
    config([
        'database.connections.unreachable' => [...config('database.connections.pgsql'), 'host' => '127.0.0.1', 'port' => 1],
        'database.default' => 'unreachable',
    ]);
}

beforeEach(function () {
    config(['app.debug' => false]);
});

// RefreshDatabase rolls back on the default connection when the test ends.
afterEach(function () {
    config(['database.default' => 'pgsql']);
});

test('the health check passes when the database is reachable', function () {
    $this->get('/up')->assertOk();
});

test('the health check fails when the database is unreachable', function () {
    simulateDatabaseOutage();

    $this->get('/up')->assertServerError();
});

test('api requests respond 503 with Retry-After when the database is down', function () {
    $token = apiToken();
    simulateDatabaseOutage();

    $this->withToken($token)->getJson('/api/currencies')
        ->assertServiceUnavailable()
        ->assertHeader('Retry-After', '30')
        ->assertExactJson(['message' => __('http.service_unavailable')]);
});

test('the outage response does not leak connection details', function () {
    $token = apiToken();
    simulateDatabaseOutage();

    $content = $this->withToken($token)->getJson('/api/currencies')->getContent();

    expect($content)->not->toContain('SQLSTATE')
        ->not->toContain('127.0.0.1')
        ->not->toContain(config('database.connections.pgsql.username'));
});

test('regular database errors are not reported as an outage', function () {
    CurrencyModel::factory()->create();

    $this->withToken(apiToken())->getJson('/api/currencies')->assertOk();
});
