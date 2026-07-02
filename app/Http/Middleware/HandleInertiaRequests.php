<?php

namespace App\Http\Middleware;

use App\Models\Collection;
use App\Models\User;
use App\Models\Vinyl;
use App\Models\VinylFormat;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Middleware;
use Tighten\Ziggy\Ziggy;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return array_merge(parent::share($request), [
            'auth' => [
                'user' => $request->user() ? $this->formatUser($request->user()->load('roles')) : null,
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'message' => fn () => $request->session()->get('message'),
                'new_image_url' => fn () => $request->session()->get('new_image_url'),
            ],
            'ziggy' => function () use ($request) {
                return array_merge((new Ziggy)->toArray(), [
                    'location' => $request->url(),
                ]);
            },
            'globalStats' => fn () => $this->getGlobalStats(),
            'vinylFormats' => fn () => VinylFormat::allCached(),
            'recaptcha' => fn () => [
                'enabled' => (bool) config('services.recaptcha.enabled', false)
                    && filled(config('services.recaptcha.site_key'))
                    && filled(config('services.recaptcha.secret_key')),
                'siteKey' => config('services.recaptcha.site_key'),
            ],
        ]);
    }

    /**
     * Format user data for frontend
     */
    private function formatUser($user)
    {
        $userData = $user->toArray();

        // L'avatar contient déjà l'URL complète S3, pas besoin de transformation
        // (contrairement aux autres assets qui pourraient être des paths relatifs)

        // Include unread messages count
        $userData['unread_messages_count'] = $user->unread_messages_count ?? 0;

        return $userData;
    }

    /**
     * Get global site statistics (cached for 1 hour)
     */
    private function getGlobalStats(): array
    {
        return Cache::remember('global_stats', 3600, function () {
            return [
                'totalVinyls' => Vinyl::count(),
                'totalUsers' => User::whereNotNull('email_verified_at')->count(),
                'totalCollections' => Collection::where('visibility', 'public')->count(),
            ];
        });
    }
}
