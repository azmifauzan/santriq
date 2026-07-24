<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
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

interface Student {
    id: number;
    name: string;
    nis: string;
    classroom?: Classroom | null;
}

interface Payment {
    id: number;
    amount: number;
    method: string;
    paid_at: string;
}

interface Invoice {
    id: number;
    student_id: number;
    period: string;
    amount: number;
    due_date: string;
    status: 'unpaid' | 'paid' | 'void';
    student?: Student;
    payments?: Payment[];
}

defineProps<{
    invoices: Invoice[];
    classrooms: Classroom[];
    students: Student[];
    filters: {
        status?: string;
        period?: string;
    };
}>();

defineOptions({
    layout: AppLayout,
});

const isBatchModalOpen = ref(false);
const isVerifyModalOpen = ref(false);
const selectedInvoice = ref<Invoice | null>(null);

const batchForm = useForm({
    classroom_id: '' as string | number,
    period: new Date().toISOString().slice(0, 7),
    amount: 50000,
    due_date: new Date(new Date().getFullYear(), new Date().getMonth() + 1, 10)
        .toISOString()
        .split('T')[0],
});

const verifyForm = useForm({
    amount: 0,
    method: 'cash' as 'cash' | 'transfer',
    note: 'Pembayaran SPP tunai',
});

function openBatchModal() {
    batchForm.reset();
    batchForm.clearErrors();
    batchForm.period = new Date().toISOString().slice(0, 7);
    isBatchModalOpen.value = true;
}

function openVerifyModal(inv: Invoice) {
    selectedInvoice.value = inv;
    verifyForm.reset();
    verifyForm.clearErrors();
    verifyForm.amount = inv.amount;
    isVerifyModalOpen.value = true;
}

function submitBatch() {
    batchForm.post('/invoices/batch', {
        onSuccess: () => {
            isBatchModalOpen.value = false;
        },
    });
}

function submitVerify() {
    if (selectedInvoice.value) {
        verifyForm.post(`/invoices/${selectedInvoice.value.id}/verify`, {
            onSuccess: () => {
                isVerifyModalOpen.value = false;
                selectedInvoice.value = null;
            },
        });
    }
}

function formatCurrency(val: number): string {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(val);
}
</script>

<template>
    <Head title="Manajemen SPP & Tagihan" />

    <div class="flex flex-col gap-6 p-6">
        <div
            class="flex flex-col items-start gap-4 sm:flex-row sm:items-center sm:justify-between"
        >
            <div>
                <h1 class="text-2xl font-bold tracking-tight">
                    Manajemen SPP & Tagihan
                </h1>
                <p class="text-sm text-muted-foreground">
                    Terbitkan tagihan SPP bulanan massal dan verifikasi
                    pembayaran wali santri.
                </p>
            </div>
            <Button class="w-full sm:w-auto" @click="openBatchModal">
                ⚡ Menerbitkan Tagihan Massal
            </Button>
        </div>

        <!-- Table -->
        <div class="overflow-x-auto rounded-md border bg-card">
            <table class="w-full text-left text-sm">
                <thead
                    class="border-b bg-muted/50 text-xs font-medium text-muted-foreground uppercase"
                >
                    <tr>
                        <th class="px-4 py-3">Periode</th>
                        <th class="px-4 py-3">Nama Santri</th>
                        <th class="px-4 py-3">Kelas</th>
                        <th class="px-4 py-3">Jumlah</th>
                        <th class="px-4 py-3">Jatuh Tempo</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <tr
                        v-for="inv in invoices"
                        :key="inv.id"
                        class="hover:bg-muted/30"
                    >
                        <td class="px-4 py-3 font-mono font-semibold">
                            {{ inv.period }}
                        </td>
                        <td class="px-4 py-3 font-medium">
                            {{ inv.student?.name || '-' }}
                        </td>
                        <td class="px-4 py-3 text-muted-foreground">
                            {{ inv.student?.classroom?.name || '-' }}
                        </td>
                        <td class="px-4 py-3 font-mono font-bold">
                            {{ formatCurrency(inv.amount) }}
                        </td>
                        <td class="px-4 py-3 text-xs text-muted-foreground">
                            {{ inv.due_date }}
                        </td>
                        <td class="px-4 py-3">
                            <span
                                class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium uppercase"
                                :class="
                                    inv.status === 'paid'
                                        ? 'bg-green-100 text-green-800'
                                        : 'bg-red-100 text-red-800'
                                "
                            >
                                {{
                                    inv.status === 'paid'
                                        ? 'LUNAS'
                                        : 'BELUM LUNAS'
                                }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <Button
                                v-if="inv.status === 'unpaid'"
                                size="sm"
                                class="bg-emerald-600 text-white hover:bg-emerald-700"
                                @click="openVerifyModal(inv)"
                            >
                                ✓ Verifikasi Bayar
                            </Button>
                            <span
                                v-else
                                class="text-xs text-muted-foreground italic"
                                >Terbayar</span
                            >
                        </td>
                    </tr>
                    <tr v-if="invoices.length === 0">
                        <td
                            colspan="7"
                            class="px-4 py-8 text-center text-muted-foreground"
                        >
                            Belum ada tagihan SPP diterbitkan.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Modal Batch Generate -->
        <div
            v-if="isBatchModalOpen"
            class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-black/50 p-4 sm:items-center"
        >
            <div
                class="max-h-[calc(100dvh-2rem)] w-full max-w-md overflow-y-auto rounded-lg border bg-background p-6 shadow-lg"
            >
                <h2 class="mb-4 text-lg font-semibold">
                    Terbitkan Tagihan SPP Massal
                </h2>

                <form @submit.prevent="submitBatch" class="space-y-4">
                    <div>
                        <Label for="classroom_id">Pilih Kelas (Opsional)</Label>
                        <select
                            id="classroom_id"
                            v-model="batchForm.classroom_id"
                            class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm"
                        >
                            <option value="">Semua Kelas</option>
                            <option
                                v-for="c in classrooms"
                                :key="c.id"
                                :value="c.id"
                            >
                                {{ c.name }}
                            </option>
                        </select>
                    </div>

                    <div>
                        <Label for="period"
                            >Periode Tagihan (Format YYYY-MM)</Label
                        >
                        <Input
                            id="period"
                            v-model="batchForm.period"
                            placeholder="2026-07"
                            required
                        />
                        <InputError :message="batchForm.errors.period" />
                    </div>

                    <div>
                        <Label for="amount">Nominal SPP (Rp)</Label>
                        <Input
                            id="amount"
                            type="number"
                            min="0"
                            v-model="batchForm.amount"
                            required
                        />
                        <InputError :message="batchForm.errors.amount" />
                    </div>

                    <div>
                        <Label for="due_date">Tanggal Jatuh Tempo</Label>
                        <Input
                            id="due_date"
                            type="date"
                            v-model="batchForm.due_date"
                            required
                        />
                        <InputError :message="batchForm.errors.due_date" />
                    </div>

                    <div class="flex justify-end gap-2 pt-4">
                        <Button
                            type="button"
                            variant="outline"
                            @click="isBatchModalOpen = false"
                            >Batal</Button
                        >
                        <Button type="submit" :disabled="batchForm.processing"
                            >Terbitkan Massal</Button
                        >
                    </div>
                </form>
            </div>
        </div>

        <!-- Modal Verify Payment -->
        <div
            v-if="isVerifyModalOpen"
            class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-black/50 p-4 sm:items-center"
        >
            <div
                class="max-h-[calc(100dvh-2rem)] w-full max-w-sm overflow-y-auto rounded-lg border bg-background p-6 shadow-lg"
            >
                <h2 class="mb-2 text-lg font-semibold">
                    Verifikasi Pembayaran SPP
                </h2>
                <p class="mb-4 text-xs text-muted-foreground">
                    Santri:
                    <span class="font-bold text-foreground">{{
                        selectedInvoice?.student?.name
                    }}</span>
                    | Periode:
                    <span class="font-mono font-bold">{{
                        selectedInvoice?.period
                    }}</span>
                </p>

                <form @submit.prevent="submitVerify" class="space-y-4">
                    <div>
                        <Label for="amount">Jumlah Pembayaran (Rp)</Label>
                        <Input
                            id="amount"
                            type="number"
                            min="0"
                            v-model="verifyForm.amount"
                            required
                        />
                        <InputError :message="verifyForm.errors.amount" />
                    </div>

                    <div>
                        <Label for="method">Metode Pembayaran</Label>
                        <select
                            id="method"
                            v-model="verifyForm.method"
                            class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm"
                        >
                            <option value="cash">Tunai (Cash)</option>
                            <option value="transfer">Transfer Bank</option>
                        </select>
                    </div>

                    <div>
                        <Label for="note">Catatan / Keterangan</Label>
                        <Input
                            id="note"
                            v-model="verifyForm.note"
                            placeholder="Mis. Diterima oleh Ustadz Ahmad"
                        />
                    </div>

                    <div class="flex justify-end gap-2 pt-4">
                        <Button
                            type="button"
                            variant="outline"
                            @click="isVerifyModalOpen = false"
                            >Batal</Button
                        >
                        <Button
                            type="submit"
                            class="bg-emerald-600 text-white hover:bg-emerald-700"
                            :disabled="verifyForm.processing"
                        >
                            Konfirmasi Lunas
                        </Button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
