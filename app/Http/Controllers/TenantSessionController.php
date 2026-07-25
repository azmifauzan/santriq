<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\CurrentTenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TenantSessionController extends Controller
{
    /**
     * Consume a signed handoff link minted on the apex domain and establish the
     * session on this subdomain. See App\Support\TenantSessionHandoff for why
     * the session cannot simply be carried across.
     */
    public function verify(Request $request, User $user): RedirectResponse
    {
        abort_unless($request->hasValidSignature(), 403);
        abort_unless($user->tenant_id === CurrentTenant::get()->id, 403);

        Auth::guard('web')->login($user);

        return redirect()->intended(route('dashboard', ['subdomain' => CurrentTenant::get()->subdomain]));
    }
}
