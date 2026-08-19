<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class Buyback extends Model
{
    use HasFactory;

    protected $table = 'buyback';
    protected $fillable = ['visit_id', 'outlet_id', 'item_type', 'qty', 'unit_price', 'cashback_amount', 'photo_path', 'status'];
    protected $casts = ['unit_price' => 'decimal:2', 'cashback_amount' => 'decimal:2'];

    public function visit() { return $this->belongsTo(Visit::class); }
    public function outlet() { return $this->belongsTo(Outlet::class); }

}
