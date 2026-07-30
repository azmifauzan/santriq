<?php

namespace App\Http\Responses;

use App\Models\User;
use App\Support\TenantSessionHandoff;
use Illuminate\Http\Request;
use Laravel\Fortify\Contracts\VerifyEmailResponse as VerifyEmailResponseContract;
use Symfony\Component\HttpFoundation\Response;

class VerifyEmailResponse implements VerifyEmailResponseContract
{
    public function toResponse($request): Response
    {
        /** @var Request $request */
        /** @var User $user */
        $user = $request->user();

        // Fortify's own verification.verify route has no domain constraint (it
        // matches whatever host the emailed link was clicked on — usually the
        // apex, since that's where registration happened), and its default
        // response redirects to the fixed path config('fortify.home'), which
        // only exists under the tenant subdomain route group. Build the
        // dashboard URL from the user's own tenant instead, same pattern as
        // LoginResponse and RegisterResponse. The emailed link is normally
        // opened on the apex, whose session cookie never reaches the subdomain,
        // so hand the identity over rather than redirect straight in.
        return TenantSessionHandoff::isOnOwnTenant($user)
            ? redirect()->intended(route('dashboard', ['subdomain' => $user->tenant->subdomain]))
            : TenantSessionHandoff::redirect($user);
    }
}
