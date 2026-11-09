<?php

/*
|--------------------------------------------------------------------------
| Feature Landing Pages
|--------------------------------------------------------------------------
|
| One entry per /fitur/{slug} page, kept here (like config/legal.php) so the
| client-side page, the server-rendered description/FAQ JSON-LD, and the
| no-JavaScript fallback all read the same text. Keep every claim in step with
| what the app actually does.
|
*/

return [
    'absensi-qr' => [
        'title' => 'Aplikasi Absensi TPQ Gratis dengan QR Code',
        'heading' => 'Absensi santri pakai QR code, gratis untuk TPQ dan TPA',
        'nav' => 'Absensi QR',
        'description' => 'Catat kehadiran santri TPQ/TPA cukup dengan memindai kartu QR lewat browser. Tanpa buku absen, tanpa alat tambahan, dan gratis.',
        'problem' => 'Absen manual di buku memakan waktu, mudah salah catat, dan sulit direkap di akhir bulan. Pengajar baru tahu santri yang jarang hadir setelah menghitung satu per satu.',
        'steps' => [
            'Setiap santri mendapat kartu dengan QR code unik. Isinya kode acak, bukan nomor urut, sehingga tidak bisa ditebak.',
            'Pengajar membuka halaman pindai di browser ponsel atau laptop, lalu mengarahkan kamera ke kartu. Butuh koneksi HTTPS, yang sudah disediakan SantriQ.',
            'Waktu datang dan pulang tercatat otomatis memakai zona waktu lembaga. Status dapat dikoreksi pengajar bila ada salah pindai.',
            'Rekap kehadiran per santri atau per kelas siap dilihat dan diekspor ke Excel.',
        ],
        'faq' => [
            ['q' => 'Apakah perlu membeli alat pemindai?', 'a' => 'Tidak. SantriQ memakai kamera ponsel atau laptop melalui browser. Cukup buka halaman pindai dan izinkan akses kamera.'],
            ['q' => 'Apakah aplikasi absensi TPQ ini benar-benar gratis?', 'a' => 'Ya. SantriQ gratis dan open source.'],
            ['q' => 'Bagaimana kalau santri lupa membawa kartu?', 'a' => 'Pengajar dapat mengubah status kehadiran secara manual dari halaman kehadiran.'],
        ],
        'related' => ['notifikasi-wali', 'kartu-santri'],
    ],
    'notifikasi-wali' => [
        'title' => 'Notifikasi Kehadiran Santri ke Orang Tua via Telegram',
        'heading' => 'Kabari wali santri otomatis lewat Telegram',
        'nav' => 'Notifikasi wali',
        'description' => 'Begitu santri dipindai, wali menerima pesan Telegram berisi kehadiran anaknya. Wali juga bisa cek prestasi, tagihan, dan mengajukan izin lewat bot.',
        'problem' => 'Orang tua sering tidak tahu apakah anaknya sudah sampai di TPQ, dan pengajar harus mengirim kabar satu per satu lewat grup chat.',
        'steps' => [
            'Wali membuka tautan khusus dari lembaga lalu menghubungkan akun Telegram-nya. Wali tidak perlu membuat akun aplikasi atau kata sandi.',
            'Setiap kali anak dipindai datang atau pulang, bot mengirim pesan ke wali saat itu juga.',
            'Pesan dikirim lewat antrean dengan percobaan ulang, dan status pengirimannya tercatat.',
            'Wali dapat mengetik perintah /kehadiran, /prestasi, /tagihan, dan /izin di bot untuk melihat data anaknya kapan saja.',
        ],
        'faq' => [
            ['q' => 'Apakah wali perlu memasang aplikasi SantriQ?', 'a' => 'Tidak. Wali cukup memakai Telegram yang sudah ada di ponselnya.'],
            ['q' => 'Apakah bisa lewat WhatsApp?', 'a' => 'Saat ini notifikasi hanya lewat Telegram.'],
            ['q' => 'Data apa yang bisa dilihat wali?', 'a' => 'Kehadiran, pencapaian, status tagihan, dan rapor anaknya sendiri.'],
        ],
        'related' => ['absensi-qr', 'perizinan'],
    ],
    'spp' => [
        'title' => 'Aplikasi SPP TPQ Gratis: Tagihan dan Pembayaran Santri',
        'heading' => 'Catat SPP santri dengan rapi, gratis untuk TPQ dan TPA',
        'nav' => 'SPP',
        'description' => 'Buat tagihan SPP semua santri sekaligus, catat pembayaran, dan pantau status lunas per santri. Wali bisa cek tagihan lewat Telegram.',
        'problem' => 'SPP yang dicatat di buku kas atau lembar Excel terpisah mudah terlewat, dan sulit menjawab pertanyaan "bulan ini siapa yang belum bayar?".',
        'steps' => [
            'Admin membuat tagihan untuk satu santri atau sekaligus untuk seluruh santri.',
            'Pembayaran dicatat dan diverifikasi admin, sehingga status lunas atau belum selalu jelas.',
            'Wali bisa mengetik /tagihan di bot Telegram untuk melihat tagihan anaknya.',
            'Daftar tagihan dapat diekspor ke Excel untuk laporan keuangan lembaga.',
        ],
        'faq' => [
            ['q' => 'Apakah SantriQ memproses pembayaran online?', 'a' => 'Tidak. SantriQ mencatat dan memverifikasi pembayaran yang diterima lembaga, bukan payment gateway.'],
            ['q' => 'Bisakah membuat tagihan untuk semua santri sekaligus?', 'a' => 'Bisa. Admin dapat membuat tagihan massal dari halaman tagihan.'],
            ['q' => 'Apakah data bisa diekspor?', 'a' => 'Ya, daftar tagihan dapat diunduh sebagai file Excel.'],
        ],
        'related' => ['notifikasi-wali', 'rapor-santri'],
    ],
    'rapor-santri' => [
        'title' => 'Rapor Santri TPQ Online dan Catatan Perkembangan',
        'heading' => 'Rapor dan catatan perkembangan santri dalam satu riwayat',
        'nav' => 'Rapor santri',
        'description' => 'Catat hafalan, bacaan, dan perkembangan belajar santri, lalu terbitkan rapor per periode yang bisa dibuka wali dari ponsel.',
        'problem' => 'Catatan perkembangan santri tercecer di buku pribadi pengajar, sehingga rapor akhir periode harus disusun ulang dari ingatan.',
        'steps' => [
            'Pengajar mencatat pencapaian santri sepanjang periode: hafalan, bacaan, dan perkembangan lainnya.',
            'Pada akhir periode, pilih rentang tanggal dan lihat pratinjau rapor yang menggabungkan kehadiran dan pencapaian.',
            'Rapor yang diterbitkan tersimpan sebagai arsip dan tidak berubah meski data diedit kemudian.',
            'Wali membuka rapor anaknya dari portal wali.',
        ],
        'faq' => [
            ['q' => 'Apakah format rapor bisa diubah?', 'a' => 'Rapor memakai format bawaan SantriQ yang merangkum kehadiran dan pencapaian pada periode yang dipilih.'],
            ['q' => 'Apakah rapor lama ikut berubah kalau data diedit?', 'a' => 'Tidak. Rapor yang sudah diterbitkan disimpan sebagai arsip.'],
            ['q' => 'Siapa yang bisa melihat rapor?', 'a' => 'Staf lembaga, serta wali dari santri yang bersangkutan.'],
        ],
        'related' => ['absensi-qr', 'spp'],
    ],
    'perizinan' => [
        'title' => 'Izin Santri Online: Ajukan Izin atau Sakit lewat Telegram',
        'heading' => 'Izin santri online tanpa surat kertas',
        'nav' => 'Perizinan',
        'description' => 'Wali mengajukan izin atau sakit lewat bot Telegram, lembaga memprosesnya dari dasbor, dan tercatat rapi.',
        'problem' => 'Izin yang dititipkan lewat pesan pribadi atau surat mudah terlewat, dan sulit dilacak saat rekap kehadiran.',
        'steps' => [
            'Wali mengirim perintah /izin di bot: jenis (sakit atau izin), tanggal mulai, tanggal selesai, dan alasan. Bisa juga lewat portal wali.',
            'Pengajuan masuk ke daftar perizinan lembaga.',
            'Staf lembaga menyetujui atau menolak dari dasbor.',
            'Riwayat izin dapat diekspor ke Excel.',
        ],
        'faq' => [
            ['q' => 'Bagaimana kalau wali punya lebih dari satu anak?', 'a' => 'Wali mengawali perintah dengan NIS anak yang dimaksud.'],
            ['q' => 'Apakah wali perlu mendaftar akun?', 'a' => 'Tidak. Wali masuk lewat Telegram atau tautan khusus dari lembaga.'],
            ['q' => 'Apakah pengajuan bisa ditolak?', 'a' => 'Bisa. Setiap pengajuan disetujui atau ditolak oleh staf lembaga.'],
        ],
        'related' => ['notifikasi-wali', 'absensi-qr'],
    ],
    'kartu-santri' => [
        'title' => 'Kartu Santri QR Code: Cetak Otomatis dari SantriQ',
        'heading' => 'Cetak kartu santri ber-QR code langsung dari aplikasi',
        'nav' => 'Kartu santri',
        'description' => 'Setiap santri otomatis punya QR code unik. Cetak kartu semua santri sekaligus dan atur tampilannya sesuai lembaga.',
        'problem' => 'Membuat kartu santri satu per satu di aplikasi desain memakan waktu, dan nomor urut pada kartu mudah ditiru.',
        'steps' => [
            'Tambahkan santri satu per satu atau impor dari Excel. QR code dibuat otomatis.',
            'QR berisi kode acak, bukan nomor urut, sehingga tidak bisa ditebak dari kartu santri lain.',
            'Atur tampilan kartu di pengaturan cetak kartu, lalu cetak semua kartu sekaligus.',
            'Kartu langsung dipakai untuk absensi QR.',
        ],
        'faq' => [
            ['q' => 'Apakah bisa cetak kartu banyak santri sekaligus?', 'a' => 'Bisa. Halaman santri menyediakan cetak kartu massal.'],
            ['q' => 'Apakah QR bisa ditebak?', 'a' => 'QR berisi kode acak yang unik per santri, bukan nomor urut.'],
            ['q' => 'Apakah perlu mendesain kartu sendiri?', 'a' => 'Tidak. Kartu dibuat otomatis dari data santri.'],
        ],
        'related' => ['absensi-qr', 'rapor-santri'],
    ],
];
