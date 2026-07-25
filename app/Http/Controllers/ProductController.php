<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Category;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::with('category');

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('barcode', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('low_stock')) {
            $query->whereRaw('stock_quantity <= min_stock_level');
        }

        $products = $query->paginate(15);
        $categories = Category::where('is_active', true)->get();

        return view('products.index', compact('products', 'categories'));
    }

    public function create()
    {
        $categories = Category::where('is_active', true)->get();
        return view('products.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'purchase_price' => 'required|numeric|min:0',
            'sale_price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'min_stock' => 'required|integer|min:0',
            'description' => 'nullable|string',
            'is_active' => 'nullable|boolean'
        ]);

        // Form verilerini model alanlarına uygun şekilde dönüştür
        $data = [
            'name' => $request->name,
            'category_id' => $request->category_id,
            'purchase_price' => $request->purchase_price,
            'sale_price' => $request->sale_price,
            'stock_quantity' => $request->stock,
            'min_stock_level' => $request->min_stock,
            'description' => $request->description,
            'is_active' => $request->has('is_active') ? true : false,
            'unit' => 'adet' // Varsayılan birim
        ];

        $product = Product::create($data);

        // İlk stok girişi
        if ($product->stock_quantity > 0) {
            StockMovement::create([
                'product_id' => $product->id,
                'type' => 'in',
                'quantity' => $product->stock_quantity,
                'unit_price' => $product->purchase_price,
                'reason' => 'İlk stok girişi',
                'user_id' => 1 // Şimdilik sabit user_id
            ]);
        }

        return redirect()->route('products.index')->with('success', 'Ürün başarıyla oluşturuldu.');
    }

    public function update(Request $request, Product $product)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'purchase_price' => 'required|numeric|min:0',
            'sale_price' => 'required|numeric|min:0',
            'min_stock' => 'required|integer|min:0',
            'description' => 'nullable|string',
            'is_active' => 'nullable|boolean'
        ]);

        $data = [
            'name' => $request->name,
            'category_id' => $request->category_id,
            'purchase_price' => $request->purchase_price,
            'sale_price' => $request->sale_price,
            'min_stock_level' => $request->min_stock,
            'description' => $request->description,
            'is_active' => $request->has('is_active') ? true : false
        ];

        $product->update($data);

        return redirect()->route('products.index')->with('success', 'Ürün başarıyla güncellendi.');
    }

    public function show(Product $product)
    {
        $product->load(['category', 'stockMovements']);
        $stockMovements = $product->stockMovements()->orderBy('created_at', 'desc')->paginate(10);
        
        return view('products.show', compact('product', 'stockMovements'));
    }

    public function edit(Product $product)
    {
        $categories = Category::where('is_active', true)->get();
        return view('products.edit', compact('product', 'categories'));
    }

    public function destroy(Product $product)
    {
        if ($product->saleItems()->count() > 0) {
            return redirect()->route('products.index')->with('error', 'Bu ürüne ait satış kayıtları bulunduğu için silinemez.');
        }

        $product->delete();

        return redirect()->route('products.index')->with('success', 'Ürün başarıyla silindi.');
    }

    public function bulkDestroy(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer'
        ]);

        $ids = array_values(array_unique($request->input('ids', [])));

        $products = Product::whereIn('id', $ids)->withCount('saleItems')->get();

        $blocked = $products->where('sale_items_count', '>', 0);
        $deletable = $products->where('sale_items_count', 0);

        $deletedCount = 0;
        if ($deletable->count() > 0) {
            $deletedCount = Product::whereIn('id', $deletable->pluck('id')->all())->delete();
        }

        if ($blocked->count() > 0) {
            $blockedNames = $blocked->pluck('name')->take(10)->implode(', ');
            $suffix = $blocked->count() > 10 ? '...' : '';

            return redirect()
                ->route('products.index')
                ->with('error', $deletedCount . ' ürün silindi. ' . $blocked->count() . ' ürün satış kaydı olduğu için silinemedi: ' . $blockedNames . $suffix);
        }

        return redirect()
            ->route('products.index')
            ->with('success', $deletedCount . ' ürün başarıyla silindi.');
    }

    public function updateStock(Request $request, Product $product)
    {
        $request->validate([
            'type' => 'required|in:in,out',
            'quantity' => 'required|integer|min:1',
            'notes' => 'nullable|string'
        ]);

        if ($request->type === 'out' && $product->stock_quantity < $request->quantity) {
            return back()->with('error', 'Yetersiz stok! Mevcut stok: ' . $product->stock_quantity);
        }

        // Stok hareketi kaydet
        StockMovement::create([
            'product_id' => $product->id,
            'type' => $request->type,
            'quantity' => $request->quantity,
            'unit_price' => $product->purchase_price,
            'reason' => $request->notes ?? ($request->type == 'in' ? 'Stok girişi' : 'Stok çıkışı'),
            'user_id' => 1 // Şimdilik sabit user_id
        ]);

        // Ürün stokunu güncelle
        if ($request->type === 'in') {
            $product->increment('stock_quantity', $request->quantity);
        } else {
            $product->decrement('stock_quantity', $request->quantity);
        }

        return back()->with('success', 'Stok başarıyla güncellendi.');
    }

    public function search(Request $request)
    {
        try {
            $query = $request->get('q');
            
            if (strlen($query) < 2) {
                return response()->json([]);
            }
            
            $products = Product::where('name', 'like', "%{$query}%")
                              ->where('is_active', true)
                              ->where('stock_quantity', '>', 0)
                              ->orderBy('name')
                              ->take(8)
                              ->get(['id', 'name', 'sale_price as price', 'stock_quantity as stock']);
            
            return response()->json($products);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Arama sırasında bir hata oluştu: ' . $e->getMessage()], 500);
        }
    }
}
