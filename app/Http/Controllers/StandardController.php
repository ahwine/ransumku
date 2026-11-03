<?php

namespace App\Http\Controllers;

use App\Models\NutrientStandard;
use App\Services\NutrientStandardService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class StandardController extends Controller
{
    public function __construct(
        protected NutrientStandardService $standardService
    ) {}

    public function index(): View
    {
        $farm = $this->farm();

        if (! $farm->standards()->exists()) {
            $this->standardService->syncOfficialStandards($farm);
        }

        $perluSinkron = $this->standardService->needsSync($farm);

        $acuan = $farm->standards()
            ->orderByDesc('verified')
            ->orderBy('id')
            ->get();

        $syncMetadata = NutrientStandardService::getSyncMetadata();

        return view('acuan.index', compact('acuan', 'syncMetadata', 'perluSinkron'));
    }

    public function sync(Request $r): RedirectResponse
    {
        $farm = $this->farm();
        $count = $this->standardService->syncOfficialStandards($farm);

        return redirect()->route('acuan.index')->with('ok', "Sinkronisasi selesai. {$count} entri katalog acuan gizi telah diperbarui.");
    }

    public function create(): RedirectResponse
    {
        return redirect()->route('acuan.index')->with('info', 'Pilih salah satu entri berstatus belum terverifikasi pada katalog jika ingin melengkapi angkanya.');
    }

    public function store(): RedirectResponse
    {
        return redirect()->route('acuan.index')->with('info', 'Gunakan fitur ubah pada entri yang belum terverifikasi untuk melengkapi data.');
    }

    public function edit(NutrientStandard $standard): View|RedirectResponse
    {
        $this->own($standard);

        if ($standard->verified) {
            return redirect()->route('acuan.index')->with('info', 'Standar yang sudah terverifikasi dikunci agar tetap sesuai dokumen sumber.');
        }

        return view('acuan.form', [
            'acuan' => $standard,
        ]);
    }

    public function update(Request $r, NutrientStandard $standard): RedirectResponse
    {
        $this->own($standard);

        if ($standard->verified) {
            return redirect()->route('acuan.index')->with('info', 'Standar yang sudah terverifikasi dikunci agar tetap sesuai dokumen sumber.');
        }

        $data = $r->validate([
            'nama' => ['required', 'string', 'max:150'],
            'edition_year' => ['required', 'string', 'max:60'],
            'source' => ['required', 'string', 'max:500'],
            'protein_min' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'energi_min' => ['nullable', 'integer', 'min:0', 'max:10000'],
            'kalsium_min' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'kalsium_max' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'fosfor_min' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'fosfor_max' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'serat_max' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'verified' => ['nullable', 'boolean'],
        ]);

        $caMin = $data['kalsium_min'] ?? null;
        $caMax = $data['kalsium_max'] ?? null;
        if ($caMin !== null && $caMax !== null && (float) $caMax < (float) $caMin) {
            return back()->withInput()->withErrors([
                'kalsium_max' => 'Kalsium maksimal tidak boleh lebih kecil dari kalsium minimal.',
            ]);
        }

        $pMin = $data['fosfor_min'] ?? null;
        $pMax = $data['fosfor_max'] ?? null;
        if ($pMin !== null && $pMax !== null && (float) $pMax < (float) $pMin) {
            return back()->withInput()->withErrors([
                'fosfor_max' => 'Fosfor maksimal tidak boleh lebih kecil dari fosfor minimal.',
            ]);
        }

        $tahunInt = preg_match('/\b(19\d{2}|20\d{2})\b/', $data['edition_year'], $m)
            ? (int) $m[1]
            : $standard->tahun;

        $proteinMin = $data['protein_min'] ?? null;
        $energiMin = $data['energi_min'] ?? null;
        $seratMax = $data['serat_max'] ?? null;

        $standard->update([
            'nama' => $data['nama'],
            'edition_year' => $data['edition_year'],
            'tahun' => $tahunInt,
            'source' => $data['source'],
            'sumber' => $data['source'],
            'protein_min' => $proteinMin !== null && $proteinMin !== '' ? (float) $proteinMin : null,
            'energi_min' => $energiMin !== null && $energiMin !== '' ? (int) $energiMin : null,
            'kalsium_min' => $caMin !== null && $caMin !== '' ? (float) $caMin : null,
            'kalsium_max' => $caMax !== null && $caMax !== '' ? (float) $caMax : null,
            'fosfor_min' => $pMin !== null && $pMin !== '' ? (float) $pMin : null,
            'fosfor_max' => $pMax !== null && $pMax !== '' ? (float) $pMax : null,
            'serat_max' => $seratMax !== null && $seratMax !== '' ? (float) $seratMax : null,
            'verified' => $r->boolean('verified'),
        ]);

        return redirect()->route('acuan.index')->with('ok', "Data acuan \"{$standard->nama}\" berhasil diperbarui.");
    }

    public function destroy(NutrientStandard $standard): RedirectResponse
    {
        $this->own($standard);

        if ($standard->verified || $standard->is_resmi) {
            return redirect()->route('acuan.index')->with('info', 'Entri katalog acuan gizi tidak dapat dihapus.');
        }

        $this->standardService->archiveStandardOnRations($standard);
        $standard->delete();

        return redirect()->route('acuan.index')->with('ok', 'Acuan gizi berhasil dihapus.');
    }
}
