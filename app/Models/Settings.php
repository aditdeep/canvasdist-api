<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Settings extends Model
{
    use HasFactory;

    protected $fillable = [
        'app_name', 'logo_path', 'owner_name', 'owner_email', 'owner_phone',
        'platform_fee_percent', 'platform_owner_user_id',
    ];

    protected $casts = ['platform_fee_percent' => 'decimal:2'];

    public function platformOwner() { return $this->belongsTo(User::class, 'platform_owner_user_id'); }

    /**
     * Selalu ada 1 baris config (id=1). Kalau entah kenapa belum ada
     * (mis. migration lama / fresh install), buat otomatis dengan default.
     */
    public static function current(): self
    {
        return static::firstOrCreate(['id' => 1], ['app_name' => 'Super OEY']);
    }
}
