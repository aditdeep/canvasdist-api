<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Promo
 * Fields (rencana): name, type, value, start_date, end_date, target_level
 */
class Promo extends Model
{
    use HasFactory;

    protected $fillable = [
        // TODO: sesuaikan dengan migration
    ];
}
