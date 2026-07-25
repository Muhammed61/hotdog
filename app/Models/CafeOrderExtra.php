<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CafeOrderExtra extends Model
{
    use HasFactory;

    protected $fillable = [
        'cafe_order_id',
        'amount',
        'description'
    ];

    protected $casts = [
        'amount' => 'decimal:2'
    ];

    public function cafeOrder()
    {
        return $this->belongsTo(CafeOrder::class);
    }
}