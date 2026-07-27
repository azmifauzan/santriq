<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class SendSuperAdminTelegramAlert implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /**
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return [10, 30, 60];
    }

    public function __construct(public string $messageText) {}

    public function handle(): void
    {
        $botToken = config('services.telegram.bot_token');
        $chatId = config('services.telegram.super_admin_chat_id');

        if (empty($botToken) || empty($chatId)) {
            Log::warning('SendSuperAdminTelegramAlert dilewati: bot_token/super_admin_chat_id belum dikonfigurasi.');

            return;
        }

        $response = Http::post("https://api.telegram.org/bot{$botToken}/sendMessage", [
            'chat_id' => $chatId,
            'text' => $this->messageText,
            'parse_mode' => 'HTML',
        ]);

        if ($response->failed()) {
            throw new RuntimeException('Telegram API error: '.$response->body());
        }
    }
}
