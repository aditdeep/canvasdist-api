<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class DeliveryTracking extends Model
{
    use HasFactory;

    protected $fillable = ['delivery_order_id', 'lat', 'lng', 'status', 'recorded_at'];
    protected $casts = ['recorded_at' => 'datetime'];

    public function deliveryOrder() { return $this->belongsTo(DeliveryOrder::class); }

}
