<?php

namespace App\Http\Middleware;

use App\Support\CurrentTenant;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();
        if ($user) {
            $user->load('tenant');
        }

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $user,
            ],
            // The lembaga the current request resolved to (subdomain host, or the
            // {subdomain} path segment in fallback mode — see ResolveTenantFromDomain),
            // falling back to the logged-in user's own tenant on pages that never
            // resolve one (the central marketing page). Fed into Wayfinder's client-side
            // URL defaults in resources/js/lib/tenantUrlDefaults.ts so every generated
            // route function that needs a `subdomain` keeps working without passing it
            // explicitly at every call site.
            'subdomain' => CurrentTenant::resolved() ? CurrentTenant::get()->subdomain : $user?->tenant?->subdomain,
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }
}
