<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens;

    protected $fillable = ['name', 'email', 'phone', 'password', 'role', 'parent_id', 'region_code', 'address', 'avatar_path', 'push_token', 'outlet_id', 'shipping_fee', 'courier_fee_flat', 'courier_fee_percent', 'is_active'];
    protected $hidden = ['password', 'remember_token'];
    protected $casts = ['is_active' => 'boolean', 'email_verified_at' => 'datetime'];

    public function parent() { return $this->belongsTo(User::class, 'parent_id'); }
    public function children() { return $this->hasMany(User::class, 'parent_id'); }
    public function outlets() { return $this->hasMany(Outlet::class, 'agent_id'); }
    public function warehouses() { return $this->hasMany(Warehouse::class, 'agent_id'); }
    public function visits() { return $this->hasMany(Visit::class, 'sales_id'); }
    public function deliveryOrders() { return $this->hasMany(DeliveryOrder::class, 'courier_id'); }
    public function wallet() { return $this->hasOne(Wallet::class); }
    public function memberCard() { return $this->hasOne(MemberCard::class); }
    public function commissions() { return $this->hasMany(Commission::class); }
    public function outlet() { return $this->belongsTo(Outlet::class); }

    public function isRole(string ...$roles): bool { return in_array($this->role, $roles); }

}
