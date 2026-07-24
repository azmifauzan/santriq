<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

class ResolveTenantFromDomain
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $subdomain = config('tenancy.subdomain_active')
            ? $this->subdomainFromHost($request)
            : $this->subdomainFromPath($request);

        if ($subdomain !== null) {
            $tenant = Tenant::where('subdomain', $subdomain)->first();

            abort_unless($tenant !== null, 404);

            app()->instance(Tenant::class, $tenant);
            URL::defaults(['subdomain' => $subdomain]);
        } elseif ($request->user('web') && $request->is('login', 'register')) {
            Auth::guard('web')->logout();
        }

        // {subdomain} is a route parameter on every tenant route (domain segment
        // or URI prefix, depending on mode) but no controller method declares a
        // matching $subdomain argument. Left in place, that stray entry desyncs
        // Illuminate\Routing\ResolvesRouteDependencies::resolveMethodDependencies(),
        // which splices container-resolved dependencies (like $request) into the
        // route-parameters array *by reflection position* — the extra entry shifts
        // later positional slots, and a route-model-bound argument (e.g. Attendance
        // $attendance) can end up receiving this raw string instead of its model.
        $request->route()?->forgetParameter('subdomain');

        return $next($request);
    }

    /**
     * Subdomain-active mode: the lembaga is encoded in the Host header
     * ({subdomain}.{tenancy.domain}).
     */
    private function subdomainFromHost(Request $request): ?string
    {
        $suffix = '.'.config('tenancy.domain');
        $host = $request->getHost();

        if (! str_ends_with($host, $suffix)) {
            return null;
        }

        return substr($host, 0, -strlen($suffix));
    }

    /**
     * Fallback mode (no wildcard DNS yet): the lembaga is the first URI
     * segment on the apex domain ({tenancy.domain}/{subdomain}/...), matched
     * by routes/tenant.php's {subdomain} prefix.
     */
    private function subdomainFromPath(Request $request): ?string
    {
        $subdomain = $request->route()?->parameter('subdomain');

        return is_string($subdomain) ? $subdomain : null;
    }
}
