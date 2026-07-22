<?php

namespace App\Models;

use App\Concerns\BelongsToTenant;
use Database\Factories\StudentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property int $tenant_id
 * @property int|null $classroom_id
 * @property string $nis
 * @property string $name
 * @property string $gender
 * @property string|null $birth_date
 * @property string $qr_token
 * @property string $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['tenant_id', 'classroom_id', 'nis', 'name', 'gender', 'birth_date', 'qr_token', 'status'])]
class Student extends Model
{
    /** @use HasFactory<StudentFactory> */
    use BelongsToTenant, HasFactory;

    protected static function booted(): void
    {
        static::creating(function (Student $student) {
            if (empty($student->qr_token)) {
                $student->qr_token = (string) Str::ulid();
            }
        });
    }

    /**
     * Get the classroom of the student.
     *
     * @return BelongsTo<Classroom, $this>
     */
    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    /**
     * Get the guardians of the student.
     *
     * @return BelongsToMany<Guardian, $this>
     */
    public function guardians(): BelongsToMany
    {
        return $this->belongsToMany(Guardian::class, 'guardian_student')
            ->withPivot('relation')
            ->withTimestamps();
    }
}
