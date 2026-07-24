<script setup lang="ts">
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/AppLayout.vue';

interface Classroom {
    id: number;
    name: string;
}

interface Student {
    id: number;
    nis: string;
    name: string;
    classroom?: Classroom | null;
}

interface User {
    id: number;
    name: string;
}

interface Attendance {
    id: number;
    date: string;
    status: 'hadir' | 'izin' | 'sakit' | 'alpa';
    checked_in_at: string | null;
    checked_out_at: string | null;
    student?: Student;
    recorder?: User | null;
}

const props = defineProps<{
    attendances: Attendance[];
    classrooms: Classroom[];
    filters: {
        date: string;
        classroom_id?: string;
    };
}>();

defineOptions({
    layout: AppLayout,
});

const selectedDate = ref(props.filters.date);
const selectedClassroom = ref(props.filters.classroom_id || '');

const isModalOpen = ref(false);
const editingAttendance = ref<Attendance | null>(null);

const form = useForm({
    status: 'hadir' as 'hadir' | 'izin' | 'sakit' | 'alpa',
});

function applyFilter() {
    router.get(
        '/attendance',
        {
            date: selectedDate.value,
            classroom_id: selectedClassroom.value,
        },
        { preserveState: true },
    );
}

function openEditModal(att: Attendance) {
    editingAttendance.value = att;
    form.status = att.status;
    isModalOpen.value = true;
}

function closeModal() {
    isModalOpen.value = false;
    editingAttendance.value = null;
}

function submitCorrection() {
    if (editingAttendance.value) {
        form.put(`/attendance/${editingAttendance.value.id}`, {
            onSuccess: () => closeModal(),
        });
    }
}

function formatTime(dateTimeStr: string | null): string {
    if (!dateTimeStr) {
        return '-';
    }

    try {
        const d = new Date(dateTimeStr);

        return d.toLocaleTimeString('id-ID', {
            hour: '2-digit',
            minute: '2-digit',
        });
    } catch {
        return '-';
    }
}
</script>

<template>
    <Head title="Daftar & Koreksi Presensi" />

    <div class="flex flex-col gap-6 p-6">
        <div
            class="flex flex-col items-start gap-4 sm:flex-row sm:items-center sm:justify-between"
        >
            <div>
                <h1 class="text-2xl font-bold tracking-tight">
                    Rekap & Koreksi Presensi Harian
                </h1>
                <p class="text-sm text-muted-foreground">
                    Daftar riwayat kehadiran harian santri dan koreksi manual
                    status presensi.
                </p>
            </div>
        </div>

        <!-- Filters -->
        <div
            class="flex flex-wrap items-center gap-4 rounded-lg border bg-muted/30 p-4"
        >
            <div>
                <Input
                    type="date"
                    v-model="selectedDate"
                    @change="applyFilter"
                    class="w-44"
                />
            </div>
            <div>
                <select
                    v-model="selectedClassroom"
                    @change="applyFilter"
                    class="flex h-9 w-48 rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm"
                >
                    <option value="">Semua Kelas</option>
                    <option v-for="c in classrooms" :key="c.id" :value="c.id">
                        {{ c.name }}
                    </option>
                </select>
            </div>
            <Button variant="secondary" size="sm" @click="applyFilter">
                Tampilkan
            </Button>
        </div>

        <!-- Table -->
        <div class="overflow-x-auto rounded-md border bg-card">
            <table class="w-full text-left text-sm">
                <thead
                    class="border-b bg-muted/50 text-xs font-medium text-muted-foreground uppercase"
                >
                    <tr>
                        <th class="px-4 py-3">NIS</th>
                        <th class="px-4 py-3">Nama Santri</th>
                        <th class="px-4 py-3">Kelas</th>
                        <th class="px-4 py-3">Masuk</th>
                        <th class="px-4 py-3">Pulang</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <tr
                        v-for="att in attendances"
                        :key="att.id"
                        class="hover:bg-muted/30"
                    >
                        <td class="px-4 py-3 font-mono font-medium">
                            {{ att.student?.nis || '-' }}
                        </td>
                        <td class="px-4 py-3 font-medium">
                            {{ att.student?.name || '-' }}
                        </td>
                        <td class="px-4 py-3 text-muted-foreground">
                            {{ att.student?.classroom?.name || '-' }}
                        </td>
                        <td class="px-4 py-3 font-mono">
                            {{ formatTime(att.checked_in_at) }}
                        </td>
                        <td class="px-4 py-3 font-mono">
                            {{ formatTime(att.checked_out_at) }}
                        </td>
                        <td class="px-4 py-3">
                            <span
                                class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium uppercase"
                                :class="{
                                    'bg-green-100 text-green-800':
                                        att.status === 'hadir',
                                    'bg-blue-100 text-blue-800':
                                        att.status === 'izin',
                                    'bg-amber-100 text-amber-800':
                                        att.status === 'sakit',
                                    'bg-red-100 text-red-800':
                                        att.status === 'alpa',
                                }"
                            >
                                {{ att.status }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <Button
                                variant="outline"
                                size="sm"
                                @click="openEditModal(att)"
                            >
                                Edit Status
                            </Button>
                        </td>
                    </tr>
                    <tr v-if="attendances.length === 0">
                        <td
                            colspan="7"
                            class="px-4 py-8 text-center text-muted-foreground"
                        >
                            Belum ada catatan presensi pada tanggal ini.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Modal Correction -->
        <div
            v-if="isModalOpen"
            class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-black/50 p-4 sm:items-center"
        >
            <div
                class="max-h-[calc(100dvh-2rem)] w-full max-w-sm overflow-y-auto rounded-lg border bg-background p-6 shadow-lg"
            >
                <h2 class="mb-2 text-lg font-semibold">
                    Koreksi Status Presensi
                </h2>
                <p class="mb-4 text-xs text-muted-foreground">
                    Santri:
                    <span class="font-bold text-foreground">{{
                        editingAttendance?.student?.name
                    }}</span>
                </p>

                <form @submit.prevent="submitCorrection" class="space-y-4">
                    <div>
                        <label class="text-sm font-medium"
                            >Pilih Status Baru</label
                        >
                        <select
                            v-model="form.status"
                            class="mt-1 flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm"
                        >
                            <option value="hadir">Hadir</option>
                            <option value="izin">Izin</option>
                            <option value="sakit">Sakit</option>
                            <option value="alpa">Alpa</option>
                        </select>
                    </div>

                    <div class="flex justify-end gap-2 pt-4">
                        <Button
                            type="button"
                            variant="outline"
                            @click="closeModal"
                            >Batal</Button
                        >
                        <Button type="submit" :disabled="form.processing"
                            >Simpan Koreksi</Button
                        >
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
