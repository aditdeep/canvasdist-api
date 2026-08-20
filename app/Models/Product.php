<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class Product extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'sku', 'category', 'category_id', 'unit', 'base_price', 'photo_path', 'description', 'is_active'];
    protected $casts = ['base_price' => 'decimal:2', 'is_active' => 'boolean'];

    public function prices() { return $this->hasMany(ProductPrice::class); }
    public function categoryModel() { return $this->belongsTo(Category::class, 'category_id'); }

    public function priceForLevel(string $level): float
    {
        return optional($this->prices()->where('level', $level)->first())->price ?? $this->base_price;
    }

}
