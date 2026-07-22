<?php

namespace App\Models;

use App\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $tenant_id
 * @property int $guardian_id
 * @property array<string, mixed> $payload
 * @property string $status
 * @property int $attempts
 * @property Carbon|null $sent_at
 * @property string|null $error
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['tenant_id', 'guardian_id', 'payload', 'status', 'attempts', 'sent_at', 'error'])]
class TelegramMessage extends Model
{
    use BelongsToTenant;

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'sent_at' => 'datetime',
        ];
    }

    /**
     * Get the guardian for this telegram message.
     *
     * @return BelongsTo<Guardian, $this>
     */
    public function guardian(): BelongsTo
    {
        return $this->belongsTo(Guardian::class);
    }
}
