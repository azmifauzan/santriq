<?php

namespace App\Http\Responses;

use App\Models\User;
use App\Support\TenantSessionHandoff;
use Illuminate\Http\Request;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Symfony\Component\HttpFoundation\Response;

class LoginResponse implements LoginResponseContract
{
    public function toResponse($request): Response
    {
        /** @var Request $request */
        /** @var User $user */
        $user = $request->user();

        // Fortify's own `home` redirect is a fixed string (config('fortify.home')),
        // which can't carry a {subdomain} route parameter. The `login` route is
        // reachable from any host/path (see RegisterResponse), so the request
        // that authenticated this user may not have resolved a tenant at all —
        // build the dashboard URL from the user's own tenant instead of relying
        // on ambient URL::defaults(). When the login happened off the user's own
        // subdomain (i.e. on the apex), the session cookie cannot follow them
        // there either, so hand the identity over through a signed link.
        return TenantSessionHandoff::isOnOwnTenant($user)
            ? redirect()->intended(route('dashboard', ['subdomain' => $user->tenant->subdomain]))
            : TenantSessionHandoff::redirect($user);
    }
}
