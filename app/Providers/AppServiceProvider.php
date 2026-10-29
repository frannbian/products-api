<?php

namespace App\Providers;

use App\Application\Shared\Contracts\AuditTrail;
use App\Application\Shared\Contracts\EventPublisher;
use App\Application\Shared\Contracts\TransactionManager;
use App\Infrastructure\Shared\Audit\EloquentAuditTrail;
use App\Infrastructure\Shared\Audit\RecordAuditEntry;
use App\Infrastructure\Shared\Health\CacheHealthCheck;
use App\Infrastructure\Shared\Health\DatabaseHealthCheck;
use App\Infrastructure\Shared\Messaging\DomainEventMessage;
use App\Infrastructure\Shared\Messaging\LaravelEventPublisher;
use App\Infrastructure\Shared\Persistence\DatabaseTransactionManager;
use Dedoc\Scramble\Scramble;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\SecurityScheme;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\DiagnosingHealth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * All of the container bindings that should be registered.
     *
     * @var array<class-string, class-string>
     */
    public array $bindings = [
        TransactionManager::class => DatabaseTransactionManager::class,
        EventPublisher::class => LaravelEventPublisher::class,
        AuditTrail::class => EloquentAuditTrail::class,
    ];

    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Consumers of domain event messages: each one is a queued listener.
        Event::listen(DomainEventMessage::class, RecordAuditEntry::class);

        Event::listen(DiagnosingHealth::class, DatabaseHealthCheck::class);
        Event::listen(DiagnosingHealth::class, CacheHealthCheck::class);

        // Fail fast on N+1 queries and attribute typos while developing.
        Model::shouldBeStrict(! $this->app->isProduction());

        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->getAuthIdentifier() ?: $request->ip());
        });

        Scramble::configure()->withDocumentTransformers(function (OpenApi $openApi) {
            $openApi->secure(SecurityScheme::http('bearer', 'JWT'));
        });
    }
}
