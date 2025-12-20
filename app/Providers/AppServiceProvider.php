<?php

namespace App\Providers;

use Illuminate\Support\Facades\Vite;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Symfony\Component\Mailer\Bridge\Brevo\Transport\BrevoTransportFactory;
use Symfony\Component\Mailer\Transport\Dsn;
use App\Models\Collection;
use App\Models\CollectionVinyl;
use App\Observers\CollectionObserver;
use App\Observers\CollectionVinylObserver;
use App\Prometheus\FixedLaravelCacheAdapter;
use Prometheus\CollectorRegistry;
use Illuminate\Support\Facades\Cache;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Override Spatie's buggy adapter with our fixed version
        $this->app->scoped(CollectorRegistry::class, function () {
            $cacheStore = config('prometheus.cache');

            if ($cacheStore === null) {
                return new CollectorRegistry(new \Prometheus\Storage\InMemory(), false);
            }

            return new CollectorRegistry(
                new FixedLaravelCacheAdapter(Cache::store($cacheStore)),
                false
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);

        Mail::extend('brevo', function () {
            return (new BrevoTransportFactory)->create(
                new Dsn(
                    'brevo+api',
                    'default',
                    config('services.brevo.key')
                )
            );
        });

        // Rate limiter pour les exports Excel
        RateLimiter::for('export', function (Request $request) {
            return Limit::perMinute(1)->by($request->user()?->id ?: $request->ip());
        });

        // Register observers for automatic count synchronization
        Collection::observe(CollectionObserver::class);
        CollectionVinyl::observe(CollectionVinylObserver::class);
    }
}
