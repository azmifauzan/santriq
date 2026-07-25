<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\URL;

/**
 * Moves an authenticated identity from the apex domain onto a tenant subdomain.
 *
 * Registration, login, and email verification all live on the apex domain
 * (their routes carry no domain constraint), but the dashboard only exists
 * under {subdomain}.santriq.web.id. SESSION_DOMAIN is deliberately null, so the
 * session cookie minted on the apex is host-scoped and never reaches the
 * subdomain — redirecting straight to the dashboard lands there as a guest and
 * bounces back to the login screen. Hand the identity over through a
 * short-lived signed link that the subdomain verifies and logs in itself.
 */
class TenantSessionHandoff
{
    private const LINK_LIFETIME_MINUTES = 5;

    public static function redirect(User $user): RedirectResponse
    {
        return redirect(self::link($user));
    }

    public static function link(User $user): string
    {
        return URL::temporarySignedRoute(
            'tenant.session.verify',
            now()->addMinutes(self::LINK_LIFETIME_MINUTES),
            ['subdomain' => $user->tenant->subdomain, 'user' => $user->id]
        );
    }

    /**
     * True when the request already runs on the user's own tenant subdomain,
     * in which case the session is on the right host and no handoff is needed.
     */
    public static function isOnOwnTenant(User $user): bool
    {
        return CurrentTenant::resolved() && CurrentTenant::get()->id === $user->tenant_id;
    }
}
