<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsActivity;
use Carbon\Carbon;

class CafeOrder extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'table_id',
        'user_id',
        'order_number',
        'total_amount',
        'extra_amount',
        'discount_percentage',
        'discount_amount',
        'final_amount',
        'status',
        'payment_method',
        'is_paid',
        'paid_at',
        'notes'
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'extra_amount' => 'decimal:2',
        'discount_percentage' => 'integer',
        'discount_amount' => 'decimal:2',
        'final_amount' => 'decimal:2',
        'is_paid' => 'boolean',
        'paid_at' => 'datetime'
    ];

    // Sipariş durumları
    const STATUS_PENDING = 'pending';
    const STATUS_PREPARING = 'preparing';
    const STATUS_READY = 'ready';
    const STATUS_SERVED = 'served';
    const STATUS_CANCELLED = 'cancelled';

    // Ödeme yöntemleri
    const PAYMENT_CASH = 'cash';
    const PAYMENT_CARD = 'card';
    const PAYMENT_SPLIT = 'split';

    public function table()
    {
        return $this->belongsTo(Table::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function cafeOrderItems()
    {
        return $this->hasMany(CafeOrderItem::class);
    }

    public function cafeOrderNotes()
    {
        return $this->hasMany(CafeOrderNote::class)->orderBy('created_at', 'asc');
    }

    public function cafeOrderExtras()
    {
        return $this->hasMany(CafeOrderExtra::class);
    }

    public function cafeOrderLogs()
    {
        return $this->hasMany(CafeOrderLog::class)->orderBy('created_at', 'desc');
    }

    public function latestLog()
    {
        return $this->hasOne(CafeOrderLog::class)->latestOfMany('created_at');
    }

    // Masa oturma süresini hesapla
    public function getTableDurationAttribute()
    {
        $startTime = $this->created_at;
        
        // Eğer sipariş ödenmiş ve paid_at varsa onu kullan
        if ($this->is_paid && $this->paid_at) {
            $endTime = $this->paid_at;
        }
        // Eğer sipariş iptal edilmişse, iptal zamanını kullan
        elseif ($this->status === self::STATUS_CANCELLED) {
            $endTime = $this->updated_at;
        }
        // Aktif sipariş ise (served dahil) şu anki zamanı kullan
        else {
            $endTime = now();
        }
    
        return $startTime->diff($endTime);
    }

    // Süreyi okunabilir formatta döndür
    public function getFormattedDurationAttribute()
    {
        $duration = $this->table_duration;
        
        $hours = $duration->h;
        $minutes = $duration->i;
        
        if ($hours > 0) {
            return "{$hours} saat {$minutes} dakika";
        } else {
            return "{$minutes} dakika";
        }
    }

    // Süreyi dakika cinsinden döndür
    public function getDurationInMinutesAttribute()
    {
        $duration = $this->table_duration;
        return ($duration->h * 60) + $duration->i;
    }

    // Masa için toplam oturma süresini hesapla (aynı masadaki tüm siparişler)
    public function getTotalTableDurationAttribute()
    {
        // Aynı masadaki ilk sipariş zamanını bul
        $firstOrder = self::where('table_id', $this->table_id)
            ->where('created_at', '<=', $this->created_at)
            ->orderBy('created_at', 'asc')
            ->first();

        // Aynı masadaki son ödeme zamanını bul
        $lastPayment = self::where('table_id', $this->table_id)
            ->where('is_paid', true)
            ->where('created_at', '<=', $this->created_at)
            ->orderBy('paid_at', 'desc')
            ->first();

        $startTime = $firstOrder ? $firstOrder->created_at : $this->created_at;
        $endTime = $lastPayment && $lastPayment->paid_at ? $lastPayment->paid_at : now();

        return $startTime->diff($endTime);
    }

    public function getStatusTextAttribute()
    {
        return match($this->status) {
            self::STATUS_PENDING => 'Bekliyor',
            self::STATUS_PREPARING => 'Hazırlanıyor',
            self::STATUS_READY => 'Hazır',
            self::STATUS_SERVED => 'Servis Edildi',
            self::STATUS_CANCELLED => 'İptal',
            default => 'Bilinmiyor'
        };
    }

    public function getStatusColorAttribute()
    {
        return match($this->status) {
            self::STATUS_PENDING => 'warning',
            self::STATUS_PREPARING => 'info',
            self::STATUS_READY => 'success',
            self::STATUS_SERVED => 'primary',
            self::STATUS_CANCELLED => 'danger',
            default => 'secondary'
        };
    }

    public function cafeOrderPayments()
    {
        return $this->hasMany(CafeOrderPayment::class);
    }

    // Split payment detaylarını almak için helper method
    public function getSplitPaymentDetailsAttribute()
    {
        if ($this->payment_method !== self::PAYMENT_SPLIT) {
            return null;
        }

        $payments = $this->cafeOrderPayments;
        
        return [
            'cash_amount' => $payments->where('payment_method', 'cash')->sum('amount'),
            'card_amount' => $payments->where('payment_method', 'card')->sum('amount'),
            'cash_count' => $payments->where('payment_method', 'cash')->count(),
            'card_count' => $payments->where('payment_method', 'card')->count(),
            'total_amount' => $payments->sum('amount'),
            'total_count' => $payments->count()
        ];
    }

    public function getPaymentMethodTextAttribute()
    {
        return match($this->payment_method) {
            self::PAYMENT_CASH => 'Nakit',
            self::PAYMENT_CARD => 'Kredi Kartı',
            self::PAYMENT_SPLIT => 'Bölünmüş Ödeme',
            default => 'Belirtilmemiş'
        };
    }

    public function getIsSplitPaymentAttribute()
    {
        return $this->payment_method === self::PAYMENT_SPLIT;
    }

    // Kısmi ödeme bilgilerini almak için yeni metodlar
    public function getTotalPaidAmountAttribute()
    {
        return $this->cafeOrderPayments()->sum('amount');
    }

    public function getRemainingAmountAttribute()
    {
        return $this->total_amount - $this->total_paid_amount;
    }

    public function getIsPartiallyPaidAttribute()
    {
        $totalPaid = $this->total_paid_amount;
        return $totalPaid > 0 && $totalPaid < $this->total_amount && !$this->is_paid;
    }

    public function getPaymentStatusTextAttribute()
    {
        if ($this->is_paid) {
            return 'Ödendi';
        } elseif ($this->is_partially_paid) {
            return 'Kısmi Ödendi';
        } else {
            return 'Ödenmedi';
        }
    }

    public function getPaymentStatusColorAttribute()
    {
        if ($this->is_paid) {
            return 'success';
        } elseif ($this->is_partially_paid) {
            return 'warning';
        } else {
            return 'danger';
        }
    }

    // Hesap kapatma durumunu kontrol et
    public function getIsClosedWithPartialPaymentAttribute()
    {
        $totalPaid = $this->total_paid_amount;
        return $this->is_paid && $totalPaid > 0 && $totalPaid < $this->total_amount;
    }

    /**
     * Sipariş toplam tutarını hesapla ve güncelle
     */
    public function calculateTotal()
    {
        // Sipariş ürünlerinin toplam tutarını hesapla
        $itemsTotal = $this->cafeOrderItems()->sum(\DB::raw('quantity * unit_price'));
        
        // Ekstra ücretlerin toplam tutarını hesapla
        $extrasTotal = $this->cafeOrderExtras()->sum('amount');
        
        // Toplam tutarı hesapla
        $totalAmount = $itemsTotal + $extrasTotal;
        
        // İndirim varsa uygula
        $discountAmount = 0;
        if ($this->discount_percentage > 0) {
            $discountAmount = ($totalAmount * $this->discount_percentage) / 100;
        } elseif ($this->discount_amount > 0) {
            $discountAmount = $this->discount_amount;
        }
        
        // Final tutarı hesapla
        $finalAmount = $totalAmount - $discountAmount;
        
        // Veritabanını güncelle
        $this->update([
            'total_amount' => $totalAmount,
            'discount_amount' => $discountAmount,
            'final_amount' => $finalAmount
        ]);
        
        return $finalAmount;
    }
}
