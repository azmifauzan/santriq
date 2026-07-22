<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';

interface Student {
    id: number;
    name: string;
    nis: string;
}

interface User {
    id: number;
    name: string;
}

interface LeaveRequestItem {
    id: number;
    student_id: number;
    type: 'sakit' | 'izin';
    start_date: string;
    end_date: string;
    reason: string | null;
    status: 'pending' | 'approved' | 'rejected';
    student?: Student;
    reviewer?: User | null;
}

defineProps<{
    leaveRequests: LeaveRequestItem[];
    students: Student[];
    filters: {
        status?: string;
    };
}>();

defineOptions({
    layout: AppLayout,
});

const isModalOpen = ref(false);

const form = useForm({
    student_id: '' as string | number,
    type: 'izin' as 'sakit' | 'izin',
    start_date: new Date().toISOString().split('T')[0],
    end_date: new Date().toISOString().split('T')[0],
    reason: '',
});

function openCreateModal() {
    form.reset();
    form.clearErrors();
    form.start_date = new Date().toISOString().split('T')[0];
    form.end_date = new Date().toISOString().split('T')[0];
    isModalOpen.value = true;
}

function submitForm() {
    form.post('/leave-requests', {
        onSuccess: () => {
            isModalOpen.value = false;
        },
    });
}

function reviewRequest(
    reqItem: LeaveRequestItem,
    status: 'approved' | 'rejected',
) {
    const actionLabel = status === 'approved' ? 'menyetujui' : 'menolak';

    if (
        confirm(
            `Apakah Anda yakin ingin ${actionLabel} perizinan santri ${reqItem.student?.name}?`,
        )
    ) {
        useForm({ status }).put(`/leave-requests/${reqItem.id}/review`);
    }
}
</script>

<template>
    <Head title="Manajemen Perizinan Mandiri" />

    <div class="flex flex-col gap-6 p-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold tracking-tight">
                    Manajemen Perizinan Santri
                </h1>
                <p class="text-sm text-muted-foreground">
                    Tinjau pengajuan perizinan (sakit/izin) dari santri atau
                    wali santri.
                </p>
            </div>
            <Button @click="openCreateModal"> + Input Izin Manual </Button>
        </div>

        <!-- Table -->
        <div class="overflow-x-auto rounded-md border bg-card">
            <table class="w-full text-left text-sm">
                <thead
                    class="border-b bg-muted/50 text-xs font-medium text-muted-foreground uppercase"
                >
                    <tr>
                        <th class="px-4 py-3">Nama Santri</th>
                        <th class="px-4 py-3">Jenis Izin</th>
                        <th class="px-4 py-3">Rentang Tanggal</th>
                        <th class="px-4 py-3">Alasan</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3 text-right">Aksi Peninjauan</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <tr
                        v-for="reqItem in leaveRequests"
                        :key="reqItem.id"
                        class="hover:bg-muted/30"
                    >
                        <td class="px-4 py-3 font-medium">
                            {{ reqItem.student?.name || '-' }}
                        </td>
                        <td class="px-4 py-3">
                            <span
                                class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium uppercase"
                                :class="
                                    reqItem.type === 'sakit'
                                        ? 'bg-amber-100 text-amber-800'
                                        : 'bg-blue-100 text-blue-800'
                                "
                            >
                                {{ reqItem.type }}
                            </span>
                        </td>
                        <td class="px-4 py-3 font-mono text-xs">
                            {{ reqItem.start_date }} s/d {{ reqItem.end_date }}
                        </td>
                        <td
                            class="max-w-xs truncate px-4 py-3 text-muted-foreground"
                        >
                            {{ reqItem.reason || '-' }}
                        </td>
                        <td class="px-4 py-3">
                            <span
                                class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium uppercase"
                                :class="{
                                    'bg-amber-100 text-amber-800':
                                        reqItem.status === 'pending',
                                    'bg-green-100 text-green-800':
                                        reqItem.status === 'approved',
                                    'bg-red-100 text-red-800':
                                        reqItem.status === 'rejected',
                                }"
                            >
                                {{
                                    reqItem.status === 'pending'
                                        ? 'Menunggu'
                                        : reqItem.status === 'approved'
                                          ? 'Disetujui'
                                          : 'Ditolak'
                                }}
                            </span>
                        </td>
                        <td class="space-x-2 px-4 py-3 text-right">
                            <template v-if="reqItem.status === 'pending'">
                                <Button
                                    size="sm"
                                    class="bg-emerald-600 text-white hover:bg-emerald-700"
                                    @click="reviewRequest(reqItem, 'approved')"
                                >
                                    Setujui
                                </Button>
                                <Button
                                    variant="destructive"
                                    size="sm"
                                    @click="reviewRequest(reqItem, 'rejected')"
                                >
                                    Tolak
                                </Button>
                            </template>
                            <span
                                v-else
                                class="text-xs text-muted-foreground italic"
                            >
                                Ditinjau oleh
                                {{ reqItem.reviewer?.name || 'Sistem' }}
                            </span>
                        </td>
                    </tr>
                    <tr v-if="leaveRequests.length === 0">
                        <td
                            colspan="6"
                            class="px-4 py-8 text-center text-muted-foreground"
                        >
                            Belum ada pengajuan izin santri.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Modal Form -->
        <div
            v-if="isModalOpen"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
        >
            <div
                class="w-full max-w-md rounded-lg border bg-background p-6 shadow-lg"
            >
                <h2 class="mb-4 text-lg font-semibold">
                    Input Perizinan Santri
                </h2>

                <form @submit.prevent="submitForm" class="space-y-4">
                    <div>
                        <Label for="student_id">Pilih Santri</Label>
                        <select
                            id="student_id"
                            v-model="form.student_id"
                            required
                            class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm"
                        >
                            <option value="" disabled>
                                -- Pilih Santri --
                            </option>
                            <option
                                v-for="s in students"
                                :key="s.id"
                                :value="s.id"
                            >
                                {{ s.name }} (NIS: {{ s.nis }})
                            </option>
                        </select>
                        <InputError :message="form.errors.student_id" />
                    </div>

                    <div>
                        <Label for="type">Jenis Izin</Label>
                        <select
                            id="type"
                            v-model="form.type"
                            class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm"
                        >
                            <option value="izin">
                                Izin (Bepergian / Keperluan)
                            </option>
                            <option value="sakit">Sakit</option>
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <Label for="start_date">Mulai Tanggal</Label>
                            <Input
                                id="start_date"
                                type="date"
                                v-model="form.start_date"
                                required
                            />
                            <InputError :message="form.errors.start_date" />
                        </div>
                        <div>
                            <Label for="end_date">Sampai Tanggal</Label>
                            <Input
                                id="end_date"
                                type="date"
                                v-model="form.end_date"
                                required
                            />
                            <InputError :message="form.errors.end_date" />
                        </div>
                    </div>

                    <div>
                        <Label for="reason">Alasan Perizinan</Label>
                        <textarea
                            id="reason"
                            v-model="form.reason"
                            rows="3"
                            class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm"
                            placeholder="Alasan izin / sakit..."
                        ></textarea>
                    </div>

                    <div class="flex justify-end gap-2 pt-4">
                        <Button
                            type="button"
                            variant="outline"
                            @click="isModalOpen = false"
                            >Batal</Button
                        >
                        <Button type="submit" :disabled="form.processing"
                            >Simpan Izin</Button
                        >
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
