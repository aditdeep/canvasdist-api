<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeliveryLeg extends Model
{
    use HasFactory;

    protected $fillable = [
        'delivery_order_id', 'sequence', 'from_hub_id', 'to_hub_id',
        'courier_id', 'status', 'departed_at', 'arrived_at', 'notes',
    ];

    protected $casts = ['departed_at' => 'datetime', 'arrived_at' => 'datetime'];

    public function deliveryOrder() { return $this->belongsTo(DeliveryOrder::class); }
    public function fromHub() { return $this->belongsTo(Hub::class, 'from_hub_id'); }
    public function toHub() { return $this->belongsTo(Hub::class, 'to_hub_id'); }
    public function courier() { return $this->belongsTo(User::class, 'courier_id'); }

    /** Etape ini last-mile langsung ke outlet (bukan transit ke hub lain). */
    public function isFinalMile(): bool
    {
        return is_null($this->to_hub_id);
    }
}
