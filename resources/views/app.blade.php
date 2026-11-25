<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"  @class(['dark' => ($appearance ?? 'system') == 'dark'])>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        {{-- Rendered server-side so crawlers that do not execute JavaScript (Google's
             OAuth branding verification among them) can read what this app is for. --}}
        @php($siteDescription = 'SantriQ menyediakan absensi QR santri TPQ/TPA lewat kamera HP tanpa alat tambahan, plus notifikasi Telegram dan SPP. Daftar gratis atau coba demo.')
        @php($publicPages = ['Welcome', 'Legal', 'RequestFeature', 'Feature', 'Article', 'ArticleIndex', 'Guide'])
        @php($isWelcome = ($page['component'] ?? null) === 'Welcome')
        {{-- Must match each page's client-side <Head> title, which app.ts suffixes with " - SantriQ". --}}
        @php($pageTitle = match ($page['component'] ?? null) {
            'Welcome' => 'Absensi QR Santri & Aplikasi TPQ/TPA Gratis - SantriQ',
            'Feature', 'Article', 'Guide', 'Legal' => $page['props']['content']['title'].' - SantriQ',
            'ArticleIndex' => 'Template dan Panduan Administrasi TPQ/TPA Gratis - SantriQ',
            'RequestFeature' => 'Fitur Aplikasi Manajemen TPQ/TPA - SantriQ',
            'auth/Login' => 'Masuk Akun Lembaga TPQ/TPA - SantriQ',
            'auth/Register' => 'Daftar Lembaga TPQ/TPA Gratis - SantriQ',
            'auth/ForgotPassword' => 'Lupa Kata Sandi Akun - SantriQ',
            'auth/ResetPassword' => 'Atur Ulang Kata Sandi - SantriQ',
            'auth/VerifyEmail' => 'Verifikasi Alamat Email - SantriQ',
            'auth/ConfirmPassword' => 'Konfirmasi Kata Sandi - SantriQ',
            'Tenant/Landing' => ($page['props']['tenant']['name'] ?? 'Lembaga').' - SantriQ',
            default => config('app.name', 'Laravel'),
        })
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
                            '@type' => 'WebSite',
                            '@id' => url('/').'#website',
                            'name' => 'SantriQ',
                            'url' => url('/'),
                            'inLanguage' => 'id',
                            'publisher' => ['@id' => url('/').'#organization'],
                        ],
                        [
                            '@type' => 'Organization',
                            '@id' => url('/').'#organization',
                            'name' => 'SantriQ',
                            'url' => url('/'),
                            'description' => $siteDescription,
                            'logo' => [
                                '@type' => 'ImageObject',
                                'url' => asset('apple-touch-icon.png'),
                            ],
                            'founder' => ['@id' => url('/').'#founder'],
                            'sameAs' => [
                                'https://github.com/azmifauzan/santriq',
                            ],
                            'contactPoint' => [
                                '@type' => 'ContactPoint',
                                'contactType' => 'customer support',
                                'url' => route('request-feature').'#request',
                                'availableLanguage' => ['id', 'en'],
                            ],
                        ],
                        [
                            '@type' => 'Person',
                            '@id' => url('/').'#founder',
                            'name' => 'Fauzan Azmi',
                            'jobTitle' => 'Pembuat dan Pengelola SantriQ',
                            'url' => 'https://github.com/azmifauzan',
                            'sameAs' => [
                                'https://github.com/azmifauzan',
                                'https://www.threads.com/@azmifauzan',
                                'https://www.facebook.com/azmifauzan/',
                                'https://www.linkedin.com/in/fauzan-azmi-29094522/',
                            ],
                            'worksFor' => ['@id' => url('/').'#organization'],
                        ],
                        [
                            '@type' => 'SoftwareApplication',
                            '@id' => url('/').'#app',
                            'name' => 'SantriQ',
                            'url' => url('/'),
                            'description' => $siteDescription,
                            'applicationCategory' => 'EducationalApplication',
                            'operatingSystem' => 'Web',
                            'inLanguage' => 'id',
                            'isAccessibleForFree' => true,
                            'author' => ['@id' => url('/').'#founder'],
                            'publisher' => ['@id' => url('/').'#organization'],
                            'offers' => ['@type' => 'Offer', 'price' => '0', 'priceCurrency' => 'IDR'],
                        ],
                    ],
                ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}
            </script>
        @elseif (($page['component'] ?? null) === 'Legal')
            <meta name="description" content="{{ $page['props']['content']['description'] }}">
        @elseif (($page['component'] ?? null) === 'Guide')
            <meta name="description" content="{{ $page['props']['content']['description'] }}">
        @elseif (($page['component'] ?? null) === 'RequestFeature')
            <meta name="description" content="Daftar fitur SantriQ untuk TPQ/TPA: absensi QR, notifikasi wali via Telegram, SPP, rapor, perizinan, dan kartu santri. Usulkan fitur baru di sini.">
        @elseif (($page['component'] ?? null) === 'ArticleIndex')
            <meta name="description" content="Unduh template Excel gratis untuk rapor TPQ dan buku kas SPP, lengkap dengan contoh catatan akhlak santri, plus panduan notifikasi wali via Telegram.">
        @elseif (($page['component'] ?? null) === 'Article')
            @php($article = $page['props']['content'])
            <meta name="description" content="{{ $article['description'] }}">
            <script type="application/ld+json">
                {!! json_encode([
                    '@context' => 'https://schema.org',
                    '@graph' => [
                        [
                            '@type' => 'Article',
                            'headline' => $article['heading'],
                            'description' => $article['description'],
                            'datePublished' => $article['published_at'],
                            'inLanguage' => 'id',
                            'mainEntityOfPage' => url()->current(),
                            'author' => ['@type' => 'Organization', 'name' => 'SantriQ'],
                            'publisher' => ['@type' => 'Organization', 'name' => 'SantriQ', 'logo' => ['@type' => 'ImageObject', 'url' => asset('apple-touch-icon.png')]],
                        ],
                        [
                            '@type' => 'FAQPage',
                            'mainEntity' => array_map(fn (array $item): array => [
                                '@type' => 'Question',
                                'name' => $item['q'],
                                'acceptedAnswer' => ['@type' => 'Answer', 'text' => $item['a']],
                            ], $article['faq']),
                        ],
                    ],
                ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}
            </script>
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
        @elseif (($page['component'] ?? null) === 'auth/Login')
            <meta name="description" content="Masuk ke dasbor lembaga TPA/TPQ Anda di SantriQ untuk mengelola absensi QR santri, notifikasi Telegram, rapor, dan SPP.">
        @elseif (($page['component'] ?? null) === 'auth/Register')
            <meta name="description" content="Daftarkan lembaga TPA/TPQ Anda secara gratis di SantriQ untuk memakai absensi QR santri, notifikasi wali Telegram, rapor, dan SPP.">
        @elseif (($page['component'] ?? null) === 'auth/ForgotPassword')
            <meta name="description" content="Minta tautan atur ulang kata sandi untuk akun pengelola lembaga SantriQ Anda.">
        @endif

        {{-- Link previews on WhatsApp, Telegram, Facebook, X, etc. --}}
        <meta property="og:type" content="website">
        <meta property="og:site_name" content="SantriQ">
        <meta property="og:title" content="{{ $pageTitle }}">
        <meta property="og:description" content="{{ $siteDescription }}">
        <meta property="og:url" content="{{ url()->current() }}">
        <meta property="og:image" content="{{ asset('og-image.jpg') }}">
        <meta property="og:image:width" content="1200">
        <meta property="og:image:height" content="630">
        <meta property="og:image:alt" content="SantriQ — aplikasi absensi QR santri dan manajemen TPA/TPQ gratis dengan notifikasi Telegram">
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
            <title>{{ $pageTitle }}</title>
        </x-inertia::head>
    </head>
    <body class="font-sans antialiased">
        <x-inertia::app />

        @include('partials.crawler-fallback')
    </body>
</html>
