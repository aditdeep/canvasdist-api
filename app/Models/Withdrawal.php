<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Withdrawal extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'amount', 'bank_name', 'account_number', 'account_holder_name',
        'status', 'rejection_reason', 'processed_by', 'processed_at',
    ];

    protected $casts = ['amount' => 'decimal:2', 'processed_at' => 'datetime'];

    public function user() { return $this->belongsTo(User::class); }
    public function processedBy() { return $this->belongsTo(User::class, 'processed_by'); }
}
