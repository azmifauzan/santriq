{{--
    Public pages are client-rendered, so without this block a request that does not
    run JavaScript sees an empty body. Google's OAuth branding verification reads the
    raw HTML and rejects the app when it cannot find the app name, a description of
    its purpose, and readable privacy policy and terms pages.

    The sign-in pages carry the same fallback for a different reason: a credential
    form that looks like a blank page to a crawler cannot be attributed to anyone,
    which is one of the signals behind a "Deceptive pages" classification. Say
    plainly whose login this is and where the site's policies live.
--}}
@php($component = $page['component'] ?? null)
@php($authTitles = [
    'auth/Login' => 'Masuk ke SantriQ',
    'auth/Register' => 'Daftarkan lembaga Anda di SantriQ',
    'auth/ForgotPassword' => 'Atur ulang kata sandi SantriQ',
    'auth/ResetPassword' => 'Atur ulang kata sandi SantriQ',
    'auth/VerifyEmail' => 'Verifikasi email SantriQ',
    'auth/ConfirmPassword' => 'Konfirmasi kata sandi SantriQ',
])

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
@elseif (isset($authTitles[$component]))
    <noscript>
        <h1>SantriQ &mdash; {{ $authTitles[$component] }}</h1>
        <p>
            Halaman ini bagian dari SantriQ, platform gratis dan open source untuk
            manajemen TPA/TPQ. SantriQ hanya meminta kredensial akun SantriQ milik
            Anda sendiri, dan tidak pernah meminta data kartu, PIN, atau OTP
            perbankan.
        </p>
        <p>
            This page belongs to SantriQ, a free and open source school management
            platform for Indonesian Qur'an study centres. It only ever asks for your
            own SantriQ account credentials, never banking or payment details.
        </p>
        <p>
            <a href="{{ route('home') }}">Beranda SantriQ</a> &middot;
            <a href="{{ route('privacy') }}">Kebijakan Privasi</a> &middot;
            <a href="{{ route('terms') }}">Syarat &amp; Ketentuan</a> &middot;
            <a href="https://github.com/azmifauzan/santriq" rel="noopener">Kode sumber</a>
        </p>
    </noscript>
@endif
