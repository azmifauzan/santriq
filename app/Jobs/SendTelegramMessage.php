<?php

namespace App\Jobs;

use App\Models\Guardian;
use App\Models\TelegramMessage;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

class SendTelegramMessage implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /**
     * The outbox row tracking this message. Created when the job is dispatched so
     * every retry updates the same row instead of logging a new one.
     */
    public TelegramMessage $log;

    /**
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return [10, 30, 60];
    }

    public function __construct(
        public Guardian $guardian,
        public string $messageText
    ) {
        $this->log = TelegramMessage::create([
            'tenant_id' => $guardian->tenant_id,
            'guardian_id' => $guardian->id,
            'payload' => [
                'chat_id' => $guardian->telegram_chat_id,
                'text' => $messageText,
            ],
            'status' => 'pending',
            'attempts' => 0,
        ]);
    }

    public function handle(): void
    {
        if (empty($this->guardian->telegram_chat_id)) {
            $this->log->update([
                'status' => 'failed',
                'attempts' => $this->attempts(),
                'error' => 'Guardian is not linked to Telegram',
            ]);

            return;
        }

        $botToken = config('services.telegram.bot_token');

        if (empty($botToken)) {
            $this->log->update([
                'status' => 'failed',
                'attempts' => $this->attempts(),
                'error' => 'TELEGRAM_BOT_TOKEN is not configured',
            ]);

            return;
        }

        $response = Http::post("https://api.telegram.org/bot{$botToken}/sendMessage", [
            'chat_id' => $this->guardian->telegram_chat_id,
            'text' => $this->messageText,
            'parse_mode' => 'HTML',
        ]);

        if ($response->failed()) {
            $this->log->update([
                'status' => 'failed',
                'attempts' => $this->attempts(),
                'error' => $response->body(),
            ]);

            throw new RuntimeException('Telegram API error: '.$response->body());
        }

        $this->log->update([
            'status' => 'sent',
            'sent_at' => now(),
            'attempts' => $this->attempts(),
            'error' => null,
        ]);
    }

    public function failed(?Throwable $exception): void
    {
        $this->log->update([
            'status' => 'failed',
            'error' => $exception?->getMessage() ?? 'Max retries reached',
        ]);
    }
}
