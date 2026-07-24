<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { update } from '@/routes/lembaga';

defineProps<{
    tenant: {
        address?: string;
        phone?: string;
    };
    landing: {
        tagline?: string;
        description?: string;
        operating_hours?: string;
        accent_color?: string;
        logo_path?: string;
        gallery?: string[];
    };
}>();
</script>

<template>
    <Head title="Profil Landing Page" />

    <SettingsLayout>
        <div class="space-y-6">
            <Heading
                variant="small"
                title="Profil Landing Page"
                description="Konten ini tampil di halaman publik lembaga Anda."
            />

            <Form
                v-bind="update.form()"
                v-slot="{ errors, processing }"
                class="space-y-6"
            >
                <div class="grid gap-2">
                    <Label for="address">Alamat</Label>
                    <Input
                        id="address"
                        name="address"
                        :default-value="tenant.address"
                        maxlength="255"
                    />
                    <InputError :message="errors.address" />
                </div>

                <div class="grid gap-2">
                    <Label for="phone">Telepon</Label>
                    <Input
                        id="phone"
                        name="phone"
                        :default-value="tenant.phone"
                        maxlength="30"
                    />
                    <InputError :message="errors.phone" />
                </div>

                <div class="grid gap-2">
                    <Label for="tagline">Tagline</Label>
                    <Input
                        id="tagline"
                        name="tagline"
                        :default-value="landing.tagline"
                        maxlength="150"
                    />
                    <InputError :message="errors.tagline" />
                </div>

                <div class="grid gap-2">
                    <Label for="description">Deskripsi</Label>
                    <textarea
                        id="description"
                        name="description"
                        rows="4"
                        class="rounded-md border border-input bg-background px-3 py-2 text-sm focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                        maxlength="2000"
                        :default-value="landing.description"
                    />
                    <InputError :message="errors.description" />
                </div>

                <div class="grid gap-2">
                    <Label for="operating_hours">Jam Operasional</Label>
                    <Input
                        id="operating_hours"
                        name="operating_hours"
                        :default-value="landing.operating_hours"
                    />
                    <InputError :message="errors.operating_hours" />
                </div>

                <div class="grid gap-2">
                    <Label for="accent_color">Warna Aksen</Label>
                    <Input
                        id="accent_color"
                        name="accent_color"
                        type="color"
                        :default-value="landing.accent_color ?? '#059669'"
                        class="h-10 w-20 cursor-pointer p-1"
                    />
                    <InputError :message="errors.accent_color" />
                </div>

                <div class="grid gap-2">
                    <Label for="logo">Logo</Label>
                    <Input id="logo" name="logo" type="file" accept="image/*" />
                    <InputError :message="errors.logo" />
                </div>

                <div class="grid gap-2">
                    <Label for="gallery">Galeri Foto (maks. 6)</Label>
                    <Input
                        id="gallery"
                        name="gallery"
                        type="file"
                        accept="image/*"
                        multiple
                    />
                    <InputError :message="errors.gallery" />
                </div>

                <Button type="submit" :disabled="processing">
                    <Spinner v-if="processing" />
                    Simpan
                </Button>
            </Form>
        </div>
    </SettingsLayout>
</template>
