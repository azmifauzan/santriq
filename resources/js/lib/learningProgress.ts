export interface LearningProgress {
    learning_method: string | null;
    level: string | null;
    page_start: number | null;
    page_end: number | null;
    surah: string | null;
    ayah_start: number | null;
    ayah_end: number | null;
    activity_type: string | null;
    fluency: string | null;
    practice_note: string | null;
}

export const activityLabels: Record<string, string> = {
    bacaan: 'Bacaan',
    ziyadah: 'Hafalan baru (ziyadah)',
    murajaah: 'Murajaah',
};

export const fluencyLabels: Record<string, string> = {
    lancar: 'Lancar',
    perlu_latihan: 'Perlu latihan',
    mengulang: 'Mengulang',
};
