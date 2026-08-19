<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class PaymentTransaction extends Model
{
    use HasFactory;

    protected $fillable = ['reference', 'gateway', 'order_id', 'wallet_id', 'amount', 'status', 'payload'];
    protected $casts = ['amount' => 'decimal:2', 'payload' => 'array'];

    public function order() { return $this->belongsTo(Order::class); }
    public function wallet() { return $this->belongsTo(Wallet::class); }

}
