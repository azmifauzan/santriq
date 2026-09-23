<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import SiteFooter from '@/components/SiteFooter.vue';
import SiteHeader from '@/components/SiteHeader.vue';
import { home } from '@/routes';

type LegalDocument = 'privacy' | 'terms';

type LegalSection = {
    title: string;
    paragraphs?: string[];
    items?: string[];
};

type LegalContent = {
    title: string;
    updated_at: string;
    description: string;
    sections: LegalSection[];
};

// The text itself lives in config/legal.php so app.blade.php can render the
// same document for crawlers that do not execute JavaScript.
defineProps<{ document: LegalDocument; content: LegalContent }>();
</script>

<template>
    <div
        class="min-h-screen bg-[#fbfdf9] text-slate-950 dark:bg-slate-950 dark:text-white"
    >
        <!-- The description meta is rendered server-side in app.blade.php so crawlers
             that do not execute JavaScript can read it. -->
        <Head :title="content.title" />

        <SiteHeader :sections-href="home.url()" />

        <main class="mx-auto max-w-3xl px-4 py-10 sm:px-8 sm:py-20">
            <p
                class="text-sm font-semibold text-emerald-700 dark:text-emerald-400"
            >
                Terakhir diperbarui: {{ content.updated_at }}
            </p>
            <h1 class="mt-3 text-3xl font-bold tracking-tight sm:text-5xl">
                {{ content.title }}
            </h1>
            <p
                class="mt-5 text-base leading-8 text-slate-600 sm:text-lg dark:text-slate-300"
            >
                {{ content.description }}
            </p>

            <div class="mt-10 flex flex-col gap-10 sm:mt-12">
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
            </div>
        </main>

        <SiteFooter />
    </div>
</template>
