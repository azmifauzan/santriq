<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { update } from '@/routes/card-print';
import { edit as editLembaga } from '@/routes/lembaga';

const props = defineProps<{
    cardPrint: {
        columns_per_print_row: number;
        accent_color: string;
        show_nis: boolean;
        show_classroom: boolean;
        show_gender: boolean;
        show_logo: boolean;
    };
    logoPath: string | null;
}>();

const columns = ref(props.cardPrint.columns_per_print_row);
const accentColor = ref(props.cardPrint.accent_color);
const showNis = ref(props.cardPrint.show_nis);
const showClassroom = ref(props.cardPrint.show_classroom);
const showGender = ref(props.cardPrint.show_gender);
const showLogo = ref(props.cardPrint.show_logo);
</script>

<template>
    <Head title="Kustomisasi Kartu Santri" />

    <SettingsLayout>
        <div class="space-y-6">
            <Heading
                variant="small"
                title="Kustomisasi Kartu Santri"
                description="Atur tampilan kartu QR absensi santri saat dicetak."
            />

            <div class="grid gap-6 lg:grid-cols-[1fr_auto]">
                <Form
                    v-bind="update.form()"
                    v-slot="{ errors, processing }"
                    class="space-y-6"
                >
                    <div class="grid gap-2">
                        <Label for="columns_per_print_row"
                            >Jumlah Kolom Saat Cetak</Label
                        >
                        <select
                            id="columns_per_print_row"
                            name="columns_per_print_row"
                            v-model.number="columns"
                            class="h-9 w-32 rounded-md border border-input bg-background px-3 text-sm focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                        >
                            <option :value="2">2 kolom</option>
                            <option :value="3">3 kolom</option>
                            <option :value="4">4 kolom</option>
                        </select>
                        <InputError :message="errors.columns_per_print_row" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="accent_color">Warna Aksen</Label>
                        <Input
                            id="accent_color"
                            name="accent_color"
                            type="color"
                            v-model="accentColor"
                            class="h-10 w-20 cursor-pointer p-1"
                        />
                        <InputError :message="errors.accent_color" />
                    </div>

                    <div class="grid gap-3">
                        <p class="text-sm font-medium">
                            Field yang Ditampilkan
                        </p>
                        <div class="flex items-center gap-2">
                            <Checkbox
                                id="show_nis"
                                name="show_nis"
                                v-model="showNis"
                            />
                            <Label for="show_nis" class="font-normal"
                                >Tampilkan NIS</Label
                            >
                        </div>
                        <div class="flex items-center gap-2">
                            <Checkbox
                                id="show_classroom"
                                name="show_classroom"
                                v-model="showClassroom"
                            />
                            <Label for="show_classroom" class="font-normal"
                                >Tampilkan Kelas</Label
                            >
                        </div>
                        <div class="flex items-center gap-2">
                            <Checkbox
                                id="show_gender"
                                name="show_gender"
                                v-model="showGender"
                            />
                            <Label for="show_gender" class="font-normal"
                                >Tampilkan Jenis Kelamin</Label
                            >
                        </div>
                    </div>

                    <div class="grid gap-2">
                        <div class="flex items-center gap-2">
                            <Checkbox
                                id="show_logo"
                                name="show_logo"
                                v-model="showLogo"
                                :disabled="!logoPath"
                            />
                            <Label for="show_logo" class="font-normal"
                                >Tampilkan Logo Lembaga</Label
                            >
                        </div>
                        <p
                            v-if="!logoPath"
                            class="text-sm text-muted-foreground"
                        >
                            Belum ada logo. Upload dulu di
                            <Link :href="editLembaga()" class="underline"
                                >Settings &rarr; Lembaga</Link
                            >.
                        </p>
                    </div>

                    <Button type="submit" :disabled="processing">
                        <Spinner v-if="processing" />
                        Simpan
                    </Button>
                </Form>

                <div class="space-y-2">
                    <p class="text-sm font-medium text-muted-foreground">
                        Pratinjau
                    </p>
                    <div
                        class="flex h-[280px] w-[220px] flex-col items-center justify-between rounded-xl border-2 bg-white p-4 text-center shadow-sm"
                        :style="{ borderColor: accentColor }"
                    >
                        <div
                            class="mb-2 w-full border-b-2 pb-2"
                            :style="{ borderColor: accentColor }"
                        >
                            <span
                                class="flex items-center justify-center gap-1 text-xs font-bold tracking-wider uppercase"
                                :style="{ color: accentColor }"
                            >
                                <img
                                    v-if="showLogo && logoPath"
                                    :src="`/storage/${logoPath}`"
                                    alt=""
                                    class="h-4 w-4 object-contain"
                                />
                                Nama Lembaga
                            </span>
                            <span
                                class="block text-sm font-semibold text-slate-900"
                                >KARTU PRESENSI SANTRI</span
                            >
                        </div>
                        <div
                            class="flex h-24 w-24 items-center justify-center rounded-md border bg-white text-xs text-slate-400"
                        >
                            QR
                        </div>
                        <div class="w-full border-t pt-2">
                            <h3
                                class="text-base leading-tight font-bold text-slate-900"
                            >
                                Ahmad Fauzi
                            </h3>
                            <div
                                class="mt-1 flex flex-wrap items-center justify-center gap-x-3 font-mono text-xs text-slate-600"
                            >
                                <span v-if="showNis">NIS: 1234567</span>
                                <span v-if="showClassroom">Kelas A</span>
                                <span v-if="showGender">Laki-laki</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </SettingsLayout>
</template>
