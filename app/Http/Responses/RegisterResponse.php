<?php

namespace App\Http\Responses;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Laravel\Fortify\Contracts\RegisterResponse as RegisterResponseContract;

class RegisterResponse implements RegisterResponseContract
{
    public function toResponse($request): RedirectResponse
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
        return $user->hasVerifiedEmail()
            ? redirect()->intended(route('dashboard', ['subdomain' => $user->tenant->subdomain]))
            : redirect()->route('verification.notice');
    }
}
