<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Warehouse
 * Fields (rencana): name, agent_id, address
 */
class Warehouse extends Model
{
    use HasFactory;

    protected $fillable = [
        // TODO: sesuaikan dengan migration
    ];
}
