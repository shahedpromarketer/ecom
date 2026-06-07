<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    protected $fillable = [
        'user_id', 'action', 'action_performed', 'subject_type', 'subject_id',
        'description', 'properties', 'old_data', 'updated_data',
        'ip_address', 'user_agent',
    ];

    protected $casts = [
        'properties'   => 'array',
        'old_data'     => 'array',
        'updated_data' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopeForSubject($query, string $type, int $id)
    {
        return $query->where('subject_type', $type)->where('subject_id', $id);
    }

    public static function log(
        string $action,
        $subject,
        string $description,
        array $properties = [],
        array $oldData = [],
        array $updatedData = []
    ): void {
        try {
            static::create([
                'user_id'          => auth()->id(),
                'action'           => $action,
                'action_performed' => $action,
                'subject_type'     => get_class($subject),
                'subject_id'       => $subject->id,
                'description'      => $description,
                'properties'       => $properties,
                'old_data'         => $oldData ?: null,
                'updated_data'     => $updatedData ?: null,
                'ip_address'       => request()->ip(),
                'user_agent'       => request()->userAgent(),
            ]);
        } catch (\Exception $e) {
            \Log::error('ActivityLog failed: ' . $e->getMessage());
        }
    }
}
