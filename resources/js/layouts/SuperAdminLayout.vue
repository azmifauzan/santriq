<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import AppLogo from '@/components/AppLogo.vue';
import { Button } from '@/components/ui/button';
import { logout } from '@/routes';
import { index } from '@/routes/super-admin';
</script>

<template>
    <div class="flex min-h-svh flex-col bg-background">
        <header class="border-b">
            <div
                class="mx-auto flex max-w-6xl items-center justify-between px-6 py-4"
            >
                <Link :href="index()" class="flex items-center gap-2">
                    <AppLogo />
                    <span class="text-sm font-medium text-muted-foreground">
                        Super Admin
                    </span>
                </Link>
                <Button as-child variant="outline" size="sm">
                    <Link :href="logout()" method="post">Keluar</Link>
                </Button>
            </div>
        </header>

        <main class="mx-auto w-full max-w-6xl flex-1 px-6 py-8">
            <slot />
        </main>
    </div>
</template>
