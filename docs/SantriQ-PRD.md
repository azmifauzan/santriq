# Product Requirements Document (PRD)

# SantriQ

## 1. Ringkasan Produk

SantriQ adalah platform gratis, dan open source untuk manajemen TPA/TPQ, yang menyediakan sistem absensi otomatis berbasis pemindaian QR code, notifikasi kehadiran realtime ke wali santri melalui Telegram, serta pencatatan dan pelaporan pencapaian/prestasi santri yang dapat diakses oleh wali santri.

## 2. Latar Belakang & Tujuan

Lembaga TPA/TPQ pada umumnya masih mencatat kehadiran santri secara manual, sehingga wali santri tidak mendapatkan informasi kehadiran anak secara real-time. Pencatatan capaian belajar (bacaan, hafalan, dll.) juga sering tidak terdokumentasi secara digital sehingga sulit dipantau oleh wali santri.

**Tujuan produk:**

- Mengotomatiskan pencatatan kehadiran santri melalui pemindaian QR code.
- Mengirimkan notifikasi kehadiran secara real-time kepada wali santri melalui Telegram.
- Menyediakan riwayat pencapaian/prestasi santri yang transparan dan dapat diakses wali santri.
- Menjadi produk yang gratis, open source, dan dapat digunakan oleh banyak lembaga TPA/TPQ (multi-tenant).

## 3. Target Pengguna

| Peran           | Deskripsi                                                                     |
| --------------- | ----------------------------------------------------------------------------- |
| Admin Lembaga   | Pengurus TPA/TPQ; mengelola data santri dan pengaturan lembaga                |
| Pengajar/Ustadz | Mencatat pencapaian/prestasi santri                                           |
| Wali Santri     | Menerima notifikasi kehadiran & melihat laporan prestasi anaknya via Telegram |

## 4. Ruang Lingkup

### In-scope (V1)

- Halaman publik yang menjelaskan manfaat dan fitur SantriQ
- Registrasi & manajemen data lembaga (tenant)
- Manajemen data santri per lembaga
- Generate & cetak kode QR unik per santri
- Pemindaian QR untuk pencatatan kehadiran masuk & pulang menggunakan kamera perangkat (HP/tablet)
- Notifikasi otomatis ke wali santri melalui Telegram saat santri tercatat masuk/pulang
- Pencatatan pencapaian/prestasi santri oleh pengajar
- Laporan kehadiran & pencapaian yang dapat diakses wali santri
- Modul pembayaran/SPP
- Modul perizinan mandiri (sakit/izin)
- Tema terang dan gelap dengan pilihan awal mengikuti sistem pengguna

## 5. Functional Requirements

### 5.1 Manajemen Lembaga (Tenant)

- Registrasi lembaga baru dengan data profil lembaga
- Admin lembaga dapat menambahkan pengajar

### 5.2 Manajemen Santri

- CRUD data santri (nama, kelas/jenjang, data wali)
- Generate kode QR unik per santri
- Cetak kartu santri berisi QR

### 5.3 Absensi

- Pemindaian QR melalui kamera perangkat untuk mencatat waktu masuk
- Pemindaian QR kedua untuk mencatat waktu pulang
- Validasi agar satu QR tidak tercatat berulang dalam rentang waktu tertentu (mencegah duplikasi absen)

### 5.4 Notifikasi Wali Santri (Telegram)

- Wali santri menghubungkan akun Telegram ke sistem melalui kode unik/tautan bot
- Sistem mengirim pesan otomatis ke Telegram saat santri tercatat masuk dan saat tercatat pulang

### 5.5 Pencapaian/Prestasi Santri

- Pengajar mencatat pencapaian santri (bacaan, hafalan, penilaian) melalui form
- Riwayat pencapaian tersimpan per santri
- Wali santri dapat melihat riwayat pencapaian anaknya via Telegram/portal

### 5.6 Laporan

- Rekap kehadiran per santri per periode
- Rekap pencapaian per santri per periode
- Admin lembaga dapat melihat rekap seluruh santri di lembaganya

### 5.7 Modul Pembayaran/SPP

- Admin lembaga mencatat tagihan SPP per santri per periode
- Pencatatan status pembayaran (lunas/belum) per santri
- Wali santri dapat melihat status dan riwayat pembayaran anaknya
- Notifikasi Telegram ke wali santri saat tagihan baru terbit atau pembayaran terverifikasi

### 5.8 Modul Perizinan Mandiri

- Wali santri dapat mengajukan izin (sakit/izin) untuk anaknya melalui Telegram/portal
- Pengajuan izin berisi tanggal dan alasan
- Admin/pengajar dapat menyetujui atau menolak pengajuan izin
- Status kehadiran santri otomatis tercatat sesuai izin yang disetujui
- Notifikasi Telegram ke wali santri saat pengajuan izin disetujui/ditolak

### 5.9 Halaman Publik & Autentikasi

- Landing page menjelaskan manfaat, fitur utama, cara kerja, dan ajakan registrasi
- Halaman masuk dan registrasi memakai identitas visual yang konsisten dengan landing page
- Pengguna dapat mengganti tema terang/gelap dari landing page dan halaman autentikasi
- Tema awal mengikuti preferensi sistem dan pilihan pengguna disimpan di browser

## 6. Alur Pengguna Utama

**Alur 1 — Absen Santri**

1. Admin/pengajar membuka halaman pindai di perangkat
2. Santri menunjukkan kartu QR
3. Sistem mencatat waktu & status (masuk/pulang)
4. Sistem mengirim notifikasi ke Telegram wali santri

**Alur 2 — Pencatatan Prestasi**

1. Pengajar login dan memilih santri
2. Pengajar mengisi form pencapaian
3. Data tersimpan dan dapat dilihat wali santri

**Alur 3 — Wali Santri Memantau**

1. Wali santri membuka bot Telegram/portal
2. Wali santri melihat riwayat kehadiran dan pencapaian anaknya

**Alur 4 — Pembayaran SPP**

1. Admin lembaga menerbitkan tagihan SPP untuk santri
2. Wali santri menerima notifikasi tagihan via Telegram
3. Wali santri melakukan pembayaran (di luar/di dalam sistem sesuai mekanisme lembaga)
4. Admin memverifikasi pembayaran, status diperbarui, dan wali santri menerima notifikasi konfirmasi

**Alur 5 — Pengajuan Izin**

1. Wali santri mengajukan izin (sakit/izin) melalui Telegram/portal
2. Admin/pengajar meninjau dan menyetujui/menolak pengajuan
3. Status kehadiran santri tercatat sesuai keputusan
4. Wali santri menerima notifikasi hasil pengajuan via Telegram

**Alur 6 — Lembaga Baru Memulai**

1. Pengunjung mempelajari fitur melalui landing page
2. Pengunjung memilih daftar gratis
3. Pengunjung mendaftarkan lembaga dan akun admin pertama
4. Sistem mengarahkan admin ke dasbor lembaganya

## 7. Non-Functional Requirements

- Data setiap lembaga terisolasi dari lembaga lain (multi-tenant)
- Dapat diakses melalui browser tanpa instalasi aplikasi tambahan untuk proses absensi
- Waktu respons pemindaian QR harus cepat agar tidak menghambat antrean santri
- Mekanisme retry untuk pengiriman notifikasi Telegram bila gagal terkirim
- Halaman publik dan autentikasi responsif pada perangkat seluler maupun desktop
- Tema awal mengikuti `prefers-color-scheme` tanpa menghalangi pilihan manual pengguna

## 8. Model Distribusi

- Produk didistribusikan gratis dan open source
- Dapat digunakan oleh banyak lembaga TPA/TPQ (multi-tenant), baik melalui instance yang dikelola bersama maupun self-hosted oleh masing-masing lembaga

## 9. Metrik Keberhasilan

- Jumlah lembaga yang menggunakan SantriQ
- Jumlah santri terdaftar
- Tingkat keberhasilan pengiriman notifikasi Telegram
- Tingkat penggunaan berkelanjutan (retensi lembaga)

## 10. Risiko & Asumsi

- **Asumsi:** wali santri bersedia menggunakan Telegram sebagai satu-satunya kanal notifikasi
- **Risiko:** sebagian wali santri mungkin belum familiar/tidak menggunakan Telegram
- **Risiko:** keterbatasan perangkat atau koneksi internet di lokasi lembaga

## 11. Fase Pengembangan

- **Fase 1 (MVP):** manajemen lembaga & santri, absensi QR, notifikasi Telegram, pencatatan & laporan prestasi, modul pembayaran/SPP, modul perizinan mandiri
- **Fase 2:** modul tambahan sesuai kebutuhan lembaga, ditentukan berdasarkan masukan pengguna
