<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class AuthController extends Controller
{
    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        $credentials = $request->only('email', 'password');
        $remember = $request->boolean('remember');

        // Önce kullanıcıyı bulalım
        $user = User::where('email', $request->email)->first();

        // Kullanıcı varsa ve pasifse giriş yapmasına izin vermeyelim
        if ($user && !$user->is_active) {
            return back()->withErrors([
                'email' => 'Hesabınız pasif durumda. Lütfen yönetici ile iletişime geçin.',
            ])->withInput($request->except('password'));
        }

        // Normal giriş kontrolü
        if (Auth::attempt($credentials, $remember)) {
            $request->session()->regenerate();
            
            // Garson ise direkt kafe sistemine yönlendir
            if (Auth::user()->role === 'waiter') {
                return redirect()->route('cafe.index')->with('success', 'Başarıyla giriş yaptınız!');
            }
            
            return redirect()->intended(route('dashboard'))->with('success', 'Başarıyla giriş yaptınız!');
        }

        return back()->withErrors([
            'email' => 'Giriş bilgileri hatalı.',
        ])->withInput($request->except('password'));
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        
        return redirect()->route('login')->with('success', 'Başarıyla çıkış yaptınız!');
    }

    public function showProfile()
    {
        return view('auth.profile');
    }

    public function updateProfile(Request $request)
    {
        $user = Auth::user();
        
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'current_password' => 'nullable|required_with:password',
            'password' => 'nullable|string|min:6|confirmed',
        ]);

        // Mevcut şifre kontrolü
        if ($request->filled('current_password')) {
            if (!Hash::check($request->current_password, $user->password)) {
                return back()->withErrors(['current_password' => 'Mevcut şifre hatalı.']);
            }
        }

        $data = [
            'name' => $request->name,
            'email' => $request->email,
        ];

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        User::where('id', $user->id)->update($data);

        $message = 'Profil başarıyla güncellendi.';
        if ($request->filled('password')) {
            $message .= ' Şifreniz değiştirildi.';
        }

        return back()->with('success', $message);
    }
}