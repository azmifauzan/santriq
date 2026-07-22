<?php

namespace App\Concerns;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

trait BelongsToTenant
{
    /**
     * Boot the trait.
     */
    public static function bootBelongsToTenant(): void
    {
        static::addGlobalScope('tenant', function (Builder $builder) {
            if (Auth::check() && Auth::user()->tenant_id !== null) {
                $table = $builder->getModel()->getTable();
                $builder->where($table.'.tenant_id', Auth::user()->tenant_id);
            }
        });

        static::creating(function ($model) {
            if (! $model->tenant_id && Auth::check() && Auth::user()->tenant_id !== null) {
                $model->tenant_id = Auth::user()->tenant_id;
            }
        });
    }

    /**
     * Get the tenant that owns the model.
     *
     * @return BelongsTo<Tenant, $this>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
