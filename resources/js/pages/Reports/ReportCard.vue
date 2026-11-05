<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import LearningProgressSummary from '@/components/LearningProgress.vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import type { LearningProgress } from '@/lib/learningProgress';
import { student as guardianStudent } from '@/routes/guardian/portal';
import { destroy, show, store } from '@/routes/report-cards';
import { index as reportsIndex } from '@/routes/reports';

interface ReportSummary {
    id: number;
    start_date: string;
    end_date: string;
}

const props = defineProps<{
    snapshot: {
        tenant_name: string;
        student: {
            id: number;
            name: string;
            nis: string;
            classroom: string | null;
        };
        start_date: string;
        end_date: string;
        attendance: Record<string, number>;
        achievements: Array<
            LearningProgress & {
                achieved_at: string;
                category: string;
                title: string;
                score: number | null;
                note: string | null;
            }
        >;
        publisher_name?: string;
    };
    snapshotHash: string | null;
    reportCard: {
        id: number;
        teacher_note: string | null;
        published_at: string;
        published_date: string;
    } | null;
    isGuardian: boolean;
    previousReports: ReportSummary[];
}>();

const form = useForm({
    start_date: props.snapshot.start_date,
    end_date: props.snapshot.end_date,
    teacher_note: '',
    snapshot_hash: props.snapshotHash,
});

function publish() {
    form.post(store.url({ student: props.snapshot.student.id }));
}

function revoke() {
    if (
        props.reportCard &&
        confirm(
            'Cabut rapor ini? Wali tidak dapat membukanya lagi. Anda dapat menerbitkan rapor yang sudah diperbaiki.',
        )
    ) {
        router.delete(destroy.url({ reportCard: props.reportCard.id }));
    }
}

function printReport() {
    window.print();
}

function formatDate(date: string) {
    return new Intl.DateTimeFormat('id-ID', {
        dateStyle: 'long',
        timeZone: 'UTC',
    }).format(new Date(`${date}T00:00:00Z`));
}
</script>

<template>
    <main class="report-page mx-auto max-w-4xl space-y-6 p-4 sm:p-8">
        <Head :title="`Rapor ${snapshot.student.name}`" />
        <nav
            class="report-controls flex flex-wrap items-center justify-between gap-3"
            aria-label="Navigasi rapor"
        >
            <Link
                :href="
                    isGuardian
                        ? guardianStudent({ student: snapshot.student.id })
                        : reportsIndex()
                "
                class="inline-flex min-h-11 items-center text-sm underline underline-offset-4 focus-visible:ring-2 focus-visible:ring-ring"
                >Kembali ke {{ isGuardian ? 'data anak' : 'laporan' }}</Link
            >
            <div v-if="reportCard" class="flex flex-wrap gap-2">
                <Button class="min-h-11" @click="printReport"
                    >Cetak / Simpan PDF</Button
                >
                <Button
                    v-if="!isGuardian"
                    variant="outline"
                    class="min-h-11"
                    @click="revoke"
                    >Cabut rapor</Button
                >
            </div>
        </nav>

        <section
            v-if="!reportCard"
            class="report-controls space-y-3 rounded-md border bg-muted/30 p-4"
            aria-label="Petunjuk penerbitan"
        >
            <h1 class="text-xl font-semibold">Pratinjau rapor</h1>
            <p class="text-sm text-muted-foreground">
                Periksa kehadiran dan perkembangan belajar di bawah. Tambahkan
                catatan, lalu terbitkan agar wali dapat membuka rapor melalui
                portal. Data rapor terbit tidak ikut berubah saat catatan harian
                diedit.
            </p>
            <div v-if="previousReports.length" class="space-y-2">
                <p class="text-sm font-medium">Rapor yang sudah terbit</p>
                <ul class="space-y-1 text-sm">
                    <li v-for="report in previousReports" :key="report.id">
                        <Link
                            :href="show({ reportCard: report.id })"
                            class="inline-flex min-h-11 items-center underline underline-offset-4"
                            >{{ formatDate(report.start_date) }} sampai
                            {{ formatDate(report.end_date) }}</Link
                        >
                    </li>
                </ul>
            </div>
        </section>

        <article
            class="report-document space-y-6 rounded-md border bg-card p-4 sm:p-8"
        >
            <header class="space-y-2 border-b pb-5">
                <p class="font-medium">{{ snapshot.tenant_name }}</p>
                <h2 class="text-2xl font-semibold">
                    Rapor Perkembangan Santri
                </h2>
                <p class="text-sm text-muted-foreground">
                    {{ formatDate(snapshot.start_date) }} sampai
                    {{ formatDate(snapshot.end_date) }}
                </p>
            </header>
            <dl class="grid gap-4 sm:grid-cols-3">
                <div>
                    <dt class="text-sm text-muted-foreground">Nama santri</dt>
                    <dd class="font-medium break-words">
                        {{ snapshot.student.name }}
                    </dd>
                </div>
                <div>
                    <dt class="text-sm text-muted-foreground">NIS</dt>
                    <dd class="break-words">{{ snapshot.student.nis }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-muted-foreground">Kelas</dt>
                    <dd>
                        {{ snapshot.student.classroom ?? 'Belum ditentukan' }}
                    </dd>
                </div>
            </dl>

            <section class="space-y-3" aria-labelledby="attendance-title">
                <h3 id="attendance-title" class="font-semibold">Kehadiran</h3>
                <dl class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                    <div
                        v-for="(count, status) in snapshot.attendance"
                        :key="status"
                        class="rounded-md border p-3"
                    >
                        <dt class="text-sm capitalize">{{ status }}</dt>
                        <dd class="text-xl font-semibold">{{ count }}</dd>
                    </div>
                </dl>
                <p class="text-xs text-muted-foreground">
                    Rekap berdasarkan presensi yang dicatat. Hari tanpa catatan
                    tidak dihitung sebagai alpa.
                </p>
            </section>

            <section class="space-y-3" aria-labelledby="progress-title">
                <h3 id="progress-title" class="font-semibold">
                    Perkembangan belajar
                </h3>
                <p
                    v-if="!snapshot.achievements.length"
                    class="text-sm text-muted-foreground"
                >
                    Belum ada catatan perkembangan pada periode ini.
                </p>
                <ol v-else class="divide-y rounded-md border px-4">
                    <li
                        v-for="(achievement, index) in snapshot.achievements"
                        :key="index"
                        class="report-entry space-y-2 py-4"
                    >
                        <div
                            class="flex flex-wrap justify-between gap-2 text-sm"
                        >
                            <p class="font-medium">
                                {{ achievement.category }} ·
                                {{ achievement.title }}
                            </p>
                            <p>{{ formatDate(achievement.achieved_at) }}</p>
                        </div>
                        <LearningProgressSummary :progress="achievement" />
                        <p v-if="achievement.score !== null" class="text-sm">
                            Nilai: {{ achievement.score }}
                        </p>
                        <p
                            v-if="achievement.note"
                            class="text-sm break-words whitespace-pre-wrap"
                        >
                            {{ achievement.note }}
                        </p>
                    </li>
                </ol>
            </section>

            <section
                v-if="reportCard"
                class="space-y-3 border-t pt-5"
                aria-labelledby="teacher-note-title"
            >
                <h3 id="teacher-note-title" class="font-semibold">
                    Catatan pengajar
                </h3>
                <p class="text-sm break-words whitespace-pre-wrap">
                    {{
                        reportCard.teacher_note || 'Tidak ada catatan tambahan.'
                    }}
                </p>
                <p class="pt-4 text-sm">
                    Diterbitkan oleh
                    {{ snapshot.publisher_name ?? 'Pengajar' }} pada
                    {{ formatDate(reportCard.published_date) }}.
                </p>
            </section>
        </article>

        <form
            v-if="!reportCard"
            class="report-controls space-y-4 rounded-md border p-4"
            @submit.prevent="publish"
        >
            <div class="space-y-2">
                <Label for="teacher_note"
                    >Catatan pengajar untuk wali (opsional)</Label
                >
                <textarea
                    id="teacher_note"
                    v-model="form.teacher_note"
                    rows="4"
                    maxlength="5000"
                    class="w-full rounded-md border border-input bg-background p-3 text-sm focus-visible:ring-2 focus-visible:ring-ring"
                    placeholder="Perkembangan yang patut diapresiasi dan latihan yang perlu didampingi di rumah"
                ></textarea>
                <InputError :message="form.errors.teacher_note" />
            </div>
            <InputError :message="form.errors.start_date" />
            <InputError :message="form.errors.end_date" />
            <InputError :message="form.errors.snapshot_hash" />
            <Button
                type="submit"
                class="min-h-11 w-full sm:w-auto"
                :disabled="form.processing"
                >{{
                    form.processing
                        ? 'Menerbitkan...'
                        : 'Terbitkan rapor untuk wali'
                }}</Button
            >
        </form>
        <p
            v-if="reportCard"
            class="report-controls text-sm text-muted-foreground"
        >
            Gunakan tombol cetak, lalu pilih printer atau “Simpan sebagai PDF”.
            Wali dapat membuka rapor terbit dari halaman anak di portal.
        </p>
    </main>
</template>

<style>
@media print {
    @page {
        size: A4;
        margin: 15mm;
    }
    html,
    body {
        background: white !important;
        color: black !important;
        color-scheme: light;
    }
    .report-controls,
    [data-sonner-toaster] {
        display: none !important;
    }
    .report-page {
        max-width: none;
        margin: 0;
        padding: 0;
    }
    .report-document {
        border: 0;
        padding: 0;
        background: white;
        color: black;
    }
    .report-document .text-muted-foreground {
        color: #444;
    }
    .report-entry {
        break-inside: avoid;
    }
}
</style>
