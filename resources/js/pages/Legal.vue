<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { GraduationCap } from '@lucide/vue';
import ThemeToggle from '@/components/ThemeToggle.vue';
import { home, privacy, terms } from '@/routes';

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
                Terakhir diperbarui: {{ content.updated_at }}
            </p>
            <h1 class="mt-3 text-4xl font-bold tracking-tight sm:text-5xl">
                {{ content.title }}
            </h1>
            <p
                class="mt-5 text-lg leading-8 text-slate-600 dark:text-slate-300"
            >
                {{ content.description }}
            </p>

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
