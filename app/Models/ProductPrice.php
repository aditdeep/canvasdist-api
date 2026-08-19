<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class ProductPrice extends Model
{
    use HasFactory;

    protected $fillable = ['product_id', 'level', 'price'];
    protected $casts = ['price' => 'decimal:2'];

    public function product() { return $this->belongsTo(Product::class); }

}
