<?php

namespace App\Models;

use App\Concerns\BelongsToTenant;
use Database\Factories\AchievementFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $tenant_id
 * @property int $student_id
 * @property string $category
 * @property string $title
 * @property string|null $note
 * @property int|null $score
 * @property string $achieved_at
 * @property string|null $learning_method
 * @property string|null $level
 * @property int|null $page_start
 * @property int|null $page_end
 * @property string|null $surah
 * @property int|null $ayah_start
 * @property int|null $ayah_end
 * @property string|null $activity_type
 * @property string|null $fluency
 * @property string|null $practice_note
 * @property int|null $recorded_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['tenant_id', 'student_id', 'category', 'title', 'note', 'score', 'achieved_at', 'recorded_by', 'learning_method', 'level', 'page_start', 'page_end', 'surah', 'ayah_start', 'ayah_end', 'activity_type', 'fluency', 'practice_note'])]
class Achievement extends Model
{
    /** @use HasFactory<AchievementFactory> */
    use BelongsToTenant, HasFactory;

    protected function casts(): array
    {
        return [
            'achieved_at' => 'date:Y-m-d',
        ];
    }

    /**
     * Get student for this achievement.
     *
     * @return BelongsTo<Student, $this>
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * Get user who recorded this achievement.
     *
     * @return BelongsTo<User, $this>
     */
    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
