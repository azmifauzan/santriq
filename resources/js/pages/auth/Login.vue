<script setup lang="ts">
import { Form, Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { redirect as googleRedirect } from '@/actions/App/Http/Controllers/GoogleAuthController';
import GuardianAuthController from '@/actions/App/Http/Controllers/GuardianAuthController';
import AlertError from '@/components/AlertError.vue';
import GoogleAuthButton from '@/components/GoogleAuthButton.vue';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { register } from '@/routes';
import { store } from '@/routes/login';
import { request } from '@/routes/password';

defineOptions({
    layout: {
        title: 'Selamat datang kembali',
        description: 'Masuk untuk melanjutkan pengelolaan lembaga Anda.',
    },
});

defineProps<{
    status?: string;
    canResetPassword: boolean;
    tenantBrand?: Record<string, unknown> | null;
    demoHint?: {
        admin: { email: string; password: string };
        pengajar: { email: string; password: string };
    } | null;
}>();

const page = usePage<{
    subdomain?: string | null;
    errors?: Record<string, string>;
}>();
const googleLoginUrl = computed(() =>
    googleRedirect.url({
        query: { intent: 'login', subdomain: page.props.subdomain ?? '' },
    }),
);
const googleError = computed(() => page.props.errors?.google);
const guardianLoginUrl = computed(() => GuardianAuthController.create());
</script>

<template>
    <Head title="Masuk" />

    <div
        v-if="status"
        class="mb-4 text-center text-sm font-medium text-green-600"
    >
        {{ status }}
    </div>

    <div
        v-if="demoHint"
        class="mb-6 space-y-2 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm dark:border-emerald-900 dark:bg-emerald-950"
    >
        <p class="font-semibold text-emerald-800 dark:text-emerald-300">
            Coba SantriQ tanpa daftar
        </p>
        <p class="text-emerald-700 dark:text-emerald-400">
            Admin:
            <code class="rounded bg-white/60 px-1 dark:bg-black/20">{{
                demoHint.admin.email
            }}</code>
            /
            <code class="rounded bg-white/60 px-1 dark:bg-black/20">{{
                demoHint.admin.password
            }}</code>
        </p>
        <p class="text-emerald-700 dark:text-emerald-400">
            Pengajar:
            <code class="rounded bg-white/60 px-1 dark:bg-black/20">{{
                demoHint.pengajar.email
            }}</code>
            /
            <code class="rounded bg-white/60 px-1 dark:bg-black/20">{{
                demoHint.pengajar.password
            }}</code>
        </p>
        <TextLink :href="guardianLoginUrl" class="inline-block font-semibold">
            Coba sebagai Wali Santri &rarr;
        </TextLink>
    </div>

    <AlertError
        v-if="googleError"
        :errors="[googleError]"
        title="Masuk dengan Google gagal"
        class="mb-6"
    />

    <GoogleAuthButton
        :href="googleLoginUrl"
        label="Masuk dengan Google"
        class="mb-6"
    />

    <div class="relative mb-6 text-center text-sm text-muted-foreground">
        <span class="relative z-10 bg-background px-2">atau</span>
        <div class="absolute inset-x-0 top-1/2 -z-0 border-t"></div>
    </div>

    <Form
        v-bind="store.form()"
        :reset-on-success="['password']"
        v-slot="{ errors, processing }"
        class="flex flex-col gap-6"
    >
        <div class="grid gap-6">
            <div class="grid gap-2">
                <Label for="email">Alamat email</Label>
                <Input
                    id="email"
                    type="email"
                    name="email"
                    required
                    autofocus
                    :tabindex="1"
                    autocomplete="email"
                    placeholder="email@example.com"
                />
                <InputError :message="errors.email" />
            </div>

            <div class="grid gap-2">
                <div class="flex items-center justify-between">
                    <Label for="password">Kata sandi</Label>
                    <TextLink
                        v-if="canResetPassword"
                        :href="request()"
                        class="text-sm"
                        :tabindex="5"
                    >
                        Lupa kata sandi?
                    </TextLink>
                </div>
                <PasswordInput
                    id="password"
                    name="password"
                    required
                    :tabindex="2"
                    autocomplete="current-password"
                    placeholder="Kata sandi"
                />
                <InputError :message="errors.password" />
            </div>

            <div class="flex items-center justify-between">
                <Label for="remember" class="flex items-center space-x-3">
                    <Checkbox id="remember" name="remember" :tabindex="3" />
                    <span>Ingat saya</span>
                </Label>
            </div>

            <Button
                type="submit"
                class="mt-4 h-11 w-full rounded-xl bg-emerald-600 font-semibold text-white hover:bg-emerald-700"
                :tabindex="4"
                :disabled="processing"
                data-test="login-button"
            >
                <Spinner v-if="processing" />
                Masuk
            </Button>
        </div>

        <div class="text-center text-sm text-muted-foreground">
            Belum punya akun?
            <TextLink
                :href="register()"
                class="font-semibold text-emerald-700 dark:text-emerald-400"
                :tabindex="5"
                >Daftar gratis</TextLink
            >
        </div>
    </Form>
</template>
