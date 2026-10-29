<?php

use Illuminate\Support\Facades\Artisan;

/**
 * The OpenAPI document is generated from the code: these tests make sure it
 * keeps describing the behavior that inference alone can not see.
 */
function openApi(): array
{
    static $document;

    if ($document === null) {
        $path = tempnam(sys_get_temp_dir(), 'openapi');
        Artisan::call('scramble:export', ['--path' => $path]);
        $document = json_decode(file_get_contents($path), true);
        unlink($path);
    }

    return $document;
}

function operation(string $method, string $path): array
{
    return openApi()['paths'][$path][$method];
}

function headerParameters(array $operation): array
{
    return collect($operation['parameters'] ?? [])->where('in', 'header')->pluck('name')->all();
}

test('creating resources is documented as 201', function (string $path) {
    expect(operation('post', $path)['responses'])->toHaveKey('201')->not->toHaveKey('200');
})->with(['/products', '/currencies']);

test('registering a price documents both created and replaced', function () {
    expect(operation('post', '/products/{product}/prices')['responses'])->toHaveKeys(['200', '201']);
});

test('POST endpoints document the Idempotency-Key header', function (string $path) {
    expect(headerParameters(operation('post', $path)))->toContain('Idempotency-Key');
})->with(['/products', '/currencies', '/products/{product}/prices']);

test('updates document the If-Match header', function (string $path) {
    expect(headerParameters(operation('put', $path)))->toContain('If-Match');
})->with(['/products/{product}', '/currencies/{currency}']);

test('single resources document the ETag response header and If-None-Match', function (string $path) {
    $operation = operation('get', $path);

    expect($operation['responses']['200']['headers'] ?? [])->toHaveKey('ETag')
        ->and(headerParameters($operation))->toContain('If-None-Match')
        ->and($operation['responses'])->toHaveKey('304');
})->with(['/products/{product}', '/currencies/{currency}']);
