<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class Outlet extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'owner_name', 'phone', 'address', 'latitude', 'longitude', 'agent_id'];

    public function agent() { return $this->belongsTo(User::class, 'agent_id'); }
    public function visits() { return $this->hasMany(Visit::class); }
    public function orders() { return $this->hasMany(Order::class); }

}
