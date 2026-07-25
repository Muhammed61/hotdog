<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WarehouseProduct extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'initial_stock',
        'current_stock',
        'min_stock_level',
        'is_active'
    ];

    protected $casts = [
        'initial_stock' => 'integer',
        'current_stock' => 'integer',
        'min_stock_level' => 'integer',
        'is_active' => 'boolean'
    ];

    // Stok hareketleri ilişkisi
    public function stockMovements()
    {
        return $this->hasMany(WarehouseStockMovement::class, 'warehouse_product_id');
    }

    // Düşük stok kontrolü
    public function isLowStock()
    {
        return $this->current_stock <= $this->min_stock_level;
    }

    // Stok durumu
    public function getStockStatusAttribute()
    {
        if ($this->current_stock <= 0) {
            return 'out_of_stock';
        } elseif ($this->isLowStock()) {
            return 'low_stock';
        }
        return 'in_stock';
    }
}