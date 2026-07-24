<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { Check, GraduationCap, QrCode } from '@lucide/vue';
import { computed } from 'vue';
import ThemeToggle from '@/components/ThemeToggle.vue';
import { home } from '@/routes';
import { landing } from '@/routes/tenant';

interface TenantBrand {
    name: string;
    logo_path: string | null;
    tagline: string;
    description: string;
}

const page = usePage<{ tenantBrand?: TenantBrand | null }>();
const tenantBrand = computed(() => page.props.tenantBrand ?? null);

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
                v-if="tenantBrand"
                :href="landing()"
                class="relative z-10 flex w-fit items-center gap-3 text-xl font-bold tracking-tight"
            >
                <img
                    v-if="tenantBrand.logo_path"
                    :src="`/storage/${tenantBrand.logo_path}`"
                    :alt="`Logo ${tenantBrand.name}`"
                    class="size-10 rounded-xl object-cover shadow-lg shadow-emerald-950/30"
                />
                <span
                    v-else
                    class="flex size-10 items-center justify-center rounded-xl bg-emerald-500 shadow-lg shadow-emerald-950/30"
                >
                    <GraduationCap class="size-5" aria-hidden="true" />
                </span>
                {{ tenantBrand.name }}
            </Link>
            <a
                v-else
                :href="home.url()"
                class="relative z-10 flex w-fit items-center gap-3 text-xl font-bold tracking-tight"
            >
                <span
                    class="flex size-10 items-center justify-center rounded-xl bg-emerald-500 shadow-lg shadow-emerald-950/30"
                >
                    <GraduationCap class="size-5" aria-hidden="true" />
                </span>
                Santri<span class="-ml-3 text-emerald-400">Q</span>
            </a>

            <div class="relative z-10 max-w-lg">
                <span
                    class="flex size-14 items-center justify-center rounded-2xl bg-white/10 text-emerald-300"
                >
                    <QrCode class="size-7" aria-hidden="true" />
                </span>
                <h2
                    class="mt-7 text-4xl leading-tight font-bold tracking-tight"
                >
                    {{
                        tenantBrand?.tagline ??
                        'Kelola santri lebih mudah, dampingi lebih dekat.'
                    }}
                </h2>
                <p class="mt-5 max-w-md leading-7 text-emerald-100/70">
                    {{
                        tenantBrand?.description ??
                        'Semua kebutuhan administrasi TPA/TPQ dalam satu platform yang sederhana dan aman.'
                    }}
                </p>
                <div
                    class="mt-8 flex flex-col gap-3 text-sm text-emerald-50/85"
                >
                    <span class="flex items-center gap-2.5">
                        <Check
                            class="size-4 text-emerald-300"
                            aria-hidden="true"
                        />
                        {{
                            tenantBrand
                                ? `Portal staf resmi ${tenantBrand.name}`
                                : 'Absensi QR dan notifikasi Telegram'
                        }}
                    </span>
                    <span class="flex items-center gap-2.5">
                        <Check
                            class="size-4 text-emerald-300"
                            aria-hidden="true"
                        />
                        {{
                            tenantBrand
                                ? 'Pengelolaan data aman bersama SantriQ'
                                : 'Gratis dan open source'
                        }}
                    </span>
                </div>
            </div>

            <a
                :href="home.url()"
                class="relative z-10 w-fit text-xs text-emerald-100/60 transition hover:text-emerald-100 hover:underline"
            >
                Powered by SantriQ
            </a>
        </aside>

        <main
            class="relative flex min-h-svh items-center justify-center px-6 py-16 sm:px-10"
        >
            <div class="absolute top-5 right-5 sm:top-7 sm:right-8">
                <ThemeToggle />
            </div>

            <div class="w-full max-w-md">
                <Link
                    v-if="tenantBrand"
                    :href="landing()"
                    class="mb-10 flex w-fit items-center gap-2.5 text-xl font-bold tracking-tight lg:hidden"
                >
                    <img
                        v-if="tenantBrand.logo_path"
                        :src="`/storage/${tenantBrand.logo_path}`"
                        :alt="`Logo ${tenantBrand.name}`"
                        class="size-9 rounded-xl object-cover"
                    />
                    <span
                        v-else
                        class="flex size-9 items-center justify-center rounded-xl bg-emerald-600 text-white"
                    >
                        <GraduationCap class="size-5" aria-hidden="true" />
                    </span>
                    {{ tenantBrand.name }}
                </Link>
                <a
                    v-else
                    :href="home.url()"
                    class="mb-10 flex w-fit items-center gap-2.5 text-xl font-bold tracking-tight lg:hidden"
                >
                    <span
                        class="flex size-9 items-center justify-center rounded-xl bg-emerald-600 text-white"
                    >
                        <GraduationCap class="size-5" aria-hidden="true" />
                    </span>
                    Santri<span class="-ml-2.5 text-emerald-600">Q</span>
                </a>

                <div class="mb-8">
                    <h1 class="text-3xl font-bold tracking-tight" v-if="title">
                        {{
                            tenantBrand ? `Masuk ke ${tenantBrand.name}` : title
                        }}
                    </h1>
                    <p
                        class="mt-2 text-sm leading-6 text-slate-500 dark:text-slate-400"
                        v-if="description"
                    >
                        {{ tenantBrand?.tagline ?? description }}
                    </p>
                </div>

                <slot />

                <p
                    class="mt-8 text-center text-xs text-slate-400 lg:hidden dark:text-slate-500"
                >
                    Powered by
                    <a
                        :href="home.url()"
                        class="font-semibold text-emerald-700 hover:underline dark:text-emerald-400"
                    >
                        SantriQ
                    </a>
                </p>
            </div>
        </main>
    </div>
</template>
