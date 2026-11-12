<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import SiteFooter from '@/components/SiteFooter.vue';
import SiteHeader from '@/components/SiteHeader.vue';
import { home } from '@/routes';
import { index as articlesIndex } from '@/routes/articles';

// Markdown rendered server-side (App\Http\Controllers\GuideController);
// the meta description is emitted by app.blade.php.
defineProps<{
    content: { title: string; description: string; published_at: string };
    html: string;
}>();
</script>

<template>
    <div
        class="min-h-screen bg-[#fbfdf9] text-slate-950 dark:bg-slate-950 dark:text-white"
    >
        <Head :title="content.title" />

        <SiteHeader :sections-href="home.url()" />

        <main class="mx-auto max-w-3xl px-4 py-10 sm:px-8 sm:py-20">
            <p class="text-sm font-semibold text-emerald-700 dark:text-emerald-400">
                <Link :href="articlesIndex()" class="hover:underline">Panduan</Link>
                &middot; {{ content.published_at }}
            </p>
            <h1 class="mt-3 text-3xl font-bold tracking-tight text-balance sm:text-5xl">
                {{ content.title }}
            </h1>
            <article
                class="mt-8 text-base text-slate-600 dark:text-slate-300 [&_a]:font-medium [&_a]:text-emerald-700 [&_a]:underline dark:[&_a]:text-emerald-400 [&_h2]:mt-12 [&_h2]:text-xl [&_h2]:font-bold [&_h2]:tracking-tight [&_h2]:text-slate-950 dark:[&_h2]:text-white [&_li]:mt-2 [&_ol]:mt-4 [&_ol]:list-decimal [&_ol]:pl-6 [&_p]:mt-4 [&_p]:leading-7 [&_ul]:mt-4 [&_ul]:list-disc [&_ul]:pl-6"
                v-html="html"
            />
        </main>

        <SiteFooter />
    </div>
</template>
