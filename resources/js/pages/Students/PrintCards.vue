<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { edit as editCardPrint } from '@/routes/card-print';

interface PrintableStudent {
    id: number;
    nis: string;
    name: string;
    gender: 'L' | 'P';
    classroom_name: string;
    qr_svg: string;
    tenant_name: string;
}

interface CardSettings {
    columns_per_print_row: number;
    accent_color: string;
    show_nis: boolean;
    show_classroom: boolean;
    show_gender: boolean;
    show_logo: boolean;
}

defineProps<{
    students: PrintableStudent[];
    cardSettings: CardSettings;
    logoPath: string | null;
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
            <div class="flex gap-2">
                <Link
                    :href="editCardPrint()"
                    class="rounded-md border px-4 py-2 text-sm font-medium transition-colors hover:bg-muted"
                >
                    ⚙️ Kustomisasi Kartu
                </Link>
                <button
                    @click="triggerPrint"
                    class="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground transition-opacity hover:opacity-90"
                >
                    🖨️ Cetak Kartu Sekarang
                </button>
            </div>
        </div>

        <!-- Cards Grid -->
        <div
            class="card-grid grid grid-cols-2 gap-6 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 print:gap-4 print:p-0"
            :style="{ '--print-cols': cardSettings.columns_per_print_row }"
        >
            <div
                v-for="s in students"
                :key="s.id"
                class="card flex h-[280px] flex-col items-center justify-between rounded-xl border-2 bg-white p-4 text-center shadow-sm print:break-inside-avoid print:shadow-none"
                :style="{ '--accent': cardSettings.accent_color }"
            >
                <!-- Header -->
                <div class="card-accent-border mb-2 w-full border-b-2 pb-2">
                    <span
                        class="card-accent-text flex items-center justify-center gap-1 text-xs font-bold tracking-wider uppercase"
                    >
                        <img
                            v-if="cardSettings.show_logo && logoPath"
                            :src="`/storage/${logoPath}`"
                            alt=""
                            class="h-4 w-4 object-contain"
                        />
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
                        class="mt-1 flex flex-wrap items-center justify-center gap-x-3 font-mono text-xs text-slate-600"
                    >
                        <span v-if="cardSettings.show_nis"
                            >NIS: {{ s.nis }}</span
                        >
                        <span v-if="cardSettings.show_classroom">{{
                            s.classroom_name
                        }}</span>
                        <span v-if="cardSettings.show_gender">{{
                            s.gender === 'L' ? 'Laki-laki' : 'Perempuan'
                        }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.card {
    border-color: var(--accent, #1e293b);
}

.card-accent-border {
    border-color: var(--accent, #1e293b);
}

.card-accent-text {
    color: var(--accent, #1e293b);
}

@media print {
    body {
        background-color: white !important;
    }

    .card-grid {
        grid-template-columns: repeat(var(--print-cols), minmax(0, 1fr));
    }
}
</style>
