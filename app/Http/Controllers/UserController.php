<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::query();

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('email', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        $users = $query->orderBy('created_at', 'desc')->paginate(15);

        return view('users.index', compact('users'));
    }

    public function create()
    {
        $roles = User::getRoles();
        return view('users.create', compact('roles'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6|confirmed',
            'role' => 'required|in:' . implode(',', array_keys(User::getRoles())),
            'is_active' => 'nullable|boolean'
        ]);

        try {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role' => $request->role,
                'is_active' => $request->has('is_active') ? true : false
            ]);

            return redirect()->route('users.index')->with('success', 'Kullanıcı başarıyla oluşturuldu!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Kullanıcı oluşturulurken bir hata oluştu: ' . $e->getMessage())->withInput();
        }
    }

    public function show(Request $request, User $user)
    {
        // Sayfalama miktarını request'ten al, varsayılan 10
        $perPage = $request->get('per_page', 10);
        
        // Sayfalama miktarını sınırla (5-50 arası)
        $perPage = max(5, min(50, (int)$perPage));
        
        $user->load(['stockMovements', 'sales']);
        
        // Stok hareketlerini sayfalama ile al
        $stockMovements = $user->stockMovements()
            ->with('product')
            ->latest()
            ->paginate($perPage, ['*'], 'stock_page');
            
        // Satışları sayfalama ile al
        $sales = $user->sales()
            ->latest()
            ->paginate($perPage, ['*'], 'sales_page');
        
        // Aktiviteleri birleştir ve sırala
        $allActivities = collect();
        
        // Stok hareketlerini ekle
        foreach($stockMovements as $movement) {
            $allActivities->push([
                'type' => 'stock_movement',
                'data' => $movement,
                'created_at' => $movement->created_at
            ]);
        }
        
        // Satışları ekle
        foreach($sales as $sale) {
            $allActivities->push([
                'type' => 'sale',
                'data' => $sale,
                'created_at' => $sale->created_at
            ]);
        }
        
        // Tarihe göre sırala
        $recentActivities = $allActivities->sortByDesc('created_at')->take($perPage);

        return view('users.show', compact('user', 'recentActivities', 'stockMovements', 'sales', 'perPage'));
    }

    public function edit(User $user)
    {
        $roles = User::getRoles();
        return view('users.edit', compact('user', 'roles'));
    }

    public function update(Request $request, User $user)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'password' => 'nullable|string|min:6|confirmed',
            'role' => 'required|in:' . implode(',', array_keys(User::getRoles())),
            'is_active' => 'nullable|boolean'
        ]);

        try {
            $data = [
                'name' => $request->name,
                'email' => $request->email,
                'role' => $request->role,
                'is_active' => $request->has('is_active') ? true : false
            ];

            if ($request->filled('password')) {
                $data['password'] = Hash::make($request->password);
            }

            $user->update($data);

            return redirect()->route('users.index')->with('success', 'Kullanıcı başarıyla güncellendi!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Kullanıcı güncellenirken bir hata oluştu: ' . $e->getMessage())->withInput();
        }
    }

    public function destroy(User $user)
    {
        if ($user->stockMovements()->count() > 0 || $user->sales()->count() > 0) {
            return redirect()->route('users.index')->with('error', 'Bu kullanıcıya ait kayıtlar bulunduğu için silinemez.');
        }

        $user->delete();

        return redirect()->route('users.index')->with('success', 'Kullanıcı başarıyla silindi.');
    }
}