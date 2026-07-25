<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    Award,
    CalendarCheck,
    CreditCard,
    FileText,
    QrCode,
    Users,
} from '@lucide/vue';
import AppLayout from '@/layouts/AppLayout.vue';

interface Stats {
    total_students: number;
    today_attendance_count: number;
    unpaid_invoices_count: number;
    pending_leaves_count: number;
}

interface Student {
    id: number;
    name: string;
    nis: string;
}

interface RecentAttendance {
    id: number;
    date: string;
    checked_in_at: string | null;
    checked_out_at: string | null;
    status: string;
    student?: Student;
}

interface RecentAchievement {
    id: number;
    category: string;
    title: string;
    score: number | null;
    achieved_at: string;
    student?: Student;
}

defineProps<{
    stats: Stats;
    recent_attendances: RecentAttendance[];
    recent_achievements: RecentAchievement[];
}>();

defineOptions({
    layout: AppLayout,
});

function formatTime(str: string | null): string {
    if (!str) {
        return '-';
    }

    try {
        return new Date(str).toLocaleTimeString('id-ID', {
            hour: '2-digit',
            minute: '2-digit',
        });
    } catch {
        return '-';
    }
}
</script>

<template>
    <Head title="Dashboard SantriQ" />

    <div class="flex flex-col gap-6 p-6">
        <!-- Header Hero -->
        <div
            class="rounded-2xl bg-gradient-to-br from-emerald-600 to-emerald-700 p-6 text-white shadow-md"
        >
            <div
                class="flex flex-col items-start justify-between gap-4 md:flex-row md:items-center"
            >
                <div>
                    <h1 class="text-2xl font-extrabold tracking-tight">
                        Selamat Datang di SantriQ
                    </h1>
                    <p class="mt-1 text-sm text-emerald-100">
                        Platform Manajemen TPA/TPQ Berbasis QR Code & Notifikasi
                        Telegram Realtime.
                    </p>
                </div>
                <Link
                    href="/scan"
                    class="inline-flex items-center gap-2 rounded-xl border border-white/30 bg-white/20 px-5 py-2.5 font-semibold text-white backdrop-blur-md transition-all hover:bg-white/30"
                >
                    <QrCode class="h-5 w-5" />
                    <span>Mulai Pindai Presensi</span>
                </Link>
            </div>
        </div>

        <!-- Metrics Cards -->
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div
                class="rounded-2xl border bg-card p-5 shadow-sm transition-shadow hover:shadow-md"
            >
                <div class="flex items-center justify-between">
                    <span
                        class="text-xs font-semibold text-muted-foreground uppercase"
                        >Total Santri Aktif</span
                    >
                    <div
                        class="rounded-lg bg-emerald-100 p-2 text-emerald-600 dark:bg-emerald-950 dark:text-emerald-400"
                    >
                        <Users class="h-5 w-5" />
                    </div>
                </div>
                <div class="mt-3 text-3xl font-extrabold">
                    {{ stats.total_students }}
                </div>
                <p class="mt-1 text-xs text-muted-foreground">
                    Santri terdaftar di lembaga
                </p>
            </div>

            <div
                class="rounded-2xl border bg-card p-5 shadow-sm transition-shadow hover:shadow-md"
            >
                <div class="flex items-center justify-between">
                    <span
                        class="text-xs font-semibold text-muted-foreground uppercase"
                        >Hadir Hari Ini</span
                    >
                    <div
                        class="rounded-lg bg-blue-100 p-2 text-blue-600 dark:bg-blue-950 dark:text-blue-400"
                    >
                        <CalendarCheck class="h-5 w-5" />
                    </div>
                </div>
                <div class="mt-3 text-3xl font-extrabold">
                    {{ stats.today_attendance_count }}
                </div>
                <p class="mt-1 text-xs text-muted-foreground">
                    Tercatat presensi masuk
                </p>
            </div>

            <div
                class="rounded-2xl border bg-card p-5 shadow-sm transition-shadow hover:shadow-md"
            >
                <div class="flex items-center justify-between">
                    <span
                        class="text-xs font-semibold text-muted-foreground uppercase"
                        >Tagihan Belum Lunas</span
                    >
                    <div
                        class="rounded-lg bg-amber-100 p-2 text-amber-600 dark:bg-amber-950 dark:text-amber-400"
                    >
                        <CreditCard class="h-5 w-5" />
                    </div>
                </div>
                <div class="mt-3 text-3xl font-extrabold text-amber-600">
                    {{ stats.unpaid_invoices_count }}
                </div>
                <p class="mt-1 text-xs text-muted-foreground">
                    Tagihan SPP belum terbayar
                </p>
            </div>

            <div
                class="rounded-2xl border bg-card p-5 shadow-sm transition-shadow hover:shadow-md"
            >
                <div class="flex items-center justify-between">
                    <span
                        class="text-xs font-semibold text-muted-foreground uppercase"
                        >Pengajuan Izin Pending</span
                    >
                    <div
                        class="rounded-lg bg-rose-100 p-2 text-rose-600 dark:bg-rose-950 dark:text-rose-400"
                    >
                        <FileText class="h-5 w-5" />
                    </div>
                </div>
                <div class="mt-3 text-3xl font-extrabold text-rose-600">
                    {{ stats.pending_leaves_count }}
                </div>
                <p class="mt-1 text-xs text-muted-foreground">
                    Membutuhkan persetujuan
                </p>
            </div>
        </div>

        <!-- Recent Activity Grids -->
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            <!-- Recent Attendances -->
            <div class="rounded-xl border bg-card p-5">
                <div class="mb-4 flex items-center justify-between">
                    <h3 class="flex items-center gap-2 text-base font-bold">
                        <CalendarCheck class="h-4 w-4 text-emerald-600" />
                        Presensi Terkini Hari Ini
                    </h3>
                    <Link
                        href="/attendance"
                        class="text-xs font-semibold text-primary hover:underline"
                    >
                        Lihat Semua →
                    </Link>
                </div>

                <div class="divide-y">
                    <div
                        v-for="att in recent_attendances"
                        :key="att.id"
                        class="flex items-center justify-between py-3 text-sm"
                    >
                        <div>
                            <p class="font-semibold">{{ att.student?.name }}</p>
                            <p class="font-mono text-xs text-muted-foreground">
                                NIS: {{ att.student?.nis }}
                            </p>
                        </div>
                        <div class="text-right">
                            <span
                                class="inline-flex items-center rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-medium text-emerald-800"
                            >
                                {{
                                    att.checked_out_at
                                        ? 'Pulang ' +
                                          formatTime(att.checked_out_at)
                                        : 'Masuk ' +
                                          formatTime(att.checked_in_at)
                                }}
                            </span>
                        </div>
                    </div>

                    <div
                        v-if="recent_attendances.length === 0"
                        class="py-6 text-center text-xs text-muted-foreground"
                    >
                        Belum ada presensi tercatat hari ini.
                    </div>
                </div>
            </div>

            <!-- Recent Achievements -->
            <div class="rounded-xl border bg-card p-5">
                <div class="mb-4 flex items-center justify-between">
                    <h3 class="flex items-center gap-2 text-base font-bold">
                        <Award class="h-4 w-4 text-amber-500" />
                        Pencapaian Terbaru
                    </h3>
                    <Link
                        href="/achievements"
                        class="text-xs font-semibold text-primary hover:underline"
                    >
                        Lihat Semua →
                    </Link>
                </div>

                <div class="divide-y">
                    <div
                        v-for="ach in recent_achievements"
                        :key="ach.id"
                        class="flex items-center justify-between py-3 text-sm"
                    >
                        <div>
                            <p class="font-semibold">{{ ach.student?.name }}</p>
                            <p class="text-xs text-muted-foreground">
                                {{ ach.category }}: {{ ach.title }}
                            </p>
                        </div>
                        <div class="text-right">
                            <span
                                v-if="ach.score"
                                class="font-mono text-xs font-bold text-primary"
                            >
                                Nilai: {{ ach.score }}
                            </span>
                        </div>
                    </div>

                    <div
                        v-if="recent_achievements.length === 0"
                        class="py-6 text-center text-xs text-muted-foreground"
                    >
                        Belum ada catatan pencapaian terbaru.
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
