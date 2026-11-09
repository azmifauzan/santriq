<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"  @class(['dark' => ($appearance ?? 'system') == 'dark'])>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        {{-- Rendered server-side so crawlers that do not execute JavaScript (Google's
             OAuth branding verification among them) can read what this app is for. --}}
        @php($siteDescription = 'SantriQ adalah platform gratis dan open source untuk manajemen TPA/TPQ: absensi QR, notifikasi kehadiran ke wali santri via Telegram, pencatatan pencapaian, SPP, dan perizinan santri.')
        @php($publicPages = ['Welcome', 'Legal', 'RequestFeature', 'Feature'])
        @php($isWelcome = ($page['component'] ?? null) === 'Welcome')
        @if (in_array($page['component'] ?? null, $publicPages, true))
            <link rel="canonical" href="{{ url()->current() }}">
        @else
            {{-- Everything outside the marketing pages is a tenant or account screen. --}}
            <meta name="robots" content="noindex, nofollow">
        @endif
        @if ($isWelcome)
            <meta name="description" content="{{ $siteDescription }}">
            <script type="application/ld+json">
                {!! json_encode([
                    '@context' => 'https://schema.org',
                    '@graph' => [
                        [
                            '@type' => 'SoftwareApplication',
                            'name' => 'SantriQ',
                            'url' => url('/'),
                            'description' => $siteDescription,
                            'applicationCategory' => 'EducationalApplication',
                            'operatingSystem' => 'Web',
                            'inLanguage' => 'id',
                            'isAccessibleForFree' => true,
                            'offers' => ['@type' => 'Offer', 'price' => '0', 'priceCurrency' => 'IDR'],
                        ],
                        [
                            '@type' => 'Organization',
                            'name' => 'SantriQ',
                            'url' => url('/'),
                            'logo' => asset('apple-touch-icon.png'),
                        ],
                    ],
                ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}
            </script>
        @elseif (($page['component'] ?? null) === 'Legal')
            <meta name="description" content="{{ $page['props']['content']['description'] }}">
        @elseif (($page['component'] ?? null) === 'Feature')
            @php($feature = $page['props']['content'])
            <meta name="description" content="{{ $feature['description'] }}">
            <script type="application/ld+json">
                {!! json_encode([
                    '@context' => 'https://schema.org',
                    '@type' => 'FAQPage',
                    'mainEntity' => array_map(fn (array $item): array => [
                        '@type' => 'Question',
                        'name' => $item['q'],
                        'acceptedAnswer' => ['@type' => 'Answer', 'text' => $item['a']],
                    ], $feature['faq']),
                ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}
            </script>
        @endif

        {{-- Link previews on WhatsApp, Telegram, Facebook, X, etc. --}}
        <meta property="og:type" content="website">
        <meta property="og:site_name" content="SantriQ">
        <meta property="og:title" content="SantriQ — Kelola santri lebih mudah, dampingi lebih dekat">
        <meta property="og:description" content="{{ $siteDescription }}">
        <meta property="og:url" content="{{ url()->current() }}">
        <meta property="og:image" content="{{ asset('og-image.jpg') }}">
        <meta property="og:image:width" content="1200">
        <meta property="og:image:height" content="630">
        <meta property="og:image:alt" content="SantriQ — aplikasi manajemen TPA/TPQ gratis dengan absensi QR dan notifikasi Telegram">
        <meta property="og:locale" content="id_ID">
        <meta name="twitter:card" content="summary_large_image">

        {{-- Inline script to detect system dark mode preference and apply it immediately --}}
        <script>
            (function() {
                const appearance = '{{ $appearance ?? "system" }}';

                if (appearance === 'system') {
                    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

                    if (prefersDark) {
                        document.documentElement.classList.add('dark');
                    }
                }
            })();
        </script>

        {{-- Inline style to set the HTML background color based on our theme in app.css --}}
        <style>
            html {
                background-color: oklch(1 0 0);
            }

            html.dark {
                background-color: oklch(0.145 0 0);
            }
        </style>

        {{-- Marketing and signup pages only: tenant screens (dashboard, students, ...)
             would otherwise flood the property with app traffic. --}}
        @if (! app()->bound(\App\Models\Tenant::class)
            && in_array($page['component'] ?? null, [...$publicPages, 'auth/Login', 'auth/Register', 'auth/VerifyEmail'], true)
            && ($gaId = config('services.google_analytics.measurement_id')))
            <script async src="https://www.googletagmanager.com/gtag/js?id={{ $gaId }}"></script>
            <script>
                window.dataLayer = window.dataLayer || [];
                function gtag(){dataLayer.push(arguments);}
                gtag('js', new Date());
                gtag('config', @js($gaId));
            </script>
        @endif

        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">

        @fonts

        @vite(['resources/css/app.css', 'resources/js/app.ts', "resources/js/pages/{$page['component']}.vue"])
        <x-inertia::head>
            <title>{{ $isWelcome ? 'Aplikasi Manajemen TPQ/TPA Gratis, Absensi QR & Notifikasi Wali - SantriQ' : (($page['component'] ?? null) === 'Feature' ? $page['props']['content']['title'].' - SantriQ' : config('app.name', 'Laravel')) }}</title>
        </x-inertia::head>
    </head>
    <body class="font-sans antialiased">
        <x-inertia::app />

        @include('partials.crawler-fallback')
    </body>
</html>
