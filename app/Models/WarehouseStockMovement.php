<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WarehouseStockMovement extends Model
{
    use HasFactory;

    protected $fillable = [
        'warehouse_product_id',
        'type',
        'quantity',
        'reason',
        'user_id'
    ];

    protected $casts = [
        'quantity' => 'integer'
    ];

    // Ürün ilişkisi
    public function warehouseProduct()
    {
        return $this->belongsTo(WarehouseProduct::class);
    }

    // Kullanıcı ilişkisi
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}