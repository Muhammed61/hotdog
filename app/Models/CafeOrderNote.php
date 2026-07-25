<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CafeOrderNote extends Model
{
    use HasFactory;

    protected $fillable = [
        'cafe_order_id',
        'user_id',
        'note',
        'note_type'
    ];

    protected $casts = [
        'created_at' => 'datetime'
    ];

    // Not tipleri
    const TYPE_INITIAL = 'initial';
    const TYPE_ADDITIONAL = 'additional';
    const TYPE_STATUS_CHANGE = 'status_change';

    public function cafeOrder()
    {
        return $this->belongsTo(CafeOrder::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getTypeTextAttribute()
    {
        return match($this->note_type) {
            self::TYPE_INITIAL => 'İlk Sipariş',
            self::TYPE_ADDITIONAL => 'Ek Sipariş',
            self::TYPE_STATUS_CHANGE => 'Durum Değişikliği',
            default => 'Not'
        };
    }

    public function getTypeColorAttribute()
    {
        return match($this->note_type) {
            self::TYPE_INITIAL => 'primary',
            self::TYPE_ADDITIONAL => 'success',
            self::TYPE_STATUS_CHANGE => 'warning',
            default => 'info'
        };
    }
}