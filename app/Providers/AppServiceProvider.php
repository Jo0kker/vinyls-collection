<?php

namespace App\Providers;

use Illuminate\Support\Facades\Vite;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Auth\Events\Registered;
use Laravel\Pulse\Facades\Pulse;
use App\Models\Collection;
use App\Models\CollectionVinyl;
use App\Observers\CollectionObserver;
use App\Observers\CollectionVinylObserver;
use App\Listeners\LinkSupportTicketsToUser;

class AppServiceProvider extends ServiceProvider
{
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
        Vite::prefetch(concurrency: 3);

        // Rate limiter pour les exports Excel
        RateLimiter::for('export', function (Request $request) {
            return Limit::perMinute(1)->by($request->user()?->id ?: $request->ip());
        });

        // Register observers for automatic count synchronization
        Collection::observe(CollectionObserver::class);
        CollectionVinyl::observe(CollectionVinylObserver::class);

        // Pulse authorization - only users with 'view pulse' permission can access
        Gate::define('viewPulse', function ($user) {
            return $user->hasPermissionTo('view pulse');
        });

        // Link support tickets to user on registration
        Event::listen(Registered::class, LinkSupportTicketsToUser::class);
    }
}
