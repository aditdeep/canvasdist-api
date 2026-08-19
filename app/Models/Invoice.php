<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Invoice
 * Fields (rencana): order_id, amount, due_date, status
 */
class Invoice extends Model
{
    use HasFactory;

    protected $fillable = [
        // TODO: sesuaikan dengan migration
    ];
}
