<?php

namespace App\Http\Controllers;

use App\Models\Table;
use App\Models\CafeOrder;
use Illuminate\Http\Request;

class TableController extends Controller
{
    public function index()
    {
        $tables = Table::orderBy('name')->paginate(15);
        return view('tables.index', compact('tables'));
    }

    public function create()
    {
        return view('tables.create');
    }

    public function store(Request $request)
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255|unique:tables',
                'capacity' => 'required|integer|min:1|max:20',
                'is_active' => 'nullable|boolean'
            ]);

            $table = Table::create([
                'name' => $request->name,
                'capacity' => $request->capacity,
                'status' => Table::STATUS_AVAILABLE,
                'is_active' => $request->has('is_active') ? true : false
            ]);

            return redirect()->route('tables.index')->with('success', 'Masa başarıyla oluşturuldu.');
        } catch (\Exception $e) {
            return redirect()->back()->withInput()->with('error', 'Hata: ' . $e->getMessage());
        }
    }

    public function edit(Table $table)
    {
        return view('tables.edit', compact('table'));
    }

    public function update(Request $request, Table $table)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255|unique:tables,name,' . $table->id,
                'capacity' => 'required|integer|min:1|max:20',
                'status' => 'required|in:available,occupied,reserved,cleaning,closed',
                'is_active' => 'nullable'
            ]);

            $table->update([
                'name' => $validated['name'],
                'capacity' => $validated['capacity'],
                'status' => $validated['status'],
                'is_active' => $request->has('is_active') ? 1 : 0
            ]);

            return redirect()->route('tables.index')->with('success', 'Masa başarıyla güncellendi.');
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->back()
                ->withInput()
                ->withErrors($e->validator)
                ->with('error', 'Lütfen form alanlarını kontrol edin.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Güncelleme sırasında hata oluştu: ' . $e->getMessage());
        }
    }

    public function updateStatus(Request $request, Table $table)
    {
        $request->validate([
            'status' => 'required|in:available,occupied,reserved,cleaning'
        ]);

        // Eğer masa müsait yapılıyorsa, aktif siparişleri kontrol et
        if ($request->status === Table::STATUS_AVAILABLE) {
            $activeOrdersCount = CafeOrder::where('table_id', $table->id)
                ->where('is_paid', false)
                ->whereNotIn('status', [CafeOrder::STATUS_CANCELLED])
                ->count();

            if ($activeOrdersCount > 0) {
                return back()->with('error', 'Bu masanın ödenmemiş siparişleri bulunuyor. Önce siparişleri tamamlayın.');
            }
        }

        $table->update(['status' => $request->status]);

        return back()->with('success', 'Masa durumu "' . $table->status_text . '" olarak güncellendi.');
    }

    public function destroy(Table $table)
    {
        if ($table->cafeOrders()->count() > 0) {
            return redirect()->route('tables.index')->with('error', 'Bu masaya ait sipariş kayıtları bulunduğu için silinemez.');
        }

        $table->delete();

        return redirect()->route('tables.index')->with('success', 'Masa başarıyla silindi.');
    }
}