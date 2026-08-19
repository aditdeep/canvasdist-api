<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class DeliveryOrder extends Model
{
    use HasFactory;

    protected $fillable = ['do_number', 'order_id', 'courier_id', 'status', 'pod_photo_path', 'pod_signature_path', 'shipped_at', 'delivered_at'];
    protected $casts = ['shipped_at' => 'datetime', 'delivered_at' => 'datetime'];

    public function order() { return $this->belongsTo(Order::class); }
    public function courier() { return $this->belongsTo(User::class, 'courier_id'); }
    public function trackings() { return $this->hasMany(DeliveryTracking::class); }
    public function legs() { return $this->hasMany(DeliveryLeg::class)->orderBy('sequence'); }

    /** Etape yang sedang berjalan/berikutnya yang belum tiba. */
    public function activeLeg()
    {
        return $this->legs()->where('status', '!=', 'arrived')->orderBy('sequence')->first();
    }

}
