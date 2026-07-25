<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CafeOrderPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'cafe_order_id',
        'amount',
        'payment_method',
        'description',
        'selected_items'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'selected_items' => 'array'
    ];

    public function cafeOrder()
    {
        return $this->belongsTo(CafeOrder::class);
    }

    public function getPaymentMethodTextAttribute()
    {
        switch ($this->payment_method) {
            case 'cash':
                return 'Nakit';
            case 'card':
                return 'Kredi Kartı';
            default:
                return 'Bilinmiyor';
        }
    }
}
