<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CafeOrderItem extends Model
{
    use HasFactory;

    const STATUS_PENDING = 'pending';
    const STATUS_PREPARING = 'preparing';
    const STATUS_READY = 'ready';
    const STATUS_SERVED = 'served';
    const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'cafe_order_id',
        'product_id',
        'quantity',
        'unit_price',
        'total_price',
        'notes',
        'status'
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2'
    ];

    public function cafeOrder()
    {
        return $this->belongsTo(CafeOrder::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function getStatusTextAttribute()
    {
        return match($this->status) {
            self::STATUS_PENDING => 'Bekliyor',
            self::STATUS_PREPARING => 'Hazırlanıyor',
            self::STATUS_READY => 'Hazır',
            self::STATUS_SERVED => 'Servis Edildi',
            self::STATUS_CANCELLED => 'İptal',
            default => 'Bilinmiyor'
        };
    }

    public function getStatusColorAttribute()
    {
        return match($this->status) {
            self::STATUS_PENDING => 'warning',
            self::STATUS_PREPARING => 'info',
            self::STATUS_READY => 'success',
            self::STATUS_SERVED => 'primary',
            self::STATUS_CANCELLED => 'danger',
            default => 'secondary'
        };
    }
}