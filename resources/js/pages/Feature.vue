<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import SiteFooter from '@/components/SiteFooter.vue';
import SiteHeader from '@/components/SiteHeader.vue';
import { home, register } from '@/routes';

type FeatureContent = {
    title: string;
    heading: string;
    description: string;
    problem: string;
    steps: string[];
    faq: { q: string; a: string }[];
};

// The text lives in config/features.php so app.blade.php can render the same
// content as FAQPage JSON-LD and as a no-JavaScript fallback.
defineProps<{
    slug: string;
    content: FeatureContent;
    related: { slug: string; title: string; href: string }[];
    guides: { title: string; href: string }[];
}>();
</script>

<template>
    <div
        class="min-h-screen bg-[#fbfdf9] text-slate-950 dark:bg-slate-950 dark:text-white"
    >
        <!-- Description and FAQPage JSON-LD are rendered server-side in app.blade.php. -->
        <Head :title="content.title" />

        <SiteHeader :sections-href="home.url()" />

        <main class="mx-auto max-w-3xl px-4 py-10 sm:px-8 sm:py-20">
            <h1
                class="text-3xl font-bold tracking-tight text-balance sm:text-5xl"
            >
                {{ content.heading }}
            </h1>
            <p
                class="mt-5 text-base leading-8 text-slate-600 sm:text-lg dark:text-slate-300"
            >
                {{ content.description }}
            </p>
            <Link
                :href="register()"
                class="mt-8 inline-flex h-11 items-center rounded-full bg-emerald-600 px-6 text-sm font-semibold text-white transition hover:bg-emerald-700"
            >
                Daftarkan lembaga gratis
            </Link>

            <section class="mt-14 flex flex-col gap-4">
                <h2 class="text-xl font-bold tracking-tight">Masalahnya</h2>
                <p class="leading-7 text-slate-600 dark:text-slate-300">
                    {{ content.problem }}
                </p>
            </section>

            <section class="mt-12 flex flex-col gap-4">
                <h2 class="text-xl font-bold tracking-tight">Cara kerjanya</h2>
                <ol
                    class="list-decimal space-y-3 pl-5 leading-7 text-slate-600 marker:font-semibold marker:text-emerald-600 dark:text-slate-300"
                >
                    <li v-for="step in content.steps" :key="step">
                        {{ step }}
                    </li>
                </ol>
            </section>

            <section class="mt-12 flex flex-col gap-6">
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

            <section v-if="guides.length" class="mt-12 flex flex-col gap-3">
                <h2 class="text-xl font-bold tracking-tight">Panduan</h2>
                <ul class="flex flex-wrap gap-3">
                    <li v-for="guide in guides" :key="guide.href">
                        <Link
                            :href="guide.href"
                            class="inline-flex h-10 items-center rounded-full border border-emerald-200 px-4 text-sm font-medium text-emerald-800 transition hover:bg-emerald-50 dark:border-emerald-900 dark:text-emerald-300 dark:hover:bg-emerald-950"
                        >
                            {{ guide.title }}
                        </Link>
                    </li>
                </ul>
            </section>

            <section class="mt-12 flex flex-col gap-3">
                <h2 class="text-xl font-bold tracking-tight">Fitur terkait</h2>
                <ul class="flex flex-wrap gap-3">
                    <li v-for="item in related" :key="item.slug">
                        <Link
                            :href="item.href"
                            class="inline-flex h-10 items-center rounded-full border border-emerald-200 px-4 text-sm font-medium text-emerald-800 transition hover:bg-emerald-50 dark:border-emerald-900 dark:text-emerald-300 dark:hover:bg-emerald-950"
                        >
                            {{ item.title }}
                        </Link>
                    </li>
                </ul>
            </section>
        </main>

        <SiteFooter />
    </div>
</template>
