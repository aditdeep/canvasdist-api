<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * ProductPrice
 * Fields (rencana): product_id, level (wilayah/agen/reseller), price
 */
class ProductPrice extends Model
{
    use HasFactory;

    protected $fillable = [
        // TODO: sesuaikan dengan migration
    ];
}
