<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\CurrentTenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class TenantSessionController extends Controller
{
    /**
     * Consume a signed handoff link minted on the apex domain and establish the
     * session on this subdomain. See App\Support\TenantSessionHandoff for why
     * the session cannot simply be carried across.
     *
     * Renders a branded "signing you in" page that navigates onward client-side
     * rather than issuing an immediate 302: a bare, contentless redirect from a
     * link carrying a numeric user id and signature is exactly the shape
     * Chrome's phishing heuristics flagged on this route in production.
     */
    public function verify(Request $request, User $user): Response
    {
        abort_unless($request->hasValidSignature(), 403);
        abort_unless($user->tenant_id === CurrentTenant::get()->id, 403);

        Auth::guard('web')->login($user);

        $redirectTo = $request->session()->pull(
            'url.intended',
            route('dashboard', ['subdomain' => CurrentTenant::get()->subdomain])
        );

        return Inertia::render('auth/SigningIn', ['redirectTo' => $redirectTo]);
    }
}
