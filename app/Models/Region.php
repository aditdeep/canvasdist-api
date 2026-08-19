<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class Region extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'code', 'parent_region_id'];

    public function parent() { return $this->belongsTo(Region::class, 'parent_region_id'); }
    public function children() { return $this->hasMany(Region::class, 'parent_region_id'); }

}
