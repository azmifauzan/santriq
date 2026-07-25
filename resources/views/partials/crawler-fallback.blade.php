{{--
    Public pages are client-rendered, so without this block a request that does not
    run JavaScript sees an empty body. Google's OAuth branding verification reads the
    raw HTML and rejects the app when it cannot find the app name, a description of
    its purpose, and readable privacy policy and terms pages.
--}}
@php($component = $page['component'] ?? null)

@if ($component === 'Welcome')
    <noscript>
        <h1>SantriQ</h1>
        <p>
            SantriQ is a free and open source school management platform for Indonesian
            Qur'an study centers (TPA/TPQ). It provides QR-code student attendance,
            real-time attendance notifications to parents via Telegram, learning
            progress records, tuition billing, and student leave requests.
        </p>
        <p>
            SantriQ adalah platform gratis dan open source untuk manajemen TPA/TPQ:
            absensi QR, notifikasi kehadiran ke wali santri via Telegram, pencatatan
            pencapaian, SPP, dan perizinan santri.
        </p>
        <p>
            <a href="{{ route('privacy') }}">Kebijakan Privasi</a> &middot;
            <a href="{{ route('terms') }}">Syarat &amp; Ketentuan</a>
        </p>
    </noscript>
@elseif ($component === 'Legal')
    @php($content = $page['props']['content'])
    <noscript>
        <h1>SantriQ &mdash; {{ $content['title'] }}</h1>
        <p>Terakhir diperbarui: {{ $content['updated_at'] }}</p>
        <p>{{ $content['description'] }}</p>

        @foreach ($content['sections'] as $section)
            <h2>{{ $section['title'] }}</h2>

            @foreach ($section['paragraphs'] ?? [] as $paragraph)
                <p>{{ $paragraph }}</p>
            @endforeach

            @if (! empty($section['items']))
                <ul>
                    @foreach ($section['items'] as $item)
                        <li>{{ $item }}</li>
                    @endforeach
                </ul>
            @endif
        @endforeach

        <p>
            <a href="{{ route('home') }}">Beranda</a> &middot;
            <a href="{{ route('privacy') }}">Kebijakan Privasi</a> &middot;
            <a href="{{ route('terms') }}">Syarat &amp; Ketentuan</a>
        </p>
    </noscript>
@endif
