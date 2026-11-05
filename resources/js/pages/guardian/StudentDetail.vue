<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import LearningProgressSummary from '@/components/LearningProgress.vue';
import type { LearningProgress } from '@/lib/learningProgress';
import { index } from '@/routes/guardian/portal';
import { show } from '@/routes/guardian/portal/report-cards';

defineProps<{
    student: { id: number; name: string; nis: string };
    attendances: Array<{
        date: string;
        checked_in_at: string | null;
        checked_out_at: string | null;
        status: string;
    }>;
    reportCards: Array<{ id: number; start_date: string; end_date: string }>;
    achievements: Array<
        LearningProgress & {
            category: string;
            title: string;
            note: string | null;
            score: number | null;
            achieved_at: string;
        }
    >;
}>();
</script>

<template>
    <main class="mx-auto max-w-2xl space-y-6 p-4">
        <Head :title="student.name" />
        <div class="flex items-center gap-2">
            <Link
                :href="index()"
                class="text-sm text-muted-foreground hover:underline"
                >&larr; Kembali</Link
            >
        </div>

        <h1 class="text-xl font-semibold">
            {{ student.name }} ({{ student.nis }})
        </h1>

        <section class="space-y-2">
            <h2 class="font-medium">Rapor Terbit</h2>
            <p v-if="!reportCards.length" class="text-sm text-muted-foreground">
                Belum ada rapor yang diterbitkan pengajar.
            </p>
            <ul v-else class="space-y-2">
                <li v-for="report in reportCards" :key="report.id">
                    <Link
                        :href="show({ reportCard: report.id })"
                        class="flex min-h-11 items-center rounded-md border p-3 text-sm underline underline-offset-4 focus-visible:ring-2 focus-visible:ring-ring"
                        >Rapor {{ report.start_date }} sampai
                        {{ report.end_date }}</Link
                    >
                </li>
            </ul>
        </section>

        <section class="space-y-2">
            <h2 class="font-medium">Kehadiran Terbaru</h2>
            <ul class="space-y-1 text-sm">
                <li
                    v-for="a in attendances"
                    :key="a.date"
                    class="flex justify-between rounded border p-2"
                >
                    <span>{{ a.date }}</span>
                    <span class="font-medium capitalize">{{ a.status }}</span>
                </li>
            </ul>
        </section>

        <section class="space-y-2">
            <h2 class="font-medium">Pencapaian Terbaru</h2>
            <ul class="space-y-1 text-sm">
                <li
                    v-for="(ach, i) in achievements"
                    :key="i"
                    class="space-y-1 rounded border p-2"
                >
                    <div class="flex flex-wrap justify-between gap-2">
                        <span class="font-medium"
                            >{{ ach.category }}: {{ ach.title }}</span
                        >
                        <span
                            v-if="ach.score !== null"
                            class="font-semibold text-emerald-600"
                            >{{ ach.score }}</span
                        >
                    </div>
                    <LearningProgressSummary :progress="ach" />
                    <p v-if="ach.note" class="text-xs text-muted-foreground">
                        {{ ach.note }}
                    </p>
                    <p class="text-xs text-muted-foreground">
                        {{ ach.achieved_at }}
                    </p>
                </li>
            </ul>
        </section>
    </main>
</template>
