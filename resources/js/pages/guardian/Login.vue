<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import GuardianAuthController from '@/actions/App/Http/Controllers/GuardianAuthController';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';

defineOptions({
    layout: {
        title: 'Portal Wali',
        description:
            'Masuk dengan nomor HP yang terdaftar untuk melihat kehadiran dan pencapaian anak Anda.',
    },
});

defineProps<{
    isDemo?: boolean;
}>();
</script>

<template>
    <Head title="Portal Wali" />

    <div class="flex min-h-screen items-center justify-center p-4">
        <div class="w-full max-w-sm space-y-6">
            <div class="space-y-2 text-center">
                <h1 class="text-2xl font-bold">Portal Wali Santri</h1>
                <p class="text-sm text-muted-foreground">
                    Masukkan nomor HP yang terdaftar untuk menerima tautan masuk
                    via Telegram.
                </p>
            </div>

            <div
                v-if="isDemo"
                class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-center text-sm dark:border-emerald-900 dark:bg-emerald-950"
            >
                <p class="mb-3 text-emerald-800 dark:text-emerald-300">
                    Ini tenant demo — coba masuk sebagai wali tanpa Telegram.
                </p>
                <Form
                    v-bind="GuardianAuthController.loginDemo.form()"
                    v-slot="{ processing }"
                >
                    <Button
                        type="submit"
                        class="w-full bg-emerald-600 hover:bg-emerald-700"
                        :disabled="processing"
                    >
                        <Spinner v-if="processing" />
                        Masuk sebagai Wali Demo
                    </Button>
                </Form>
            </div>

            <Form
                action="/wali/masuk"
                method="post"
                v-slot="{ errors, processing, recentlySuccessful }"
                class="flex flex-col gap-6"
            >
                <div class="grid gap-2">
                    <Label for="phone">Nomor HP</Label>
                    <Input
                        id="phone"
                        name="phone"
                        type="tel"
                        required
                        autofocus
                        placeholder="08xxxxxxxxxx"
                    />
                    <InputError :message="errors.phone" />
                </div>

                <p v-if="recentlySuccessful" class="text-sm text-emerald-600">
                    Tautan masuk telah dikirim ke Telegram Anda. Buka chat
                    dengan bot untuk melanjutkan.
                </p>

                <Button
                    type="submit"
                    class="w-full bg-emerald-600 hover:bg-emerald-700"
                    :disabled="processing"
                >
                    <Spinner v-if="processing" />
                    Kirim Tautan Masuk
                </Button>
            </Form>
        </div>
    </div>
</template>
