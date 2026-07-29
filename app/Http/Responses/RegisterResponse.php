<?php

namespace App\Http\Responses;

use App\Models\User;
use App\Support\TenantSessionHandoff;
use Illuminate\Http\Request;
use Laravel\Fortify\Contracts\RegisterResponse as RegisterResponseContract;
use Symfony\Component\HttpFoundation\Response;

class RegisterResponse implements RegisterResponseContract
{
    public function toResponse($request): Response
    {
        /** @var Request $request */
        /** @var User $user */
        $user = $request->user();

        // A Google signup already has its email attested by Google (see
        // CreateNewUser), so there's nothing to verify — go straight in.
        // A manual signup still needs to click the link Fortify just emailed;
        // Fortify's controller already logged them in before this response
        // runs, so send them to the (host-agnostic, see route:list) email
        // verification prompt instead of back out to /login.
        if (! $user->hasVerifiedEmail()) {
            return redirect()->route('verification.notice');
        }

        // Registration always runs on the apex domain, so the session Fortify
        // just created cannot follow the user to their subdomain — hand it off.
        return TenantSessionHandoff::isOnOwnTenant($user)
            ? redirect()->intended(route('dashboard', ['subdomain' => $user->tenant->subdomain]))
            : TenantSessionHandoff::redirect($user);
    }
}
