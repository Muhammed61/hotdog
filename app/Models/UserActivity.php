<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserActivity extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'system_type',
        'action',
        'model',
        'model_id',
        'description',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
        'device_type',
        'browser',
        'platform'
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getFormattedDescriptionAttribute()
    {
        return $this->description;
    }

    public function getSystemTypeNameAttribute()
    {
        return $this->system_type === 'stock' ? 'Stok Takip Sistemi' : 'Kafe Sistemi';
    }

    public function getActionNameAttribute()
    {
        $actions = [
            'create' => 'Oluşturma',
            'update' => 'Güncelleme',
            'delete' => 'Silme',
            'view' => 'Görüntüleme',
            'login' => 'Giriş',
            'logout' => 'Çıkış',
            'export' => 'Dışa Aktarma',
            'import' => 'İçe Aktarma'
        ];

        return $actions[$this->action] ?? $this->action;
    }

    public function scopeStockSystem($query)
    {
        return $query->where('system_type', 'stock');
    }

    public function scopeCafeSystem($query)
    {
        return $query->where('system_type', 'cafe');
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }
}