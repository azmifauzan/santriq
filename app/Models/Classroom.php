<?php

namespace App\Models;

use App\Concerns\BelongsToTenant;
use Database\Factories\ClassroomFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $tenant_id
 * @property string $name
 * @property string|null $level
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['tenant_id', 'name', 'level'])]
class Classroom extends Model
{
    /** @use HasFactory<ClassroomFactory> */
    use BelongsToTenant, HasFactory;

    /**
     * Get students in this classroom.
     *
     * @return HasMany<Student, $this>
     */
    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }
}
