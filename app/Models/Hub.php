<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Hub extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'type', 'warehouse_id', 'agent_id', 'address', 'latitude', 'longitude'];

    public function warehouse() { return $this->belongsTo(Warehouse::class); }
    public function agent() { return $this->belongsTo(User::class, 'agent_id'); }
    public function legsFrom() { return $this->hasMany(DeliveryLeg::class, 'from_hub_id'); }
    public function legsTo() { return $this->hasMany(DeliveryLeg::class, 'to_hub_id'); }
}
