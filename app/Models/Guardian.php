<?php

namespace App\Models;

use App\Concerns\BelongsToTenant;
use Database\Factories\GuardianFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property int $tenant_id
 * @property string $name
 * @property string|null $phone
 * @property string|null $telegram_chat_id
 * @property string|null $link_token
 * @property Carbon|null $linked_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['tenant_id', 'name', 'phone', 'telegram_chat_id', 'link_token', 'linked_at'])]
class Guardian extends Model
{
    /** @use HasFactory<GuardianFactory> */
    use BelongsToTenant, HasFactory;

    protected static function booted(): void
    {
        static::creating(function (Guardian $guardian) {
            if (empty($guardian->link_token)) {
                $guardian->link_token = Str::random(32);
            }
        });
    }

    /**
     * Get the students for this guardian.
     *
     * @return BelongsToMany<Student, $this>
     */
    public function students(): BelongsToMany
    {
        return $this->belongsToMany(Student::class, 'guardian_student')
            ->withPivot('relation')
            ->withTimestamps();
    }
}
