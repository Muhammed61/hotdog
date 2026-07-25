<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    // Rol sabitleri
    const ROLE_ADMIN = 'admin';
    const ROLE_MANAGER = 'manager';
    const ROLE_WAITER = 'waiter';
    const ROLE_CASHIER = 'cashier';
    const ROLE_WAREHOUSE_MANAGER = 'warehouse_manager';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'is_active'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_active' => 'boolean'
    ];

    // Rol kontrol metodları
    public function isAdmin()
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isManager()
    {
        return $this->role === self::ROLE_MANAGER;
    }

    public function isWaiter()
    {
        return $this->role === self::ROLE_WAITER;
    }

    public function isCashier()
    {
        return $this->role === self::ROLE_CASHIER;
    }

    public function isWarehouseManager()
    {
        return $this->role === self::ROLE_WAREHOUSE_MANAGER;
    }

    public function hasRole($role)
    {
        return $this->role === $role;
    }

    public function hasAnyRole($roles)
    {
        return in_array($this->role, $roles);
    }

    // Rol adını Türkçe olarak döndür
    public function getRoleNameAttribute()
    {
        $roles = [
            self::ROLE_ADMIN => 'Yönetici',
            self::ROLE_MANAGER => 'Müdür',
            self::ROLE_WAITER => 'Garson',
            self::ROLE_CASHIER => 'Kasiyer',
            self::ROLE_WAREHOUSE_MANAGER => 'Depo Yöneticisi'
        ];

        return $roles[$this->role] ?? 'Bilinmeyen';
    }

    // Mevcut roller listesi
    public static function getRoles()
    {
        return [
            self::ROLE_ADMIN => 'Yönetici',
            self::ROLE_MANAGER => 'Müdür',
            self::ROLE_WAITER => 'Garson',
            self::ROLE_CASHIER => 'Kasiyer',
            self::ROLE_WAREHOUSE_MANAGER => 'Depo Yöneticisi'
        ];
    }

    // İlişkiler
    public function stockMovements()
    {
        return $this->hasMany(StockMovement::class);
    }

    public function sales()
    {
        return $this->hasMany(Sale::class);
    }
}