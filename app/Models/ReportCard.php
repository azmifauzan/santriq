<?php

namespace App\Models;

use App\Concerns\BelongsToTenant;
use Database\Factories\ReportCardFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $tenant_id
 * @property int $student_id
 * @property string $start_date
 * @property string $end_date
 * @property array<string, mixed> $snapshot
 * @property string|null $teacher_note
 * @property int|null $published_by
 * @property Carbon $published_at
 */
#[Fillable(['tenant_id', 'student_id', 'start_date', 'end_date', 'snapshot', 'teacher_note', 'published_by', 'published_at'])]
class ReportCard extends Model
{
    /** @use HasFactory<ReportCardFactory> */
    use BelongsToTenant, HasFactory;

    protected function casts(): array
    {
        return [
            'start_date' => 'date:Y-m-d',
            'end_date' => 'date:Y-m-d',
            'snapshot' => 'array',
            'published_at' => 'datetime',
        ];
    }
}
