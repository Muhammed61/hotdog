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
        switch ($this->status) {
            case self::STATUS_AVAILABLE:
                return 'Müsait';
            case self::STATUS_OCCUPIED:
                return 'Dolu';
            case self::STATUS_RESERVED:
                return 'Rezerve';
            case self::STATUS_CLEANING:
                return 'Temizleniyor';
            case self::STATUS_CLOSED:
                return 'Kapalı';
            default:
                return 'Bilinmiyor';
        }
    }

    public function getStatusColorAttribute()
    {
        switch ($this->status) {
            case self::STATUS_AVAILABLE:
                return 'success';
            case self::STATUS_OCCUPIED:
                return 'danger';
            case self::STATUS_RESERVED:
                return 'warning';
            case self::STATUS_CLEANING:
                return 'info';
            case self::STATUS_CLOSED:
                return 'dark';
            default:
                return 'secondary';
        }
    }


    public function cafeOrders()
    {
        return $this->hasMany(CafeOrder::class);
    }
}