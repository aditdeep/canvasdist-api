<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class Promo extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'type', 'value', 'min_qty', 'start_date', 'end_date', 'target_level', 'is_active'];
    protected $casts = ['value' => 'decimal:2', 'start_date' => 'date', 'end_date' => 'date', 'is_active' => 'boolean'];

    public function isValidNow(): bool
    {
        $today = now()->toDateString();
        return $this->is_active && $this->start_date <= $today && $this->end_date >= $today;
    }

}
