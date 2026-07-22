<?php

namespace App\Models;

use App\Concerns\BelongsToTenant;
use Database\Factories\LeaveRequestFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $tenant_id
 * @property int $student_id
 * @property string $type
 * @property string $start_date
 * @property string $end_date
 * @property string|null $reason
 * @property string $status
 * @property int|null $reviewed_by
 * @property Carbon|null $reviewed_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['tenant_id', 'student_id', 'type', 'start_date', 'end_date', 'reason', 'status', 'reviewed_by', 'reviewed_at'])]
class LeaveRequest extends Model
{
    /** @use HasFactory<LeaveRequestFactory> */
    use BelongsToTenant, HasFactory;

    protected function casts(): array
    {
        return [
            'start_date' => 'date:Y-m-d',
            'end_date' => 'date:Y-m-d',
            'reviewed_at' => 'datetime',
        ];
    }

    /**
     * Get student for this leave request.
     *
     * @return BelongsTo<Student, $this>
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * Get reviewer user.
     *
     * @return BelongsTo<User, $this>
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
