<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SettingsController extends Controller
{
    public function index()
    {
        // Sadece admin ve manager erişebilir
        if (!Auth::user()->hasAnyRole(['admin', 'manager'])) {
            return redirect()->route('dashboard')->with('error', 'Bu sayfaya erişim yetkiniz yok!');
        }

        // Ayarları gruplarına göre al (sadece general ve stock)
        $generalSettings = Setting::where('group', 'general')->get();
        $stockSettings = Setting::where('group', 'stock')->get();

        return view('settings.index', compact('generalSettings', 'stockSettings'));
    }

    public function update(Request $request)
    {
        // Sadece admin ve manager güncelleyebilir
        if (!Auth::user()->hasAnyRole(['admin', 'manager'])) {
            return redirect()->route('dashboard')->with('error', 'Bu işlemi yapmaya yetkiniz yok!');
        }

        $request->validate([
            'settings' => 'required|array',
            'settings.*' => 'nullable'
        ]);

        DB::beginTransaction();

        try {
            foreach ($request->settings as $key => $value) {
                // Boolean değerler için özel işlem
                $setting = Setting::where('key', $key)->first();
                if ($setting && $setting->type === 'boolean') {
                    $value = $value === '1' ? '1' : '0';
                }
                
                // Düşük stok uyarı seviyesi güncelleniyorsa, tüm ürünlerin minimum stok seviyesini güncelle
                if ($key === 'low_stock_alert' && is_numeric($value) && $value > 0) {
                    $updatedCount = Product::query()->update(['min_stock_level' => $value]);
                    
                    \Log::info('Düşük stok uyarı seviyesi güncellendi', [
                        'new_value' => $value,
                        'updated_products_count' => $updatedCount
                    ]);
                }
                
                Setting::setValue($key, $value);
            }

            DB::commit();

            // Özel mesajlar
            $messages = [];
            
            // Eğer düşük stok uyarı seviyesi güncellendiyse özel mesaj göster
            if (isset($request->settings['low_stock_alert'])) {
                $newValue = $request->settings['low_stock_alert'];
                $productCount = Product::count();
                $messages[] = "Düşük stok uyarı seviyesi {$newValue} olarak ayarlandı ve {$productCount} ürünün minimum stok seviyesi güncellendi.";
            }
            
            // Eğer otomatik adisyon fişi ayarı güncellendiyse özel mesaj göster
            if (isset($request->settings['auto_print_receipt'])) {
                $isEnabled = $request->settings['auto_print_receipt'] === '1';
                $status = $isEnabled ? 'AÇIK' : 'KAPALI';
                $messages[] = "Otomatik adisyon fişi ayarı {$status} olarak ayarlandı.";
            }
            
            // Mesajları birleştir
            if (!empty($messages)) {
                $finalMessage = "Ayarlar başarıyla güncellendi! " . implode(' ', $messages);
                return redirect()->route('settings.index')->with('success', $finalMessage);
            }

            return redirect()->route('settings.index')->with('success', 'Ayarlar başarıyla güncellendi!');

        } catch (\Exception $e) {
            DB::rollback();
            \Log::error('Settings update hatası', ['error' => $e->getMessage()]);
            return redirect()->route('settings.index')->with('error', 'Ayarlar güncellenirken hata oluştu: ' . $e->getMessage());
        }
    }
}
