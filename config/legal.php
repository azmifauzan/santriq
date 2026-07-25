<?php

/*
|--------------------------------------------------------------------------
| Legal Documents
|--------------------------------------------------------------------------
|
| The privacy policy and terms of service, kept here rather than inside the
| Vue page so the same text can be rendered both by the client-side page and
| by the server-side fallback for crawlers that do not execute JavaScript.
|
*/

return [
    'privacy' => [
        'title' => 'Kebijakan Privasi',
        'updated_at' => '23 Juli 2026',
        'description' => 'Kebijakan ini menjelaskan cara SantriQ mengumpulkan, menggunakan, dan melindungi data saat layanan digunakan.',
        'sections' => [
            [
                'title' => '1. Data yang kami kelola',
                'paragraphs' => [
                    'SantriQ mengelola data yang diberikan oleh lembaga, pengguna staf, dan wali santri untuk menjalankan layanan.',
                ],
                'items' => [
                    'Data akun, seperti nama, alamat email, lembaga, dan kata sandi yang telah di-hash.',
                    'Data lembaga, santri, kelas, wali, kehadiran, pencapaian, tagihan, pembayaran, dan pengajuan izin.',
                    'Data integrasi Telegram, seperti chat ID dan riwayat status pengiriman pesan.',
                    'Data teknis dasar, seperti alamat IP, waktu akses, dan catatan kesalahan untuk keamanan serta pemeliharaan layanan.',
                ],
            ],
            [
                'title' => '2. Cara data digunakan',
                'items' => [
                    'Menyediakan fitur administrasi, absensi QR, laporan, tagihan, dan portal wali.',
                    'Mengirim notifikasi dan menanggapi perintah wali melalui Telegram.',
                    'Mengamankan akun, mencegah penyalahgunaan, dan memperbaiki gangguan layanan.',
                    'Memenuhi kewajiban hukum yang berlaku.',
                ],
            ],
            [
                'title' => '3. Peran lembaga',
                'paragraphs' => [
                    'Lembaga yang menggunakan SantriQ bertanggung jawab memastikan data santri dan wali diperoleh serta digunakan dengan dasar yang sah. Lembaga juga menentukan siapa yang dapat mengakses data dalam ruang kerjanya.',
                ],
            ],
            [
                'title' => '4. Pembagian data',
                'paragraphs' => [
                    'Kami tidak menjual data pribadi. Data hanya dibagikan kepada penyedia layanan yang diperlukan untuk mengoperasikan SantriQ, seperti penyedia hosting dan Telegram, atau ketika diwajibkan oleh hukum. Penggunaan Telegram juga tunduk pada kebijakan privasi Telegram.',
                ],
            ],
            [
                'title' => '5. Penyimpanan dan keamanan',
                'paragraphs' => [
                    'Data disimpan selama akun lembaga aktif atau selama diperlukan untuk menyediakan layanan dan memenuhi kewajiban hukum. Kami menerapkan pembatasan akses, pemisahan data antar-lembaga, enkripsi koneksi, dan praktik keamanan yang wajar. Tidak ada sistem yang dapat menjamin keamanan mutlak.',
                ],
            ],
            [
                'title' => '6. Hak Anda',
                'paragraphs' => [
                    'Anda dapat meminta akses, koreksi, ekspor, atau penghapusan data melalui pengelola lembaga Anda. Permintaan yang berkaitan dengan akun atau layanan SantriQ dapat disampaikan melalui halaman GitHub resmi SantriQ.',
                ],
            ],
            [
                'title' => '7. Perubahan kebijakan',
                'paragraphs' => [
                    'Kebijakan ini dapat diperbarui untuk mencerminkan perubahan layanan atau hukum. Tanggal pembaruan terbaru akan dicantumkan pada halaman ini.',
                ],
            ],
        ],
    ],

    'terms' => [
        'title' => 'Syarat & Ketentuan',
        'updated_at' => '23 Juli 2026',
        'description' => 'Syarat ini mengatur penggunaan SantriQ oleh lembaga, pengguna staf, dan wali santri.',
        'sections' => [
            [
                'title' => '1. Penerimaan syarat',
                'paragraphs' => [
                    'Dengan membuat akun atau menggunakan SantriQ, Anda menyetujui syarat ini dan Kebijakan Privasi. Jika Anda mewakili lembaga, Anda menyatakan berwenang menerima syarat ini atas nama lembaga tersebut.',
                ],
            ],
            [
                'title' => '2. Penggunaan layanan',
                'paragraphs' => [
                    'SantriQ menyediakan alat untuk membantu pengelolaan TPA/TPQ. Lembaga tetap bertanggung jawab atas keputusan administratif, keakuratan data, dan komunikasi kepada santri serta wali.',
                ],
            ],
            [
                'title' => '3. Akun dan keamanan',
                'items' => [
                    'Berikan informasi yang benar dan perbarui bila terjadi perubahan.',
                    'Jaga kerahasiaan kredensial akun dan segera laporkan akses yang tidak sah.',
                    'Pastikan setiap pengguna hanya memperoleh akses yang sesuai dengan tugasnya.',
                    'Lembaga bertanggung jawab atas aktivitas yang dilakukan melalui akun dalam ruang kerjanya.',
                ],
            ],
            [
                'title' => '4. Penggunaan yang dilarang',
                'items' => [
                    'Menggunakan layanan untuk kegiatan melanggar hukum atau merugikan pihak lain.',
                    'Mengakses data lembaga lain atau mencoba melewati pembatasan keamanan.',
                    'Mengunggah kode berbahaya, mengganggu layanan, atau melakukan pengujian keamanan tanpa izin.',
                    'Mengirim pesan massal, penipuan, atau konten yang melanggar hak pihak lain.',
                ],
            ],
            [
                'title' => '5. Layanan pihak ketiga',
                'paragraphs' => [
                    'Fitur tertentu bergantung pada layanan pihak ketiga, termasuk Telegram dan penyedia infrastruktur. Ketersediaan serta penggunaan layanan tersebut tunduk pada ketentuan masing-masing penyedia.',
                ],
            ],
            [
                'title' => '6. Ketersediaan dan perubahan layanan',
                'paragraphs' => [
                    'SantriQ disediakan secara gratis dan open source. Kami dapat memperbarui, membatasi, atau menghentikan bagian layanan untuk keamanan, pemeliharaan, atau pengembangan. Kami berupaya menjaga layanan tetap tersedia, tetapi tidak menjamin layanan selalu bebas gangguan atau kesalahan.',
                ],
            ],
            [
                'title' => '7. Penghentian akses',
                'paragraphs' => [
                    'Akses dapat dibatasi atau dihentikan bila terjadi pelanggaran syarat, risiko keamanan, penyalahgunaan, atau kewajiban hukum. Lembaga dapat berhenti menggunakan layanan kapan saja.',
                ],
            ],
            [
                'title' => '8. Batasan tanggung jawab',
                'paragraphs' => [
                    'Sejauh diizinkan hukum, SantriQ tidak bertanggung jawab atas kerugian tidak langsung, kehilangan data akibat tindakan pengguna, atau gangguan dari layanan pihak ketiga. Ketentuan ini tidak membatasi hak yang tidak dapat dikesampingkan berdasarkan hukum Indonesia.',
                ],
            ],
            [
                'title' => '9. Perubahan syarat',
                'paragraphs' => [
                    'Syarat ini dapat diperbarui dari waktu ke waktu. Penggunaan layanan setelah perubahan berlaku berarti Anda menerima syarat yang diperbarui.',
                ],
            ],
        ],
    ],
];
