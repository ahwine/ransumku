<?php

namespace App\Http\Controllers;

use App\Models\FarmIngredient;
use App\Models\Ingredient;
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
            'sumber' => 'nullable|string|max:255',
            'catatan' => 'nullable|string|max:1000',
        ];
    }

    public function index(Request $r)
    {
        $q = trim((string) $r->query('q'));
        $bahan = $this->farm()->ingredients()
            ->when($q !== '', fn ($x) => $x->where('nama', 'like', "%{$q}%"))
            ->orderBy('nama')->get();

        return view('bahan.index', compact('bahan', 'q'));
    }

    public function create()
    {
        return view('bahan.form', ['bahan' => new Ingredient]);
    }

    public function store(Request $r)
    {
        $data = $r->validate($this->rules());
        $farm = $this->farm();
        $ing = $farm->ingredients()->create($data + ['sumber' => $data['sumber'] ?? 'Input peternak']);

        // Langsung masuk daftar bahan kandang (stok 0, nonaktif sampai dipilih di form ransum)
        FarmIngredient::create([
            'farm_id' => $farm->id, 'ingredient_id' => $ing->id, 'harga' => $ing->harga_default,
            'stok_kg' => 0, 'aktif' => false, 'harga_updated_at' => now(),
        ]);

        return redirect()->route('bahan.index')->with('ok', 'Bahan ditambahkan. Isi stoknya di halaman Racik Ransum.');
    }

    public function edit(Ingredient $ingredient)
    {
        return view('bahan.form', ['bahan' => $this->own($ingredient)]);
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
