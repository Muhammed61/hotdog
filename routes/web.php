<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\StockMovementController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CafeController;
use App\Http\Controllers\TableController;
use App\Http\Controllers\CashRegisterController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\WarehouseController;
use App\Http\Controllers\TodoController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// Auth Routes
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// ROLE BASED DASHBOARD REDIRECTS - Bu route'lar EN ÜSTTE olmalı
Route::middleware(['auth', 'user.active'])->group(function () {
    Route::get('/', function () {
        $user = auth()->user();
        
        if ($user->role === 'warehouse_manager') {
            return redirect()->route('warehouse.index');
        }
        
        if ($user->role === 'waiter') {
            return redirect()->route('cafe.index');
        }
        
        if (in_array($user->role, ['admin', 'manager', 'cashier'])) {
            return app(DashboardController::class)->index();
        }
        
        // Eğer hiçbiri değilse login'e yönlendir
        return redirect()->route('login');
    })->name('dashboard');
});

// Profile - Tüm kullanıcılar profil güncelleyebilir
Route::middleware(['auth', 'user.active'])->group(function () {
    Route::get('/profile', [AuthController::class, 'showProfile'])->name('profile');
    Route::put('/profile', [AuthController::class, 'updateProfile'])->name('profile.update');
});

// Warehouse Management - Depo Yönetimi (Admin, Manager ve Warehouse Manager erişebilir)
Route::middleware(['auth', 'user.active', 'role:admin,manager,warehouse_manager'])->group(function () {
    Route::prefix('warehouse')->name('warehouse.')->group(function () {
        Route::get('/', [WarehouseController::class, 'index'])->name('index');
        Route::get('/create', [WarehouseController::class, 'create'])->name('create');
        Route::post('/store', [WarehouseController::class, 'store'])->name('store');
        Route::get('/movements', [WarehouseController::class, 'movements'])->name('movements');
        Route::post('/quick-entry', [WarehouseController::class, 'quickEntry'])->name('quick-entry');
        Route::get('/reports', [WarehouseController::class, 'reports'])->name('reports');
        Route::get('/products/create', [WarehouseController::class, 'createProduct'])->name('products.create');
        Route::post('/products', [WarehouseController::class, 'storeProduct'])->name('products.store');
        // Ürün silme route'u
        Route::delete('/products/{id}', [WarehouseController::class, 'destroyProduct'])->name('products.destroy');
    });
});

// Cafe System - Garson, Kasiyer, Manager ve Admin erişebilir
Route::middleware(['auth', 'user.active', 'role:admin,manager,waiter,cashier'])->group(function () {
    // Cafe routes
    Route::prefix('cafe')->name('cafe.')->group(function () {
        Route::get('/', [CafeController::class, 'index'])->name('index');
        Route::get('/table/{table}', [CafeController::class, 'selectTable'])->name('table.select');
        Route::post('/table/{table}/order', [CafeController::class, 'storeOrder'])->name('order.store');
        Route::get('/order/{order}', [CafeController::class, 'showOrder'])->name('order.show');
        Route::patch('/order/{order}/status', [CafeController::class, 'updateOrderStatus'])->name('order.status');
        Route::post('/order/{order}/payment', [CafeController::class, 'processPayment'])->name('order.payment');
        Route::get('/order/{order}/split-payment', [CafeController::class, 'splitPayment'])->name('order.split-payment');
        Route::post('/order/{order}/process-split-payment', [CafeController::class, 'processSplitPayment'])->name('order.process-split-payment');
        Route::post('/order/{order}/process-partial-payment', [CafeController::class, 'processPartialPayment'])->name('order.process-partial-payment');
        Route::post('/order/{order}/close-order', [CafeController::class, 'closeOrder'])->name('order.close-order');
        Route::post('/order/{order}/cancel-payment', [CafeController::class, 'cancelPayment'])->name('order.cancel-payment');
        Route::get('/orders', [CafeController::class, 'orders'])->name('orders');
        Route::patch('/item/{item}/status', [CafeController::class, 'updateItemStatus'])->name('item.status');
        Route::delete('/item/{item}', [CafeController::class, 'removeOrderItem'])->name('item.remove');
        Route::delete('/order/{order}/served-group', [CafeController::class, 'removeServedGroup'])->name('served-group.remove');
        Route::delete('/order-extra/{extra}', [CafeController::class, 'removeExtraPrice'])->name('extra.remove');
        Route::delete('/order/{order}/extra-amount', [CafeController::class, 'removeExtraAmount'])->name('order.extra-amount.remove');
        Route::patch('/order/{order}/transfer', [CafeController::class, 'transferOrder'])->name('order.transfer');
        Route::get('/order/{order}/available-tables', [CafeController::class, 'getAvailableTablesForTransfer'])->name('order.available-tables');
        Route::post('/order/{order}/merge', [CafeController::class, 'mergeOrder'])->name('order.merge');
        Route::get('/order/{order}/occupied-tables', [CafeController::class, 'getOccupiedTablesForMerge'])->name('order.occupied-tables');
        Route::post('/print-receipt/{order}', [CafeController::class, 'printReceipt'])->name('print.receipt');
        Route::get('/search-product', [CafeController::class, 'searchProduct'])->name('search.product');

    });
    
    // Masa durumu güncelleme - Garson, Kasiyer, Manager ve Admin
    Route::patch('tables/{table}/status', [TableController::class, 'updateStatus'])->name('tables.update-status');
});

// Admin ve Manager Routes - Tam yetki
Route::middleware(['auth', 'user.active', 'role:admin,manager'])->group(function () {
    // Categories
    Route::resource('categories', CategoryController::class);
    
    // Products - Search route'u resource'dan ÖNCE tanımlanmalı
    Route::get('products/search', [ProductController::class, 'search'])->name('products.search');
    Route::delete('products/bulk-destroy', [ProductController::class, 'bulkDestroy'])->name('products.bulk-destroy');
    Route::resource('products', ProductController::class);
    Route::post('products/{product}/update-stock', [ProductController::class, 'updateStock'])->name('products.update-stock');
    
    // Sales
    Route::resource('sales', SaleController::class);
    
    // Stock Movements
    Route::get('stock-movements', [StockMovementController::class, 'index'])->name('stock-movements.index');
    
    // Users - Sadece admin ve manager
    Route::resource('users', UserController::class);
    
    // Tables (Masa Yönetimi) - Tam yetki
    Route::resource('tables', TableController::class);
    
    // Settings - Sistem Ayarları
    Route::get('settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::put('settings', [SettingsController::class, 'update'])->name('settings.update');
    
    // Cash Register - Kasa Yönetimi
    Route::get('cash-register', [CashRegisterController::class, 'index'])->name('cash-register.index');
    Route::get('cash-register/{cashType}/create', [CashRegisterController::class, 'create'])->name('cash-register.create');
    Route::post('cash-register/{cashType}', [CashRegisterController::class, 'store'])->name('cash-register.store');
    Route::get('cash-register/{cashType}/transactions', [CashRegisterController::class, 'transactions'])->name('cash-register.transactions');
    Route::get('cash-register/transaction/{transaction}', [CashRegisterController::class, 'show'])->name('cash-register.show');
    
    // Reports - Tam raporlar
    Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('reports/sales', [ReportController::class, 'sales'])->name('reports.sales');
    Route::get('reports/stock', [ReportController::class, 'stock'])->name('reports.stock');
    Route::get('reports/movements', [ReportController::class, 'movements'])->name('reports.movements');
    Route::get('reports/profit', [ReportController::class, 'profit'])->name('reports.profit');
    Route::get('reports/cafe', [ReportController::class, 'cafe'])->name('reports.cafe');
    Route::get('reports/cafe/thermal-daily', [ReportController::class, 'dailyThermalReport'])->name('reports.cafe.thermal-daily');
    Route::get('reports/cash', [ReportController::class, 'cash'])->name('reports.cash');
    Route::get('reports/activities/user', [ReportController::class, 'userActivities'])->name('reports.activities.user');
    Route::get('reports/activities/stock', [ReportController::class, 'stockActivities'])->name('reports.activities.stock');
    Route::get('reports/activities/cafe', [ReportController::class, 'cafeActivities'])->name('reports.activities.cafe');
    Route::post('reports/activities/cleanup', [ReportController::class, 'cleanupActivities'])->name('reports.activities.cleanup');
});

// Todo Routes
Route::middleware('auth')->group(function () {
    Route::get('/todos', [TodoController::class, 'index'])->name('todos.index');
    Route::post('/todos', [TodoController::class, 'store'])->name('todos.store');
    Route::get('/todos/{todo}/edit', [TodoController::class, 'edit'])->name('todos.edit');
    Route::put('/todos/{todo}', [TodoController::class, 'update'])->name('todos.update');
    Route::delete('/todos/{todo}', [TodoController::class, 'destroy'])->name('todos.destroy');
    Route::patch('/todos/{todo}/toggle', [TodoController::class, 'toggleStatus'])->name('todos.toggle');
    Route::get('/todos/movements', [TodoController::class, 'movements'])->name('todos.movements');
    Route::get('/todos/reports', [TodoController::class, 'reports'])->name('todos.reports');
});

// Storage Link Route - Sadece admin erişimi
Route::get('/storage-link', function () {
    if (!auth()->check() || auth()->user()->role !== 'admin') {
        abort(403, 'Bu işlem için admin yetkisi gereklidir.');
    }
    
    try {
        $target = storage_path('app/public');
        $link = public_path('storage');
        
        // Eğer storage dizini zaten varsa
        if (file_exists($link)) {
            return response()->json([
                'success' => true,
                'message' => 'Storage dizini zaten mevcut.',
                'target' => $target,
                'link' => $link
            ]);
        }
        
        // Manuel olarak dizin oluştur
        if (!file_exists($target)) {
            mkdir($target, 0755, true);
        }
        
        if (!file_exists($link)) {
            mkdir($link, 0755, true);
        }
        
        return response()->json([
            'success' => true,
            'message' => 'Storage dizini başarıyla oluşturuldu!',
            'target' => $target,
            'link' => $link,
            'note' => 'Dosyalar storage/app/public dizinine kaydedilecek ve public/storage üzerinden erişilebilecek.'
        ]);
        
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Hata: ' . $e->getMessage()
        ], 500);
    }
})->name('storage.link');

// Fiş gösterme route'u - Session'dan silmeyelim
Route::get('/cafe/receipt/{key}', function($key) {
    $content = session($key);
    
    if (!$content) {
        abort(404, 'Fiş bulunamadı veya süresi dolmuş');
    }
    
    // Session'dan silme - birden fazla kez erişilebilsin
    // session()->forget($key);
    
    return response($content)->header('Content-Type', 'text/html');
})->name('cafe.show-receipt');
