<?php

namespace App\Http\Responses;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Fortify\Contracts\RegisterResponse as RegisterResponseContract;

class RegisterResponse implements RegisterResponseContract
{
    public function toResponse($request): RedirectResponse
    {
        /** @var Request $request */
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // The `login` route stays central (no {subdomain} parameter) in both
        // subdomain-active and path-fallback mode — see LoginResponse for why
        // the dashboard redirect after a successful login still lands on the
        // right lembaga regardless.
        return redirect()->away(route('login', ['registered' => 1]));
    }
}
