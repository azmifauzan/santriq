<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import type { User } from '@/types/auth';

defineProps<{
    teachers: User[];
}>();

defineOptions({
    layout: AppLayout,
});

const isModalOpen = ref(false);
const editingTeacher = ref<User | null>(null);

const form = useForm({
    name: '',
    email: '',
    password: '',
    role: 'pengajar' as 'admin' | 'pengajar',
});

function openCreateModal() {
    editingTeacher.value = null;
    form.reset();
    form.clearErrors();
    form.role = 'pengajar';
    isModalOpen.value = true;
}

function openEditModal(teacher: User) {
    editingTeacher.value = teacher;
    form.reset();
    form.clearErrors();
    form.name = teacher.name;
    form.email = teacher.email;
    form.role = teacher.role;
    form.password = '';
    isModalOpen.value = true;
}

function closeModal() {
    isModalOpen.value = false;
    editingTeacher.value = null;
    form.reset();
}

function submitForm() {
    if (editingTeacher.value) {
        form.put(`/teachers/${editingTeacher.value.id}`, {
            onSuccess: () => closeModal(),
        });
    } else {
        form.post('/teachers', {
            onSuccess: () => closeModal(),
        });
    }
}

function deleteTeacher(teacher: User) {
    if (
        confirm(`Apakah Anda yakin ingin menghapus pengajar ${teacher.name}?`)
    ) {
        useForm({}).delete(`/teachers/${teacher.id}`);
    }
}
</script>

<template>
    <Head title="Manajemen Pengajar" />

    <div class="flex flex-col gap-6 p-6">
        <div
            class="flex flex-col items-start gap-4 sm:flex-row sm:items-center sm:justify-between"
        >
            <div>
                <h1 class="text-2xl font-bold tracking-tight">
                    Manajemen Pengajar
                </h1>
                <p class="text-sm text-muted-foreground">
                    Kelola pengajar dan pengurus TPA/TPQ lembaga Anda.
                </p>
            </div>
            <Button class="w-full sm:w-auto" @click="openCreateModal">
                + Tambah Pengajar
            </Button>
        </div>

        <!-- Table -->
        <div class="overflow-x-auto rounded-md border bg-card">
            <table class="w-full text-left text-sm">
                <thead
                    class="border-b bg-muted/50 text-xs font-medium text-muted-foreground uppercase"
                >
                    <tr>
                        <th class="px-4 py-3">Nama</th>
                        <th class="px-4 py-3">Email</th>
                        <th class="px-4 py-3">Peran</th>
                        <th class="px-4 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <tr
                        v-for="teacher in teachers"
                        :key="teacher.id"
                        class="hover:bg-accent/60"
                    >
                        <td class="px-4 py-3 font-medium">
                            {{ teacher.name }}
                        </td>
                        <td class="px-4 py-3 text-muted-foreground">
                            {{ teacher.email }}
                        </td>
                        <td class="px-4 py-3">
                            <span
                                class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
                                :class="
                                    teacher.role === 'admin'
                                        ? 'bg-primary/10 text-primary'
                                        : 'bg-secondary text-secondary-foreground'
                                "
                            >
                                {{
                                    teacher.role === 'admin'
                                        ? 'Admin Lembaga'
                                        : 'Pengajar'
                                }}
                            </span>
                        </td>
                        <td class="space-x-2 px-4 py-3 text-right">
                            <Button
                                variant="outline"
                                size="sm"
                                @click="openEditModal(teacher)"
                            >
                                Edit
                            </Button>
                            <Button
                                variant="destructive"
                                size="sm"
                                @click="deleteTeacher(teacher)"
                            >
                                Hapus
                            </Button>
                        </td>
                    </tr>
                    <tr v-if="teachers.length === 0">
                        <td
                            colspan="4"
                            class="px-4 py-8 text-center text-muted-foreground"
                        >
                            Belum ada pengajar terdaftar.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Dialog Modal -->
        <div
            v-if="isModalOpen"
            class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-black/50 p-4 sm:items-center"
        >
            <div
                class="max-h-[calc(100dvh-2rem)] w-full max-w-md overflow-y-auto rounded-lg border bg-background p-6 shadow-lg"
            >
                <h2 class="mb-4 text-lg font-semibold">
                    {{
                        editingTeacher
                            ? 'Edit Pengajar'
                            : 'Tambah Pengajar Baru'
                    }}
                </h2>

                <form @submit.prevent="submitForm" class="space-y-4">
                    <div>
                        <Label for="name">Nama Lengkap</Label>
                        <Input id="name" v-model="form.name" required />
                        <InputError :message="form.errors.name" />
                    </div>

                    <div>
                        <Label for="email">Email</Label>
                        <Input
                            id="email"
                            type="email"
                            v-model="form.email"
                            required
                        />
                        <InputError :message="form.errors.email" />
                    </div>

                    <div>
                        <Label for="password">
                            Password
                            {{
                                editingTeacher
                                    ? '(Kosongkan bila tidak diubah)'
                                    : ''
                            }}
                        </Label>
                        <Input
                            id="password"
                            type="password"
                            v-model="form.password"
                            :required="!editingTeacher"
                        />
                        <InputError :message="form.errors.password" />
                    </div>

                    <div>
                        <Label for="role">Peran</Label>
                        <select
                            id="role"
                            v-model="form.role"
                            class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm transition-colors focus-visible:ring-1 focus-visible:ring-ring focus-visible:outline-none"
                        >
                            <option value="pengajar">Pengajar / Ustadz</option>
                            <option value="admin">Admin Lembaga</option>
                        </select>
                        <InputError :message="form.errors.role" />
                    </div>

                    <div class="flex justify-end gap-2 pt-4">
                        <Button
                            type="button"
                            variant="outline"
                            @click="closeModal"
                        >
                            Batal
                        </Button>
                        <Button type="submit" :disabled="form.processing">
                            {{ editingTeacher ? 'Simpan' : 'Tambah' }}
                        </Button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
