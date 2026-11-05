<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import {
    store,
    update,
    destroy,
    exportMethod,
} from '@/actions/App/Http/Controllers/AchievementController';
import ExcelIcon from '@/components/icons/ExcelIcon.vue';
import InputError from '@/components/InputError.vue';
import LearningProgressSummary from '@/components/LearningProgress.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import type { LearningProgress } from '@/lib/learningProgress';
import { activityLabels, fluencyLabels } from '@/lib/learningProgress';

interface Student {
    id: number;
    name: string;
    nis: string;
}

interface User {
    id: number;
    name: string;
}

interface Achievement extends LearningProgress {
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

const props = defineProps<{
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
    learning_method: '',
    level: '',
    page_start: '' as string | number,
    page_end: '' as string | number,
    surah: '',
    ayah_start: '' as string | number,
    ayah_end: '' as string | number,
    activity_type: '',
    fluency: '',
    practice_note: '',
    note: '',
    score: '' as string | number,
    achieved_at: new Date().toISOString().split('T')[0],
});

const latestProgress = computed(() =>
    props.achievements.find(
        (achievement) => achievement.student_id === Number(form.student_id),
    ),
);

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
    form.learning_method = ach.learning_method ?? '';
    form.level = ach.level ?? '';
    form.page_start = ach.page_start ?? '';
    form.page_end = ach.page_end ?? '';
    form.surah = ach.surah ?? '';
    form.ayah_start = ach.ayah_start ?? '';
    form.ayah_end = ach.ayah_end ?? '';
    form.activity_type = ach.activity_type ?? '';
    form.fluency = ach.fluency ?? '';
    form.practice_note = ach.practice_note ?? '';
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
        form.put(update.url({ achievement: editingAchievement.value.id }), {
            onSuccess: () => closeModal(),
        });
    } else {
        form.post(store.url(), {
            onSuccess: () => closeModal(),
        });
    }
}

function deleteAchievement(ach: Achievement) {
    if (confirm(`Apakah Anda yakin ingin menghapus pencapaian ${ach.title}?`)) {
        useForm({}).delete(destroy.url({ achievement: ach.id }));
    }
}

function exportAchievements() {
    window.location.href = exportMethod.url(undefined, {
        query: props.filters,
    });
}
</script>

<template>
    <div class="flex flex-col gap-6 p-4 sm:p-6">
        <Head title="Buku Perkembangan & Prestasi Santri" />
        <div
            class="flex flex-col items-start gap-4 sm:flex-row sm:items-center sm:justify-between"
        >
            <div>
                <h1 class="text-2xl font-bold tracking-tight">
                    Buku Perkembangan & Prestasi Santri
                </h1>
                <p class="text-sm text-muted-foreground">
                    Catat dan pantau capaian bacaan, hafalan, hadits, dan
                    prestasi santri.
                </p>
            </div>
            <div
                class="flex w-full flex-col gap-2 sm:w-auto sm:flex-row sm:items-center"
            >
                <Button
                    variant="outline"
                    class="min-h-11"
                    @click="exportAchievements"
                >
                    <ExcelIcon class="size-4" />
                    Export Excel
                </Button>
                <Button class="min-h-11" @click="openCreateModal">
                    + Tambah Pencapaian
                </Button>
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
                        <td class="min-w-52 space-y-2 px-4 py-3">
                            <p class="font-semibold">{{ ach.title }}</p>
                            <LearningProgressSummary :progress="ach" />
                        </td>
                        <td class="px-4 py-3 font-mono font-bold">
                            {{ ach.score ?? '-' }}
                        </td>
                        <td
                            class="max-w-xs px-4 py-3 text-sm break-words whitespace-pre-wrap text-muted-foreground"
                        >
                            {{ ach.note || '-' }}
                        </td>
                        <td class="space-x-2 px-4 py-3 text-right">
                            <Button
                                variant="outline"
                                size="sm"
                                class="min-h-11"
                                @click="openEditModal(ach)"
                            >
                                Edit
                            </Button>
                            <Button
                                variant="destructive"
                                size="sm"
                                class="min-h-11"
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

        <Dialog v-model:open="isModalOpen">
            <DialogContent
                class="max-h-[calc(100dvh-2rem)] overflow-y-auto sm:max-w-2xl [&_[data-slot=dialog-close]]:p-3.5 [&_button]:min-h-11 [&_input]:min-h-11"
            >
                <DialogHeader>
                    <DialogTitle>{{
                        editingAchievement
                            ? 'Edit Pencapaian'
                            : 'Tambah Pencapaian Baru'
                    }}</DialogTitle>
                    <DialogDescription
                        >Catat materi hari ini. Isi rincian perkembangan sesuai
                        metode belajar santri.</DialogDescription
                    >
                </DialogHeader>

                <form @submit.prevent="submitForm" class="space-y-4">
                    <div>
                        <Label for="student_id">Pilih Santri</Label>
                        <select
                            id="student_id"
                            v-model="form.student_id"
                            required
                            class="flex h-11 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm"
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

                    <section
                        v-if="latestProgress"
                        class="space-y-2 rounded-md border bg-muted/30 p-3"
                        aria-label="Catatan belajar terakhir"
                    >
                        <p class="text-sm font-medium">
                            Catatan terakhir · {{ latestProgress.achieved_at }}
                        </p>
                        <p class="text-sm">{{ latestProgress.title }}</p>
                        <LearningProgressSummary :progress="latestProgress" />
                    </section>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <Label for="category">Kategori</Label>
                            <select
                                id="category"
                                v-model="form.category"
                                required
                                class="flex h-11 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm"
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

                    <details
                        class="space-y-4 rounded-md border p-4"
                        :open="
                            !!editingAchievement?.learning_method ||
                            !!editingAchievement?.activity_type ||
                            !!editingAchievement?.fluency ||
                            !!editingAchievement?.page_start ||
                            !!editingAchievement?.surah ||
                            !!editingAchievement?.practice_note ||
                            !!editingAchievement?.level
                        "
                    >
                        <summary
                            class="min-h-11 cursor-pointer py-2 text-sm font-medium focus-visible:ring-2 focus-visible:ring-ring"
                        >
                            Rincian perkembangan belajar (opsional)
                        </summary>
                        <fieldset class="space-y-4">
                            <legend class="sr-only">
                                Perkembangan belajar (opsional)
                            </legend>
                            <div class="grid gap-4 sm:grid-cols-2">
                                <div>
                                    <Label for="learning_method"
                                        >Metode / buku</Label
                                    >
                                    <Input
                                        id="learning_method"
                                        v-model="form.learning_method"
                                        placeholder="Iqra, Ummi, Tilawati, Al-Qur'an"
                                        maxlength="255"
                                    />
                                    <InputError
                                        :message="form.errors.learning_method"
                                    />
                                </div>
                                <div>
                                    <Label for="level">Jilid / tingkat</Label>
                                    <Input
                                        id="level"
                                        v-model="form.level"
                                        placeholder="Contoh: 3"
                                        maxlength="255"
                                    />
                                    <InputError :message="form.errors.level" />
                                </div>
                                <div>
                                    <Label for="page_start">Halaman awal</Label>
                                    <Input
                                        id="page_start"
                                        v-model="form.page_start"
                                        type="number"
                                        min="1"
                                        max="9999"
                                    />
                                    <InputError
                                        :message="form.errors.page_start"
                                    />
                                </div>
                                <div>
                                    <Label for="page_end">Halaman akhir</Label>
                                    <Input
                                        id="page_end"
                                        v-model="form.page_end"
                                        type="number"
                                        min="1"
                                        max="9999"
                                    />
                                    <InputError
                                        :message="form.errors.page_end"
                                    />
                                </div>
                            </div>
                            <div>
                                <Label for="surah">Surah</Label>
                                <Input
                                    id="surah"
                                    v-model="form.surah"
                                    placeholder="Contoh: An-Naba"
                                    maxlength="255"
                                />
                                <InputError :message="form.errors.surah" />
                            </div>
                            <div class="grid gap-4 sm:grid-cols-2">
                                <div>
                                    <Label for="ayah_start">Ayat awal</Label>
                                    <Input
                                        id="ayah_start"
                                        v-model="form.ayah_start"
                                        type="number"
                                        min="1"
                                        max="286"
                                    />
                                    <InputError
                                        :message="form.errors.ayah_start"
                                    />
                                </div>
                                <div>
                                    <Label for="ayah_end">Ayat akhir</Label>
                                    <Input
                                        id="ayah_end"
                                        v-model="form.ayah_end"
                                        type="number"
                                        min="1"
                                        max="286"
                                    />
                                    <InputError
                                        :message="form.errors.ayah_end"
                                    />
                                </div>
                                <div>
                                    <Label for="activity_type"
                                        >Jenis kegiatan</Label
                                    >
                                    <select
                                        id="activity_type"
                                        v-model="form.activity_type"
                                        class="h-11 w-full rounded-md border border-input bg-background px-3 text-sm focus-visible:ring-2 focus-visible:ring-ring"
                                    >
                                        <option value="">Tidak diisi</option>
                                        <option
                                            v-for="(
                                                label, value
                                            ) in activityLabels"
                                            :key="value"
                                            :value="value"
                                        >
                                            {{ label }}
                                        </option>
                                    </select>
                                    <InputError
                                        :message="form.errors.activity_type"
                                    />
                                </div>
                                <div>
                                    <Label for="fluency">Kelancaran</Label>
                                    <select
                                        id="fluency"
                                        v-model="form.fluency"
                                        class="h-11 w-full rounded-md border border-input bg-background px-3 text-sm focus-visible:ring-2 focus-visible:ring-ring"
                                    >
                                        <option value="">Tidak diisi</option>
                                        <option
                                            v-for="(
                                                label, value
                                            ) in fluencyLabels"
                                            :key="value"
                                            :value="value"
                                        >
                                            {{ label }}
                                        </option>
                                    </select>
                                    <InputError
                                        :message="form.errors.fluency"
                                    />
                                </div>
                            </div>
                            <div>
                                <Label for="practice_note"
                                    >Latihan berikutnya</Label
                                >
                                <textarea
                                    id="practice_note"
                                    v-model="form.practice_note"
                                    rows="2"
                                    maxlength="5000"
                                    class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus-visible:ring-2 focus-visible:ring-ring"
                                    placeholder="Bagian yang perlu diulang pada pertemuan berikutnya"
                                ></textarea>
                                <InputError
                                    :message="form.errors.practice_note"
                                />
                            </div>
                        </fieldset>
                    </details>

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
                            class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm focus-visible:ring-2 focus-visible:ring-ring"
                            placeholder="Catatan bacaan, hafalan, atau perkembangan santri"
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
            </DialogContent>
        </Dialog>
    </div>
</template>
