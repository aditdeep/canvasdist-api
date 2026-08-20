<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'image_path', 'sort_order', 'is_active'];
    protected $casts = ['is_active' => 'boolean'];

    public function products() { return $this->hasMany(Product::class); }
}
