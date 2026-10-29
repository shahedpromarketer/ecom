<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Soft delete + remember who deleted it. Deleted rows show up in the Recycle Bin,
 * where they can be restored or (owners only) removed for good.
 */
trait GoesToRecycleBin
{
    use SoftDeletes;

    public static function bootGoesToRecycleBin(): void
    {
        static::softDeleted(function ($model) {
            $model->forceFill(['deleted_by' => auth()->id()])->saveQuietly();
        });

        static::restoring(function ($model) {
            $model->deleted_by = null;
        });
    }

    public function deletedByUser()
    {
        return $this->belongsTo(\App\Models\User::class, 'deleted_by');
    }
}
