<?php

namespace App\Http\Controllers;

use App\Models\CafeOrder;
use App\Models\CafeOrderItem;
use App\Models\CafeOrderNote;
use App\Models\CafeOrderExtra;
use App\Models\CafeOrderLog;
use App\Models\CafeOrderPayment;
use App\Models\Table;
use App\Models\Product;
use App\Models\Category;
use App\Models\Setting;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CafeController extends Controller
{
    public function index()
    {
        $tables = Table::where('is_active', true)
            ->with(['cafeOrders' => function($query) {
                $query->where('is_paid', false)
                      ->whereNotIn('status', [CafeOrder::STATUS_CANCELLED])
                      ->orderBy('updated_at', 'desc')
                      ->with(['latestLog']);
            }])->get();
        
        return view('cafe.index', compact('tables'));
    }

    public function selectTable(Table $table)
    {
        // Masa aktif değilse reddet
        if (!$table->is_active) {
            return redirect()->route('cafe.index')->with('error', 'Bu masa aktif değil!');
        }

        // Sadece temizleniyor durumundaki masalara sipariş alınamaz
        // Dolu masalara da ek sipariş alınabilir
        if ($table->status === Table::STATUS_CLEANING) {
            return redirect()->route('cafe.index')->with('error', 'Bu masa şu anda temizleniyor!');
        }

        $categories = Category::where('is_active', true)->with(['products' => function($query) {
            $query->where('is_active', true);
        }])->get();

        return view('cafe.order', compact('table', 'categories'));
    }

    public function storeOrder(Request $request, Table $table)
    {
        $request->validate([
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'notes' => 'nullable|string'
        ]);

        DB::beginTransaction();

        try {
            $totalAmount = 0;
            $items = [];

            // Ürün kontrolü ve toplam hesaplama
            foreach ($request->items as $item) {
                $product = Product::find($item['product_id']);
                
                if (!$product || !$product->is_active) {
                    throw new \Exception("Ürün bulunamadı veya aktif değil.");
                }

                $unitPrice = $product->sale_price;
                $totalPrice = $unitPrice * $item['quantity'];
                $totalAmount += $totalPrice;

                $items[] = [
                    'product' => $product,
                    'quantity' => $item['quantity'],
                    'unit_price' => $unitPrice,
                    'total_price' => $totalPrice,
                    'notes' => $item['notes'] ?? null
                ];
            }

            // Bu masa için ödenmemiş aktif sipariş var mı kontrol et
            $existingOrder = CafeOrder::where('table_id', $table->id)
                ->where('is_paid', false)
                ->whereNotIn('status', [CafeOrder::STATUS_CANCELLED])
                ->first();

            if ($existingOrder) {
                // Mevcut siparişe ürünleri ekle
                foreach ($items as $item) {
                    // Aynı üründen varsa miktarını artır, yoksa yeni ekle
                    $existingItem = CafeOrderItem::where('cafe_order_id', $existingOrder->id)
                        ->where('product_id', $item['product']->id)
                        ->where('unit_price', $item['unit_price'])
                        ->where('status', '!=', CafeOrderItem::STATUS_SERVED) // Servis edilmemiş olanları bul
                        ->first();

                    if ($existingItem) {
                        // Mevcut ürünün miktarını artır
                        $existingItem->update([
                            'quantity' => $existingItem->quantity + $item['quantity'],
                            'total_price' => ($existingItem->quantity + $item['quantity']) * $item['unit_price']
                        ]);
                    } else {
                        // Yeni ürün ekle (varsayılan durum: pending)
                        CafeOrderItem::create([
                            'cafe_order_id' => $existingOrder->id,
                            'product_id' => $item['product']->id,
                            'quantity' => $item['quantity'],
                            'unit_price' => $item['unit_price'],
                            'total_price' => $item['total_price'],
                            'notes' => $item['notes'],
                            'status' => CafeOrderItem::STATUS_PENDING
                        ]);
                    }
                }

                // Siparişin toplam tutarını güncelle
                $existingOrder->update([
                    'total_amount' => $existingOrder->total_amount + $totalAmount
                ]);

                // Log oluştur
                foreach ($items as $item) {
                    $noteMsg = $item['notes'] ? " (Not: {$item['notes']})" : "";
                    CafeOrderLog::create([
                        'cafe_order_id' => $existingOrder->id,
                        'user_id' => Auth::id(),
                        'action' => 'item_added',
                        'message' => "{$item['quantity']} adet {$item['product']->name} eklendi.{$noteMsg}"
                    ]);
                }

                // Ek sipariş notu ekle
                if ($request->notes) {
                    CafeOrderNote::create([
                        'cafe_order_id' => $existingOrder->id,
                        'user_id' => Auth::id(),
                        'note' => $request->notes,
                        'note_type' => CafeOrderNote::TYPE_ADDITIONAL
                    ]);

                    CafeOrderLog::create([
                        'cafe_order_id' => $existingOrder->id,
                        'user_id' => Auth::id(),
                        'action' => 'note_added',
                        'message' => "Sipariş notu eklendi: {$request->notes}"
                    ]);
                }

                // Eğer sipariş servis edilmişse, ek sipariş geldiğinde tekrar bekliyor durumuna al
                if ($existingOrder->status === CafeOrder::STATUS_SERVED) {
                    $existingOrder->update(['status' => CafeOrder::STATUS_PENDING]);
                }

                $order = $existingOrder;
                $message = 'Ek sipariş başarıyla eklendi!';

            } else {
                // Yeni sipariş oluştur - Benzersiz sipariş numarası oluştur
                do {
                    $orderNumber = 'CAFE-' . date('Ymd') . '-' . str_pad(CafeOrder::whereDate('created_at', today())->max('id') + 1, 4, '0', STR_PAD_LEFT);
                } while (CafeOrder::where('order_number', $orderNumber)->exists());

                $order = CafeOrder::create([
                    'table_id' => $table->id,
                    'user_id' => Auth::id(),
                    'order_number' => $orderNumber,
                    'total_amount' => $totalAmount,
                    'status' => CafeOrder::STATUS_PENDING,
                    'notes' => $request->notes
                ]);

                // İlk sipariş notu ekle
                if ($request->notes) {
                    CafeOrderNote::create([
                        'cafe_order_id' => $order->id,
                        'user_id' => Auth::id(),
                        'note' => $request->notes,
                        'note_type' => CafeOrderNote::TYPE_INITIAL
                    ]);

                    CafeOrderLog::create([
                        'cafe_order_id' => $order->id,
                        'user_id' => Auth::id(),
                        'action' => 'note_added',
                        'message' => "Sipariş notu eklendi: {$request->notes}"
                    ]);
                }

                // Log oluştur
                CafeOrderLog::create([
                    'cafe_order_id' => $order->id,
                    'user_id' => Auth::id(),
                    'action' => 'order_created',
                    'message' => 'Sipariş oluşturuldu.'
                ]);

                // Sipariş detayları (varsayılan durum: pending)
                foreach ($items as $item) {
                    CafeOrderItem::create([
                        'cafe_order_id' => $order->id,
                        'product_id' => $item['product']->id,
                        'quantity' => $item['quantity'],
                        'unit_price' => $item['unit_price'],
                        'total_price' => $item['total_price'],
                        'notes' => $item['notes'],
                        'status' => CafeOrderItem::STATUS_PENDING
                    ]);

                    $noteMsg = $item['notes'] ? " (Not: {$item['notes']})" : "";
                    CafeOrderLog::create([
                        'cafe_order_id' => $order->id,
                        'user_id' => Auth::id(),
                        'action' => 'item_added',
                        'message' => "{$item['quantity']} adet {$item['product']->name} eklendi.{$noteMsg}"
                    ]);
                }

                $message = 'Sipariş başarıyla alındı!';
            }

            // Masa durumunu güncelle (sadece müsait ise dolu yap)
            if ($table->status === Table::STATUS_AVAILABLE) {
                $table->update(['status' => Table::STATUS_OCCUPIED]);
                
                // Masa durumu değişimi logu (Sipariş ID'si ile ilişkilendirerek)
                CafeOrderLog::create([
                    'cafe_order_id' => $order->id,
                    'user_id' => Auth::id(),
                    'action' => 'table_status_changed',
                    'message' => "Masa durumu 'Müsait' -> 'Dolu' olarak değişti."
                ]);
            }

            DB::commit();

            // Otomatik fiş yazdırma kontrolü
            $autoPrintEnabled = Setting::getValue('auto_print_receipt', '1');
            if ($autoPrintEnabled === '1') {
                if (!$existingOrder) {
                    $this->printOrderReceipt($order);
                } else {
                    try {
                        $receiptContent = $this->generateExtrasReceipt($order, $items, $request->notes);
                        $this->manualPrintReceipt($order, $receiptContent);
                    } catch (\Exception $e) {
                        \Log::error('Ek sipariş fiş yazdırma hatası', [
                            'order_id' => $order->id,
                            'error' => $e->getMessage()
                        ]);
                    }
                }
            }

            return redirect()->route('cafe.order.show', $order)->with('success', $message);

        } catch (\Exception $e) {
            DB::rollback();
            return back()->with('error', $e->getMessage())->withInput();
        }
    }

    public function showOrder(CafeOrder $order)
    {
        $order->load(['table', 'user', 'cafeOrderItems.product', 'cafeOrderNotes.user', 'cafeOrderExtras', 'cafeOrderLogs.user']);
        return view('cafe.order-detail', compact('order'));
    }

    private function getReadableStatus($status)
    {
        $statuses = [
            'pending' => 'Bekliyor',
            'preparing' => 'Hazırlanıyor',
            'ready' => 'Hazır',
            'served' => 'Servis Edildi',
            'cancelled' => 'İptal Edildi',
            'available' => 'Müsait',
            'occupied' => 'Dolu',
            'cleaning' => 'Temizleniyor',
            'reserved' => 'Rezerve'
        ];

        return $statuses[$status] ?? $status;
    }

    public function updateOrderStatus(Request $request, CafeOrder $order)
    {
        $request->validate([
            'status' => 'required|in:pending,preparing,ready,served,cancelled',
            'extra_amount' => 'nullable|numeric|min:0',
            'extra_description' => 'nullable|string|max:255'
        ]);

        DB::beginTransaction();

        try {
            $oldStatus = $order->status;
            
            // Debug: Sipariş durumu güncelleme logları
            \Log::info('updateOrderStatus çağrıldı', [
                'order_id' => $order->id,
                'old_status' => $oldStatus,
                'new_status' => $request->status
            ]);
            
            // Sipariş durumunu güncelle
            $order->update(['status' => $request->status]);

            $oldStatusText = $this->getReadableStatus($oldStatus);
            $newStatusText = $this->getReadableStatus($request->status);

            CafeOrderLog::create([
                'cafe_order_id' => $order->id,
                'user_id' => Auth::id(),
                'action' => 'status_changed',
                'message' => "Sipariş durumu '{$oldStatusText}' -> '{$newStatusText}' olarak değiştirildi."
            ]);

            // Eğer sipariş "servis edildi" olarak işaretlendiyse
            if ($request->status === CafeOrder::STATUS_SERVED && $oldStatus !== CafeOrder::STATUS_SERVED) {
                \Log::info('Sipariş servis edildi olarak işaretlendi, tüm itemları güncelleniyor');
                
                // Tüm ürünleri de "servis edildi" yap ve stok düşümü yap
                foreach ($order->cafeOrderItems as $item) {
                    $itemOldStatus = $item->status;
                    
                    // Item durumunu güncelle
                    $item->update(['status' => CafeOrderItem::STATUS_SERVED]);
                    
                    // Eğer item daha önce served değilse stok düşümü yap
                    if ($itemOldStatus !== CafeOrderItem::STATUS_SERVED) {
                        \Log::info('Item için stok düşümü yapılıyor', [
                            'item_id' => $item->id,
                            'old_status' => $itemOldStatus
                        ]);
                        $this->handleAutoStockUpdate($item);
                    }
                }
                
                // Masa durumunu tekrar dolu yap (iptalden geri alma vb.)
                if (in_array($order->table->status, [Table::STATUS_AVAILABLE, Table::STATUS_RESERVED])) {
                    $order->table->update([
                        'status' => Table::STATUS_OCCUPIED,
                        'occupied_at' => now()
                    ]);

                    CafeOrderLog::create([
                        'cafe_order_id' => $order->id,
                        'user_id' => Auth::id(),
                        'action' => 'table_status_changed',
                        'message' => "Sipariş 'Servis Edildi' olarak güncellendi - masa durumu 'Müsait' -> 'Dolu' olarak değişti."
                    ]);
                }
            }

            // Ekstra fiyat eklenmişse
            if ($request->filled('extra_amount') && $request->extra_amount > 0) {
                // Yeni sistem: CafeOrderExtra tablosuna kaydet
                CafeOrderExtra::create([
                    'cafe_order_id' => $order->id,
                    'amount' => $request->extra_amount,
                    'description' => $request->extra_description ?: 'Ekstra ücret'
                ]);

                // Sipariş toplam tutarını güncelle
                $order->update([
                    'total_amount' => $order->total_amount + $request->extra_amount
                ]);

                CafeOrderLog::create([
                    'cafe_order_id' => $order->id,
                    'user_id' => Auth::id(),
                    'action' => 'extra_added',
                    'message' => 'Ekstra ücret eklendi: ' . number_format($request->extra_amount, 2) . ' ₺' . ($request->extra_description ? ' (' . $request->extra_description . ')' : '')
                ]);
            }

            // Sipariş iptal edilirse masayı boşalt
            if ($request->status === CafeOrder::STATUS_CANCELLED) {
                // Bu masanın başka aktif siparişi var mı kontrol et
                $otherActiveOrders = CafeOrder::where('table_id', $order->table_id)
                    ->where('id', '!=', $order->id)
                    ->where('is_paid', false)
                    ->whereNotIn('status', [CafeOrder::STATUS_CANCELLED])
                    ->count();

                // Başka aktif sipariş yoksa masayı boşalt
                if ($otherActiveOrders === 0) {
                    $order->table->update([
                        'status' => Table::STATUS_AVAILABLE,
                        'occupied_at' => null
                    ]);

                    CafeOrderLog::create([
                        'cafe_order_id' => $order->id,
                        'user_id' => Auth::id(),
                        'action' => 'table_status_changed',
                        'message' => "Sipariş iptali nedeniyle masa durumu 'Dolu' -> 'Müsait' olarak değişti."
                    ]);
                }
            }

            DB::commit();

            return back()->with('success', 'Sipariş durumu başarıyla güncellendi!');

        } catch (\Exception $e) {
            DB::rollback();
            \Log::error('updateOrderStatus hatası', ['error' => $e->getMessage()]);
            return back()->with('error', 'Güncelleme sırasında hata oluştu: ' . $e->getMessage());
        }
    }

    public function updateItemStatus(Request $request, CafeOrderItem $item)
    {
        // Debug: Fonksiyonun çağrıldığını kontrol edelim
        \Log::info('updateItemStatus çağrıldı', [
            'item_id' => $item->id,
            'old_status' => $item->status,
            'new_status' => $request->status
        ]);

        $request->validate([
            'status' => 'required|in:pending,preparing,ready,served,cancelled'
        ]);

        $oldStatus = $item->status;
        $item->update(['status' => $request->status]);

        $oldStatusText = $this->getReadableStatus($oldStatus);
        $newStatusText = $this->getReadableStatus($request->status);

        CafeOrderLog::create([
            'cafe_order_id' => $item->cafe_order_id,
            'user_id' => Auth::id(),
            'action' => 'item_status_changed',
            'message' => "{$item->product->name} durumu '{$oldStatusText}' -> '{$newStatusText}' olarak değiştirildi."
        ]);

        \Log::info('Ürün durumu güncellendi', [
            'item_id' => $item->id,
            'old_status' => $oldStatus,
            'new_status' => $request->status
        ]);

        // Otomatik stok güncelleme kontrolü
        if ($request->status === 'served' && $oldStatus !== 'served') {
            \Log::info('Stok güncelleme koşulu sağlandı, handleAutoStockUpdate çağrılıyor');
            $this->handleAutoStockUpdate($item);
        } else {
            \Log::info('Stok güncelleme koşulu sağlanmadı', [
                'new_status' => $request->status,
                'old_status' => $oldStatus,
                'condition_met' => ($request->status === 'served' && $oldStatus !== 'served')
            ]);
        }

        // Sipariş durumunu otomatik güncelle
        $this->updateOrderStatusBasedOnItems($item->cafeOrder);

        return back()->with('success', 'Ürün durumu güncellendi!');
    }

    private function updateOrderStatusBasedOnItems(CafeOrder $order)
    {
        $items = $order->cafeOrderItems;
        
        if ($items->isEmpty()) {
            return;
        }

        // Tüm ürünler servis edildiyse
        if ($items->every(fn($item) => $item->status === CafeOrderItem::STATUS_SERVED)) {
            $order->update(['status' => CafeOrder::STATUS_SERVED]);
        }
        // Herhangi bir ürün hazırlanıyorsa
        elseif ($items->contains('status', CafeOrderItem::STATUS_PREPARING)) {
            $order->update(['status' => CafeOrder::STATUS_PREPARING]);
        }
        // Herhangi bir ürün hazırsa
        elseif ($items->contains('status', CafeOrderItem::STATUS_READY)) {
            $order->update(['status' => CafeOrder::STATUS_READY]);
        }
        // Aksi halde bekliyor
        else {
            $order->update(['status' => CafeOrder::STATUS_PENDING]);
        }
    }

    /**
     * Otomatik stok güncelleme işlemi
     */
    private function handleAutoStockUpdate(CafeOrderItem $item)
    {
        // Debug: Log ekleyelim
        \Log::info('handleAutoStockUpdate çağrıldı', [
            'item_id' => $item->id,
            'product_id' => $item->product_id,
            'quantity' => $item->quantity
        ]);

        // Otomatik stok güncelleme ayarını kontrol et
        $autoStockUpdate = Setting::getValue('auto_stock_update', '0');
        
        \Log::info('Auto stock update ayarı', ['value' => $autoStockUpdate]);
        
        if ($autoStockUpdate !== '1') {
            \Log::info('Auto stock update kapalı, işlem yapılmıyor');
            return; // Ayar kapalıysa işlem yapma
        }

        $product = $item->product;
        if (!$product) {
            \Log::warning('Ürün bulunamadı', ['item_id' => $item->id]);
            return; // Ürün bulunamazsa işlem yapma
        }

        // Bu item için daha önce stok düşümü yapılmış mı kontrol et
        // reason kolonunda sipariş numarası ve item ID'yi arayalım
        $reasonPattern = "Kafe siparişi otomatik stok düşümü - Sipariş: {$item->cafeOrder->order_number}, Item ID: {$item->id}";
        
        $existingMovement = StockMovement::where('product_id', $product->id)
            ->where('type', 'out')
            ->where('reason', $reasonPattern)
            ->first();

        if ($existingMovement) {
            \Log::info('Bu item için zaten stok düşümü yapılmış, tekrar yapılmıyor', [
                'item_id' => $item->id,
                'existing_movement_id' => $existingMovement->id
            ]);
            return;
        }

        \Log::info('Stok güncelleme başlıyor', [
            'product_id' => $product->id,
            'current_stock' => $product->stock_quantity,
            'quantity_to_reduce' => $item->quantity
        ]);

        // Stok miktarını azalt (negatife düşmemesini sağla)
        $newStock = max(0, $product->stock_quantity - $item->quantity);
        $actualReduction = $product->stock_quantity - $newStock;
        
        if ($actualReduction > 0) {
            $product->update(['stock_quantity' => $newStock]);

            \Log::info('Stok güncellendi', [
                'product_id' => $product->id,
                'old_stock' => $product->stock_quantity + $actualReduction,
                'new_stock' => $newStock,
                'reduction' => $actualReduction
            ]);

            // Stok hareketi kaydı oluştur - Item ID'yi reason'a ekle
            StockMovement::create([
                'product_id' => $product->id,
                'type' => 'out',
                'quantity' => $actualReduction,
                'reason' => "Kafe siparişi otomatik stok düşümü - Sipariş: {$item->cafeOrder->order_number}, Masa: {$item->cafeOrder->table->name}, Item ID: {$item->id}",
                'user_id' => auth()->id()
            ]);

            \Log::info('Stok hareketi kaydı oluşturuldu');
        } else {
            \Log::info('Stok zaten 0 veya yetersiz, güncelleme yapılmadı');
        }
    }

    public function removeExtraPrice(CafeOrderExtra $extra)
    {
        $order = $extra->cafeOrder;
        $amount = $extra->amount;
        
        // Sipariş ödenmiş mi kontrol et
        if ($order->is_paid) {
            return back()->with('error', 'Ödeme alınmış siparişten ekstra fiyat silinemez!');
        }
        
        // Sipariş toplam tutarını güncelle
        $order->update([
            'total_amount' => $order->total_amount - $amount,
            'extra_amount' => ($order->extra_amount ?? 0) - $amount
        ]);
        
        // Ekstra fiyat kaydını sil
        $extra->delete();
        
        CafeOrderLog::create([
            'cafe_order_id' => $order->id,
            'user_id' => Auth::id(),
            'action' => 'extra_removed',
            'message' => 'Ekstra ücret silindi: ' . number_format($amount, 2) . ' ₺' . ($extra->description ? ' (' . $extra->description . ')' : '')
        ]);
        
        return back()->with('success', 'Ekstra fiyat (' . number_format($amount, 2) . ' ₺) başarıyla silindi!');
    }

    public function removeExtraAmount(CafeOrder $order)
    {
        // Tüm ekstra fiyatları sil
        $totalExtra = $order->cafeOrderExtras->sum('amount');
        
        if ($totalExtra > 0) {
            $order->cafeOrderExtras()->delete();
            $order->update([
                'total_amount' => $order->total_amount - $totalExtra,
                'extra_amount' => 0
            ]);
            
            CafeOrderLog::create([
                'cafe_order_id' => $order->id,
                'user_id' => Auth::id(),
                'action' => 'extra_removed',
                'message' => 'Tüm ekstra fiyatlar silindi: ' . number_format($totalExtra, 2) . ' ₺'
            ]);
            
            return back()->with('success', 'Tüm ekstra fiyatlar (' . number_format($totalExtra, 2) . ' ₺) başarıyla silindi!');
        }
        
        return back()->with('info', 'Silinecek ekstra fiyat bulunamadı.');
    }

    // Normal ödeme işlemi
    public function processPayment(Request $request, CafeOrder $order)
    {
        if ($order->is_paid) {
            return back()->with('error', 'Bu sipariş zaten ödenmiş!');
        }

        if ($order->status !== 'served') {
            return back()->with('error', 'Sipariş henüz servis edilmemiş!');
        }

        $paymentMethod = $request->payment_method;
        
        if (!in_array($paymentMethod, ['cash', 'card'])) {
            return back()->with('error', 'Geçersiz ödeme yöntemi!');
        }

        // İndirim hesaplaması
        $discountPercentage = (int) ($request->discount_percentage ?? 0);
        $originalAmount = $order->total_amount;
        $discountAmount = 0;
        $finalAmount = $originalAmount;
        
        if ($discountPercentage > 0) {
            $discountAmount = ($originalAmount * $discountPercentage) / 100;
            $finalAmount = $originalAmount - $discountAmount;
        }

        DB::beginTransaction();

        try {
            // İndirim bilgilerini siparişe kaydet
            if ($discountPercentage > 0) {
                $order->update([
                    'discount_percentage' => $discountPercentage,
                    'discount_amount' => $discountAmount,
                    'final_amount' => $finalAmount
                ]);
            }

            // Kısmi ödeme kontrolü
            $existingPayments = $order->total_paid_amount;
            $remainingAmount = $discountPercentage > 0 ? $finalAmount : $order->remaining_amount;
            
            if ($existingPayments > 0) {
                // Kısmi ödeme var, kalan tutarı öde
                CafeOrderPayment::create([
                    'cafe_order_id' => $order->id,
                    'amount' => $remainingAmount,
                    'payment_method' => $paymentMethod,
                    'description' => 'Kalan tutar ödemesi'
                ]);
                
                $paymentText = $paymentMethod === 'cash' ? 'Nakit' : 'Kredi Kartı';
                $message = "Kalan tutar {$paymentText} ile ödendi! Toplam: " . number_format($finalAmount, 2) . " ₺";
            } else {
                // Normal tam ödeme
                $paymentAmount = $discountPercentage > 0 ? $finalAmount : $originalAmount;
                
                CafeOrderPayment::create([
                    'cafe_order_id' => $order->id,
                    'amount' => $paymentAmount,
                    'payment_method' => $paymentMethod,
                    'description' => $discountPercentage > 0 ? "İndirimli ödeme (%{$discountPercentage})" : 'Tam ödeme'
                ]);
                
                $paymentText = $paymentMethod === 'cash' ? 'Nakit' : 'Kredi Kartı';
                $message = "{$paymentText} ödemesi başarıyla tamamlandı!";
                
                if ($discountPercentage > 0) {
                    $message .= " (İndirim: %{$discountPercentage} - " . number_format($discountAmount, 2) . " ₺)";
                }
            }

            // Siparişi ödendi olarak işaretle
            $order->update([
                'payment_method' => $existingPayments > 0 ? CafeOrder::PAYMENT_SPLIT : $paymentMethod,
                'is_paid' => true,
                'paid_at' => now()
            ]);

            // Masanın başka ödenmemiş siparişi var mı kontrol et
            $unpaidOrdersCount = CafeOrder::where('table_id', $order->table_id)
                ->where('is_paid', false)
                ->whereNotIn('status', [CafeOrder::STATUS_CANCELLED])
                ->where('id', '!=', $order->id)
                ->count();

            // Debug log ekle
            \Log::info('Masa boşaltma kontrolü', [
                'table_id' => $order->table_id,
                'order_id' => $order->id,
                'unpaid_orders_count' => $unpaidOrdersCount,
                'current_table_status' => $order->table->status
            ]);

            // Başka ödenmemiş sipariş yoksa masayı boşalt
            if ($unpaidOrdersCount === 0) {
                $order->table->update([
                    'status' => Table::STATUS_AVAILABLE,
                    'occupied_at' => null
                ]);
                
                \Log::info('Masa boşaltıldı', [
                    'table_id' => $order->table_id,
                    'table_name' => $order->table->name
                ]);

                CafeOrderLog::create([
                    'cafe_order_id' => $order->id,
                    'user_id' => Auth::id(),
                    'action' => 'table_status_changed',
                    'message' => "Ödeme sonrası masa durumu 'Dolu' -> 'Müsait' olarak değişti."
                ]);
            } else {
                \Log::info('Masa boşaltılmadı - başka ödenmemiş siparişler var', [
                    'table_id' => $order->table_id,
                    'unpaid_count' => $unpaidOrdersCount
                ]);
            }

            DB::commit();



            return redirect()->route('cafe.order.show', $order)->with('success', $message);

        } catch (\Exception $e) {
            DB::rollback();
            \Log::error('Payment Error:', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return back()->with('error', 'Ödeme işlemi sırasında bir hata oluştu: ' . $e->getMessage());
        }
    }

    // Ödeme iptal işlemi
    public function cancelPayment(CafeOrder $order)
    {
        if (!$order->is_paid) {
            return back()->with('error', 'Bu siparişin ödemesi alınmamış!');
        }

        DB::beginTransaction();

        try {
            // Split payment kayıtlarını sil
            CafeOrderPayment::where('cafe_order_id', $order->id)->delete();
            
            // Ödeme bilgilerini ve indirim bilgilerini sıfırla
            $order->update([
                'payment_method' => null,
                'is_paid' => false,
                'paid_at' => null,
                'discount_percentage' => 0,
                'discount_amount' => 0,
                'final_amount' => 0
            ]);

            // Masayı tekrar dolu durumuna getir
            $order->table->update([
                'status' => Table::STATUS_OCCUPIED,
                'occupied_at' => now()
            ]);

            CafeOrderLog::create([
                'cafe_order_id' => $order->id,
                'user_id' => Auth::id(),
                'action' => 'table_status_changed',
                'message' => "Ödeme iptali sonrası masa durumu 'Müsait' -> 'Dolu' olarak değişti."
            ]);

            DB::commit();

            return back()->with('success', 'Ödeme başarıyla iptal edildi ve ödeme kayıtları temizlendi!');

        } catch (\Exception $e) {
            DB::rollback();
            return back()->with('error', 'Ödeme iptal işlemi sırasında hata oluştu: ' . $e->getMessage());
        }
    }

    public function orders(Request $request)
    {
        $query = CafeOrder::with(['table', 'user', 'cafeOrderItems.product']);

        // Arama filtresi
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                  ->orWhereHas('table', function($tableQuery) use ($search) {
                      $tableQuery->where('name', 'like', "%{$search}%");
                  })
                  ->orWhereHas('user', function($userQuery) use ($search) {
                      $userQuery->where('name', 'like', "%{$search}%");
                  });
            });
        }

        // Masa filtresi
        if ($request->filled('table_id')) {
            $query->where('table_id', $request->table_id);
        }

        // Durum filtresi
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Ödeme durumu filtresi
        if ($request->filled('payment_status')) {
            if ($request->payment_status === 'paid') {
                $query->where('is_paid', true);
            } elseif ($request->payment_status === 'unpaid') {
                $query->where('is_paid', false);
            }
        }

        // Tarih filtresi
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $orders = $query->orderBy('created_at', 'desc')->paginate(20);
        
        // Masaları da gönder
        $tables = Table::orderBy('name')->get();

        return view('cafe.orders', compact('orders', 'tables'));
    }

    // Split payment sayfası
    public function splitPayment(Request $request, CafeOrder $order)
    {
        if ($order->is_paid) {
            return back()->with('error', 'Bu sipariş zaten ödenmiş!');
        }

        if ($order->status !== 'served') {
            return back()->with('error', 'Sipariş henüz servis edilmemiş!');
        }

        // İndirim parametresini al
        $discountPercentage = $request->get('discount', 0);
        
        return view('cafe.split-payment', compact('order', 'discountPercentage'));
    }

    // Split payment işlemi
    public function processSplitPayment(Request $request, CafeOrder $order)
    {
        if ($order->is_paid) {
            return back()->with('error', 'Bu sipariş zaten ödenmiş!');
        }

        if ($order->status === CafeOrder::STATUS_CANCELLED) {
            return back()->with('error', 'İptal edilmiş siparişe ödeme yapılamaz!');
        }

        // DEBUG: Raw request data'yı logla
        \Log::info('Raw Request Data:', [
            'payment_data_raw' => $request->payment_data,
            'all_request' => $request->all()
        ]);

        $paymentData = json_decode($request->payment_data, true);
        
        // DEBUG: Decoded payment data'yı logla
        \Log::info('Decoded Payment Data:', [
            'payment_data' => $paymentData,
            'json_last_error' => json_last_error(),
            'json_last_error_msg' => json_last_error_msg()
        ]);
        
        if (!$paymentData || empty($paymentData)) {
            return back()->with('error', 'Ödeme bilgileri eksik!');
        }

        // Toplam kontrolü
        $totalPaid = array_sum(array_column($paymentData, 'amount'));
        if (abs($totalPaid - $order->total_amount) > 0.01) {
            return back()->with('error', 'Ödeme tutarı sipariş tutarı ile eşleşmiyor!');
        }

        // Ödeme yöntemlerini kontrol et
        foreach ($paymentData as $index => $payment) {
            \Log::info("Payment {$index} validation:", [
                'payment' => $payment,
                'payment_method' => $payment['payment_method'] ?? 'NOT_SET',
                'amount' => $payment['amount'] ?? 'NOT_SET'
            ]);
            
            if (!in_array($payment['payment_method'], ['cash', 'card'])) {
                return back()->with('error', 'Geçersiz ödeme yöntemi: ' . ($payment['payment_method'] ?? 'boş'));
            }
            if ($payment['amount'] <= 0) {
                return back()->with('error', 'Ödeme tutarı sıfırdan büyük olmalıdır!');
            }
        }

        DB::beginTransaction();

        try {
            // Her ödeme için kayıt oluştur
            foreach ($paymentData as $index => $payment) {
                \Log::info("Creating payment {$index}:", [
                    'cafe_order_id' => $order->id,
                    'amount' => $payment['amount'],
                    'payment_method' => $payment['payment_method'],
                    'description' => $payment['description'] ?? 'Bölünmüş ödeme'
                ]);
                
                $createdPayment = CafeOrderPayment::create([
                    'cafe_order_id' => $order->id,
                    'amount' => $payment['amount'],
                    'payment_method' => $payment['payment_method'],
                    'description' => $payment['description'] ?? 'Bölünmüş ödeme'
                ]);
                
                // DEBUG: Oluşturulan payment'ı logla
                \Log::info('Created Payment:', [
                    'id' => $createdPayment->id,
                    'cafe_order_id' => $createdPayment->cafe_order_id,
                    'amount' => $createdPayment->amount,
                    'payment_method' => $createdPayment->payment_method,
                    'description' => $createdPayment->description
                ]);
                
                // Veritabanından tekrar oku ve kontrol et
                $dbPayment = CafeOrderPayment::find($createdPayment->id);
                \Log::info('Payment from DB:', [
                    'id' => $dbPayment->id,
                    'payment_method' => $dbPayment->payment_method,
                    'amount' => $dbPayment->amount
                ]);
            }

            // Siparişi ödendi olarak işaretle
            $order->update([
                'payment_method' => CafeOrder::PAYMENT_SPLIT,
                'is_paid' => true,
                'paid_at' => now()
            ]);

            // Masanın başka ödenmemiş siparişi var mı kontrol et
            $unpaidOrdersCount = CafeOrder::where('table_id', $order->table_id)
                ->where('is_paid', false)
                ->whereNotIn('status', [CafeOrder::STATUS_CANCELLED])
                ->where('id', '!=', $order->id)
                ->count();

            // Başka ödenmemiş sipariş yoksa masayı boşalt
            if ($unpaidOrdersCount === 0) {
                $order->table->update([
                    'status' => Table::STATUS_AVAILABLE,
                    'occupied_at' => null
                ]);

                CafeOrderLog::create([
                    'cafe_order_id' => $order->id,
                    'user_id' => Auth::id(),
                    'action' => 'table_status_changed',
                    'message' => "Bölünmüş ödeme sonrası masa durumu 'Dolu' -> 'Müsait' olarak değişti."
                ]);
            }

            DB::commit();

            return redirect()->route('cafe.order.show', $order)->with('success', 'Bölünmüş ödeme başarıyla tamamlandı!');

        } catch (\Exception $e) {
            DB::rollback();
            \Log::error('Split Payment Error:', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return back()->with('error', 'Ödeme işlemi sırasında bir hata oluştu: ' . $e->getMessage());
        }
    }

    // Masa taşıma işlemi
    public function transferOrder(Request $request, CafeOrder $order)
    {
        $request->validate([
            'target_table_id' => 'required|exists:tables,id',
            'transfer_reason' => 'nullable|string|max:255'
        ]);

        $newTable = Table::findOrFail($request->target_table_id);
        
        // Yeni masa müsait mi kontrol et
        if ($newTable->status !== 'available') {
            return back()->with('error', 'Seçilen masa müsait değil!');
        }

        // Sipariş ödenmiş mi kontrol et
        if ($order->is_paid) {
            return back()->with('error', 'Ödenen sipariş taşınamaz!');
        }

        $oldTable = $order->table;

        DB::beginTransaction();
        try {
            // Siparişi yeni masaya taşı
            $order->update(['table_id' => $newTable->id]);

            // Eski masayı boşalt
            $oldTable->update([
                'status' => 'available',
                'occupied_at' => null
            ]);

            // Yeni masayı dolu yap
            $newTable->update([
                'status' => 'occupied',
                'occupied_at' => now()
            ]);

            $reasonMsg = $request->transfer_reason ? " (Sebep: {$request->transfer_reason})" : "";
            CafeOrderLog::create([
                'cafe_order_id' => $order->id,
                'user_id' => Auth::id(),
                'action' => 'table_transfer',
                'message' => "Sipariş {$oldTable->name} masasından {$newTable->name} masasına taşındı.{$reasonMsg}"
            ]);

            // Transfer sebebini sipariş notlarına ekle
            if ($request->transfer_reason) {
                $order->update([
                    'notes' => ($order->notes ? $order->notes . ' | ' : '') . 
                              'Masa Taşıma: ' . $request->transfer_reason
                ]);
            }

            DB::commit();

            return redirect()->route('cafe.order.show', $order)
                           ->with('success', "Sipariş {$oldTable->name} masasından {$newTable->name} masasına başarıyla taşındı!");

        } catch (\Exception $e) {
            DB::rollback();
            return back()->with('error', 'Masa taşıma işlemi sırasında bir hata oluştu: ' . $e->getMessage());
        }
    }

    // Müsait masaları getir
    public function getAvailableTablesForTransfer(CafeOrder $order)
    {
        try {
            $availableTables = Table::where('status', 'available')
                                    ->where('is_active', true)
                                    ->where('id', '!=', $order->table_id)
                                    ->orderBy('name')
                                    ->get(['id', 'name', 'capacity', 'status']);

            return response()->json($availableTables);
        } catch (\Exception $e) {
            \Log::error('getAvailableTablesForTransfer hatası', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Masalar yüklenirken hata oluştu'], 500);
        }
    }

    /**
     * Masa birleştirme için dolu masaları getir
     */
    public function getOccupiedTablesForMerge(CafeOrder $order)
    {
        try {
            // Mevcut siparişin masası hariç, sadece dolu (occupied) ve aktif (ödenmemiş, iptal edilmemiş) siparişe sahip masaları getir
            $occupiedTables = Table::where('status', Table::STATUS_OCCUPIED)
                ->where('id', '!=', $order->table_id)
                ->whereHas('cafeOrders', function($query) {
                    $query->where('is_paid', false)
                          ->where('status', '!=', CafeOrder::STATUS_CANCELLED);
                })
                ->get()
                ->map(function($table) {
                    $orderCount = $table->cafeOrders()
                        ->where('is_paid', false)
                        ->where('status', '!=', CafeOrder::STATUS_CANCELLED)
                        ->count();
                    return [
                        'id' => $table->id,
                        'name' => $table->name,
                        'capacity' => $table->capacity,
                        'order_count' => $orderCount
                    ];
                });

            return response()->json($occupiedTables);
        } catch (\Exception $e) {
            \Log::error('getOccupiedTablesForMerge hatası', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Masalar yüklenirken hata oluştu'], 500);
        }
    }

    /**
     * Siparişi başka masayla birleştir
     */
    public function mergeOrder(Request $request, CafeOrder $order)
    {
        $request->validate([
            'target_order_id' => 'required|exists:cafe_orders,id',
            'merge_reason' => 'nullable|string|max:255'
        ]);

        if ($order->is_paid) {
            return back()->with('error', 'Ödenmiş sipariş birleştirilemez!');
        }

        $targetOrder = CafeOrder::where('id', $request->target_order_id)
            ->where('is_paid', false)
            ->where('status', '!=', CafeOrder::STATUS_CANCELLED)
            ->first();

        if (!$targetOrder) {
            return back()->with('error', 'Hedef sipariş bulunamadı veya birleştirilemez durumda!');
        }

        try {
            DB::beginTransaction();

            $targetTable = $targetOrder->table;
            $sourceTable = $order->table;

            $sourceHasPayments = $order->cafeOrderPayments()->exists();

            if ($sourceHasPayments) {
                foreach ($order->cafeOrderItems as $item) {
                    $item->update(['cafe_order_id' => $targetOrder->id]);
                }
            } else {
                foreach ($order->cafeOrderItems as $item) {
                    $existingItem = $targetOrder->cafeOrderItems()
                        ->where('product_id', $item->product_id)
                        ->where('status', $item->status)
                        ->first();

                    if ($existingItem) {
                        $existingItem->quantity += $item->quantity;
                        $existingItem->total_price += $item->total_price;
                        $existingItem->save();
                        $item->delete();
                    } else {
                        $item->update(['cafe_order_id' => $targetOrder->id]);
                    }
                }
            }

            foreach ($order->cafeOrderExtras as $extra) {
                $extra->update(['cafe_order_id' => $targetOrder->id]);
            }

            if ($sourceHasPayments) {
                $order->cafeOrderPayments()->update(['cafe_order_id' => $targetOrder->id]);
                $targetOrder->payment_method = CafeOrder::PAYMENT_SPLIT;
                $targetOrder->save();
            }

            $targetOrder->calculateTotal();

            $reasonMsg = $request->merge_reason ? " (Sebep: {$request->merge_reason})" : "";
            CafeOrderLog::create([
                'cafe_order_id' => $targetOrder->id,
                'user_id' => Auth::id(),
                'action' => 'order_merged',
                'message' => "Sipariş {$sourceTable->name} masasından birleştirildi.{$reasonMsg}"
            ]);

            $order->delete();

            $sourceTable->update([
                'status' => Table::STATUS_AVAILABLE,
                'occupied_at' => null
            ]);

            DB::commit();

            return redirect()->route('cafe.index')
                ->with('success', "Sipariş başarıyla {$targetTable->name} masasına birleştirildi!");

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('mergeOrder hatası', ['error' => $e->getMessage()]);
            return back()->with('error', 'Masa birleştirme işlemi sırasında bir hata oluştu: ' . $e->getMessage());
        }
    }

    // Kısmi ödeme işlemi
    public function processPartialPayment(Request $request, CafeOrder $order)
    {
        if ($order->is_paid) {
            return back()->with('error', 'Bu sipariş zaten ödenmiş!');
        }

        if ($order->status === CafeOrder::STATUS_CANCELLED) {
            return back()->with('error', 'İptal edilmiş siparişe ödeme yapılamaz!');
        }

        $paymentData = json_decode($request->payment_data, true);
        $selectedItems = json_decode($request->selected_items, true);
        
        if (!$paymentData || empty($paymentData)) {
            return back()->with('error', 'Ödeme bilgileri eksik!');
        }

        // Toplam ödenen tutarı hesapla
        $totalPaid = array_sum(array_column($paymentData, 'amount'));
        
        // Kısmi ödeme kontrolü - toplam tutardan az olabilir
        if ($totalPaid <= 0) {
            return back()->with('error', 'Ödeme tutarı sıfırdan büyük olmalıdır!');
        }

        if ($totalPaid > $order->total_amount) {
            return back()->with('error', 'Ödeme tutarı sipariş tutarından fazla olamaz!');
        }

        // Ödeme yöntemlerini kontrol et
        foreach ($paymentData as $payment) {
            if (!in_array($payment['payment_method'], ['cash', 'card'])) {
                return back()->with('error', 'Geçersiz ödeme yöntemi!');
            }
            if ($payment['amount'] <= 0) {
                return back()->with('error', 'Ödeme tutarı sıfırdan büyük olmalıdır!');
            }
        }

        DB::beginTransaction();

        try {
            // Mevcut ödemeleri kontrol et
            $existingPayments = $order->cafeOrderPayments()->sum('amount');
            $newTotalPaid = $existingPayments + $totalPaid;

            // Toplam ödeme tutarını kontrol et
            if ($newTotalPaid > $order->total_amount) {
                return back()->with('error', 'Toplam ödeme tutarı sipariş tutarını aşamaz!');
            }

            // Her ödeme için kayıt oluştur
            foreach ($paymentData as $payment) {
                CafeOrderPayment::create([
                    'cafe_order_id' => $order->id,
                    'amount' => $payment['amount'],
                    'payment_method' => $payment['payment_method'],
                    'description' => $payment['description'] ?? 'Kısmi ödeme',
                    'selected_items' => $selectedItems
                ]);
            }

            // Eğer tam ödeme tamamlandıysa siparişi kapat
            if (abs($newTotalPaid - $order->total_amount) < 0.01) {
                $order->update([
                    'payment_method' => CafeOrder::PAYMENT_SPLIT,
                    'is_paid' => true,
                    'paid_at' => now()
                ]);

                // Masanın başka ödenmemiş siparişi var mı kontrol et
                $unpaidOrdersCount = CafeOrder::where('table_id', $order->table_id)
                    ->where('is_paid', false)
                    ->whereNotIn('status', [CafeOrder::STATUS_CANCELLED])
                    ->where('id', '!=', $order->id)
                    ->count();

                // Başka ödenmemiş sipariş yoksa masayı boşalt
                if ($unpaidOrdersCount === 0) {
                    $order->table->update([
                        'status' => Table::STATUS_AVAILABLE,
                        'occupied_at' => null
                    ]);

                    CafeOrderLog::create([
                        'cafe_order_id' => $order->id,
                        'user_id' => Auth::id(),
                        'action' => 'table_status_changed',
                        'message' => "Son kısmi ödeme sonrası masa durumu 'Dolu' -> 'Müsait' olarak değişti."
                    ]);
                }

                DB::commit();
                return redirect()->route('cafe.order.show', $order)->with('success', 'Ödeme başarıyla tamamlandı! Masa boşaltıldı.');
            } else {
                // Kısmi ödeme - sipariş açık kalır, masa dolu kalır
                $order->update([
                    'payment_method' => CafeOrder::PAYMENT_SPLIT
                ]);

                $remainingAmount = $order->total_amount - $newTotalPaid;
                $message = "Kısmi ödeme alındı! Kalan tutar: " . number_format($remainingAmount, 2) . " ₺";
                
                DB::commit();
                return redirect()->route('cafe.order.split-payment', $order)->with('success', $message);
            }

            return redirect()->route('cafe.order.split-payment', $order)->with('success', $message);

        } catch (\Exception $e) {
            DB::rollback();
            \Log::error('Partial Payment Error:', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return back()->with('error', 'Ödeme işlemi sırasında bir hata oluştu: ' . $e->getMessage());
        }
    }
    
    // Hesabı kapat (kısmi ödeme ile)
    public function closeOrder(Request $request, CafeOrder $order)
    {
        if ($order->is_paid) {
            return back()->with('error', 'Bu sipariş zaten ödenmiş!');
        }

        if ($order->status === CafeOrder::STATUS_CANCELLED) {
            return back()->with('error', 'İptal edilmiş sipariş kapatılamaz!');
        }

        // Ödenen tutar varsa kısmi ödeme olarak işaretle
        $totalPaid = $order->cafeOrderPayments()->sum('amount');
        
        DB::beginTransaction();

        try {
            if ($totalPaid > 0) {
                // Kısmi ödeme yapılmış, hesabı kapat
                $order->update([
                    'payment_method' => CafeOrder::PAYMENT_SPLIT,
                    'is_paid' => true,
                    'paid_at' => now()
                ]);
            } else {
                // Hiç ödeme yapılmamış, siparişi iptal et
                $order->update([
                    'status' => CafeOrder::STATUS_CANCELLED
                ]);
            }

            // Masanın başka ödenmemiş siparişi var mı kontrol et
            $unpaidOrdersCount = CafeOrder::where('table_id', $order->table_id)
                ->where('is_paid', false)
                ->whereNotIn('status', [CafeOrder::STATUS_CANCELLED])
                ->where('id', '!=', $order->id)
                ->count();

            // Başka ödenmemiş sipariş yoksa masayı boşalt
            if ($unpaidOrdersCount === 0) {
                $order->table->update([
                    'status' => Table::STATUS_AVAILABLE,
                    'occupied_at' => null
                ]);
            }

            DB::commit();

            $remainingAmount = $order->total_amount - $totalPaid;
            if ($totalPaid > 0) {
                $message = "Hesap kapatıldı! Ödenen: " . number_format($totalPaid, 2) . " ₺, Kalan: " . number_format($remainingAmount, 2) . " ₺";
            } else {
                $message = "Sipariş iptal edildi ve masa boşaltıldı.";
            }

            return redirect()->route('cafe.orders')->with('success', $message);

        } catch (\Exception $e) {
            DB::rollback();
            \Log::error('Close Order Error:', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return back()->with('error', 'Hesap kapatılırken bir hata oluştu: ' . $e->getMessage());
        }
    }
    
    private function getPaidQuantities(CafeOrder $order)
    {
        $paidQuantities = [];
        
        foreach ($order->cafeOrderPayments as $payment) {
            if (!empty($payment->selected_items)) {
                foreach ($payment->selected_items as $item) {
                    $itemId = $item['id'];
                    $qty = $item['quantity'];
                    
                    if (!isset($paidQuantities[$itemId])) {
                        $paidQuantities[$itemId] = 0;
                    }
                    $paidQuantities[$itemId] += $qty;
                }
            }
        }
        
        return $paidQuantities;
    }

    public function removeOrderItem(Request $request, CafeOrderItem $item)
    {
        $order = $item->cafeOrder;
        
        if ($order->status === CafeOrder::STATUS_CANCELLED) {
            return back()->with('error', 'İptal edilen siparişten ürün silinemez!');
        }
        
        // Ödeme kontrolü (Tam ödeme)
        if ($order->is_paid) {
            return back()->with('error', 'Ödeme alınmış siparişten ürün silinemez!');
        }

        // Kısmi ödeme kontrolü
        $paidQuantities = $this->getPaidQuantities($order);
        $paidQty = $paidQuantities[$item->id] ?? 0;
        
        // Silinmek istenen miktar
        $removeQty = (int)($request->input('remove_qty', 1));
        if ($removeQty < 1) {
            $removeQty = 1;
        }

        // Silinebilir maksimum miktar (Toplam - Ödenen)
        $maxRemovable = $item->quantity - $paidQty;

        if ($maxRemovable <= 0) {
            return back()->with('error', 'Bu ürünün tamamı ödenmiş, silinemez!');
        }

        if ($removeQty > $maxRemovable) {
            return back()->with('error', "Bu üründen en fazla {$maxRemovable} adet silebilirsiniz (Geri kalanı ödendi)!");
        }
        
        DB::beginTransaction();
        
        try {
            $unitPrice = $item->unit_price;
            $productName = $item->product->name;

            // Kısmi silme mi, tam silme mi?
            if ($removeQty >= $item->quantity) {
                // Burada bir mantık hatası olmaması için tekrar kontrol:
                // Eğer removeQty >= item->quantity ise ve maxRemovable < item->quantity ise,
                // yukarıdaki if($removeQty > $maxRemovable) bloğuna girerdi.
                // Yani buraya geldiysek ve tam silme yapıyorsak, paidQty 0 demektir.
                
                // Tam silme
                $itemPrice = $item->total_price;
                $quantity = $item->quantity;

                // Ürünü sil
                $item->delete();
                
                // Sipariş toplam tutarını güncelle
                $order->update([
                    'total_amount' => $order->total_amount - $itemPrice
                ]);

                // Siparişte başka ürün kalmadıysa siparişi iptal et
                if ($order->cafeOrderItems()->count() === 0) {
                    $order->update(['status' => CafeOrder::STATUS_CANCELLED]);
                    
                    // Masayı boşalt
                    $order->table->update([
                        'status' => Table::STATUS_AVAILABLE,
                        'occupied_at' => null
                    ]);

                    CafeOrderLog::create([
                        'cafe_order_id' => $order->id,
                        'user_id' => Auth::id(),
                        'action' => 'order_cancelled',
                        'message' => 'Tüm ürünler silindiği için sipariş iptal edildi.'
                    ]);
                } else {
                    // Sipariş durumunu kalan ürünlere göre güncelle
                    $this->updateOrderStatusBasedOnItems($order);
                }

                CafeOrderLog::create([
                    'cafe_order_id' => $order->id,
                    'user_id' => Auth::id(),
                    'action' => 'item_removed',
                    'message' => "{$quantity} adet {$productName} silindi."
                ]);

                DB::commit();

                return back()->with('success', "{$quantity}x {$productName} ürünü başarıyla silindi! (" . number_format($itemPrice, 2) . " ₺)");
            } else {
                // Kısmi silme: miktarı düşür
                $decreaseAmount = $removeQty;
                $decreaseTotal = $unitPrice * $decreaseAmount;

                $item->quantity = $item->quantity - $decreaseAmount;
                $item->total_price = $item->quantity * $unitPrice;
                $item->save();

                // Sipariş toplam tutarını güncelle
                $order->update([
                    'total_amount' => $order->total_amount - $decreaseTotal
                ]);

                // Sipariş durumunu kalan ürünlere göre güncelle
                $this->updateOrderStatusBasedOnItems($order);

                CafeOrderLog::create([
                    'cafe_order_id' => $order->id,
                    'user_id' => Auth::id(),
                    'action' => 'item_removed',
                    'message' => "{$decreaseAmount} adet {$productName} silindi."
                ]);

                DB::commit();

                return back()->with('success', "{$productName} ürününden {$decreaseAmount} adet silindi! (" . number_format($decreaseTotal, 2) . " ₺)");
            }
            
        } catch (\Exception $e) {
            DB::rollback();
            \Log::error('removeOrderItem hatası', ['error' => $e->getMessage()]);
            return back()->with('error', 'Ürün silme işlemi sırasında hata oluştu: ' . $e->getMessage());
        }
    }
    
    /**
     * Manuel fiş yazdırma (sipariş detay sayfasından)
     */
    public function printReceipt($orderId)
    {
        $order = CafeOrder::with(['table', 'user', 'cafeOrderItems.product'])->findOrFail($orderId);
        
        // Manuel yazdırma - ayar kontrolü yapmadan direkt yazdır
        $this->manualPrintReceipt($order);
        
        return back()->with('success', 'Fiş yazıcıya gönderildi!');
    }

    /**
     * Manuel fiş yazdırma metodu (ayar kontrolü olmadan)
     */
    private function manualPrintReceipt($order, $customContent = null)
    {
        try {
            // Kompakt fiş içeriği oluştur
            $receiptContent = $customContent ?? $this->generateCompactReceipt($order);

            // Tarayıcıda gör/önizleme: session'a HTML içerik ve URL yaz
            $previewKey = 'receipt_preview_' . $order->id . '_' . time();
            session([$previewKey => $this->generateReceiptHtml($receiptContent, $order)]);
            session()->flash('manual_print_url', route('cafe.show-receipt', ['key' => $previewKey]));
            
            // Windows ortamı kontrolü
            $this->printWithBrowser($order, $receiptContent);
            
        } catch (\Exception $e) {
            \Log::error('Manuel fiş yazdırma hatası', [
                'order_id' => $order->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Windows'ta Notepad ile fiş yazdırma
     */
    private function printWithNotepad($order, $receiptContent)
    {
        // Geçici metin dosyası oluştur
        $tempFile = storage_path('app/temp_receipt_' . $order->id . '.txt');
        file_put_contents($tempFile, $receiptContent);
        
        // Notepad yazdırma ayarlarını düzenle (kenar boşluklarını sıfırla)
        $regScript = "
            # Notepad yazdırma ayarlarını düzenle
            reg add \"HKCU\\Software\\Microsoft\\Notepad\" /v fWrap /t REG_DWORD /d 0 /f
            reg add \"HKCU\\Software\\Microsoft\\Notepad\" /v iMarginLeft /t REG_DWORD /d 0 /f
            reg add \"HKCU\\Software\\Microsoft\\Notepad\" /v iMarginRight /t REG_DWORD /d 0 /f
            reg add \"HKCU\\Software\\Microsoft\\Notepad\" /v iMarginTop /t REG_DWORD /d 0 /f
            reg add \"HKCU\\Software\\Microsoft\\Notepad\" /v iMarginBottom /t REG_DWORD /d 0 /f
        ";
        
        $regFile = storage_path('app/notepad_settings_' . $order->id . '.ps1');
        file_put_contents($regFile, $regScript);
        
        // Registry ayarlarını uygula
        exec("powershell.exe -ExecutionPolicy Bypass -File \"{$regFile}\"");
        
        // Notepad ile aç ve yazdır
        $command = "notepad /p \"{$tempFile}\"";
        pclose(popen("start /B " . $command, "r"));
        
        // 5 saniye sonra dosyaları sil
        sleep(5);
        if (file_exists($tempFile)) {
            unlink($tempFile);
        }
        if (file_exists($regFile)) {
            unlink($regFile);
        }
    }

    /**
     * Sunucuda tarayıcı ile fiş yazdırma - Basit tarayıcı yazdırma
     */
    private function printWithBrowser($order, $receiptContent)
    {
        try {
            // POP-UP KULLANMAYAN YAZDIRMA - iframe ile
            $printScript = "
                console.log('Pop-up kullanmayan fiş yazdırma başlatılıyor...');
                
                // Fiş içeriğini hazırla
                var receiptContent = `" . str_replace(["\r\n", "\n", "\r"], "\\n", addslashes($receiptContent)) . "`;
                
                // Gizli iframe oluştur
                var printFrame = document.createElement('iframe');
                printFrame.style.position = 'absolute';
                printFrame.style.top = '-1000px';
                printFrame.style.left = '-1000px';
                printFrame.style.width = '1px';
                printFrame.style.height = '1px';
                printFrame.style.border = 'none';
                document.body.appendChild(printFrame);
                
                // iframe içeriğini hazırla
                var frameDoc = printFrame.contentWindow.document;
                frameDoc.open();
                
                var htmlContent = '<html><head><meta charset=\"utf-8\"><style>';
                htmlContent += '@page { size: 80mm auto; margin: 0 !important; padding: 0 !important; }';
                htmlContent += '* { margin: 0 !important; padding: 0 !important; box-sizing: border-box !important; }';
                htmlContent += 'html, body { width: 80mm !important; margin: 0 !important; padding: 0 !important; }';
                htmlContent += 'body { font-family: \"Courier New\", monospace !important; font-size: 16px !important; }';
                htmlContent += 'pre { margin: 0 !important; padding: 0 !important; white-space: pre-wrap !important; }';
                htmlContent += 'pre { width: 80mm !important; overflow: hidden !important; }';
                htmlContent += '@media print { ';
                htmlContent += '@page { margin: 0 !important; padding: 0 !important; size: 80mm auto !important; }';
                htmlContent += 'html, body { margin: 0 !important; padding: 0 !important; width: 80mm !important; }';
                htmlContent += 'pre { margin: 0 !important; padding: 0 !important; width: 80mm !important; }';
                htmlContent += '}';
                htmlContent += '</style></head><body>';
                htmlContent += '<pre>' + receiptContent + '</pre>';
                htmlContent += '</body></html>';
                
                frameDoc.write(htmlContent);
                frameDoc.close();
                
                // iframe yüklendikten sonra yazdır
                printFrame.onload = function() {
                    setTimeout(function() {
                        try {
                            printFrame.contentWindow.focus();
                            printFrame.contentWindow.print();
                            
                            // Yazdırma tamamlandıktan sonra iframe'i sil
                            setTimeout(function() {
                                document.body.removeChild(printFrame);
                            }, 2000);
                            
                            console.log('Fiş başarıyla yazdırıldı (iframe ile)');
                        } catch(e) {
                            console.log('Yazdırma hatası:', e);
                            document.body.removeChild(printFrame);
                        }
                    }, 500);
                };
            ";
            
            // JavaScript kodunu session'a kaydet
            session(['qz_direct_print' => $printScript]);
            
            \Log::info('Pop-up kullanmayan fiş yazdırma scripti hazırlandı', [
                'order_id' => $order->id,
                'content_length' => strlen($receiptContent)
            ]);
            
        } catch (\Exception $e) {
            \Log::error('printWithBrowser hatası', [
                'order_id' => $order->id,
                'error' => $e->getMessage()
            ]);
            
            session()->flash('error', 'Fiş hazırlanırken hata oluştu: ' . $e->getMessage());
        }
    }
    
    /**
     * QZ Tray ile direkt yazıcı yazdırma
     */
    private function printWithQZTray($order, $receiptContent)
    {
        try {
            // Fiş içeriğini session'da sakla
            $receiptKey = 'qz_receipt_' . $order->id . '_' . time();
            session([$receiptKey => $receiptContent]);
            
            // QZ Tray JavaScript kodunu session'a kaydet
            session(['qz_print_script' => "
                // QZ Tray bağlantısı
                qz.websocket.connect().then(function() {
                    console.log('QZ Tray bağlandı');
                    
                    // Yazıcı seç (varsayılan yazıcı)
                    return qz.printers.getDefault();
                }).then(function(printer) {
                    console.log('Yazıcı seçildi: ' + printer);
                    
                    // Yazdırma konfigürasyonu
                    var config = qz.configs.create(printer);
                    
                    // Fiş içeriğini al
                    var receiptData = '" . addslashes($receiptContent) . "';
                    
                    // Yazdırma verisi hazırla
                    var data = [{
                        type: 'raw',
                        format: 'plain',
                        data: receiptData
                    }];
                    
                    // Yazdır
                    return qz.print(config, data);
                }).then(function() {
                    console.log('Fiş başarıyla yazdırıldı!');
                    alert('Fiş yazıcıya gönderildi!');
                }).catch(function(error) {
                    console.error('QZ Tray hatası:', error);
                    alert('Yazdırma hatası: ' + error.message);
                });
            "]);
            
            \Log::info('QZ Tray ile fiş hazırlandı', [
                'order_id' => $order->id
            ]);
            
        } catch (\Exception $e) {
            \Log::error('QZ Tray hatası', [
                'order_id' => $order->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * HTML fiş içeriği oluştur
     */
    private function generateReceiptHtml($receiptContent, $order)
    {
        // Metin içeriğini HTML'e çevir
        $htmlContent = nl2br(htmlspecialchars($receiptContent));
        
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='utf-8'>
            <title>Fiş - {$order->order_number}</title>
            <style>
                body { 
                    font-family: 'Courier New', monospace; 
                    font-size: 12px; 
                    margin: 0; 
                    padding: 10px;
                    width: 200px;
                }
                @media print {
                    body { margin: 0; padding: 5px; }
                    @page { margin: 0; size: 58mm auto; }
                }
            </style>
        </head>
        <body>
            {$htmlContent}
            <script>
                window.onload = function() {
                    try { window.print(); } catch(e){}
                };
            </script>
        </body>
        </html>";
    }
    
    /**
     * Direkt yazdırma için HTML içeriği oluştur
     */
    private function generateDirectPrintHtml($receiptContent, $order)
    {
        // Metin içeriğini HTML'e çevir
        $htmlContent = nl2br(htmlspecialchars($receiptContent));
        
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='utf-8'>
            <title>Fiş - {$order->order_number}</title>
            <style>
                body { 
                    font-family: 'Courier New', monospace; 
                    font-size: 12px; 
                    margin: 0; 
                    padding: 10px;
                    background: white;
                    color: black;
                }
                @media print {
                    body { 
                        margin: 0; 
                        padding: 5px; 
                        font-size: 11px;
                    }
                    @page { 
                        margin: 0.2cm; 
                        size: 58mm auto;
                    }
                }
            </style>
        </head>
        <body>
            <div style='max-width: 200px;'>
                {$htmlContent}
            </div>
        </body>
        </html>";
    }
    
    public function removeServedGroup(Request $request, CafeOrder $order)
    {
        // İptal edilmiş siparişte silme yok
        if ($order->status === CafeOrder::STATUS_CANCELLED) {
            return back()->with('error', 'İptal edilen siparişten ürün silinemez!');
        }
        
        // Ödenmiş siparişte silme yok
        if ($order->is_paid) {
            return back()->with('error', 'Ödeme alınmış siparişten ürün silinemez!');
        }

        $productId = (int)$request->input('product_id');
        $unitPrice = (float)$request->input('unit_price');

        DB::beginTransaction();
        try {
            // Aynı ürün ve birim fiyata sahip SERVED kalemler
            $items = $order->cafeOrderItems()
                ->where('status', 'served')
                ->where('product_id', $productId)
                ->where('unit_price', $unitPrice)
                ->get();

            if ($items->isEmpty()) {
                DB::commit();
                return back()->with('info', 'Silinecek servis edilmiş ürün bulunamadı.');
            }

            // Ödenen miktarları al
            $paidQuantities = $this->getPaidQuantities($order);
            
            // Toplam silinecek tutar ve adet
            $totalRemove = 0;
            $quantitySum = 0;
            $keptCount = 0;

            foreach ($items as $it) {
                $paidQty = $paidQuantities[$it->id] ?? 0;
                $removableQty = $it->quantity - $paidQty;

                if ($removableQty > 0) {
                    // Eğer kısmen ödendiyse, sadece ödenmemiş kısmı sil
                    if ($paidQty > 0) {
                        // Kısmi silme (Miktarı düşür)
                        $decreaseAmount = $removableQty;
                        $decreaseTotal = $it->unit_price * $decreaseAmount;

                        $it->quantity = $paidQty; // Sadece ödenen miktar kalsın
                        $it->total_price = $it->quantity * $it->unit_price;
                        $it->save();

                        $totalRemove += $decreaseTotal;
                        $quantitySum += $decreaseAmount;
                    } else {
                        // Hiç ödenmemiş, tamamen sil
                        $totalRemove += $it->total_price;
                        $quantitySum += $it->quantity;
                        $it->delete();
                    }
                } else {
                    // Tamamı ödenmiş, dokunma
                    $keptCount += $it->quantity;
                }
            }

            if ($quantitySum === 0) {
                DB::rollback(); // Değişiklik yapma
                return back()->with('error', 'Bu gruptaki tüm ürünlerin ödemesi alınmış, silinemez!');
            }

            // Sipariş toplamını güncelle
            $order->update([
                'total_amount' => $order->total_amount - $totalRemove
            ]);

            // Kalan kalem yoksa siparişi iptal edip masayı boşa çıkar
            if ($order->cafeOrderItems()->count() === 0) {
                $order->update(['status' => CafeOrder::STATUS_CANCELLED]);
                $order->table->update([
                    'status' => Table::STATUS_AVAILABLE,
                    'occupied_at' => null
                ]);

                CafeOrderLog::create([
                    'cafe_order_id' => $order->id,
                    'user_id' => Auth::id(),
                    'action' => 'order_cancelled',
                    'message' => 'Tüm ürünler silindiği için sipariş iptal edildi.'
                ]);
            } else {
                // Kalan kalemlere göre sipariş durumunu güncelle
                $this->updateOrderStatusBasedOnItems($order);
            }

            $productName = optional($items->first()->product)->name ?? 'Ürün';
            
            $logMessage = "{$quantitySum} adet {$productName} (servis edildi grubu) silindi.";
            if ($keptCount > 0) {
                $logMessage .= " ({$keptCount} adet ödenmiş olduğu için silinmedi)";
            }

            CafeOrderLog::create([
                'cafe_order_id' => $order->id,
                'user_id' => Auth::id(),
                'action' => 'item_removed',
                'message' => $logMessage
            ]);

            DB::commit();

            $successMsg = "{$quantitySum}x {$productName} (servis edildi grubu) silindi! (" . number_format($totalRemove, 2) . " ₺)";
            if ($keptCount > 0) {
                $successMsg .= " <br><small class='text-muted'>({$keptCount} adet ödenmiş olduğu için silinmedi)</small>";
            }

            return back()->with('success', $successMsg);
        } catch (\Exception $e) {
            DB::rollback();
            \Log::error('removeServedGroup hatası', [
                'order_id' => $order->id,
                'product_id' => $productId,
                'unit_price' => $unitPrice,
                'error' => $e->getMessage()
            ]);
            return back()->with('error', 'Servis edilen grup silinirken hata oluştu: ' . $e->getMessage());
        }
    }
    
    /**
     * Basit HTML fiş içeriği oluştur - otomatik yazdırma olmadan
     */
    private function generateSimpleReceiptHtml($receiptContent, $order)
    {
        // Metin içeriğini HTML'e çevir
        $htmlContent = nl2br(htmlspecialchars($receiptContent));
        
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='utf-8'>
            <title>Fiş - {$order->order_number}</title>
            <style>
                body { 
                    font-family: 'Courier New', monospace; 
                    font-size: 14px; 
                    margin: 20px; 
                    padding: 20px;
                    background: white;
                }
                .print-btn {
                    position: fixed;
                    top: 10px;
                    right: 10px;
                    background: #28a745;
                    color: white;
                    border: none;
                    padding: 10px 20px;
                    border-radius: 5px;
                    cursor: pointer;
                    font-size: 16px;
                    z-index: 1000;
                }
                .print-btn:hover {
                    background: #218838;
                }
                @media print {
                    .print-btn { display: none; }
                    body { margin: 0; padding: 10px; }
                    @page { margin: 0.5cm; }
                }
            </style>
        </head>
        <body>
            <button class='print-btn' onclick='window.print()'>🖨️ YAZDIR</button>
            <div style='max-width: 300px;'>
                {$htmlContent}
            </div>
            <br><br>
            <div style='text-align: center; color: #666; font-size: 12px;'>
                Yazdırmak için yukarıdaki yeşil butona tıklayın<br>
                veya Ctrl+P tuşlarını kullanın
            </div>
        </body>
        </html>";
    }

    /**
     * Dosya silme işlemini zamanla
     */
    private function scheduleFileCleanup($filePath, $delaySeconds = 30)
    {
        // Basit bir cleanup sistemi - gerçek projede queue kullanılabilir
        ignore_user_abort(true);
        
        register_shutdown_function(function() use ($filePath, $delaySeconds) {
            sleep($delaySeconds);
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        });
    }

    /**
     * Sipariş fişi otomatik yazdırma - Notepad ile (kenar boşluksuz)
     */
    private function printOrderReceipt($order)
    {
        try {
            // Otomatik yazdırma ayarını kontrol et
            $autoPrintEnabled = Setting::getValue('auto_print_receipt', '1');
            
            // Ayar kapalıysa işlem yapma
            if ($autoPrintEnabled !== '1') {
                \Log::info('Otomatik fiş yazdırma devre dışı');
                return;
            }
            
            // Manuel yazdırma metodunu kullan
            $this->manualPrintReceipt($order);
            
        } catch (\Exception $e) {
            \Log::error('Otomatik fiş yazdırma hatası', [
                'order_id' => $order->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Ek sipariş fişi oluştur
     */
    private function generateExtrasReceipt($order, array $items, ?string $orderNote = null)
    {
        $order->load(['table', 'user', 'cafeOrderNotes', 'cafeOrderExtras']);
        $content = "";
        $content .= "EK SIPARIS FISI\n";
        $content .= str_repeat("=", 32) . "\n";
        $content .= "No: " . $order->id . "\n";
        $content .= "Masa: " . ($order->table->name ?? 'Paket') . "\n";
        $content .= "Tarih: " . now()->format('d.m.Y H:i') . "\n";
        $content .= "Garson: " . $order->user->name . "\n";
        $content .= str_repeat("-", 32) . "\n";
        
        foreach ($items as $item) {
            $productName = strlen($item['product']->name) > 28
                ? substr($item['product']->name, 0, 25) . "..."
                : $item['product']->name;
            $content .= $productName . "\n";
            
            $qtyPrice = $item['quantity'] . "x" . number_format($item['unit_price'], 2);
            $total = number_format($item['total_price'], 2) . "TL";
            $spaces = 32 - strlen($qtyPrice) - strlen($total);
            $content .= $qtyPrice . str_repeat(" ", max(1, $spaces)) . $total . "\n";
            
            if (!empty($item['notes'])) {
                $notes = strlen($item['notes']) > 28
                    ? substr($item['notes'], 0, 25) . "..."
                    : $item['notes'];
                $content .= "Not: " . $notes . "\n";
            }
        }
        
        if ($orderNote) {
            $noteText = strlen($orderNote) > 28
                ? substr($orderNote, 0, 25) . "..."
                : $orderNote;
            $content .= "Not: " . $noteText . "\n";
        }
        
        $content .= str_repeat("-", 32) . "\n";
        $extrasTotal = array_sum(array_map(fn($i) => $i['total_price'], $items));
        $subtotalText = "EKSTRA TOPLAM: " . number_format($extrasTotal, 2) . "TL";
        $spaces = 32 - strlen($subtotalText);
        $content .= str_repeat(" ", max(0, $spaces)) . $subtotalText . "\n";
        
        $content .= str_repeat("=", 32) . "\n";
        $content .= "Afiyet Olsun!\n";
        $content .= now()->format('d.m.Y H:i:s') . "\n";
        
        return $content;
    }

    /**
     * Kompakt fiş içeriği oluştur (kenar boşluksuz)
     */
    private function generateCompactReceipt($order)
    {
        $order->load(['table', 'user', 'cafeOrderItems.product', 'cafeOrderExtras', 'cafeOrderPayments']);
        
        $content = "";
        $content .= "KAFE SIPARIS FISI\n";
        $content .= str_repeat("=", 32) . "\n";
        $content .= "No: " . $order->id . "\n";
        $content .= "Masa: " . ($order->table->name ?? 'Paket') . "\n";
        $content .= "Tarih: " . $order->created_at->format('d.m.Y H:i') . "\n";
        $content .= "Garson: " . $order->user->name . "\n";
        $content .= str_repeat("-", 32) . "\n";
        
        foreach ($order->cafeOrderItems as $item) {
            // Ürün adını kısalt
            $productName = strlen($item->product->name) > 28 ? 
                substr($item->product->name, 0, 25) . "..." : 
                $item->product->name;
            $content .= $productName . "\n";
            
            // Miktar ve fiyat bilgisi
            $qtyPrice = $item->quantity . "x" . number_format($item->unit_price, 2);
            $total = number_format($item->total_price, 2) . "TL";
            $spaces = 32 - strlen($qtyPrice) - strlen($total);
            $content .= $qtyPrice . str_repeat(" ", max(1, $spaces)) . $total . "\n";
            
            if ($item->notes) {
                $notes = strlen($item->notes) > 28 ? 
                    substr($item->notes, 0, 25) . "..." : 
                    $item->notes;
                $content .= "Not: " . $notes . "\n";
            }
        }
        
        // Ekstra fiyatları ekle
    if ($order->cafeOrderExtras && $order->cafeOrderExtras->count() > 0) {
        foreach ($order->cafeOrderExtras as $extra) {
            // Ekstra açıklamasını kısalt
            $extraDesc = $extra->description ?: 'Ekstra Ucret';
            if (strlen($extraDesc) > 28) {
                $extraDesc = substr($extraDesc, 0, 25) . "...";
            }
            $content .= $extraDesc . "\n";
            
            // Ekstra fiyat bilgisi
            $extraPrice = "1x" . number_format($extra->amount, 2);
            $extraTotal = number_format($extra->amount, 2) . "TL";
            $spaces = 32 - strlen($extraPrice) - strlen($extraTotal);
            $content .= $extraPrice . str_repeat(" ", max(1, $spaces)) . $extraTotal . "\n";
        }
    }
    
    // Sipariş düzeyindeki notlar (CafeOrderNote veya eski $order->notes)
    if ($order->cafeOrderNotes && $order->cafeOrderNotes->count() > 0) {
        foreach ($order->cafeOrderNotes as $note) {
            $noteText = strlen($note->note) > 28
                ? substr($note->note, 0, 25) . "..."
                : $note->note;
            $content .= "Not: " . $noteText . "\n";
        }
    } elseif ($order->notes) {
        $noteText = strlen($order->notes) > 28
            ? substr($order->notes, 0, 25) . "..."
            : $order->notes;
        $content .= "Not: " . $noteText . "\n";
    }
    
    $content .= str_repeat("-", 32) . "\n";
        
        // Ara toplam (indirim öncesi)
        $subtotalText = "ARA TOPLAM: " . number_format($order->total_amount, 2) . "TL";
        $spaces = 32 - strlen($subtotalText);
        $content .= str_repeat(" ", max(0, $spaces)) . $subtotalText . "\n";
        
        // İndirim bilgisi (varsa)
        if ($order->discount_percentage && $order->discount_percentage > 0) {
            $discountText = "INDIRIM %" . $order->discount_percentage . ": -" . number_format($order->discount_amount, 2) . "TL";
            $spaces = 32 - strlen($discountText);
            $content .= str_repeat(" ", max(0, $spaces)) . $discountText . "\n";
        }
        
        // Net toplam
        $finalAmount = $order->final_amount ?: $order->total_amount;
        $totalText = "NET TOPLAM: " . number_format($finalAmount, 2) . "TL";
        $spaces = 32 - strlen($totalText);
        $content .= str_repeat(" ", max(0, $spaces)) . $totalText . "\n";
        
        $content .= str_repeat("=", 32) . "\n";
        
        // Ödeme bilgisi (sipariş ödenmiş ise)
        if ($order->is_paid) {
            $content .= str_repeat("-", 32) . "\n";
            $content .= "ODEME ALINDI\n";
            
            // Ödeme yöntemlerini göster
            if ($order->cafeOrderPayments && $order->cafeOrderPayments->count() > 0) {
                $totalPaid = $order->cafeOrderPayments->sum('amount');
                
                foreach ($order->cafeOrderPayments as $payment) {
                    $paymentMethod = $payment->payment_method === 'cash' ? 'Nakit' : 'Kredi Karti';
                    $paymentText = $paymentMethod . ": " . number_format($payment->amount, 2) . "TL";
                    $content .= $paymentText . "\n";
                }
                
                // Hesap kapatma durumu kontrolü
                if ($totalPaid < $order->total_amount) {
                    $remainingAmount = $order->total_amount - $totalPaid;
                    $content .= str_repeat("-", 32) . "\n";
                    $content .= "HESAP KAPATILDI\n";
                    $content .= "Odenen: " . number_format($totalPaid, 2) . "TL\n";
                    $content .= "Kapatilan: " . number_format($remainingAmount, 2) . "TL\n";
                }
            } else {
                // Eski sistem için
                $paymentMethod = $order->payment_method === 'cash' ? 'Nakit' : 'Kredi Karti';
                $paymentText = $paymentMethod . ": " . number_format($finalAmount, 2) . "TL";
                $content .= $paymentText . "\n";
            }
            
            $content .= "Odeme Tarihi: " . $order->paid_at->format('d.m.Y H:i') . "\n";
            $content .= str_repeat("-", 32) . "\n";
        }
        
        $content .= "Afiyet Olsun!\n";
        $content .= now()->format('d.m.Y H:i:s') . "\n";
        
        return $content;
    }

    public function searchProduct(Request $request)
    {
        $query = $request->input('query');
        
        if (empty($query) || strlen($query) < 2) {
            return response()->json([]);
        }

        // �r�nleri ara
        $products = Product::where('is_active', true)
            ->where('name', 'LIKE', '%' . $query . '%')
            ->limit(10)
            ->get();

        $results = [];

        foreach ($products as $product) {
            // Bu �r�n�n hangi masalarda sipari� edildi�ini bul (�denmemi� sipari�ler)
            $orderItems = CafeOrderItem::where('product_id', $product->id)
                ->whereHas('cafeOrder', function($q) {
                    $q->where('is_paid', false)
                      ->whereNotIn('status', [CafeOrder::STATUS_CANCELLED]);
                })
                ->with(['cafeOrder.table'])
                ->get();

            $tables = [];
            foreach ($orderItems as $item) {
                if ($item->cafeOrder && $item->cafeOrder->table) {
                    $table = $item->cafeOrder->table;
                    $tables[] = [
                        'id' => $table->id,
                        'name' => $table->name,
                        'order_id' => $item->cafeOrder->id,
                        'quantity' => $item->quantity,
                        'status' => $item->status_text,
                        'status_color' => $item->status_color
                    ];
                }
            }

            $results[] = [
                'id' => $product->id,
                'name' => $product->name,
                'price' => number_format($product->sale_price, 2),
                'tables' => $tables
            ];
        }

        return response()->json($results);
    }
}