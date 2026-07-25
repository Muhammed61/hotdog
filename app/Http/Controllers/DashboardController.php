<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Sale;
use App\Models\StockMovement;
use App\Models\CafeOrder;
use App\Models\CashTransaction;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $today = Carbon::today();
        $thisMonth = Carbon::now()->startOfMonth();

        // Kasa bakiyelerini hesapla
        $existingStockCash = Sale::where('payment_method', 'cash')->sum('total_amount');
        
        // Split ödemeli siparişlerin ID'lerini al
        $splitOrderIds = DB::table('cafe_order_payments')
            ->join('cafe_orders', 'cafe_order_payments.cafe_order_id', '=', 'cafe_orders.id')
            ->where('cafe_orders.status', '!=', 'cancelled')
            ->where('cafe_orders.is_paid', true)
            ->pluck('cafe_orders.id')
            ->unique();

        // Normal nakit ödemeler - Split payment'ı olanları ÇIKAR
        $normalCafeCash = CafeOrder::where('payment_method', 'cash')
            ->where('is_paid', true)
            ->where('status', '!=', CafeOrder::STATUS_CANCELLED)
            ->whereNotIn('id', $splitOrderIds)
            ->get()
            ->sum(function($order) {
                return $order->final_amount > 0 ? $order->final_amount : $order->total_amount;
            });
            
        // Split nakit ödemeler - SADECE NAKIT KISMINI AL
        $splitCafeCash = DB::table('cafe_order_payments')
            ->join('cafe_orders', 'cafe_order_payments.cafe_order_id', '=', 'cafe_orders.id')
            ->where('cafe_orders.status', '!=', CafeOrder::STATUS_CANCELLED)
            ->where('cafe_orders.is_paid', true)
            ->where('cafe_order_payments.payment_method', 'cash')
            ->sum('cafe_order_payments.amount');
            
        // Sadece nakit ödemeler (kasa işlemleri HARİÇ) - cash-register'daki existingCafeCash ile aynı
        $existingCafeCash = $normalCafeCash + $splitCafeCash;
        
        $stockTransactionBalance = CashTransaction::getCashBalance('stock');
        $cafeTransactionBalance = CashTransaction::getCashBalance('cafe');
        
        $totalStockCash = $existingStockCash + $stockTransactionBalance;
        $totalCafeCash = $existingCafeCash + $cafeTransactionBalance;
        
        // Dashboard'da gösterilecek değer - toplam kasa (nakit ödemeler + kasa işlemleri)
        $cafeBalance = $totalCafeCash;

        // Düşük stok ürünlerini yeni sistemle hesapla
        $lowStockProducts = Product::getLowStockProducts();

        $stats = [
            'total_products' => Product::count(),
            'low_stock_products' => $lowStockProducts->count(),
            'today_sales' => Sale::whereDate('created_at', $today)->sum('total_amount'),
            'monthly_sales' => Sale::where('created_at', '>=', $thisMonth)->sum('total_amount'),
            'today_sales_count' => Sale::whereDate('created_at', $today)->count(),
            'stock_cash_balance' => $totalStockCash,
            'cafe_cash_balance' => $totalCafeCash,
        ];

        // Sadece nakit ödemeler (kasa işlemleri HARİÇ)
        $pureNormalCash = CafeOrder::where('payment_method', 'cash')
            ->where('is_paid', true)
            ->where('status', '!=', 'cancelled')
            ->get()
            ->sum(function($order) {
                return $order->final_amount > 0 ? $order->final_amount : $order->total_amount;
            });
            
        $pureSplitCash = DB::table('cafe_order_payments')
            ->join('cafe_orders', 'cafe_order_payments.cafe_order_id', '=', 'cafe_orders.id')
            ->where('cafe_order_payments.payment_method', 'cash')
            ->where('cafe_orders.is_paid', true)
            ->where('cafe_orders.status', '!=', 'cancelled')
            ->sum('cafe_order_payments.amount');
            
        $pureCardPayments = CafeOrder::where('payment_method', 'card')
            ->where('is_paid', true)
            ->where('status', '!=', 'cancelled')
            ->get()
            ->sum(function($order) {
                return $order->final_amount > 0 ? $order->final_amount : $order->total_amount;
            });
            
        $pureSplitCard = DB::table('cafe_order_payments')
            ->join('cafe_orders', 'cafe_order_payments.cafe_order_id', '=', 'cafe_orders.id')
            ->where('cafe_order_payments.payment_method', 'card')
            ->where('cafe_orders.is_paid', true)
            ->where('cafe_orders.status', '!=', 'cancelled')
            ->sum('cafe_order_payments.amount');

        // Sipariş gelirini (cash + card, split dahil), rapordaki mantıkla hesapla
        $orderRevenue = CafeOrder::where('is_paid', true)
            ->where('status', '!=', CafeOrder::STATUS_CANCELLED)
            ->selectRaw('SUM(CASE WHEN final_amount > 0 THEN final_amount ELSE total_amount END) as revenue')
            ->value('revenue') ?? 0;

        // Toplam Gelir kartı: Sipariş geliri + Kafe Kasası (kasa işlemleri) net etkisi
        $totalRevenueCombined = $orderRevenue + $cafeTransactionBalance;

        // Kafe İstatistikleri (Toplam Gelir kartı artık kasa işlemleri dahil)
        $cafeStats = [
            'total_orders' => CafeOrder::count(),
            'total_revenue' => $totalRevenueCombined,
            'cash_payments' => $pureNormalCash + $pureSplitCash, // SADECE nakit ödemeler
            'card_payments' => $pureCardPayments + $pureSplitCard, // Tüm kart ödemeleri
            'unpaid_orders' => CafeOrder::where('status', 'cancelled')->count(),
            'completed_orders' => CafeOrder::where('status', 'served')->count(),
        ];

        // Düşük stok ürünlerini sırala (en düşük stok önce)
        $lowStockProducts = $lowStockProducts->sortBy('stock_quantity')->take(10);

        $recentSales = Sale::with('user')
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        $dailySales = Sale::selectRaw('DATE(created_at) as date, SUM(total_amount) as total')
            ->where('created_at', '>=', Carbon::now()->subDays(7))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // En çok sipariş edilen ürünler (son 30 gün) - Sadece ödenen siparişler
        $topProducts = DB::table('cafe_order_items')
            ->join('cafe_orders', 'cafe_order_items.cafe_order_id', '=', 'cafe_orders.id')
            ->join('products', 'cafe_order_items.product_id', '=', 'products.id')
            ->selectRaw('
                products.name, 
                SUM(cafe_order_items.quantity) as total_ordered, 
                SUM(
                    CASE 
                        WHEN cafe_orders.final_amount > 0 AND cafe_orders.total_amount > 0
                        THEN cafe_order_items.total_price * (cafe_orders.final_amount / cafe_orders.total_amount)
                        ELSE cafe_order_items.total_price 
                    END
                ) as total_revenue
            ')
            ->where('cafe_orders.created_at', '>=', Carbon::now()->subDays(30))
            ->where('cafe_orders.status', '!=', CafeOrder::STATUS_CANCELLED)
            ->where('cafe_orders.is_paid', true)
            ->groupBy('products.id', 'products.name')
            ->orderBy('total_ordered', 'desc')
            ->limit(5)
            ->get();

        // Masa performansı (son 30 gün) - Sadece ödenen siparişler
        $tablePerformance = DB::table('cafe_orders')
            ->join('tables', 'cafe_orders.table_id', '=', 'tables.id')
            ->selectRaw('
                tables.name as table_name, 
                COUNT(*) as orders_count, 
                SUM(
                    CASE 
                        WHEN cafe_orders.final_amount > 0 
                        THEN cafe_orders.final_amount
                        ELSE cafe_orders.total_amount 
                    END
                ) as total_revenue, 
                AVG(
                    CASE 
                        WHEN cafe_orders.final_amount > 0 
                        THEN cafe_orders.final_amount
                        ELSE cafe_orders.total_amount 
                    END
                ) as average_order
            ')
            ->where('cafe_orders.created_at', '>=', Carbon::now()->subDays(30))
            ->where('cafe_orders.status', '!=', CafeOrder::STATUS_CANCELLED)
            ->where('cafe_orders.is_paid', true)
            ->groupBy('tables.id', 'tables.name')
            ->orderBy('total_revenue', 'desc')
            ->limit(5)
            ->get();

        return view('dashboard', compact('stats', 'cafeStats', 'lowStockProducts', 'recentSales', 'dailySales', 'topProducts', 'tablePerformance', 'cafeBalance'));
    }
}