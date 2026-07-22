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
| 7 — Rilis                | Sebagian: halaman publik selesai; tersisa backup, deploy, dan webhook produksi |

Verifikasi: `composer ci:check` hijau (ESLint, Prettier, vue-tsc, Pint, PHPStan level 7, Pest). `php artisan migrate:fresh --seed` berjalan bersih dan menghasilkan 1 lembaga demo, 2 akun, 2 kelas, 10 santri — semuanya punya `qr_token`, semua wali punya `link_token`.

Temuan yang sudah diperbaiki saat verifikasi:

- `bacon/bacon-qr-code` dipakai langsung oleh `QrCodeService` tetapi hanya ikut sebagai dependensi transitif Fortify — sekarang dideklarasikan eksplisit di `composer.json`.
- `SendTelegramMessage` membuat baris `telegram_messages` baru pada setiap percobaan ulang, dan `failed()` menandai gagal semua baris `pending` milik wali tersebut. Sekarang baris outbox dibuat sekali saat dispatch dan dipakai ulang oleh seluruh retry.
- Webhook Telegram bersifat publik dan dikecualikan dari CSRF, tetapi lolos begitu saja bila `TELEGRAM_SECRET_TOKEN` kosong — bot bisa dipakai mengirim pesan ke chat id sembarangan. Sekarang permintaan ditolak di luar environment `local`/`testing` bila secret kosong, perbandingan memakai `hash_equals`, dan endpoint dibatasi `throttle:120,1`.
- `TELEGRAM_BOT_TOKEN` dan `TELEGRAM_SECRET_TOKEN` belum ada di `.env.example`.
- Perizinan mandiri baru tersedia dari sisi staf; perintah `/izin` untuk wali santri di bot ditambahkan.
- `DatabaseSeeder` masih bawaan starter kit; diganti seeder lembaga demo. Catatan: seeder sengaja tidak memakai `WithoutModelEvents` karena `qr_token` dan `link_token` dibuat di hook `creating`.

Catatan lain: 2FA punya scaffolding (`TwoFactorAuthenticationRequest`, `RequirePassword` pada halaman keamanan) tetapi belum diaktifkan di `config/fortify.php` dan belum ada UI-nya. Akibatnya 4 test di `tests/Feature/Settings/SecurityTest.php` otomatis di-skip (`skipUnlessFortifyHas`) — inilah 4 skip yang muncul saat `composer test`.

## 1. Keputusan Arsitektur

Keputusan diambil sekali di depan supaya tidak diperdebatkan ulang tiap fase.

| Topik              | Keputusan                                                                                                         | Alasan                                                                                            |
| ------------------ | ----------------------------------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------- |
| Multi-tenancy      | Satu database, kolom `tenant_id` di setiap tabel milik lembaga + global scope Eloquent                            | Tanpa dependensi baru; cukup untuk skala TPA/TPQ. Database-per-tenant hanya jika terbukti perlu   |
| Peran pengguna     | Kolom `role` (enum: `admin`, `pengajar`) pada `users` + Policy Laravel                                            | Paket permission penuh belum dibutuhkan untuk dua peran                                           |
| Identitas wali     | Wali **bukan** user aplikasi; interaksi lewat Telegram + tautan publik bertanda tangan                            | Wali tidak perlu password; menurunkan beban dukungan                                              |
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
tenants          id, name, slug, address, phone, timezone, settings(json)
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

### Fase 7 — Rilis — belum

1. ~~Rate limit endpoint publik~~ — sudah: `throttle:60,1` pada scan, `throttle:120,1` pada webhook, `throttle:6,1` pada ganti password.
2. ~~Landing page publik, tampilan login/registrasi, dan tema terang/gelap responsif~~ — sudah.
3. Backup database terjadwal + retensi.
4. Deploy ke santriq.web.id: HTTPS (wajib untuk kamera), MySQL/PostgreSQL, queue worker sebagai daemon, scheduler cron.
5. Set webhook Telegram ke domain produksi berikut `TELEGRAM_SECRET_TOKEN` (perintah `setWebhook` ada di README).
6. ~~Seeder demo + dokumentasi self-hosting di README~~ — sudah.

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
