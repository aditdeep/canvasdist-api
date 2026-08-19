<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class Visit extends Model
{
    use HasFactory;

    protected $fillable = ['sales_id', 'outlet_id', 'checkin_lat', 'checkin_lng', 'photo_path', 'notes', 'visited_at'];
    protected $casts = ['visited_at' => 'datetime'];

    public function sales() { return $this->belongsTo(User::class, 'sales_id'); }
    public function outlet() { return $this->belongsTo(Outlet::class); }
    public function order() { return $this->hasOne(Order::class); }

}
