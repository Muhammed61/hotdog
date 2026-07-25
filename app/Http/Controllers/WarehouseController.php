<?php

namespace App\Http\Controllers;

use App\Models\WarehouseProduct;
use App\Models\WarehouseStockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class WarehouseController extends Controller
{
    public function index(Request $request)
    {
        $query = WarehouseProduct::where('is_active', true);

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $products = $query
            ->orderBy('name')
            ->paginate(20)
            ->appends($request->only('search'));

        $totalProducts = WarehouseProduct::where('is_active', true)->count();
        $lowStockProducts = WarehouseProduct::where('is_active', true)
            ->whereRaw('current_stock <= min_stock_level')
            ->count();
        $totalInQuantity = WarehouseStockMovement::where('type', 'in')->sum('quantity');
        $totalOutQuantity = WarehouseStockMovement::where('type', 'out')->sum('quantity');

        // AJAX istekleri için sadece veri döndür
        if ($request->ajax()) {
            $items = $products->map(function ($p) {
                return [
                    'id' => $p->id,
                    'name' => $p->name,
                    'description' => $p->description,
                    'current_stock' => $p->current_stock,
                    'min_stock_level' => $p->min_stock_level,
                    'is_low_stock' => $p->isLowStock(),
                ];
            })->values();

            return response()->json([
                'success' => true,
                'items' => $items,
                'total' => $products->total(),
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
                'per_page' => $products->perPage(),
                'has_pages' => $products->hasPages(),
                'path' => $products->path(),
                'prev_url' => $products->appends($request->query())->previousPageUrl(),
                'next_url' => $products->appends($request->query())->nextPageUrl(),
            ]);
        }

        return view('warehouse.index', compact(
            'products',
            'totalProducts',
            'lowStockProducts',
            'totalInQuantity',
            'totalOutQuantity'
        ));
    }

    public function createProduct()
    {
        return view('warehouse.create-product');
    }

    public function storeProduct(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:warehouse_products,name',
            'description' => 'nullable|string',
            'initial_stock' => 'required|integer|min:0',
            'min_stock_level' => 'required|integer|min:0'
        ]);

        DB::beginTransaction();
        try {
            $product = WarehouseProduct::create([
                'name' => $request->name,
                'description' => $request->description,
                'initial_stock' => $request->initial_stock,
                'current_stock' => $request->initial_stock,
                'min_stock_level' => $request->min_stock_level
            ]);

            // Başlangıç stoku için hareket kaydı oluştur
            if ($request->initial_stock > 0) {
                WarehouseStockMovement::create([
                    'warehouse_product_id' => $product->id,
                    'type' => 'in',
                    'quantity' => $request->initial_stock,
                    'reason' => 'Başlangıç stoku',
                    'user_id' => Auth::id()
                ]);
            }

            DB::commit();
            return redirect()->route('warehouse.index')->with('success', 'Ürün başarıyla eklendi.');

        } catch (\Exception $e) {
            DB::rollback();
            return back()->with('error', 'Ürün eklenirken bir hata oluştu: ' . $e->getMessage())->withInput();
        }
    }

    public function create()
    {
        $products = WarehouseProduct::where('is_active', true)->orderBy('name')->get();
        return view('warehouse.create', compact('products'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'warehouse_product_id' => 'required|exists:warehouse_products,id',
            'type' => 'required|in:in,out',
            'quantity' => 'required|integer|min:1',
            'reason' => 'required|string|max:255'
        ]);

        $product = WarehouseProduct::find($request->warehouse_product_id);

        // Çıkış işlemi için stok kontrolü
        if ($request->type === 'out' && $product->current_stock < $request->quantity) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Yetersiz stok! Mevcut stok: ' . $product->current_stock
                ], 422);
            }
            return back()->with('error', 'Yetersiz stok! Mevcut stok: ' . $product->current_stock)->withInput();
        }

        DB::beginTransaction();
        try {
            // Stok hareketi kaydet
            WarehouseStockMovement::create([
                'warehouse_product_id' => $request->warehouse_product_id,
                'type' => $request->type,
                'quantity' => $request->quantity,
                'reason' => $request->reason,
                'user_id' => Auth::id()
            ]);

            // Ürün stokunu güncelle
            if ($request->type === 'in') {
                $product->increment('current_stock', $request->quantity);
                $message = $request->quantity . ' adet ' . $product->name . ' depoya giriş yapıldı.';
            } else {
                $product->decrement('current_stock', $request->quantity);
                $message = $request->quantity . ' adet ' . $product->name . ' depodan çıkış yapıldı.';
            }

            DB::commit();
            
            // AJAX isteği ise JSON response döndür
            if ($request->expectsJson()) {
                // Güncellenmiş ürünü tekrar yükle
                $product->refresh();
                
                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'new_stock' => $product->current_stock,
                    'product_id' => $product->id
                ]);
            }
            
            return redirect()->route('warehouse.index')->with('success', $message);

        } catch (\Exception $e) {
            DB::rollback();
            
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'İşlem sırasında bir hata oluştu: ' . $e->getMessage()
                ], 500);
            }
            
            return back()->with('error', 'İşlem sırasında bir hata oluştu: ' . $e->getMessage())->withInput();
        }
    }

    public function movements(Request $request)
    {
        $query = WarehouseStockMovement::with(['warehouseProduct', 'user']);

        // Tarih filtreleri
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Ürün filtresi
        if ($request->filled('warehouse_product_id')) {
            $query->where('warehouse_product_id', $request->warehouse_product_id);
        }

        // Hareket tipi filtresi
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        $movements = $query->orderBy('created_at', 'desc')->paginate(20);

        // Filtreleme için ürünleri al
        $products = WarehouseProduct::where('is_active', true)->orderBy('name')->get();

        // Özet bilgiler (filtrelere göre)
        $totalIn = WarehouseStockMovement::where('type', 'in')
            ->when($request->filled('date_from'), function($q) use ($request) {
                return $q->whereDate('created_at', '>=', $request->date_from);
            })
            ->when($request->filled('date_to'), function($q) use ($request) {
                return $q->whereDate('created_at', '<=', $request->date_to);
            })
            ->when($request->filled('warehouse_product_id'), function($q) use ($request) {
                return $q->where('warehouse_product_id', $request->warehouse_product_id);
            })
            ->sum('quantity');

        $totalOut = WarehouseStockMovement::where('type', 'out')
            ->when($request->filled('date_from'), function($q) use ($request) {
                return $q->whereDate('created_at', '>=', $request->date_from);
            })
            ->when($request->filled('date_to'), function($q) use ($request) {
                return $q->whereDate('created_at', '<=', $request->date_to);
            })
            ->when($request->filled('warehouse_product_id'), function($q) use ($request) {
                return $q->where('warehouse_product_id', $request->warehouse_product_id);
            })
            ->sum('quantity');

        return view('warehouse.movements', compact('movements', 'products', 'totalIn', 'totalOut'));
    }

    public function reports(Request $request)
    {
        // Temel sorgu
        $query = WarehouseStockMovement::with(['warehouseProduct', 'user']);

        // Tarih filtreleri
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Ürün filtresi
        if ($request->filled('warehouse_product_id')) {
            $query->where('warehouse_product_id', $request->warehouse_product_id);
        }

        // Hareket tipi filtresi
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        $movements = $query->orderBy('created_at', 'desc')->paginate(20);

        // Filtreleme için veriler
        $products = WarehouseProduct::where('is_active', true)->orderBy('name')->get();
        $categories = collect(); // Boş collection - WarehouseProduct'ta kategori sistemi yok

        // Özet bilgiler (filtrelere göre)
        $totalIn = WarehouseStockMovement::where('type', 'in')
            ->when($request->filled('date_from'), function($q) use ($request) {
                return $q->whereDate('created_at', '>=', $request->date_from);
            })
            ->when($request->filled('date_to'), function($q) use ($request) {
                return $q->whereDate('created_at', '<=', $request->date_to);
            })
            ->when($request->filled('warehouse_product_id'), function($q) use ($request) {
                return $q->where('warehouse_product_id', $request->warehouse_product_id);
            })
            ->sum('quantity');

        $totalOut = WarehouseStockMovement::where('type', 'out')
            ->when($request->filled('date_from'), function($q) use ($request) {
                return $q->whereDate('created_at', '>=', $request->date_from);
            })
            ->when($request->filled('date_to'), function($q) use ($request) {
                return $q->whereDate('created_at', '<=', $request->date_to);
            })
            ->when($request->filled('warehouse_product_id'), function($q) use ($request) {
                return $q->where('warehouse_product_id', $request->warehouse_product_id);
            })
            ->sum('quantity');

        // Ürün bazında özet
        $productSummary = WarehouseStockMovement::with('warehouseProduct')
            ->when($request->filled('date_from'), function($q) use ($request) {
                return $q->whereDate('created_at', '>=', $request->date_from);
            })
            ->when($request->filled('date_to'), function($q) use ($request) {
                return $q->whereDate('created_at', '<=', $request->date_to);
            })
            ->when($request->filled('warehouse_product_id'), function($q) use ($request) {
                return $q->where('warehouse_product_id', $request->warehouse_product_id);
            })
            ->selectRaw('warehouse_product_id, 
                        SUM(CASE WHEN type = "in" THEN quantity ELSE 0 END) as total_in,
                        SUM(CASE WHEN type = "out" THEN quantity ELSE 0 END) as total_out')
            ->groupBy('warehouse_product_id')
            ->having('total_in', '>', 0)
            ->orHaving('total_out', '>', 0)
            ->get();

        return view('warehouse.reports', compact(
            'movements', 
            'products', 
            'categories',
            'totalIn', 
            'totalOut', 
            'productSummary'
        ));
    }
    
    // Ürün silme metodu
    public function destroyProduct($id)
    {
        $product = WarehouseProduct::findOrFail($id);
        $productName = $product->name;
        
        try {
            $product->delete();
            return redirect()->route('warehouse.index')->with('success', "\"$productName\" ürünü başarıyla silindi.");
        } catch (\Exception $e) {
            return redirect()->route('warehouse.index')->with('error', "Ürün silinemedi: " . $e->getMessage());
        }
    }
}