<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { GraduationCap } from '@lucide/vue';
import { computed } from 'vue';
import ThemeToggle from '@/components/ThemeToggle.vue';

const props = defineProps<{ status: number }>();

const titles: Record<number, string> = {
    403: 'Akses ditolak',
    404: 'Halaman tidak ditemukan',
    419: 'Sesi telah berakhir',
    429: 'Terlalu banyak permintaan',
    500: 'Terjadi kesalahan pada server',
    503: 'Layanan sedang tidak tersedia',
};

const descriptions: Record<number, string> = {
    403: 'Anda tidak memiliki izin untuk mengakses halaman ini.',
    404: 'Halaman yang Anda cari tidak ada atau sudah dipindahkan.',
    419: 'Muat ulang halaman ini lalu coba kirim formulir kembali.',
    429: 'Anda mengirim terlalu banyak permintaan. Silakan coba lagi sesaat lagi.',
    500: 'Maaf, terjadi kesalahan di server kami. Tim kami sudah diberi tahu.',
    503: 'Kami sedang melakukan pemeliharaan singkat. Silakan periksa kembali sebentar lagi.',
};

const title = computed(
    () => titles[props.status] ?? `Terjadi kesalahan (${props.status})`,
);
const description = computed(
    () =>
        descriptions[props.status] ??
        'Maaf, terjadi kesalahan yang tidak terduga.',
);
</script>

<template>
    <div
        class="flex min-h-screen flex-col bg-[#fbfdf9] text-slate-950 dark:bg-slate-950 dark:text-white"
    >
        <Head :title="title" />

        <header
            class="border-b border-emerald-950/5 px-5 py-4 sm:px-8 dark:border-white/10"
        >
            <div class="mx-auto flex max-w-7xl items-center justify-between">
                <a href="/" class="flex items-center gap-2.5">
                    <span
                        class="flex size-9 items-center justify-center rounded-xl bg-emerald-600 text-white shadow-sm shadow-emerald-900/20"
                    >
                        <GraduationCap class="size-5" aria-hidden="true" />
                    </span>
                    <span class="text-xl font-bold tracking-tight">
                        Santri<span class="text-emerald-600">Q</span>
                    </span>
                </a>
                <ThemeToggle />
            </div>
        </header>

        <main class="flex flex-1 items-center justify-center px-5 py-16">
            <div class="max-w-md text-center">
                <p
                    class="text-sm font-bold tracking-widest text-emerald-600 uppercase"
                >
                    Error {{ status }}
                </p>
                <h1
                    class="mt-3 text-3xl font-bold tracking-tight text-balance sm:text-4xl"
                >
                    {{ title }}
                </h1>
                <p class="mt-4 leading-7 text-slate-600 dark:text-slate-300">
                    {{ description }}
                </p>
                <a
                    href="/"
                    class="mt-8 inline-flex h-11 items-center justify-center gap-2 rounded-full bg-emerald-600 px-6 text-sm font-semibold text-white shadow-lg shadow-emerald-900/15 transition hover:-translate-y-0.5 hover:bg-emerald-700 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-600"
                >
                    Kembali ke beranda
                </a>
            </div>
        </main>
    </div>
</template>
