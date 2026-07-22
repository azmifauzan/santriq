<script setup lang="ts">
import { Moon, Sun } from '@lucide/vue';
import { computed, onMounted, ref } from 'vue';
import { useAppearance } from '@/composables/useAppearance';

const { resolvedAppearance, updateAppearance } = useAppearance();
const isMounted = ref(false);
const isDark = computed(
    () => isMounted.value && resolvedAppearance.value === 'dark',
);

onMounted(() => {
    isMounted.value = true;
});

function toggleTheme(): void {
    updateAppearance(resolvedAppearance.value === 'dark' ? 'light' : 'dark');
}
</script>

<template>
    <button
        type="button"
        role="switch"
        :aria-checked="isDark"
        :aria-label="isDark ? 'Gunakan tema terang' : 'Gunakan tema gelap'"
        :title="isDark ? 'Gunakan tema terang' : 'Gunakan tema gelap'"
        data-test="theme-toggle"
        class="relative inline-flex h-9 w-16 shrink-0 items-center rounded-full border border-slate-200 bg-slate-100 px-1 text-slate-500 transition-colors focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-400"
        @click="toggleTheme"
    >
        <Sun class="absolute left-2 size-3.5" aria-hidden="true" />
        <Moon class="absolute right-2 size-3.5" aria-hidden="true" />
        <span
            class="relative z-10 flex size-7 items-center justify-center rounded-full bg-white text-amber-500 shadow-sm transition-transform dark:translate-x-7 dark:bg-slate-950 dark:text-emerald-300"
        >
            <Sun v-if="!isDark" class="size-3.5" aria-hidden="true" />
            <Moon v-else class="size-3.5" aria-hidden="true" />
        </span>
    </button>
</template>
