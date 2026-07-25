<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsActivity;

class CashTransaction extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'cash_type',
        'transaction_type',
        'amount',
        'description',
        'notes',
        'reference_type',
        'reference_id',
        'user_id'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    // İlişkiler
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Kasa türü metinleri
    public function getCashTypeTextAttribute()
    {
        return [
            'stock' => 'Stok Takip Kasası',
            'cafe' => 'Kafe Sistemi Kasası'
        ][$this->cash_type] ?? $this->cash_type;
    }

    // İşlem türü metinleri
    public function getTransactionTypeTextAttribute()
    {
        return [
            'income' => 'Gelir',
            'expense' => 'Gider',
            'withdrawal' => 'Para Çekme'
        ][$this->transaction_type] ?? $this->transaction_type;
    }

    // İşlem türü renkleri
    public function getTransactionTypeColorAttribute()
    {
        return [
            'income' => 'success',
            'expense' => 'danger',
            'withdrawal' => 'warning'
        ][$this->transaction_type] ?? 'secondary';
    }

    // İşlem türü ikonları
    public function getTransactionTypeIconAttribute()
    {
        return [
            'income' => 'fas fa-plus-circle',
            'expense' => 'fas fa-minus-circle',
            'withdrawal' => 'fas fa-hand-holding-usd'
        ][$this->transaction_type] ?? 'fas fa-circle';
    }

    // Kasa bakiyesi hesaplama
    public static function getCashBalance($cashType)
    {
        $income = self::where('cash_type', $cashType)
            ->where('transaction_type', 'income')
            ->sum('amount');

        $expenses = self::where('cash_type', $cashType)
            ->whereIn('transaction_type', ['expense', 'withdrawal'])
            ->sum('amount');

        return $income - $expenses;
    }

    // Son işlemler
    public static function getRecentTransactions($cashType, $limit = 10)
    {
        return self::with('user')
            ->where('cash_type', $cashType)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }
}