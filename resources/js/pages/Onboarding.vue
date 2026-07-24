<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { skip, update } from '@/routes/onboarding';

const { tenant, landing } = defineProps<{
    tenant: { address?: string; phone?: string };
    landing: {
        tagline?: string;
        description?: string;
        operating_hours?: string;
        accent_color?: string;
    };
}>();

const step = ref<1 | 2>(1);

const form = useForm({
    address: tenant.address ?? '',
    phone: tenant.phone ?? '',
    tagline: landing.tagline ?? '',
    description: landing.description ?? '',
    operating_hours: landing.operating_hours ?? '',
    accent_color: landing.accent_color ?? '#059669',
    logo: null as File | null,
    gallery: null as File[] | null,
});

function submit() {
    form.transform((data) => ({
        ...data,
        gallery: data.gallery ?? undefined,
    })).put(update.url());
}

function skipOnboarding() {
    form.post(skip.url());
}

function onLogoChange(event: Event) {
    form.logo = (event.target as HTMLInputElement).files?.[0] ?? null;
}

function onGalleryChange(event: Event) {
    form.gallery = Array.from((event.target as HTMLInputElement).files ?? []);
}
</script>

<template>
    <Head title="Selamat Datang" />

    <div
        class="flex min-h-screen items-center justify-center bg-slate-50 px-4 py-12 dark:bg-slate-950"
    >
        <div
            class="w-full max-w-xl rounded-2xl border border-slate-200 bg-white p-8 shadow-sm dark:border-slate-800 dark:bg-slate-900"
        >
            <div class="mb-6 flex items-center gap-2">
                <span
                    class="h-1.5 flex-1 rounded-full"
                    :class="step >= 1 ? 'bg-emerald-600' : 'bg-slate-200 dark:bg-slate-800'"
                />
                <span
                    class="h-1.5 flex-1 rounded-full"
                    :class="step >= 2 ? 'bg-emerald-600' : 'bg-slate-200 dark:bg-slate-800'"
                />
            </div>

            <div v-if="step === 1">
                <h1 class="text-xl font-bold">Info Lembaga</h1>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                    Data ini tampil di halaman landing lembaga Anda.
                </p>

                <div class="mt-6 space-y-4">
                    <div class="grid gap-2">
                        <Label for="address">Alamat</Label>
                        <Input id="address" v-model="form.address" maxlength="255" />
                        <InputError :message="form.errors.address" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="phone">Telepon</Label>
                        <Input id="phone" v-model="form.phone" maxlength="30" />
                        <InputError :message="form.errors.phone" />
                    </div>
                </div>

                <div class="mt-8 flex items-center justify-between">
                    <button
                        type="button"
                        class="text-sm text-slate-500 hover:underline dark:text-slate-400"
                        @click="skipOnboarding"
                    >
                        Lewati
                    </button>
                    <Button type="button" @click="step = 2">Lanjut</Button>
                </div>
            </div>

            <form v-else @submit.prevent="submit">
                <h1 class="text-xl font-bold">Landing Page</h1>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                    Konten ini tampil di halaman publik lembaga Anda.
                </p>

                <div class="mt-6 space-y-4">
                    <div class="grid gap-2">
                        <Label for="tagline">Tagline</Label>
                        <Input id="tagline" v-model="form.tagline" maxlength="150" />
                        <InputError :message="form.errors.tagline" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="description">Deskripsi</Label>
                        <textarea
                            id="description"
                            v-model="form.description"
                            rows="4"
                            class="rounded-md border border-input bg-background px-3 py-2 text-sm focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                            maxlength="2000"
                        />
                        <InputError :message="form.errors.description" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="operating_hours">Jam Operasional</Label>
                        <Input id="operating_hours" v-model="form.operating_hours" />
                        <InputError :message="form.errors.operating_hours" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="accent_color">Warna Aksen</Label>
                        <Input
                            id="accent_color"
                            v-model="form.accent_color"
                            type="color"
                            class="h-10 w-20 cursor-pointer p-1"
                        />
                        <InputError :message="form.errors.accent_color" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="logo">Logo</Label>
                        <Input id="logo" type="file" accept="image/*" @change="onLogoChange" />
                        <InputError :message="form.errors.logo" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="gallery">Galeri Foto (maks. 6)</Label>
                        <Input
                            id="gallery"
                            type="file"
                            accept="image/*"
                            multiple
                            @change="onGalleryChange"
                        />
                        <InputError :message="form.errors.gallery" />
                    </div>
                </div>

                <div class="mt-8 flex items-center justify-between">
                    <button
                        type="button"
                        class="text-sm text-slate-500 hover:underline dark:text-slate-400"
                        @click="skipOnboarding"
                    >
                        Lewati
                    </button>
                    <div class="flex gap-2">
                        <Button type="button" variant="outline" @click="step = 1">
                            Kembali
                        </Button>
                        <Button type="submit" :disabled="form.processing">
                            <Spinner v-if="form.processing" />
                            Simpan &amp; Selesai
                        </Button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</template>
