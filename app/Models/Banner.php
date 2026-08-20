<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Banner extends Model
{
    use HasFactory;

    protected $fillable = ['title', 'subtitle', 'image_path', 'link_url', 'sort_order', 'is_active'];
    protected $casts = ['is_active' => 'boolean'];
}
