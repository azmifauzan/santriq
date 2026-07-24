<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';

defineProps<{
    students: Array<{ id: number; name: string; nis: string }>;
    leaveRequests: Array<{
        id: number;
        type: string;
        start_date: string;
        end_date: string;
        status: string;
        student: { name: string };
    }>;
}>();
</script>

<template>
    <Head title="Ajukan Izin" />

    <div class="mx-auto max-w-2xl space-y-6 p-4">
        <div class="flex items-center gap-2">
            <Link
                href="/wali/portal"
                class="text-sm text-muted-foreground hover:underline"
                >&larr; Kembali ke Portal</Link
            >
        </div>

        <h1 class="text-xl font-semibold">Ajukan Izin / Sakit</h1>

        <Form
            action="/wali/portal/izin"
            method="post"
            v-slot="{ errors, processing }"
            class="space-y-4"
        >
            <div class="grid gap-2">
                <Label for="student_id">Santri</Label>
                <select
                    id="student_id"
                    name="student_id"
                    class="rounded-md border border-input bg-background px-3 py-2 text-sm"
                >
                    <option v-for="s in students" :key="s.id" :value="s.id">
                        {{ s.name }} ({{ s.nis }})
                    </option>
                </select>
                <InputError :message="errors.student_id" />
            </div>

            <div class="grid gap-2">
                <Label for="type">Jenis</Label>
                <select
                    id="type"
                    name="type"
                    class="rounded-md border border-input bg-background px-3 py-2 text-sm"
                >
                    <option value="sakit">Sakit</option>
                    <option value="izin">Izin</option>
                </select>
                <InputError :message="errors.type" />
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div class="grid gap-2">
                    <Label for="start_date">Mulai</Label>
                    <Input id="start_date" name="start_date" type="date" />
                    <InputError :message="errors.start_date" />
                </div>
                <div class="grid gap-2">
                    <Label for="end_date">Selesai</Label>
                    <Input id="end_date" name="end_date" type="date" />
                    <InputError :message="errors.end_date" />
                </div>
            </div>

            <div class="grid gap-2">
                <Label for="reason">Alasan</Label>
                <Input
                    id="reason"
                    name="reason"
                    placeholder="Keterangan singkat"
                />
                <InputError :message="errors.reason" />
            </div>

            <Button
                type="submit"
                class="bg-emerald-600 hover:bg-emerald-700"
                :disabled="processing"
            >
                <Spinner v-if="processing" />
                Kirim Pengajuan
            </Button>
        </Form>

        <section class="space-y-2">
            <h2 class="font-medium">Riwayat Pengajuan</h2>
            <ul class="space-y-2">
                <li
                    v-for="lr in leaveRequests"
                    :key="lr.id"
                    class="flex flex-wrap items-center justify-between gap-2 rounded-lg border p-3 text-sm"
                >
                    <div>
                        <p class="font-medium">
                            {{ lr.student.name }} — {{ lr.type }}
                        </p>
                        <p class="text-xs text-muted-foreground">
                            {{ lr.start_date }} s/d {{ lr.end_date }}
                        </p>
                    </div>
                    <span
                        class="rounded bg-muted px-2 py-1 text-xs font-semibold capitalize"
                        >{{ lr.status }}</span
                    >
                </li>
            </ul>
        </section>
    </div>
</template>
