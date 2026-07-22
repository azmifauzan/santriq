<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Check, GraduationCap, QrCode } from '@lucide/vue';
import ThemeToggle from '@/components/ThemeToggle.vue';
import { home } from '@/routes';

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <div
        class="grid min-h-svh bg-[#fbfdf9] text-slate-950 lg:grid-cols-2 dark:bg-slate-950 dark:text-white"
    >
        <aside
            class="relative hidden overflow-hidden bg-emerald-950 p-10 text-white lg:flex lg:flex-col lg:justify-between xl:p-14"
        >
            <div
                class="absolute -top-24 -right-24 size-80 rounded-full border-[3rem] border-emerald-800/40"
            />
            <div
                class="absolute -bottom-36 -left-28 size-96 rounded-full border-[4rem] border-emerald-800/30"
            />

            <Link
                :href="home()"
                class="relative z-10 flex w-fit items-center gap-3 text-xl font-bold tracking-tight"
            >
                <span
                    class="flex size-10 items-center justify-center rounded-xl bg-emerald-500 shadow-lg shadow-emerald-950/30"
                >
                    <GraduationCap class="size-5" aria-hidden="true" />
                </span>
                Santri<span class="-ml-3 text-emerald-400">Q</span>
            </Link>

            <div class="relative z-10 max-w-lg">
                <span
                    class="flex size-14 items-center justify-center rounded-2xl bg-white/10 text-emerald-300"
                >
                    <QrCode class="size-7" aria-hidden="true" />
                </span>
                <h2
                    class="mt-7 text-4xl leading-tight font-bold tracking-tight"
                >
                    Kelola santri lebih mudah, dampingi lebih dekat.
                </h2>
                <p class="mt-5 max-w-md leading-7 text-emerald-100/70">
                    Semua kebutuhan administrasi TPA/TPQ dalam satu platform
                    yang sederhana dan aman.
                </p>
                <div
                    class="mt-8 flex flex-col gap-3 text-sm text-emerald-50/85"
                >
                    <span class="flex items-center gap-2.5">
                        <Check
                            class="size-4 text-emerald-300"
                            aria-hidden="true"
                        />
                        Absensi QR dan notifikasi Telegram
                    </span>
                    <span class="flex items-center gap-2.5">
                        <Check
                            class="size-4 text-emerald-300"
                            aria-hidden="true"
                        />
                        Gratis dan open source
                    </span>
                </div>
            </div>

            <p class="relative z-10 text-xs text-emerald-100/50">
                Platform manajemen TPA/TPQ Indonesia
            </p>
        </aside>

        <main
            class="relative flex min-h-svh items-center justify-center px-6 py-16 sm:px-10"
        >
            <div class="absolute top-5 right-5 sm:top-7 sm:right-8">
                <ThemeToggle />
            </div>

            <div class="w-full max-w-md">
                <Link
                    :href="home()"
                    class="mb-10 flex w-fit items-center gap-2.5 text-xl font-bold tracking-tight lg:hidden"
                >
                    <span
                        class="flex size-9 items-center justify-center rounded-xl bg-emerald-600 text-white"
                    >
                        <GraduationCap class="size-5" aria-hidden="true" />
                    </span>
                    Santri<span class="-ml-2.5 text-emerald-600">Q</span>
                </Link>

                <div class="mb-8">
                    <h1 class="text-3xl font-bold tracking-tight" v-if="title">
                        {{ title }}
                    </h1>
                    <p
                        class="mt-2 text-sm leading-6 text-slate-500 dark:text-slate-400"
                        v-if="description"
                    >
                        {{ description }}
                    </p>
                </div>

                <slot />
            </div>
        </main>
    </div>
</template>
