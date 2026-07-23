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
        $tenant = $request->user()->tenant;

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        $scheme = $request->getScheme();
        $url = "{$scheme}://{$tenant->subdomain}.".config('tenancy.domain').'/login?registered=1';

        return redirect()->away($url);
    }
}
