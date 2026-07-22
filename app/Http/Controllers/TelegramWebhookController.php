<?php

namespace App\Http\Controllers;

use App\Models\Achievement;
use App\Models\Attendance;
use App\Models\Guardian;
use App\Models\Invoice;
use App\Models\LeaveRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use InvalidArgumentException;

class TelegramWebhookController extends Controller
{
    public function handle(Request $request): JsonResponse
    {
        $secretToken = config('services.telegram.secret_token');

        if (empty($secretToken)) {
            // The endpoint is public and CSRF-exempt: without a secret anyone could
            // drive the bot into replying to arbitrary chat ids.
            if (! app()->environment('local', 'testing')) {
                return response()->json(['message' => 'Webhook secret is not configured'], 403);
            }
        } elseif (! hash_equals($secretToken, (string) $request->header('X-Telegram-Bot-Api-Secret-Token'))) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $update = $request->all();
        $message = $update['message'] ?? null;

        if (! $message || ! isset($message['text'], $message['chat']['id'])) {
            return response()->json(['status' => 'ignored']);
        }

        $chatId = (string) $message['chat']['id'];
        $text = trim($message['text']);

        if (Str::startsWith($text, '/start')) {
            $parts = explode(' ', $text);
            $token = $parts[1] ?? null;

            if ($token) {
                $guardian = Guardian::where('link_token', $token)->first();
                if ($guardian) {
                    $guardian->update([
                        'telegram_chat_id' => $chatId,
                        'linked_at' => now(),
                    ]);

                    $studentNames = $guardian->students->pluck('name')->join(', ');

                    $reply = "✅ <b>Akun Telegram Berhasil Terhubung!</b>\n\n".
                        "Selamat datang, <b>{$guardian->name}</b>.\n".
                        'Anda telah terhubung dengan data santri: <b>'.($studentNames ?: 'Santri')."</b>.\n\n".
                        "Gunakan perintah berikut:\n".
                        "• /kehadiran — Cek status presensi anak\n".
                        "• /prestasi — Cek pencapaian & hafalan anak\n".
                        "• /tagihan — Cek status SPP & tagihan\n".
                        '• /izin — Ajukan izin/sakit, contoh: <code>/izin sakit 2026-07-23 2026-07-24 demam</code>';

                    $this->sendTelegramReply($chatId, $reply);

                    return response()->json(['status' => 'linked']);
                }
            }

            $this->sendTelegramReply($chatId, '❌ Kode penautan tidak valid. Silakan periksa kembali tautan yang diberikan oleh pengurus TPA/TPQ.');

            return response()->json(['status' => 'invalid_token']);
        }

        $guardian = Guardian::where('telegram_chat_id', $chatId)->first();

        if (! $guardian) {
            $this->sendTelegramReply($chatId, '⚠️ Akun Telegram Anda belum terhubung. Silakan hubungi pengurus TPA/TPQ untuk mendapatkan kode penautan.');

            return response()->json(['status' => 'not_linked']);
        }

        if (Str::startsWith($text, '/kehadiran')) {
            $reply = $this->buildAttendanceSummary($guardian);
            $this->sendTelegramReply($chatId, $reply);

            return response()->json(['status' => 'handled_kehadiran']);
        }

        if (Str::startsWith($text, '/prestasi')) {
            $reply = $this->buildAchievementSummary($guardian);
            $this->sendTelegramReply($chatId, $reply);

            return response()->json(['status' => 'handled_prestasi']);
        }

        if (Str::startsWith($text, '/tagihan')) {
            $reply = $this->buildInvoiceSummary($guardian);
            $this->sendTelegramReply($chatId, $reply);

            return response()->json(['status' => 'handled_tagihan']);
        }

        if (Str::startsWith($text, '/izin')) {
            [$reply, $status] = $this->handleLeaveRequestCommand($guardian, $text);
            $this->sendTelegramReply($chatId, $reply);

            return response()->json(['status' => $status]);
        }

        $this->sendTelegramReply($chatId, "📌 Perintah tidak dikenali.\n\nGunakan perintah:\n• /kehadiran\n• /prestasi\n• /tagihan\n• /izin");

        return response()->json(['status' => 'unknown_command']);
    }

    /**
     * Handle "/izin <sakit|izin> <mulai> <selesai> [alasan]", optionally prefixed
     * with the student NIS when a guardian has more than one child.
     *
     * @return array{0: string, 1: string}
     */
    private function handleLeaveRequestCommand(Guardian $guardian, string $text): array
    {
        $usage = "Format: <code>/izin sakit 2026-07-23 2026-07-24 demam</code>\n".
            'Jika Anda memiliki lebih dari satu anak, awali dengan NIS: <code>/izin 12345 sakit 2026-07-23 2026-07-24 demam</code>';

        $parts = preg_split('/\s+/', trim($text)) ?: [];
        array_shift($parts);

        $students = $guardian->students;

        if ($students->isEmpty()) {
            return ['Belum ada data santri yang terhubung dengan akun Anda.', 'leave_no_student'];
        }

        $student = $students->first();

        if (isset($parts[0]) && ($match = $students->firstWhere('nis', $parts[0])) !== null) {
            $student = $match;
            array_shift($parts);
        } elseif ($students->count() > 1) {
            $list = $students->map(fn ($s) => "• {$s->nis} — {$s->name}")->join("\n");

            return ["Anda memiliki lebih dari satu santri. Sebutkan NIS:\n{$list}\n\n{$usage}", 'leave_needs_nis'];
        }

        $type = $parts[0] ?? null;

        if (! in_array($type, ['sakit', 'izin'], true)) {
            return ["Jenis izin harus <b>sakit</b> atau <b>izin</b>.\n\n{$usage}", 'leave_invalid_type'];
        }

        try {
            $startDate = Carbon::createFromFormat('Y-m-d', $parts[1] ?? '')->startOfDay();
            $endDate = Carbon::createFromFormat('Y-m-d', $parts[2] ?? '')->startOfDay();
        } catch (InvalidArgumentException) {
            return ["Tanggal harus berformat YYYY-MM-DD.\n\n{$usage}", 'leave_invalid_date'];
        }

        if ($endDate->lt($startDate)) {
            return ["Tanggal selesai tidak boleh sebelum tanggal mulai.\n\n{$usage}", 'leave_invalid_range'];
        }

        $reason = trim(implode(' ', array_slice($parts, 3)));

        LeaveRequest::create([
            'tenant_id' => $student->tenant_id,
            'student_id' => $student->id,
            'type' => $type,
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
            'reason' => $reason !== '' ? $reason : null,
            'status' => 'pending',
        ]);

        return [
            "📨 <b>Pengajuan izin terkirim</b>\n\n".
            "Santri: <b>{$student->name}</b>\n".
            'Jenis: <b>'.strtoupper($type)."</b>\n".
            'Tanggal: <b>'.$startDate->format('Y-m-d').'</b> s/d <b>'.$endDate->format('Y-m-d')."</b>\n\n".
            'Menunggu persetujuan pengurus. Anda akan menerima notifikasi hasilnya.',
            'leave_created',
        ];
    }

    private function sendTelegramReply(string $chatId, string $text): void
    {
        $botToken = config('services.telegram.bot_token');
        if (empty($botToken)) {
            return;
        }

        Http::post("https://api.telegram.org/bot{$botToken}/sendMessage", [
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => 'HTML',
        ]);
    }

    private function buildAttendanceSummary(Guardian $guardian): string
    {
        $students = $guardian->students;
        if ($students->isEmpty()) {
            return 'Belum ada data santri yang terhubung dengan akun Anda.';
        }

        $lines = ['📋 <b>Ringkasan Presensi Hari Ini ('.now()->format('d/m/Y').")</b>\n"];
        foreach ($students as $student) {
            $todayAtt = Attendance::where('student_id', $student->id)
                ->where('date', now()->format('Y-m-d'))
                ->first();

            if (! $todayAtt) {
                $lines[] = "• <b>{$student->name}</b>: Belum ada catatan presensi hari ini.";
            } else {
                $in = $todayAtt->checked_in_at ? $todayAtt->checked_in_at->format('H:i') : '-';
                $out = $todayAtt->checked_out_at ? $todayAtt->checked_out_at->format('H:i') : '-';
                $lines[] = "• <b>{$student->name}</b>: Status <b>".strtoupper($todayAtt->status)."</b> (Masuk: {$in}, Pulang: {$out})";
            }
        }

        return implode("\n", $lines);
    }

    private function buildAchievementSummary(Guardian $guardian): string
    {
        $students = $guardian->students;
        if ($students->isEmpty()) {
            return 'Belum ada data santri yang terhubung dengan akun Anda.';
        }

        $lines = ["🏆 <b>Pencapaian Terbaru Santri</b>\n"];
        foreach ($students as $student) {
            $achievements = Achievement::where('student_id', $student->id)
                ->latest('achieved_at')
                ->take(3)
                ->get();

            $lines[] = "<b>{$student->name}</b>:";
            if ($achievements->isEmpty()) {
                $lines[] = '  (Belum ada pencapaian dicatat)';
            } else {
                foreach ($achievements as $ach) {
                    $score = $ach->score !== null ? " [Nilai: {$ach->score}]" : '';
                    $lines[] = "  - {$ach->category}: {$ach->title}{$score} ({$ach->achieved_at})";
                }
            }
        }

        return implode("\n", $lines);
    }

    private function buildInvoiceSummary(Guardian $guardian): string
    {
        $students = $guardian->students;
        if ($students->isEmpty()) {
            return 'Belum ada data santri yang terhubung dengan akun Anda.';
        }

        $lines = ["💳 <b>Status Tagihan & SPP</b>\n"];
        foreach ($students as $student) {
            $invoices = Invoice::where('student_id', $student->id)
                ->latest('due_date')
                ->take(3)
                ->get();

            $lines[] = "<b>{$student->name}</b>:";
            if ($invoices->isEmpty()) {
                $lines[] = '  (Tidak ada tagihan)';
            } else {
                foreach ($invoices as $inv) {
                    $statusStr = $inv->status === 'paid' ? 'LUNAS' : ($inv->status === 'unpaid' ? 'BELUM LUNAS' : 'BATAL');
                    $lines[] = '  - Periode '.$inv->period.': Rp '.number_format($inv->amount, 0, ',', '.')." ({$statusStr})";
                }
            }
        }

        return implode("\n", $lines);
    }
}
