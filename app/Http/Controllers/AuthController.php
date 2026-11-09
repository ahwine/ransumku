<?php

namespace App\Http\Controllers;

use App\Models\Farm;
use App\Models\User;
use App\Services\FarmBootstrap;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function masukForm()
    {
        return view('auth.masuk');
    }

    public function masuk(Request $r)
    {
        $data = $r->validate(['email' => 'required|email', 'password' => 'required']);

        if (! Auth::attempt($data, $r->boolean('ingat'))) {
            return back()->withErrors(['email' => 'Email atau kata sandi salah.'])->onlyInput('email');
        }
        $r->session()->regenerate();

        return redirect()->intended(route('beranda'));
    }

    public function daftarForm()
    {
        return view('auth.daftar');
    }

    public function daftar(Request $r)
    {
        $data = $r->validate([
            'name' => 'required|string|max:80',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8|confirmed',
        ], [
            'email.unique' => 'Email ini sudah terdaftar.',
            'password.min' => 'Kata sandi minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi kata sandi tidak sama.',
        ]);

        $user = User::create($data);
        Auth::login($user);

        return redirect()->route('onboarding');
    }

    public function keluar(Request $r)
    {
        Auth::logout();
        $r->session()->invalidate();
        $r->session()->regenerateToken();

        return redirect()->route('masuk');
    }

    public function onboardingForm()
    {
        if (auth()->user()->farm) {
            return redirect()->route('beranda');
        }

        return view('auth.onboarding');
    }

    public function onboarding(Request $r, FarmBootstrap $boot)
    {
        $data = $r->validate([
            'nama' => 'required|string|max:80',
            'jumlah_ayam' => 'required|integer|min:1|max:1000000',
            'fase' => 'nullable|in:starter,grower,produksi',
            'umur_awal_minggu' => 'nullable|integer|min:1|max:120',
            'strain_ayam' => 'nullable|string|max:60',
        ]);
        $data['fase'] = $data['fase'] ?? 'produksi';

        $farm = Farm::create($data + ['user_id' => auth()->id()]);
        $boot->setupFarm($farm);

        return redirect()->route('beranda')->with('ok', 'Kandang berhasil disiapkan.');
    }
}
