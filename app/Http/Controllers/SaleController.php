<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\Product;
use App\Models\SaleItem;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SaleController extends Controller
{
    public function index(Request $request)
    {
        $query = Sale::with('user');

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $sales = $query->orderBy('created_at', 'desc')->paginate(15);

        return view('sales.index', compact('sales'));
    }

    public function create()
    {
        $products = Product::where('is_active', true)->where('stock_quantity', '>', 0)->get();
        return view('sales.create', compact('products'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.price' => 'required|numeric|min:0',
            'payment_method' => 'required|in:cash,card,transfer'
        ]);

        DB::beginTransaction();

        try {
            $totalAmount = 0;
            $items = [];

            // Stok kontrolü ve toplam hesaplama
            foreach ($request->items as $item) {
                $product = Product::find($item['product_id']);
                
                if (!$product) {
                    throw new \Exception("Ürün bulunamadı.");
                }
                
                if (!$product->is_active) {
                    throw new \Exception("Ürün aktif değil: {$product->name}");
                }
                
                if ($product->stock_quantity < $item['quantity']) {
                    throw new \Exception("Yetersiz stok: {$product->name} (Mevcut: {$product->stock_quantity}, İstenen: {$item['quantity']})");
                }

                $unitPrice = $product->sale_price;
                $totalPrice = $unitPrice * $item['quantity'];
                $totalAmount += $totalPrice;

                $items[] = [
                    'product' => $product,
                    'quantity' => $item['quantity'],
                    'unit_price' => $unitPrice,
                    'total_price' => $totalPrice
                ];
            }

            // Satış kaydı oluştur
            $sale = Sale::create([
                'invoice_number' => 'SAT-' . date('Ymd') . '-' . str_pad(Sale::whereDate('created_at', today())->count() + 1, 4, '0', STR_PAD_LEFT),
                'total_amount' => $totalAmount,
                'tax_amount' => 0, // Şimdilik vergi yok
                'payment_method' => $request->payment_method,
                'user_id' => Auth::id() ?? 1 // Fallback user_id
            ]);

            // Satış detayları ve stok güncellemeleri
            foreach ($items as $item) {
                // Satış detayı kaydet
                SaleItem::create([
                    'sale_id' => $sale->id,
                    'product_id' => $item['product']->id,
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'total_price' => $item['total_price']
                ]);

                // Stok hareketi kaydet
                StockMovement::create([
                    'product_id' => $item['product']->id,
                    'type' => 'out',
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'reason' => 'Satış - Fatura No: ' . $sale->invoice_number,
                    'user_id' => Auth::id() ?? 1
                ]);

                // Ürün stokunu azalt
                $item['product']->decrement('stock_quantity', $item['quantity']);
            }

            DB::commit();

            return redirect()->route('sales.show', $sale)->with('success', 'Satış başarıyla kaydedildi. Fatura No: ' . $sale->invoice_number);

        } catch (\Exception $e) {
            DB::rollback();
            return back()->with('error', $e->getMessage())->withInput();
        }
    }

    public function show(Sale $sale)
    {
        $sale->load(['saleItems.product', 'user']);
        return view('sales.show', compact('sale'));
    }

    public function destroy(Sale $sale)
    {
        DB::beginTransaction();

        try {
            // Satış detaylarını al
            $saleItems = $sale->saleItems()->with('product')->get();

            foreach ($saleItems as $item) {
                // Stoku geri ekle
                $item->product->increment('stock_quantity', $item->quantity);

                // Ters stok hareketi kaydet
                StockMovement::create([
                    'product_id' => $item->product_id,
                    'type' => 'in',
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'reason' => 'Satış iptali - Fatura No: ' . $sale->invoice_number,
                    'user_id' => Auth::id()
                ]);
            }

            $sale->delete();

            DB::commit();

            return redirect()->route('sales.index')->with('success', 'Satış başarıyla iptal edildi.');

        } catch (\Exception $e) {
            DB::rollback();
            return back()->with('error', 'Satış iptal edilirken bir hata oluştu.');
        }
    }
}