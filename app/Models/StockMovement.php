<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsActivity;

class StockMovement extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'product_id',
        'type',
        'quantity',
        'unit_price',
        'reason',
        'user_id'
    ];

    protected $casts = [
        'unit_price' => 'decimal:2'
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}