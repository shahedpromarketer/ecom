<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class OrderReturn extends Model {
    protected $table = 'order_returns';
    protected $fillable = [
        'order_id', 'reason', 'status', 'refund_amount', 'refund_method',
        'admin_note', 'requested_at', 'resolved_at', 'resolved_by',
    ];
    protected $casts = [
        'refund_amount' => 'decimal:2',
        'requested_at'  => 'datetime',
        'resolved_at'   => 'datetime',
    ];
    public function order() { return $this->belongsTo(Order::class); }
    public function resolvedBy() { return $this->belongsTo(User::class, 'resolved_by'); }
}
