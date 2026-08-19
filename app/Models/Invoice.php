<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class Invoice extends Model
{
    use HasFactory;

    protected $fillable = ['invoice_no', 'order_id', 'amount', 'due_date', 'status'];
    protected $casts = ['amount' => 'decimal:2', 'due_date' => 'date'];

    public function order() { return $this->belongsTo(Order::class); }
    public function receivable() { return $this->hasOne(Receivable::class); }

}
