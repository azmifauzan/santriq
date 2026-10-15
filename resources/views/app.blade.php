<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"  @class(['dark' => ($appearance ?? 'system') == 'dark'])>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        {{-- Rendered server-side so crawlers that do not execute JavaScript (Google's
             OAuth branding verification among them) can read what this app is for. --}}
        @php($siteDescription = 'SantriQ adalah platform gratis dan open source untuk manajemen TPA/TPQ: absensi QR, notifikasi kehadiran ke wali santri via Telegram, pencatatan pencapaian, SPP, dan perizinan santri.')
        @if (($page['component'] ?? null) === 'Welcome')
            <meta name="description" content="{{ $siteDescription }}">
        @elseif (($page['component'] ?? null) === 'Legal')
            <meta name="description" content="{{ $page['props']['content']['description'] }}">
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

        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">

        @fonts

        @vite(['resources/css/app.css', 'resources/js/app.ts', "resources/js/pages/{$page['component']}.vue"])
        <x-inertia::head>
            <title>{{ config('app.name', 'Laravel') }}</title>
        </x-inertia::head>
    </head>
    <body class="font-sans antialiased">
        <x-inertia::app />

        @include('partials.crawler-fallback')
    </body>
</html>
