<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CafeOrderLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'cafe_order_id',
        'user_id',
        'action',
        'message'
    ];

    public function cafeOrder()
    {
        return $this->belongsTo(CafeOrder::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
