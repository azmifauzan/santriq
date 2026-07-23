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
];
