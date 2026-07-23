<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

class ResolveTenantFromDomain
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $suffix = '.'.config('tenancy.domain');
        $host = $request->getHost();

        if (! str_ends_with($host, $suffix)) {
            return $next($request);
        }

        $subdomain = substr($host, 0, -strlen($suffix));

        $tenant = Tenant::where('subdomain', $subdomain)->first();

        abort_unless($tenant !== null, 404);

        app()->instance(Tenant::class, $tenant);
        URL::defaults(['subdomain' => $subdomain]);
        $request->route()?->forgetParameter('subdomain');

        return $next($request);
    }
}
