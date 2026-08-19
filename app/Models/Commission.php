<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class Commission extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'source_order_id', 'level', 'percentage', 'amount', 'status', 'paid_at'];
    protected $casts = ['percentage' => 'decimal:2', 'amount' => 'decimal:2', 'paid_at' => 'datetime'];

    public function user() { return $this->belongsTo(User::class); }
    public function sourceOrder() { return $this->belongsTo(Order::class, 'source_order_id'); }

}
