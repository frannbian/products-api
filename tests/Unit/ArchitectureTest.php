<?php

arch('domain does not depend on outer layers')
    ->expect('App\Domain')
    ->not->toUse(['App\Application', 'App\Infrastructure', 'App\Presentation']);

arch('domain does not depend on the framework')
    ->expect('App\Domain')
    ->not->toUse(['Illuminate', 'Laravel', 'Database']);

arch('application does not depend on infrastructure or presentation')
    ->expect('App\Application')
    ->not->toUse(['App\Infrastructure', 'App\Presentation']);

arch('application only uses the framework for auth events')
    ->expect('App\Application')
    ->not->toUse([
        'Illuminate\Database',
        'Illuminate\Foundation',
        'Illuminate\Http',
        'Illuminate\Support',
        'Illuminate\Validation',
        'Laravel',
    ]);

arch('infrastructure implements contracts but never drives use cases')
    ->expect('App\Infrastructure')
    ->not->toUse([
        'App\Presentation',
        'App\Application\Identity\UseCases',
    ]);

arch('presentation delegates to the application layer')
    ->expect('App\Presentation')
    ->not->toUse([
        'App\Infrastructure',
        'App\Domain\Identity\Repositories',
        'Illuminate\Support\Facades\Auth',
        'Illuminate\Support\Facades\Hash',
        'Illuminate\Support\Facades\Password',
        'Illuminate\Support\Facades\DB',
    ]);

arch('controllers live in the presentation layer')
    ->expect('App\Presentation\Http\Controllers')
    ->toHaveSuffix('Controller')
    ->toExtend('App\Presentation\Http\Controllers\Controller')
    ->ignoring('App\Presentation\Http\Controllers\Controller');

arch('value objects are final and readonly')
    ->expect('App\Domain\Identity\ValueObjects')
    ->toBeFinal()
    ->toBeReadonly();

arch('domain exceptions are domain exceptions')
    ->expect('App\Domain\Identity\Exceptions')
    ->toExtend(DomainException::class);

arch('application contracts are interfaces')
    ->expect('App\Application\Identity\Contracts')
    ->toBeInterfaces();

arch('use cases are final')
    ->expect('App\Application\Identity\UseCases')
    ->toBeFinal();
