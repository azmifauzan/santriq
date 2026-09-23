<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { onMounted } from 'vue';
import { Spinner } from '@/components/ui/spinner';

const props = defineProps<{
    redirectTo: string;
}>();

defineOptions({
    layout: {
        title: 'Menyiapkan dashboard Anda',
        description: 'Anda akan diarahkan sebentar lagi.',
    },
});

// Renders visible, branded content first instead of bouncing straight to the
// signed link's target — an instant, contentless redirect through a link
// carrying a numeric user id and signature is exactly the shape Chrome's
// phishing heuristics flag on cross-subdomain auth handoffs (see
// App\Support\TenantSessionHandoff and docs/DEPLOY.md history).
onMounted(() => {
    router.visit(props.redirectTo, { replace: true });
});
</script>

<template>
    <div class="flex flex-col items-center gap-4 py-4 text-center">
        <Spinner class="size-6 text-emerald-600" />
    </div>
</template>
