<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\CafeOrder;
use App\Models\UserActivity;
use App\Models\Table;
use App\Models\CashTransaction;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function index()
    {
        return view('reports.index');
    }

    public function sales(Request $request)
    {
        $startDate = $request->start_date ? Carbon::parse($request->start_date) : Carbon::now()->startOfMonth();
        $endDate = $request->end_date ? Carbon::parse($request->end_date) : Carbon::now()->endOfMonth();
        $period = $request->period ?? 'daily';

        // Excel export kontrolÃƒÂ¼
        if ($request->has('export') && $request->export === 'excel') {
            return $this->exportSalesExcel($startDate, $endDate, $period);
        }

        // SatÃ„Â±Ã…Å¸ verilerini al (sayfalama olmadan ÃƒÂ¶zet iÃƒÂ§in)
        $allSales = Sale::with(['user', 'saleItems.product'])
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get();

        // Ãƒâ€“zet bilgileri hesapla
        $summary = [
            'total_sales' => $allSales->count(),
            'total_revenue' => $allSales->sum('total_amount'),
            'average_sale' => $allSales->count() > 0 ? $allSales->sum('total_amount') / $allSales->count() : 0,
            'total_items' => $allSales->sum(function($sale) {
                return $sale->saleItems->sum('quantity');
            })
        ];

        // Periyoda gÃƒÂ¶re satÃ„Â±Ã…Å¸ verilerini grupla (sayfalama ile)
        $salesData = collect();
        
        if ($period === 'daily') {
            $salesData = Sale::selectRaw('DATE(created_at) as date, COUNT(*) as sales_count, SUM(total_amount) as total_revenue, AVG(total_amount) as average_sale')
                ->whereBetween('created_at', [$startDate, $endDate])
                ->groupBy('date')
                ->orderBy('date')
                ->paginate(15);
        } elseif ($period === 'weekly') {
            $salesData = Sale::selectRaw('YEARWEEK(created_at) as date, COUNT(*) as sales_count, SUM(total_amount) as total_revenue, AVG(total_amount) as average_sale')
                ->whereBetween('created_at', [$startDate, $endDate])
                ->groupBy('date')
                ->orderBy('date')
                ->paginate(15);
        } elseif ($period === 'monthly') {
            $salesData = Sale::selectRaw('DATE_FORMAT(created_at, "%Y-%m") as date, COUNT(*) as sales_count, SUM(total_amount) as total_revenue, AVG(total_amount) as average_sale')
                ->whereBetween('created_at', [$startDate, $endDate])
                ->groupBy('date')
                ->orderBy('date')
                ->paginate(15);
        }

        // En ÃƒÂ§ok satan ÃƒÂ¼rÃƒÂ¼nler
        $topProducts = DB::table('sale_items')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->join('products', 'sale_items.product_id', '=', 'products.id')
            ->selectRaw('products.name, SUM(sale_items.quantity) as total_sold')
            ->whereBetween('sales.created_at', [$startDate, $endDate])
            ->groupBy('products.id', 'products.name')
            ->orderBy('total_sold', 'desc')
            ->limit(10)
            ->get();

        return view('reports.sales', compact('allSales', 'summary', 'salesData', 'topProducts', 'startDate', 'endDate', 'period'));
    }

    private function exportSalesExcel($startDate, $endDate, $period)
    {
        // SatÃ„Â±Ã…Å¸ verilerini al
        $sales = Sale::with(['user', 'saleItems.product'])
            ->whereBetween('created_at', [$startDate, $endDate])
            ->orderBy('created_at', 'desc')
            ->get();

        // Periyoda gÃƒÂ¶re satÃ„Â±Ã…Å¸ verilerini grupla
        $salesData = collect();
        
        if ($period === 'daily') {
            $salesData = Sale::selectRaw('DATE(created_at) as date, COUNT(*) as sales_count, SUM(total_amount) as total_revenue, AVG(total_amount) as average_sale')
                ->whereBetween('created_at', [$startDate, $endDate])
                ->groupBy('date')
                ->orderBy('date')
                ->get();
        } elseif ($period === 'weekly') {
            $salesData = Sale::selectRaw('YEARWEEK(created_at) as date, COUNT(*) as sales_count, SUM(total_amount) as total_revenue, AVG(total_amount) as average_sale')
                ->whereBetween('created_at', [$startDate, $endDate])
                ->groupBy('date')
                ->orderBy('date')
                ->get();
        } elseif ($period === 'monthly') {
            $salesData = Sale::selectRaw('DATE_FORMAT(created_at, "%Y-%m") as date, COUNT(*) as sales_count, SUM(total_amount) as total_revenue, AVG(total_amount) as average_sale')
                ->whereBetween('created_at', [$startDate, $endDate])
                ->groupBy('date')
                ->orderBy('date')
                ->get();
        }

        // CSV formatÃ„Â±nda Excel dosyasÃ„Â± oluÃ…Å¸tur
        $filename = 'satis_raporu_' . $startDate->format('Y-m-d') . '_' . $endDate->format('Y-m-d') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0'
        ];

        $callback = function() use ($salesData, $sales, $startDate, $endDate) {
            $file = fopen('php://output', 'w');
            
            // UTF-8 BOM ekle (TÃƒÂ¼rkÃƒÂ§e karakterler iÃƒÂ§in)
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // BaÃ…Å¸lÃ„Â±k bilgileri
            fputcsv($file, ['SATIÃ…Â RAPORU'], ';');
            fputcsv($file, ['Tarih AralÃ„Â±Ã„Å¸Ã„Â±: ' . $startDateTime->format('d.m.Y') . ' - ' . $endDateTime->format('d.m.Y')], ';');
            fputcsv($file, ['Saat AralÃ„Â±Ã„Å¸Ã„Â±: ' . $startTime . ' - ' . $endTime], ';');
            fputcsv($file, ['Rapor Tarihi: ' . now()->format('d.m.Y H:i')], ';');
            fputcsv($file, [''], ';'); // BoÃ…Å¸ satÃ„Â±r
            
            // Ãƒâ€“zet bilgiler
            fputcsv($file, ['Ãƒâ€“ZET BÃ„Â°LGÃ„Â°LER'], ';');
            fputcsv($file, ['Toplam SatÃ„Â±Ã…Å¸ SayÃ„Â±sÃ„Â±', $sales->count()], ';');
            fputcsv($file, ['Toplam Gelir', number_format($sales->sum('total_amount'), 2) . ' Ã¢â€šÂº'], ';');
            fputcsv($file, ['Ortalama SatÃ„Â±Ã…Å¸', number_format($sales->count() > 0 ? $sales->sum('total_amount') / $sales->count() : 0, 2) . ' Ã¢â€šÂº'], ';');
            fputcsv($file, ['Toplam SatÃ„Â±lan ÃƒÅ“rÃƒÂ¼n', $sales->sum(function($sale) { return $sale->saleItems->sum('quantity'); })], ';');
            fputcsv($file, [''], ';'); // BoÃ…Å¸ satÃ„Â±r
            
            // Periyodik veriler baÃ…Å¸lÃ„Â±Ã„Å¸Ã„Â±
            fputcsv($file, ['PERÃ„Â°YODÃ„Â°K SATIÃ…Â VERÃ„Â°LERÃ„Â°'], ';');
            fputcsv($file, ['Tarih', 'SatÃ„Â±Ã…Å¸ SayÃ„Â±sÃ„Â±', 'Toplam Gelir (Ã¢â€šÂº)', 'Ortalama SatÃ„Â±Ã…Å¸ (Ã¢â€šÂº)'], ';');
            
            foreach ($salesData as $data) {
                fputcsv($file, [
                    $data->date,
                    $data->sales_count,
                    number_format($data->total_revenue, 2),
                    number_format($data->average_sale, 2)
                ], ';');
            }
            
            fputcsv($file, [''], ';'); // BoÃ…Å¸ satÃ„Â±r
            
            // DetaylÃ„Â± satÃ„Â±Ã…Å¸ listesi
            fputcsv($file, ['DETAYLI SATIÃ…Â LÃ„Â°STESÃ„Â°'], ';');
            fputcsv($file, ['Tarih', 'Saat', 'SatÃ„Â±Ã…Å¸ ID', 'KullanÃ„Â±cÃ„Â±', 'Toplam Tutar (Ã¢â€šÂº)', 'Ãƒâ€“deme YÃƒÂ¶ntemi'], ';');
            
            foreach ($sales as $sale) {
                fputcsv($file, [
                    $sale->created_at->format('d.m.Y'),
                    $sale->created_at->format('H:i'),
                    $sale->id,
                    $sale->user->name ?? 'Bilinmiyor',
                    number_format($sale->total_amount, 2),
                    $sale->payment_method === 'cash' ? 'Nakit' : ($sale->payment_method === 'card' ? 'Kart' : 'DiÃ„Å¸er')
                ], ';');
            }
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function stock()
    {
        $products = Product::with('category')
            ->selectRaw('*, (stock_quantity * purchase_price) as stock_value')
            ->orderBy('name')
            ->paginate(20);

        $totalStockValue = Product::selectRaw('SUM(stock_quantity * purchase_price) as total_value')->value('total_value');
        
        $lowStockProducts = Product::whereRaw('stock_quantity <= min_stock_level')->get();

        $categoryStock = Product::with('category')
            ->selectRaw('category_id, SUM(stock_quantity) as total_quantity, SUM(stock_quantity * purchase_price) as total_value')
            ->groupBy('category_id')
            ->get();

        return view('reports.stock', compact('products', 'totalStockValue', 'lowStockProducts', 'categoryStock'));
    }

    public function movements(Request $request)
    {
        $startDate = $request->start_date ? Carbon::parse($request->start_date) : Carbon::now()->startOfMonth();
        $endDate = $request->end_date ? Carbon::parse($request->end_date) : Carbon::now()->endOfMonth();

        $movements = StockMovement::with(['product', 'user'])
            ->whereBetween('created_at', [$startDate, $endDate])
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        $inMovements = StockMovement::where('type', 'in')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->sum('quantity');

        $outMovements = StockMovement::where('type', 'out')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->sum('quantity');

        return view('reports.movements', compact('movements', 'inMovements', 'outMovements', 'startDate', 'endDate'));
    }

    public function profit(Request $request)
    {
        $startDate = $request->start_date ? Carbon::parse($request->start_date) : Carbon::now()->startOfMonth();
        $endDate = $request->end_date ? Carbon::parse($request->end_date) : Carbon::now()->endOfMonth();

        $profitData = DB::table('sale_items')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->join('products', 'sale_items.product_id', '=', 'products.id')
            ->selectRaw('
                sale_items.product_id,
                products.name as product_name,
                SUM(sale_items.quantity) as total_sold,
                SUM(sale_items.total_price) as total_revenue,
                SUM(sale_items.quantity * products.purchase_price) as total_cost,
                SUM(sale_items.total_price - (sale_items.quantity * products.purchase_price)) as total_profit
            ')
            ->whereBetween('sales.created_at', [$startDate, $endDate])
            ->groupBy('sale_items.product_id', 'products.name')
            ->orderBy('total_profit', 'desc')
            ->paginate(20);

        $totalRevenue = DB::table('sale_items')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->whereBetween('sales.created_at', [$startDate, $endDate])
            ->sum('sale_items.total_price');
            
        $totalCost = DB::table('sale_items')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->join('products', 'sale_items.product_id', '=', 'products.id')
            ->whereBetween('sales.created_at', [$startDate, $endDate])
            ->selectRaw('SUM(sale_items.quantity * products.purchase_price) as total_cost')
            ->value('total_cost');
            
        $totalProfit = $totalRevenue - $totalCost;

        return view('reports.profit', compact('profitData', 'totalRevenue', 'totalCost', 'totalProfit', 'startDate', 'endDate'));
    }


    public function cafe(Request $request)
    {
        $productPerPage = $request->get('product_per_page', 20);
        $topProductsPerPage = $request->get('top_products_per_page', 10);
        $tablePerformancePerPage = $request->get('table_performance_per_page', 10);
        $startDate = $request->get('start_date', now()->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->format('Y-m-d'));
        $period = $request->get('period', 'daily');
        
        // Saat ve dakika filtresi (cash register gibi)
        $startTime = $request->get('start_time', '00:00');
        $endTime = $request->get('end_time', '23:59');

        // Tarih formatını düzenle - sadece tarih string'i kullan
        $startDateTime = Carbon::parse($startDate . ' ' . $startTime . ':00');
        $endDateTime = Carbon::parse($endDate . ' ' . $endTime . ':59');
    
        // Excel export kontrolü

        if ($request->has('export') && $request->export === 'excel') {
            return $this->exportCafeExcel($startDateTime, $endDateTime, $period, $startTime, $endTime);
        }
    
        $cafeOrders = CafeOrder::with(['table', 'user', 'cafeOrderItems.product', 'cafeOrderExtras'])
            ->whereBetween('created_at', [$startDateTime, $endDateTime])
            ->where('status', '!=', CafeOrder::STATUS_CANCELLED)
            ->orderBy('created_at', 'desc')
            ->paginate(20);
    
        // Toplam gelir (sayfalama olmadan hesapla) - Ã„Â°ptal edilenleri hariÃƒÂ§ tut
        $allOrders = CafeOrder::whereBetween('created_at', [$startDateTime, $endDateTime])
            ->where('status', '!=', CafeOrder::STATUS_CANCELLED)
            ->get();
        
        // Ã„Â°ndirim hesaplamalarÃ„Â± ekle - Debug bilgileri
        $totalOriginalAmount = $allOrders->where('is_paid', true)->sum('total_amount');
        $totalDiscountAmount = $allOrders->where('is_paid', true)->sum('discount_amount');
        $totalFinalAmount = $allOrders->where('is_paid', true)->sum('final_amount');
        
        // Debug iÃƒÂ§in log ekle
        \Log::info('Ã„Â°ndirim Debug', [
            'total_original' => $totalOriginalAmount,
            'total_discount' => $totalDiscountAmount,
            'total_final' => $totalFinalAmount,
            'paid_orders_count' => $allOrders->where('is_paid', true)->count()
        ]);
        
        // EÃ„Å¸er final_amount null ise total_amount kullan
        $totalRevenue = $totalFinalAmount > 0 ? $totalFinalAmount : $totalOriginalAmount;
        
        // Ã„Â°ndirim istatistikleri - Her zaman oluÃ…Å¸tur
        $discountStats = [
            'total_original_amount' => $totalOriginalAmount,
            'total_discount_amount' => $totalDiscountAmount,
            'total_final_amount' => $totalRevenue,
            'orders_with_discount' => $allOrders->where('is_paid', true)->where('discount_amount', '>', 0)->count()
        ];
        
        $totalOrders = $allOrders->count();
        $paidOrders = $allOrders->where('is_paid', true)->count();
        $unpaidOrders = $allOrders->where('is_paid', false)->count();
        
        // Ãƒâ€“denen sipariÃ…Å¸ tutarÃ„Â±
        $paidOrdersAmount = $allOrders->where('is_paid', true)->sum('total_amount');
    
        // SPLIT PAYMENT'LARI Ãƒâ€“DEME YÃƒâ€“NTEMÃ„Â°NE GÃƒâ€“RE AYIR - SADECE Ã„Â°PTAL EDÃ„Â°LMEYEN SÃ„Â°PARÃ„Â°Ã…ÂLERDEN
        // Nakit split payment'lar
        $splitCashPayments = DB::table('cafe_order_payments')
            ->join('cafe_orders', 'cafe_order_payments.cafe_order_id', '=', 'cafe_orders.id')
            ->whereBetween('cafe_orders.created_at', [$startDateTime, $endDateTime])
            ->where('cafe_orders.status', '!=', CafeOrder::STATUS_CANCELLED)
            ->where('cafe_orders.is_paid', true) // Sadece ÃƒÂ¶denen sipariÃ…Å¸ler
            ->where('cafe_order_payments.payment_method', 'cash')
            ->selectRaw('COUNT(cafe_order_payments.id) as payment_count, COALESCE(SUM(cafe_order_payments.amount), 0) as total_amount')
            ->first();
            
        // Kart split payment'lar
        $splitCardPayments = DB::table('cafe_order_payments')
            ->join('cafe_orders', 'cafe_order_payments.cafe_order_id', '=', 'cafe_orders.id')
            ->whereBetween('cafe_orders.created_at', [$startDateTime, $endDateTime])
            ->where('cafe_orders.status', '!=', CafeOrder::STATUS_CANCELLED)
            ->where('cafe_orders.is_paid', true) // Sadece ÃƒÂ¶denen sipariÃ…Å¸ler
            ->where('cafe_order_payments.payment_method', 'card')
            ->selectRaw('COUNT(cafe_order_payments.id) as payment_count, COALESCE(SUM(cafe_order_payments.amount), 0) as total_amount')
            ->first();
            
        // Null kontrolÃƒÂ¼ ve default deÃ„Å¸erler
        $splitCashPayments = $splitCashPayments ?: (object) ['payment_count' => 0, 'total_amount' => 0];
        $splitCardPayments = $splitCardPayments ?: (object) ['payment_count' => 0, 'total_amount' => 0];
        
        // Toplam split payment hesaplamasÃ„Â± - Ã„Â°ptal edilenleri hariÃƒÂ§ tut
        $splitPaymentTotal = ($splitCashPayments->total_amount ?? 0) + ($splitCardPayments->total_amount ?? 0);
        
        // Split payment sayÃ„Â±sÃ„Â±
        $splitPaymentCount = ($splitCashPayments->payment_count ?? 0) + ($splitCardPayments->payment_count ?? 0);
        
        // Geriye uyumluluk iÃƒÂ§in eski deÃ„Å¸iÃ…Å¸kenler (sadece split ÃƒÂ¶demeler)
        $cashPayments = $splitCashPayments;
        $cardPayments = $splitCardPayments;
        
        // PaymentStats'Ã„Â± sadece split payment'lar iÃƒÂ§in dÃƒÂ¼zenle
        $paymentStats = collect([
            (object) [
                'payment_method' => 'cash',
                'order_count' => $splitCashPayments->payment_count ?? 0,
                'total_amount' => $splitCashPayments->total_amount ?? 0
            ],
            (object) [
                'payment_method' => 'card', 
                'order_count' => $splitCardPayments->payment_count ?? 0,
                'total_amount' => $splitCardPayments->total_amount ?? 0
            ]
        ]);
        
        // KARTLAR Ã„Â°Ãƒâ€¡Ã„Â°N: PaymentStats ile aynÃ„Â± deÃ„Å¸erleri kullan (sadece split payment'lar)
        $enhancedCashPayments = (object) [
            'total_amount' => $splitCashPayments->total_amount ?? 0,
            'order_count' => $splitCashPayments->payment_count ?? 0,
            'normal_amount' => 0,
            'normal_count' => 0,
            'split_amount' => $splitCashPayments->total_amount ?? 0,
            'split_count' => $splitCashPayments->payment_count ?? 0
        ];
        
        $enhancedCardPayments = (object) [
            'total_amount' => $splitCardPayments->total_amount ?? 0,
            'order_count' => $splitCardPayments->payment_count ?? 0,
            'normal_amount' => 0,
            'normal_count' => 0,
            'split_amount' => $splitCardPayments->total_amount ?? 0,
            'split_count' => $splitCardPayments->payment_count ?? 0
        ];
        
        // Toplam geliri de split payment'lar toplamÃ„Â± olarak ayarla
        $totalRevenue = $splitPaymentTotal;
        
        // DEBUG: Enhanced payments'larÃ„Â± logla
        \Log::info('Enhanced Payments:', [
            'totalRevenue' => $totalRevenue,
            'splitPaymentTotal' => $splitPaymentTotal,
            'enhancedCashPayments' => $enhancedCashPayments,
            'enhancedCardPayments' => $enhancedCardPayments,
            'total_check' => $enhancedCashPayments->total_amount + $enhancedCardPayments->total_amount
        ]);
    
        // Ãƒâ€“zet bilgileri hesapla - Ã„Â°ptal edilenleri hariÃƒÂ§ tut
        $summary = [
            'total_orders' => $allOrders->count(),
            'total_revenue' => $allOrders->sum('total_amount'),
            'average_order' => $allOrders->count() > 0 ? $allOrders->sum('total_amount') / $allOrders->count() : 0,
            'total_items' => $allOrders->sum(function($order) {
                return $order->cafeOrderItems->sum('quantity');
            }),
            'completed_orders' => $allOrders->where('status', 'served')->count(),
            'cancelled_orders' => CafeOrder::whereBetween('created_at', [$startDate, $endDate])
                ->where('status', CafeOrder::STATUS_CANCELLED)->count()
        ];

        // Periyoda gÃƒÂ¶re sipariÃ…Å¸ verilerini grupla - Ã„Â°ptal edilenleri hariÃƒÂ§ tut
        $ordersData = collect();
        
        if ($period === 'daily') {
            $ordersData = CafeOrder::selectRaw('DATE(created_at) as date, COUNT(*) as orders_count, SUM(total_amount) as total_revenue, AVG(total_amount) as average_order')
                ->whereBetween('created_at', [$startDateTime, $endDateTime])
                ->where('status', '!=', CafeOrder::STATUS_CANCELLED)
                ->groupBy('date')
                ->orderBy('date')
                ->get();
        } elseif ($period === 'weekly') {
            $ordersData = CafeOrder::selectRaw('YEARWEEK(created_at) as date, COUNT(*) as orders_count, SUM(total_amount) as total_revenue, AVG(total_amount) as average_order')
                ->whereBetween('created_at', [$startDateTime, $endDateTime])
                ->where('status', '!=', CafeOrder::STATUS_CANCELLED)
                ->groupBy('date')
                ->orderBy('date')
                ->get();
        } elseif ($period === 'monthly') {
            $ordersData = CafeOrder::selectRaw('DATE_FORMAT(created_at, "%Y-%m") as date, COUNT(*) as orders_count, SUM(total_amount) as total_revenue, AVG(total_amount) as average_order')
                ->whereBetween('created_at', [$startDateTime, $endDateTime])
                ->where('status', '!=', CafeOrder::STATUS_CANCELLED)
                ->groupBy('date')
                ->orderBy('date')
                ->get();
        }

        // En ÃƒÂ§ok sipariÃ…Å¸ edilen ÃƒÂ¼rÃƒÂ¼nler - Ã„Â°ptal edilenleri hariÃƒÂ§ tut VE sadece ÃƒÂ¶denen sipariÃ…Å¸ler
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
            ->whereBetween('cafe_orders.created_at', [$startDateTime, $endDateTime])
            ->where('cafe_orders.status', '!=', CafeOrder::STATUS_CANCELLED)
            ->where('cafe_orders.is_paid', true)
            ->groupBy('products.id', 'products.name')
            ->orderBy('total_ordered', 'desc')
            ->limit(10)
            ->paginate($topProductsPerPage, ['*'], 'top_products_page');
        
        $topProducts->appends($request->except('top_products_page'));

        // Masa performansÃ„Â± - Ã„Â°ptal edilenleri hariÃƒÂ§ tut VE sadece ÃƒÂ¶denen sipariÃ…Å¸ler
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
            ->whereBetween('cafe_orders.created_at', [$startDateTime, $endDateTime])
            ->where('cafe_orders.status', '!=', CafeOrder::STATUS_CANCELLED)
            ->where('cafe_orders.is_paid', true)
            ->groupBy('tables.id', 'tables.name')
            ->orderBy('total_revenue', 'desc')
            ->paginate($tablePerformancePerPage, ['*'], 'table_performance_page');
        
        $tablePerformance->appends($request->except('table_performance_page'));

        // SipariÃ…Å¸ durumu daÃ„Å¸Ã„Â±lÃ„Â±mÃ„Â± - TÃƒÂ¼m durumlarÃ„Â± dahil et (iptal edilenler de dahil)
        $statusDistribution = CafeOrder::selectRaw('status, COUNT(*) as count')
            ->whereBetween('created_at', [$startDateTime, $endDateTime])
            ->groupBy('status')
            ->get();

        // Saatlik sipariÃ…Å¸ daÃ„Å¸Ã„Â±lÃ„Â±mÃ„Â± - Ã„Â°ptal edilenleri hariÃƒÂ§ tut
        $hourlyOrders = CafeOrder::selectRaw('HOUR(created_at) as hour, COUNT(*) as orders_count, SUM(total_amount) as total_revenue')
            ->whereBetween('created_at', [$startDateTime, $endDateTime])
            ->where('status', '!=', CafeOrder::STATUS_CANCELLED)
            ->groupBy('hour')
            ->orderBy('hour')
            ->get();

        // SÃƒÂ¼re analizi ve yoÃ„Å¸unluk istatistikleri
        $durationStats = $this->calculateDurationStats($allOrders);
        $occupancyStats = $this->calculateOccupancyStats($allOrders);
        
        
        // TÃƒÂ¼m ÃƒÂ¼rÃƒÂ¼n satÃ„Â±Ã…Å¸larÃ„Â± - Ã„Â°ptal edilmemiÃ…Å¸ sipariÃ…Å¸lerden
        $allProductSales = DB::table('cafe_order_items')
            ->join('cafe_orders', 'cafe_order_items.cafe_order_id', '=', 'cafe_orders.id')
            ->join('products', 'cafe_order_items.product_id', '=', 'products.id')
            ->whereBetween('cafe_orders.created_at', [$startDateTime, $endDateTime])
            ->where('cafe_orders.status', '!=', CafeOrder::STATUS_CANCELLED)
            ->where('cafe_orders.is_paid', true)
            ->select(
                'products.id',
                'products.name',
                DB::raw('SUM(cafe_order_items.quantity) as total_quantity'),
                DB::raw('SUM(cafe_order_items.total_price) as total_revenue'),
                DB::raw('COUNT(DISTINCT cafe_orders.id) as order_count')
            )
            ->groupBy('products.id', 'products.name')
            ->orderBy('total_quantity', 'desc')
            ->paginate($productPerPage, ['*'], 'product_page');
        
        // Sayfalama parametrelerini ekle
        $allProductSales->appends($request->except('product_page'));

        // SÃƒÂ¼re analizi iÃƒÂ§in geÃƒÂ§erli sipariÃ…Å¸ler
        $total_valid_orders = $durationStats['total_analyzed_orders'];

        return view('reports.cafe', compact(
            'cafeOrders',
            'summary',
            'ordersData',
            'topProducts',
            'tablePerformance',
            'statusDistribution',
            'hourlyOrders',
            'durationStats',
            'occupancyStats',
            'totalRevenue',
            'totalOrders',
            'paidOrders',
            'unpaidOrders',
            'paidOrdersAmount',
            'paymentStats',
            'cashPayments',
            'cardPayments',
            'enhancedCashPayments',
            'enhancedCardPayments',
            'splitPaymentTotal',
            'splitPaymentCount',
            'discountStats',
            'total_valid_orders',
            'startDateTime',
            'endDateTime',
            'period',
            'startTime',
            'endTime',
            'allProductSales'
        ));
    }

    private function exportCafeExcel($startDateTime, $endDateTime, $period, $startTime = '00:00', $endTime = '23:59')
    {
        // Zaman filtreleri zaten startDateTime ve endDateTime iÃƒÂ§inde
        
        // Kafe sipariÃ…Å¸lerini al - Ã„Â°ptal edilenleri hariÃƒÂ§ tut
        $cafeOrders = CafeOrder::with(['table', 'user', 'cafeOrderItems.product', 'cafeOrderExtras'])
            ->whereBetween('created_at', [$startDateTime, $endDateTime])
            ->where('status', '!=', CafeOrder::STATUS_CANCELLED)
            ->orderBy('created_at', 'desc')
            ->get();

        // SADECE NORMAL Ãƒâ€“DEMELERÃ„Â° AL (Split payment hariÃƒÂ§) - Ã„Â°ptal edilenleri hariÃƒÂ§ tut
        $paymentStats = CafeOrder::whereBetween('created_at', [$startDateTime, $endDateTime])
            ->where('is_paid', true)
            ->where('status', '!=', CafeOrder::STATUS_CANCELLED)
            ->where('payment_method', '!=', CafeOrder::PAYMENT_SPLIT)
            ->selectRaw('
                payment_method,
                COUNT(*) as order_count,
                SUM(total_amount) as total_amount
            ')
            ->groupBy('payment_method')
            ->get();

        // Normal nakit ve kart ÃƒÂ¶demeleri
        $normalCashPayments = $paymentStats->where('payment_method', 'cash')->first();
        $normalCardPayments = $paymentStats->where('payment_method', 'card')->first();
        
        // Null kontrolÃƒÂ¼ ve default deÃ„Å¸erler
        $normalCashPayments = $normalCashPayments ?: (object) ['order_count' => 0, 'total_amount' => 0];
        $normalCardPayments = $normalCardPayments ?: (object) ['order_count' => 0, 'total_amount' => 0];
        
        // SPLIT PAYMENT'LARI Ãƒâ€“DEME YÃƒâ€“NTEMÃ„Â°NE GÃƒâ€“RE AYIR - SADECE Ã„Â°PTAL EDÃ„Â°LMEYEN SÃ„Â°PARÃ„Â°Ã…ÂLERDEN
        // Nakit split payment'lar
        $splitCashPayments = DB::table('cafe_order_payments')
            ->join('cafe_orders', 'cafe_order_payments.cafe_order_id', '=', 'cafe_orders.id')
            ->whereBetween('cafe_orders.created_at', [$startDateTime, $endDateTime])
            ->where('cafe_orders.status', '!=', CafeOrder::STATUS_CANCELLED)
            ->where('cafe_orders.is_paid', true) // Sadece ÃƒÂ¶denen sipariÃ…Å¸ler
            ->where('cafe_order_payments.payment_method', 'cash')
            ->selectRaw('COUNT(cafe_order_payments.id) as payment_count, COALESCE(SUM(cafe_order_payments.amount), 0) as total_amount')
            ->first();
            
        // Kart split payment'lar
        $splitCardPayments = DB::table('cafe_order_payments')
            ->join('cafe_orders', 'cafe_order_payments.cafe_order_id', '=', 'cafe_orders.id')
            ->whereBetween('cafe_orders.created_at', [$startDateTime, $endDateTime])
            ->where('cafe_orders.status', '!=', CafeOrder::STATUS_CANCELLED)
            ->where('cafe_orders.is_paid', true) // Sadece ÃƒÂ¶denen sipariÃ…Å¸ler
            ->where('cafe_order_payments.payment_method', 'card')
            ->selectRaw('COUNT(cafe_order_payments.id) as payment_count, COALESCE(SUM(cafe_order_payments.amount), 0) as total_amount')
            ->first();
            
        // Null kontrolÃƒÂ¼ ve default deÃ„Å¸erler
        $splitCashPayments = $splitCashPayments ?: (object) ['payment_count' => 0, 'total_amount' => 0];
        $splitCardPayments = $splitCardPayments ?: (object) ['payment_count' => 0, 'total_amount' => 0];
        
        // ENHANCED PAYMENTS: Normal + Split birleÃ…Å¸imi
        $enhancedCashPayments = (object) [
            'total_amount' => ($normalCashPayments->total_amount ?? 0) + ($splitCashPayments->total_amount ?? 0),
            'order_count' => ($normalCashPayments->order_count ?? 0) + ($splitCashPayments->payment_count ?? 0),
            'normal_amount' => $normalCashPayments->total_amount ?? 0,
            'normal_count' => $normalCashPayments->order_count ?? 0,
            'split_amount' => $splitCashPayments->total_amount ?? 0,
            'split_count' => $splitCashPayments->payment_count ?? 0
        ];
        
        $enhancedCardPayments = (object) [
            'total_amount' => ($normalCardPayments->total_amount ?? 0) + ($splitCardPayments->total_amount ?? 0),
            'order_count' => ($normalCardPayments->order_count ?? 0) + ($splitCardPayments->payment_count ?? 0),
            'normal_amount' => $normalCardPayments->total_amount ?? 0,
            'normal_count' => $normalCardPayments->order_count ?? 0,
            'split_amount' => $splitCardPayments->total_amount ?? 0,
            'split_count' => $splitCardPayments->payment_count ?? 0
        ];

        // Periyoda gÃƒÂ¶re sipariÃ…Å¸ verilerini grupla - Ã„Â°ptal edilenleri hariÃƒÂ§ tut
        $ordersData = collect();
        
        if ($period === 'daily') {
            $ordersData = CafeOrder::selectRaw('DATE(created_at) as date, COUNT(*) as orders_count, SUM(total_amount) as total_revenue, AVG(total_amount) as average_order')
                ->whereBetween('created_at', [$startDateTime, $endDateTime])
                ->where('status', '!=', CafeOrder::STATUS_CANCELLED)
                ->groupBy('date')
                ->orderBy('date')
                ->get();
        } elseif ($period === 'weekly') {
            $ordersData = CafeOrder::selectRaw('YEARWEEK(created_at) as date, COUNT(*) as orders_count, SUM(total_amount) as total_revenue, AVG(total_amount) as average_order')
                ->whereBetween('created_at', [$startDateTime, $endDateTime])
                ->where('status', '!=', CafeOrder::STATUS_CANCELLED)
                ->groupBy('date')
                ->orderBy('date')
                ->get();
        } elseif ($period === 'monthly') {
            $ordersData = CafeOrder::selectRaw('DATE_FORMAT(created_at, "%Y-%m") as date, COUNT(*) as orders_count, SUM(total_amount) as total_revenue, AVG(total_amount) as average_order')
                ->whereBetween('created_at', [$startDateTime, $endDateTime])
                ->where('status', '!=', CafeOrder::STATUS_CANCELLED)
                ->groupBy('date')
                ->orderBy('date')
                ->get();
        }

        // En ÃƒÂ§ok sipariÃ…Å¸ edilen ÃƒÂ¼rÃƒÂ¼nler - Ã„Â°ptal edilenleri hariÃƒÂ§ tut
        $topProducts = DB::table('cafe_order_items')
            ->join('cafe_orders', 'cafe_order_items.cafe_order_id', '=', 'cafe_orders.id')
            ->join('products', 'cafe_order_items.product_id', '=', 'products.id')
            ->selectRaw('products.name, SUM(cafe_order_items.quantity) as total_ordered, SUM(cafe_order_items.total_price) as total_revenue')
            ->whereBetween('cafe_orders.created_at', [$startDateTime, $endDateTime])
            ->where('cafe_orders.status', '!=', CafeOrder::STATUS_CANCELLED)
            ->groupBy('products.id', 'products.name')
            ->orderBy('total_ordered', 'desc')
            ->get();

        // Masa performansÃ„Â± - Ã„Â°ptal edilenleri hariÃƒÂ§ tut
        $tablePerformance = DB::table('cafe_orders')
            ->join('tables', 'cafe_orders.table_id', '=', 'tables.id')
            ->selectRaw('tables.name as table_name, COUNT(*) as orders_count, SUM(total_amount) as total_revenue, AVG(total_amount) as average_order')
            ->whereBetween('cafe_orders.created_at', [$startDateTime, $endDateTime])
            ->where('cafe_orders.status', '!=', CafeOrder::STATUS_CANCELLED)
            ->groupBy('tables.id', 'tables.name')
            ->orderBy('total_revenue', 'desc')
            ->get();

        // Saatlik sipariÃ…Å¸ daÃ„Å¸Ã„Â±lÃ„Â±mÃ„Â± - Ã„Â°ptal edilenleri hariÃƒÂ§ tut
        $hourlyOrders = CafeOrder::selectRaw('HOUR(created_at) as hour, COUNT(*) as orders_count, SUM(total_amount) as total_revenue')
            ->whereBetween('created_at', [$startDateTime, $endDateTime])
            ->where('status', '!=', CafeOrder::STATUS_CANCELLED)
            ->groupBy('hour')
            ->orderBy('hour')
            ->get();

        // CSV formatÃ„Â±nda Excel dosyasÃ„Â± oluÃ…Å¸tur
        $filename = 'kafe_satis_raporu_' . $startDateTime->format('Y-m-d') . '_' . $endDateTime->format('Y-m-d') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0'
        ];

        $callback = function() use ($cafeOrders, $ordersData, $topProducts, $tablePerformance, $hourlyOrders, $enhancedCashPayments, $enhancedCardPayments, $startDateTime, $endDateTime, $period, $startTime, $endTime) {
            $file = fopen('php://output', 'w');
            
            // UTF-8 BOM ekle (TÃƒÂ¼rkÃƒÂ§e karakterler iÃƒÂ§in)
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // BaÃ…Å¸lÃ„Â±k bilgileri
            fputcsv($file, ['KAFE SATIÃ…Â RAPORU'], ';');
            fputcsv($file, ['Tarih AralÃ„Â±Ã„Å¸Ã„Â±: ' . $startDateTime->format('d.m.Y') . ' - ' . $endDateTime->format('d.m.Y')], ';');
            fputcsv($file, ['Periyot: ' . ($period === 'daily' ? 'GÃƒÂ¼nlÃƒÂ¼k' : ($period === 'weekly' ? 'HaftalÃ„Â±k' : 'AylÃ„Â±k'))], ';');
            fputcsv($file, ['Rapor Tarihi: ' . now()->format('d.m.Y H:i')], ';');
            fputcsv($file, [''], ';'); // BoÃ…Å¸ satÃ„Â±r
            
            // Ãƒâ€“denen ve ÃƒÂ¶denmemiÃ…Å¸ sipariÃ…Å¸leri ayÃ„Â±r
            $paidOrders = $cafeOrders->where('is_paid', true);
            $unpaidOrders = $cafeOrders->where('is_paid', false);
            
            // Ãƒâ€“zet bilgiler
            fputcsv($file, ['Ãƒâ€“ZET BÃ„Â°LGÃ„Â°LER'], ';');
            fputcsv($file, ['Toplam SipariÃ…Å¸ SayÃ„Â±sÃ„Â±', $cafeOrders->count()], ';');
            fputcsv($file, ['Ãƒâ€“denen SipariÃ…Å¸ SayÃ„Â±sÃ„Â±', $paidOrders->count()], ';');
            fputcsv($file, ['Ãƒâ€“denmemiÃ…Å¸ SipariÃ…Å¸ SayÃ„Â±sÃ„Â±', $unpaidOrders->count()], ';');
            fputcsv($file, ['Ã„Â°ptal Edilen SipariÃ…Å¸ SayÃ„Â±sÃ„Â±', CafeOrder::whereBetween('created_at', [$startDateTime, $endDateTime])->where('status', CafeOrder::STATUS_CANCELLED)->count()], ';');
            fputcsv($file, [''], ';'); // BoÃ…Å¸ satÃ„Â±r
            
            // Gelir bilgileri (sadece ÃƒÂ¶denen sipariÃ…Å¸ler)
            fputcsv($file, ['GELÃ„Â°R BÃ„Â°LGÃ„Â°LERÃ„Â°'], ';');
            fputcsv($file, ['Toplam Gelir (Ãƒâ€“denen)', number_format($paidOrders->sum('total_amount'), 2) . ' Ã¢â€šÂº'], ';');
            fputcsv($file, ['Ortalama SipariÃ…Å¸ (Ãƒâ€“denen)', number_format($paidOrders->count() > 0 ? $paidOrders->sum('total_amount') / $paidOrders->count() : 0, 2) . ' Ã¢â€šÂº'], ';');
            fputcsv($file, ['Bekleyen Gelir (Ãƒâ€“denmemiÃ…Å¸)', number_format($unpaidOrders->sum('total_amount'), 2) . ' Ã¢â€šÂº'], ';');
            fputcsv($file, ['Toplam Potansiyel Gelir', number_format($cafeOrders->sum('total_amount'), 2) . ' Ã¢â€šÂº'], ';');
            fputcsv($file, ['Toplam SipariÃ…Å¸ Edilen ÃƒÅ“rÃƒÂ¼n', $cafeOrders->sum(function($order) { return $order->cafeOrderItems->sum('quantity'); })], ';');
            fputcsv($file, [''], ';'); // BoÃ…Å¸ satÃ„Â±r
            
            // Ãƒâ€“deme yÃƒÂ¶ntemleri detayÃ„Â±
            fputcsv($file, ['Ãƒâ€“DEME YÃƒâ€“NTEMLERÃ„Â° DETAYI'], ';');
            fputcsv($file, ['Nakit Ãƒâ€“deme (Toplam)', number_format($enhancedCashPayments->total_amount, 2) . ' Ã¢â€šÂº (' . $enhancedCashPayments->order_count . ' ÃƒÂ¶deme)'], ';');
            fputcsv($file, ['  - Normal Nakit Ãƒâ€“deme', number_format($enhancedCashPayments->normal_amount, 2) . ' Ã¢â€šÂº (' . $enhancedCashPayments->normal_count . ' sipariÃ…Å¸)'], ';');
            fputcsv($file, ['  - Split Nakit Ãƒâ€“deme', number_format($enhancedCashPayments->split_amount, 2) . ' Ã¢â€šÂº (' . $enhancedCashPayments->split_count . ' ÃƒÂ¶deme)'], ';');
            fputcsv($file, ['Kart Ãƒâ€“deme (Toplam)', number_format($enhancedCardPayments->total_amount, 2) . ' Ã¢â€šÂº (' . $enhancedCardPayments->order_count . ' ÃƒÂ¶deme)'], ';');
            fputcsv($file, ['  - Normal Kart Ãƒâ€“deme', number_format($enhancedCardPayments->normal_amount, 2) . ' Ã¢â€šÂº (' . $enhancedCardPayments->normal_count . ' sipariÃ…Å¸)'], ';');
            fputcsv($file, ['  - Split Kart Ãƒâ€“deme', number_format($enhancedCardPayments->split_amount, 2) . ' Ã¢â€šÂº (' . $enhancedCardPayments->split_count . ' ÃƒÂ¶deme)'], ';');
            fputcsv($file, ['Toplam Ãƒâ€“denen Tutar', number_format($enhancedCashPayments->total_amount + $enhancedCardPayments->total_amount, 2) . ' Ã¢â€šÂº'], ';');
            fputcsv($file, [''], ';'); // BoÃ…Å¸ satÃ„Â±r
            
            // Periyodik veriler baÃ…Å¸lÃ„Â±Ã„Å¸Ã„Â±
            $periodText = $period === 'daily' ? 'GÃƒÅ“NLÃƒÅ“K' : ($period === 'weekly' ? 'HAFTALIK' : 'AYLIK');
            fputcsv($file, [$periodText . ' SÃ„Â°PARÃ„Â°Ã…Â VERÃ„Â°LERÃ„Â°'], ';');
            fputcsv($file, ['Tarih', 'SipariÃ…Å¸ SayÃ„Â±sÃ„Â±', 'Toplam Gelir (Ã¢â€šÂº)', 'Ortalama SipariÃ…Å¸ (Ã¢â€šÂº)'], ';');
            
            foreach ($ordersData as $data) {
                fputcsv($file, [
                    $data->date,
                    $data->orders_count,
                    number_format($data->total_revenue, 2),
                    number_format($data->average_order, 2)
                ], ';');
            }
            
            fputcsv($file, [''], ';'); // BoÃ…Å¸ satÃ„Â±r
            
            // En ÃƒÂ§ok sipariÃ…Å¸ edilen ÃƒÂ¼rÃƒÂ¼nler
            fputcsv($file, ['EN Ãƒâ€¡OK SÃ„Â°PARÃ„Â°Ã…Â EDÃ„Â°LEN ÃƒÅ“RÃƒÅ“NLER'], ';');
            fputcsv($file, ['ÃƒÅ“rÃƒÂ¼n AdÃ„Â±', 'Toplam SipariÃ…Å¸', 'Toplam Gelir (Ã¢â€šÂº)'], ';');
            
            foreach ($topProducts as $product) {
                fputcsv($file, [
                    $product->name,
                    $product->total_ordered,
                    number_format($product->total_revenue, 2)
                ], ';');
            }
            
            fputcsv($file, [''], ';'); // BoÃ…Å¸ satÃ„Â±r
            
            // Masa performansÃ„Â±
            fputcsv($file, ['MASA PERFORMANSI'], ';');
            fputcsv($file, ['Masa AdÃ„Â±', 'SipariÃ…Å¸ SayÃ„Â±sÃ„Â±', 'Toplam Gelir (Ã¢â€šÂº)', 'Ortalama SipariÃ…Å¸ (Ã¢â€šÂº)'], ';');
            
            foreach ($tablePerformance as $table) {
                fputcsv($file, [
                    $table->table_name,
                    $table->orders_count,
                    number_format($table->total_revenue, 2),
                    number_format($table->average_order, 2)
                ], ';');
            }
            
            fputcsv($file, [''], ';'); // BoÃ…Å¸ satÃ„Â±r
            
            // Saatlik daÃ„Å¸Ã„Â±lÃ„Â±m
            fputcsv($file, ['SAATLÃ„Â°K SÃ„Â°PARÃ„Â°Ã…Â DAÃ„ÂILIMI'], ';');
            fputcsv($file, ['Saat', 'SipariÃ…Å¸ SayÃ„Â±sÃ„Â±', 'Toplam Gelir (Ã¢â€šÂº)'], ';');
            
            foreach ($hourlyOrders as $hourly) {
                fputcsv($file, [
                    $hourly->hour . ':00',
                    $hourly->orders_count,
                    number_format($hourly->total_revenue, 2)
                ], ';');
            }
            
            fputcsv($file, [''], ';'); // BoÃ…Å¸ satÃ„Â±r
            
            // DetaylÃ„Â± sipariÃ…Å¸ listesi
            fputcsv($file, ['DETAYLI SÃ„Â°PARÃ„Â°Ã…Â LÃ„Â°STESÃ„Â°'], ';');
            fputcsv($file, ['SipariÃ…Å¸ No', 'Tarih', 'Saat', 'Masa', 'Garson', 'Oturma SÃƒÂ¼resi', 'Durum', 'Ãƒâ€“deme Durumu', 'Ãƒâ€“deme YÃƒÂ¶ntemi', 'Toplam Tutar (Ã¢â€šÂº)', 'Notlar'], ';');
            
            foreach ($cafeOrders as $order) {
                $statusText = match($order->status) {
                    'pending' => 'Bekliyor',
                    'preparing' => 'HazÃ„Â±rlanÃ„Â±yor',
                    'ready' => 'HazÃ„Â±r',
                    'served' => 'Servis Edildi',
                    'cancelled' => 'Ã„Â°ptal',
                    default => 'Bilinmiyor'
                };
                
                $paymentMethodText = match($order->payment_method) {
                    'cash' => 'Nakit',
                    'card' => 'Kart',
                    default => 'BelirtilmemiÃ…Å¸'
                };
                
                fputcsv($file, [
                    $order->order_number,
                    $order->created_at->format('d.m.Y'),
                    $order->created_at->format('H:i'),
                    $order->table->name ?? 'Bilinmiyor',
                    $order->user->name ?? 'Bilinmiyor',
                    $order->formatted_duration,
                    $statusText,
                    $order->is_paid ? 'Ãƒâ€“dendi' : 'Ãƒâ€“denmedi',
                    $order->is_paid ? $paymentMethodText : '-',
                    number_format($order->total_amount, 2),
                    $order->notes ?? ''
                ], ';');
            }
            
            fputcsv($file, [''], ';'); // BoÃ…Å¸ satÃ„Â±r
            
            // SipariÃ…Å¸ detaylarÃ„Â±
            fputcsv($file, ['SÃ„Â°PARÃ„Â°Ã…Â DETAYLARI'], ';');
            fputcsv($file, ['SipariÃ…Å¸ No', 'ÃƒÅ“rÃƒÂ¼n AdÃ„Â±', 'Miktar', 'Birim Fiyat (Ã¢â€šÂº)', 'Toplam Fiyat (Ã¢â€šÂº)'], ';');
            
            foreach ($cafeOrders as $order) {
                foreach ($order->cafeOrderItems as $item) {
                    fputcsv($file, [
                        $order->order_number,
                        $item->product->name ?? 'Bilinmiyor',
                        $item->quantity,
                        number_format($item->unit_price, 2),
                        number_format($item->total_price, 2)
                    ], ';');
                }
            }
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function userActivities(Request $request)
    {
        $startDate = $request->start_date ? Carbon::parse($request->start_date)->startOfDay() : Carbon::now()->startOfMonth();
        $endDate = $request->end_date ? Carbon::parse($request->end_date)->endOfDay() : Carbon::now()->endOfMonth();
        $userId = $request->user_id;
        $action = $request->action;
        $systemType = $request->system_type; // Sistem tipi filtresi ekle
        $perPage = $request->per_page ?? 10;
        $perPage = max(5, min(50, $perPage)); // 5-50 arasÃ„Â± sÃ„Â±nÃ„Â±rla

        // Excel export kontrolÃƒÂ¼
        if ($request->has('export') && $request->export === 'excel') {
            return $this->exportUserActivitiesExcel($startDate, $endDate, $userId, $action, $systemType);
        }

        // Aktiviteleri getir
        $query = UserActivity::with('user')
            ->byDateRange($startDate, $endDate);

        if ($userId) {
            $query->where('user_id', $userId);
        }

        if ($action) {
            $query->where('action', $action);
        }

        // Sistem tipi filtresi ekle
        if ($systemType) {
            $query->where('system_type', $systemType);
        }

        // Sayfalama ile aktiviteleri al
        $activities = $query->orderBy('created_at', 'desc')
            ->paginate($perPage);
        
        // Arama parametrelerini sayfalama linklerine ekle
        $activities->appends($request->query());

        // Ãƒâ€“zet bilgiler iÃƒÂ§in ayrÃ„Â± query (sayfalama olmadan)
        $summaryQuery = UserActivity::byDateRange($startDate, $endDate);

        if ($userId) {
            $summaryQuery->where('user_id', $userId);
        }

        if ($action) {
            $summaryQuery->where('action', $action);
        }

        // Sistem tipi filtresi ÃƒÂ¶zet iÃƒÂ§in de ekle
        if ($systemType) {
            $summaryQuery->where('system_type', $systemType);
        }

        $summary = [
            'total' => $summaryQuery->count(),
            'create' => $summaryQuery->clone()->where('action', 'create')->count(),
            'update' => $summaryQuery->clone()->where('action', 'update')->count(),
            'delete' => $summaryQuery->clone()->where('action', 'delete')->count(),
        ];

        // KullanÃ„Â±cÃ„Â± listesi (filtre iÃƒÂ§in)
        $users = \App\Models\User::where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('reports.user-activities', compact(
            'activities', 
            'summary', 
            'users', 
            'startDate', 
            'endDate', 
            'userId', 
            'action',
            'systemType'
        ));
    }

    public function stockActivities(Request $request)
    {
        $request->merge(['system_type' => 'stock']);
        return $this->userActivities($request);
    }

    public function cafeActivities(Request $request)
    {
        
        $request->merge(['system_type' => 'cafe']);
        $startDate = $request->start_date ? Carbon::parse($request->start_date)->startOfDay() : Carbon::now()->startOfMonth();
        $endDate = $request->end_date ? Carbon::parse($request->end_date)->endOfDay() : Carbon::now()->endOfMonth();
        $userId = $request->user_id;
        $productId = $request->product_id;
        $perPage = $request->get('stats_per_page', 20);
        
        // Garson bazında ürün sipariş istatistikleri
        $waiterProductStats = DB::table('cafe_order_items')
            ->join('cafe_orders', 'cafe_order_items.cafe_order_id', '=', 'cafe_orders.id')
            ->join('users', 'cafe_orders.user_id', '=', 'users.id')
            ->join('products', 'cafe_order_items.product_id', '=', 'products.id')
            ->whereBetween('cafe_orders.created_at', [$startDate, $endDate])
            ->where('cafe_orders.status', '!=', CafeOrder::STATUS_CANCELLED)
            ->when($userId, function($query) use ($userId) {
                return $query->where('cafe_orders.user_id', $userId);
            })
            ->when($productId, function($query) use ($productId) {
                return $query->where('cafe_order_items.product_id', $productId);
            })
            ->select(
                'users.id as user_id',
                'users.name as user_name',
                'products.id as product_id',
                'products.name as product_name',
                DB::raw('SUM(cafe_order_items.quantity) as total_quantity'),
                DB::raw('COUNT(DISTINCT cafe_orders.id) as order_count'),
                DB::raw('SUM(cafe_order_items.total_price) as total_revenue'),
                DB::raw('MAX(cafe_orders.created_at) as last_order_date')
            )
            ->groupBy('users.id', 'users.name', 'products.id', 'products.name')
            ->orderBy('users.name')
            ->orderBy('total_quantity', 'desc')
            ->paginate($perPage, ['*'], 'stats_page');
        
        // Sayfalama parametrelerini ekle
        $waiterProductStats->appends($request->except('stats_page'));
        
        // Ürün listesi (filtre için)
        $products = \App\Models\Product::where('is_active', true)
            ->orderBy('name')
            ->get();
        
        // Normal aktivite verilerini al
        $result = $this->userActivities($request);
        
        // View'a ek veriler ekle
        return $result->with([
            'waiterProductStats' => $waiterProductStats,
            'products' => $products,
            'productId' => $productId
        ]);
    }
    public function cleanupActivities(Request $request)
    {
        // Sadece admin kullanÃ„Â±cÃ„Â±lar temizleme yapabilir
        if (!auth()->user()->isAdmin()) {
            return redirect()->back()->with('error', 'Bu iÃ…Å¸lem iÃƒÂ§in yetkiniz bulunmamaktadÃ„Â±r.');
        }

        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'action' => 'nullable|in:create,update,delete',
            'system_type' => 'nullable|in:stock,cafe'
        ]);

        $startDate = Carbon::parse($request->start_date)->startOfDay();
        $endDate = Carbon::parse($request->end_date)->endOfDay();
        $action = $request->action;
        $systemType = $request->system_type;

        // Silinecek aktiviteleri say
        $query = UserActivity::whereBetween('created_at', [$startDate, $endDate]);
        
        if ($action) {
            $query->where('action', $action);
        }
        
        if ($systemType) {
            $query->where('system_type', $systemType);
        }

        $count = $query->count();
        
        if ($count === 0) {
            return redirect()->back()->with('warning', 'SeÃƒÂ§ilen kriterlere uygun aktivite bulunamadÃ„Â±.');
        }

        // Aktiviteleri sil
        $deleted = $query->delete();

        // Sistem tipine gÃƒÂ¶re yÃƒÂ¶nlendirme URL'sini belirle
        $redirectRoute = 'reports.activities.stock'; // varsayÃ„Â±lan
        if ($systemType === 'cafe') {
            $redirectRoute = 'reports.activities.cafe';
        } elseif (!$systemType) {
            $redirectRoute = 'reports.activities.user';
        }

        // Bu temizleme iÃ…Å¸lemini logla
        \App\Traits\LogsActivity::logCustomActivity(
            $systemType ?? 'system',
            'cleanup',
            "Aktivite temizleme: {$deleted} adet aktivite silindi ({$startDate->format('d.m.Y')} - {$endDate->format('d.m.Y')})",
            'UserActivity',
            null
        );

        return redirect()->route($redirectRoute)->with('success', "{$deleted} adet aktivite baÃ…Å¸arÃ„Â±yla silindi.");
    }

    private function exportUserActivitiesExcel($startDate, $endDate, $userId, $action, $systemType)
    {
        // Aktiviteleri al
        $query = UserActivity::with('user')
            ->byDateRange($startDate, $endDate);

        if ($userId) {
            $query->where('user_id', $userId);
        }

        if ($action) {
            $query->where('action', $action);
        }

        if ($systemType) {
            $query->where('system_type', $systemType);
        }

        $activities = $query->orderBy('created_at', 'desc')->get();

        // Ãƒâ€“zet bilgileri hesapla
        $summary = [
            'total' => $activities->count(),
            'create' => $activities->where('action', 'create')->count(),
            'update' => $activities->where('action', 'update')->count(),
            'delete' => $activities->where('action', 'delete')->count(),
        ];

        // KullanÃ„Â±cÃ„Â± bazÃ„Â±nda ÃƒÂ¶zet
        $userSummary = $activities->groupBy('user_id')->map(function ($userActivities) {
            $user = $userActivities->first()->user;
            return [
                'user_name' => $user->name,
                'user_email' => $user->email,
                'total' => $userActivities->count(),
                'create' => $userActivities->where('action', 'create')->count(),
                'update' => $userActivities->where('action', 'update')->count(),
                'delete' => $userActivities->where('action', 'delete')->count(),
            ];
        });

        // Sistem tÃƒÂ¼rÃƒÂ¼ne gÃƒÂ¶re dosya adÃ„Â±
        $systemTypeText = match($systemType) {
            'stock' => 'stok_sistemi',
            'cafe' => 'kafe_sistemi',
            default => 'kullanici'
        };

        // CSV formatÃ„Â±nda Excel dosyasÃ„Â± oluÃ…Å¸tur
        $filename = $systemTypeText . '_aktiviteleri_' . $startDate->format('Y-m-d') . '_' . $endDate->format('Y-m-d') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0'
        ];

        $callback = function() use ($activities, $summary, $userSummary, $startDate, $endDate, $systemType, $userId, $action) {
            $file = fopen('php://output', 'w');
            
            // UTF-8 BOM ekle (TÃƒÂ¼rkÃƒÂ§e karakterler iÃƒÂ§in)
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // BaÃ…Å¸lÃ„Â±k bilgileri
            $systemTypeText = match($systemType) {
                'stock' => 'STOK SÃ„Â°STEMÃ„Â° AKTÃ„Â°VÃ„Â°TELERÃ„Â°',
                'cafe' => 'KAFE SÃ„Â°STEMÃ„Â° AKTÃ„Â°VÃ„Â°TELERÃ„Â°',
                default => 'KULLANICI AKTÃ„Â°VÃ„Â°TELERÃ„Â°'
            };
            
            fputcsv($file, [$systemTypeText], ';');
            fputcsv($file, ['Tarih AralÃ„Â±Ã„Å¸Ã„Â±: ' . $startDate->format('d.m.Y') . ' - ' . $endDate->format('d.m.Y')], ';');
            
            if ($userId) {
                $user = \App\Models\User::find($userId);
                fputcsv($file, ['KullanÃ„Â±cÃ„Â±: ' . ($user ? $user->name : 'Bilinmiyor')], ';');
            } else {
                fputcsv($file, ['KullanÃ„Â±cÃ„Â±: TÃƒÂ¼m KullanÃ„Â±cÃ„Â±lar'], ';');
            }
            
            if ($action) {
                $actionText = match($action) {
                    'create' => 'OluÃ…Å¸turma',
                    'update' => 'GÃƒÂ¼ncelleme',
                    'delete' => 'Silme',
                    default => 'Bilinmiyor'
                };
                fputcsv($file, ['Ã„Â°Ã…Å¸lem TÃƒÂ¼rÃƒÂ¼: ' . $actionText], ';');
            } else {
                fputcsv($file, ['Ã„Â°Ã…Å¸lem TÃƒÂ¼rÃƒÂ¼: TÃƒÂ¼m Ã„Â°Ã…Å¸lemler'], ';');
            }
            
            fputcsv($file, ['Rapor Tarihi: ' . now()->format('d.m.Y H:i')], ';');
            fputcsv($file, [''], ';'); // BoÃ…Å¸ satÃ„Â±r
            
            // Genel ÃƒÂ¶zet
            fputcsv($file, ['GENEL Ãƒâ€“ZET'], ';');
            fputcsv($file, ['Toplam Aktivite', $summary['total']], ';');
            fputcsv($file, ['OluÃ…Å¸turma Ã„Â°Ã…Å¸lemleri', $summary['create']], ';');
            fputcsv($file, ['GÃƒÂ¼ncelleme Ã„Â°Ã…Å¸lemleri', $summary['update']], ';');
            fputcsv($file, ['Silme Ã„Â°Ã…Å¸lemleri', $summary['delete']], ';');
            fputcsv($file, [''], ';'); // BoÃ…Å¸ satÃ„Â±r
            
            // KullanÃ„Â±cÃ„Â± bazÃ„Â±nda ÃƒÂ¶zet
            if ($userSummary->count() > 1) {
                fputcsv($file, ['KULLANICI BAZINDA Ãƒâ€“ZET'], ';');
                fputcsv($file, ['KullanÃ„Â±cÃ„Â±', 'E-posta', 'Toplam', 'OluÃ…Å¸turma', 'GÃƒÂ¼ncelleme', 'Silme'], ';');
                
                foreach ($userSummary as $userStat) {
                    fputcsv($file, [
                        $userStat['user_name'],
                        $userStat['user_email'],
                        $userStat['total'],
                        $userStat['create'],
                        $userStat['update'],
                        $userStat['delete']
                    ], ';');
                }
                
                fputcsv($file, [''], ';'); // BoÃ…Å¸ satÃ„Â±r
            }
            
            // DetaylÃ„Â± aktivite listesi
            fputcsv($file, ['DETAYLI AKTÃ„Â°VÃ„Â°TE LÃ„Â°STESÃ„Â°'], ';');
            fputcsv($file, ['Tarih', 'Saat', 'KullanÃ„Â±cÃ„Â±', 'E-posta', 'Ã„Â°Ã…Å¸lem', 'AÃƒÂ§Ã„Â±klama', 'IP Adresi', 'Cihaz TÃƒÂ¼rÃƒÂ¼', 'TarayÃ„Â±cÃ„Â±', 'Platform'], ';');
            
            foreach ($activities as $activity) {
                $actionText = match($activity->action) {
                    'create' => 'OluÃ…Å¸turma',
                    'update' => 'GÃƒÂ¼ncelleme',
                    'delete' => 'Silme',
                    default => 'Bilinmiyor'
                };
                
                fputcsv($file, [
                    $activity->created_at->format('d.m.Y'),
                    $activity->created_at->format('H:i:s'),
                    $activity->user->name ?? 'Bilinmiyor',
                    $activity->user->email ?? 'Bilinmiyor',
                    $actionText,
                    $activity->description ?? '',
                    $activity->ip_address ?? '',
                    $activity->device_type ?? '',
                    $activity->browser ?? '',
                    $activity->platform ?? ''
                ], ';');
            }
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function cash(Request $request)
    {
        // Tarih formatÃ„Â±nÃ„Â± dÃƒÂ¼zelt - saat bilgisini de dahil et
        $startDate = $request->start_date ? Carbon::parse($request->start_date)->startOfDay() : Carbon::now()->startOfMonth();
        $endDate = $request->end_date ? Carbon::parse($request->end_date)->endOfDay() : Carbon::now()->endOfMonth();
        $cashType = $request->cash_type ?? 'all'; // all, stock, cafe
        $transactionType = $request->transaction_type ?? 'all'; // all, income, expense, withdrawal

        // Sayfalama miktarÃ„Â±nÃ„Â± request'ten al, varsayÃ„Â±lan 10
        $perPage = $request->get('per_page', 10);
        
        // Sayfalama miktarÃ„Â±nÃ„Â± sÃ„Â±nÃ„Â±rla (5-50 arasÃ„Â±)
        $perPage = max(5, min(50, (int)$perPage));

        // Debug iÃƒÂ§in - geÃƒÂ§ici olarak ekleyelim
        \Log::info('Cash Report Debug:', [
            'start_date' => $startDate->format('Y-m-d H:i:s'),
            'end_date' => $endDate->format('Y-m-d H:i:s'),
            'cash_type' => $cashType,
            'transaction_type' => $transactionType,
            'per_page' => $perPage,
            'request_params' => $request->all()
        ]);

        // Excel export kontrolÃƒÂ¼
        if ($request->has('export') && $request->export === 'excel') {
            return $this->exportCashExcel($startDate, $endDate, $cashType, $transactionType);
        }

        // Kasa iÃ…Å¸lemlerini al - filtreleme ile
        $query = CashTransaction::with(['user'])
            ->whereBetween('created_at', [$startDate, $endDate]);

        if ($cashType !== 'all') {
            $query->where('cash_type', $cashType);
        }

        if ($transactionType !== 'all') {
            $query->where('transaction_type', $transactionType);
        }

        // Debug iÃƒÂ§in query'yi logla
        \Log::info('Cash Query SQL:', [
            'sql' => $query->toSql(),
            'bindings' => $query->getBindings()
        ]);

        $transactions = $query->orderBy('created_at', 'desc')->paginate($perPage);
        
        // Arama parametrelerini sayfalama linklerine ekle
        $transactions->appends($request->query());

        // Debug iÃƒÂ§in transaction sayÃ„Â±sÃ„Â±nÃ„Â± logla
        \Log::info('Transaction Count:', ['count' => $transactions->total()]);

        // Ãƒâ€“zet bilgileri hesapla - TÃƒÅ“M veriler ÃƒÂ¼zerinden (filtresiz)
        $allTransactionsQuery = CashTransaction::whereBetween('created_at', [$startDate, $endDate]);
        $allTransactions = $allTransactionsQuery->get();

        // Stok kasasÃ„Â± ÃƒÂ¶zeti (tÃƒÂ¼m veriler)
        $stockSummary = [
            'total_income' => $allTransactions->where('cash_type', 'stock')->where('transaction_type', 'income')->sum('amount'),
            'total_expense' => $allTransactions->where('cash_type', 'stock')->where('transaction_type', 'expense')->sum('amount'),
            'total_withdrawal' => $allTransactions->where('cash_type', 'stock')->where('transaction_type', 'withdrawal')->sum('amount'),
            'transaction_count' => $allTransactions->where('cash_type', 'stock')->count()
        ];
        $stockSummary['net_balance'] = $stockSummary['total_income'] - $stockSummary['total_expense'] - $stockSummary['total_withdrawal'];

        // Kafe kasasÃ„Â± ÃƒÂ¶zeti (tÃƒÂ¼m veriler)
        $cafeSummary = [
            'total_income' => $allTransactions->where('cash_type', 'cafe')->where('transaction_type', 'income')->sum('amount'),
            'total_expense' => $allTransactions->where('cash_type', 'cafe')->where('transaction_type', 'expense')->sum('amount'),
            'total_withdrawal' => $allTransactions->where('cash_type', 'cafe')->where('transaction_type', 'withdrawal')->sum('amount'),
            'transaction_count' => $allTransactions->where('cash_type', 'cafe')->count()
        ];
        $cafeSummary['net_balance'] = $cafeSummary['total_income'] - $cafeSummary['total_expense'] - $cafeSummary['total_withdrawal'];

        // Genel ÃƒÂ¶zet (tÃƒÂ¼m veriler)
        $generalSummary = [
            'total_income' => $allTransactions->where('transaction_type', 'income')->sum('amount'),
            'total_expense' => $allTransactions->where('transaction_type', 'expense')->sum('amount'),
            'total_withdrawal' => $allTransactions->where('transaction_type', 'withdrawal')->sum('amount'),
            'transaction_count' => $allTransactions->count()
        ];
        $generalSummary['net_balance'] = $generalSummary['total_income'] - $generalSummary['total_expense'] - $generalSummary['total_withdrawal'];

        // GÃƒÂ¼nlÃƒÂ¼k iÃ…Å¸lem daÃ„Å¸Ã„Â±lÃ„Â±mÃ„Â± - filtreleme ile
        $dailyTransactionsQuery = CashTransaction::selectRaw('
                DATE(created_at) as date,
                cash_type,
                transaction_type,
                SUM(amount) as total_amount,
                COUNT(*) as transaction_count
            ')
            ->whereBetween('created_at', [$startDate, $endDate]);

        if ($cashType !== 'all') {
            $dailyTransactionsQuery->where('cash_type', $cashType);
        }

        if ($transactionType !== 'all') {
            $dailyTransactionsQuery->where('transaction_type', $transactionType);
        }

        $dailyTransactions = $dailyTransactionsQuery
            ->groupBy('date', 'cash_type', 'transaction_type')
            ->orderBy('date', 'desc')
            ->get();

        // KullanÃ„Â±cÃ„Â± bazÃ„Â±nda iÃ…Å¸lem ÃƒÂ¶zeti - filtreleme ile
        $userSummaryQuery = CashTransaction::with('user')
            ->selectRaw('
                user_id,
                cash_type,
                transaction_type,
                SUM(amount) as total_amount,
                COUNT(*) as transaction_count
            ')
            ->whereBetween('created_at', [$startDate, $endDate]);

        if ($cashType !== 'all') {
            $userSummaryQuery->where('cash_type', $cashType);
        }

        if ($transactionType !== 'all') {
            $userSummaryQuery->where('transaction_type', $transactionType);
        }

        $userSummary = $userSummaryQuery
            ->groupBy('user_id', 'cash_type', 'transaction_type')
            ->orderBy('total_amount', 'desc')
            ->get();

        // Mevcut kasa bakiyeleri (FÃ„Â°LTRELEME Ã„Â°LE - Kasa Sistemi mantÃ„Â±Ã„Å¸Ã„Â±)
        // Stok kasasÃ„Â± - sadece seÃƒÂ§ilen tarih aralÃ„Â±Ã„Å¸Ã„Â±ndaki satÃ„Â±Ã…Å¸lar
        $existingStockCash = Sale::whereBetween('created_at', [$startDate, $endDate])
            ->sum('total_amount');
        
        // Kafe kasasÃ„Â± iÃƒÂ§in seÃƒÂ§ilen tarih aralÃ„Â±Ã„Å¸Ã„Â±ndaki sipariÃ…Å¸ler
        // Split payment'Ã„Â± olan sipariÃ…Å¸ ID'lerini al (tarih filtreli)
        $splitOrderIds = DB::table('cafe_order_payments')
            ->join('cafe_orders', 'cafe_order_payments.cafe_order_id', '=', 'cafe_orders.id')
            ->whereBetween('cafe_orders.created_at', [$startDate, $endDate])
            ->where('cafe_orders.status', '!=', CafeOrder::STATUS_CANCELLED)
            ->where('cafe_orders.is_paid', true)
            ->pluck('cafe_orders.id')
            ->unique();

        // Normal ÃƒÂ¶demeler - Split payment'Ã„Â± olanlarÃ„Â± Ãƒâ€¡IKAR ve final_amount kontrolÃƒÂ¼ yap (tarih filtreli)
        $normalCafeCash = CafeOrder::whereBetween('created_at', [$startDate, $endDate])
            ->where('is_paid', true)
            ->where('status', '!=', CafeOrder::STATUS_CANCELLED)
            ->whereNotIn('id', $splitOrderIds)
            ->get()
            ->sum(function($order) {
                return $order->final_amount > 0 ? $order->final_amount : $order->total_amount;
            });
            
        // Split ÃƒÂ¶demeler - TOPLAM TUTARI AL (tarih filtreli)
        $splitCafeCash = DB::table('cafe_order_payments')
            ->join('cafe_orders', 'cafe_order_payments.cafe_order_id', '=', 'cafe_orders.id')
            ->whereBetween('cafe_orders.created_at', [$startDate, $endDate])
            ->where('cafe_orders.status', '!=', CafeOrder::STATUS_CANCELLED)
            ->where('cafe_orders.is_paid', true)
            ->sum('cafe_order_payments.amount');
            
        $existingCafeCash = $normalCafeCash + $splitCafeCash;
        
        // Kasa iÃ…Å¸lemleri de tarih filtreli
        $stockTransactionBalance = CashTransaction::where('cash_type', 'stock')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('SUM(CASE WHEN transaction_type = "income" THEN amount ELSE -amount END) as balance')
            ->value('balance') ?? 0;
            
        $cafeTransactionBalance = CashTransaction::where('cash_type', 'cafe')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('SUM(CASE WHEN transaction_type = "income" THEN amount ELSE -amount END) as balance')
            ->value('balance') ?? 0;

        $currentBalances = [
            'stock_cash' => $existingStockCash + $stockTransactionBalance,
            'cafe_cash' => $existingCafeCash + $cafeTransactionBalance,
            'total_cash' => $existingStockCash + $existingCafeCash + $stockTransactionBalance + $cafeTransactionBalance
        ];

        return view('reports.cash', compact(
            'transactions',
            'stockSummary',
            'cafeSummary',
            'generalSummary',
            'dailyTransactions',
            'userSummary',
            'currentBalances',
            'startDate',
            'endDate',
            'cashType',
            'transactionType'
        ));
    }

    private function exportCashExcel($startDate, $endDate, $cashType, $transactionType)
    {
        // Kasa iÃ…Å¸lemlerini al
        $query = CashTransaction::with(['user'])
            ->whereBetween('created_at', [$startDate, $endDate]);

        if ($cashType !== 'all') {
            $query->where('cash_type', $cashType);
        }

        if ($transactionType !== 'all') {
            $query->where('transaction_type', $transactionType);
        }

        $transactions = $query->orderBy('created_at', 'desc')->get();

        // Ãƒâ€“zet bilgileri hesapla
        $allTransactions = CashTransaction::whereBetween('created_at', [$startDate, $endDate]);
        
        if ($cashType !== 'all') {
            $allTransactions->where('cash_type', $cashType);
        }

        $allTransactions = $allTransactions->get();
        
        // Stok kasasÃ„Â± ÃƒÂ¶zeti
        $stockSummary = [
            'total_income' => $allTransactions->where('cash_type', 'stock')->where('transaction_type', 'income')->sum('amount'),
            'total_expense' => $allTransactions->where('cash_type', 'stock')->where('transaction_type', 'expense')->sum('amount'),
            'total_withdrawal' => $allTransactions->where('cash_type', 'stock')->where('transaction_type', 'withdrawal')->sum('amount'),
            'transaction_count' => $allTransactions->where('cash_type', 'stock')->count()
        ];
        $stockSummary['net_balance'] = $stockSummary['total_income'] - $stockSummary['total_expense'] - $stockSummary['total_withdrawal'];

        // Kafe kasasÃ„Â± ÃƒÂ¶zeti
        $cafeSummary = [
            'total_income' => $allTransactions->where('cash_type', 'cafe')->where('transaction_type', 'income')->sum('amount'),
            'total_expense' => $allTransactions->where('cash_type', 'cafe')->where('transaction_type', 'expense')->sum('amount'),
            'total_withdrawal' => $allTransactions->where('cash_type', 'cafe')->where('transaction_type', 'withdrawal')->sum('amount'),
            'transaction_count' => $allTransactions->where('cash_type', 'cafe')->count()
        ];
        $cafeSummary['net_balance'] = $cafeSummary['total_income'] - $cafeSummary['total_expense'] - $cafeSummary['total_withdrawal'];

        // KullanÃ„Â±cÃ„Â± bazÃ„Â±nda ÃƒÂ¶zet
        $userSummary = CashTransaction::with('user')
            ->selectRaw('
                user_id,
                cash_type,
                transaction_type,
                SUM(amount) as total_amount,
                COUNT(*) as transaction_count
            ')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->when($cashType !== 'all', function($query) use ($cashType) {
                return $query->where('cash_type', $cashType);
            })
            ->when($transactionType !== 'all', function($query) use ($transactionType) {
                return $query->where('transaction_type', $transactionType);
            })
            ->groupBy('user_id', 'cash_type', 'transaction_type')
            ->orderBy('total_amount', 'desc')
            ->get();

        // CSV formatÃ„Â±nda Excel dosyasÃ„Â± oluÃ…Å¸tur
        $filename = 'kasa_raporu_' . $startDate->format('Y-m-d') . '_' . $endDate->format('Y-m-d') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0'
        ];

        $callback = function() use ($transactions, $stockSummary, $cafeSummary, $userSummary, $startDate, $endDate, $cashType, $transactionType) {
            $file = fopen('php://output', 'w');
            
            // UTF-8 BOM ekle (TÃƒÂ¼rkÃƒÂ§e karakterler iÃƒÂ§in)
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // BaÃ…Å¸lÃ„Â±k bilgileri
            fputcsv($file, ['KASA RAPORU'], ';');
            fputcsv($file, ['Tarih AralÃ„Â±Ã„Å¸Ã„Â±: ' . $startDateTime->format('d.m.Y') . ' - ' . $endDateTime->format('d.m.Y')], ';');
            fputcsv($file, ['Saat AralÃ„Â±Ã„Å¸Ã„Â±: ' . $startDateTime->format('H:i') . ' - ' . $endDateTime->format('H:i')], ';');
            fputcsv($file, ['Kasa TÃƒÂ¼rÃƒÂ¼: ' . ($cashType === 'all' ? 'TÃƒÂ¼mÃƒÂ¼' : ($cashType === 'stock' ? 'Stok KasasÃ„Â±' : 'Kafe KasasÃ„Â±'))], ';');
            fputcsv($file, ['Ã„Â°Ã…Å¸lem TÃƒÂ¼rÃƒÂ¼: ' . ($transactionType === 'all' ? 'TÃƒÂ¼mÃƒÂ¼' : ($transactionType === 'income' ? 'Gelir' : ($transactionType === 'expense' ? 'Gider' : 'Para Ãƒâ€¡ekme')))], ';');
            fputcsv($file, ['Rapor Tarihi: ' . now()->format('d.m.Y H:i')], ';');
            fputcsv($file, [''], ';'); // BoÃ…Å¸ satÃ„Â±r
            
            // Mevcut kasa bakiyeleri
            fputcsv($file, ['MEVCUT KASA BAKÃ„Â°YELERÃ„Â°'], ';');
            
            // Stok kasasÃ„Â± mevcut bakiye
            $existingStockCash = \App\Models\Sale::where('payment_method', 'cash')->sum('total_amount');
            $stockTransactionBalance = \App\Models\CashTransaction::where('cash_type', 'stock')
                ->selectRaw('SUM(CASE WHEN transaction_type = "income" THEN amount ELSE -amount END) as balance')
                ->value('balance') ?? 0;
            $totalStockCash = $existingStockCash + $stockTransactionBalance;
            
            // Kafe kasasÃ„Â± mevcut bakiye (normal + split)
            $normalCafeCash = \App\Models\CafeOrder::where('payment_method', 'cash')
                ->where('is_paid', true)
                ->sum('total_amount');
                
            $splitCafeCash = \Illuminate\Support\Facades\DB::table('cafe_order_payments')
                ->join('cafe_orders', 'cafe_order_payments.cafe_order_id', '=', 'cafe_orders.id')
                ->where('cafe_orders.is_paid', true)
                ->where('cafe_orders.status', '!=', \App\Models\CafeOrder::STATUS_CANCELLED)
                ->where('cafe_order_payments.payment_method', 'cash')
                ->sum('cafe_order_payments.amount');
                
            $existingCafeCash = $normalCafeCash + $splitCafeCash;
            $cafeTransactionBalance = \App\Models\CashTransaction::where('cash_type', 'cafe')
                ->selectRaw('SUM(CASE WHEN transaction_type = "income" THEN amount ELSE -amount END) as balance')
                ->value('balance') ?? 0;
            $totalCafeCash = $existingCafeCash + $cafeTransactionBalance;
            
            fputcsv($file, ['Stok KasasÃ„Â± Mevcut Bakiye', number_format($totalStockCash, 2) . ' Ã¢â€šÂº'], ';');
            fputcsv($file, ['  - SatÃ„Â±Ã…Å¸lardan Gelen Nakit', number_format($existingStockCash, 2) . ' Ã¢â€šÂº'], ';');
            fputcsv($file, ['  - Kasa Ã„Â°Ã…Å¸lemlerinden Bakiye', number_format($stockTransactionBalance, 2) . ' Ã¢â€šÂº'], ';');
            fputcsv($file, ['Kafe KasasÃ„Â± Mevcut Bakiye', number_format($totalCafeCash, 2) . ' Ã¢â€šÂº'], ';');
            fputcsv($file, ['  - Normal Nakit Ãƒâ€“demeler', number_format($normalCafeCash, 2) . ' Ã¢â€šÂº'], ';');
            fputcsv($file, ['  - Split Nakit Ãƒâ€“demeler', number_format($splitCafeCash, 2) . ' Ã¢â€šÂº'], ';');
            fputcsv($file, ['  - Kasa Ã„Â°Ã…Å¸lemlerinden Bakiye', number_format($cafeTransactionBalance, 2) . ' Ã¢â€šÂº'], ';');
            fputcsv($file, ['Toplam Kasa Bakiyesi', number_format($totalStockCash + $totalCafeCash, 2) . ' Ã¢â€šÂº'], ';');
            fputcsv($file, [''], ';'); // BoÃ…Å¸ satÃ„Â±r
            
            // Stok kasasÃ„Â± ÃƒÂ¶zeti
            fputcsv($file, ['STOK KASASI Ãƒâ€“ZETÃ„Â°'], ';');
            fputcsv($file, ['Toplam Gelir', number_format($stockSummary['total_income'], 2) . ' Ã¢â€šÂº'], ';');
            fputcsv($file, ['Toplam Gider', number_format($stockSummary['total_expense'], 2) . ' Ã¢â€šÂº'], ';');
            fputcsv($file, ['Toplam Para Ãƒâ€¡ekme', number_format($stockSummary['total_withdrawal'], 2) . ' Ã¢â€šÂº'], ';');
            fputcsv($file, ['Net Bakiye', number_format($stockSummary['net_balance'], 2) . ' Ã¢â€šÂº'], ';');
            fputcsv($file, ['Ã„Â°Ã…Å¸lem SayÃ„Â±sÃ„Â±', $stockSummary['transaction_count']], ';');
            fputcsv($file, [''], ';'); // BoÃ…Å¸ satÃ„Â±r
            
            // Kafe kasasÃ„Â± ÃƒÂ¶zeti
            fputcsv($file, ['KAFE KASASI Ãƒâ€“ZETÃ„Â°'], ';');
            fputcsv($file, ['Toplam Gelir', number_format($cafeSummary['total_income'], 2) . ' Ã¢â€šÂº'], ';');
            fputcsv($file, ['Toplam Gider', number_format($cafeSummary['total_expense'], 2) . ' Ã¢â€šÂº'], ';');
            fputcsv($file, ['Toplam Para Ãƒâ€¡ekme', number_format($cafeSummary['total_withdrawal'], 2) . ' Ã¢â€šÂº'], ';');
            fputcsv($file, ['Net Bakiye', number_format($cafeSummary['net_balance'], 2) . ' Ã¢â€šÂº'], ';');
            fputcsv($file, ['Ã„Â°Ã…Å¸lem SayÃ„Â±sÃ„Â±', $cafeSummary['transaction_count']], ';');
            fputcsv($file, [''], ';'); // BoÃ…Å¸ satÃ„Â±r
            
            // KullanÃ„Â±cÃ„Â± bazÃ„Â±nda ÃƒÂ¶zet
            fputcsv($file, ['KULLANICI BAZINDA Ã„Â°Ã…ÂLEM Ãƒâ€“ZETÃ„Â°'], ';');
            fputcsv($file, ['KullanÃ„Â±cÃ„Â±', 'Kasa TÃƒÂ¼rÃƒÂ¼', 'Ã„Â°Ã…Å¸lem TÃƒÂ¼rÃƒÂ¼', 'Toplam Tutar (Ã¢â€šÂº)', 'Ã„Â°Ã…Å¸lem SayÃ„Â±sÃ„Â±'], ';');
            
            foreach ($userSummary as $user) {
                $cashTypeText = $user->cash_type === 'stock' ? 'Stok KasasÃ„Â±' : 'Kafe KasasÃ„Â±';
                $transactionTypeText = match($user->transaction_type) {
                    'income' => 'Gelir',
                    'expense' => 'Gider',
                    'withdrawal' => 'Para Ãƒâ€¡ekme',
                    default => 'Bilinmiyor'
                };
                
                fputcsv($file, [
                    $user->user->name ?? 'Bilinmiyor',
                    $cashTypeText,
                    $transactionTypeText,
                    number_format($user->total_amount, 2),
                    $user->transaction_count
                ], ';');
            }
            
            fputcsv($file, [''], ';'); // BoÃ…Å¸ satÃ„Â±r
            
            // DetaylÃ„Â± iÃ…Å¸lem listesi
            fputcsv($file, ['DETAYLI Ã„Â°Ã…ÂLEM LÃ„Â°STESÃ„Â°'], ';');
            fputcsv($file, ['Tarih', 'Saat', 'KullanÃ„Â±cÃ„Â±', 'Kasa TÃƒÂ¼rÃƒÂ¼', 'Ã„Â°Ã…Å¸lem TÃƒÂ¼rÃƒÂ¼', 'Tutar (Ã¢â€šÂº)', 'AÃƒÂ§Ã„Â±klama', 'Notlar'], ';');
            
            foreach ($transactions as $transaction) {
                $cashTypeText = $transaction->cash_type === 'stock' ? 'Stok KasasÃ„Â±' : 'Kafe KasasÃ„Â±';
                $transactionTypeText = match($transaction->transaction_type) {
                    'income' => 'Gelir',
                    'expense' => 'Gider',
                    'withdrawal' => 'Para Ãƒâ€¡ekme',
                    default => 'Bilinmiyor'
                };
                
                fputcsv($file, [
                    $transaction->created_at->format('d.m.Y'),
                    $transaction->created_at->format('H:i'),
                    $transaction->user->name ?? 'Bilinmiyor',
                    $cashTypeText,
                    $transactionTypeText,
                    number_format($transaction->amount, 2),
                    $transaction->description ?? '',
                    $transaction->notes ?? ''
                ], ';');
            }
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
    
    private function calculateDurationStats($orders)
    {
        $durations = [];
        $durationRanges = [
            '0-30' => 0,
            '31-60' => 0,
            '61-120' => 0,
            '121-180' => 0,
            '180+' => 0
        ];

        foreach ($orders as $order) {
            $duration = $order->duration_in_minutes;
            if ($duration !== null) {
                $durations[] = $duration;
                
                // SÃƒÂ¼re aralÃ„Â±klarÃ„Â±na gÃƒÂ¶re grupla
                if ($duration <= 30) {
                    $durationRanges['0-30']++;
                } elseif ($duration <= 60) {
                    $durationRanges['31-60']++;
                } elseif ($duration <= 120) {
                    $durationRanges['61-120']++;
                } elseif ($duration <= 180) {
                    $durationRanges['121-180']++;
                } else {
                    $durationRanges['180+']++;
                }
            }
        }

        return [
            'average_duration' => count($durations) > 0 ? round(array_sum($durations) / count($durations), 1) : 0,
            'min_duration_minutes' => count($durations) > 0 ? min($durations) : 0,
            'max_duration_minutes' => count($durations) > 0 ? max($durations) : 0,
            'total_analyzed_orders' => count($durations),
            'total_valid_orders' => count($durations),
            'duration_ranges' => $durationRanges
        ];
    }

    private function calculateOccupancyStats($orders)
    {
        $hourlyOccupancy = [];
        $weekdayOrders = 0;
        $weekendOrders = 0;
        $totalTables = Table::count();

        // Saatlik yoÃ„Å¸unluk hesapla
        for ($hour = 0; $hour < 24; $hour++) {
            $hourlyOccupancy[$hour] = 0;
        }

        foreach ($orders as $order) {
            $hour = $order->created_at->hour;
            $hourlyOccupancy[$hour]++;

            // Hafta iÃƒÂ§i/hafta sonu daÃ„Å¸Ã„Â±lÃ„Â±mÃ„Â±
            $dayOfWeek = $order->created_at->dayOfWeek;
            if ($dayOfWeek >= 1 && $dayOfWeek <= 5) { // Pazartesi-Cuma
                $weekdayOrders++;
            } else { // Cumartesi-Pazar
                $weekendOrders++;
            }
        }

        // En yoÃ„Å¸un saatleri bul
        $peakHours = [];
        $maxOrders = max($hourlyOccupancy);
        foreach ($hourlyOccupancy as $hour => $orderCount) {
            if ($orderCount === $maxOrders && $maxOrders > 0) {
                $peakHours[] = $hour . ':00';
            }
        }

        // Ortalama doluluk oranÃ„Â± hesapla (basit yaklaÃ…Å¸Ã„Â±m)
        $totalOrders = count($orders);
        $averageOccupancy = $totalTables > 0 ? min(100, ($totalOrders / ($totalTables * 24)) * 100) : 0;

        return [
            'hourly_occupancy' => $hourlyOccupancy,
            'peak_hours' => $peakHours,
            'average_occupancy_percentage' => round($averageOccupancy, 1),
            'total_tables' => $totalTables,
            'weekday_orders' => $weekdayOrders,
            'weekend_orders' => $weekendOrders,
            'weekday_percentage' => $totalOrders > 0 ? round(($weekdayOrders / $totalOrders) * 100, 1) : 0,
            'weekend_percentage' => $totalOrders > 0 ? round(($weekendOrders / $totalOrders) * 100, 1) : 0
        ];
    }

    public function dailyThermalReport(Request $request)
{
    // BaÃ…Å¸langÃ„Â±ÃƒÂ§ ve bitiÃ…Å¸ tarihlerini al
    $startDate = $request->get('start_date', now()->format('Y-m-d'));
    $endDate = $request->get('end_date', $startDate); // BitiÃ…Å¸ tarihi yoksa baÃ…Å¸langÃ„Â±ÃƒÂ§ tarihini kullan
    
    // Saat parametrelerini al
    $startTime = $request->get('start_time', '00:00');
    $endTime = $request->get('end_time', '23:59');
    
    // BaÃ…Å¸langÃ„Â±ÃƒÂ§ ve bitiÃ…Å¸ tarih-saatleri
    $startDateTime = Carbon::parse($startDate . ' ' . $startTime . ':00');
    $endDateTime = Carbon::parse($endDate . ' ' . $endTime . ':59');
    
    // KAFE RAPORLARIYLA TAMAMEN AYNI MANTIK - SADECE SPLIT PAYMENTS
    
    // Split payment'larÃ„Â± ÃƒÂ¶deme yÃƒÂ¶ntemine gÃƒÂ¶re ayÃ„Â±r
    $splitCashPayments = DB::table('cafe_order_payments')
        ->join('cafe_orders', 'cafe_order_payments.cafe_order_id', '=', 'cafe_orders.id')
        ->whereBetween('cafe_orders.created_at', [$startDateTime, $endDateTime])
        ->where('cafe_orders.status', '!=', CafeOrder::STATUS_CANCELLED)
        ->where('cafe_orders.is_paid', true)
        ->where('cafe_order_payments.payment_method', 'cash')
        ->selectRaw('COUNT(cafe_order_payments.id) as payment_count, COALESCE(SUM(cafe_order_payments.amount), 0) as total_amount')
        ->first();
        
    $splitCardPayments = DB::table('cafe_order_payments')
        ->join('cafe_orders', 'cafe_order_payments.cafe_order_id', '=', 'cafe_orders.id')
        ->whereBetween('cafe_orders.created_at', [$startDateTime, $endDateTime])
        ->where('cafe_orders.status', '!=', CafeOrder::STATUS_CANCELLED)
        ->where('cafe_orders.is_paid', true)
        ->where('cafe_order_payments.payment_method', 'card')
        ->selectRaw('COUNT(cafe_order_payments.id) as payment_count, COALESCE(SUM(cafe_order_payments.amount), 0) as total_amount')
        ->first();
        
    $splitCashPayments = $splitCashPayments ?: (object) ['payment_count' => 0, 'total_amount' => 0];
    $splitCardPayments = $splitCardPayments ?: (object) ['payment_count' => 0, 'total_amount' => 0];
    
    // Toplam hesaplama - SADECE SPLIT PAYMENTS (Kafe raporlarÃ„Â±yla aynÃ„Â±)
    $totalCash = $splitCashPayments->total_amount ?? 0;
    $totalCard = $splitCardPayments->total_amount ?? 0;
    $totalRevenue = $totalCash + $totalCard;
    
    // DiÃ„Å¸er bilgiler
    $paidOrders = CafeOrder::whereBetween('created_at', [$startDateTime, $endDateTime])
        ->where('status', '!=', CafeOrder::STATUS_CANCELLED)
        ->where('is_paid', true)
        ->get();
        
    $totalOrders = $paidOrders->count();
    $totalDiscountAmount = $paidOrders->sum('discount_amount');
    $totalOriginalAmount = $paidOrders->sum('total_amount');
    
    // Termal yazÃ„Â±cÃ„Â± iÃƒÂ§in response
    $thermalData = [
        'start_date' => $startDate,
        'end_date' => $endDate,
        'formatted_start_date' => Carbon::parse($startDate)->format('d.m.Y'),
        'formatted_end_date' => Carbon::parse($endDate)->format('d.m.Y'),
        'start_time' => $startTime,
        'end_time' => $endTime,
        'total_orders' => $totalOrders,
        'total_revenue' => $totalRevenue,
        'total_discount' => $totalDiscountAmount,
        'total_original' => $totalOriginalAmount,
        'total_cash' => $totalCash,
        'total_card' => $totalCard
    ];
    
    return response()->view('reports.thermal-daily', $thermalData)
        ->header('Content-Type', 'text/plain; charset=utf-8');
}
    

}
