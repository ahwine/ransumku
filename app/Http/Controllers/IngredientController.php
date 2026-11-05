<?php

namespace App\Http\Controllers;

use App\Models\FarmIngredient;
use App\Models\Ingredient;
use App\Services\FarmBootstrap;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class IngredientController extends Controller
{
    private function rules(): array
    {
        return [
            'nama' => 'required|string|max:80',
            'protein' => 'required|numeric|min:0|max:100',
            'kalsium' => 'required|numeric|min:0|max:100',
            'fosfor' => 'required|numeric|min:0|max:100',
            'serat' => 'required|numeric|min:0|max:100',
            'energi' => 'required|integer|min:0|max:10000',
            'harga_default' => 'required|integer|min:0',
            'bk' => 'nullable|numeric|min:1|max:100',
            'min_persen' => 'nullable|numeric|min:0|max:100',
            'max_persen' => 'nullable|numeric|min:0|max:100',
            'catatan_keamanan' => 'nullable|string|max:255',
            'sumber' => 'nullable|string|max:255',
            'catatan' => 'nullable|string|max:1000',
        ];
    }

    public function index(Request $r)
    {
        $farm = $this->farm();
        (new FarmBootstrap)->setupFarm($farm, false);

        $q = trim((string) $r->query('q'));
        $tab = $r->query('tab', 'stok');

        $bahan = $farm->ingredients()
            ->when($q !== '', fn ($x) => $x->where('nama', 'like', "%{$q}%"))
            ->orderBy('nama')->get();

        $farmIngredients = $farm->farmIngredients()
            ->with('ingredient')
            ->get()
            ->when($q !== '', fn ($col) => $col->filter(fn ($fi) => str_contains(strtolower($fi->ingredient->nama ?? ''), strtolower($q))))
            ->values();

        // Kebutuhan pakan harian kandang (misal 200 ekor * 0.11 kg = 22 kg)
        $pakanHarianKg = (float) max(1.0, ($farm->jumlah_ayam * ($farm->konsumsi_per_ekor ?: 0.11)));

        // Ambang batas Safety Stock
        $stokGudangData = $farmIngredients->map(function ($fi) use ($pakanHarianKg) {
            $nama = strtolower($fi->ingredient->nama ?? '');
            $porsiEst = 0.20;
            if (str_contains($nama, 'jagung') || str_contains($nama, 'menir') || str_contains($nama, 'sorgum')) {
                $porsiEst = 0.50;
            } elseif (str_contains($nama, 'konsentrat') || str_contains($nama, 'kedelai') || str_contains($nama, 'ikan')) {
                $porsiEst = 0.30;
            } elseif (str_contains($nama, 'dedak') || str_contains($nama, 'bekatul') || str_contains($nama, 'polard')) {
                $porsiEst = 0.20;
            } elseif (str_contains($nama, 'kapur') || str_contains($nama, 'kerang') || str_contains($nama, 'grit') || str_contains($nama, 'premix')) {
                $porsiEst = 0.07;
            }

            $kebutuhanHarian = max(0.5, round($pakanHarianKg * $porsiEst, 2));
            $safetyBuffer3Hari = round($kebutuhanHarian * 3, 1);
            $stok = (float) $fi->stok_kg;
            $hariTersisa = $kebutuhanHarian > 0 ? round($stok / $kebutuhanHarian, 1) : 0;

            if ($stok <= 0.05) {
                $status = 'habis';
                $statusLabel = 'Habis';
                $badgeClass = 'bg-rose-100 text-rose-800 border-rose-300';
            } elseif ($stok <= $safetyBuffer3Hari) {
                $status = 'menipis';
                $statusLabel = 'Perlu Pesan (Menipis)';
                $badgeClass = 'bg-amber-100 text-amber-800 border-amber-300';
            } else {
                $status = 'aman';
                $statusLabel = 'Stok Aman';
                $badgeClass = 'bg-emerald-100 text-emerald-800 border-emerald-300';
            }

            return [
                'fi' => $fi,
                'stok_kg' => $stok,
                'harga' => $fi->harga,
                'kebutuhan_harian_kg' => $kebutuhanHarian,
                'safety_buffer_kg' => $safetyBuffer3Hari,
                'hari_tersisa' => $hariTersisa,
                'status' => $status,
                'status_label' => $statusLabel,
                'badge_class' => $badgeClass,
            ];
        });

        $totalStokKg = $stokGudangData->sum('stok_kg');
        $totalNilaiRp = $stokGudangData->sum(fn ($d) => $d['stok_kg'] * $d['harga']);
        $kritisCount = $stokGudangData->whereIn('status', ['habis', 'menipis'])->count();

        return view('bahan.index', compact('farm', 'bahan', 'q', 'tab', 'stokGudangData', 'totalStokKg', 'totalNilaiRp', 'kritisCount', 'pakanHarianKg'));
    }

    public function updateStok(Request $r): RedirectResponse
    {
        $data = $r->validate([
            'farm_ingredient_id' => 'required|integer',
            'stok_kg' => 'required|numeric|min:0|max:100000',
            'harga' => 'nullable|integer|min:0',
        ]);

        $farm = $this->farm();
        $fi = $farm->farmIngredients()->findOrFail($data['farm_ingredient_id']);

        $update = ['stok_kg' => (float) $data['stok_kg']];
        if (isset($data['harga'])) {
            $update['harga'] = (int) $data['harga'];
            $update['harga_updated_at'] = now();
        }

        $fi->update($update);

        return back()->with('ok', "Stok bahan {$fi->ingredient->nama} berhasil diperbarui menjadi {$data['stok_kg']} kg!");
    }

    public function create()
    {
        return redirect()->route('bahan.index')->with('info', 'Seluruh bahan baku standar pakan ayam petelur telah terdaftar lengkap pada katalog.');
    }

    public function store(Request $r)
    {
        $data = $r->validate($this->rules());
        $farm = $this->farm();
        $ing = $farm->ingredients()->create($data + ['sumber' => $data['sumber'] ?? 'Input peternak']);

        // Langsung masuk daftar bahan kandang (stok 0, nonaktif sampai dipilih di form ransum)
        FarmIngredient::create([
            'farm_id' => $farm->id,
            'ingredient_id' => $ing->id,
            'harga' => $ing->harga_default,
            'stok_kg' => 0,
            'min_persen' => $ing->min_persen,
            'max_persen' => $ing->max_persen,
            'aktif' => false,
            'harga_updated_at' => now(),
        ]);

        return redirect()->route('bahan.index')->with('ok', 'Bahan ditambahkan. Isi stoknya di halaman Racik Ransum.');
    }

    public function edit(Ingredient $ingredient)
    {
        return redirect()->route('bahan.index')->with('info', 'Nutrisi bahan baku pakan terstandarisasi dan dikunci sesuai acuan ilmiah resmi.');
    }

    public function update(Request $r, Ingredient $ingredient)
    {
        $this->own($ingredient)->update($r->validate($this->rules()));

        return redirect()->route('bahan.index')->with('ok', 'Bahan diperbarui. Hitung ulang ransum agar perubahan dipakai.');
    }

    public function destroy(Ingredient $ingredient)
    {
        $this->own($ingredient)->delete();

        return redirect()->route('bahan.index')->with('ok', 'Bahan dihapus.');
    }
}
