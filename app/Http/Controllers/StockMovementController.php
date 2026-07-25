<?php

namespace App\Http\Controllers;

use App\Models\StockMovement;
use App\Models\Product;
use Illuminate\Http\Request;

class StockMovementController extends Controller
{
    public function index(Request $request)
    {
        $query = StockMovement::with(['product', 'user']);

        // Tarih filtreleri
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Ürün filtresi
        if ($request->filled('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        // Hareket tipi filtresi
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        $movements = $query->orderBy('created_at', 'desc')->paginate(20);

        // Filtreleme için ürünleri al
        $products = Product::where('is_active', true)->orderBy('name')->get();

        // Özet bilgiler
        $totalIn = StockMovement::where('type', 'in')
            ->when($request->filled('date_from'), function($q) use ($request) {
                return $q->whereDate('created_at', '>=', $request->date_from);
            })
            ->when($request->filled('date_to'), function($q) use ($request) {
                return $q->whereDate('created_at', '<=', $request->date_to);
            })
            ->sum('quantity');

        $totalOut = StockMovement::where('type', 'out')
            ->when($request->filled('date_from'), function($q) use ($request) {
                return $q->whereDate('created_at', '>=', $request->date_from);
            })
            ->when($request->filled('date_to'), function($q) use ($request) {
                return $q->whereDate('created_at', '<=', $request->date_to);
            })
            ->sum('quantity');

        return view('stock-movements.index', compact('movements', 'products', 'totalIn', 'totalOut'));
    }
}