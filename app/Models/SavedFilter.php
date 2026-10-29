<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/** A named set of list filters (stored as a query string) that a user can reopen or share. */
class SavedFilter extends Model
{
    use \App\Models\Concerns\BelongsToTenant;

    public const PAGES = [
        'orders'    => 'admin.orders.index',
        'customers' => 'admin.customers.index',
        'leads'     => 'admin.leads.index',
        'followups' => 'admin.followups.index',
    ];

    protected $fillable = ['tenant_id', 'user_id', 'page', 'name', 'query', 'is_shared'];

    protected $casts = ['is_shared' => 'boolean'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /** Own filters plus the ones colleagues shared. */
    public function scopeAvailableTo(Builder $query, User $user, string $page): Builder
    {
        return $query->where('page', $page)
            ->where(fn ($q) => $q->where('user_id', $user->id)->orWhere('is_shared', true))
            ->orderBy('name');
    }

    public function url(): string
    {
        return route(self::PAGES[$this->page]) . ($this->query ? '?' . $this->query : '');
    }
}
