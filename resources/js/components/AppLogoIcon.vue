<script setup lang="ts">
import { GraduationCap } from '@lucide/vue';
import type { HTMLAttributes } from 'vue';

defineOptions({
    inheritAttrs: false,
});

type Props = {
    className?: HTMLAttributes['class'];
};

defineProps<Props>();
</script>

<template>
    <GraduationCap
        :class="className"
        v-bind="$attrs"
        aria-hidden="true"
        style="fill: none"
    />
</template>
