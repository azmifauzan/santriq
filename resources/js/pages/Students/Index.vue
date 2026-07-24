<script setup lang="ts">
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';

interface Classroom {
    id: number;
    name: string;
}

interface Guardian {
    id: number;
    name: string;
}

interface Student {
    id: number;
    nis: string;
    name: string;
    gender: 'L' | 'P';
    birth_date: string | null;
    qr_token: string;
    status: string;
    classroom_id: number | null;
    classroom?: Classroom | null;
    guardians?: Guardian[];
}

const props = defineProps<{
    students: Student[];
    classrooms: Classroom[];
    guardians: Guardian[];
    filters: {
        classroom_id?: string;
        search?: string;
    };
}>();

defineOptions({
    layout: AppLayout,
});

const isModalOpen = ref(false);
const editingStudent = ref<Student | null>(null);
const searchInput = ref(props.filters.search || '');
const selectedClassroom = ref(props.filters.classroom_id || '');

const form = useForm({
    nis: '',
    name: '',
    gender: 'L' as 'L' | 'P',
    birth_date: '',
    classroom_id: '' as string | number,
    guardian_ids: [] as number[],
    status: 'active',
});

function openCreateModal() {
    editingStudent.value = null;
    form.reset();
    form.clearErrors();
    form.gender = 'L';
    form.status = 'active';
    form.guardian_ids = [];
    isModalOpen.value = true;
}

function openEditModal(student: Student) {
    editingStudent.value = student;
    form.reset();
    form.clearErrors();
    form.nis = student.nis;
    form.name = student.name;
    form.gender = student.gender;
    form.birth_date = student.birth_date ?? '';
    form.classroom_id = student.classroom_id ?? '';
    form.status = student.status;
    form.guardian_ids = student.guardians
        ? student.guardians.map((g) => g.id)
        : [];
    isModalOpen.value = true;
}

function closeModal() {
    isModalOpen.value = false;
    editingStudent.value = null;
    form.reset();
}

function submitForm() {
    if (editingStudent.value) {
        form.put(`/students/${editingStudent.value.id}`, {
            onSuccess: () => closeModal(),
        });
    } else {
        form.post('/students', {
            onSuccess: () => closeModal(),
        });
    }
}

function deleteStudent(student: Student) {
    if (confirm(`Apakah Anda yakin ingin menghapus santri ${student.name}?`)) {
        useForm({}).delete(`/students/${student.id}`);
    }
}

function filterStudents() {
    router.get(
        '/students',
        {
            search: searchInput.value,
            classroom_id: selectedClassroom.value,
        },
        { preserveState: true },
    );
}

function printSelectedCards() {
    const url = `/students/print-cards?classroom_id=${selectedClassroom.value}`;
    window.open(url, '_blank');
}
</script>

<template>
    <Head title="Master Data Santri" />

    <div class="flex flex-col gap-6 p-6">
        <div
            class="flex flex-col items-start gap-4 sm:flex-row sm:items-center sm:justify-between"
        >
            <div>
                <h1 class="text-2xl font-bold tracking-tight">
                    Master Data Santri
                </h1>
                <p class="text-sm text-muted-foreground">
                    Kelola data santri, NIS, penempatan kelas, dan cetak kartu
                    QR absensi.
                </p>
            </div>
            <div
                class="flex w-full flex-col gap-2 sm:w-auto sm:flex-row sm:items-center"
            >
                <Button variant="outline" @click="printSelectedCards">
                    🖨️ Cetak Kartu QR
                </Button>
                <Button @click="openCreateModal"> + Tambah Santri </Button>
            </div>
        </div>

        <!-- Filters -->
        <div
            class="flex flex-wrap items-center gap-4 rounded-lg border bg-muted/30 p-4"
        >
            <div class="w-full md:w-64">
                <Input
                    v-model="searchInput"
                    placeholder="Cari nama atau NIS..."
                    @keyup.enter="filterStudents"
                />
            </div>
            <div class="w-full md:w-48">
                <select
                    v-model="selectedClassroom"
                    @change="filterStudents"
                    class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm transition-colors"
                >
                    <option value="">Semua Kelas</option>
                    <option v-for="c in classrooms" :key="c.id" :value="c.id">
                        {{ c.name }}
                    </option>
                </select>
            </div>
            <Button variant="secondary" size="sm" @click="filterStudents">
                Filter
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
                        <th class="px-4 py-3">L/P</th>
                        <th class="px-4 py-3">Kelas</th>
                        <th class="px-4 py-3">Wali Santri</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <tr
                        v-for="student in students"
                        :key="student.id"
                        class="hover:bg-muted/30"
                    >
                        <td class="px-4 py-3 font-mono font-medium">
                            {{ student.nis }}
                        </td>
                        <td class="px-4 py-3 font-medium">
                            {{ student.name }}
                        </td>
                        <td class="px-4 py-3">
                            {{
                                student.gender === 'L'
                                    ? 'Laki-laki'
                                    : 'Perempuan'
                            }}
                        </td>
                        <td class="px-4 py-3 text-muted-foreground">
                            {{ student.classroom?.name || '-' }}
                        </td>
                        <td class="px-4 py-3">
                            <span
                                v-if="
                                    student.guardians &&
                                    student.guardians.length > 0
                                "
                            >
                                {{
                                    student.guardians
                                        .map((g) => g.name)
                                        .join(', ')
                                }}
                            </span>
                            <span
                                v-else
                                class="text-xs text-muted-foreground italic"
                                >Belum ditautkan</span
                            >
                        </td>
                        <td class="px-4 py-3">
                            <span
                                class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium"
                                :class="
                                    student.status === 'active'
                                        ? 'bg-green-100 text-green-800'
                                        : 'bg-gray-100 text-gray-800'
                                "
                            >
                                {{
                                    student.status === 'active'
                                        ? 'Aktif'
                                        : 'Non-aktif'
                                }}
                            </span>
                        </td>
                        <td class="space-x-2 px-4 py-3 text-right">
                            <Button
                                variant="outline"
                                size="sm"
                                @click="openEditModal(student)"
                            >
                                Edit
                            </Button>
                            <Button
                                variant="destructive"
                                size="sm"
                                @click="deleteStudent(student)"
                            >
                                Hapus
                            </Button>
                        </td>
                    </tr>
                    <tr v-if="students.length === 0">
                        <td
                            colspan="7"
                            class="px-4 py-8 text-center text-muted-foreground"
                        >
                            Belum ada santri terdaftar.
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
                class="max-h-[calc(100dvh-2rem)] w-full max-w-lg overflow-y-auto rounded-lg border bg-background p-6 shadow-lg"
            >
                <h2 class="mb-4 text-lg font-semibold">
                    {{ editingStudent ? 'Edit Santri' : 'Tambah Santri Baru' }}
                </h2>

                <form @submit.prevent="submitForm" class="space-y-4">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <Label for="nis">NIS (Nomor Induk)</Label>
                            <Input id="nis" v-model="form.nis" required />
                            <InputError :message="form.errors.nis" />
                        </div>
                        <div>
                            <Label for="name">Nama Lengkap</Label>
                            <Input id="name" v-model="form.name" required />
                            <InputError :message="form.errors.name" />
                        </div>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <Label for="gender">Jenis Kelamin</Label>
                            <select
                                id="gender"
                                v-model="form.gender"
                                class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm"
                            >
                                <option value="L">Laki-laki (L)</option>
                                <option value="P">Perempuan (P)</option>
                            </select>
                            <InputError :message="form.errors.gender" />
                        </div>
                        <div>
                            <Label for="classroom_id">Kelas</Label>
                            <select
                                id="classroom_id"
                                v-model="form.classroom_id"
                                class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm"
                            >
                                <option value="">Tanpa Kelas</option>
                                <option
                                    v-for="c in classrooms"
                                    :key="c.id"
                                    :value="c.id"
                                >
                                    {{ c.name }}
                                </option>
                            </select>
                            <InputError :message="form.errors.classroom_id" />
                        </div>
                    </div>

                    <div>
                        <Label for="birth_date">Tanggal Lahir</Label>
                        <Input
                            id="birth_date"
                            type="date"
                            v-model="form.birth_date"
                        />
                        <InputError :message="form.errors.birth_date" />
                    </div>

                    <div>
                        <Label for="guardians">Wali Santri</Label>
                        <div
                            class="mt-1 max-h-36 space-y-2 overflow-y-auto rounded-md border p-3"
                        >
                            <label
                                v-for="g in guardians"
                                :key="g.id"
                                class="flex cursor-pointer items-center gap-2 text-sm"
                            >
                                <input
                                    type="checkbox"
                                    :value="g.id"
                                    v-model="form.guardian_ids"
                                    class="rounded border-input text-primary focus:ring-primary"
                                />
                                <span>{{ g.name }}</span>
                            </label>
                            <div
                                v-if="guardians.length === 0"
                                class="text-xs text-muted-foreground italic"
                            >
                                Belum ada wali santri terdaftar.
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end gap-2 pt-4">
                        <Button
                            type="button"
                            variant="outline"
                            @click="closeModal"
                            >Batal</Button
                        >
                        <Button type="submit" :disabled="form.processing">
                            {{ editingStudent ? 'Simpan' : 'Tambah' }}
                        </Button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
