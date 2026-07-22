<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { onMounted, onUnmounted, ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/AppLayout.vue';

defineOptions({
    layout: AppLayout,
});

const videoRef = ref<HTMLVideoElement | null>(null);
const manualToken = ref('');
const isScanning = ref(false);
const scanMessage = ref<{
    type: 'success' | 'error' | 'info';
    text: string;
    studentName?: string;
    time?: string;
} | null>(null);
const supportsBarcodeDetector = ref(false);
let stream: MediaStream | null = null;
let scanInterval: number | null = null;
let isProcessingScan = false;

onMounted(async () => {
    if ('BarcodeDetector' in window) {
        supportsBarcodeDetector.value = true;
        await startCamera();
    }
});

onUnmounted(() => {
    stopCamera();
});

async function startCamera() {
    try {
        stream = await navigator.mediaDevices.getUserMedia({
            video: { facingMode: 'environment' },
        });

        if (videoRef.value) {
            videoRef.value.srcObject = stream;
            await videoRef.value.play();
            isScanning.value = true;

            const barcodeDetector = new (window as any).BarcodeDetector({
                formats: ['qr_code'],
            });

            scanInterval = window.setInterval(async () => {
                if (!videoRef.value || isProcessingScan) {
                    return;
                }

                try {
                    const barcodes = await barcodeDetector.detect(
                        videoRef.value,
                    );

                    if (barcodes.length > 0) {
                        const rawValue = barcodes[0].rawValue;

                        if (rawValue) {
                            processToken(rawValue);
                        }
                    }
                } catch {
                    // ignore detection frames errors
                }
            }, 500);
        }
    } catch {
        scanMessage.value = {
            type: 'info',
            text: 'Kamera tidak dapat diakses. Anda dapat menggunakan pemindai USB atau mengetikkan token secara manual.',
        };
    }
}

function stopCamera() {
    if (scanInterval) {
        clearInterval(scanInterval);
        scanInterval = null;
    }

    if (stream) {
        stream.getTracks().forEach((track) => track.stop());
        stream = null;
    }

    isScanning.value = false;
}

async function processToken(qrToken: string) {
    if (isProcessingScan) {
        return;
    }

    isProcessingScan = true;

    try {
        const response = await fetch('/attendance/scan', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN':
                    (
                        document.querySelector(
                            'meta[name="csrf-token"]',
                        ) as HTMLMetaElement
                    )?.content || '',
                Accept: 'application/json',
            },
            body: JSON.stringify({ qr_token: qrToken }),
        });

        const data = await response.json();

        if (response.ok && data.success) {
            playBeep('success');
            scanMessage.value = {
                type: 'success',
                text: data.message,
                studentName: data.student?.name,
                time: data.time,
            };
        } else {
            playBeep('error');
            scanMessage.value = {
                type: 'error',
                text: data.message || 'Presensi gagal.',
            };
        }
    } catch {
        playBeep('error');
        scanMessage.value = {
            type: 'error',
            text: 'Terjadi kesalahan koneksi saat memproses presensi.',
        };
    } finally {
        setTimeout(() => {
            isProcessingScan = false;
        }, 2000);
    }
}

function submitManual() {
    if (!manualToken.value.trim()) {
        return;
    }

    processToken(manualToken.value.trim());
    manualToken.value = '';
}

function playBeep(type: 'success' | 'error') {
    try {
        const ctx = new (
            window.AudioContext || (window as any).webkitAudioContext
        )();
        const osc = ctx.createOscillator();
        const gain = ctx.createGain();

        osc.type = type === 'success' ? 'sine' : 'sawtooth';
        osc.frequency.setValueAtTime(
            type === 'success' ? 880 : 300,
            ctx.currentTime,
        );

        gain.gain.setValueAtTime(0.3, ctx.currentTime);
        gain.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.3);

        osc.connect(gain);
        gain.connect(ctx.destination);

        osc.start();
        osc.stop(ctx.currentTime + 0.3);
    } catch {
        // Audio fallback ignored
    }
}
</script>

<template>
    <Head title="Pindai QR Presensi" />

    <div class="mx-auto flex max-w-4xl flex-col gap-6 p-6">
        <div>
            <h1 class="text-2xl font-bold tracking-tight">
                Pemindaian QR Presensi
            </h1>
            <p class="text-sm text-muted-foreground">
                Arahkan kartu QR santri ke kamera perangkat untuk pencatatan
                presensi masuk/pulang otomatis.
            </p>
        </div>

        <!-- Banner Feedback Status -->
        <div
            v-if="scanMessage"
            class="rounded-xl p-4 text-center shadow-sm transition-all"
            :class="{
                'bg-emerald-500 text-lg font-semibold text-white':
                    scanMessage.type === 'success',
                'bg-red-500 text-lg font-semibold text-white':
                    scanMessage.type === 'error',
                'border border-blue-200 bg-blue-100 text-blue-900':
                    scanMessage.type === 'info',
            }"
        >
            <div class="mb-1 text-xl">
                {{
                    scanMessage.type === 'success'
                        ? '✅'
                        : scanMessage.type === 'error'
                          ? '❌'
                          : 'ℹ️'
                }}
                {{ scanMessage.text }}
            </div>
            <div
                v-if="scanMessage.studentName && scanMessage.time"
                class="text-sm font-normal opacity-90"
            >
                Santri:
                <span class="font-bold">{{ scanMessage.studentName }}</span> |
                Waktu: <span class="font-bold">{{ scanMessage.time }}</span>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
            <!-- Camera View -->
            <div
                class="relative flex min-h-[300px] flex-col items-center justify-center overflow-hidden rounded-xl border bg-slate-900 p-4 text-white"
            >
                <video
                    ref="videoRef"
                    class="h-full w-full rounded-lg object-cover"
                    autoplay
                    playsinline
                    muted
                ></video>
                <div v-if="!isScanning" class="p-6 text-center text-slate-400">
                    <p class="text-base">Kamera Tidak Aktif</p>
                    <p class="mt-1 text-xs">
                        Pastikan izin kamera diberikan atau gunakan input manual
                        di samping.
                    </p>
                </div>
                <div
                    v-else
                    class="absolute bottom-4 animate-pulse rounded-full bg-black/60 px-3 py-1 font-mono text-xs text-emerald-400"
                >
                    🎥 Kamera Aktif — Pindai Kode QR
                </div>
            </div>

            <!-- Manual Input Fallback -->
            <div
                class="flex flex-col justify-between rounded-xl border bg-card p-6"
            >
                <div>
                    <h3 class="mb-2 text-base font-semibold">
                        Input Presensi Manual / Barcode Scanner
                    </h3>
                    <p class="mb-4 text-sm text-muted-foreground">
                        Jika menggunakan alat pemindai fisik (USB) atau ingin
                        memasukkan token secara manual.
                    </p>

                    <form @submit.prevent="submitManual" class="space-y-4">
                        <div>
                            <Input
                                v-model="manualToken"
                                placeholder="Tempel/Ketik token QR Santri..."
                                autofocus
                            />
                        </div>
                        <Button type="submit" class="w-full">
                            Proses Presensi Manual
                        </Button>
                    </form>
                </div>

                <div
                    class="mt-6 space-y-1 border-t pt-4 text-xs text-muted-foreground"
                >
                    <p>💡 **Tips Presensi:**</p>
                    <p>• Pindai pertama di hari yang sama = Presensi MASUK.</p>
                    <p>• Pindai kedua di hari yang sama = Presensi PULANG.</p>
                </div>
            </div>
        </div>
    </div>
</template>
