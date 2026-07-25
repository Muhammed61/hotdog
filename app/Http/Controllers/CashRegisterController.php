<?php

namespace App\Http\Controllers;

use App\Models\CashTransaction;
use App\Models\Sale;
use App\Models\CafeOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CashRegisterController extends Controller
{
    public function index(Request $request)
    {
        // Tarih filtresi parametreleri
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');
        $startTime = $request->get('start_time', '00:00');
        $endTime = $request->get('end_time', '23:59');

        // Varsayılan tarih aralığı (bugün)
        if (!$startDate) {
            $startDate = now()->format('Y-m-d');
        }
        if (!$endDate) {
            $endDate = now()->format('Y-m-d');
        }

        // Tam tarih-saat oluştur
        $startDateTime = $startDate . ' ' . $startTime . ':00';
        $endDateTime = $endDate . ' ' . $endTime . ':59';

        // Stok kasası - tarih filtreli nakit satışlar
        $existingStockCash = Sale::where('payment_method', 'cash')
            ->whereBetween('created_at', [$startDateTime, $endDateTime])
            ->sum('total_amount');
        
        // Kafe kasası - HEM normal HEM split payment'ları hesapla (tarih filtreli)
        // Split payment'ı olan sipariş ID'lerini al
        $splitOrderIds = DB::table('cafe_order_payments')
            ->join('cafe_orders', 'cafe_order_payments.cafe_order_id', '=', 'cafe_orders.id')
            ->where('cafe_orders.status', '!=', CafeOrder::STATUS_CANCELLED)
            ->where('cafe_orders.is_paid', true)
            ->whereBetween('cafe_orders.created_at', [$startDateTime, $endDateTime])
            ->pluck('cafe_orders.id')
            ->unique();

        // Normal nakit ödemeler - Split payment'ı olanları ÇIKAR (tarih filtreli)
        $normalCafeCash = CafeOrder::where('payment_method', 'cash')
            ->where('is_paid', true)
            ->where('status', '!=', CafeOrder::STATUS_CANCELLED)
            ->whereNotIn('id', $splitOrderIds)
            ->whereBetween('created_at', [$startDateTime, $endDateTime])
            ->get()
            ->sum(function($order) {
                return $order->final_amount > 0 ? $order->final_amount : $order->total_amount;
            });
            
        // Split nakit ödemeler - SADECE NAKIT KISMINI AL (tarih filtreli)
        $splitCafeCash = DB::table('cafe_order_payments')
            ->join('cafe_orders', 'cafe_order_payments.cafe_order_id', '=', 'cafe_orders.id')
            ->where('cafe_orders.status', '!=', CafeOrder::STATUS_CANCELLED)
            ->where('cafe_orders.is_paid', true)
            ->where('cafe_order_payments.payment_method', 'cash')
            ->whereBetween('cafe_orders.created_at', [$startDateTime, $endDateTime])
            ->sum('cafe_order_payments.amount');
            
        // Toplam kafe nakit gelirleri
        $existingCafeCash = $normalCafeCash + $splitCafeCash;

        // Kasa işlemlerinden bakiyeleri hesapla (tarih filtreli)
        $stockTransactionBalance = CashTransaction::where('cash_type', 'stock')
            ->whereBetween('created_at', [$startDateTime, $endDateTime])
            ->selectRaw('SUM(CASE WHEN transaction_type = "income" THEN amount ELSE -amount END) as balance')
            ->value('balance') ?? 0;
            
        $cafeTransactionBalance = CashTransaction::where('cash_type', 'cafe')
            ->whereBetween('created_at', [$startDateTime, $endDateTime])
            ->selectRaw('SUM(CASE WHEN transaction_type = "income" THEN amount ELSE -amount END) as balance')
            ->value('balance') ?? 0;

        // Toplam bakiyeler
        $stockBalance = $existingStockCash + $stockTransactionBalance;
        $cafeBalance = $existingCafeCash + $cafeTransactionBalance;

        // Son işlemler (tarih filtreli)
        $stockTransactions = CashTransaction::with('user')
            ->where('cash_type', 'stock')
            ->whereBetween('created_at', [$startDateTime, $endDateTime])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();
            
        $cafeTransactions = CashTransaction::with('user')
            ->where('cash_type', 'cafe')
            ->whereBetween('created_at', [$startDateTime, $endDateTime])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        // Günlük özet (seçilen tarih aralığı için)
        $todayStats = [
            'stock' => [
                'income' => CashTransaction::where('cash_type', 'stock')
                    ->where('transaction_type', 'income')
                    ->whereBetween('created_at', [$startDateTime, $endDateTime])
                    ->sum('amount'),
                'expense' => CashTransaction::where('cash_type', 'stock')
                    ->whereIn('transaction_type', ['expense', 'withdrawal'])
                    ->whereBetween('created_at', [$startDateTime, $endDateTime])
                    ->sum('amount'),
            ],
            'cafe' => [
                'income' => CashTransaction::where('cash_type', 'cafe')
                    ->where('transaction_type', 'income')
                    ->whereBetween('created_at', [$startDateTime, $endDateTime])
                    ->sum('amount'),
                'expense' => CashTransaction::where('cash_type', 'cafe')
                    ->whereIn('transaction_type', ['expense', 'withdrawal'])
                    ->whereBetween('created_at', [$startDateTime, $endDateTime])
                    ->sum('amount'),
            ]
        ];

        return view('cash-register.index', compact(
            'stockBalance',
            'cafeBalance',
            'stockTransactions',
            'cafeTransactions',
            'todayStats',
            'existingStockCash',
            'existingCafeCash',
            'startDate',
            'endDate',
            'startTime',
            'endTime'
        ));
    }

    public function transactions(Request $request, $cashType)
    {
        // Sayfa başına gösterilecek öğe sayısı (varsayılan: 15)
        $perPage = $request->get('per_page', 15);
        
        // Geçerli per_page değerlerini kontrol et
        $allowedPerPage = [5, 10, 15, 20, 25, 50];
        if (!in_array($perPage, $allowedPerPage)) {
            $perPage = 15;
        }

        // Tarih filtresi parametreleri
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');
        $startTime = $request->get('start_time', '00:00');
        $endTime = $request->get('end_time', '23:59');

        // Varsayılan tarih aralığı (bugün)
        if (!$startDate) {
            $startDate = now()->format('Y-m-d');
        }
        if (!$endDate) {
            $endDate = now()->format('Y-m-d');
        }

        // Tam tarih-saat oluştur
        $startDateTime = $startDate . ' ' . $startTime . ':00';
        $endDateTime = $endDate . ' ' . $endTime . ':59';

        $transactions = CashTransaction::with('user')
            ->where('cash_type', $cashType)
            ->whereBetween('created_at', [$startDateTime, $endDateTime])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        // URL parametrelerini korumak için
        $transactions->appends($request->query());

        // Mevcut sistemdeki paraları hesapla (tarih filtreli)
        if ($cashType === 'stock') {
            $existingCash = Sale::where('payment_method', 'cash')
                ->whereBetween('created_at', [$startDateTime, $endDateTime])
                ->sum('total_amount');
        } else {
            // Kafe için HEM normal HEM split payment'ları hesapla (tarih filtreli)
            // Split payment'ı olan sipariş ID'lerini al
            $splitOrderIds = DB::table('cafe_order_payments')
                ->join('cafe_orders', 'cafe_order_payments.cafe_order_id', '=', 'cafe_orders.id')
                ->where('cafe_orders.status', '!=', CafeOrder::STATUS_CANCELLED)
                ->where('cafe_orders.is_paid', true)
                ->whereBetween('cafe_orders.created_at', [$startDateTime, $endDateTime])
                ->pluck('cafe_orders.id')
                ->unique();

            // Normal nakit ödemeler - Split payment'ı olanları ÇIKAR (tarih filtreli)
            $normalCash = CafeOrder::where('payment_method', 'cash')
                ->where('is_paid', true)
                ->where('status', '!=', CafeOrder::STATUS_CANCELLED)
                ->whereNotIn('id', $splitOrderIds)
                ->whereBetween('created_at', [$startDateTime, $endDateTime])
                ->get()
                ->sum(function($order) {
                    return $order->final_amount > 0 ? $order->final_amount : $order->total_amount;
                });
                
            // Split nakit ödemeler - SADECE NAKIT KISMINI AL (tarih filtreli)
            $splitCash = DB::table('cafe_order_payments')
                ->join('cafe_orders', 'cafe_order_payments.cafe_order_id', '=', 'cafe_orders.id')
                ->where('cafe_orders.status', '!=', CafeOrder::STATUS_CANCELLED)
                ->where('cafe_orders.is_paid', true)
                ->where('cafe_order_payments.payment_method', 'cash')
                ->whereBetween('cafe_orders.created_at', [$startDateTime, $endDateTime])
                ->sum('cafe_order_payments.amount');
                
            $existingCash = $normalCash + $splitCash;
        }

        // Kasa işlemlerinden bakiye (tarih filtreli)
        $transactionBalance = CashTransaction::where('cash_type', $cashType)
            ->whereBetween('created_at', [$startDateTime, $endDateTime])
            ->selectRaw('SUM(CASE WHEN transaction_type = "income" THEN amount ELSE -amount END) as balance')
            ->value('balance') ?? 0;
        
        // Toplam bakiye
        $currentBalance = $existingCash + $transactionBalance;

        // İstatistikler (tarih filtreli)
        $totalIncome = CashTransaction::where('cash_type', $cashType)
            ->where('transaction_type', 'income')
            ->whereBetween('created_at', [$startDateTime, $endDateTime])
            ->sum('amount');
        
        $totalExpense = CashTransaction::where('cash_type', $cashType)
            ->where('transaction_type', 'expense')
            ->whereBetween('created_at', [$startDateTime, $endDateTime])
            ->sum('amount');
        
        $totalWithdrawal = CashTransaction::where('cash_type', $cashType)
            ->where('transaction_type', 'withdrawal')
            ->whereBetween('created_at', [$startDateTime, $endDateTime])
            ->sum('amount');

        return view('cash-register.transactions', compact(
            'transactions', 
            'cashType', 
            'currentBalance',
            'totalIncome',
            'totalExpense',
            'totalWithdrawal',
            'startDate',
            'endDate',
            'startTime',
            'endTime'
        ));
    }

    public function create($cashType)
    {
        return view('cash-register.create', compact('cashType'));
    }

    public function store(Request $request, $cashType)
    {
        $request->validate([
            'transaction_type' => 'required|in:income,expense,withdrawal',
            'amount' => 'required|numeric|min:0.01',
            'description' => 'required|string|max:255',
            'notes' => 'nullable|string|max:1000'
        ]);

        CashTransaction::create([
            'cash_type' => $cashType,
            'transaction_type' => $request->transaction_type,
            'amount' => $request->amount,
            'description' => $request->description,
            'notes' => $request->notes,
            'user_id' => Auth::id()
        ]);

        $cashTypeName = $cashType === 'stock' ? 'Stok Takip Kasası' : 'Kafe Sistemi Kasası';
        
        return redirect()->route('cash-register.index')
            ->with('success', $cashTypeName . ' için işlem başarıyla kaydedildi.');
    }

    public function show(CashTransaction $transaction)
    {
        return view('cash-register.show', compact('transaction'));
    }
}