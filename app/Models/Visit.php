<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Visit
 * Fields (rencana): sales_id, outlet_id, checkin_lat, checkin_lng, photo_path, notes, visited_at
 */
class Visit extends Model
{
    use HasFactory;

    protected $fillable = [
        // TODO: sesuaikan dengan migration
    ];
}
