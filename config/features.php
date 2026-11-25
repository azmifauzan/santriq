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
        'problem' => 'Absen manual di buku memakan waktu saat santri datang bersamaan, mudah salah catat, dan menyulitkan rekap di akhir bulan. Pengajar sering baru menyadari santri yang beberapa kali tidak hadir setelah menghitung catatan satu per satu, sementara orang tua juga tidak mendapat kabar apakah anaknya sudah tiba di TPQ.',
        'steps' => [
            'Setiap santri otomatis mendapat kartu dengan QR code unik berisi kode acak (ULID), bukan nomor urut, sehingga tidak bisa ditebak atau ditiru dari kartu santri lain.',
            'Pengajar membuka halaman pindai di browser Chrome atau Edge pada ponsel maupun laptop, lalu mengarahkan kamera ke kartu QR santri. Pemindaian memanfaatkan kamera perangkat lewat koneksi HTTPS yang sudah disediakan SantriQ.',
            'Pindai pertama mencatat jam datang dan pindai berikutnya mencatat jam pulang mengikuti zona waktu lembaga. Sistem juga mengabaikan scan ulang yang terlalu dekat untuk mencegah pencatatan ganda.',
            'Status kehadiran dapat dikoreksi secara manual oleh pengajar bila santri lupa membawa kartu atau terjadi salah pindai.',
            'Rekap kehadiran per santri maupun per kelas langsung tersedia di dasbor dan dapat diekspor ke berkas Excel untuk laporan bulanan lembaga.',
        ],
        'faq' => [
            ['q' => 'Apakah perlu membeli alat pemindai barcode atau mesin absen?', 'a' => 'Tidak. SantriQ memakai kamera ponsel atau laptop melalui browser. Cukup buka halaman pindai di browser yang mendukung kamera lalu izinkan akses kamera.'],
            ['q' => 'Apakah aplikasi absensi TPQ ini benar-benar gratis?', 'a' => 'Ya. SantriQ gratis tanpa biaya langganan dan bersifat open source, sehingga dapat dipakai lembaga kecil maupun besar.'],
            ['q' => 'Bagaimana kalau santri lupa membawa kartu QR?', 'a' => 'Pengajar tetap dapat mencatat atau mengubah status kehadiran santri secara manual dari halaman kehadiran di dasbor.'],
            ['q' => 'Bagaimana kalau kartu dipindai dua kali secara tidak sengaja?', 'a' => 'SantriQ memiliki jeda perlindungan pemindaian ganda (bawaan lima menit) sehingga scan berulang dalam waktu singkat tidak langsung tercatat sebagai jam pulang.'],
            ['q' => 'Apakah data kehadiran bisa diunduh untuk laporan?', 'a' => 'Ya. Admin dan pengajar dapat mengekspor rekap kehadiran ke format Excel langsung dari dasbor lembaga.'],
        ],
        'related' => ['notifikasi-wali', 'kartu-santri'],
    ],
    'notifikasi-wali' => [
        'title' => 'Notifikasi Kehadiran Santri ke Orang Tua via Telegram',
        'heading' => 'Kabari wali santri otomatis lewat Telegram',
        'nav' => 'Notifikasi wali',
        'description' => 'Begitu santri dipindai, wali menerima pesan Telegram berisi kehadiran anaknya. Wali juga bisa cek prestasi, tagihan, dan mengajukan izin lewat bot.',
        'problem' => 'Orang tua sering khawatir apakah anaknya sudah sampai di TPQ dengan selamat atau sudah pulang mengaji. Di sisi lain, pengajar tidak punya waktu mengirim kabar satu per satu ke grup chat atau pesan pribadi setiap sore, sehingga komunikasi dengan wali sering tertunda hingga pembagian rapor.',
        'steps' => [
            'Admin atau pengajar membagikan tautan penautan khusus kepada wali santri. Wali cukup membuka tautan tersebut di Telegram tanpa perlu membuat akun aplikasi atau mengingat kata sandi baru.',
            'Setiap kali kartu QR santri dipindai saat datang maupun pulang, bot SantriQ langsung mengirim pesan kehadiran ke akun Telegram wali sesuai waktu setempat lembaga.',
            'Pengiriman pesan diproses melalui antrean otomatis dengan mekanisme percobaan ulang bila jaringan sempat terganggu, dan status pengiriman tercatat di log lembaga.',
            'Wali dapat mengetik perintah /kehadiran, /prestasi, /tagihan, dan /izin langsung di bot Telegram, atau masuk ke portal web wali memakai tautan sekali pakai tanpa kata sandi.',
            'Satu akun Telegram wali dapat terhubung dengan lebih dari satu anak di lembaga yang sama, sehingga orang tua memantau semua anaknya dari satu obrolan.',
        ],
        'faq' => [
            ['q' => 'Apakah wali perlu memasang aplikasi SantriQ di ponselnya?', 'a' => 'Tidak. Wali cukup memakai aplikasi Telegram yang sudah ada di ponselnya dan menghubungkan akun lewat tautan dari lembaga.'],
            ['q' => 'Apakah notifikasi kehadiran bisa dikirim lewat WhatsApp?', 'a' => 'Saat ini notifikasi otomatis hanya berjalan melalui Bot API Telegram agar tetap gratis tanpa biaya pengiriman pesan per notifikasi.'],
            ['q' => 'Data apa saja yang bisa dilihat wali lewat Telegram?', 'a' => 'Wali hanya dapat melihat data anaknya sendiri, yaitu riwayat kehadiran, catatan pencapaian belajar, status tagihan SPP, dan rapor yang sudah diterbitkan.'],
            ['q' => 'Apakah wali juga bisa mengajukan izin lewat Telegram?', 'a' => 'Bisa. Wali cukup mengirim perintah /izin beserta jenis izin atau sakit, tanggal, dan alasan, lalu staf lembaga memprosesnya di dasbor.'],
            ['q' => 'Bagaimana jika pesan Telegram gagal terkirim?', 'a' => 'Sistem akan mencoba mengirim ulang secara otomatis melalui antrean dan mencatat status serta pesan kesalahannya agar admin dapat memeriksa penautan akun wali.'],
        ],
        'related' => ['absensi-qr', 'perizinan'],
    ],
    'spp' => [
        'title' => 'SPP TPQ dan TPA: Aplikasi Tagihan Santri Gratis',
        'heading' => 'Catat SPP santri dengan rapi, gratis untuk TPQ dan TPA',
        'nav' => 'SPP',
        'description' => 'Buat tagihan SPP TPQ atau TPA untuk semua santri sekaligus, catat pembayaran, dan lihat siapa yang belum lunas. Wali cek tagihan dengan /tagihan di Telegram.',
        'problem' => 'Pencatatan SPP di buku kas kertas atau lembar Excel terpisah mudah terlewat saat banyak wali membayar bersamaan di awal bulan. Bendahara sering kesulitan menjawab siapa saja yang belum melunasi bulan berjalan, sementara wali harus bertanya lewat pesan pribadi untuk memastikan pembayarannya sudah tercatat.',
        'steps' => [
            'Admin dapat membuat tagihan SPP untuk satu santri tertentu atau menerbitkan tagihan massal sekaligus untuk seluruh santri aktif pada periode bulan berjalan.',
            'Setiap pembayaran yang diterima lembaga dicatat dan diverifikasi oleh admin sehingga status lunas, belum lunas, atau jatuh tempo selalu jelas di daftar tagihan.',
            'Wali santri dapat mengecek rincian tagihan dan riwayat pembayaran anaknya kapan saja melalui perintah /tagihan di bot Telegram maupun dari portal wali.',
            'Admin dapat menyaring daftar tagihan berdasarkan status pelunasan atau periode untuk menindaklanjuti tunggakan tanpa harus membuka catatan manual satu per satu.',
            'Seluruh daftar tagihan dan status pembayaran dapat diekspor ke berkas Excel untuk arsip bendahara atau laporan keuangan bulanan lembaga.',
        ],
        'faq' => [
            ['q' => 'Apakah SantriQ memproses pembayaran online atau potongan transaksi?', 'a' => 'Tidak. SantriQ membantu mencatat dan memverifikasi pembayaran tunai maupun transfer yang diterima langsung oleh lembaga, bukan payment gateway, sehingga tidak ada potongan biaya transaksi.'],
            ['q' => 'Bisakah membuat tagihan SPP untuk semua santri sekaligus?', 'a' => 'Bisa. Admin cukup memilih fitur buat tagihan massal untuk menerbitkan tagihan periode baru bagi seluruh santri aktif sekaligus.'],
            ['q' => 'Apakah data tagihan SPP bisa diekspor ke Excel?', 'a' => 'Ya. Daftar tagihan beserta status pelunasannya dapat diunduh sebagai file Excel kapan saja.'],
            ['q' => 'Siapa yang berhak mengubah data SPP di dasbor?', 'a' => 'Pengelolaan dan verifikasi tagihan SPP dibatasi sesuai peran staf lembaga sehingga catatan keuangan tetap terkontrol dan terpisah antar lembaga.'],
            ['q' => 'Bagaimana wali mengetahui status pembayaran SPP anaknya?', 'a' => 'Wali cukup mengetik perintah /tagihan di bot Telegram yang sudah ditautkan atau membukanya melalui portal wali.'],
        ],
        'related' => ['notifikasi-wali', 'rapor-santri'],
    ],
    'rapor-santri' => [
        'title' => 'Rapor Santri TPQ Online dan Catatan Perkembangan',
        'heading' => 'Rapor dan catatan perkembangan santri dalam satu riwayat',
        'nav' => 'Rapor santri',
        'description' => 'Catat hafalan, bacaan, dan perkembangan santri TPQ, lalu terbitkan rapor per periode yang bisa dibuka wali dari ponsel. Ada juga template rapor Excel gratis.',
        'problem' => 'Catatan perkembangan bacaan Iqro, Al-Qur\'an, hafalan surat pendek, dan akhlak santri sering tercecer di buku pribadi masing-masing pengajar. Ketika akhir semester tiba, pengajar harus menyusun rapor ulang dari ingatan dan mengumpulkan rekap kehadiran secara manual dari buku absen.',
        'steps' => [
            'Pengajar mencatat pencapaian santri secara rutin sepanjang pertemuan, mencakup perkembangan bacaan, jilid atau juz, hafalan, serta catatan evaluasi belajar.',
            'Setiap catatan tersimpan dalam riwayat per santri sehingga pengajar pengganti maupun admin lembaga dapat langsung melihat capaian terakhir tanpa membuka buku fisik.',
            'Pada akhir periode penilaian, admin atau pengajar memilih rentang tanggal untuk melihat pratinjau rapor yang otomatis menggabungkan rekap kehadiran dan pencapaian.',
            'Setelah diperiksa, rapor diterbitkan dan disimpan sebagai arsip tetap sehingga isinya tidak berubah meskipun data pencapaian santri terus bertambah pada periode berikutnya.',
            'Wali santri dapat membuka rapor anaknya langsung dari ponsel melalui portal wali, dan pengajar juga bisa mengekspor riwayat pencapaian ke Excel.',
        ],
        'faq' => [
            ['q' => 'Apakah format rapor bisa diubah?', 'a' => 'Rapor memakai format bawaan SantriQ yang merangkum identitas santri, rekap kehadiran, dan seluruh catatan pencapaian pada rentang tanggal yang dipilih.'],
            ['q' => 'Apakah rapor lama ikut berubah kalau data baru ditambahkan?', 'a' => 'Tidak. Rapor yang sudah diterbitkan disimpan sebagai arsip snapshot tersendiri sehingga riwayat setiap periode tetap utuh.'],
            ['q' => 'Siapa saja yang bisa melihat rapor santri?', 'a' => 'Hanya staf lembaga yang berwenang serta wali dari santri yang bersangkutan melalui portal wali.'],
            ['q' => 'Apakah catatan pencapaian bisa diekspor ke Excel?', 'a' => 'Ya. Riwayat pencapaian santri dapat diunduh sebagai berkas Excel untuk arsip administrasi atau rapat evaluasi pengajar.'],
            ['q' => 'Apakah tersedia template rapor TPQ dalam format Excel?', 'a' => 'Tersedia. Anda dapat mengunduh template rapor TPQ gratis di halaman panduan format rapor TPQ tanpa perlu mendaftar.'],
        ],
        'related' => ['absensi-qr', 'spp'],
    ],
    'perizinan' => [
        'title' => 'Izin Santri Online: Ajukan Izin atau Sakit lewat Telegram',
        'heading' => 'Izin santri online tanpa surat kertas',
        'nav' => 'Perizinan',
        'description' => 'Wali mengajukan izin atau sakit lewat bot Telegram, lembaga memprosesnya dari dasbor, dan tercatat rapi.',
        'problem' => 'Pemberitahuan izin atau sakit yang dititipkan lewat pesan pribadi ke salah satu ustaz atau surat kertas sering terlewat saat pergantian jadwal mengajar. Akibatnya santri tetap tercatat tanpa keterangan pada buku absen dan rekap kehadiran akhir periode menjadi kurang akurat.',
        'steps' => [
            'Wali mengirim perintah /izin di bot Telegram dengan menuliskan jenis ketidakhadiran (sakit atau izin), tanggal mulai, tanggal selesai, dan alasan singkat. Pengajuan juga dapat dilakukan melalui formulir di portal web wali.',
            'Pengajuan izin langsung masuk ke daftar perizinan di dasbor lembaga sehingga semua staf yang bertugas dapat melihatnya tanpa harus meneruskan pesan chat secara manual.',
            'Admin atau pengajar meninjau rincian izin lalu memilih setujui atau tolak langsung dari dasbor lembaga.',
            'Status keputusan tercatat rapi bersama tanggal dan alasan izin, sehingga rekap kehadiran bulanan santri lebih akurat dan mudah ditelusuri oleh admin maupun pengajar kelas.',
            'Seluruh riwayat perizinan santri dapat disaring menurut status maupun periode serta diekspor ke berkas Excel kapan saja untuk keperluan administrasi lembaga.',
        ],
        'faq' => [
            ['q' => 'Bagaimana kalau wali memiliki lebih dari satu anak di TPQ?', 'a' => 'Wali cukup mengawali perintah /izin di Telegram dengan nomor induk (NIS) anak yang dimaksud, atau memilih nama anak langsung di portal wali.'],
            ['q' => 'Apakah wali perlu mendaftar akun baru untuk mengajukan izin?', 'a' => 'Tidak. Wali menggunakan akun Telegram yang sudah terhubung dengan lembaga atau masuk lewat tautan portal wali tanpa kata sandi.'],
            ['q' => 'Apakah pengajuan izin bisa ditolak oleh lembaga?', 'a' => 'Bisa. Setiap pengajuan berstatus menunggu hingga disetujui atau ditolak oleh staf lembaga yang berwenang.'],
            ['q' => 'Apakah wali mendapat kabar setelah izinnya diproses?', 'a' => 'Ya. Setelah staf menyetujui atau menolak pengajuan, bot Telegram mengirimkan pemberitahuan status izin kepada wali santri.'],
            ['q' => 'Apakah riwayat izin santri bisa diunduh?', 'a' => 'Ya. Admin dapat mengekspor daftar perizinan ke format Excel langsung dari halaman perizinan.'],
        ],
        'related' => ['notifikasi-wali', 'absensi-qr'],
    ],
    'kartu-santri' => [
        'title' => 'Kartu Santri QR Code: Cetak Massal dan Gratis',
        'heading' => 'Cetak kartu santri ber-QR code langsung dari aplikasi',
        'nav' => 'Kartu santri',
        'description' => 'Setiap santri otomatis punya QR code acak yang unik. Atur tampilan kartu santri sesuai lembaga, cetak semua kartu sekaligus, lalu pakai untuk absensi QR.',
        'problem' => 'Membuat kartu santri satu per satu di aplikasi desain memakan banyak waktu setiap kali tahun ajaran baru dimulai atau ada santri pindahan. Selain itu, kode QR yang dibuat dari nomor urut sederhana mudah ditebak dan tidak terhubung langsung dengan sistem pencatatan kehadiran.',
        'steps' => [
            'Tambahkan data santri satu per satu atau impor banyak santri sekaligus dari berkas Excel. Begitu data tersimpan, QR code unik setiap santri dibuat secara otomatis.',
            'Setiap QR code berisi kode acak (ULID) yang unik per santri, bukan nomor urut atau ID basis data, sehingga aman dan tidak dapat ditebak dari kartu milik santri lain.',
            'Sesuaikan identitas lembaga pada pengaturan cetak kartu, termasuk nama lembaga, warna tampilan, dan tata letak kartu sesuai kebutuhan cetak.',
            'Gunakan fitur cetak massal di halaman santri untuk menghasilkan tata letak siap cetak bagi seluruh santri sekaligus dalam satu proses.',
            'Setelah dicetak dan dilaminating atau dimasukkan ke tempat kartu, kartu QR santri dapat langsung dipindai untuk mencatat kehadiran datang dan pulang.',
        ],
        'faq' => [
            ['q' => 'Apakah bisa mencetak kartu untuk banyak santri sekaligus?', 'a' => 'Bisa. Halaman santri menyediakan fitur cetak kartu massal sehingga lembaga tidak perlu mengunduh atau menata kartu satu per satu.'],
            ['q' => 'Apakah kode QR pada kartu santri bisa ditebak?', 'a' => 'Tidak. QR berisi kode acak unik (ULID) per santri, bukan nomor urut berurutan, sehingga tidak bisa ditiru hanya dengan melihat kartu lain.'],
            ['q' => 'Apakah pengelola perlu mendesain kartu sendiri di aplikasi lain?', 'a' => 'Tidak perlu. Kartu santri beserta QR code SVG dibuat otomatis dari data santri dan pengaturan kartu lembaga di SantriQ.'],
            ['q' => 'Bisakah mengimpor daftar santri dari Excel sebelum mencetak kartu?', 'a' => 'Bisa. Admin dapat mengimpor data santri, kelas, dan wali dari file Excel, lalu langsung mencetak seluruh kartu QR santri.'],
            ['q' => 'Apakah kartu yang sudah dicetak langsung bisa dipakai untuk absensi?', 'a' => 'Ya. Kartu QR santri langsung terhubung dengan halaman pemindaian absensi di browser ponsel atau laptop pengajar.'],
        ],
        'related' => ['absensi-qr', 'rapor-santri'],
    ],
];
