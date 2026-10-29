<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * A potential order: someone who asked about a product (Messenger, phone,
 * landing page, imported list…) but has not ordered yet.
 */
class Lead extends Model
{
    use \App\Models\Concerns\BelongsToTenant;

    public const SOURCES = [
        'facebook'     => 'Facebook / Page',
        'messenger'    => 'Messenger',
        'whatsapp'     => 'WhatsApp',
        'phone_call'   => 'Phone call',
        'landing_page' => 'Landing page',
        'website'      => 'Website',
        'bulk_sms'     => 'Bulk SMS',
        'import'       => 'Imported list',
        'other'        => 'Other',
    ];

    public const STATUSES = [
        'pending'   => 'Pending',
        'confirmed' => 'Confirmed',
        'canceled'  => 'Canceled',
    ];

    public const CANCEL_REASONS = ['Not interested', 'Wrong number', 'Already ordered', 'Price too high', 'Duplicate', 'Fake'];

    protected $fillable = [
        'tenant_id', 'customer_id', 'status', 'source', 'tag', 'is_star', 'assigned_to', 'created_by',
        'interest', 'note', 'cancel_reason', 'order_id', 'read_at', 'confirmed_at',
    ];

    protected $casts = [
        'is_star'      => 'boolean',
        'read_at'      => 'datetime',
        'confirmed_at' => 'datetime',
    ];

    public function customer() { return $this->belongsTo(Customer::class); }
    public function order()    { return $this->belongsTo(Order::class); }
    public function assignee() { return $this->belongsTo(User::class, 'assigned_to'); }
    public function creator()  { return $this->belongsTo(User::class, 'created_by'); }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        return $user->can('view_all_leads') ? $query : $query->where('assigned_to', $user->id);
    }

    /** Mark this lead as turned into an order. */
    public function confirmWith(?Order $order): void
    {
        $this->forceFill([
            'status'       => 'confirmed',
            'order_id'     => $order?->id ?? $this->order_id,
            'confirmed_at' => now(),
            'read_at'      => $this->read_at ?? now(),
        ])->save();
    }
}
