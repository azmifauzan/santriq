<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import SuperAdminLayout from '@/layouts/SuperAdminLayout.vue';
import { update } from '@/routes/super-admin/settings';

defineProps<{
    settings: {
        whatsapp_number?: string | null;
    };
}>();

defineOptions({
    layout: SuperAdminLayout,
});
</script>

<template>
    <Head title="Pengaturan Aplikasi" />

    <div class="flex flex-col gap-6 p-6">
        <Heading
            title="Pengaturan Aplikasi"
            description="Berlaku untuk seluruh SantriQ, bukan lembaga tertentu."
        />

        <Form
            v-bind="update.form()"
            v-slot="{ errors, processing }"
            class="max-w-md space-y-6"
        >
            <div class="grid gap-2">
                <Label for="whatsapp_number">Nomor WhatsApp kontak</Label>
                <Input
                    id="whatsapp_number"
                    name="whatsapp_number"
                    :default-value="settings.whatsapp_number ?? undefined"
                    placeholder="+6285220150587"
                    maxlength="20"
                />
                <InputError :message="errors.whatsapp_number" />
                <p class="text-sm text-muted-foreground">
                    Ditampilkan sebagai tombol chat di halaman utama publik.
                </p>
            </div>

            <Button type="submit" :disabled="processing">
                <Spinner v-if="processing" />
                Simpan
            </Button>
        </Form>
    </div>
</template>
