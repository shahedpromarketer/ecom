<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Announcement extends Model {
    protected $fillable = ['title', 'message', 'target', 'sent_by'];

    public function sender() {
        return $this->belongsTo(User::class, 'sent_by');
    }

    public function getTargetLabelAttribute(): string {
        return match($this->target) {
            'all'             => 'All Tenants',
            'active'          => 'Active Tenants',
            'plan_basic'      => 'Basic Plan',
            'plan_pro'        => 'Pro Plan',
            'plan_enterprise' => 'Enterprise Plan',
            default           => ucfirst($this->target),
        };
    }
}
