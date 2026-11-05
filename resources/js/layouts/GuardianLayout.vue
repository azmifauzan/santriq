<script setup lang="ts">
import { Toaster } from '@/components/ui/sonner';
</script>

<template>
    <div>
        <slot />
        <Toaster />
    </div>
</template>
