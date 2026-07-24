<script setup lang="ts">
import { computed } from 'vue';

type MonthlyPoint = {
    label: string;
    count: number;
};

const props = defineProps<{
    data: MonthlyPoint[];
}>();

const max = computed(() =>
    Math.max(1, ...props.data.map((point) => point.count)),
);
</script>

<template>
    <div class="rounded-md border bg-card p-4">
        <p class="mb-4 text-sm font-medium text-muted-foreground">
            Lembaga baru per bulan
        </p>
        <div class="flex h-40 items-end gap-1">
            <div
                v-for="point in data"
                :key="point.label"
                class="group relative flex flex-1 flex-col items-center justify-end gap-2"
            >
                <div
                    class="absolute -top-8 hidden rounded-md bg-foreground px-2 py-1 text-xs whitespace-nowrap text-background group-hover:block"
                >
                    {{ point.label }}: {{ point.count }}
                </div>
                <div
                    class="w-full max-w-6 rounded-t bg-primary transition-colors group-hover:bg-primary/80"
                    :style="{
                        height: `${(point.count / max) * 100}%`,
                        minHeight: point.count > 0 ? '2px' : '0',
                    }"
                />
                <span class="text-[10px] text-muted-foreground">
                    {{ point.label.split(' ')[0] }}
                </span>
            </div>
        </div>
    </div>
</template>
