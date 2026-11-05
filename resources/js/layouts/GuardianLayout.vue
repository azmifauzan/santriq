<script setup lang="ts">
import { Toaster } from '@/components/ui/sonner';
</script>

<template>
    <div class="min-h-screen">
        <slot />
        <Toaster />
    </div>
</template>
