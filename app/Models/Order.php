<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class Order extends Model
{
    use HasFactory;

    protected $fillable = ['order_no', 'visit_id', 'outlet_id', 'agent_id', 'status', 'payment_method', 'payment_status', 'fulfillment_type', 'is_storefront_order', 'subtotal', 'discount_total', 'total'];
    protected $casts = ['subtotal' => 'decimal:2', 'discount_total' => 'decimal:2', 'total' => 'decimal:2'];

    public function visit() { return $this->belongsTo(Visit::class); }
    public function outlet() { return $this->belongsTo(Outlet::class); }
    public function agent() { return $this->belongsTo(User::class, 'agent_id'); }
    public function items() { return $this->hasMany(OrderItem::class); }
    public function deliveryOrder() { return $this->hasOne(DeliveryOrder::class); }
    public function invoice() { return $this->hasOne(Invoice::class); }
    public function commissions() { return $this->hasMany(Commission::class, 'source_order_id'); }
    public function returns() { return $this->hasMany(ReturnItem::class); }
    public function paymentTransactions() { return $this->hasMany(PaymentTransaction::class); }

}
