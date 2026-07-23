<?php

namespace App\Support;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;
use JsonException;

/**
 * The Google OAuth redirect bounces through accounts.google.com and back to a
 * fixed apex callback URL (see GOOGLE_REDIRECT_URI), so it can cross tenant
 * subdomains. SESSION_DOMAIN is null (session cookies are host-scoped, see
 * config/session.php), meaning a session started on a tenant subdomain is
 * invisible to the apex callback — Socialite's default session-based `state`
 * can't survive that hop. This carries arbitrary short-lived payloads
 * (OAuth state, or a just-authenticated Google identity) across the redirect
 * as a signed, timestamped token instead.
 */
class GoogleOAuthToken
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public static function encode(array $payload): string
    {
        return Crypt::encryptString(json_encode([...$payload, 'ts' => now()->timestamp], JSON_THROW_ON_ERROR));
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function decode(string $token, int $maxAgeSeconds = 600): ?array
    {
        try {
            $payload = json_decode(Crypt::decryptString($token), true, flags: JSON_THROW_ON_ERROR);
        } catch (DecryptException|JsonException) {
            return null;
        }

        if (! is_array($payload) || ! isset($payload['ts']) || now()->timestamp - $payload['ts'] > $maxAgeSeconds) {
            return null;
        }

        return $payload;
    }
}
