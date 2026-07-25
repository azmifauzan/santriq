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

interface Achievement {
    id: number;
    student_id: number;
    category: string;
    title: string;
    note: string | null;
    score: number | null;
    achieved_at: string;
    student?: Student;
    recorder?: User | null;
}

defineProps<{
    achievements: Achievement[];
    students: Student[];
    filters: {
        student_id?: string;
        category?: string;
    };
}>();

defineOptions({
    layout: AppLayout,
});

const isModalOpen = ref(false);
const editingAchievement = ref<Achievement | null>(null);

const form = useForm({
    student_id: '' as string | number,
    category: "Hafalan Qur'an",
    title: '',
    note: '',
    score: '' as string | number,
    achieved_at: new Date().toISOString().split('T')[0],
});

function openCreateModal() {
    editingAchievement.value = null;
    form.reset();
    form.clearErrors();
    form.achieved_at = new Date().toISOString().split('T')[0];
    isModalOpen.value = true;
}

function openEditModal(ach: Achievement) {
    editingAchievement.value = ach;
    form.reset();
    form.clearErrors();
    form.student_id = ach.student_id;
    form.category = ach.category;
    form.title = ach.title;
    form.note = ach.note ?? '';
    form.score = ach.score ?? '';
    form.achieved_at = ach.achieved_at;
    isModalOpen.value = true;
}

function closeModal() {
    isModalOpen.value = false;
    editingAchievement.value = null;
    form.reset();
}

function submitForm() {
    if (editingAchievement.value) {
        form.put(`/achievements/${editingAchievement.value.id}`, {
            onSuccess: () => closeModal(),
        });
    } else {
        form.post('/achievements', {
            onSuccess: () => closeModal(),
        });
    }
}

function deleteAchievement(ach: Achievement) {
    if (confirm(`Apakah Anda yakin ingin menghapus pencapaian ${ach.title}?`)) {
        useForm({}).delete(`/achievements/${ach.id}`);
    }
}

function exportAchievements() {
    window.location.href = '/achievements/export';
}
</script>

<template>
    <Head title="Pencapaian & Prestasi Santri" />

    <div class="flex flex-col gap-6 p-6">
        <div
            class="flex flex-col items-start gap-4 sm:flex-row sm:items-center sm:justify-between"
        >
            <div>
                <h1 class="text-2xl font-bold tracking-tight">
                    Pencapaian & Prestasi Santri
                </h1>
                <p class="text-sm text-muted-foreground">
                    Catat dan pantau capaian bacaan, hafalan, hadits, dan
                    prestasi santri.
                </p>
            </div>
            <div
                class="flex w-full flex-col gap-2 sm:w-auto sm:flex-row sm:items-center"
            >
                <Button variant="outline" @click="exportAchievements">
                    Export Excel
                </Button>
                <Button @click="openCreateModal"> + Tambah Pencapaian </Button>
            </div>
        </div>

        <!-- Table -->
        <div class="overflow-x-auto rounded-md border bg-card">
            <table class="w-full text-left text-sm">
                <thead
                    class="border-b bg-muted/50 text-xs font-medium text-muted-foreground uppercase"
                >
                    <tr>
                        <th class="px-4 py-3">Tanggal</th>
                        <th class="px-4 py-3">Nama Santri</th>
                        <th class="px-4 py-3">Kategori</th>
                        <th class="px-4 py-3">Judul / Materi</th>
                        <th class="px-4 py-3">Nilai</th>
                        <th class="px-4 py-3">Catatan</th>
                        <th class="px-4 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <tr
                        v-for="ach in achievements"
                        :key="ach.id"
                        class="hover:bg-accent/60"
                    >
                        <td
                            class="px-4 py-3 font-mono text-xs text-muted-foreground"
                        >
                            {{ ach.achieved_at }}
                        </td>
                        <td class="px-4 py-3 font-medium">
                            {{ ach.student?.name || '-' }}
                        </td>
                        <td class="px-4 py-3">
                            <span
                                class="inline-flex items-center rounded-full bg-blue-50 px-2 py-0.5 text-xs font-medium text-blue-700"
                            >
                                {{ ach.category }}
                            </span>
                        </td>
                        <td class="px-4 py-3 font-semibold">{{ ach.title }}</td>
                        <td class="px-4 py-3 font-mono font-bold">
                            {{ ach.score ?? '-' }}
                        </td>
                        <td
                            class="max-w-xs truncate px-4 py-3 text-xs text-muted-foreground"
                        >
                            {{ ach.note || '-' }}
                        </td>
                        <td class="space-x-2 px-4 py-3 text-right">
                            <Button
                                variant="outline"
                                size="sm"
                                @click="openEditModal(ach)"
                            >
                                Edit
                            </Button>
                            <Button
                                variant="destructive"
                                size="sm"
                                @click="deleteAchievement(ach)"
                            >
                                Hapus
                            </Button>
                        </td>
                    </tr>
                    <tr v-if="achievements.length === 0">
                        <td
                            colspan="7"
                            class="px-4 py-8 text-center text-muted-foreground"
                        >
                            Belum ada pencapaian santri yang dicatat.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Modal Form -->
        <div
            v-if="isModalOpen"
            class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-black/50 p-4 sm:items-center"
        >
            <div
                class="max-h-[calc(100dvh-2rem)] w-full max-w-md overflow-y-auto rounded-lg border bg-background p-6 shadow-lg"
            >
                <h2 class="mb-4 text-lg font-semibold">
                    {{
                        editingAchievement
                            ? 'Edit Pencapaian'
                            : 'Tambah Pencapaian Baru'
                    }}
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

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <Label for="category">Kategori</Label>
                            <select
                                id="category"
                                v-model="form.category"
                                required
                                class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm"
                            >
                                <option value="Hafalan Qur'an">
                                    Hafalan Qur'an
                                </option>
                                <option value="Bacaan Iqra">Bacaan Iqra</option>
                                <option value="Hafalan Hadits">
                                    Hafalan Hadits
                                </option>
                                <option value="Doa Harian">Doa Harian</option>
                                <option value="Akhlak & Adab">
                                    Akhlak & Adab
                                </option>
                                <option value="Lainnya">Lainnya</option>
                            </select>
                            <InputError :message="form.errors.category" />
                        </div>
                        <div>
                            <Label for="achieved_at">Tanggal</Label>
                            <Input
                                id="achieved_at"
                                type="date"
                                v-model="form.achieved_at"
                                required
                            />
                            <InputError :message="form.errors.achieved_at" />
                        </div>
                    </div>

                    <div>
                        <Label for="title">Judul / Surah / Materi</Label>
                        <Input
                            id="title"
                            v-model="form.title"
                            placeholder="Mis. Surah An-Naba 1-15 / Iqra 4 Hal 12"
                            required
                        />
                        <InputError :message="form.errors.title" />
                    </div>

                    <div>
                        <Label for="score">Nilai (0-100, Opsional)</Label>
                        <Input
                            id="score"
                            type="number"
                            min="0"
                            max="100"
                            v-model="form.score"
                            placeholder="85"
                        />
                        <InputError :message="form.errors.score" />
                    </div>

                    <div>
                        <Label for="note">Catatan Pengajar</Label>
                        <textarea
                            id="note"
                            v-model="form.note"
                            rows="3"
                            class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm"
                            placeholder="Mis. Tajwid sangat makhraj lancar..."
                        ></textarea>
                        <InputError :message="form.errors.note" />
                    </div>

                    <div class="flex justify-end gap-2 pt-4">
                        <Button
                            type="button"
                            variant="outline"
                            @click="closeModal"
                            >Batal</Button
                        >
                        <Button type="submit" :disabled="form.processing">
                            {{ editingAchievement ? 'Simpan' : 'Tambah' }}
                        </Button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
