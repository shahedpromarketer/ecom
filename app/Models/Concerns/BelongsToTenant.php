<?php

namespace App\Models\Concerns;

use App\Models\Tenant;
use App\Support\Tenancy;
use Illuminate\Database\Eloquent\Builder;

/**
 * For models with their own tenant_id column.
 *
 * Every query is limited to the current tenant, and new rows get the
 * current tenant's id. Super admins and console code are not limited.
 *
 * Override includesSharedTenantRows() to also return rows with
 * tenant_id = NULL (defaults shared by every shop).
 */
trait BelongsToTenant
{
    public static function bootBelongsToTenant(): void
    {
        static::addGlobalScope('tenant', function (Builder $query) {
            if (! Tenancy::active()) {
                return;
            }

            $model  = $query->getModel();
            $column = $model->qualifyColumn('tenant_id');
            $id     = Tenancy::id();

            if ($model->includesSharedTenantRows()) {
                $query->where(fn (Builder $q) => $q->where($column, $id)->orWhereNull($column));
            } else {
                $query->where($column, $id);
            }
        });

        static::creating(function ($model) {
            if (empty($model->tenant_id) && Tenancy::active() && Tenancy::id()) {
                $model->tenant_id = Tenancy::id();
            }
        });
    }

    public function includesSharedTenantRows(): bool
    {
        return false;
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}
