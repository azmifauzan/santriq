<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import ThemeToggle from '@/components/ThemeToggle.vue';
import { login } from '@/routes';

defineProps<{
    tenant: {
        id: number;
        name: string;
        address: string | null;
        phone: string | null;
    };
    landing: {
        tagline?: string;
        description?: string;
        operating_hours?: string;
        accent_color?: string;
        logo_path?: string;
        gallery?: string[];
    };
    stats: { students: number; teachers: number; classrooms: number };
}>();
</script>

<template>
    <Head :title="tenant.name" />

    <div class="min-h-screen bg-background text-foreground">
        <header class="flex items-center justify-between border-b p-4">
            <div class="flex items-center gap-3">
                <img
                    v-if="landing.logo_path"
                    :src="`/storage/${landing.logo_path}`"
                    :alt="tenant.name"
                    class="h-10 w-10 rounded-full object-cover"
                />
                <span class="font-semibold">{{ tenant.name }}</span>
            </div>
            <div class="flex items-center gap-3">
                <ThemeToggle />
                <Link href="/wali/masuk" class="text-sm font-medium"
                    >Portal Wali</Link
                >
                <Link :href="login()" class="text-sm font-medium"
                    >Masuk Staf</Link
                >
            </div>
        </header>

        <main class="mx-auto max-w-4xl space-y-10 p-6">
            <section class="space-y-3 text-center">
                <p v-if="landing.tagline" class="text-lg text-muted-foreground">
                    {{ landing.tagline }}
                </p>
                <p v-if="landing.description" class="whitespace-pre-line">
                    {{ landing.description }}
                </p>
            </section>

            <section class="grid grid-cols-3 gap-4 text-center">
                <div>
                    <p class="text-3xl font-bold">{{ stats.students }}</p>
                    <p class="text-sm text-muted-foreground">Santri Aktif</p>
                </div>
                <div>
                    <p class="text-3xl font-bold">{{ stats.teachers }}</p>
                    <p class="text-sm text-muted-foreground">
                        Pengajar & Admin
                    </p>
                </div>
                <div>
                    <p class="text-3xl font-bold">{{ stats.classrooms }}</p>
                    <p class="text-sm text-muted-foreground">Kelas</p>
                </div>
            </section>

            <section
                v-if="landing.gallery?.length"
                class="grid grid-cols-3 gap-2"
            >
                <img
                    v-for="path in landing.gallery"
                    :key="path"
                    :src="`/storage/${path}`"
                    class="aspect-square rounded-lg object-cover"
                />
            </section>

            <section
                v-if="landing.operating_hours || tenant.address || tenant.phone"
                class="space-y-1 text-center text-sm text-muted-foreground"
            >
                <p v-if="landing.operating_hours">
                    {{ landing.operating_hours }}
                </p>
                <p v-if="tenant.address">{{ tenant.address }}</p>
                <p v-if="tenant.phone">{{ tenant.phone }}</p>
            </section>
        </main>
    </div>
</template>
