<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    protected $fillable = [
        'user_id', 'action', 'subject_type', 'subject_id',
        'description', 'properties', 'ip_address', 'user_agent',
    ];

    protected $casts = ['properties' => 'array'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopeForSubject($query, string $type, int $id)
    {
        return $query->where('subject_type', $type)->where('subject_id', $id);
    }

    public static function log(string $action, $subject, string $description, array $properties = []): void
    {
        try {
            static::create([
                'user_id'      => auth()->id(),
                'action'       => $action,
                'subject_type' => get_class($subject),
                'subject_id'   => $subject->id,
                'description'  => $description,
                'properties'   => $properties,
                'ip_address'   => request()->ip(),
                'user_agent'   => request()->userAgent(),
            ]);
        } catch (\Exception $e) {
            \Log::error('ActivityLog failed: ' . $e->getMessage());
        }
    }
}
