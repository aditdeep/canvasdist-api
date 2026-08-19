<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * DeliveryTracking
 * Fields (rencana): delivery_order_id, lat, lng, status, recorded_at
 */
class DeliveryTracking extends Model
{
    use HasFactory;

    protected $fillable = [
        // TODO: sesuaikan dengan migration
    ];
}
