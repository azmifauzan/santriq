<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import ExcelIcon from '@/components/icons/ExcelIcon.vue';
import ImportDialog from '@/components/ImportDialog.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';

interface Classroom {
    id: number;
    name: string;
    level: string | null;
    students_count?: number;
}

defineProps<{
    classrooms: Classroom[];
}>();

defineOptions({
    layout: AppLayout,
});

const isModalOpen = ref(false);
const editingClassroom = ref<Classroom | null>(null);

function exportClassrooms() {
    window.location.href = '/classrooms/export';
}

const form = useForm({
    name: '',
    level: '',
});

function openCreateModal() {
    editingClassroom.value = null;
    form.reset();
    form.clearErrors();
    isModalOpen.value = true;
}

function openEditModal(classroom: Classroom) {
    editingClassroom.value = classroom;
    form.reset();
    form.clearErrors();
    form.name = classroom.name;
    form.level = classroom.level ?? '';
    isModalOpen.value = true;
}

function closeModal() {
    isModalOpen.value = false;
    editingClassroom.value = null;
    form.reset();
}

function submitForm() {
    if (editingClassroom.value) {
        form.put(`/classrooms/${editingClassroom.value.id}`, {
            onSuccess: () => closeModal(),
        });
    } else {
        form.post('/classrooms', {
            onSuccess: () => closeModal(),
        });
    }
}

function deleteClassroom(classroom: Classroom) {
    if (confirm(`Apakah Anda yakin ingin menghapus kelas ${classroom.name}?`)) {
        useForm({}).delete(`/classrooms/${classroom.id}`);
    }
}
</script>

<template>
    <Head title="Manajemen Kelas" />

    <div class="flex flex-col gap-6 p-6">
        <div
            class="flex flex-col items-start gap-4 sm:flex-row sm:items-center sm:justify-between"
        >
            <div>
                <h1 class="text-2xl font-bold tracking-tight">
                    Manajemen Kelas
                </h1>
                <p class="text-sm text-muted-foreground">
                    Kelola daftar kelas dan jenjang di TPA/TPQ Anda.
                </p>
            </div>
            <div
                class="flex w-full flex-col gap-2 sm:w-auto sm:flex-row sm:items-center"
            >
                <Button variant="outline" @click="exportClassrooms">
                    <ExcelIcon class="size-4" />
                    Export Excel
                </Button>
                <ImportDialog
                    import-url="/classrooms/import"
                    template-url="/classrooms/export?template=1"
                    title="Import Data Kelas"
                />
                <Button @click="openCreateModal"> + Tambah Kelas </Button>
            </div>
        </div>

        <div class="overflow-x-auto rounded-md border bg-card">
            <table class="w-full text-left text-sm">
                <thead
                    class="border-b bg-muted/50 text-xs font-medium text-muted-foreground uppercase"
                >
                    <tr>
                        <th class="px-4 py-3">Nama Kelas</th>
                        <th class="px-4 py-3">Jenjang / Level</th>
                        <th class="px-4 py-3">Jumlah Santri</th>
                        <th class="px-4 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <tr
                        v-for="classroom in classrooms"
                        :key="classroom.id"
                        class="hover:bg-accent/60"
                    >
                        <td class="px-4 py-3 font-medium">
                            {{ classroom.name }}
                        </td>
                        <td class="px-4 py-3 text-muted-foreground">
                            {{ classroom.level || '-' }}
                        </td>
                        <td class="px-4 py-3">
                            {{ classroom.students_count ?? 0 }} Santri
                        </td>
                        <td class="space-x-2 px-4 py-3 text-right">
                            <Button
                                variant="outline"
                                size="sm"
                                @click="openEditModal(classroom)"
                            >
                                Edit
                            </Button>
                            <Button
                                variant="destructive"
                                size="sm"
                                @click="deleteClassroom(classroom)"
                            >
                                Hapus
                            </Button>
                        </td>
                    </tr>
                    <tr v-if="classrooms.length === 0">
                        <td
                            colspan="4"
                            class="px-4 py-8 text-center text-muted-foreground"
                        >
                            Belum ada kelas terdaftar.
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
                    {{ editingClassroom ? 'Edit Kelas' : 'Tambah Kelas Baru' }}
                </h2>

                <form @submit.prevent="submitForm" class="space-y-4">
                    <div>
                        <Label for="name">Nama Kelas</Label>
                        <Input
                            id="name"
                            v-model="form.name"
                            placeholder="Mis. Kelas Abu Bakar"
                            required
                        />
                        <InputError :message="form.errors.name" />
                    </div>

                    <div>
                        <Label for="level">Jenjang / Level</Label>
                        <Input
                            id="level"
                            v-model="form.level"
                            placeholder="Mis. Jilid 1 / Iqra 2 / Al-Qur'an"
                        />
                        <InputError :message="form.errors.level" />
                    </div>

                    <div class="flex justify-end gap-2 pt-4">
                        <Button
                            type="button"
                            variant="outline"
                            @click="closeModal"
                            >Batal</Button
                        >
                        <Button type="submit" :disabled="form.processing">
                            {{ editingClassroom ? 'Simpan' : 'Tambah' }}
                        </Button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
