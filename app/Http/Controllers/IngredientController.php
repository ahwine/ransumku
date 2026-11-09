<?php

namespace App\Http\Controllers;

use App\Models\FarmIngredient;
use App\Models\Ingredient;
use App\Services\FarmBootstrap;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class IngredientController extends Controller
{
    public function index(Request $r)
    {
        $farm = $this->farm();
        (new FarmBootstrap)->setupFarm($farm, false);

        $q = trim((string) $r->query('q'));

        // Ambil bahan-bahan yang ada di gudang kandang ini (aktif)
        $farmIngredients = $farm->farmIngredients()
            ->with('ingredient')
            ->where('aktif', true)
            ->get()
            ->when($q !== '', fn ($col) => $col->filter(fn ($fi) => str_contains(strtolower($fi->ingredient->nama ?? ''), strtolower($q))))
            ->values();

        $lastRation = $farm->rations()->where('status', 'layak')->with('items')->latest('id')->first();
        $pakanHarianKg = (float) max(1.0, ($farm->jumlah_ayam * ($farm->konsumsi_per_ekor ?: 0.11)));

        $stokGudangData = $farmIngredients->map(function ($fi) use ($farm, $lastRation) {
            $eval = $farm->evaluasiStokBahan($fi, $lastRation);

            return [
                'fi' => $fi,
                'stok_kg' => $eval['stok_kg'],
                'harga' => $fi->harga,
                'status' => $eval['status'],
                'status_label' => $eval['status_label'],
                'text_class' => $eval['text_class'],
            ];
        });

        $totalStokKg = $stokGudangData->sum('stok_kg');
        $totalNilaiRp = $stokGudangData->sum(fn ($d) => $d['stok_kg'] * $d['harga']);
        $kritisCount = $stokGudangData->whereIn('status', ['habis', 'menipis'])->count();

        // Katalog Master untuk Dropdown Tambah Bahan
        $masterCatalog = FarmBootstrap::getMasterBahanCatalog();

        // Daftar nama bahan yang sudah ada di gudang agar ditandai
        $existingNames = $farmIngredients->pluck('ingredient.nama')->toArray();

        return view('bahan.index', compact(
            'farm',
            'q',
            'stokGudangData',
            'totalStokKg',
            'totalNilaiRp',
            'kritisCount',
            'pakanHarianKg',
            'masterCatalog',
            'existingNames'
        ));
    }

    /**
     * Tambah bahan ke gudang menggunakan acuan nutrisi paten dari Master Catalog.
     */
    public function store(Request $r): RedirectResponse
    {
        $data = $r->validate([
            'nama_template' => 'required|string',
            'stok_kg' => 'required|numeric|min:0|max:100000',
            'harga' => 'required|integer|min:0',
        ], [
            'nama_template.required' => 'Pilih bahan pakan dari daftar resmi.',
            'stok_kg.required' => 'Masukkan jumlah stok pakan (kg).',
            'harga.required' => 'Masukkan harga beli pakan per kg.',
        ]);

        $farm = $this->farm();
        $catalog = FarmBootstrap::getMasterBahanCatalog();

        if (! isset($catalog[$data['nama_template']])) {
            return back()->withInput()->withErrors(['nama_template' => 'Bahan pakan yang dipilih tidak valid.']);
        }

        $tpl = $catalog[$data['nama_template']];

        // Buat atau temukan Ingredient untuk farm ini dengan nutrisi paten
        $ing = $farm->ingredients()->firstOrCreate(
            ['nama' => $tpl['nama']],
            [
                'protein' => $tpl['protein'],
                'kalsium' => $tpl['kalsium'],
                'fosfor' => $tpl['fosfor'],
                'serat' => $tpl['serat'],
                'energi' => $tpl['energi'],
                'bk' => $tpl['bk'],
                'harga_default' => $data['harga'] ?: $tpl['harga_default'],
                'min_persen' => $tpl['min_persen'],
                'max_persen' => $tpl['max_persen'],
                'catatan_keamanan' => $tpl['catatan_keamanan'],
                'sumber' => FarmBootstrap::SUMBER,
                'catatan' => FarmBootstrap::CATATAN,
            ]
        );

        // Sambungkan ke FarmIngredient (Gudang Farm)
        $fi = $farm->farmIngredients()->firstOrNew(['ingredient_id' => $ing->id]);
        $fi->stok_kg = (float) $data['stok_kg'];
        $fi->harga = (int) $data['harga'];
        $fi->aktif = true;
        $fi->min_persen = $fi->min_persen ?? $tpl['min_persen'];
        $fi->max_persen = $fi->max_persen ?? $tpl['max_persen'];
        $fi->harga_updated_at = now();
        $fi->save();

        return redirect()->route('bahan.index')->with('ok', "Bahan {$tpl['label']} ditambahkan ke gudang.");
    }

    /**
     * Memperbarui stok dan harga bahan yang sudah ada di gudang.
     */
    public function updateStok(Request $r): RedirectResponse
    {
        $data = $r->validate([
            'farm_ingredient_id' => 'required|integer',
            'stok_kg' => 'required|numeric|min:0|max:100000',
            'harga' => 'nullable|integer|min:0',
        ]);

        $farm = $this->farm();
        $fi = $farm->farmIngredients()->findOrFail($data['farm_ingredient_id']);

        $update = ['stok_kg' => (float) $data['stok_kg'], 'aktif' => true];
        if (isset($data['harga'])) {
            $update['harga'] = (int) $data['harga'];
            $update['harga_updated_at'] = now();
        }

        $fi->update($update);

        return back()->with('ok', "Stok {$fi->ingredient->nama} diperbarui.");
    }

    /**
     * Menghapus bahan dari gudang kandang aktif.
     */
    public function hapusDariGudang(FarmIngredient $farmIngredient): RedirectResponse
    {
        $farm = $this->farm();
        if ($farmIngredient->farm_id !== $farm->id) {
            abort(403);
        }

        $nama = $farmIngredient->ingredient->nama ?? 'Bahan pakan';
        $farmIngredient->delete();

        return redirect()->route('bahan.index')->with('ok', "Bahan {$nama} dihapus dari gudang.");
    }
}
