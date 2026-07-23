# SantriQ

Platform gratis dan open source untuk manajemen TPA/TPQ: absensi berbasis pemindaian QR code, notifikasi kehadiran realtime ke wali santri via Telegram, serta pencatatan pencapaian, pembayaran SPP, dan perizinan santri.

Situs: [santriq.web.id](https://santriq.web.id) (rencana)

## Fitur

- **Landing page per lembaga** — tiap lembaga punya subdomain sendiri (`{subdomain}.santriq.web.id`) dengan landing page publik yang kontennya (tagline, deskripsi, logo, galeri, jam operasional, warna aksen) bisa diubah admin lewat `settings/lembaga`.
- **Portal wali** — wali santri masuk tanpa password lewat tautan magic-link yang dikirim ke Telegram (`/wali/masuk`), lalu memantau kehadiran, pencapaian, dan mengajukan izin dari portal web (`/wali/portal`).
- **Multi-tenant** — satu instance melayani banyak lembaga, data antar lembaga terisolasi.
- **Manajemen santri** — data santri, kelas/jenjang, data wali, generate & cetak kartu QR.
- **Absensi QR** — pemindaian lewat kamera HP/tablet untuk mencatat jam masuk & pulang, dengan proteksi scan ganda.
- **Notifikasi Telegram** — wali santri otomatis menerima pesan saat anak masuk/pulang, tagihan terbit, atau izin diproses.
- **Pencapaian santri** — pengajar mencatat bacaan, hafalan, dan penilaian; wali dapat memantau riwayatnya.
- **Laporan** — rekap kehadiran dan pencapaian per santri maupun per lembaga.
- **SPP** — penerbitan tagihan per periode, verifikasi pembayaran, riwayat untuk wali.
- **Perizinan mandiri** — wali mengajukan izin/sakit, admin menyetujui, status kehadiran tercatat otomatis.

Semua fitur di atas sudah berjalan. Yang belum: wildcard DNS/TLS untuk `*.santriq.web.id` di produksi (jalan sementara lewat mode fallback path, lihat di bawah), backup terjadwal, pendaftaran webhook produksi, impor CSV santri, dan 2FA.

Detail kebutuhan produk ada di [docs/SantriQ-PRD.md](docs/SantriQ-PRD.md). Status per fase dan keputusan arsitektur ada di [docs/RENCANA-IMPLEMENTASI.md](docs/RENCANA-IMPLEMENTASI.md).

## Tech Stack

| Lapis    | Teknologi                                          |
| -------- | -------------------------------------------------- |
| Backend  | PHP 8.5, Laravel 13, Laravel Fortify (autentikasi) |
| Frontend | Inertia.js v3, Vue 3, TypeScript, Tailwind CSS v4  |
| Routing  | Laravel Wayfinder (route function bertipe)         |
| Build    | Vite                                               |
| Database | SQLite (lokal), MySQL/PostgreSQL (produksi)        |
| Testing  | Pest 4, PHPStan/Larastan level 7, Pint, ESLint     |

## Persyaratan

- PHP 8.3 atau lebih baru (CI memakai 8.5)
- Composer 2
- Node.js 22 atau lebih baru, dan npm
- SQLite (default) atau MySQL/PostgreSQL

## Instalasi

```bash
git clone https://github.com/<user>/santriq.git
cd santriq
composer setup
```

`composer setup` menjalankan `composer install`, menyalin `.env`, membuat app key, migrasi database, `npm install`, dan build aset.

Jalankan server pengembangan:

```bash
composer dev
```

Perintah tersebut menjalankan `artisan serve`, `queue:listen`, `pail` (log), dan Vite sekaligus dalam satu terminal.

Isi data demo (satu lembaga, dua akun, dua kelas, sepuluh santri beserta walinya):

```bash
php artisan db:seed
```

Akun demo: `admin@santriq.test` / `pengajar@santriq.test`, password `password`.

Registrasi lewat halaman `/register` (domain utama) membuat lembaga baru sekaligus akun admin pertamanya — pendaftar memilih subdomain sendiri (permanen), lalu diarahkan ke halaman masuk lembaganya (`{subdomain}.santriq.web.id/login`) setelah berhasil daftar.
Pilihan tema dapat diubah dari landing page, halaman masuk, dan halaman registrasi; preferensi disimpan di browser.

### Subdomain lembaga

```dotenv
APP_TENANT_DOMAIN=santriq.web.id       # domain akar; ganti sesuai environment
APP_TENANT_SUBDOMAIN_ACTIVE=true       # false = fallback tanpa wildcard DNS (lihat di bawah)
```

Tiap lembaga dilayani di `{subdomain}.{APP_TENANT_DOMAIN}` — landing page publik, halaman masuk staf, dashboard, dan portal wali semuanya di subdomain itu; domain utama (`APP_TENANT_DOMAIN` tanpa subdomain) hanya melayani landing marketing, `/register`, dan webhook Telegram.

Untuk pengembangan lokal, browser modern otomatis me-resolve `*.localhost` ke loopback tanpa perlu ubah `/etc/hosts` — set `APP_TENANT_DOMAIN=localhost` lalu akses lembaga lewat `http://{subdomain}.localhost:8000`.

Bila wildcard DNS/TLS untuk `*.{APP_TENANT_DOMAIN}` belum tersedia (mis. sebelum deploy produksi), set `APP_TENANT_SUBDOMAIN_ACTIVE=false`: seluruh rute lembaga tetap bisa diakses lewat `{APP_TENANT_DOMAIN}/{subdomain}/...` di domain utama saja, tanpa perlu subdomain. Ganti nilai ini butuh restart aplikasi dan `npm run build` ulang (bentuk rute dibakukan ke berkas JS oleh Wayfinder saat build).

## Konfigurasi Telegram

```dotenv
TELEGRAM_BOT_TOKEN=            # token dari @BotFather
TELEGRAM_SECRET_TOKEN=         # string acak, wajib diisi di produksi
```

Daftarkan webhook ke domain aplikasi:

```bash
curl -X POST "https://api.telegram.org/bot<TOKEN>/setWebhook" \
  -d "url=https://santriq.web.id/telegram/webhook" \
  -d "secret_token=<TELEGRAM_SECRET_TOKEN>"
```

`TELEGRAM_SECRET_TOKEN` wajib diisi di semua environment kecuali `testing`: webhook menolak seluruh permintaan bila kosong. Endpoint ini publik dan dikecualikan dari CSRF, dan pengembangan lokal pun biasanya menembus tunnel publik agar Telegram bisa menjangkaunya.

Wali santri menautkan akunnya dengan mengirim `/start <link_token>` ke bot, lalu dapat memakai `/kehadiran`, `/prestasi`, `/tagihan`, dan `/izin`.

Notifikasi dikirim lewat queue, jadi queue worker harus berjalan (`php artisan queue:work`). Setiap pesan dicatat di tabel `telegram_messages` beserta status dan error-nya.

## Catatan Pemindaian QR

Halaman `/scan` memakai `BarcodeDetector` bawaan browser, sehingga:

- perlu HTTPS (atau `localhost`) agar kamera bisa diakses;
- didukung penuh di Chrome/Edge Android dan desktop; browser tanpa `BarcodeDetector` akan menampilkan pesan agar berganti browser.

Scan pertama mencatat jam masuk, scan berikutnya mencatat jam pulang. Scan ulang dalam rentang `settings.dedup_minutes` (default 5 menit) diabaikan.

## Pengembangan

```bash
composer test          # config:clear + lint check + phpstan + pest
php artisan test --compact --filter=NamaTest   # jalankan satu test
composer lint          # Pint (format PHP)
composer types:check   # PHPStan level 7
npm run lint           # ESLint --fix
npm run format         # Prettier
npm run types:check    # vue-tsc
composer ci:check      # rangkaian yang dijalankan CI
```

CI (GitHub Actions) menjalankan `composer setup` lalu `composer ci:check` pada setiap push ke `main` dan setiap pull request.

## Struktur

```
app/Concerns/BelongsToTenant.php   Global scope + auto-isi tenant_id
app/Http/Controllers/              Controller domain (santri, absensi, SPP, dst.)
app/Http/Middleware/ResolveTenantFromDomain.php   Resolusi tenant dari subdomain/path
app/Support/CurrentTenant.php      Akses tenant hasil resolusi request saat ini
app/Policies/                      Otorisasi admin vs pengajar per model
app/Jobs/SendTelegramMessage.php   Pengiriman Telegram (queued, retry, outbox)
app/Services/QrCodeService.php     Render QR SVG untuk kartu santri
resources/js/pages/                Halaman Inertia (Vue)
resources/js/pages/guardian/       Halaman portal wali
resources/js/components/ui/        Komponen shadcn-vue
routes/web.php                     Route domain utama (marketing, register, webhook Telegram)
routes/tenant.php                  Route per lembaga (landing, staf, portal wali)
database/migrations/               Skema database
tests/Feature/                     Test fitur (Pest)
tests/PathFallback/                Test mode fallback tanpa wildcard DNS
docs/                              PRD, rencana implementasi, & spesifikasi desain
```

## Kontribusi

Proyek ini open source dan terbuka untuk kontribusi. Pastikan `composer ci:check` lolos sebelum membuka pull request.

## Lisensi

MIT.
