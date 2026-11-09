<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import SiteFooter from '@/components/SiteFooter.vue';
import SiteHeader from '@/components/SiteHeader.vue';
import { home } from '@/routes';

defineProps<{
    articles: { title: string; description: string; href: string }[];
}>();
</script>

<template>
    <div
        class="min-h-screen bg-[#fbfdf9] text-slate-950 dark:bg-slate-950 dark:text-white"
    >
        <!-- The description meta is rendered server-side in app.blade.php. -->
        <Head title="Panduan Administrasi TPQ dan TPA" />

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
        </main>

        <SiteFooter />
    </div>
</template>
