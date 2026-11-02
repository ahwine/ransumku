<?php

namespace App\Http\Controllers;

use App\Models\NutrientStandard;
use App\Services\NutrientStandardService;
use Illuminate\Http\Request;

class StandardController extends Controller
{
    public function __construct(
        protected NutrientStandardService $standardService
    ) {}

    public function index()
    {
        $farm = $this->farm();

        // Pastikan standar resmi lengkap di database kandang
        if ($farm->standards()->where('is_resmi', true)->count() < 4) {
            $this->standardService->syncOfficialStandards($farm);
        }

        $acuan = $farm->standards()
            ->orderBy('id')
            ->get();

        $syncMetadata = NutrientStandardService::getSyncMetadata();

        return view('acuan.index', compact('acuan', 'syncMetadata'));
    }

    public function sync(Request $r)
    {
        $farm = $this->farm();
        $count = $this->standardService->syncOfficialStandards($farm);

        return redirect()->route('acuan.index')->with('ok', "Sinkronisasi selesai. {$count} standar resmi BSN & Breeder Guide telah diperbarui.");
    }

    public function create()
    {
        return redirect()->route('acuan.index')->with('info', 'Acuan gizi adalah direktori standar resmi terverifikasi dan tidak perlu ditambahkan secara manual.');
    }

    public function store()
    {
        return redirect()->route('acuan.index')->with('info', 'Acuan gizi adalah direktori standar resmi terverifikasi.');
    }

    public function edit(NutrientStandard $standard)
    {
        return redirect()->route('acuan.index')->with('info', 'Acuan gizi resmi tidak dapat diubah guna menjaga akurasi standar ilmiah.');
    }

    public function update(Request $r, NutrientStandard $standard)
    {
        return redirect()->route('acuan.index')->with('info', 'Acuan gizi resmi tidak dapat diubah guna menjaga akurasi standar ilmiah.');
    }

    public function destroy(NutrientStandard $standard)
    {
        return redirect()->route('acuan.index')->with('info', 'Standar resmi terkunci untuk menjaga keutuhan data formulasi ransum.');
    }
}
