<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { GraduationCap } from '@lucide/vue';
import { computed } from 'vue';
import ThemeToggle from '@/components/ThemeToggle.vue';
import { home, privacy, terms } from '@/routes';

type LegalDocument = 'privacy' | 'terms';

type LegalSection = {
    title: string;
    paragraphs?: string[];
    items?: string[];
};

const props = defineProps<{ document: LegalDocument }>();

const documents: Record<
    LegalDocument,
    { title: string; description: string; sections: LegalSection[] }
> = {
    privacy: {
        title: 'Kebijakan Privasi',
        description:
            'Kebijakan ini menjelaskan cara SantriQ mengumpulkan, menggunakan, dan melindungi data saat layanan digunakan.',
        sections: [
            {
                title: '1. Data yang kami kelola',
                paragraphs: [
                    'SantriQ mengelola data yang diberikan oleh lembaga, pengguna staf, dan wali santri untuk menjalankan layanan.',
                ],
                items: [
                    'Data akun, seperti nama, alamat email, lembaga, dan kata sandi yang telah di-hash.',
                    'Data lembaga, santri, kelas, wali, kehadiran, pencapaian, tagihan, pembayaran, dan pengajuan izin.',
                    'Data integrasi Telegram, seperti chat ID dan riwayat status pengiriman pesan.',
                    'Data teknis dasar, seperti alamat IP, waktu akses, dan catatan kesalahan untuk keamanan serta pemeliharaan layanan.',
                ],
            },
            {
                title: '2. Cara data digunakan',
                items: [
                    'Menyediakan fitur administrasi, absensi QR, laporan, tagihan, dan portal wali.',
                    'Mengirim notifikasi dan menanggapi perintah wali melalui Telegram.',
                    'Mengamankan akun, mencegah penyalahgunaan, dan memperbaiki gangguan layanan.',
                    'Memenuhi kewajiban hukum yang berlaku.',
                ],
            },
            {
                title: '3. Peran lembaga',
                paragraphs: [
                    'Lembaga yang menggunakan SantriQ bertanggung jawab memastikan data santri dan wali diperoleh serta digunakan dengan dasar yang sah. Lembaga juga menentukan siapa yang dapat mengakses data dalam ruang kerjanya.',
                ],
            },
            {
                title: '4. Pembagian data',
                paragraphs: [
                    'Kami tidak menjual data pribadi. Data hanya dibagikan kepada penyedia layanan yang diperlukan untuk mengoperasikan SantriQ, seperti penyedia hosting dan Telegram, atau ketika diwajibkan oleh hukum. Penggunaan Telegram juga tunduk pada kebijakan privasi Telegram.',
                ],
            },
            {
                title: '5. Penyimpanan dan keamanan',
                paragraphs: [
                    'Data disimpan selama akun lembaga aktif atau selama diperlukan untuk menyediakan layanan dan memenuhi kewajiban hukum. Kami menerapkan pembatasan akses, pemisahan data antar-lembaga, enkripsi koneksi, dan praktik keamanan yang wajar. Tidak ada sistem yang dapat menjamin keamanan mutlak.',
                ],
            },
            {
                title: '6. Hak Anda',
                paragraphs: [
                    'Anda dapat meminta akses, koreksi, ekspor, atau penghapusan data melalui pengelola lembaga Anda. Permintaan yang berkaitan dengan akun atau layanan SantriQ dapat disampaikan melalui halaman GitHub resmi SantriQ.',
                ],
            },
            {
                title: '7. Perubahan kebijakan',
                paragraphs: [
                    'Kebijakan ini dapat diperbarui untuk mencerminkan perubahan layanan atau hukum. Tanggal pembaruan terbaru akan dicantumkan pada halaman ini.',
                ],
            },
        ],
    },
    terms: {
        title: 'Syarat & Ketentuan',
        description:
            'Syarat ini mengatur penggunaan SantriQ oleh lembaga, pengguna staf, dan wali santri.',
        sections: [
            {
                title: '1. Penerimaan syarat',
                paragraphs: [
                    'Dengan membuat akun atau menggunakan SantriQ, Anda menyetujui syarat ini dan Kebijakan Privasi. Jika Anda mewakili lembaga, Anda menyatakan berwenang menerima syarat ini atas nama lembaga tersebut.',
                ],
            },
            {
                title: '2. Penggunaan layanan',
                paragraphs: [
                    'SantriQ menyediakan alat untuk membantu pengelolaan TPA/TPQ. Lembaga tetap bertanggung jawab atas keputusan administratif, keakuratan data, dan komunikasi kepada santri serta wali.',
                ],
            },
            {
                title: '3. Akun dan keamanan',
                items: [
                    'Berikan informasi yang benar dan perbarui bila terjadi perubahan.',
                    'Jaga kerahasiaan kredensial akun dan segera laporkan akses yang tidak sah.',
                    'Pastikan setiap pengguna hanya memperoleh akses yang sesuai dengan tugasnya.',
                    'Lembaga bertanggung jawab atas aktivitas yang dilakukan melalui akun dalam ruang kerjanya.',
                ],
            },
            {
                title: '4. Penggunaan yang dilarang',
                items: [
                    'Menggunakan layanan untuk kegiatan melanggar hukum atau merugikan pihak lain.',
                    'Mengakses data lembaga lain atau mencoba melewati pembatasan keamanan.',
                    'Mengunggah kode berbahaya, mengganggu layanan, atau melakukan pengujian keamanan tanpa izin.',
                    'Mengirim pesan massal, penipuan, atau konten yang melanggar hak pihak lain.',
                ],
            },
            {
                title: '5. Layanan pihak ketiga',
                paragraphs: [
                    'Fitur tertentu bergantung pada layanan pihak ketiga, termasuk Telegram dan penyedia infrastruktur. Ketersediaan serta penggunaan layanan tersebut tunduk pada ketentuan masing-masing penyedia.',
                ],
            },
            {
                title: '6. Ketersediaan dan perubahan layanan',
                paragraphs: [
                    'SantriQ disediakan secara gratis dan open source. Kami dapat memperbarui, membatasi, atau menghentikan bagian layanan untuk keamanan, pemeliharaan, atau pengembangan. Kami berupaya menjaga layanan tetap tersedia, tetapi tidak menjamin layanan selalu bebas gangguan atau kesalahan.',
                ],
            },
            {
                title: '7. Penghentian akses',
                paragraphs: [
                    'Akses dapat dibatasi atau dihentikan bila terjadi pelanggaran syarat, risiko keamanan, penyalahgunaan, atau kewajiban hukum. Lembaga dapat berhenti menggunakan layanan kapan saja.',
                ],
            },
            {
                title: '8. Batasan tanggung jawab',
                paragraphs: [
                    'Sejauh diizinkan hukum, SantriQ tidak bertanggung jawab atas kerugian tidak langsung, kehilangan data akibat tindakan pengguna, atau gangguan dari layanan pihak ketiga. Ketentuan ini tidak membatasi hak yang tidak dapat dikesampingkan berdasarkan hukum Indonesia.',
                ],
            },
            {
                title: '9. Perubahan syarat',
                paragraphs: [
                    'Syarat ini dapat diperbarui dari waktu ke waktu. Penggunaan layanan setelah perubahan berlaku berarti Anda menerima syarat yang diperbarui.',
                ],
            },
        ],
    },
};

const selectedDocument = computed(() => documents[props.document]);
</script>

<template>
    <div
        class="min-h-screen bg-[#fbfdf9] text-slate-950 dark:bg-slate-950 dark:text-white"
    >
        <Head :title="selectedDocument.title">
            <meta
                head-key="description"
                name="description"
                :content="selectedDocument.description"
            />
        </Head>

        <header
            class="border-b border-emerald-950/5 bg-[#fbfdf9]/85 backdrop-blur-xl dark:border-white/10 dark:bg-slate-950/85"
        >
            <nav
                aria-label="Navigasi legal"
                class="mx-auto flex h-18 max-w-5xl items-center justify-between px-5 sm:px-8"
            >
                <Link :href="home()" class="flex items-center gap-2.5">
                    <span
                        class="flex size-9 items-center justify-center rounded-xl bg-emerald-600 text-white shadow-sm shadow-emerald-900/20"
                    >
                        <GraduationCap class="size-5" aria-hidden="true" />
                    </span>
                    <span class="text-xl font-bold tracking-tight">
                        Santri<span class="text-emerald-600">Q</span>
                    </span>
                </Link>
                <ThemeToggle />
            </nav>
        </header>

        <main class="mx-auto max-w-3xl px-5 py-14 sm:px-8 sm:py-20">
            <p
                class="text-sm font-semibold text-emerald-700 dark:text-emerald-400"
            >
                Terakhir diperbarui: 23 Juli 2026
            </p>
            <h1 class="mt-3 text-4xl font-bold tracking-tight sm:text-5xl">
                {{ selectedDocument.title }}
            </h1>
            <p
                class="mt-5 text-lg leading-8 text-slate-600 dark:text-slate-300"
            >
                {{ selectedDocument.description }}
            </p>

            <div class="mt-12 flex flex-col gap-10">
                <section
                    v-for="section in selectedDocument.sections"
                    :key="section.title"
                    class="flex flex-col gap-4"
                >
                    <h2 class="text-xl font-bold tracking-tight">
                        {{ section.title }}
                    </h2>
                    <p
                        v-for="paragraph in section.paragraphs"
                        :key="paragraph"
                        class="leading-7 text-slate-600 dark:text-slate-300"
                    >
                        {{ paragraph }}
                    </p>
                    <ul
                        v-if="section.items"
                        class="list-disc space-y-2 pl-5 leading-7 text-slate-600 marker:text-emerald-600 dark:text-slate-300"
                    >
                        <li v-for="item in section.items" :key="item">
                            {{ item }}
                        </li>
                    </ul>
                </section>
            </div>
        </main>

        <footer class="border-t border-emerald-950/8 dark:border-white/10">
            <nav
                aria-label="Tautan legal"
                class="mx-auto flex max-w-5xl flex-wrap gap-x-6 gap-y-3 px-5 py-8 text-sm text-slate-500 sm:px-8 dark:text-slate-400"
            >
                <Link
                    :href="home()"
                    class="transition hover:text-emerald-700 dark:hover:text-emerald-400"
                >
                    Beranda
                </Link>
                <Link
                    :href="privacy()"
                    class="transition hover:text-emerald-700 dark:hover:text-emerald-400"
                >
                    Kebijakan Privasi
                </Link>
                <Link
                    :href="terms()"
                    class="transition hover:text-emerald-700 dark:hover:text-emerald-400"
                >
                    Syarat & Ketentuan
                </Link>
            </nav>
        </footer>
    </div>
</template>
