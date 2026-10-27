<?php

namespace App\Http\Controllers;

use App\Models\NutrientStandard;
use Illuminate\Http\Request;

class StandardController extends Controller
{
    private function rules(): array
    {
        return [
            'nama' => 'required|string|max:120',
            'tahun' => 'nullable|integer|min:1900|max:2100',
            'protein_min' => 'required|numeric|min:0|max:100',
            'kalsium_min' => 'required|numeric|min:0|max:100',
            'kalsium_max' => 'required|numeric|min:0|max:100|gte:kalsium_min',
            'fosfor_min' => 'required|numeric|min:0|max:100',
            'fosfor_max' => 'required|numeric|min:0|max:100|gte:fosfor_min',
            'serat_max' => 'nullable|numeric|min:0|max:100',
            'energi_min' => 'required|integer|min:0|max:10000',
            'sumber' => 'nullable|string|max:255',
        ];
    }

    public function index()
    {
        return view('acuan.index', ['acuan' => $this->farm()->standards()->get()]);
    }

    public function create()
    {
        return view('acuan.form', ['acuan' => new NutrientStandard]);
    }

    public function store(Request $r)
    {
        $this->farm()->standards()->create($r->validate($this->rules()));

        return redirect()->route('acuan.index')->with('ok', 'Acuan gizi ditambahkan.');
    }

    public function edit(NutrientStandard $standard)
    {
        return view('acuan.form', ['acuan' => $this->own($standard)]);
    }

    public function update(Request $r, NutrientStandard $standard)
    {
        $this->own($standard)->update($r->validate($this->rules()));

        return redirect()->route('acuan.index')->with('ok', 'Acuan gizi diperbarui.');
    }

    public function destroy(NutrientStandard $standard)
    {
        abort_if($this->farm()->standards()->count() <= 1, 422, 'Minimal harus ada satu acuan gizi.');
        $this->own($standard)->delete();

        return redirect()->route('acuan.index')->with('ok', 'Acuan gizi dihapus.');
    }
}
