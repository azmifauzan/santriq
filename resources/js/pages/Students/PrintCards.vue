<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

interface PrintableStudent {
    id: number;
    nis: string;
    name: string;
    gender: 'L' | 'P';
    classroom_name: string;
    qr_svg: string;
    tenant_name: string;
}

defineProps<{
    students: PrintableStudent[];
}>();

function triggerPrint() {
    window.print();
}
</script>

<template>
    <Head title="Cetak Kartu QR Santri" />

    <div class="mx-auto max-w-[1600px] p-6">
        <!-- Print Header Action (hidden during print) -->
        <div
            class="mb-6 flex items-center justify-between rounded-lg border bg-muted/40 p-4 print:hidden"
        >
            <div>
                <h1 class="text-xl font-bold">Cetak Kartu QR Absensi Santri</h1>
                <p class="text-sm text-muted-foreground">
                    Total {{ students.length }} kartu siap dicetak. Gunakan
                    kertas A4 / HVS.
                </p>
            </div>
            <button
                @click="triggerPrint"
                class="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground transition-opacity hover:opacity-90"
            >
                🖨️ Cetak Kartu Sekarang
            </button>
        </div>

        <!-- Cards Grid -->
        <div
            class="grid grid-cols-2 gap-6 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 print:grid-cols-2 print:gap-4 print:p-0"
        >
            <div
                v-for="s in students"
                :key="s.id"
                class="flex h-[280px] flex-col items-center justify-between rounded-xl border-2 border-slate-300 bg-white p-4 text-center shadow-sm print:break-inside-avoid print:border-slate-800 print:shadow-none"
            >
                <!-- Header -->
                <div class="mb-2 w-full border-b pb-2">
                    <span
                        class="block text-xs font-bold tracking-wider text-slate-600 uppercase"
                    >
                        {{ s.tenant_name }}
                    </span>
                    <span class="block text-sm font-semibold text-slate-900">
                        KARTU PRESENSI SANTRI
                    </span>
                </div>

                <!-- QR Code SVG -->
                <div
                    class="my-1 flex h-32 w-32 items-center justify-center rounded-md border bg-white p-1"
                    v-html="s.qr_svg"
                ></div>

                <!-- Footer Info -->
                <div class="w-full border-t pt-2">
                    <h3
                        class="text-base leading-tight font-bold text-slate-900"
                    >
                        {{ s.name }}
                    </h3>
                    <div
                        class="mt-1 flex items-center justify-between font-mono text-xs text-slate-600"
                    >
                        <span>NIS: {{ s.nis }}</span>
                        <span>{{ s.classroom_name }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
@media print {
    body {
        background-color: white !important;
    }
}
</style>
