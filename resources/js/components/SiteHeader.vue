<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { GraduationCap, Menu } from '@lucide/vue';
import { onMounted, ref } from 'vue';
import ThemeToggle from '@/components/ThemeToggle.vue';
import {
    Sheet,
    SheetContent,
    SheetHeader,
    SheetTitle,
    SheetTrigger,
} from '@/components/ui/sheet';
import { home, login, register, requestFeature } from '@/routes';

// On the landing page itself this stays empty so "Cara kerja"/"Tentang" are
// plain same-page anchors ("#cara-kerja"); other pages pass home().url so
// they resolve to "/#cara-kerja" and jump to the landing page's sections.
withDefaults(defineProps<{ sectionsHref?: string }>(), {
    sectionsHref: '',
});

const isMobileMenuOpen = ref(false);
const githubStars = ref<number>();

const closeMobileMenu = () => {
    isMobileMenuOpen.value = false;
};

onMounted(async () => {
    try {
        const response = await fetch(
            'https://api.github.com/repos/azmifauzan/santriq',
        );

        if (response.ok) {
            const repository = (await response.json()) as {
                stargazers_count: number;
            };

            githubStars.value = repository.stargazers_count;
        }
    } catch {
        // The repository link remains usable when GitHub's API is unavailable.
    }
});
</script>

<template>
    <header
        class="relative z-50 border-b border-emerald-950/5 bg-[#fbfdf9]/85 backdrop-blur-xl dark:border-white/10 dark:bg-slate-950/85"
    >
        <nav
            aria-label="Navigasi utama"
            class="mx-auto flex h-16 max-w-7xl items-center justify-between px-4 sm:h-18 sm:px-8 lg:px-10"
        >
            <Link
                :href="home()"
                class="flex shrink-0 items-center gap-2.5"
                @click="closeMobileMenu"
            >
                <span
                    class="flex size-9 items-center justify-center rounded-xl bg-emerald-600 text-white shadow-sm shadow-emerald-900/20"
                >
                    <GraduationCap class="size-5" aria-hidden="true" />
                </span>
                <span class="text-xl font-bold tracking-tight">
                    Santri<span class="text-emerald-600">Q</span>
                </span>
            </Link>

            <div
                class="hidden items-center gap-8 text-sm font-medium text-slate-600 md:flex dark:text-slate-300"
            >
                <Link
                    :href="requestFeature()"
                    class="transition hover:text-emerald-700 dark:hover:text-emerald-400"
                >
                    Fitur
                </Link>
                <a
                    :href="`${sectionsHref}#cara-kerja`"
                    class="transition hover:text-emerald-700 dark:hover:text-emerald-400"
                >
                    Cara kerja
                </a>
                <a
                    :href="`${sectionsHref}#tentang`"
                    class="transition hover:text-emerald-700 dark:hover:text-emerald-400"
                >
                    Tentang
                </a>
            </div>

            <div class="flex items-center gap-1.5 sm:gap-2">
                <a
                    href="https://github.com/azmifauzan/santriq"
                    target="_blank"
                    rel="noopener noreferrer"
                    aria-label="Beri bintang SantriQ di GitHub"
                    class="hidden h-10 items-center gap-2 rounded-full px-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-100 hover:text-emerald-700 lg:inline-flex dark:text-slate-200 dark:hover:bg-slate-800 dark:hover:text-emerald-400"
                >
                    <svg
                        viewBox="0 0 24 24"
                        fill="currentColor"
                        class="size-4"
                        aria-hidden="true"
                    >
                        <path
                            d="M12 .7a11.5 11.5 0 0 0-3.64 22.41c.58.1.79-.25.79-.56v-2.23c-3.22.7-3.9-1.37-3.9-1.37-.53-1.34-1.29-1.7-1.29-1.7-1.05-.72.08-.7.08-.7 1.16.08 1.78 1.19 1.78 1.19 1.04 1.77 2.72 1.26 3.38.96.1-.75.4-1.26.74-1.55-2.57-.3-5.27-1.29-5.27-5.69 0-1.26.45-2.29 1.19-3.09-.12-.29-.52-1.47.11-3.05 0 0 .97-.31 3.16 1.18a10.97 10.97 0 0 1 5.75 0c2.2-1.49 3.16-1.18 3.16-1.18.63 1.58.23 2.76.12 3.05.74.8 1.18 1.83 1.18 3.09 0 4.42-2.71 5.39-5.29 5.68.42.36.79 1.06.79 2.14v3.17c0 .31.21.67.8.56A11.5 11.5 0 0 0 12 .7Z"
                        />
                    </svg>
                    <span>GitHub</span>
                    <span
                        class="min-w-4 rounded-full bg-slate-200 px-1.5 py-0.5 text-center text-xs tabular-nums dark:bg-slate-700"
                    >
                        {{ githubStars ?? '…' }}
                    </span>
                </a>

                <ThemeToggle />

                <Link
                    :href="login()"
                    class="inline-flex h-9 items-center rounded-full px-3 text-xs font-semibold text-slate-700 transition hover:bg-slate-100 hover:text-emerald-700 sm:h-10 sm:px-4 sm:text-sm dark:text-slate-200 dark:hover:bg-slate-800 dark:hover:text-emerald-400"
                >
                    Masuk
                </Link>

                <Link
                    :href="register()"
                    class="hidden h-10 items-center rounded-full bg-emerald-600 px-4 text-sm font-semibold text-white transition hover:bg-emerald-700 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-600 sm:inline-flex lg:px-5"
                >
                    Daftar gratis
                </Link>

                <div class="md:hidden">
                    <Sheet v-model:open="isMobileMenuOpen">
                        <SheetTrigger as-child>
                            <button
                                type="button"
                                aria-label="Buka menu navigasi"
                                class="inline-flex size-9 items-center justify-center rounded-xl text-slate-700 transition hover:bg-slate-100 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-600 sm:size-10 dark:text-slate-200 dark:hover:bg-slate-800"
                            >
                                <Menu class="size-5" aria-hidden="true" />
                            </button>
                        </SheetTrigger>
                        <SheetContent
                            side="right"
                            class="flex w-[85vw] max-w-xs flex-col justify-between p-6"
                        >
                            <div class="space-y-6">
                                <SheetHeader class="text-left">
                                    <SheetTitle
                                        class="flex items-center gap-2.5"
                                    >
                                        <span
                                            class="flex size-8 items-center justify-center rounded-lg bg-emerald-600 text-white"
                                        >
                                            <GraduationCap
                                                class="size-4"
                                                aria-hidden="true"
                                            />
                                        </span>
                                        <span
                                            class="text-lg font-bold tracking-tight"
                                        >
                                            Santri<span class="text-emerald-600"
                                                >Q</span
                                            >
                                        </span>
                                    </SheetTitle>
                                </SheetHeader>

                                <nav
                                    aria-label="Navigasi menu mobile"
                                    class="flex flex-col space-y-1 font-medium text-slate-700 dark:text-slate-200"
                                >
                                    <Link
                                        :href="requestFeature()"
                                        class="flex h-11 items-center rounded-xl px-3 transition hover:bg-emerald-50 hover:text-emerald-700 dark:hover:bg-emerald-950/50 dark:hover:text-emerald-400"
                                        @click="closeMobileMenu"
                                    >
                                        Fitur
                                    </Link>
                                    <a
                                        :href="`${sectionsHref}#cara-kerja`"
                                        class="flex h-11 items-center rounded-xl px-3 transition hover:bg-emerald-50 hover:text-emerald-700 dark:hover:bg-emerald-950/50 dark:hover:text-emerald-400"
                                        @click="closeMobileMenu"
                                    >
                                        Cara kerja
                                    </a>
                                    <a
                                        :href="`${sectionsHref}#tentang`"
                                        class="flex h-11 items-center rounded-xl px-3 transition hover:bg-emerald-50 hover:text-emerald-700 dark:hover:bg-emerald-950/50 dark:hover:text-emerald-400"
                                        @click="closeMobileMenu"
                                    >
                                        Tentang
                                    </a>
                                </nav>

                                <div
                                    class="border-t border-slate-200 pt-5 dark:border-slate-800"
                                >
                                    <div class="flex flex-col gap-2.5">
                                        <Link
                                            :href="register()"
                                            class="flex h-11 w-full items-center justify-center rounded-xl bg-emerald-600 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700"
                                            @click="closeMobileMenu"
                                        >
                                            Daftar gratis
                                        </Link>
                                        <Link
                                            :href="login()"
                                            class="flex h-11 w-full items-center justify-center rounded-xl border border-slate-200 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-slate-800 dark:text-slate-200 dark:hover:bg-slate-900"
                                            @click="closeMobileMenu"
                                        >
                                            Masuk
                                        </Link>
                                    </div>
                                </div>
                            </div>

                            <div
                                class="border-t border-slate-200 pt-4 dark:border-slate-800"
                            >
                                <a
                                    href="https://github.com/azmifauzan/santriq"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    aria-label="Beri bintang SantriQ di GitHub"
                                    class="flex items-center justify-between rounded-xl bg-slate-100 px-3.5 py-2.5 text-xs font-semibold text-slate-700 transition hover:text-emerald-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:text-emerald-400"
                                >
                                    <span class="flex items-center gap-2">
                                        <svg
                                            viewBox="0 0 24 24"
                                            fill="currentColor"
                                            class="size-4"
                                            aria-hidden="true"
                                        >
                                            <path
                                                d="M12 .7a11.5 11.5 0 0 0-3.64 22.41c.58.1.79-.25.79-.56v-2.23c-3.22.7-3.9-1.37-3.9-1.37-.53-1.34-1.29-1.7-1.29-1.7-1.05-.72.08-.7.08-.7 1.16.08 1.78 1.19 1.78 1.19 1.04 1.77 2.72 1.26 3.38.96.1-.75.4-1.26.74-1.55-2.57-.3-5.27-1.29-5.27-5.69 0-1.26.45-2.29 1.19-3.09-.12-.29-.52-1.47.11-3.05 0 0 .97-.31 3.16 1.18a10.97 10.97 0 0 1 5.75 0c2.2-1.49 3.16-1.18 3.16-1.18.63 1.58.23 2.76.12 3.05.74.8 1.18 1.83 1.18 3.09 0 4.42-2.71 5.39-5.29 5.68.42.36.79 1.06.79 2.14v3.17c0 .31.21.67.8.56A11.5 11.5 0 0 0 12 .7Z"
                                            />
                                        </svg>
                                        Beri bintang GitHub
                                    </span>
                                    <span
                                        class="rounded-full bg-slate-200 px-2 py-0.5 text-[11px] tabular-nums dark:bg-slate-700"
                                    >
                                        ★ {{ githubStars ?? '…' }}
                                    </span>
                                </a>
                            </div>
                        </SheetContent>
                    </Sheet>
                </div>
            </div>
        </nav>
    </header>
</template>
