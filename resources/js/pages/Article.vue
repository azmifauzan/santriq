<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import SiteFooter from '@/components/SiteFooter.vue';
import SiteHeader from '@/components/SiteHeader.vue';
import { home, register } from '@/routes';
import { index as articlesIndex } from '@/routes/articles';

type Section = { title: string; paragraphs?: string[]; items?: string[] };

type ArticleContent = {
    title: string;
    heading: string;
    description: string;
    published_at: string;
    intro: string;
    template: { key: string; label: string };
    sections: Section[];
    faq: { q: string; a: string }[];
};

// The text lives in config/articles.php so app.blade.php can render the same
// content as JSON-LD and as a no-JavaScript fallback.
defineProps<{
    slug: string;
    content: ArticleContent;
    featureLink: { title: string; href: string };
    templateHref: string;
}>();
</script>

<template>
    <div
        class="min-h-screen bg-[#fbfdf9] text-slate-950 dark:bg-slate-950 dark:text-white"
    >
        <!-- Description and JSON-LD are rendered server-side in app.blade.php. -->
        <Head :title="content.title" />

        <SiteHeader :sections-href="home.url()" />

        <main class="mx-auto max-w-3xl px-4 py-10 sm:px-8 sm:py-20">
            <p
                class="text-sm font-semibold text-emerald-700 dark:text-emerald-400"
            >
                <Link :href="articlesIndex()" class="hover:underline">Panduan</Link>
                &middot; {{ content.published_at }}
            </p>
            <h1
                class="mt-3 text-3xl font-bold tracking-tight text-balance sm:text-5xl"
            >
                {{ content.heading }}
            </h1>
            <p
                class="mt-5 text-base leading-8 text-slate-600 sm:text-lg dark:text-slate-300"
            >
                {{ content.intro }}
            </p>
            <a
                :href="templateHref"
                class="mt-8 inline-flex min-h-11 items-center rounded-full bg-emerald-600 px-6 text-sm font-semibold text-white transition hover:bg-emerald-700"
            >
                {{ content.template.label }}
            </a>

            <div class="mt-12 flex flex-col gap-10">
                <section
                    v-for="section in content.sections"
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

                <section class="flex flex-col gap-6">
                    <h2 class="text-xl font-bold tracking-tight">
                        Pertanyaan umum
                    </h2>
                    <div v-for="item in content.faq" :key="item.q">
                        <h3 class="font-semibold">{{ item.q }}</h3>
                        <p
                            class="mt-1 leading-7 text-slate-600 dark:text-slate-300"
                        >
                            {{ item.a }}
                        </p>
                    </div>
                </section>
            </div>

            <div
                class="mt-12 rounded-3xl border border-emerald-200 bg-emerald-50 p-6 dark:border-emerald-900 dark:bg-emerald-950"
            >
                <p class="font-semibold">
                    Mau mengurangi pekerjaan administrasi di TPQ Anda?
                </p>
                <div class="mt-4 flex flex-wrap gap-3">
                    <Link
                        :href="register()"
                        class="inline-flex min-h-11 items-center rounded-full bg-emerald-600 px-6 text-sm font-semibold text-white transition hover:bg-emerald-700"
                    >
                        Daftarkan lembaga gratis
                    </Link>
                    <Link
                        :href="featureLink.href"
                        class="inline-flex min-h-11 items-center rounded-full border border-emerald-300 px-6 text-sm font-semibold text-emerald-800 transition hover:bg-white dark:border-emerald-800 dark:text-emerald-300 dark:hover:bg-emerald-900"
                    >
                        Lihat fitur {{ featureLink.title }}
                    </Link>
                </div>
            </div>
        </main>

        <SiteFooter />
    </div>
</template>
