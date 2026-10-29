<?php

namespace App\Models\Concerns;

use App\Support\Tenancy;
use Illuminate\Database\Eloquent\Builder;

/**
 * For child rows without a tenant_id (order items, product variants, …).
 * They are only visible when their parent row is visible to the current
 * tenant — the parent's own tenant scope does the work inside whereHas().
 *
 * The model must define tenantParentRelation(), e.g. return 'order'.
 */
trait BelongsToTenantThroughParent
{
    public static function bootBelongsToTenantThroughParent(): void
    {
        static::addGlobalScope('tenant', function (Builder $query) {
            if (! Tenancy::active()) {
                return;
            }

            $query->whereHas($query->getModel()->tenantParentRelation());
        });
    }

    abstract public function tenantParentRelation(): string;
}
