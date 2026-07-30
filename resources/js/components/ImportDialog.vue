<script setup lang="ts">
import { useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import ExcelIcon from '@/components/icons/ExcelIcon.vue';
import { Button } from '@/components/ui/button';

type ImportSummary = {
    created: number;
    skipped: number;
    errors: string[];
};

const props = defineProps<{
    importUrl: string;
    templateUrl: string;
    title: string;
}>();

const isOpen = ref(false);
const fileInput = ref<HTMLInputElement | null>(null);

const form = useForm<{ file: File | null }>({
    file: null,
});

const summary = computed(() => {
    const page = usePage() as unknown as {
        flash?: { import_summary?: ImportSummary };
    };

    return page.flash?.import_summary ?? null;
});

function open() {
    form.reset();
    form.clearErrors();
    isOpen.value = true;
}

function close() {
    isOpen.value = false;
}

function onFileChange(event: Event) {
    const target = event.target as HTMLInputElement;
    form.file = target.files?.[0] ?? null;
}

function pickFile() {
    fileInput.value?.click();
}

function submit() {
    form.post(props.importUrl, {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            form.reset();

            if (fileInput.value) {
                fileInput.value.value = '';
            }
        },
    });
}
</script>

<template>
    <Button variant="outline" @click="open">
        <ExcelIcon class="size-4" />
        Import Excel
    </Button>

    <div
        v-if="isOpen"
        class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-black/50 p-4 sm:items-center"
    >
        <div
            class="flex max-h-[calc(100dvh-2rem)] w-full max-w-2xl flex-col overflow-y-auto rounded-lg border bg-background p-6 shadow-lg"
        >
            <h2 class="mb-4 text-lg font-semibold">{{ title }}</h2>

            <a
                :href="templateUrl"
                class="mb-4 inline-block text-sm text-primary underline"
            >
                Unduh Template
            </a>

            <form @submit.prevent="submit" class="space-y-4">
                <div>
                    <input
                        ref="fileInput"
                        type="file"
                        accept=".xlsx,.xls,.csv"
                        class="hidden"
                        @change="onFileChange"
                    />
                    <div class="flex items-center gap-2">
                        <Button
                            type="button"
                            variant="secondary"
                            @click="pickFile"
                        >
                            Pilih File
                        </Button>
                        <span class="truncate text-sm text-muted-foreground">
                            {{ form.file?.name ?? 'Belum ada file dipilih' }}
                        </span>
                    </div>
                    <p
                        v-if="form.errors.file"
                        class="mt-1 text-sm text-destructive"
                    >
                        {{ form.errors.file }}
                    </p>
                </div>

                <div
                    v-if="summary"
                    class="rounded-md border bg-muted/30 p-3 text-sm"
                >
                    <p>
                        Berhasil ditambahkan:
                        <strong>{{ summary.created }}</strong>
                    </p>
                    <p>
                        Dilewati: <strong>{{ summary.skipped }}</strong>
                    </p>
                    <ul
                        v-if="summary.errors.length > 0"
                        class="mt-2 max-h-48 list-disc space-y-1 overflow-y-auto pl-4 text-destructive"
                    >
                        <li v-for="(err, i) in summary.errors" :key="i">
                            {{ err }}
                        </li>
                    </ul>
                </div>

                <div class="flex justify-end gap-2 pt-2">
                    <Button type="button" variant="outline" @click="close">
                        Tutup
                    </Button>
                    <Button
                        type="submit"
                        :disabled="form.processing || !form.file"
                    >
                        Import
                    </Button>
                </div>
            </form>
        </div>
    </div>
</template>
