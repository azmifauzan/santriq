<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';

defineProps<{
    guardian: { name: string };
    students: Array<{
        id: number;
        nis: string;
        name: string;
        classroom: string | null;
        today_status: string | null;
    }>;
}>();
</script>

<template>
    <Head title="Portal Wali" />

    <div class="mx-auto max-w-2xl space-y-6 p-4">
        <header class="flex flex-wrap items-start justify-between gap-2">
            <h1 class="text-xl font-semibold">Halo, {{ guardian.name }}</h1>
            <Link
                href="/wali/keluar"
                method="post"
                as="button"
                class="text-sm text-muted-foreground"
                >Keluar</Link
            >
        </header>

        <div class="flex gap-4">
            <Link
                href="/wali/portal/izin"
                class="text-sm font-medium text-emerald-600 hover:underline"
            >
                + Ajukan Izin / Sakit
            </Link>
        </div>

        <ul class="space-y-2">
            <li
                v-for="student in students"
                :key="student.id"
                class="rounded-lg border p-4"
            >
                <Link
                    :href="`/wali/portal/anak/${student.id}`"
                    class="font-medium text-emerald-600 hover:underline"
                    >{{ student.name }}</Link
                >
                <p class="text-sm text-muted-foreground">
                    {{ student.nis }} —
                    {{ student.classroom ?? 'Belum ada kelas' }}
                </p>
                <p class="text-sm">
                    Hari ini: {{ student.today_status ?? 'Belum ada catatan' }}
                </p>
            </li>
        </ul>
    </div>
</template>
