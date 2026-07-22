<?php

namespace App\Models;

use App\Concerns\BelongsToTenant;
use Database\Factories\AttendanceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $tenant_id
 * @property int $student_id
 * @property string $date
 * @property Carbon|null $checked_in_at
 * @property Carbon|null $checked_out_at
 * @property string $status
 * @property int|null $recorded_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['tenant_id', 'student_id', 'date', 'checked_in_at', 'checked_out_at', 'status', 'recorded_by'])]
class Attendance extends Model
{
    /** @use HasFactory<AttendanceFactory> */
    use BelongsToTenant, HasFactory;

    protected function casts(): array
    {
        return [
            'date' => 'date:Y-m-d',
            'checked_in_at' => 'datetime',
            'checked_out_at' => 'datetime',
        ];
    }

    /**
     * Get the student associated with the attendance.
     *
     * @return BelongsTo<Student, $this>
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * Get the user who recorded the attendance.
     *
     * @return BelongsTo<User, $this>
     */
    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
