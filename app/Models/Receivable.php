<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class Receivable extends Model
{
    use HasFactory;

    protected $fillable = ['invoice_id', 'outlet_id', 'amount', 'paid_amount', 'status'];
    protected $casts = ['amount' => 'decimal:2', 'paid_amount' => 'decimal:2'];

    public function invoice() { return $this->belongsTo(Invoice::class); }
    public function outlet() { return $this->belongsTo(Outlet::class); }

}
