<?php

namespace App\Http\Controllers;

use App\Models\Farm;
use App\Services\FarmBootstrap;
use App\Services\WeatherService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class FarmController extends Controller
{
    /**
     * Tampilkan halaman kelola kandang & profil peternak.
     */
    public function index(): View
    {
        $user = auth()->user();
        $farms = $user->farms()->withCount(['logs', 'rations'])->orderBy('id')->get();
        $activeFarm = $this->farm();
        $presetKota = WeatherService::PRESET_KOTA;

        return view('kandang.index', [
            'user' => $user,
            'farms' => $farms,
            'activeFarm' => $activeFarm,
            'presetKota' => $presetKota,
        ]);
    }

    /**
     * Tambah kandang baru untuk peternak.
     */
    public function store(Request $request, FarmBootstrap $boot): RedirectResponse
    {
        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:80'],
            'jumlah_ayam' => ['required', 'integer', 'min:1', 'max:1000000'],
            'fase' => ['nullable', Rule::in(['starter', 'grower', 'produksi'])],
            'lokasi_kota' => ['nullable', 'string', 'max:50'],
            'tgl_chick_in' => ['nullable', 'date'],
            'umur_awal_minggu' => ['nullable', 'integer', 'min:1', 'max:120'],
            'strain_ayam' => ['nullable', 'string', 'max:60'],
        ], [
            'nama.required' => 'Nama kandang wajib diisi.',
            'jumlah_ayam.min' => 'Jumlah ayam minimal 1 ekor.',
        ]);

        $validated['fase'] = $validated['fase'] ?? 'produksi';

        if (empty($validated['lokasi_kota'])) {
            $validated['lokasi_kota'] = 'Surabaya';
        }

        if (isset(WeatherService::PRESET_KOTA[$validated['lokasi_kota']])) {
            $preset = WeatherService::PRESET_KOTA[$validated['lokasi_kota']];
            $validated['latitude'] = $preset['lat'];
            $validated['longitude'] = $preset['lon'];
        }

        $farm = auth()->user()->farms()->create($validated);
        $boot->setupFarm($farm, false);

        // Langsung aktifkan kandang baru
        session(['active_farm_id' => $farm->id]);

        return redirect()->route('beranda')->with('ok', "Kandang \"{$farm->nama}\" berhasil ditambahkan dan langsung aktif!");
    }

    /**
     * Pindah / beralih kandang aktif.
     */
    public function switchFarm(Request $request): RedirectResponse
    {
        $request->validate([
            'farm_id' => ['required', 'integer'],
        ]);

        $farm = auth()->user()->farms()->findOrFail($request->farm_id);
        session(['active_farm_id' => $farm->id]);

        return redirect()->back()->with('ok', "Beralih ke kandang: {$farm->nama}");
    }

    /**
     * Perbarui data spesifik satu kandang (nama, jumlah ayam, fase, kota, umur).
     */
    public function update(Request $request, Farm $farm): RedirectResponse
    {
        abort_unless($farm->user_id === auth()->id(), 403, 'Akses ditolak.');

        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:80'],
            'jumlah_ayam' => ['required', 'integer', 'min:1', 'max:1000000'],
            'fase' => ['nullable', Rule::in(['starter', 'grower', 'produksi'])],
            'lokasi_kota' => ['nullable', 'string', 'max:50'],
            'tgl_chick_in' => ['nullable', 'date'],
            'umur_awal_minggu' => ['nullable', 'integer', 'min:1', 'max:120'],
            'strain_ayam' => ['nullable', 'string', 'max:60'],
        ]);

        $validated['fase'] = $validated['fase'] ?? 'produksi';

        // Lokasi kota bersifat paten terikat hardware ESP32, tidak boleh diubah setelah pembuatan kandang
        unset($validated['lokasi_kota'], $validated['latitude'], $validated['longitude']);

        $farm->update($validated);

        return redirect()->back()->with('ok', "Data kandang \"{$farm->nama}\" berhasil diperbarui!");
    }

    /**
     * Hapus kandang.
     */
    public function destroy(Farm $farm): RedirectResponse
    {
        abort_unless($farm->user_id === auth()->id(), 403, 'Akses ditolak.');

        if (auth()->user()->farms()->count() <= 1) {
            return redirect()->back()->withErrors(['hapus' => 'Tidak dapat menghapus kandang. Anda harus memiliki minimal satu kandang aktif.']);
        }

        $namaHapus = $farm->nama;
        $isCurrentActive = (session('active_farm_id') == $farm->id);

        $farm->delete();

        if ($isCurrentActive) {
            session()->forget('active_farm_id');
        }

        return redirect()->route('kandang.index')->with('ok', "Kandang \"{$namaHapus}\" berhasil dihapus.");
    }

    /**
     * Perbarui profil peternak (nama pemilik, email, password).
     */
    public function updateProfile(Request $request): RedirectResponse
    {
        $user = auth()->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['nullable', 'min:8', 'confirmed'],
        ], [
            'name.required' => 'Nama peternak wajib diisi.',
            'email.unique' => 'Email ini sudah digunakan oleh akun lain.',
            'password.min' => 'Kata sandi baru minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi kata sandi tidak cocok.',
        ]);

        $updateData = [
            'name' => $validated['name'],
            'email' => $validated['email'],
        ];

        if (!empty($validated['password'])) {
            $updateData['password'] = bcrypt($validated['password']);
        }

        $user->update($updateData);

        return redirect()->back()->with('ok', 'Profil peternak berhasil diperbarui!');
    }
}
