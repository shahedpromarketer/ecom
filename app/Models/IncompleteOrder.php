<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** A checkout the visitor started (phone typed) but did not finish — shown as "Failed orders". */
class IncompleteOrder extends Model
{
    use \App\Models\Concerns\BelongsToTenant;

    public const DELETE_REASONS = ['Fake / test', 'Duplicate', 'Already ordered', 'Not interested', 'Wrong number', 'Other'];

    protected $fillable = [
        'tenant_id', 'wordpress_site_id', 'session_key', 'phone', 'name', 'email', 'address', 'district',
        'items', 'total', 'page_url', 'ip', 'user_agent', 'status', 'delete_reason', 'order_id',
        'assigned_to', 'note', 'last_activity_at',
    ];

    protected $casts = [
        'items'            => 'array',
        'total'            => 'decimal:2',
        'last_activity_at' => 'datetime',
    ];

    public function store()    { return $this->belongsTo(WordPressSite::class, 'wordpress_site_id'); }
    public function order()    { return $this->belongsTo(Order::class); }
    public function assignee() { return $this->belongsTo(User::class, 'assigned_to'); }

    /** The same number's history in this shop (for the "is this a real buyer?" call). */
    public function customer()
    {
        return Customer::where('phone', $this->phone)->first();
    }

    public function itemsSummary(): string
    {
        return collect($this->items ?? [])
            ->map(fn ($i) => ($i['qty'] ?? 1) . '× ' . ($i['name'] ?? '?') . (! empty($i['variant']) ? ' (' . $i['variant'] . ')' : ''))
            ->implode(', ');
    }
}
