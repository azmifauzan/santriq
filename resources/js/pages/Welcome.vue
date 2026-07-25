<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    ArrowRight,
    Bell,
    BookOpenCheck,
    Check,
    CircleDollarSign,
    Clock3,
    GraduationCap,
    MessageCircleMore,
    QrCode,
    ScanLine,
    ShieldCheck,
    Sparkles,
    Users,
} from '@lucide/vue';
import { onMounted, ref } from 'vue';
import ThemeToggle from '@/components/ThemeToggle.vue';
import { login, privacy, register, terms } from '@/routes';

defineProps<{
    demoUrl?: string | null;
}>();

const githubStars = ref<number>();

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

const features = [
    {
        icon: ScanLine,
        title: 'Absensi QR yang cepat',
        description:
            'Cukup pindai kartu santri untuk mencatat waktu datang dan pulang tanpa antrean panjang.',
    },
    {
        icon: Bell,
        title: 'Notifikasi langsung',
        description:
            'Wali menerima kabar kehadiran anak secara real-time melalui Telegram.',
    },
    {
        icon: BookOpenCheck,
        title: 'Pencapaian terdokumentasi',
        description:
            'Catat hafalan, bacaan, dan perkembangan belajar santri dalam satu riwayat yang rapi.',
    },
    {
        icon: CircleDollarSign,
        title: 'SPP lebih tertib',
        description:
            'Kelola tagihan, pembayaran, dan status pelunasan setiap santri dengan mudah.',
    },
    {
        icon: MessageCircleMore,
        title: 'Terhubung lewat Telegram',
        description:
            'Wali dapat melihat kehadiran, pencapaian, tagihan, dan mengajukan izin lewat bot.',
    },
    {
        icon: ShieldCheck,
        title: 'Data lembaga terpisah',
        description:
            'Setiap TPA/TPQ memiliki ruang kerja sendiri sehingga data tetap aman dan terisolasi.',
    },
];

const steps = [
    {
        number: '01',
        title: 'Daftarkan lembaga',
        description: 'Buat akun gratis dan lengkapi data TPA/TPQ Anda.',
    },
    {
        number: '02',
        title: 'Tambahkan santri',
        description:
            'Masukkan data santri, kelas, dan wali lalu cetak kartu QR.',
    },
    {
        number: '03',
        title: 'Mulai kelola',
        description: 'Pindai kehadiran dan pantau kegiatan dari satu dasbor.',
    },
];
</script>

<template>
    <div
        class="min-h-screen overflow-hidden bg-[#fbfdf9] text-slate-950 dark:bg-slate-950 dark:text-white"
    >
        <!-- The description meta is rendered server-side in app.blade.php so crawlers
             that do not execute JavaScript can read it. -->
        <Head title="SantriQ — Manajemen TPA/TPQ yang Lebih Mudah" />

        <header
            class="relative z-50 border-b border-emerald-950/5 bg-[#fbfdf9]/85 backdrop-blur-xl dark:border-white/10 dark:bg-slate-950/85"
        >
            <nav
                aria-label="Navigasi utama"
                class="mx-auto flex h-18 max-w-7xl items-center justify-between px-5 sm:px-8 lg:px-10"
            >
                <a href="#beranda" class="flex items-center gap-2.5">
                    <span
                        class="flex size-9 items-center justify-center rounded-xl bg-emerald-600 text-white shadow-sm shadow-emerald-900/20"
                    >
                        <GraduationCap class="size-5" aria-hidden="true" />
                    </span>
                    <span class="text-xl font-bold tracking-tight">
                        Santri<span class="text-emerald-600">Q</span>
                    </span>
                </a>

                <div
                    class="hidden items-center gap-8 text-sm font-medium text-slate-600 md:flex dark:text-slate-300"
                >
                    <a
                        href="#fitur"
                        class="transition hover:text-emerald-700 dark:hover:text-emerald-400"
                    >
                        Fitur
                    </a>
                    <a
                        href="#cara-kerja"
                        class="transition hover:text-emerald-700 dark:hover:text-emerald-400"
                    >
                        Cara kerja
                    </a>
                    <a
                        href="#tentang"
                        class="transition hover:text-emerald-700 dark:hover:text-emerald-400"
                    >
                        Tentang
                    </a>
                </div>

                <div class="flex items-center gap-2">
                    <a
                        href="https://github.com/azmifauzan/santriq"
                        target="_blank"
                        rel="noopener noreferrer"
                        aria-label="Beri bintang SantriQ di GitHub"
                        class="inline-flex h-10 items-center gap-2 rounded-full px-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-100 hover:text-emerald-700 dark:text-slate-200 dark:hover:bg-slate-800 dark:hover:text-emerald-400"
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
                        <span class="hidden lg:inline">GitHub</span>
                        <span
                            class="min-w-4 rounded-full bg-slate-200 px-1.5 py-0.5 text-center text-xs tabular-nums dark:bg-slate-700"
                        >
                            {{ githubStars ?? '…' }}
                        </span>
                    </a>
                    <ThemeToggle />
                    <Link
                        :href="login()"
                        class="hidden h-10 items-center rounded-full px-4 text-sm font-semibold text-slate-700 transition hover:text-emerald-700 sm:inline-flex dark:text-slate-200 dark:hover:text-emerald-400"
                    >
                        Masuk
                    </Link>
                    <Link
                        :href="register()"
                        class="inline-flex h-10 items-center rounded-full bg-emerald-600 px-5 text-sm font-semibold text-white transition hover:bg-emerald-700 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-600"
                    >
                        Daftar gratis
                    </Link>
                </div>
            </nav>
        </header>

        <main>
            <section id="beranda" class="relative">
                <div
                    class="absolute inset-x-0 top-0 -z-0 h-[44rem] bg-[radial-gradient(circle_at_78%_20%,rgba(16,185,129,0.15),transparent_27%),radial-gradient(circle_at_10%_50%,rgba(251,191,36,0.11),transparent_25%)] dark:bg-[radial-gradient(circle_at_78%_20%,rgba(16,185,129,0.14),transparent_27%),radial-gradient(circle_at_10%_50%,rgba(251,191,36,0.06),transparent_25%)]"
                />
                <div
                    class="relative mx-auto grid max-w-7xl items-center gap-14 px-5 py-18 sm:px-8 sm:py-24 lg:grid-cols-[1.02fr_0.98fr] lg:px-10 lg:py-28"
                >
                    <div class="max-w-2xl">
                        <div
                            class="mb-6 inline-flex items-center gap-2 rounded-full border border-emerald-200 bg-white/80 px-3.5 py-2 text-xs font-semibold text-emerald-800 shadow-sm dark:border-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-300"
                        >
                            <Sparkles class="size-3.5" aria-hidden="true" />
                            Gratis dan open source untuk TPA/TPQ Indonesia
                        </div>
                        <h1
                            class="text-4xl leading-[1.08] font-bold tracking-[-0.04em] text-balance sm:text-6xl lg:text-[4.25rem]"
                        >
                            SantriQ: kelola santri lebih mudah,
                            <span class="text-emerald-600"
                                >dampingi lebih dekat.</span
                            >
                        </h1>
                        <p
                            class="mt-6 max-w-xl text-base leading-8 text-slate-600 sm:text-lg dark:text-slate-300"
                        >
                            Dari absensi QR hingga notifikasi wali, semua
                            kebutuhan administrasi TPA/TPQ hadir dalam satu
                            platform yang sederhana dan mudah digunakan.
                        </p>
                        <p
                            class="mt-3 max-w-xl text-sm leading-6 text-slate-500 dark:text-slate-400"
                        >
                            SantriQ is a free and open source school management
                            platform for Indonesian Qur'an study centers
                            (TPA/TPQ): QR-code attendance, real-time attendance
                            notifications to parents via Telegram, learning
                            progress records, tuition billing, and student leave
                            requests.
                        </p>
                        <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                            <Link
                                :href="register()"
                                class="inline-flex h-12 items-center justify-center gap-2 rounded-full bg-emerald-600 px-6 text-sm font-semibold text-white shadow-lg shadow-emerald-900/15 transition hover:-translate-y-0.5 hover:bg-emerald-700 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-600"
                            >
                                Mulai gratis
                                <ArrowRight class="size-4" aria-hidden="true" />
                            </Link>
                            <a
                                href="#fitur"
                                class="inline-flex h-12 items-center justify-center rounded-full border border-slate-200 bg-white/70 px-6 text-sm font-semibold text-slate-700 transition hover:border-emerald-300 hover:text-emerald-700 dark:border-slate-700 dark:bg-slate-900/70 dark:text-slate-200 dark:hover:border-emerald-700 dark:hover:text-emerald-400"
                            >
                                Lihat semua fitur
                            </a>
                        </div>
                        <p
                            v-if="demoUrl"
                            class="mt-4 text-sm text-slate-500 dark:text-slate-400"
                        >
                            Ingin coba dulu?
                            <a
                                :href="demoUrl"
                                class="font-semibold text-emerald-700 transition hover:underline dark:text-emerald-400"
                            >
                                Jelajahi demo tanpa daftar &rarr;
                            </a>
                        </p>
                        <div
                            class="mt-8 flex flex-wrap gap-x-6 gap-y-2 text-sm text-slate-500 dark:text-slate-400"
                        >
                            <span class="flex items-center gap-2">
                                <Check
                                    class="size-4 text-emerald-600"
                                    aria-hidden="true"
                                />
                                Tanpa biaya langganan
                            </span>
                            <span class="flex items-center gap-2">
                                <Check
                                    class="size-4 text-emerald-600"
                                    aria-hidden="true"
                                />
                                Siap dipakai di ponsel
                            </span>
                        </div>
                    </div>

                    <div class="relative mx-auto w-full max-w-xl lg:max-w-none">
                        <div
                            class="absolute -top-10 -right-6 size-36 rounded-full bg-amber-300/25 blur-3xl"
                        />
                        <div
                            class="relative rounded-[2rem] border border-white/80 bg-white/90 p-3 shadow-2xl shadow-emerald-950/12 backdrop-blur dark:border-white/10 dark:bg-slate-900/90"
                        >
                            <div
                                class="overflow-hidden rounded-[1.45rem] border border-slate-100 bg-[#f7faf6] dark:border-slate-800 dark:bg-slate-950"
                            >
                                <div
                                    class="flex items-center justify-between border-b border-slate-200/70 bg-white px-5 py-4 dark:border-slate-800 dark:bg-slate-900"
                                >
                                    <div class="flex items-center gap-2.5">
                                        <span
                                            class="flex size-8 items-center justify-center rounded-lg bg-emerald-600 text-white"
                                        >
                                            <GraduationCap
                                                class="size-4"
                                                aria-hidden="true"
                                            />
                                        </span>
                                        <div>
                                            <p class="text-sm font-bold">
                                                TPQ Al-Hidayah
                                            </p>
                                            <p
                                                class="text-[10px] text-slate-400"
                                            >
                                                Ringkasan hari ini
                                            </p>
                                        </div>
                                    </div>
                                    <span
                                        class="flex size-8 items-center justify-center rounded-full bg-slate-100 dark:bg-slate-800"
                                    >
                                        <Bell
                                            class="size-3.5 text-slate-500"
                                            aria-hidden="true"
                                        />
                                    </span>
                                </div>

                                <div class="grid gap-4 p-5 sm:grid-cols-2">
                                    <div
                                        class="rounded-2xl bg-emerald-600 p-5 text-white shadow-lg shadow-emerald-900/15 sm:row-span-2"
                                    >
                                        <div
                                            class="flex items-center justify-between"
                                        >
                                            <span
                                                class="text-xs font-medium text-emerald-100"
                                            >
                                                Kehadiran hari ini
                                            </span>
                                            <Users
                                                class="size-5"
                                                aria-hidden="true"
                                            />
                                        </div>
                                        <p class="mt-6 text-4xl font-bold">
                                            86
                                        </p>
                                        <p
                                            class="mt-1 text-xs text-emerald-100"
                                        >
                                            dari 92 santri aktif
                                        </p>
                                        <div
                                            class="mt-5 h-1.5 overflow-hidden rounded-full bg-emerald-800/40"
                                        >
                                            <div
                                                class="h-full w-[93%] rounded-full bg-amber-300"
                                            />
                                        </div>
                                        <p
                                            class="mt-2 text-[10px] text-emerald-100"
                                        >
                                            93% tingkat kehadiran
                                        </p>
                                    </div>

                                    <div
                                        class="rounded-2xl border border-slate-100 bg-white p-4 dark:border-slate-800 dark:bg-slate-900"
                                    >
                                        <div class="flex items-center gap-3">
                                            <span
                                                class="flex size-9 items-center justify-center rounded-xl bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-400"
                                            >
                                                <Clock3
                                                    class="size-4"
                                                    aria-hidden="true"
                                                />
                                            </span>
                                            <div>
                                                <p
                                                    class="text-xs text-slate-400"
                                                >
                                                    Datang tepat waktu
                                                </p>
                                                <p class="text-lg font-bold">
                                                    81 santri
                                                </p>
                                            </div>
                                        </div>
                                    </div>

                                    <div
                                        class="rounded-2xl border border-slate-100 bg-white p-4 dark:border-slate-800 dark:bg-slate-900"
                                    >
                                        <div class="flex items-center gap-3">
                                            <span
                                                class="flex size-9 items-center justify-center rounded-xl bg-violet-100 text-violet-700 dark:bg-violet-950 dark:text-violet-400"
                                            >
                                                <BookOpenCheck
                                                    class="size-4"
                                                    aria-hidden="true"
                                                />
                                            </span>
                                            <div>
                                                <p
                                                    class="text-xs text-slate-400"
                                                >
                                                    Pencapaian baru
                                                </p>
                                                <p class="text-lg font-bold">
                                                    12 catatan
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div
                            class="absolute -right-3 -bottom-9 flex max-w-[15rem] items-start gap-3 rounded-2xl border border-emerald-100 bg-white p-3.5 shadow-xl sm:-right-7 dark:border-emerald-900 dark:bg-slate-900"
                        >
                            <span
                                class="flex size-9 shrink-0 items-center justify-center rounded-full bg-[#2aabee] text-white"
                            >
                                <MessageCircleMore
                                    class="size-4"
                                    aria-hidden="true"
                                />
                            </span>
                            <div>
                                <p class="text-xs font-bold">SantriQ Bot</p>
                                <p
                                    class="mt-0.5 text-[11px] leading-4 text-slate-500 dark:text-slate-400"
                                >
                                    Ahmad telah tiba pukul 15.42 WIB.
                                </p>
                            </div>
                        </div>

                        <div
                            class="absolute -bottom-5 -left-3 hidden items-center gap-2 rounded-2xl border border-slate-100 bg-white px-3.5 py-3 shadow-xl sm:flex dark:border-slate-800 dark:bg-slate-900"
                        >
                            <QrCode
                                class="size-8 text-emerald-600"
                                aria-hidden="true"
                            />
                            <div>
                                <p class="text-[10px] text-slate-400">
                                    Pemindaian
                                </p>
                                <p
                                    class="text-xs font-bold text-emerald-700 dark:text-emerald-400"
                                >
                                    Berhasil tercatat
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section id="fitur" class="py-20 sm:py-28">
                <div class="mx-auto max-w-7xl px-5 sm:px-8 lg:px-10">
                    <div class="mx-auto max-w-2xl text-center">
                        <p
                            class="text-sm font-bold tracking-widest text-emerald-600 uppercase"
                        >
                            Satu platform
                        </p>
                        <h2
                            class="mt-3 text-3xl font-bold tracking-tight text-balance sm:text-4xl"
                        >
                            Semua yang dibutuhkan untuk mengelola TPA/TPQ
                        </h2>
                        <p
                            class="mt-4 leading-7 text-slate-600 dark:text-slate-300"
                        >
                            Kurangi pekerjaan administratif agar pengajar bisa
                            lebih fokus mendampingi tumbuh kembang santri.
                        </p>
                    </div>

                    <div class="mt-12 grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                        <article
                            v-for="feature in features"
                            :key="feature.title"
                            class="group rounded-3xl border border-emerald-950/8 bg-white p-6 transition hover:-translate-y-1 hover:border-emerald-200 hover:shadow-xl hover:shadow-emerald-950/5 dark:border-white/10 dark:bg-slate-900 dark:hover:border-emerald-800"
                        >
                            <span
                                class="flex size-11 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-700 transition group-hover:bg-emerald-600 group-hover:text-white dark:bg-emerald-950 dark:text-emerald-400"
                            >
                                <component
                                    :is="feature.icon"
                                    class="size-5"
                                    aria-hidden="true"
                                />
                            </span>
                            <h3 class="mt-5 text-lg font-bold">
                                {{ feature.title }}
                            </h3>
                            <p
                                class="mt-2 text-sm leading-6 text-slate-600 dark:text-slate-400"
                            >
                                {{ feature.description }}
                            </p>
                        </article>
                    </div>
                </div>
            </section>

            <section
                id="cara-kerja"
                class="border-y border-emerald-950/5 bg-emerald-950 py-20 text-white sm:py-28 dark:border-white/10"
            >
                <div class="mx-auto max-w-7xl px-5 sm:px-8 lg:px-10">
                    <div
                        class="grid gap-12 lg:grid-cols-[0.72fr_1.28fr] lg:gap-20"
                    >
                        <div>
                            <p
                                class="text-sm font-bold tracking-widest text-emerald-300 uppercase"
                            >
                                Mulai dengan mudah
                            </p>
                            <h2
                                class="mt-3 text-3xl font-bold tracking-tight text-balance sm:text-4xl"
                            >
                                Siap digunakan dalam tiga langkah
                            </h2>
                            <p class="mt-4 leading-7 text-emerald-100/70">
                                Tidak perlu instalasi rumit. Buka melalui
                                browser di perangkat yang sudah Anda miliki.
                            </p>
                        </div>

                        <ol class="grid gap-4 sm:grid-cols-3">
                            <li
                                v-for="step in steps"
                                :key="step.number"
                                class="rounded-3xl border border-white/10 bg-white/6 p-6"
                            >
                                <span
                                    class="font-mono text-sm font-bold text-emerald-300"
                                >
                                    {{ step.number }}
                                </span>
                                <h3 class="mt-10 text-lg font-bold">
                                    {{ step.title }}
                                </h3>
                                <p
                                    class="mt-2 text-sm leading-6 text-emerald-100/65"
                                >
                                    {{ step.description }}
                                </p>
                            </li>
                        </ol>
                    </div>
                </div>
            </section>

            <section id="tentang" class="py-20 sm:py-28">
                <div
                    class="mx-auto grid max-w-7xl items-center gap-12 px-5 sm:px-8 lg:grid-cols-2 lg:px-10"
                >
                    <div
                        class="relative overflow-hidden rounded-[2rem] bg-amber-100 p-8 sm:p-12 dark:bg-amber-950/40"
                    >
                        <div
                            class="absolute -top-16 -right-16 size-52 rounded-full border-[2rem] border-amber-200/70 dark:border-amber-900/50"
                        />
                        <div
                            class="relative grid min-h-72 place-items-center rounded-3xl border border-amber-200 bg-[#fffcf3] p-8 text-center shadow-sm dark:border-amber-900 dark:bg-slate-900"
                        >
                            <span
                                class="flex size-18 items-center justify-center rounded-3xl bg-emerald-600 text-white shadow-xl shadow-emerald-900/15"
                            >
                                <GraduationCap
                                    class="size-9"
                                    aria-hidden="true"
                                />
                            </span>
                            <div class="mt-5">
                                <p class="text-3xl font-bold tracking-tight">
                                    SantriQ
                                </p>
                                <p
                                    class="mt-2 text-sm text-slate-500 dark:text-slate-400"
                                >
                                    Tumbuh bersama, mengaji lebih terarah.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div>
                        <p
                            class="text-sm font-bold tracking-widest text-emerald-600 uppercase"
                        >
                            Dibuat untuk umat
                        </p>
                        <h2
                            class="mt-3 text-3xl font-bold tracking-tight text-balance sm:text-4xl"
                        >
                            Teknologi yang sederhana, manfaat yang nyata
                        </h2>
                        <p
                            class="mt-5 leading-7 text-slate-600 dark:text-slate-300"
                        >
                            SantriQ dibangun sebagai platform gratis dan open
                            source agar lembaga pendidikan Al-Qur'an dari
                            berbagai skala dapat beralih ke pengelolaan digital
                            tanpa beban biaya.
                        </p>
                        <div class="mt-7 grid gap-3 sm:grid-cols-2">
                            <span
                                class="flex items-center gap-2 text-sm font-semibold text-slate-700 dark:text-slate-200"
                            >
                                <Check
                                    class="size-4 text-emerald-600"
                                    aria-hidden="true"
                                />
                                Mudah dipelajari
                            </span>
                            <span
                                class="flex items-center gap-2 text-sm font-semibold text-slate-700 dark:text-slate-200"
                            >
                                <Check
                                    class="size-4 text-emerald-600"
                                    aria-hidden="true"
                                />
                                Terbuka dan transparan
                            </span>
                            <span
                                class="flex items-center gap-2 text-sm font-semibold text-slate-700 dark:text-slate-200"
                            >
                                <Check
                                    class="size-4 text-emerald-600"
                                    aria-hidden="true"
                                />
                                Cocok untuk berbagai skala
                            </span>
                            <span
                                class="flex items-center gap-2 text-sm font-semibold text-slate-700 dark:text-slate-200"
                            >
                                <Check
                                    class="size-4 text-emerald-600"
                                    aria-hidden="true"
                                />
                                Data tetap milik lembaga
                            </span>
                        </div>
                    </div>
                </div>
            </section>

            <section class="px-5 pb-20 sm:px-8 sm:pb-28 lg:px-10">
                <div
                    class="relative mx-auto max-w-7xl overflow-hidden rounded-[2rem] bg-emerald-600 px-6 py-14 text-center text-white shadow-2xl shadow-emerald-950/15 sm:px-12 sm:py-18"
                >
                    <div
                        class="absolute -top-24 -left-16 size-64 rounded-full border-[2.5rem] border-white/8"
                    />
                    <div
                        class="absolute -right-20 -bottom-28 size-72 rounded-full border-[3rem] border-emerald-400/50"
                    />
                    <div class="relative mx-auto max-w-2xl">
                        <h2
                            class="text-3xl font-bold tracking-tight text-balance sm:text-4xl"
                        >
                            Saatnya administrasi lebih rapi dan wali lebih
                            tenang
                        </h2>
                        <p class="mt-4 leading-7 text-emerald-50/85">
                            Bergabunglah dan mulai kelola lembaga Anda bersama
                            SantriQ.
                        </p>
                        <Link
                            :href="register()"
                            class="mt-7 inline-flex h-12 items-center gap-2 rounded-full bg-white px-6 text-sm font-bold text-emerald-700 transition hover:-translate-y-0.5 hover:bg-emerald-50 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white"
                        >
                            Daftar sekarang
                            <ArrowRight class="size-4" aria-hidden="true" />
                        </Link>
                    </div>
                </div>
            </section>
        </main>

        <footer class="border-t border-emerald-950/8 dark:border-white/10">
            <div
                class="mx-auto flex max-w-7xl flex-col gap-4 px-5 py-8 text-sm text-slate-500 sm:flex-row sm:items-center sm:justify-between sm:px-8 lg:px-10 dark:text-slate-400"
            >
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
                <div class="flex flex-col gap-3 sm:items-end">
                    <nav aria-label="Tautan legal" class="flex gap-5">
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
                    <p>Platform manajemen TPA/TPQ gratis dan open source.</p>
                </div>
            </div>
        </footer>
    </div>
</template>
