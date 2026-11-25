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

@php($publicComponents = ['Welcome', 'Legal', 'Feature', 'Article', 'Guide', 'RequestFeature', 'ArticleIndex'])
@php($isPublicPage = in_array($component, $publicComponents, true))

@if ($isPublicPage)
    <noscript>
        <header>
            <nav aria-label="Navigasi utama">
                <a href="{{ route('home') }}">SantriQ</a> &middot;
                <a href="{{ route('request-feature') }}">Fitur</a> &middot;
                <a href="{{ route('articles.index') }}">Panduan</a> &middot;
                <a href="{{ $component === 'Welcome' ? '#tentang' : route('home').'#tentang' }}">Tentang</a> &middot;
                <a href="{{ route('request-feature') }}#request">Kontak</a> &middot;
                <a href="{{ route('login') }}">Masuk</a> &middot;
                <a href="{{ route('register') }}">Daftar gratis</a>
            </nav>
        </header>

        <main>
            @if ($component === 'Welcome')
                <h1>Absensi QR santri, kelola TPQ/TPA lebih mudah.</h1>
                <p>
                    SantriQ membantu pengelola TPA/TPQ mencatat kehadiran lewat pindai kartu QR
                    santri di kamera ponsel, mengirim notifikasi otomatis ke wali via Telegram,
                    serta mengelola rapor, SPP, dan perizinan tanpa biaya langganan.
                </p>
                <p>
                    <a href="{{ route('register') }}">Daftarkan lembaga gratis</a>
                    @if (! empty($page['props']['demoUrl']))
                        &middot; <a href="{{ $page['props']['demoUrl'] }}">Coba demo tanpa daftar</a>
                    @endif
                </p>

                <h2>Semua yang dibutuhkan untuk mengelola TPA/TPQ</h2>
                <p>
                    Kurangi pekerjaan administratif agar pengajar bisa lebih fokus mendampingi
                    tumbuh kembang santri. Setiap lembaga mendapatkan ruang kerja terpisah dengan
                    subdomain sendiri.
                </p>
                <ul>
                    <li>Absensi QR santri yang cepat: pindai kartu QR santri lewat kamera ponsel atau laptop tanpa alat tambahan untuk mencatat jam datang dan pulang.</li>
                    <li>Notifikasi langsung ke wali: orang tua menerima kabar kehadiran anak secara real-time melalui bot Telegram tanpa perlu memasang aplikasi tambahan.</li>
                    <li>Pencapaian dan rapor santri terdokumentasi: catat hafalan, bacaan, dan perkembangan belajar santri dalam satu riwayat lalu terbitkan rapor per periode.</li>
                    <li>SPP lebih tertib: buat tagihan massal untuk seluruh santri, catat verifikasi pembayaran, pantau status pelunasan, dan ekspor laporan ke Excel.</li>
                    <li>Perizinan mandiri lewat Telegram: wali dapat mengecek kehadiran, prestasi, tagihan, serta mengajukan izin atau sakit langsung dari bot atau portal wali.</li>
                    <li>Data lembaga terpisah dan aman: setiap TPA/TPQ memiliki ruang kerja terisolasi dengan pengaturan peran admin dan pengajar.</li>
                </ul>

                <h2>Siap digunakan dalam tiga langkah</h2>
                <ol>
                    <li>Daftarkan lembaga: buat akun gratis, pilih subdomain lembaga, dan lengkapi profil TPA/TPQ Anda.</li>
                    <li>Tambahkan santri: masukkan data santri, kelas, dan wali secara manual atau impor dari Excel lalu cetak kartu QR santri secara massal.</li>
                    <li>Mulai kelola: pindai kartu QR santri saat datang dan pulang, lalu pantau kehadiran, prestasi, dan SPP dari satu dasbor.</li>
                </ol>

                <section id="faq">
                    <h2>Pertanyaan seputar absensi QR santri</h2>

                    <h3>Apakah absensi QR santri butuh alat scanner khusus?</h3>
                    <p>
                        Tidak. Pengajar cukup membuka halaman pindai di browser Chrome atau Edge
                        pada ponsel maupun laptop yang memiliki kamera. Kartu QR santri diarahkan
                        ke kamera, lalu jam masuk dan jam pulang tercatat otomatis mengikuti zona
                        waktu lembaga.
                    </p>

                    <h3>Apakah SantriQ benar-benar gratis untuk TPA dan TPQ?</h3>
                    <p>
                        Ya. SantriQ gratis tanpa biaya langganan dan bersifat open source di GitHub.
                        Pengelola lembaga dapat langsung mendaftar, menambahkan data santri,
                        mencetak kartu QR, dan memakai semua fitur inti tanpa batas masa percobaan.
                    </p>

                    <h3>Apakah QR code di kartu santri bisa ditebak dari nomor urut?</h3>
                    <p>
                        Tidak. Setiap kartu santri memakai kode acak unik (ULID), bukan nomor induk
                        atau ID berurutan, sehingga tidak bisa ditiru dari kartu santri lain.
                        Sistem juga mencegah pemindaian ganda dalam rentang beberapa menit.
                    </p>

                    <h3>Apakah wali santri perlu memasang aplikasi SantriQ?</h3>
                    <p>
                        Tidak perlu. Wali cukup menghubungkan akun Telegram melalui tautan khusus
                        dari lembaga. Setelah terhubung, wali menerima notifikasi otomatis saat anak
                        datang atau pulang, serta bisa mengetik /kehadiran, /prestasi, /tagihan, dan
                        /izin di bot Telegram.
                    </p>

                    <h3>Bagaimana jika santri lupa membawa kartu QR saat mengaji?</h3>
                    <p>
                        Pengajar tetap dapat mencatat atau mengoreksi status kehadiran santri secara
                        manual dari halaman kehadiran di dasbor lembaga.
                    </p>

                    <h3>Apakah data lembaga kami terpisah dari TPA/TPQ lain?</h3>
                    <p>
                        Ya. Setiap lembaga memperoleh ruang kerja dan subdomain sendiri sehingga
                        data santri, pengajar, kelas, absensi, rapor, dan SPP terisolasi dari
                        lembaga lain. Data juga dapat diekspor ke Excel kapan saja.
                    </p>
                </section>

                <section id="tentang">
                    <h2>Tentang SantriQ</h2>
                    <p>
                        SantriQ dibangun dan dikelola oleh Fauzan Azmi sebagai platform gratis dan
                        open source agar lembaga pendidikan Al-Qur'an dari berbagai skala dapat
                        beralih ke pengelolaan digital tanpa beban biaya langganan. Kode sumber
                        terbuka dan dapat ditinjau di
                        <a href="https://github.com/azmifauzan/santriq" rel="noopener">GitHub</a>.
                    </p>
                    <p>
                        SantriQ is a free and open source school management platform for Indonesian
                        Qur'an study centers (TPA/TPQ). It provides QR-code student attendance,
                        real-time attendance notifications to parents via Telegram, learning
                        progress records, tuition billing, and student leave requests.
                    </p>
                </section>

                <section>
                    <h2>Saatnya administrasi lebih rapi dan wali lebih tenang</h2>
                    <p>
                        Bergabunglah dan mulai kelola absensi QR santri serta kegiatan lembaga Anda
                        bersama SantriQ secara gratis.
                    </p>
                    <p><a href="{{ route('register') }}">Daftar sekarang</a></p>
                </section>
            @elseif ($component === 'Legal')
                @php($content = $page['props']['content'])
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
            @elseif ($component === 'Feature')
                @php($feature = $page['props']['content'])
                <h1>{{ $feature['heading'] }}</h1>
                <p>{{ $feature['description'] }}</p>
                <p><a href="{{ route('register') }}">Daftarkan lembaga gratis</a></p>
                <h2>Masalahnya</h2>
                <p>{{ $feature['problem'] }}</p>
                <h2>Cara kerjanya</h2>
                <ol>
                    @foreach ($feature['steps'] as $step)
                        <li>{{ $step }}</li>
                    @endforeach
                </ol>
                <h2>Pertanyaan umum</h2>
                @foreach ($feature['faq'] as $item)
                    <h3>{{ $item['q'] }}</h3>
                    <p>{{ $item['a'] }}</p>
                @endforeach
                @if (! empty($page['props']['guides']))
                    <h2>Panduan terkait</h2>
                    <ul>
                        @foreach ($page['props']['guides'] as $guide)
                            <li><a href="{{ $guide['href'] }}">{{ $guide['title'] }}</a></li>
                        @endforeach
                    </ul>
                @endif
            @elseif ($component === 'Article')
                @php($article = $page['props']['content'])
                <h1>{{ $article['heading'] }}</h1>
                <p>{{ $article['intro'] }}</p>
                @foreach ($article['sections'] as $section)
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
                <h2>Pertanyaan umum</h2>
                @foreach ($article['faq'] as $item)
                    <h3>{{ $item['q'] }}</h3>
                    <p>{{ $item['a'] }}</p>
                @endforeach
                <p>
                    <a href="{{ $page['props']['templateHref'] }}">{{ $article['template']['label'] }}</a> &middot;
                    <a href="{{ $page['props']['featureLink']['href'] }}">{{ $page['props']['featureLink']['title'] }}</a> &middot;
                    <a href="{{ route('register') }}">Daftarkan lembaga gratis</a>
                </p>
            @elseif ($component === 'Guide')
                <h1>{{ $page['props']['content']['title'] }}</h1>
                <p>{{ $page['props']['content']['description'] }}</p>
                {{-- Rendered from a repo markdown file with html_input stripped (GuideController). --}}
                {!! $page['props']['html'] !!}
                <p>
                    <a href="{{ $page['props']['featureLink']['href'] }}">{{ $page['props']['featureLink']['title'] }}</a> &middot;
                    <a href="{{ route('register') }}">Daftarkan lembaga gratis</a>
                </p>
            @elseif ($component === 'RequestFeature')
                <h1>Semua yang dibutuhkan untuk mengelola TPA/TPQ</h1>
                <p>
                    Kurangi pekerjaan administratif agar pengajar bisa lebih fokus mendampingi
                    tumbuh kembang santri. SantriQ menyediakan fitur inti administrasi TPA dan TPQ
                    secara gratis dan open source.
                </p>
                <p><a href="{{ route('register') }}">Daftarkan lembaga gratis</a></p>

                <h2>Daftar fitur SantriQ</h2>
                @foreach (config('features') as $slug => $featureItem)
                    <h3><a href="{{ route('feature', $slug) }}">{{ $featureItem['title'] }}</a></h3>
                    <p>{{ $featureItem['description'] }}</p>
                    <p>{{ $featureItem['problem'] }}</p>
                @endforeach

                <section id="request">
                    <h2>Punya usulan fitur lain untuk SantriQ?</h2>
                    <p>
                        Kirim usulan, kritik, pertanyaan, atau ide fitur langsung ke pembuat dan
                        pengelola SantriQ (Fauzan Azmi) lewat salah satu kanal berikut. Semua
                        masukan dibaca dan menjadi bahan pertimbangan pengembangan berikutnya.
                    </p>
                    <ul>
                        <li><a href="{{ $page['props']['links']['threads'] ?? 'https://www.threads.com/@azmifauzan' }}" rel="noopener">Threads @azmifauzan</a></li>
                        <li><a href="{{ $page['props']['links']['facebook'] ?? 'https://www.facebook.com/azmifauzan/' }}" rel="noopener">Facebook Fauzan Azmi</a></li>
                        <li><a href="{{ $page['props']['links']['linkedin'] ?? 'https://www.linkedin.com/in/fauzan-azmi-29094522/' }}" rel="noopener">LinkedIn Fauzan Azmi</a></li>
                        <li><a href="https://github.com/azmifauzan/santriq" rel="noopener">Repositori GitHub SantriQ</a></li>
                    </ul>
                </section>
            @elseif ($component === 'ArticleIndex')
                <h1>Panduan administrasi TPQ dan TPA</h1>
                <p>
                    Panduan praktis dan template gratis untuk pengelola TPQ/TPA: rapor santri,
                    buku kas dan pencatatan SPP, absensi QR santri, serta komunikasi kehadiran
                    dengan wali santri melalui Telegram.
                </p>

                <h2>Daftar panduan dan template gratis</h2>
                <ul>
                    @foreach ($page['props']['articles'] as $item)
                        <li>
                            <h3><a href="{{ $item['href'] }}">{{ $item['title'] }}</a></h3>
                            @if (! empty($item['description']))
                                <p>{{ $item['description'] }}</p>
                            @endif
                        </li>
                    @endforeach
                </ul>

                <section>
                    <h2>Template siap pakai untuk pengurus TPA dan TPQ</h2>
                    <p>
                        Setiap panduan disusun dari kebutuhan harian pengelola lembaga pendidikan
                        Al-Qur'an, mulai dari menyusun rapor santri per periode, mencatat buku kas
                        dan rekap SPP 12 bulan di Excel, hingga menyiapkan koneksi jaringan agar
                        notifikasi kehadiran Telegram selalu sampai ke orang tua. Seluruh template
                        dapat diunduh dan diubah sesuai format lembaga tanpa perlu membuat akun.
                    </p>
                    <p>
                        Saat jumlah santri bertambah dan pencatatan di buku atau berkas Excel mulai
                        sulit dibagi antar pengajar, Anda dapat beralih ke SantriQ secara gratis.
                        Semua data kehadiran QR santri, rapor, tagihan SPP, dan perizinan tercatat
                        di satu dasbor lembaga.
                    </p>
                    <ul>
                        <li>Format rapor TPQ mencakup kolom identitas santri, jilid atau juz, hafalan surat pendek, praktik ibadah, rekap kehadiran, dan contoh kalimat catatan akhlak yang siap disesuaikan.</li>
                        <li>Template buku kas dan rekap SPP 12 bulan sudah dilengkapi rumus saldo otomatis serta penjumlahan per santri dan per bulan agar bendahara mudah memantau pelunasan.</li>
                        <li>Panduan jaringan dan Telegram membahas langkah praktis menjaga pengiriman notifikasi kehadiran santri tetap stabil di lokasi TPA maupun masjid.</li>
                    </ul>
                    <p><a href="{{ route('register') }}">Daftarkan lembaga gratis</a></p>
                </section>
            @endif
        </main>

        <footer>
            <p>SantriQ &mdash; Platform manajemen TPA/TPQ gratis dan open source oleh Fauzan Azmi.</p>
            <nav aria-label="Tautan situs dan legal">
                @foreach (config('features') as $slug => $featureItem)
                    <a href="{{ route('feature', $slug) }}">{{ $featureItem['nav'] }}</a> &middot;
                @endforeach
                <a href="{{ route('articles.index') }}">Panduan</a> &middot;
                <a href="{{ $component === 'Welcome' ? '#tentang' : route('home').'#tentang' }}">Tentang</a> &middot;
                <a href="{{ route('request-feature') }}#request">Kontak</a> &middot;
                <a href="{{ route('privacy') }}">Kebijakan Privasi</a> &middot;
                <a href="{{ route('terms') }}">Syarat &amp; Ketentuan</a>
            </nav>
        </footer>
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
