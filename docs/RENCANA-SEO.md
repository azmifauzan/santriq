# Rencana SEO SantriQ

Disusun 9 November 2026 dari data Google Search Console, GA4 (property SantriQ `562303194`), RankMySEO, dan pengecekan HTML live `santriq.web.id`.

Tujuan: menambah trafik organik non-merek dari pengelola TPA/TPQ dan mengubahnya menjadi pendaftaran lembaga baru.

## 1. Kondisi awal (baseline)

### Google Search Console (`sc-domain:santriq.web.id`, ±Agustus–November 2026)

| Metrik | Nilai |
|---|---|
| Klik | 9 |
| Impresi | 71 |
| CTR | 12,7% |
| Posisi rata-rata | 4,9 |
| Halaman terindeks | 4 |
| Tidak terindeks | 2 (1 "Blocked due to other 4xx", 1 "Page with redirect") |
| Sitemap | belum ada yang disubmit |
| Security issues | No issues detected |

- Hampir semua klik dari query merek. Query non-merek yang tercatat hanya "likny" dan "sq ?" (masing-masing 1 impresi).
- Halaman yang muncul: `/` (69 impresi), `/?ref=artifisial` (9 impresi, duplikat tanpa canonical), `demo.santriq.web.id` (2 impresi, domain lama).

### GA4 (data mulai ±4 November 2026, 28 hari terakhir)

| Channel | Sesi |
|---|---|
| Direct | 41 (80%) |
| Referral | 6 |
| AI Assistant | 2 |
| Organic Search | 2 |

- Key events: 0. Belum ada event konversi pendaftaran.
- 36% pengguna dari Amerika Serikat dengan waktu interaksi 3 detik, kemungkinan bot.
- Halaman aplikasi tenant (`/dashboard`, `/students`, `/guardians`, `/wali/portal`) tercatat di property yang sama dengan situs marketing, sehingga data marketing tercampur.

### RankMySEO (proyek Santriq)

- 8 keyword dilacak: `manajemen tpa`, `manajemen tpq`, `spp tpa`, `spp tpq`, `qr santri`, `wali santri`, `nomor induk siswa adalah`, `apa itu nomor induk siswa`. Semuanya di luar posisi 20.
- Domain authority 7, backlink 18, referring domain 3.
- Pesaing: `sipond.id` (posisi rata-rata 11), `e-santri.com`, `basmalah.web.id`.
- On-page score tidak tersedia: "Cloudflare blocked this review". Crawler RankMySEO mendapat halaman challenge Cloudflare (lihat langkah 1.7).

### Temuan teknis di situs

- `<title>` hanya "SantriQ".
- `/sitemap.xml` mengembalikan 404 dan `robots.txt` tidak mencantumkan sitemap.
- Tidak ada `<link rel="canonical">`.
- Tidak ada structured data (JSON-LD).
- Situs publik hanya punya satu halaman konten (`/`), plus `/privacy`, `/terms`, `/fitur`.
- Meta description dan Open Graph sudah ada. HTML ter-render di server (SSR aktif), jadi konten bisa dibaca crawler.

## 2. Fase 1: perbaikan teknis (minggu 1)

| # | Tugas | Lokasi | Selesai bila |
|---|---|---|---|
| 1.1 | Title deskriptif per halaman. Beranda: "SantriQ: Aplikasi Manajemen TPQ/TPA Gratis, Absensi QR & Notifikasi Wali" | `resources/views/app.blade.php`, `<Head>` di halaman Vue | `curl` beranda menampilkan title baru |
| 1.2 | `<link rel="canonical">` ke URL tanpa query string | `app.blade.php` | `/?ref=x` punya canonical ke `/` |
| 1.3 | `sitemap.xml` untuk halaman publik apex (bukan tenant) + baris `Sitemap:` di `public/robots.txt` | route di `routes/web.php` | `/sitemap.xml` 200, sudah disubmit di GSC |
| 1.4 | JSON-LD `SoftwareApplication` (gratis, `EducationalApplication`, bahasa id) dan `Organization` | `app.blade.php` | lolos Rich Results Test |
| 1.5 | `noindex` untuk halaman tenant `*.santriq.my.id` (login, dashboard, portal wali) dan tenant demo | middleware/header `X-Robots-Tag` atau meta | header terlihat di respons tenant |
| 1.6 | GA4: key event `sign_up` (registrasi lembaga) dan `onboarding_complete`; pisahkan traffic aplikasi tenant dari marketing (filter hostname apex, atau tag GA hanya di halaman publik) | tag GA (`GA_MEASUREMENT_ID`) | event muncul di Realtime |
| 1.7 | Jalankan ulang review RankMySEO. Bila masih diblokir, buat custom rule Cloudflare *Skip* untuk User-Agent crawler RankMySEO | RankMySEO, Cloudflare | on-page score muncul |
| 1.8 | Periksa dua halaman yang tidak terindeks di GSC (4xx dan redirect), lalu Validate Fix bila perlu | GSC | validasi berjalan |

Setiap perubahan kode disertai test feature (respons berisi title, canonical, sitemap, header noindex).

## 3. Fase 2: halaman fitur (minggu 2–4)

Satu halaman per klaster keyword, di domain apex. Masing-masing berisi H1 sesuai keyword utama, penjelasan masalah, cara kerja di SantriQ, tangkapan layar, FAQ (dengan JSON-LD `FAQPage`), dan CTA "Daftarkan lembaga gratis".

| URL | Keyword utama |
|---|---|
| `/fitur/absensi-qr` | aplikasi absensi tpq gratis, absensi santri pakai qr code |
| `/fitur/notifikasi-wali` | notifikasi kehadiran santri ke orang tua, laporan santri lewat telegram |
| `/fitur/spp` | aplikasi spp tpq gratis, cara mencatat spp santri |
| `/fitur/rapor-santri` | rapor santri tpq online, catatan perkembangan santri |
| `/fitur/perizinan` | izin santri online, surat izin santri tpq |
| `/fitur/kartu-santri` | kartu santri qr code |

Beranda menautkan ke semua halaman fitur, dan setiap halaman fitur menautkan balik serta ke artikel terkait.

## 4. Fase 3: konten rutin (mulai bulan 2, 1–2 artikel per minggu)

Format: panduan + template unduhan (Excel/PDF/Word). Hasil pencarian untuk topik ini sekarang didominasi PDF jurnal dan skripsi, belum ada halaman praktis yang kuat. Setiap artikel menautkan ke halaman fitur yang relevan.

Urutan prioritas:

1. Format rapor TPQ + contoh catatan akhlak santri (template unduhan)
2. Contoh buku kas dan pencatatan SPP TPQ di Excel (template unduhan)
3. Cara membuat kartu santri dengan QR code
4. Administrasi TPQ lengkap: daftar buku dan formulir yang wajib ada
5. Cara memantau kenaikan jilid dan hafalan santri
6. Contoh surat izin santri TPQ
7. Cara memberi kabar kehadiran santri ke orang tua
8. Perbandingan aplikasi manajemen TPQ gratis (jujur, termasuk pesaing)

## 5. Daftar long-tail keyword

Volume pencarian belum diukur. Validasi lewat Google Keyword Planner atau RankMySEO sebelum menetapkan prioritas akhir. Kolom "Peluang" menilai persaingan dari isi halaman 1 Google saat riset.

| Klaster | Keyword | Halaman target | Peluang |
|---|---|---|---|
| Absensi | aplikasi absensi tpq gratis | `/fitur/absensi-qr` | tinggi |
| | absensi santri pakai qr code | `/fitur/absensi-qr` | tinggi |
| | absensi tpa online | `/fitur/absensi-qr` | sedang |
| | cara absen santri tanpa buku | artikel | tinggi |
| Notifikasi wali | notifikasi kehadiran santri ke orang tua | `/fitur/notifikasi-wali` | tinggi |
| | laporan santri lewat telegram | `/fitur/notifikasi-wali` | tinggi |
| | aplikasi wali santri tpq | `/fitur/notifikasi-wali` | sedang |
| Rapor | format rapor tpq | artikel #1 | tinggi |
| | contoh rapor santri tpa | artikel #1 | tinggi |
| | contoh catatan akhlak santri di rapor | artikel #1 | sedang |
| | catatan perkembangan santri tpq | `/fitur/rapor-santri` | tinggi |
| SPP/keuangan | aplikasi spp tpq gratis | `/fitur/spp` | tinggi |
| | contoh buku kas tpq excel | artikel #2 | tinggi |
| | cara mencatat spp santri | artikel #2 | tinggi |
| | laporan keuangan tpq sederhana | artikel #2 | sedang |
| Kartu santri | cara membuat kartu santri qr code | artikel #3 | tinggi |
| | template kartu santri tpa | `/fitur/kartu-santri` | sedang |
| Administrasi | administrasi tpq lengkap | artikel #4 | sedang |
| | buku induk santri tpq | artikel #4 | tinggi |
| | aplikasi manajemen tpq gratis open source | beranda | tinggi |
| | surat izin santri tpq | artikel #6 | tinggi |
| Jilid/hafalan | kartu prestasi santri iqro | artikel #5 | tinggi |
| | catatan kenaikan jilid santri | artikel #5 | tinggi |
| | monitoring hafalan santri | artikel #5 | sedang |

Keyword yang dilacak di RankMySEO: ganti `nomor induk siswa adalah` dan `apa itu nomor induk siswa` (tidak relevan dengan produk) dengan `aplikasi absensi tpq gratis` dan `format rapor tpq`.

## 6. Fase 4: otoritas dan backlink (berjalan terus)

- README repo GitHub open source menautkan ke `santriq.web.id`.
- Daftarkan di direktori aplikasi gratis/open source dan direktori SaaS Indonesia.
- Hubungi forum dan asosiasi TPQ (FKDT, LPPTKA BKPRMI) dan grup pengelola TPQ.
- Kerja sama dengan kampus. Banyak skripsi dan pengabdian masyarakat membahas sistem informasi TPQ; tawarkan SantriQ sebagai alat PKM.
- Halaman testimoni atau studi kasus dari lembaga pengguna (dengan izin).
- Optimalkan sebutan di jawaban AI (GA4 sudah mencatat channel AI Assistant): halaman fitur dan FAQ yang jelas, lalu tambahkan prompt pemantauan di fitur AI visibility RankMySEO.

## 7. Pemantauan

| Metrik | Sumber | Frekuensi | Target 3 bulan | Target 6 bulan |
|---|---|---|---|---|
| Impresi query non-merek | GSC | bulanan | 500/bulan | 3.000/bulan |
| Klik organik | GSC | bulanan | 50/bulan | 300/bulan |
| Halaman terindeks | GSC | bulanan | 15 | 30 |
| Keyword di top 20 | RankMySEO | mingguan | 4 | 10 |
| Key event `sign_up` dari Organic Search | GA4 | bulanan | 3/bulan | 15/bulan |
| Referring domain | RankMySEO | bulanan | 10 | 25 |

Target di atas adalah perkiraan awal dari baseline yang sangat kecil. Tinjau ulang setelah data 3 bulan pertama masuk.

Tinjauan bulanan: catat angka di tabel ini, cari query baru di GSC dengan impresi tinggi dan CTR rendah, lalu perbarui title/meta atau buat artikel untuk query tersebut.

## 8. Sumber riset

- Jibble, aplikasi absensi offline: https://www.jibble.io/id/aplikasi-kehadiran-offline
- KantorKu, daftar aplikasi absensi gratis: https://kantorku.id/blog/aplikasi-absensi-online-gratis/
- Tirto, contoh raport santri: https://tirto.id/contoh-raport-santri-format-dan-penilaiannya-hnDf
- Metro TV, contoh catatan akhlak santri: https://www.metrotvnews.com/read/b1oCOp8w-momen-motivasi-para-santri-simak-15-contoh-catatan-akhlak-santri-di-rapor
- UNPAM, sistem laporan hasil belajar TPQ Al-Qaf: https://openjournal.unpam.ac.id/index.php/SNISIS/article/view/41819
- Knowbase, QR code di kartu santri: https://ejournal.uinbukittinggi.ac.id/index.php/ijokid/article/view/6018
- Repositori Yudharta, rekap kenaikan jilid TPQ dengan QR: https://repository.yudharta.ac.id/1600/1/201769040015_BAB%20%20I.pdf
- paud.id, buku kas umum: https://www.paud.id/buku-keuangan-kas-umum-paud/
- UINSGD, Excel untuk laporan keuangan TPQ An-Najah: https://journal.uinsgd.ac.id/index.php/al-khidmat/article/view/11637
- URINDO, pengelolaan keuangan TPQ Nurul Hikmah: https://ejournal.urindo.ac.id/index.php/PAMAS/article/view/1461
