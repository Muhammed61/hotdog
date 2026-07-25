<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsActivity;

class Product extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'name',
        'description',
        'category_id',
        'purchase_price',
        'sale_price',
        'stock_quantity',
        'min_stock_level',
        'unit',
        'barcode',
        'is_active'
    ];

    protected $casts = [
        'purchase_price' => 'decimal:2',
        'sale_price' => 'decimal:2',
        'is_active' => 'boolean'
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function stockMovements()
    {
        return $this->hasMany(StockMovement::class);
    }

    public function saleItems()
    {
        return $this->hasMany(SaleItem::class);
    }

    public function isLowStock()
    {
        // Önce ürünün kendi min_stock_level'ını kontrol et
        if ($this->min_stock_level !== null) {
            return $this->stock_quantity <= $this->min_stock_level;
        }

        // Ürünün kendi seviyesi yoksa genel ayarı kullan
        $lowStockAlert = \App\Models\Setting::where('key', 'low_stock_alert')->first();
        $alertLevel = $lowStockAlert ? (int)$lowStockAlert->value : 10;

        return $this->stock_quantity <= $alertLevel;
    }

    // Düşük stokta olan ürünleri getir
    public static function getLowStockProducts()
    {
        $lowStockAlert = \App\Models\Setting::where('key', 'low_stock_alert')->first();
        $alertLevel = $lowStockAlert ? (int)$lowStockAlert->value : 10;

        return self::where(function($query) use ($alertLevel) {
            $query->whereNotNull('min_stock_level')
                  ->whereColumn('stock_quantity', '<=', 'min_stock_level')
                  ->orWhere(function($subQuery) use ($alertLevel) {
                      $subQuery->whereNull('min_stock_level')
                               ->where('stock_quantity', '<=', $alertLevel);
                  });
        })->where('is_active', true)->get();
    }
}
