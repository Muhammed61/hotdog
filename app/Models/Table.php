<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsActivity;

class Table extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'name',
        'capacity',
        'status',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean'
    ];

    // Masa durumları
    const STATUS_AVAILABLE = 'available';
    const STATUS_OCCUPIED = 'occupied';
    const STATUS_RESERVED = 'reserved';
    const STATUS_CLEANING = 'cleaning';
    const STATUS_CLOSED = 'closed';

    public function getStatusTextAttribute()
    {
        return match($this->status) {
            self::STATUS_AVAILABLE => 'Musait',
            self::STATUS_OCCUPIED => 'Dolu',
            self::STATUS_RESERVED => 'Rezerve',
            self::STATUS_CLEANING => 'Temizleniyor',
            self::STATUS_CLOSED => 'Kapalı',
            default => 'Bilinmiyor'
        };
    }

    public function getStatusColorAttribute()
    {
        return match($this->status) {
            self::STATUS_AVAILABLE => 'success',
            self::STATUS_OCCUPIED => 'danger',
            self::STATUS_RESERVED => 'warning',
            self::STATUS_CLEANING => 'info',
            self::STATUS_CLOSED => 'dark',
            default => 'secondary'
        };
    }

    public function cafeOrders()
    {
        return $this->hasMany(CafeOrder::class);
    }
}