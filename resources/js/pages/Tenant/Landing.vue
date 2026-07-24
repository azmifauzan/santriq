<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    ArrowRight,
    BookOpenCheck,
    CalendarCheck2,
    Clock3,
    GraduationCap,
    HeartHandshake,
    MapPin,
    MessageCircleMore,
    Phone,
    QrCode,
    ShieldCheck,
    Sparkles,
    UsersRound,
} from '@lucide/vue';
import type { CSSProperties } from 'vue';
import ThemeToggle from '@/components/ThemeToggle.vue';
import { login } from '@/routes';
import { login as guardianLogin } from '@/routes/guardian';

const { tenant, landing, stats } = defineProps<{
    tenant: {
        id: number;
        name: string;
        address: string | null;
        phone: string | null;
    };
    landing: {
        tagline: string;
        description: string;
        operating_hours: string | null;
        accent_color: string;
        logo_path: string | null;
        gallery: string[];
    };
    stats: { students: number; teachers: number; classrooms: number };
}>();

const accentStyle = {
    '--tenant-accent': landing.accent_color,
} as CSSProperties;

const services = [
    {
        icon: QrCode,
        title: 'Kehadiran tercatat',
        description:
            'Aktivitas kehadiran santri tercatat lebih rapi melalui sistem SantriQ.',
    },
    {
        icon: BookOpenCheck,
        title: 'Perkembangan terpantau',
        description:
            'Pencapaian belajar santri terdokumentasi dan mudah dipantau dari waktu ke waktu.',
    },
    {
        icon: MessageCircleMore,
        title: 'Wali tetap terhubung',
        description:
            'Portal wali membantu keluarga mengikuti informasi penting santri dengan mudah.',
    },
];
</script>

<template>
    <div
        :style="accentStyle"
        class="min-h-screen overflow-hidden bg-[#fbfdf9] text-slate-950 dark:bg-slate-950 dark:text-white"
    >
        <Head :title="tenant.name">
            <meta
                head-key="description"
                name="description"
                :content="landing.description"
            />
        </Head>

        <header
            class="relative z-50 border-b border-emerald-950/5 bg-[#fbfdf9]/85 backdrop-blur-xl dark:border-white/10 dark:bg-slate-950/85"
        >
            <nav
                aria-label="Navigasi utama"
                class="mx-auto flex h-18 max-w-7xl items-center justify-between gap-4 px-5 sm:px-8 lg:px-10"
            >
                <a href="#beranda" class="flex min-w-0 items-center gap-3">
                    <img
                        v-if="landing.logo_path"
                        :src="`/storage/${landing.logo_path}`"
                        :alt="`Logo ${tenant.name}`"
                        class="size-10 shrink-0 rounded-xl object-cover shadow-sm"
                    />
                    <span
                        v-else
                        class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-emerald-600 text-white shadow-sm shadow-emerald-900/20"
                    >
                        <GraduationCap class="size-5" aria-hidden="true" />
                    </span>
                    <span class="min-w-0">
                        <span class="block truncate font-bold tracking-tight">
                            {{ tenant.name }}
                        </span>
                        <span
                            class="block text-[10px] font-semibold tracking-widest text-emerald-700 uppercase dark:text-emerald-400"
                        >
                            Didukung SantriQ
                        </span>
                    </span>
                </a>

                <div class="flex shrink-0 items-center gap-2">
                    <ThemeToggle />
                    <Link
                        :href="login()"
                        class="hidden h-10 items-center rounded-full px-4 text-sm font-semibold text-slate-700 transition hover:text-emerald-700 sm:inline-flex dark:text-slate-200 dark:hover:text-emerald-400"
                    >
                        Masuk staf
                    </Link>
                    <Link
                        :href="guardianLogin()"
                        class="inline-flex h-10 items-center gap-2 rounded-full bg-emerald-600 px-4 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-600 sm:px-5"
                    >
                        <UsersRound class="size-4" aria-hidden="true" />
                        <span class="hidden sm:inline">Portal wali</span>
                        <span class="sm:hidden">Wali</span>
                    </Link>
                </div>
            </nav>
        </header>

        <main>
            <section id="beranda" class="relative">
                <div
                    class="absolute inset-x-0 top-0 h-[48rem] bg-[radial-gradient(circle_at_78%_18%,rgba(16,185,129,0.16),transparent_28%),radial-gradient(circle_at_12%_48%,rgba(251,191,36,0.12),transparent_24%)] dark:bg-[radial-gradient(circle_at_78%_18%,rgba(16,185,129,0.13),transparent_28%),radial-gradient(circle_at_12%_48%,rgba(251,191,36,0.06),transparent_24%)]"
                    aria-hidden="true"
                />

                <div
                    class="relative mx-auto grid max-w-7xl items-center gap-14 px-5 py-18 sm:px-8 sm:py-24 lg:grid-cols-[1.04fr_0.96fr] lg:px-10 lg:py-28"
                >
                    <div class="max-w-2xl">
                        <div
                            class="mb-6 inline-flex items-center gap-2 rounded-full border border-emerald-200 bg-white/80 px-3.5 py-2 text-xs font-semibold text-emerald-800 shadow-sm dark:border-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-300"
                        >
                            <Sparkles class="size-3.5" aria-hidden="true" />
                            Ruang belajar dan tumbuh bersama
                        </div>
                        <h1
                            class="text-4xl leading-[1.08] font-bold tracking-[-0.04em] text-balance sm:text-6xl lg:text-[4.1rem]"
                        >
                            Selamat datang di
                            <span class="text-emerald-600">
                                {{ tenant.name }}.
                            </span>
                        </h1>
                        <p
                            class="mt-6 max-w-xl text-lg leading-8 font-semibold text-slate-700 dark:text-slate-200"
                        >
                            {{ landing.tagline }}
                        </p>
                        <p
                            class="mt-3 max-w-xl text-base leading-7 whitespace-pre-line text-slate-600 dark:text-slate-400"
                        >
                            {{ landing.description }}
                        </p>
                        <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                            <Link
                                :href="guardianLogin()"
                                class="inline-flex h-12 items-center justify-center gap-2 rounded-full bg-emerald-600 px-6 text-sm font-bold text-white shadow-lg shadow-emerald-900/15 transition hover:-translate-y-0.5 hover:bg-emerald-700 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-600"
                            >
                                Buka portal wali
                                <ArrowRight class="size-4" aria-hidden="true" />
                            </Link>
                            <Link
                                :href="login()"
                                class="inline-flex h-12 items-center justify-center rounded-full border border-slate-200 bg-white/80 px-6 text-sm font-bold text-slate-700 transition hover:-translate-y-0.5 hover:border-emerald-200 hover:text-emerald-700 dark:border-slate-700 dark:bg-slate-900/80 dark:text-slate-200 dark:hover:border-emerald-800 dark:hover:text-emerald-400"
                            >
                                Masuk sebagai staf
                            </Link>
                        </div>
                    </div>

                    <div class="relative mx-auto w-full max-w-lg">
                        <div
                            class="absolute -inset-5 rounded-[2.5rem] bg-emerald-200/35 blur-3xl dark:bg-emerald-900/20"
                            aria-hidden="true"
                        />
                        <div
                            class="relative overflow-hidden rounded-[2rem] border border-emerald-950/8 bg-white p-4 shadow-2xl shadow-emerald-950/10 sm:p-6 dark:border-white/10 dark:bg-slate-900"
                        >
                            <div
                                class="rounded-[1.5rem] bg-emerald-950 px-6 py-8 text-white sm:px-8 sm:py-10"
                            >
                                <div
                                    class="flex items-start justify-between gap-5"
                                >
                                    <div>
                                        <p
                                            class="text-xs font-semibold tracking-[0.2em] text-emerald-300 uppercase"
                                        >
                                            Komunitas belajar
                                        </p>
                                        <p
                                            class="mt-3 text-2xl leading-tight font-bold"
                                        >
                                            Mengaji lebih terarah, komunikasi
                                            lebih dekat.
                                        </p>
                                    </div>
                                    <span
                                        class="flex size-12 shrink-0 items-center justify-center rounded-2xl bg-emerald-500 text-white"
                                    >
                                        <GraduationCap
                                            class="size-6"
                                            aria-hidden="true"
                                        />
                                    </span>
                                </div>

                                <div class="mt-9 grid grid-cols-3 gap-2">
                                    <div
                                        class="rounded-2xl border border-white/10 bg-white/8 p-3"
                                    >
                                        <QrCode
                                            class="size-5 text-emerald-300"
                                            aria-hidden="true"
                                        />
                                        <p
                                            class="mt-5 text-[11px] text-emerald-100/60"
                                        >
                                            Kehadiran
                                        </p>
                                        <p class="text-xs font-bold">Tertib</p>
                                    </div>
                                    <div
                                        class="rounded-2xl border border-white/10 bg-white/8 p-3"
                                    >
                                        <BookOpenCheck
                                            class="size-5 text-amber-300"
                                            aria-hidden="true"
                                        />
                                        <p
                                            class="mt-5 text-[11px] text-emerald-100/60"
                                        >
                                            Belajar
                                        </p>
                                        <p class="text-xs font-bold">
                                            Terpantau
                                        </p>
                                    </div>
                                    <div
                                        class="rounded-2xl border border-white/10 bg-white/8 p-3"
                                    >
                                        <HeartHandshake
                                            class="size-5 text-rose-300"
                                            aria-hidden="true"
                                        />
                                        <p
                                            class="mt-5 text-[11px] text-emerald-100/60"
                                        >
                                            Wali
                                        </p>
                                        <p class="text-xs font-bold">
                                            Terhubung
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-4 grid grid-cols-3 gap-2 sm:gap-3">
                                <div
                                    class="rounded-2xl bg-emerald-50 p-3 text-center dark:bg-emerald-950/60"
                                >
                                    <p
                                        class="text-xl font-bold text-emerald-700 dark:text-emerald-300"
                                    >
                                        {{ stats.students }}
                                    </p>
                                    <p
                                        class="mt-0.5 text-[10px] text-slate-500 dark:text-slate-400"
                                    >
                                        Santri aktif
                                    </p>
                                </div>
                                <div
                                    class="rounded-2xl bg-amber-50 p-3 text-center dark:bg-amber-950/50"
                                >
                                    <p
                                        class="text-xl font-bold text-amber-700 dark:text-amber-300"
                                    >
                                        {{ stats.teachers }}
                                    </p>
                                    <p
                                        class="mt-0.5 text-[10px] text-slate-500 dark:text-slate-400"
                                    >
                                        Pengajar
                                    </p>
                                </div>
                                <div
                                    class="rounded-2xl bg-sky-50 p-3 text-center dark:bg-sky-950/50"
                                >
                                    <p
                                        class="text-xl font-bold text-sky-700 dark:text-sky-300"
                                    >
                                        {{ stats.classrooms }}
                                    </p>
                                    <p
                                        class="mt-0.5 text-[10px] text-slate-500 dark:text-slate-400"
                                    >
                                        Kelas
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div
                            class="absolute -right-2 -bottom-7 hidden items-center gap-3 rounded-2xl border border-emerald-100 bg-white p-3.5 shadow-xl sm:flex dark:border-emerald-900 dark:bg-slate-900"
                        >
                            <span
                                class="flex size-9 items-center justify-center rounded-full bg-[#2aabee] text-white"
                            >
                                <MessageCircleMore
                                    class="size-4"
                                    aria-hidden="true"
                                />
                            </span>
                            <div>
                                <p class="text-[10px] text-slate-400">
                                    Portal wali
                                </p>
                                <p class="text-xs font-bold">
                                    Informasi dalam genggaman
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section id="layanan" class="py-20 sm:py-28">
                <div class="mx-auto max-w-7xl px-5 sm:px-8 lg:px-10">
                    <div class="mx-auto max-w-2xl text-center">
                        <p
                            class="text-sm font-bold tracking-widest text-emerald-600 uppercase"
                        >
                            Bersama SantriQ
                        </p>
                        <h2
                            class="mt-3 text-3xl font-bold tracking-tight text-balance sm:text-4xl"
                        >
                            Pendampingan santri yang lebih terhubung
                        </h2>
                        <p
                            class="mt-4 leading-7 text-slate-600 dark:text-slate-300"
                        >
                            Teknologi membantu lembaga, pengajar, dan wali
                            berjalan bersama dalam mendampingi setiap santri.
                        </p>
                    </div>

                    <div class="mt-12 grid gap-4 md:grid-cols-3">
                        <article
                            v-for="service in services"
                            :key="service.title"
                            class="rounded-3xl border border-emerald-950/8 bg-white p-6 shadow-[inset_0_3px_0_var(--tenant-accent)] transition hover:-translate-y-1 hover:border-emerald-200 hover:shadow-xl hover:shadow-emerald-950/5 dark:border-white/10 dark:bg-slate-900 dark:hover:border-emerald-800"
                        >
                            <span
                                class="flex size-11 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-400"
                            >
                                <component
                                    :is="service.icon"
                                    class="size-5"
                                    aria-hidden="true"
                                />
                            </span>
                            <h3 class="mt-5 text-lg font-bold">
                                {{ service.title }}
                            </h3>
                            <p
                                class="mt-2 text-sm leading-6 text-slate-600 dark:text-slate-400"
                            >
                                {{ service.description }}
                            </p>
                        </article>
                    </div>
                </div>
            </section>

            <section
                v-if="landing.gallery.length"
                id="galeri"
                class="border-y border-emerald-950/5 bg-emerald-50/60 py-20 sm:py-28 dark:border-white/10 dark:bg-emerald-950/20"
            >
                <div class="mx-auto max-w-7xl px-5 sm:px-8 lg:px-10">
                    <div
                        class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between"
                    >
                        <div>
                            <p
                                class="text-sm font-bold tracking-widest text-emerald-600 uppercase"
                            >
                                Galeri kegiatan
                            </p>
                            <h2
                                class="mt-3 text-3xl font-bold tracking-tight sm:text-4xl"
                            >
                                Cerita dari {{ tenant.name }}
                            </h2>
                        </div>
                        <p
                            class="max-w-md text-sm leading-6 text-slate-600 dark:text-slate-400"
                        >
                            Sekilas suasana belajar dan kebersamaan para santri.
                        </p>
                    </div>

                    <div class="mt-10 grid grid-cols-2 gap-3 md:grid-cols-3">
                        <img
                            v-for="(path, index) in landing.gallery"
                            :key="path"
                            :src="`/storage/${path}`"
                            :alt="`Kegiatan ${tenant.name} ${index + 1}`"
                            class="aspect-[4/3] w-full rounded-2xl object-cover shadow-sm first:col-span-2 md:first:row-span-2 md:first:aspect-auto md:first:h-full"
                        />
                    </div>
                </div>
            </section>

            <section class="py-20 sm:py-28">
                <div
                    class="mx-auto grid max-w-7xl gap-5 px-5 sm:px-8 lg:grid-cols-[0.9fr_1.1fr] lg:px-10"
                >
                    <div
                        class="rounded-[2rem] border border-emerald-950/8 bg-white p-7 sm:p-9 dark:border-white/10 dark:bg-slate-900"
                    >
                        <p
                            class="text-sm font-bold tracking-widest text-emerald-600 uppercase"
                        >
                            Informasi lembaga
                        </p>
                        <h2
                            class="mt-3 text-2xl font-bold tracking-tight sm:text-3xl"
                        >
                            Mari belajar dan bertumbuh bersama
                        </h2>

                        <div
                            v-if="
                                landing.operating_hours ||
                                tenant.address ||
                                tenant.phone
                            "
                            class="mt-7 grid gap-4"
                        >
                            <div
                                v-if="landing.operating_hours"
                                class="flex items-start gap-3"
                            >
                                <span
                                    class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-amber-50 text-amber-700 dark:bg-amber-950/50 dark:text-amber-300"
                                >
                                    <Clock3 class="size-5" aria-hidden="true" />
                                </span>
                                <div>
                                    <p
                                        class="text-xs text-slate-500 dark:text-slate-400"
                                    >
                                        Jam kegiatan
                                    </p>
                                    <p class="mt-0.5 text-sm font-semibold">
                                        {{ landing.operating_hours }}
                                    </p>
                                </div>
                            </div>
                            <div
                                v-if="tenant.address"
                                class="flex items-start gap-3"
                            >
                                <span
                                    class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300"
                                >
                                    <MapPin class="size-5" aria-hidden="true" />
                                </span>
                                <div>
                                    <p
                                        class="text-xs text-slate-500 dark:text-slate-400"
                                    >
                                        Alamat
                                    </p>
                                    <p
                                        class="mt-0.5 text-sm leading-6 font-semibold"
                                    >
                                        {{ tenant.address }}
                                    </p>
                                </div>
                            </div>
                            <div
                                v-if="tenant.phone"
                                class="flex items-start gap-3"
                            >
                                <span
                                    class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-sky-50 text-sky-700 dark:bg-sky-950/50 dark:text-sky-300"
                                >
                                    <Phone class="size-5" aria-hidden="true" />
                                </span>
                                <div>
                                    <p
                                        class="text-xs text-slate-500 dark:text-slate-400"
                                    >
                                        Telepon
                                    </p>
                                    <p class="mt-0.5 text-sm font-semibold">
                                        {{ tenant.phone }}
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div
                            v-else
                            class="mt-7 flex items-start gap-3 rounded-2xl bg-amber-50 p-4 text-sm leading-6 text-amber-900 dark:bg-amber-950/40 dark:text-amber-200"
                        >
                            <CalendarCheck2
                                class="mt-0.5 size-5 shrink-0"
                                aria-hidden="true"
                            />
                            Informasi jadwal dan kunjungan akan diperbarui oleh
                            pengelola lembaga.
                        </div>
                    </div>

                    <div
                        class="relative overflow-hidden rounded-[2rem] bg-emerald-950 px-7 py-10 text-white sm:px-10 sm:py-12"
                    >
                        <div
                            class="absolute -top-20 -right-16 size-64 rounded-full border-[2.5rem] border-emerald-700/50"
                            aria-hidden="true"
                        />
                        <div
                            class="absolute -bottom-28 -left-20 size-72 rounded-full border-[3rem] border-amber-400/10"
                            aria-hidden="true"
                        />
                        <div class="relative max-w-xl">
                            <span
                                class="flex size-12 items-center justify-center rounded-2xl bg-emerald-500 text-white"
                            >
                                <ShieldCheck
                                    class="size-6"
                                    aria-hidden="true"
                                />
                            </span>
                            <h2
                                class="mt-7 text-3xl font-bold tracking-tight text-balance sm:text-4xl"
                            >
                                Informasi santri kini lebih dekat dengan wali
                            </h2>
                            <p class="mt-4 leading-7 text-emerald-100/70">
                                Masuk ke portal wali untuk melihat informasi
                                anak yang terhubung dengan akun Anda.
                            </p>
                            <Link
                                :href="guardianLogin()"
                                class="mt-8 inline-flex h-12 items-center gap-2 rounded-full bg-white px-6 text-sm font-bold text-emerald-800 transition hover:-translate-y-0.5 hover:bg-emerald-50 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white"
                            >
                                Masuk portal wali
                                <ArrowRight class="size-4" aria-hidden="true" />
                            </Link>
                        </div>
                    </div>
                </div>
            </section>
        </main>

        <footer class="border-t border-emerald-950/8 dark:border-white/10">
            <div
                class="mx-auto flex max-w-7xl flex-col gap-5 px-5 py-8 text-sm text-slate-500 sm:flex-row sm:items-center sm:justify-between sm:px-8 lg:px-10 dark:text-slate-400"
            >
                <div
                    class="flex items-center gap-3 text-slate-900 dark:text-white"
                >
                    <span
                        class="flex size-9 items-center justify-center rounded-xl bg-emerald-600 text-white"
                    >
                        <GraduationCap class="size-5" aria-hidden="true" />
                    </span>
                    <div>
                        <p class="font-bold">{{ tenant.name }}</p>
                        <p class="text-xs text-slate-500 dark:text-slate-400">
                            Tumbuh bersama SantriQ
                        </p>
                    </div>
                </div>
                <div class="flex items-center gap-4 sm:text-right">
                    <Link
                        :href="login()"
                        class="font-semibold transition hover:text-emerald-700 dark:hover:text-emerald-400"
                    >
                        Masuk staf
                    </Link>
                    <span aria-hidden="true">•</span>
                    <p>Platform manajemen TPA/TPQ</p>
                </div>
            </div>
        </footer>
    </div>
</template>
