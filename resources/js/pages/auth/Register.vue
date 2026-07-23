<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { login } from '@/routes';
import { store } from '@/routes/register';
import { availability } from '@/routes/subdomain';

defineProps<{
    passwordRules: string;
}>();

defineOptions({
    layout: {
        title: 'Mulai bersama SantriQ',
        description:
            'Daftarkan lembaga Anda dan kelola santri dengan lebih mudah.',
    },
});

const subdomain = ref('');
const subdomainStatus = ref<'idle' | 'checking' | 'available' | 'taken'>(
    'idle',
);
let debounceHandle: ReturnType<typeof setTimeout> | undefined;

watch(subdomain, (value) => {
    clearTimeout(debounceHandle);

    const cleaned = value.trim().toLowerCase();
    subdomain.value = cleaned;

    if (cleaned.length < 3) {
        subdomainStatus.value = 'idle';

        return;
    }

    subdomainStatus.value = 'checking';
    debounceHandle = setTimeout(async () => {
        const response = await fetch(
            availability.url({ query: { value: cleaned } }),
        );
        const data = (await response.json()) as { available: boolean };
        subdomainStatus.value = data.available ? 'available' : 'taken';
    }, 400);
});
</script>

<template>
    <Head title="Daftar" />

    <Form
        v-bind="store.form()"
        :reset-on-success="['password', 'password_confirmation']"
        v-slot="{ errors, processing }"
        class="flex flex-col gap-6"
    >
        <div class="grid gap-6">
            <div class="grid gap-2">
                <Label for="institution_name">Nama Lembaga (TPA/TPQ)</Label>
                <Input
                    id="institution_name"
                    type="text"
                    required
                    autofocus
                    :tabindex="1"
                    name="institution_name"
                    placeholder="Contoh: TPQ Al-Hidayah"
                />
                <InputError :message="errors.institution_name" />
            </div>

            <div class="grid gap-2">
                <Label for="subdomain">Alamat SantriQ Lembaga</Label>
                <div class="flex items-center gap-2">
                    <Input
                        id="subdomain"
                        v-model="subdomain"
                        type="text"
                        required
                        :tabindex="2"
                        name="subdomain"
                        placeholder="al-hidayah"
                    />
                    <span
                        class="text-sm whitespace-nowrap text-muted-foreground"
                        >.santriq.web.id</span
                    >
                </div>
                <p
                    v-if="subdomainStatus === 'checking'"
                    class="text-sm text-muted-foreground"
                >
                    Mengecek ketersediaan…
                </p>
                <p
                    v-else-if="subdomainStatus === 'available'"
                    class="text-sm text-emerald-600"
                >
                    Tersedia.
                </p>
                <p
                    v-else-if="subdomainStatus === 'taken'"
                    class="text-sm text-destructive"
                >
                    Sudah dipakai atau tidak valid.
                </p>
                <InputError :message="errors.subdomain" />
            </div>

            <div class="grid gap-2">
                <Label for="name">Nama Penanggung Jawab</Label>
                <Input
                    id="name"
                    type="text"
                    required
                    :tabindex="3"
                    autocomplete="name"
                    name="name"
                    placeholder="Nama Lengkap"
                />
                <InputError :message="errors.name" />
            </div>

            <div class="grid gap-2">
                <Label for="email">Alamat email</Label>
                <Input
                    id="email"
                    type="email"
                    required
                    :tabindex="4"
                    autocomplete="email"
                    name="email"
                    placeholder="email@example.com"
                />
                <InputError :message="errors.email" />
            </div>

            <div class="grid gap-2">
                <Label for="password">Kata sandi</Label>
                <PasswordInput
                    id="password"
                    required
                    :tabindex="5"
                    autocomplete="new-password"
                    name="password"
                    placeholder="Kata sandi"
                    :passwordrules="passwordRules"
                />
                <InputError :message="errors.password" />
            </div>

            <div class="grid gap-2">
                <Label for="password_confirmation">Konfirmasi kata sandi</Label>
                <PasswordInput
                    id="password_confirmation"
                    required
                    :tabindex="6"
                    autocomplete="new-password"
                    name="password_confirmation"
                    placeholder="Ulangi kata sandi"
                    :passwordrules="passwordRules"
                />
                <InputError :message="errors.password_confirmation" />
            </div>

            <Button
                type="submit"
                class="mt-2 h-11 w-full rounded-xl bg-emerald-600 font-semibold text-white hover:bg-emerald-700"
                tabindex="7"
                :disabled="processing"
                data-test="register-user-button"
            >
                <Spinner v-if="processing" />
                Buat akun
            </Button>
        </div>

        <div class="text-center text-sm text-muted-foreground">
            Sudah punya akun?
            <TextLink
                :href="login()"
                class="font-semibold text-emerald-700 underline underline-offset-4 dark:text-emerald-400"
                :tabindex="8"
                >Masuk</TextLink
            >
        </div>
    </Form>
</template>
