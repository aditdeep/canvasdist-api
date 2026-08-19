<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class Warehouse extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'agent_id', 'address'];

    public function agent() { return $this->belongsTo(User::class, 'agent_id'); }
    public function stocks() { return $this->hasMany(Stock::class); }

}
