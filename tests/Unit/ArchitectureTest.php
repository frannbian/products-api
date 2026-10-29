<?php

arch('domain does not depend on outer layers')
    ->expect('App\Domain')
    ->not->toUse(['App\Application', 'App\Infrastructure', 'App\Presentation']);

arch('domain does not depend on the framework')
    ->expect('App\Domain')
    ->not->toUse(['Illuminate', 'Laravel', 'Database', 'PHPOpenSourceSaver']);

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
        'PHPOpenSourceSaver',
    ]);

// Queue jobs are entry points, like controllers: a worker receives a message
// and runs a use case. Every other infrastructure class only implements
// contracts and never drives the application.
arch('infrastructure implements contracts but never drives use cases')
    ->expect('App\Infrastructure')
    ->not->toUse([
        'App\Presentation',
        'App\Application\Identity\UseCases',
        'App\Application\Catalog\UseCases',
        'App\Application\Currency\UseCases',
    ])
    ->ignoring('App\Infrastructure\Catalog\Jobs');

arch('queue jobs never depend on the presentation layer')
    ->expect('App\Infrastructure\Catalog\Jobs')
    ->not->toUse('App\Presentation');

arch('presentation delegates to the application layer')
    ->expect('App\Presentation')
    ->not->toUse([
        'App\Infrastructure',
        'App\Domain\Identity\Repositories',
        'App\Domain\Catalog\Repositories',
        'App\Domain\Currency\Repositories',
        'Illuminate\Support\Facades\Auth',
        'Illuminate\Support\Facades\Hash',
        'Illuminate\Support\Facades\Password',
        'Illuminate\Support\Facades\DB',
        'Illuminate\Database\Eloquent',
    ]);

arch('controllers live in the presentation layer')
    ->expect('App\Presentation\Http\Controllers')
    ->toHaveSuffix('Controller')
    ->toExtend('App\Presentation\Http\Controllers\Controller')
    ->ignoring('App\Presentation\Http\Controllers\Controller');

arch('form requests live in the presentation layer')
    ->expect('Illuminate\Foundation\Http\FormRequest')
    ->toOnlyBeUsedIn('App\Presentation\Http\Requests');

// Service providers are the composition root: they may configure Eloquent.
arch('eloquent models live in the infrastructure layer')
    ->expect('Illuminate\Database\Eloquent\Model')
    ->toOnlyBeUsedIn(['App\Infrastructure', 'App\Providers']);

arch('value objects are final and readonly')
    ->expect([
        'App\Domain\Identity\ValueObjects',
        'App\Domain\Catalog\ValueObjects',
        'App\Domain\Currency\ValueObjects',
    ])
    ->toBeFinal()
    ->toBeReadonly();

arch('domain entities are final')
    ->expect([
        'App\Domain\Identity\Entities',
        'App\Domain\Catalog\Entities',
        'App\Domain\Currency\Entities',
    ])
    ->toBeFinal();

arch('domain exceptions are domain exceptions')
    ->expect([
        'App\Domain\Identity\Exceptions',
        'App\Domain\Catalog\Exceptions',
        'App\Domain\Currency\Exceptions',
    ])
    ->toExtend(DomainException::class);

arch('repositories are interfaces in the domain')
    ->expect([
        'App\Domain\Identity\Repositories',
        'App\Domain\Catalog\Repositories',
        'App\Domain\Currency\Repositories',
    ])
    ->toBeInterfaces();

arch('application contracts are interfaces')
    ->expect('App\Application\Identity\Contracts')
    ->toBeInterfaces();

arch('use cases are final')
    ->expect([
        'App\Application\Identity\UseCases',
        'App\Application\Catalog\UseCases',
        'App\Application\Currency\UseCases',
    ])
    ->toBeFinal();
