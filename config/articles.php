<?php

/*
|--------------------------------------------------------------------------
| Guide Articles
|--------------------------------------------------------------------------
|
| One entry per /panduan/{slug} page. Like config/features.php, the text is
| kept here so the client page, the server-rendered JSON-LD and the
| no-JavaScript fallback all read the same content. `feature` is the
| config/features.php slug the article links to; `template` is a key handled
| by App\Http\Controllers\ArticleController::template().
|
*/

return [
    'format-rapor-tpq' => [
        'title' => 'Format Rapor TPQ dan Contoh Catatan Akhlak Santri',
        'heading' => 'Format rapor TPQ: aspek penilaian dan contoh catatan akhlak santri',
        'nav' => 'Format rapor TPQ',
        'description' => 'Panduan menyusun rapor santri TPQ/TPA: aspek yang dinilai, skala nilai, contoh catatan akhlak, dan template Excel gratis yang bisa diunduh.',
        'published_at' => '2026-11-09',
        'feature' => 'rapor-santri',
        'template' => ['key' => 'rapor', 'label' => 'Unduh template rapor santri (Excel)'],
        'intro' => 'Rapor TPQ tidak harus rumit. Rapor yang baik menjawab tiga pertanyaan orang tua: sudah sampai mana bacaan anak saya, bagaimana kehadirannya, dan apa yang perlu dibantu di rumah. Panduan ini merangkum susunan rapor yang praktis, lengkap dengan template Excel dan contoh catatan akhlak.',
        'sections' => [
            [
                'title' => 'Bagian yang sebaiknya ada di rapor',
                'items' => [
                    'Identitas: nama santri, nomor induk, kelas atau jilid, periode penilaian, dan nama pengajar.',
                    'Capaian bacaan: jilid atau juz dan halaman terakhir yang dikuasai.',
                    'Hafalan: surat pendek, doa harian, dan bacaan salat yang sudah lancar.',
                    'Tajwid dan makharij: penilaian pada bacaan yang sedang dipelajari.',
                    'Praktik ibadah: wudu, salat, dan adab di majelis.',
                    'Kehadiran: jumlah hadir, izin, sakit, dan tanpa keterangan dalam periode.',
                    'Catatan pengajar dan kolom tanggapan wali.',
                ],
            ],
            [
                'title' => 'Skala nilai yang mudah dipahami wali',
                'paragraphs' => [
                    'Angka 0–100 sering membuat wali membandingkan anak satu dengan yang lain. Banyak TPQ memakai skala kualitatif yang lebih ramah: Sangat Baik, Baik, Cukup, dan Perlu Bimbingan. Apa pun pilihannya, tulis arti tiap tingkat di bagian bawah rapor dan pakai skala yang sama setiap periode supaya perkembangan terlihat.',
                ],
            ],
            [
                'title' => 'Contoh catatan akhlak santri',
                'paragraphs' => [
                    'Catatan akhlak paling berguna kalau menyebut perilaku nyata dan memberi arah, bukan sekadar pujian umum. Beberapa contoh yang bisa disesuaikan:',
                ],
                'items' => [
                    'Ananda rajin datang tepat waktu dan selalu menyalami ustaz sebelum masuk kelas. Pertahankan kebiasaan ini.',
                    'Ananda mulai berani membaca di depan teman. Mohon dampingi latihan membaca 10 menit setiap hari di rumah.',
                    'Ananda sering membantu teman yang kesulitan menemukan halaman bacaan. Sikap saling menolong ini patut dipertahankan.',
                    'Ananda masih mudah berbicara saat teman membaca. Kami sedang membiasakan menyimak sampai giliran selesai; mohon dukungan Bapak/Ibu.',
                    'Ananda menunjukkan kemajuan dalam kesabaran menunggu giliran. Teruskan dengan memberi pujian atas usahanya.',
                    'Ananda perlu dibiasakan merapikan sandal dan perlengkapan belajar sendiri. Kami akan mengingatkan di kelas, mohon dilanjutkan di rumah.',
                ],
            ],
            [
                'title' => 'Tips menyusun rapor tanpa menumpuk pekerjaan di akhir periode',
                'items' => [
                    'Catat capaian bacaan dan hafalan setiap pertemuan, jangan menunggu akhir periode.',
                    'Rekap kehadiran dari absensi harian, bukan dari ingatan.',
                    'Tulis catatan akhlak singkat untuk tiap santri setiap beberapa pekan, lalu pilih yang paling relevan saat menyusun rapor.',
                    'Simpan arsip rapor yang sudah dibagikan supaya bisa dibandingkan dengan periode berikutnya.',
                ],
            ],
            [
                'title' => 'Dengan SantriQ',
                'paragraphs' => [
                    'Kalau pencapaian dan kehadiran sudah dicatat di SantriQ, rapor per periode tersusun dari data itu: pilih rentang tanggal, periksa pratinjau, lalu terbitkan. Rapor yang sudah diterbitkan tersimpan sebagai arsip dan bisa dibuka wali dari portal wali.',
                ],
            ],
        ],
        'faq' => [
            ['q' => 'Berapa kali rapor TPQ dibagikan dalam setahun?', 'a' => 'Tidak ada aturan baku. Banyak TPQ membagikannya per semester atau per triwulan, mengikuti kalender lembaga.'],
            ['q' => 'Apakah rapor TPQ perlu nilai angka?', 'a' => 'Tidak wajib. Skala kualitatif seperti Sangat Baik, Baik, Cukup, dan Perlu Bimbingan sering lebih mudah dipahami wali.'],
            ['q' => 'Apakah template ini boleh diubah?', 'a' => 'Boleh. Template Excel ini bebas Anda sesuaikan dengan aspek penilaian lembaga.'],
        ],
    ],
    'buku-kas-spp-tpq' => [
        'title' => 'Contoh Buku Kas dan Pencatatan SPP TPQ di Excel',
        'heading' => 'Contoh buku kas dan cara mencatat SPP santri TPQ di Excel',
        'nav' => 'Buku kas SPP TPQ',
        'description' => 'Cara mencatat SPP santri dan membuat buku kas sederhana untuk TPQ/TPA, lengkap dengan template Excel gratis berisi buku kas dan rekap SPP 12 bulan.',
        'published_at' => '2026-11-09',
        'feature' => 'spp',
        'template' => ['key' => 'kas', 'label' => 'Unduh template buku kas dan rekap SPP (Excel)'],
        'intro' => 'Keuangan TPQ biasanya kecil, tetapi tetap perlu rapi: wali ingin tahu SPP-nya tercatat, pengurus ingin tahu uang masuk dan keluar. Dua catatan sudah cukup untuk memulai: buku kas yang mencatat semua transaksi, dan rekap SPP per santri per bulan.',
        'sections' => [
            [
                'title' => 'Buku kas: satu baris untuk satu transaksi',
                'paragraphs' => [
                    'Buku kas mencatat semua uang yang masuk dan keluar menurut urutan tanggal. Kolom yang dibutuhkan hanya enam:',
                ],
                'items' => [
                    'Tanggal transaksi.',
                    'Uraian, misalnya "SPP November", "Beli spidol", atau "Honor pengajar".',
                    'Nama santri, bila transaksinya SPP atau infak atas nama santri.',
                    'Pemasukan (debit).',
                    'Pengeluaran (kredit).',
                    'Saldo, yaitu saldo sebelumnya ditambah pemasukan dikurangi pengeluaran.',
                ],
            ],
            [
                'title' => 'Rekap SPP: siapa sudah bayar bulan apa',
                'paragraphs' => [
                    'Buku kas menjawab "berapa uang kita". Rekap SPP menjawab "siapa yang belum bayar". Buat tabel dengan nama santri di baris dan bulan Januari sampai Desember di kolom. Isi jumlah yang dibayar pada bulan yang bersangkutan, lalu jumlahkan per baris untuk tahu total pembayaran tiap santri dan per kolom untuk total penerimaan bulanan.',
                ],
            ],
            [
                'title' => 'Kebiasaan yang menjaga catatan tetap benar',
                'items' => [
                    'Catat transaksi di hari yang sama. Menunda satu minggu biasanya berarti ada yang lupa.',
                    'Berikan bukti pembayaran kepada wali, meski berupa kuitansi sederhana.',
                    'Cocokkan saldo buku kas dengan uang yang ada di tangan atau rekening setiap akhir bulan.',
                    'Pisahkan uang SPP dari uang pribadi pengurus.',
                    'Simpan satu berkas per tahun dan beri nama yang jelas, misalnya "kas-tpq-2026".',
                ],
            ],
            [
                'title' => 'Kapan Excel mulai kurang',
                'paragraphs' => [
                    'Excel cukup selama santri tidak banyak. Masalah biasanya muncul ketika tagihan harus dibuat untuk puluhan santri tiap bulan, wali bertanya status pembayaran lewat pesan pribadi, atau lebih dari satu pengurus mengisi berkas yang sama. Pada titik itu pencatatan terpusat lebih aman.',
                ],
            ],
            [
                'title' => 'Dengan SantriQ',
                'paragraphs' => [
                    'Di SantriQ, admin membuat tagihan untuk seluruh santri sekaligus, mencatat dan memverifikasi pembayaran, dan wali bisa melihat tagihan anaknya lewat bot Telegram dengan perintah /tagihan. Daftar tagihan dapat diekspor ke Excel untuk laporan keuangan.',
                ],
            ],
        ],
        'faq' => [
            ['q' => 'Apakah TPQ perlu laporan keuangan formal?', 'a' => 'Bagi TPQ kecil, buku kas dan rekap SPP yang rapi sudah cukup. Kebutuhan laporan yang lebih formal bergantung pada yayasan atau penyandang dana lembaga.'],
            ['q' => 'Bagaimana menghitung saldo di Excel?', 'a' => 'Pada template, saldo baris pertama adalah pemasukan dikurangi pengeluaran, dan baris berikutnya menambahkan hasil itu ke saldo di atasnya. Rumusnya sudah terisi di template.'],
            ['q' => 'Apakah template ini gratis?', 'a' => 'Ya, bebas diunduh dan diubah sesuai kebutuhan lembaga.'],
        ],
    ],
];
