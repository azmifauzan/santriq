<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import {
    Bell,
    BookOpenCheck,
    CircleDollarSign,
    MessageCircleMore,
    ScanLine,
    ShieldCheck,
} from '@lucide/vue';
import SiteFooter from '@/components/SiteFooter.vue';
import SiteHeader from '@/components/SiteHeader.vue';
import { home } from '@/routes';

defineProps<{
    links: {
        threads: string;
        facebook: string;
        linkedin: string;
    };
}>();

const features = [
    {
        icon: ScanLine,
        title: 'Absensi QR yang cepat',
        description:
            'Cukup pindai kartu santri untuk mencatat waktu datang dan pulang tanpa antrean panjang.',
    },
    {
        icon: Bell,
        title: 'Notifikasi langsung',
        description:
            'Wali menerima kabar kehadiran anak secara real-time melalui Telegram.',
    },
    {
        icon: BookOpenCheck,
        title: 'Pencapaian terdokumentasi',
        description:
            'Catat hafalan, bacaan, dan perkembangan belajar santri dalam satu riwayat yang rapi.',
    },
    {
        icon: CircleDollarSign,
        title: 'SPP lebih tertib',
        description:
            'Kelola tagihan, pembayaran, dan status pelunasan setiap santri dengan mudah.',
    },
    {
        icon: MessageCircleMore,
        title: 'Terhubung lewat Telegram',
        description:
            'Wali dapat melihat kehadiran, pencapaian, tagihan, dan mengajukan izin lewat bot.',
    },
    {
        icon: ShieldCheck,
        title: 'Data lembaga terpisah',
        description:
            'Setiap TPA/TPQ memiliki ruang kerja sendiri sehingga data tetap aman dan terisolasi.',
    },
];
</script>

<template>
    <div
        class="min-h-screen bg-[#fbfdf9] text-slate-950 dark:bg-slate-950 dark:text-white"
    >
        <Head title="Fitur & Request Fitur" />

        <SiteHeader :sections-href="home.url()" />

        <main class="mx-auto max-w-7xl px-4 py-10 sm:px-8 sm:py-20 lg:px-10">
            <div class="mx-auto max-w-2xl text-center">
                <p
                    class="text-sm font-bold tracking-widest text-emerald-600 uppercase"
                >
                    Satu platform
                </p>
                <h1
                    class="mt-3 text-3xl font-bold tracking-tight text-balance sm:text-4xl"
                >
                    Semua yang dibutuhkan untuk mengelola TPA/TPQ
                </h1>
                <p class="mt-4 leading-7 text-slate-600 dark:text-slate-300">
                    Kurangi pekerjaan administratif agar pengajar bisa lebih
                    fokus mendampingi tumbuh kembang santri.
                </p>
            </div>

            <div class="mt-12 grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                <article
                    v-for="feature in features"
                    :key="feature.title"
                    class="group rounded-3xl border border-emerald-950/8 bg-white p-6 transition hover:-translate-y-1 hover:border-emerald-200 hover:shadow-xl hover:shadow-emerald-950/5 dark:border-white/10 dark:bg-slate-900 dark:hover:border-emerald-800"
                >
                    <span
                        class="flex size-11 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-700 transition group-hover:bg-emerald-600 group-hover:text-white dark:bg-emerald-950 dark:text-emerald-400"
                    >
                        <component
                            :is="feature.icon"
                            class="size-5"
                            aria-hidden="true"
                        />
                    </span>
                    <h2 class="mt-5 text-lg font-bold">
                        {{ feature.title }}
                    </h2>
                    <p
                        class="mt-2 text-sm leading-6 text-slate-600 dark:text-slate-400"
                    >
                        {{ feature.description }}
                    </p>
                </article>
            </div>

            <div
                id="request"
                class="mt-14 flex scroll-mt-24 flex-col items-center rounded-[2rem] border border-emerald-950/8 bg-white px-5 py-10 text-center sm:mt-20 sm:px-12 sm:py-14 dark:border-white/10 dark:bg-slate-900"
            >
                <span
                    class="flex size-14 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-400"
                >
                    <MessageCircleMore class="size-6" aria-hidden="true" />
                </span>
                <h2 class="mt-6 text-3xl font-bold tracking-tight sm:text-4xl">
                    Punya usulan fitur lain untuk SantriQ?
                </h2>
                <p
                    class="mt-4 max-w-xl leading-7 text-slate-600 dark:text-slate-300"
                >
                    Kirim usulan, kritik, atau ide fitur langsung ke pembuat
                    SantriQ lewat salah satu kanal berikut. Semua masukan dibaca
                    dan jadi bahan pengembangan berikutnya.
                </p>

                <div
                    class="mt-8 flex w-full max-w-md flex-col gap-3 sm:max-w-none sm:flex-row sm:justify-center"
                >
                    <a
                        :href="links.threads"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="inline-flex h-12 items-center justify-center gap-2 rounded-full bg-emerald-600 px-6 text-sm font-semibold text-white shadow-lg shadow-emerald-900/15 transition hover:-translate-y-0.5 hover:bg-emerald-700 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-600"
                    >
                        <svg
                            viewBox="0 0 24 24"
                            fill="currentColor"
                            class="size-4"
                            aria-hidden="true"
                        >
                            <path
                                d="M17.7 10.4c-.1-.05-.2-.1-.3-.14-.16-3.4-2.04-5.35-5.2-5.37-1.87-.01-3.44.75-4.4 2.16l1.5.98c.72-1.02 1.7-1.24 2.9-1.24 1.53.01 2.68.5 3.36 1.44.5.68.72 1.5.76 2.5-.9-.15-1.87-.2-2.9-.14-2.9.16-4.76 1.8-4.63 4.02.06 1.13.7 2.1 1.8 2.72 1.1.62 2.5.72 3.83.29 1.76-.57 3-1.87 3.4-3.7.16.1.32.2.47.32 1.16.9 1.24 2.4.4 3.65-.9 1.34-2.6 1.8-4.7 1.5-2.4-.35-4.2-1.8-4.83-4-.4-1.44-.34-3.32.18-5.1.65-2.2 2.06-3.75 4.15-4.36.98-.28 2.1-.3 3.06-.02V3.85c-1.2-.3-2.5-.28-3.72.08-2.7.8-4.6 2.85-5.4 5.66-.6 2.1-.65 4.35-.15 6.1.78 2.8 3.1 4.72 6.15 5.15.4.06.8.08 1.2.08 2.5 0 4.5-.9 5.7-2.6 1.3-1.9 1.15-4.35-.6-6.06a5.8 5.8 0 0 0-1.13-.85Zm-4.3 4.9c-.9.3-1.86.24-2.5-.13-.55-.32-.87-.8-.9-1.36-.06-1.14 1.1-1.9 2.9-2 .2-.01.4-.02.6-.02.7 0 1.4.07 2.1.2-.24 1.5-1.1 2.6-2.2 3.3Z"
                            />
                        </svg>
                        Threads @azmifauzan
                    </a>
                    <a
                        :href="links.facebook"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="inline-flex h-12 items-center justify-center gap-2 rounded-full border border-slate-200 bg-white/70 px-6 text-sm font-semibold text-slate-700 transition hover:border-emerald-300 hover:text-emerald-700 dark:border-slate-700 dark:bg-slate-900/70 dark:text-slate-200 dark:hover:border-emerald-700 dark:hover:text-emerald-400"
                    >
                        <svg
                            viewBox="0 0 24 24"
                            fill="currentColor"
                            class="size-4"
                            aria-hidden="true"
                        >
                            <path
                                d="M22 12a10 10 0 1 0-11.5 9.87v-6.99H7.9V12h2.6V9.8c0-2.57 1.53-4 3.87-4 1.12 0 2.3.2 2.3.2v2.5h-1.3c-1.28 0-1.68.8-1.68 1.62V12h2.86l-.46 2.88h-2.4v6.99A10 10 0 0 0 22 12Z"
                            />
                        </svg>
                        Facebook
                    </a>
                    <a
                        :href="links.linkedin"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="inline-flex h-12 items-center justify-center gap-2 rounded-full border border-slate-200 bg-white/70 px-6 text-sm font-semibold text-slate-700 transition hover:border-emerald-300 hover:text-emerald-700 dark:border-slate-700 dark:bg-slate-900/70 dark:text-slate-200 dark:hover:border-emerald-700 dark:hover:text-emerald-400"
                    >
                        <svg
                            viewBox="0 0 24 24"
                            fill="currentColor"
                            class="size-4"
                            aria-hidden="true"
                        >
                            <path
                                d="M20.45 20.45h-3.56v-5.57c0-1.33-.02-3.04-1.85-3.04-1.86 0-2.15 1.45-2.15 2.94v5.67H9.33V9h3.41v1.56h.05c.48-.9 1.64-1.85 3.38-1.85 3.6 0 4.27 2.37 4.27 5.46v6.28ZM5.34 7.43a2.07 2.07 0 1 1 0-4.14 2.07 2.07 0 0 1 0 4.14ZM7.12 20.45H3.56V9h3.56v11.45Z"
                            />
                        </svg>
                        LinkedIn
                    </a>
                </div>
            </div>
        </main>

        <SiteFooter />
    </div>
</template>
