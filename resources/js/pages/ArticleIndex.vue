<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import SiteFooter from '@/components/SiteFooter.vue';
import SiteHeader from '@/components/SiteHeader.vue';
import { feature, home, register } from '@/routes';

defineProps<{
    articles: { title: string; description: string; href: string }[];
}>();

const featureLinks = [
    ['absensi-qr', 'Absensi QR santri'],
    ['kartu-santri', 'Kartu santri QR'],
    ['notifikasi-wali', 'Notifikasi wali Telegram'],
    ['rapor-santri', 'Rapor santri online'],
    ['spp', 'Pencatatan SPP'],
    ['perizinan', 'Perizinan santri'],
] as const;
</script>

<template>
    <div
        class="min-h-screen bg-[#fbfdf9] text-slate-950 dark:bg-slate-950 dark:text-white"
    >
        <!-- The description meta is rendered server-side in app.blade.php. -->
        <Head title="Template dan Panduan Administrasi TPQ/TPA Gratis" />

        <SiteHeader :sections-href="home.url()" />

        <main class="mx-auto max-w-3xl px-4 py-10 sm:px-8 sm:py-20">
            <h1
                class="text-3xl font-bold tracking-tight text-balance sm:text-5xl"
            >
                Panduan administrasi TPQ dan TPA
            </h1>
            <p
                class="mt-5 text-base leading-8 text-slate-600 sm:text-lg dark:text-slate-300"
            >
                Panduan praktis dan template gratis untuk pengelola TPQ/TPA:
                rapor, keuangan, absensi, dan komunikasi dengan wali santri.
            </p>

            <ul class="mt-10 flex flex-col gap-4">
                <li v-for="article in articles" :key="article.href">
                    <Link
                        :href="article.href"
                        class="block rounded-3xl border border-emerald-950/8 bg-white p-6 transition hover:border-emerald-200 hover:shadow-lg dark:border-white/10 dark:bg-slate-900 dark:hover:border-emerald-800"
                    >
                        <h2 class="text-lg font-bold">{{ article.title }}</h2>
                        <p
                            class="mt-2 text-sm leading-6 text-slate-600 dark:text-slate-400"
                        >
                            {{ article.description }}
                        </p>
                    </Link>
                </li>
            </ul>

            <section
                class="mt-14 rounded-3xl border border-emerald-950/8 bg-white p-6 sm:p-8 dark:border-white/10 dark:bg-slate-900"
            >
                <h2 class="text-xl font-bold tracking-tight">
                    Template siap pakai untuk pengurus TPA dan TPQ
                </h2>
                <p
                    class="mt-3 text-sm leading-7 text-slate-600 dark:text-slate-300"
                >
                    Setiap panduan disusun dari kebutuhan harian pengelola
                    lembaga pendidikan Al-Qur'an, mulai dari menyusun rapor
                    santri per periode, mencatat buku kas dan rekap SPP 12 bulan
                    di Excel, hingga menyiapkan koneksi jaringan agar notifikasi
                    kehadiran Telegram selalu sampai ke orang tua. Seluruh
                    template dapat diunduh dan diubah sesuai format lembaga
                    tanpa perlu membuat akun.
                </p>
                <p
                    class="mt-3 text-sm leading-7 text-slate-600 dark:text-slate-300"
                >
                    Saat jumlah santri bertambah dan pencatatan di buku atau
                    berkas Excel mulai sulit dibagi antar pengajar, Anda dapat
                    beralih ke SantriQ secara gratis. Semua data kehadiran QR
                    santri, rapor, tagihan SPP, dan perizinan tercatat di satu
                    dasbor lembaga.
                </p>
                <ul
                    class="mt-4 list-disc space-y-2 pl-5 text-sm leading-6 text-slate-600 marker:text-emerald-600 dark:text-slate-300"
                >
                    <li>
                        Format rapor TPQ mencakup kolom identitas santri, jilid
                        atau juz, hafalan surat pendek, praktik ibadah, rekap
                        kehadiran, dan contoh kalimat catatan akhlak yang siap
                        disesuaikan.
                    </li>
                    <li>
                        Template buku kas dan rekap SPP 12 bulan sudah
                        dilengkapi rumus saldo otomatis serta penjumlahan per
                        santri dan per bulan agar bendahara mudah memantau
                        pelunasan.
                    </li>
                    <li>
                        Panduan jaringan dan Telegram membahas langkah praktis
                        menjaga pengiriman notifikasi kehadiran santri tetap
                        stabil di lokasi TPA maupun masjid.
                    </li>
                </ul>
                <div class="mt-6 flex flex-wrap gap-2.5">
                    <Link
                        v-for="[slug, label] in featureLinks"
                        :key="slug"
                        :href="feature.url(slug)"
                        class="inline-flex h-10 items-center rounded-full border border-emerald-200 px-4 text-sm font-medium text-emerald-800 transition hover:bg-emerald-50 dark:border-emerald-900 dark:text-emerald-300 dark:hover:bg-emerald-950"
                    >
                        {{ label }}
                    </Link>
                </div>
                <div class="mt-6">
                    <Link
                        :href="register()"
                        class="inline-flex h-11 items-center rounded-full bg-emerald-600 px-6 text-sm font-semibold text-white transition hover:bg-emerald-700"
                    >
                        Daftarkan lembaga gratis
                    </Link>
                </div>
            </section>
        </main>

        <SiteFooter />
    </div>
</template>
