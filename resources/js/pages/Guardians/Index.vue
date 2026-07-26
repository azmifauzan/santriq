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

interface Student {
    id: number;
    name: string;
    nis: string;
}

interface Guardian {
    id: number;
    name: string;
    phone: string | null;
    telegram_chat_id: string | null;
    link_token: string;
    linked_at: string | null;
    students?: Student[];
}

const props = defineProps<{
    guardians: Guardian[];
    students: Student[];
}>();

defineOptions({
    layout: AppLayout,
});

const isModalOpen = ref(false);
const editingGuardian = ref<Guardian | null>(null);

function exportGuardians() {
    window.location.href = '/guardians/export';
}

const form = useForm({
    name: '',
    phone: '',
    student_ids: [] as number[],
});

function openCreateModal() {
    editingGuardian.value = null;
    form.reset();
    form.clearErrors();
    form.student_ids = [];
    isModalOpen.value = true;
}

function openEditModal(guardian: Guardian) {
    editingGuardian.value = guardian;
    form.reset();
    form.clearErrors();
    form.name = guardian.name;
    form.phone = guardian.phone ?? '';
    form.student_ids = guardian.students
        ? guardian.students.map((s) => s.id)
        : [];
    isModalOpen.value = true;
}

function closeModal() {
    isModalOpen.value = false;
    editingGuardian.value = null;
    form.reset();
}

function submitForm() {
    if (editingGuardian.value) {
        form.put(`/guardians/${editingGuardian.value.id}`, {
            onSuccess: () => closeModal(),
        });
    } else {
        form.post('/guardians', {
            onSuccess: () => closeModal(),
        });
    }
}

function deleteGuardian(guardian: Guardian) {
    if (
        confirm(`Apakah Anda yakin ingin menghapus data wali ${guardian.name}?`)
    ) {
        useForm({}).delete(`/guardians/${guardian.id}`);
    }
}

function copyStartCommand(token: string) {
    const text = `/start ${token}`;
    navigator.clipboard.writeText(text);
    alert(
        `Kode bot Telegram disalin: ${text}\nKirimkan pesan ini ke bot Telegram SantriQ.`,
    );
}
</script>

<template>
    <Head title="Data Wali Santri" />

    <div class="flex flex-col gap-6 p-6">
        <div
            class="flex flex-col items-start gap-4 sm:flex-row sm:items-center sm:justify-between"
        >
            <div>
                <h1 class="text-2xl font-bold tracking-tight">
                    Data Wali Santri
                </h1>
                <p class="text-sm text-muted-foreground">
                    Kelola data wali santri dan status penautan bot Telegram.
                </p>
            </div>
            <div
                class="flex w-full flex-col gap-2 sm:w-auto sm:flex-row sm:items-center"
            >
                <Button variant="outline" @click="exportGuardians">
                    <ExcelIcon class="size-4" />
                    Export Excel
                </Button>
                <ImportDialog
                    import-url="/guardians/import"
                    template-url="/guardians/export?template=1"
                    title="Import Data Wali Santri"
                />
                <Button @click="openCreateModal"> + Tambah Wali Santri </Button>
            </div>
        </div>

        <div class="overflow-x-auto rounded-md border bg-card">
            <table class="w-full text-left text-sm">
                <thead
                    class="border-b bg-muted/50 text-xs font-medium text-muted-foreground uppercase"
                >
                    <tr>
                        <th class="px-4 py-3">Nama Wali</th>
                        <th class="px-4 py-3">Telepon / WA</th>
                        <th class="px-4 py-3">Santri Terhubung</th>
                        <th class="px-4 py-3">Status Telegram</th>
                        <th class="px-4 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <tr
                        v-for="g in guardians"
                        :key="g.id"
                        class="hover:bg-accent/60"
                    >
                        <td class="px-4 py-3 font-medium">{{ g.name }}</td>
                        <td class="px-4 py-3 text-muted-foreground">
                            {{ g.phone || '-' }}
                        </td>
                        <td class="px-4 py-3">
                            <span v-if="g.students && g.students.length > 0">
                                {{ g.students.map((s) => s.name).join(', ') }}
                            </span>
                            <span
                                v-else
                                class="text-xs text-muted-foreground italic"
                                >Belum ada</span
                            >
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2">
                                <span
                                    class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium"
                                    :class="
                                        g.telegram_chat_id
                                            ? 'bg-green-100 text-green-800'
                                            : 'bg-amber-100 text-amber-800'
                                    "
                                >
                                    {{
                                        g.telegram_chat_id
                                            ? 'Terhubung'
                                            : 'Belum Taut'
                                    }}
                                </span>
                                <Button
                                    v-if="!g.telegram_chat_id"
                                    variant="ghost"
                                    size="sm"
                                    class="h-7 px-2 text-xs"
                                    title="Salin perintah tautkan Telegram"
                                    @click="copyStartCommand(g.link_token)"
                                >
                                    📋 Kode Bot
                                </Button>
                            </div>
                        </td>
                        <td class="space-x-2 px-4 py-3 text-right">
                            <Button
                                variant="outline"
                                size="sm"
                                @click="openEditModal(g)"
                            >
                                Edit
                            </Button>
                            <Button
                                variant="destructive"
                                size="sm"
                                @click="deleteGuardian(g)"
                            >
                                Hapus
                            </Button>
                        </td>
                    </tr>
                    <tr v-if="guardians.length === 0">
                        <td
                            colspan="5"
                            class="px-4 py-8 text-center text-muted-foreground"
                        >
                            Belum ada wali santri terdaftar.
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
                        editingGuardian
                            ? 'Edit Wali Santri'
                            : 'Tambah Wali Santri Baru'
                    }}
                </h2>

                <form @submit.prevent="submitForm" class="space-y-4">
                    <div>
                        <Label for="name">Nama Wali</Label>
                        <Input id="name" v-model="form.name" required />
                        <InputError :message="form.errors.name" />
                    </div>

                    <div>
                        <Label for="phone">Nomor Telepon / WhatsApp</Label>
                        <Input
                            id="phone"
                            v-model="form.phone"
                            placeholder="0812..."
                        />
                        <InputError :message="form.errors.phone" />
                    </div>

                    <div>
                        <Label for="students">Pilih Santri yang Diwakili</Label>
                        <div
                            class="mt-1 max-h-36 space-y-2 overflow-y-auto rounded-md border p-3"
                        >
                            <label
                                v-for="s in props.students"
                                :key="s.id"
                                class="flex cursor-pointer items-center gap-2 text-sm"
                            >
                                <input
                                    type="checkbox"
                                    :value="s.id"
                                    v-model="form.student_ids"
                                    class="rounded border-input text-primary focus:ring-primary"
                                />
                                <span>{{ s.name }} (NIS: {{ s.nis }})</span>
                            </label>
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
                            {{ editingGuardian ? 'Simpan' : 'Tambah' }}
                        </Button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
