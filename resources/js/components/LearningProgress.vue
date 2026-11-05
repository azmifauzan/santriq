<script setup lang="ts">
import { activityLabels, fluencyLabels } from '@/lib/learningProgress';
import type { LearningProgress } from '@/lib/learningProgress';

defineProps<{ progress: LearningProgress }>();
</script>

<template>
    <div class="space-y-1 text-sm">
        <p v-if="progress.learning_method || progress.level">
            {{ progress.learning_method
            }}<span v-if="progress.level">
                · Jilid/tingkat {{ progress.level }}</span
            >
        </p>
        <p v-if="progress.page_start">
            Halaman {{ progress.page_start
            }}<span
                v-if="
                    progress.page_end &&
                    progress.page_end !== progress.page_start
                "
                >–{{ progress.page_end }}</span
            >
        </p>
        <p v-if="progress.surah">
            {{ progress.surah
            }}<span v-if="progress.ayah_start">
                · Ayat {{ progress.ayah_start
                }}<span
                    v-if="
                        progress.ayah_end &&
                        progress.ayah_end !== progress.ayah_start
                    "
                    >–{{ progress.ayah_end }}</span
                ></span
            >
        </p>
        <p v-if="progress.activity_type || progress.fluency">
            {{ activityLabels[progress.activity_type ?? '']
            }}<span v-if="progress.activity_type && progress.fluency"> · </span
            >{{ fluencyLabels[progress.fluency ?? ''] }}
        </p>
        <p
            v-if="progress.practice_note"
            class="break-words whitespace-pre-wrap"
        >
            Latihan berikutnya: {{ progress.practice_note }}
        </p>
    </div>
</template>
