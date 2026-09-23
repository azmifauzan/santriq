<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { GraduationCap } from '@lucide/vue';
import { privacy, requestFeature, terms } from '@/routes';
</script>

<template>
    <footer class="border-t border-emerald-950/8 dark:border-white/10">
        <div
            class="mx-auto flex max-w-7xl flex-col gap-4 px-5 py-8 text-sm text-slate-500 sm:flex-row sm:items-center sm:justify-between sm:px-8 lg:px-10 dark:text-slate-400"
        >
            <div class="flex flex-col gap-1.5">
                <div
                    class="flex items-center gap-2.5 text-slate-900 dark:text-white"
                >
                    <span
                        class="flex size-8 items-center justify-center rounded-lg bg-emerald-600 text-white"
                    >
                        <GraduationCap class="size-4" aria-hidden="true" />
                    </span>
                    <span class="font-bold">SantriQ</span>
                </div>
                <p>Platform manajemen TPA/TPQ gratis dan open source.</p>
                <p>
                    Design by
                    <a
                        href="https://satsetui.com"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="font-semibold transition hover:text-emerald-700 dark:hover:text-emerald-400"
                        >SatsetUI</a
                    >
                    and Managed by
                    <a
                        href="https://satsetops.com"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="font-semibold transition hover:text-emerald-700 dark:hover:text-emerald-400"
                        >SatsetOps</a
                    >
                </p>
            </div>
            <nav aria-label="Tautan legal" class="flex gap-5">
                <Link
                    :href="requestFeature()"
                    class="transition hover:text-emerald-700 dark:hover:text-emerald-400"
                >
                    Request Fitur
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
        </div>
    </footer>
</template>
