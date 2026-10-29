<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/** A reminder to call a customer on a given day. */
class Followup extends Model
{
    use \App\Models\Concerns\BelongsToTenant;

    public const TAGS = [
        'reorder'        => 'Re-order / repeat sale',
        'feedback'       => 'Product feedback',
        'confirm'        => 'Confirm order',
        'payment'        => 'Payment / COD issue',
        'delivery_issue' => 'Delivery problem',
        'upsell'         => 'Upsell / new product',
        'other'          => 'Other',
    ];

    public const STATUSES = [
        'listed'   => 'To call',
        'done'     => 'Done',
        'negative' => 'Negative',
    ];

    protected $fillable = [
        'tenant_id', 'customer_id', 'order_id', 'assigned_to', 'created_by',
        'scheduled_for', 'status', 'tag', 'note', 'result_note', 'done_at', 'done_by',
    ];

    protected $casts = [
        'scheduled_for' => 'date',
        'done_at'       => 'datetime',
    ];

    public function customer()   { return $this->belongsTo(Customer::class); }
    public function order()      { return $this->belongsTo(Order::class); }
    public function assignee()   { return $this->belongsTo(User::class, 'assigned_to'); }
    public function creator()    { return $this->belongsTo(User::class, 'created_by'); }
    public function doneByUser() { return $this->belongsTo(User::class, 'done_by'); }

    /** Staff without view_all_followups only see the follow-ups assigned to them. */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        return $user->can('view_all_followups') ? $query : $query->where('assigned_to', $user->id);
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('status', 'listed');
    }

    public function getIsOverdueAttribute(): bool
    {
        return $this->status === 'listed' && $this->scheduled_for->lt(today());
    }
}
