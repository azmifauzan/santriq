# Rencana Implementasi SantriQ

Dokumen kerja untuk mengubah [SantriQ-PRD.md](SantriQ-PRD.md) menjadi urutan pengerjaan, sekaligus catatan status pengerjaannya.

Domain produksi: **santriq.web.id**

## 0. Status (diverifikasi 22 Juli 2026)

| Fase                     | Status                                                                         |
| ------------------------ | ------------------------------------------------------------------------------ |
| 0 — Fondasi multi-tenant | Selesai                                                                        |
| 1 — Master data santri   | Selesai kecuali impor CSV (ditunda)                                            |
| 2 — Absensi QR           | Selesai                                                                        |
| 3 — Integrasi Telegram   | Selesai                                                                        |
| 4 — Pencapaian & laporan | Selesai                                                                        |
| 5 — SPP                  | Selesai                                                                        |
| 6 — Perizinan mandiri    | Selesai                                                                        |
| 7 — Rilis                | Sebagian: landing page & portal wali per subdomain, deploy produksi (santriq.web.id, wildcard TLS), dan webhook Telegram produksi selesai; tersisa backup terjadwal dan impor CSV |

Verifikasi: `composer ci:check` hijau (ESLint, Prettier, vue-tsc, Pint, PHPStan level 7, Pest). `php artisan migrate:fresh --seed` berjalan bersih dan menghasilkan 1 lembaga demo, 2 akun, 2 kelas, 10 santri — semuanya punya `qr_token`, semua wali punya `link_token`.
Fitur landed pada 23 Juli 2026: Subdomain per-lembaga (`{subdomain}.santriq.web.id`), landing page publik lembaga, setting profil landing page admin, magic-link Telegram login wali tanpa password dengan guard `guardian`, portal wali (status kehadiran, prestasi, pengajuan izin). Rencana detail di `docs/2026-07-23-landing-wali-login-design.md` & `docs/superpowers/plans/2026-07-23-landing-wali-login.md`.

Deploy produksi (23 Juli 2026): favicon diganti agar serupa mark landing page (emerald + graduation cap, ganti favicon.ico/svg/apple-touch-icon.png default Laravel), dan halaman error 403/404/419/429/500/503 kini dirender lewat `Inertia::handleExceptionsUsing` (`ErrorPage.vue`) alih-alih halaman default Laravel — aktif di semua environment kecuali `local`/`testing`. Prosedur redeploy lengkap di `docs/DEPLOY.md`.

Login & registrasi dengan Google (23 Juli 2026): tombol "Masuk/Daftar dengan Google" di atas form `auth/Login.vue`/`auth/Register.vue`, lewat `laravel/socialite`. `GoogleAuthController` (`auth/google/redirect`, `auth/google/callback`) selalu ada di domain utama karena `GOOGLE_REDIRECT_URI` tetap (satu redirect URI terdaftar di Google Cloud Console, tidak bisa per-subdomain) — konteks tenant/intent dibawa lewat `state` yang ditandatangani (`App\Support\GoogleOAuthToken`), bukan session, karena `SESSION_DOMAIN` kosong (cookie tidak lintas subdomain↔domain utama). Registrasi via Google tetap mengharuskan mengisi `institution_name`/`subdomain` (institusi baru butuh itu, Google cuma memberi identitas) — `CreateNewUser` menerima `google_token` sebagai pengganti password, dan **selalu memakai email/nama dari token, bukan dari form**, supaya token Google milik penyerang tidak bisa dipasangkan dengan email/nama korban. Login via Google mencocokkan berdasarkan email (unik global) + `tenant_id` cocok dengan subdomain saat ini; auto-link `google_id` ke akun password yang sudah ada hanya jika Google melaporkan `email_verified=true`. **Prasyarat operasional**: redirect URI produksi (`https://santriq.web.id/auth/google/callback`) harus terdaftar di Google Cloud Console sebelum fitur ini jalan di domain produksi — kalau belum, Google akan menolak dengan `redirect_uri_mismatch`.

Temuan yang sudah diperbaiki saat verifikasi:

- `bacon/bacon-qr-code` dipakai langsung oleh `QrCodeService` tetapi hanya ikut sebagai dependensi transitif Fortify — sekarang dideklarasikan eksplisit di `composer.json`.
- `SendTelegramMessage` membuat baris `telegram_messages` baru pada setiap percobaan ulang, dan `failed()` menandai gagal semua baris `pending` milik wali tersebut. Sekarang baris outbox dibuat sekali saat dispatch dan dipakai ulang oleh seluruh retry.
- Webhook Telegram bersifat publik dan dikecualikan dari CSRF, tetapi lolos begitu saja bila `TELEGRAM_SECRET_TOKEN` kosong — bot bisa dipakai mengirim pesan ke chat id sembarangan. Sekarang permintaan ditolak di semua environment kecuali `testing` bila secret kosong (termasuk `local`, karena Telegram hanya bisa menjangkau webhook lewat tunnel publik), perbandingan memakai `hash_equals`, dan endpoint dibatasi `throttle:120,1`.
- **IDOR lintas lembaga**: aturan `exists:students,id` dan sejenisnya bertanya langsung ke tabel sehingga melewati global scope tenant. Admin lembaga A bisa mengirim id milik lembaga B pada `students.store` (`classroom_id`, `guardian_ids`), `guardians.store` (`student_ids`), `achievements.store`, `invoices.store`, dan `leave-requests.store` — pengajuan izin yang disetujui bahkan menulis baris `attendances` untuk santri lembaga lain. Semua aturan tersebut kini memakai `App\Rules\TenantExists::in()`.
- `ReportController` tidak memanggil otorisasi sama sekali (datanya tetap ter-scope tenant, tapi tanpa gerbang policy); ditambahkan `Gate::authorize('viewAny', Attendance::class)` pada `index` dan `exportCsv`.
- `TELEGRAM_BOT_TOKEN` dan `TELEGRAM_SECRET_TOKEN` belum ada di `.env.example`.
- Perizinan mandiri baru tersedia dari sisi staf; perintah `/izin` untuk wali santri di bot ditambahkan.
- `DatabaseSeeder` masih bawaan starter kit; diganti seeder lembaga demo. Catatan: seeder sengaja tidak memakai `WithoutModelEvents` karena `qr_token` dan `link_token` dibuat di hook `creating`.
- Review lanjutan atas fitur subdomain (23 Juli 2026): redirect setelah daftar/masuk sebelumnya membangun URL subdomain dengan string manual — sekarang lewat `route()` supaya konsisten dengan mode fallback baru. `CurrentTenant::get()` melempar `RuntimeException` mentah (500) kalau tenant belum ter-resolve, misalnya saat rute wali diakses lewat domain utama tanpa subdomain — sekarang `abort(404)`. Ditambah mode fallback path (`{domain}/{subdomain}/...`) untuk dipakai sebelum wildcard DNS aktif, teruji di `tests/PathFallback/`.

Catatan lain: 2FA punya scaffolding (`TwoFactorAuthenticationRequest`, `RequirePassword` pada halaman keamanan) tetapi belum diaktifkan di `config/fortify.php` dan belum ada UI-nya. Akibatnya 4 test di `tests/Feature/Settings/SecurityTest.php` otomatis di-skip (`skipUnlessFortifyHas`) — inilah 4 skip yang muncul saat `composer test`.

## 1. Keputusan Arsitektur

Keputusan diambil sekali di depan supaya tidak diperdebatkan ulang tiap fase.

| Topik              | Keputusan                                                                                                         | Alasan                                                                                            |
| ------------------ | ----------------------------------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------- |
| Multi-tenancy      | Satu database, kolom `tenant_id` di setiap tabel milik lembaga + global scope Eloquent                            | Tanpa dependensi baru; cukup untuk skala TPA/TPQ. Database-per-tenant hanya jika terbukti perlu   |
| Peran pengguna     | Kolom `role` (enum: `admin`, `pengajar`) pada `users` + Policy Laravel                                            | Paket permission penuh belum dibutuhkan untuk dua peran                                           |
| Identitas wali     | Wali **bukan** user aplikasi dengan password, tapi punya guard `guardian` sendiri — login lewat magic-link Telegram, sesi persisten | Portal bisa dibuka berulang tanpa kembali ke bot tiap kali, tetap tanpa beban dukungan password |
| Lingkup subdomain | Setiap lembaga punya `{subdomain}.santriq.web.id`: landing publik, login staf, dashboard, dan portal wali semuanya di subdomain; domain utama murni marketing + registrasi | Branding konsisten per lembaga, dipilih eksplisit saat registrasi |
| Fallback tanpa wildcard DNS | `config('tenancy.subdomain_active')` (`APP_TENANT_SUBDOMAIN_ACTIVE`) memilih SATU bentuk rute saat boot: `{subdomain}.santriq.web.id/...` (default) atau `santriq.web.id/{subdomain}/...`. Rute `login`/`register`/reset password tetap di domain utama di kedua mode — `LoginResponse` mengarahkan ke dashboard lewat tenant milik user yang login, bukan lewat tenant hasil resolusi request | Wildcard DNS + TLS untuk `*.santriq.web.id` belum tersedia (lihat § 0); lembaga tetap bisa jalan dengan satu A record di domain utama sampai itu beres. Ganti mode butuh restart app + `npm run build` (Wayfinder membakukan bentuk rute ke JS saat build) |
| Login staf via Google | `laravel/socialite`, redirect/callback selalu di domain utama (redirect URI Google tetap, tidak bisa per-subdomain); state ditandatangani (`GoogleOAuthToken`) membawa intent+subdomain lintas domain karena session tidak ikut | `SESSION_DOMAIN` kosong → cookie tidak lintas subdomain↔domain utama; Socialite `stateless()` + state sendiri menghindari itu tanpa mengubah scope cookie aplikasi |
| Payload QR         | ULID acak per santri disimpan di kolom `qr_token` (unik), bukan ID berurutan                                      | Tidak bisa ditebak, bisa dicabut per santri tanpa mengubah ID                                     |
| Pemindai QR        | `BarcodeDetector` API bawaan browser, fallback pesan "gunakan Chrome/Android" bila tidak tersedia                 | Tanpa library JS tambahan; wajib HTTPS untuk akses kamera                                         |
| Generate gambar QR | `bacon/bacon-qr-code` (SVG, dirender di server oleh `QrCodeService`), dideklarasikan eksplisit di `composer.json` | Tidak ada cara wajar membuat QR tanpa library; jangan bergantung pada instalasi transitif Fortify |
| Telegram           | HTTP Client Laravel langsung ke Bot API, dibungkus queued job                                                     | SDK pihak ketiga tidak diperlukan untuk beberapa endpoint                                         |
| Retry notifikasi   | Queue `database` + `$tries`/`backoff` pada job, log status kirim di tabel `telegram_messages`                     | Memenuhi syarat retry di PRD tanpa infrastruktur tambahan                                         |
| Timezone           | Disimpan UTC, ditampilkan sesuai `tenants.timezone`                                                               | Lembaga tersebar di WIB/WITA/WIT                                                                  |
| Tema antarmuka     | Terang/gelap memakai class `dark`; default `prefers-color-scheme`, pilihan disimpan di browser                    | Mengikuti sistem tanpa dependensi atau pengaturan akun tambahan                                   |

## 2. Model Data

Semua tabel lembaga memakai `tenant_id` + index. Nama tabel Inggris, label UI Bahasa Indonesia.

```
tenants          id, name, subdomain, address, phone, timezone, settings(json)
```
Catatan: `settings.landing` menyimpan konten landing page per-lembaga (`tagline`, `description`, `logo_path`, `accent_color`, `operating_hours`, `gallery`).
users            + tenant_id (nullable untuk super admin), role
classrooms       tenant_id, name, level
students         tenant_id, classroom_id, nis, name, gender, birth_date, qr_token, status
guardians        tenant_id, name, phone, telegram_chat_id, link_token, linked_at
guardian_student guardian_id, student_id, relation
attendances      tenant_id, student_id, date, checked_in_at, checked_out_at,
                 status(hadir|izin|sakit|alpa), recorded_by
achievements     tenant_id, student_id, category, title, note, score, achieved_at, recorded_by
invoices         tenant_id, student_id, period, amount, due_date, status(unpaid|paid|void)
payments         invoice_id, amount, method, paid_at, verified_by, note
leave_requests   tenant_id, student_id, type(sakit|izin), start_date, end_date, reason,
                 status(pending|approved|rejected), reviewed_by, reviewed_at
telegram_messages tenant_id, guardian_id, payload, status, attempts, sent_at, error
```

Constraint penting:

- `students.qr_token` unik global.
- `attendances` unik pada (`student_id`, `date`) — satu baris per santri per hari.
- `invoices` unik pada (`student_id`, `period`).
- Foreign key `tenant_id` on delete cascade.

Semua constraint di atas sudah ada di migrasi, ditambah unik (`tenant_id`, `nis`) pada `students` dan unik (`guardian_id`, `student_id`) pada pivot.

## 3. Fase Pengerjaan

Tiap fase menghasilkan sesuatu yang bisa dipakai, dan ditutup dengan `composer ci:check` hijau. Status ringkas ada di bagian 0.

### Fase 0 — Fondasi Multi-Tenant — selesai

1. Migrasi `tenants`, tambah `tenant_id` + `role` pada `users`.
2. Trait `BelongsToTenant`: global scope filter `tenant_id`, auto-isi saat create.
3. Alur registrasi: pendaftar pertama membuat lembaga sekaligus jadi admin (sesuaikan `CreateNewUser`).
4. Halaman undang pengajar (admin menambah user pada lembaganya).
5. Policy dasar `admin` vs `pengajar`, dan navigasi sidebar per peran.
6. Factory + seeder demo satu lembaga.

**Test:** user lembaga A tidak bisa membaca/mengubah data lembaga B; pengajar ditolak pada aksi khusus admin.

### Fase 1 — Master Data Santri — selesai (kecuali impor CSV)

1. CRUD `classrooms`.
2. CRUD `students` (validasi NIS unik per lembaga), generate `qr_token` saat create.
3. CRUD `guardians` + relasi ke santri.
4. Import CSV santri (opsional, kerjakan bila ada permintaan).
5. Halaman cetak kartu QR: layout siap print (CSS `@media print`), beberapa kartu per halaman.

**Test:** create santri menghasilkan `qr_token` unik; halaman cetak menampilkan santri terpilih saja.

### Fase 2 — Absensi QR — selesai

1. Halaman `/scan`: akses kamera, `BarcodeDetector`, umpan balik suara/visual berhasil-gagal.
2. Endpoint `POST /attendance/scan` (auth, throttle) menerima `qr_token`, menentukan aksi masuk/pulang.
3. Aturan dedup: scan ulang dalam ≤ N menit (setting lembaga, default 5) diabaikan dan direspons "sudah tercatat".
4. Daftar kehadiran harian + koreksi manual oleh admin.

**Test:** scan pertama isi `checked_in_at`; scan kedua di luar jeda isi `checked_out_at`; scan dalam jeda tidak membuat baris baru; token tidak dikenal ditolak.

### Fase 3 — Integrasi Telegram — selesai

1. Config `services.telegram`, bot token lewat env.
2. Penautan wali: sistem membuat `link_token`, wali kirim `/start <token>` ke bot, webhook menyimpan `telegram_chat_id`.
3. Webhook `POST /telegram/webhook` diverifikasi dengan `secret_token` header dari Telegram.
4. Job `SendTelegramMessage` (queued, `$tries = 3`, backoff naik) + pencatatan di `telegram_messages`.
5. Event kehadiran memicu notifikasi masuk/pulang.
6. Perintah bot: `/kehadiran`, `/prestasi`, `/tagihan` menampilkan data anak dari wali tersebut.

**Test:** absensi mengantre satu job per wali; webhook tanpa secret ditolak; token tidak dikenal tidak menautkan apa pun.

### Fase 4 — Pencapaian & Laporan — selesai

1. CRUD `achievements` oleh pengajar (form: kategori, judul, catatan, nilai, tanggal).
2. Riwayat pencapaian per santri.
3. Rekap kehadiran per santri/periode dan rekap lembaga.
4. Rekap pencapaian per santri/periode.
5. Ekspor CSV rekap.

**Test:** rekap menghitung hadir/izin/sakit/alpa dengan benar untuk rentang tanggal.

### Fase 5 — SPP — selesai

1. CRUD `invoices`, penerbitan massal per kelas/periode.
2. Pencatatan `payments` + verifikasi admin, status tagihan mengikuti.
3. Notifikasi Telegram saat tagihan terbit dan saat pembayaran terverifikasi.
4. Riwayat tagihan untuk wali via bot/portal.

**Test:** verifikasi pembayaran mengubah status jadi `paid` dan mengirim satu notifikasi; tagihan ganda pada periode sama ditolak.

### Fase 6 — Perizinan Mandiri — selesai

1. Pengajuan izin dari wali lewat bot: `/izin [nis] <sakit|izin> <mulai> <selesai> [alasan]`. NIS hanya diperlukan bila satu wali punya lebih dari satu santri.
2. Antrean peninjauan untuk admin/pengajar: setujui/tolak.
3. Persetujuan menulis `attendances` berstatus `izin`/`sakit` pada rentang tanggal terkait.
4. Notifikasi hasil peninjauan ke wali.

**Test:** izin disetujui membuat baris kehadiran per hari dalam rentang; izin ditolak tidak mengubah kehadiran; perintah bot dengan tanggal salah tidak membuat pengajuan; wali dengan dua anak diminta menyebutkan NIS.

### Fase 7 — Rilis — sebagian

1. ~~Rate limit endpoint publik~~ — sudah: `throttle:60,1` pada scan, `throttle:120,1` pada webhook, `throttle:6,1` pada ganti password.
2. ~~Landing page publik, tampilan login/registrasi, dan tema terang/gelap responsif~~ — sudah.
3. Backup database terjadwal + retensi — belum.
4. ~~Deploy ke santriq.web.id~~ — sudah (23 Juli 2026): image `azmifauzan/santriq` (PHP 8.5-apache, multi-stage build) lewat Docker Compose di server produksi, wildcard TLS `*.santriq.web.id` (certbot DNS-01 via Cloudflare), queue worker `database` jalan sebagai daemon lewat `supervisord`. Detail & prosedur redeploy di `docs/DEPLOY.md`. Scheduler cron belum relevan karena belum ada `Schedule::` yang didaftarkan di `routes/console.php`.
5. ~~Set webhook Telegram ke domain produksi~~ — sudah, `TELEGRAM_SECRET_TOKEN` terpasang.
6. ~~Seeder demo + dokumentasi self-hosting di README~~ — sudah.
7. Impor CSV santri — belum (opsional, lihat Fase 1).

## 4. Konvensi Pengerjaan

- Setiap perubahan disertai test Pest; jalankan `php artisan test --compact --filter=...` untuk iterasi cepat.
- Route frontend memakai fungsi Wayfinder (`@/routes`, `@/actions`), bukan URL hardcode.
- Validasi di Form Request, otorisasi di Policy — bukan di controller.
- Query lintas tenant hanya lewat scope; jangan pernah `withoutGlobalScopes()` di jalur permintaan pengguna. Webhook Telegram berjalan tanpa sesi login sehingga scope tidak aktif — di sana `tenant_id` diambil dari data santri/wali yang bersangkutan, bukan dari user.
- Jangan menambah dependensi tanpa persetujuan (kecuali library QR yang sudah dicatat di atas).

## 5. Yang Sengaja Ditunda

| Ditunda                                                                                                             | Tambahkan bila                                                             |
| ------------------------------------------------------------------------------------------------------------------- | -------------------------------------------------------------------------- |
| Database per tenant                                                                                                 | Ada lembaga dengan kebutuhan isolasi/regulasi khusus                       |
| Paket roles & permissions                                                                                           | Peran bertambah melewati admin/pengajar                                    |
| Payment gateway                                                                                                     | Lembaga meminta pembayaran otomatis, bukan verifikasi manual               |
| Aplikasi mobile / PWA offline                                                                                       | Koneksi di lokasi terbukti jadi penghambat absensi                         |
| Kanal notifikasi selain Telegram (WA)                                                                               | Data menunjukkan banyak wali tidak memakai Telegram                        |
| SSR Inertia                                                                                                         | SEO halaman publik jadi kebutuhan nyata                                    |
| 2FA (scaffolding Fortify sudah ada, `Features::twoFactorAuthentication()` belum diaktifkan di `config/fortify.php`) | Akun admin lembaga memegang data banyak santri dan butuh proteksi tambahan |
