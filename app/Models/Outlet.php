<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Outlet
 * Fields (rencana): name, owner_name, phone, address, latitude, longitude, agent_id
 */
class Outlet extends Model
{
    use HasFactory;

    protected $fillable = [
        // TODO: sesuaikan dengan migration
    ];
}
