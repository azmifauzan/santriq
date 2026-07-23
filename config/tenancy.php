<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Tenant Root Domain
    |--------------------------------------------------------------------------
    |
    | Every lembaga is served from {subdomain}.{domain}. The apex domain
    | itself only serves the marketing page, registration, and the Telegram
    | webhook (see routes/web.php and routes/tenant.php).
    |
    */
    'domain' => env('APP_TENANT_DOMAIN', 'santriq.web.id'),

    /*
    |--------------------------------------------------------------------------
    | Wildcard Subdomain Active
    |--------------------------------------------------------------------------
    |
    | Wildcard DNS + TLS for *.{domain} is an infra prerequisite that may not
    | be provisioned yet (see docs/RENCANA-IMPLEMENTASI.md § 0, "sisa: deploy
    | produksi"). While it isn't, set this to false: routes/tenant.php mounts
    | every lembaga route under {domain}/{subdomain}/... on the apex instead
    | of {subdomain}.{domain}/..., so lembaga are still reachable with a
    | plain A record on the apex. Flipping this value changes which routes
    | exist, so it requires a full app restart (and `npm run build`, since
    | Wayfinder bakes the route shape into the generated TS) — it is not
    | something a single request can toggle.
    |
    */
    'subdomain_active' => (bool) env('APP_TENANT_SUBDOMAIN_ACTIVE', true),
];
