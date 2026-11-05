<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/AppLayout.vue';
import { preview } from '@/routes/report-cards';
import { index, exportCsv as exportCsvRoute } from '@/routes/reports';

interface Classroom {
    id: number;
    name: string;
}

interface RekapItem {
    student_id: number;
    nis: string;
    name: string;
    classroom_name: string;
    hadir: number;
    izin: number;
    sakit: number;
    alpa: number;
    total: number;
}

const props = defineProps<{
    rekap: RekapItem[];
    classrooms: Classroom[];
    filters: {
        start_date: string;
        end_date: string;
        classroom_id?: string;
    };
}>();

defineOptions({
    layout: AppLayout,
});

const startDate = ref(props.filters.start_date);
const endDate = ref(props.filters.end_date);
const selectedClassroom = ref(props.filters.classroom_id || '');

function applyFilters() {
    router.get(
        index.url(),
        {
            start_date: startDate.value,
            end_date: endDate.value,
            classroom_id: selectedClassroom.value,
        },
        { preserveState: true },
    );
}

function exportCsv() {
    window.location.href = exportCsvRoute.url(undefined, {
        query: {
            start_date: startDate.value,
            end_date: endDate.value,
            classroom_id: selectedClassroom.value,
        },
    });
}
</script>

<template>
    <div class="flex flex-col gap-6 p-4 sm:p-6">
        <Head title="Laporan & Rapor Santri" />
        <div
            class="flex flex-col items-start gap-4 sm:flex-row sm:items-center sm:justify-between"
        >
            <div>
                <h1 class="text-2xl font-bold tracking-tight">
                    Laporan & Rapor Santri
                </h1>
                <p class="text-sm text-muted-foreground">
                    Rekap agregat statistik kehadiran per santri per rentang
                    tanggal. Tinjau dan terbitkan rapor perkembangan untuk wali.
                </p>
            </div>
            <Button
                variant="outline"
                class="w-full sm:w-auto"
                @click="exportCsv"
            >
                Ekspor CSV
            </Button>
        </div>

        <!-- Filters -->
        <div
            class="flex flex-wrap items-center gap-4 rounded-lg border bg-muted/30 p-4"
        >
            <div class="flex items-center gap-2">
                <label
                    for="report-start"
                    class="text-xs font-medium text-muted-foreground"
                    >Dari:</label
                >
                <Input
                    id="report-start"
                    type="date"
                    v-model="startDate"
                    class="min-h-11 w-40"
                />
            </div>
            <div class="flex items-center gap-2">
                <label
                    for="report-end"
                    class="text-xs font-medium text-muted-foreground"
                    >Sampai:</label
                >
                <Input
                    id="report-end"
                    type="date"
                    v-model="endDate"
                    class="min-h-11 w-40"
                />
            </div>
            <div>
                <select
                    v-model="selectedClassroom"
                    aria-label="Kelas"
                    class="flex h-11 w-44 rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm focus-visible:ring-2 focus-visible:ring-ring"
                >
                    <option value="">Semua Kelas</option>
                    <option v-for="c in classrooms" :key="c.id" :value="c.id">
                        {{ c.name }}
                    </option>
                </select>
            </div>
            <Button
                variant="secondary"
                size="sm"
                class="min-h-11"
                @click="applyFilters"
            >
                Tampilkan Rekap
            </Button>
        </div>

        <!-- Table Rekap -->
        <div class="overflow-x-auto rounded-md border bg-card">
            <table class="w-full text-left text-sm">
                <thead
                    class="border-b bg-muted/50 text-xs font-medium text-muted-foreground uppercase"
                >
                    <tr>
                        <th class="px-4 py-3">NIS</th>
                        <th class="px-4 py-3">Nama Santri</th>
                        <th class="px-4 py-3">Kelas</th>
                        <th class="px-4 py-3 text-center text-green-700">
                            Hadir
                        </th>
                        <th class="px-4 py-3 text-center text-blue-700">
                            Izin
                        </th>
                        <th class="px-4 py-3 text-center text-amber-700">
                            Sakit
                        </th>
                        <th class="px-4 py-3 text-center text-red-700">Alpa</th>
                        <th class="px-4 py-3 text-center">Total Presensi</th>
                        <th class="px-4 py-3">Rapor</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <tr
                        v-for="row in rekap"
                        :key="row.student_id"
                        class="hover:bg-accent/60"
                    >
                        <td class="px-4 py-3 font-mono font-medium">
                            {{ row.nis }}
                        </td>
                        <td class="px-4 py-3 font-medium">{{ row.name }}</td>
                        <td class="px-4 py-3 text-muted-foreground">
                            {{ row.classroom_name }}
                        </td>
                        <td
                            class="px-4 py-3 text-center font-bold text-green-700"
                        >
                            {{ row.hadir }}
                        </td>
                        <td
                            class="px-4 py-3 text-center font-bold text-blue-700"
                        >
                            {{ row.izin }}
                        </td>
                        <td
                            class="px-4 py-3 text-center font-bold text-amber-700"
                        >
                            {{ row.sakit }}
                        </td>
                        <td
                            class="px-4 py-3 text-center font-bold text-red-700"
                        >
                            {{ row.alpa }}
                        </td>
                        <td class="px-4 py-3 text-center font-mono font-bold">
                            {{ row.total }} Hari
                        </td>
                        <td class="px-4 py-3">
                            <Link
                                :href="
                                    preview(
                                        { student: row.student_id },
                                        {
                                            query: {
                                                start_date: filters.start_date,
                                                end_date: filters.end_date,
                                            },
                                        },
                                    )
                                "
                                class="inline-flex min-h-11 items-center whitespace-nowrap underline underline-offset-4 focus-visible:ring-2 focus-visible:ring-ring"
                                >Tinjau rapor<span class="sr-only">
                                    {{ row.name }}</span
                                ></Link
                            >
                        </td>
                    </tr>
                    <tr v-if="rekap.length === 0">
                        <td
                            colspan="9"
                            class="px-4 py-8 text-center text-muted-foreground"
                        >
                            Tidak ada data santri/presensi untuk rentang tanggal
                            ini.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
