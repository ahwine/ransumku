<?php

namespace App\Http\Controllers;

use App\Models\Ration;
use App\Services\AiAssistant;
use App\Services\FeedConversionService;
use App\Services\NutrientStandardService;
use App\Services\RationCalculator;
use App\Services\RationSafetyValidator;
use App\Support\Fmt;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class RationController extends Controller
{
    public function index()
    {
        $rations = $this->farm()->rations()->where('status', 'layak')->with(['items', 'standard'])->latest('id')->get();

        return view('ransum.index', compact('rations'));
    }

    public function create(Request $r)
    {
        $farm = $this->farm();
        // Hanya ambil bahan-bahan yang ada di gudang peternak (aktif)
        $fis = $farm->farmIngredients()
            ->with('ingredient')
            ->where('aktif', true)
            ->get()
            ->sortByDesc('stok_kg')
            ->values();

        $stdService = app(NutrientStandardService::class);
        // Selalu prioritaskan Standar Baku Mutu SNI 8290 Layer Fase Produksi
        $targetStandard = $farm->standards()->where('nama', 'like', '%SNI 8290-5%')->orWhere('nama', 'like', '%SNI 8290:2024%')->first()
            ?? $farm->standards()->where('nama', 'like', '%Layer%')->first()
            ?? $farm->standards()->first();
        $standards = $farm->standards()->orderByDesc('verified')->orderBy('id')->get();
        $defaultStandardId = $targetStandard?->id ?? 0;

        $konsumsi = $farm->konsumsi_per_ekor;
        $pakanHarianKg = (float) max(1.0, ($farm->jumlah_ayam * ($farm->konsumsi_per_ekor ?: 0.11)));

        return view('ransum.create', compact('farm', 'fis', 'targetStandard', 'standards', 'konsumsi', 'pakanHarianKg', 'defaultStandardId'));
    }

    public function store(Request $r, RationCalculator $calc)
    {
        $farm = $this->farm();

        if (! $r->filled('mode')) {
            $r->merge(['mode' => 'hari']);
        }

        $data = $r->validate([
            'mode' => 'nullable|in:kg,hari',
            'total_kg' => 'required_if:mode,kg|nullable|numeric|min:1|max:100000',
            'jumlah_hari' => 'nullable|integer|min:1|max:365',
            'jumlah_ayam' => 'nullable|integer|min:1',
            'konsumsi' => 'nullable|numeric|min:0.01|max:1',
            'standard_id' => 'required|integer',
            'bahan' => 'required|array',
            'bahan.*.harga' => 'nullable|numeric|min:0',
            'bahan.*.stok' => 'nullable|numeric|min:0',
            'bahan.*.min_persen' => 'nullable|numeric|min:0|max:100',
            'bahan.*.max_persen' => 'nullable|numeric|min:0|max:100',
            'bahan.*.aktif' => 'nullable|boolean',
        ], [
            'total_kg.required_if' => 'Isi jumlah campuran (kg).',
            'jumlah_hari.required_if' => 'Isi jumlah hari.',
        ]);

        $std = $farm->standards()->findOrFail($data['standard_id']);

        // Simpan harga & batasan nutrisi yang diisi peternak. Stok gudang tidak diubah di form racik ini.
        $aktif = 0;
        foreach ($farm->farmIngredients()->get() as $fi) {
            $in = $data['bahan'][$fi->id] ?? null;
            if (! $in) {
                continue;
            }
            $harga = (int) round((float) ($in['harga'] ?? $fi->harga));
            $upd = [
                'min_persen' => isset($in['min_persen']) && $in['min_persen'] !== '' ? (float) $in['min_persen'] : $fi->min_persen,
                'max_persen' => isset($in['max_persen']) && $in['max_persen'] !== '' ? (float) $in['max_persen'] : $fi->max_persen,
                'aktif' => ! empty($in['aktif']),
            ];
            if ($harga !== (int) $fi->harga) {
                $upd['harga'] = $harga;
                $upd['harga_updated_at'] = now();
            }
            $fi->update($upd);
            $aktif += $upd['aktif'] ? 1 : 0;
        }

        if ($aktif === 0) {
            return back()->withInput()->withErrors(['bahan' => 'Pilih minimal satu bahan.']);
        }

        if (($data['mode'] ?? 'hari') === 'kg' && ! empty($data['total_kg'])) {
            $total = (float) $data['total_kg'];
            $hari = null;
        } else {
            $hari = (int) ($data['jumlah_hari'] ?? 3);
            $ayam = (int) ($data['jumlah_ayam'] ?? $farm->jumlah_ayam ?? 1);
            $kons = (float) ($data['konsumsi'] ?? $farm->konsumsi_per_ekor ?? 0.11);
            $total = round($hari * $ayam * $kons, 2);
        }

        $ration = $calc->hitung($farm, $total, $hari, $std);

        return redirect()->route('ransum.show', $ration);
    }

    public function show(Request $r, Ration $ration, FeedConversionService $converter, RationSafetyValidator $safetyValidator)
    {
        $this->own($ration);
        $ration->load('items.ingredient', 'standard');

        // Skala ulang resep (disimpan dalam persen) untuk jumlah lain
        $skala = (float) $r->query('kg', $ration->total_kg);
        $skala = max(1, min($skala, 100000));

        $baris = $ration->items->map(fn ($i) => [
            'id' => $i->ingredient_id,
            'nama' => $i->nama,
            'persen' => (float) $i->persen,
            'kg' => $i->persen / 100 * $skala,
            'bk' => (float) ($i->bk ?: ($i->ingredient?->bk ?? 88.0)),
            'harga' => $i->harga_saat_hitung,
            'biaya' => $i->persen / 100 * $skala * $i->harga_saat_hitung,
            'min_persen' => $i->ingredient?->min_persen,
            'max_persen' => $i->ingredient?->max_persen,
            'catatan_keamanan' => $i->ingredient?->catatan_keamanan,
        ]);

        $asFed = $converter->convertToAsFed($baris->all());

        // Kapasitas batch mixer (opsional query param: mixer_kg, default 100 kg)
        $mixerKg = max(1.0, min((float) $r->query('mixer_kg', 100.0), 10000.0));
        $mixingSheet = $converter->hitungMixingSheet($asFed['items'], $mixerKg);

        // Validasi keamanan pakan
        $giziMap = [];
        if (! empty($ration->hasil['gizi'])) {
            foreach ($ration->hasil['gizi'] as $g) {
                $lbl = strtolower(explode(' ', $g['label'])[0]);
                $giziMap[$lbl] = (float) $g['nilai'];
            }
        }
        $keamanan = $ration->hasil['keamanan'] ?? $safetyValidator->validate($baris->all(), $giziMap);

        $hargaUsang = $this->farm()->farmIngredients()->where('aktif', true)->get()->contains(fn ($fi) => $fi->hargaUsang());
        $unconstrainedParams = $ration->parameterTidakDibatasi();
        $stokDipakai = ! empty($ration->hasil['stok_dipotong']);

        // Validasi apakah seluruh bahan riil mencukupi di gudang pakan (hanya jika pakan belum pernah dipotong)
        $stokKurangList = [];
        if (! $stokDipakai) {
            foreach ($asFed['items'] ?? $baris as $b) {
                $kgDibutuhkan = (float) ($b['kg_as_fed'] ?? $b['kg'] ?? 0);
                $fi = $this->farm()->farmIngredients()
                    ->where('ingredient_id', $b['id'] ?? null)
                    ->first();
                $stokAda = (float) ($fi?->stok_kg ?? 0);
                if ($stokAda < $kgDibutuhkan) {
                    $stokKurangList[] = [
                        'nama' => $b['nama'],
                        'butuh' => $kgDibutuhkan,
                        'ada' => $stokAda,
                        'kurang' => round($kgDibutuhkan - $stokAda, 2),
                    ];
                }
            }
        }
        $farm = $this->farm();
        $bisaDiterapkan = ! $stokDipakai && empty($stokKurangList);

        // Jika resep simulasi lama tercatat 'layak' secara BK namun stok segar (As-Fed) di gudang sebenarnya kurang,
        // sinkronkan statusnya menjadi 'tidak_layak' agar tidak membingungkan peternak.
        if ($ration->layak() && ! $stokDipakai && ! empty($stokKurangList)) {
            $belanjaSinkron = [];
            $idealItemsSinkron = [];
            foreach ($asFed['items'] ?? $baris as $b) {
                $kgSegar = round((float) ($b['kg_as_fed'] ?? $b['kg'] ?? 0), 2);
                $persenSegar = round((float) ($b['persen_as_fed'] ?? $b['persen'] ?? 0), 1);
                $fi = $farm->farmIngredients()->where('ingredient_id', $b['id'] ?? null)->first();
                $stokAda = round((float) ($fi?->stok_kg ?? 0), 2);
                $hargaSat = (int) ($fi?->harga ?? $b['harga'] ?? 0);
                $kurang = round($kgSegar - $stokAda, 2);

                $idealItemsSinkron[] = [
                    'nama' => $b['nama'],
                    'kg' => $kgSegar,
                    'persen' => $persenSegar,
                ];

                if ($kurang > 0.05) {
                    $belanjaSinkron[] = [
                        'nama' => $b['nama'],
                        'stok_ada' => $stokAda,
                        'kebutuhan_kg' => $kgSegar,
                        'kekurangan_kg' => $kurang,
                        'harga_satuan' => $hargaSat,
                        'biaya' => (int) round($kurang * $hargaSat),
                    ];
                }
            }

            $rincianKurang = collect($belanjaSinkron)->map(function ($b) {
                $kurangStr = rtrim(rtrim(number_format($b['kekurangan_kg'], 2, ',', '.'), '0'), ',');
                $butuhStr = rtrim(rtrim(number_format($b['kebutuhan_kg'], 2, ',', '.'), '0'), ',');
                $adaStr = rtrim(rtrim(number_format($b['stok_ada'], 2, ',', '.'), '0'), ',');

                return "{$b['nama']} kurang {$kurangStr} kg (butuh {$butuhStr} kg segar, stok gudang {$adaStr} kg)";
            })->implode('; ');

            $hasilBaru = $ration->hasil ?? [];
            $hasilBaru['belanja'] = $belanjaSinkron;
            $hasilBaru['ideal'] = [
                'total_biaya' => (int) round($asFed['total_biaya'] ?? $ration->total_biaya),
                'items' => $idealItemsSinkron,
            ];

            $ration->update([
                'status' => 'tidak_layak',
                'pesan' => ["Stok bahan pakan di gudang belum mencukupi takaran segar: {$rincianKurang}."],
                'hasil' => $hasilBaru,
            ]);
        }

        // Jika belum bisa disusun, lengkapi data tabel belanja & cari apakah jumlah hari lebih kecil mencukupi stok segar
        $hariMaksStok = null;
        if (! $ration->layak()) {
            $fisAktif = $farm->farmIngredients()->with('ingredient')->where('aktif', true)->get();
            $stokByNama = $fisAktif->keyBy(fn ($fi) => $fi->ingredient?->nama ?? '');
            $idealItems = $ration->hasil['ideal']['items'] ?? [];
            $idealItemsMap = collect($idealItems)->keyBy('nama');

            // Pastikan item simulasi resep ideal menggunakan bobot segar (As-Fed)
            if (! empty($idealItems)) {
                $idealItemsUpdated = [];
                $totalSegarIdeal = 0.0;
                foreach ($idealItems as $idIt) {
                    $fiMatch = $stokByNama->get($idIt['nama']);
                    $bk = (float) ($fiMatch?->ingredient?->bk ?? 88.0);
                    $kgSegar = isset($idIt['kg_bk'])
                        ? (float) $idIt['kg']
                        : round(((float) $idIt['kg']) / (max(1.0, $bk) / 100.0), 2);
                    // Jika sudah terkonversi sebelumnya (total ideal > total_kg), jangan bagi dua kali
                    if (collect($idealItems)->sum('kg') > ((float) $ration->total_kg * 1.03)) {
                        $kgSegar = (float) $idIt['kg'];
                    }
                    $totalSegarIdeal += $kgSegar;
                    $idealItemsUpdated[] = [
                        'nama' => $idIt['nama'],
                        'kg' => $kgSegar,
                        'kg_bk' => (float) ($idIt['kg_bk'] ?? $idIt['kg']),
                        'persen' => (float) $idIt['persen'],
                    ];
                }
                foreach ($idealItemsUpdated as &$rowIdeal) {
                    if ($totalSegarIdeal > 0) {
                        $rowIdeal['persen'] = round(($rowIdeal['kg'] / $totalSegarIdeal) * 100.0, 1);
                    }
                }
                unset($rowIdeal);
                $idealItemsMap = collect($idealItemsUpdated)->keyBy('nama');
            }

            // Lengkapi kolom tabel belanja (termasuk untuk data lama)
            $belanjaRaw = $ration->hasil['belanja'] ?? [];
            $belanjaLengkap = [];
            foreach ($belanjaRaw as $bItem) {
                $fiMatch = $stokByNama->get($bItem['nama']);
                $idMatch = $idealItemsMap->get($bItem['nama']);
                $stokAda = isset($bItem['stok_ada']) ? (float) $bItem['stok_ada'] : (float) ($fiMatch?->stok_kg ?? 0);
                $kebutuhanKg = $idMatch ? (float) $idMatch['kg'] : (float) ($bItem['kebutuhan_kg'] ?? ($stokAda + (float) ($bItem['kekurangan_kg'] ?? 0)));
                $kurangKg = round(max(0.0, $kebutuhanKg - $stokAda), 2);
                $hargaSatuan = isset($bItem['harga_satuan']) ? (int) $bItem['harga_satuan'] : (int) ($fiMatch?->harga ?? 0);

                if ($kurangKg > 0.05) {
                    $belanjaLengkap[] = [
                        'nama' => $bItem['nama'],
                        'stok_ada' => round($stokAda, 2),
                        'kebutuhan_kg' => round($kebutuhanKg, 2),
                        'kekurangan_kg' => $kurangKg,
                        'harga_satuan' => $hargaSatuan,
                        'biaya' => $hargaSatuan > 0 ? (int) round($kurangKg * $hargaSatuan) : (int) ($bItem['biaya'] ?? 0),
                    ];
                }
            }
            $hasilCopy = $ration->hasil ?? [];
            $hasilCopy['belanja'] = $belanjaLengkap;
            if (! empty($idealItemsUpdated)) {
                $hasilCopy['ideal']['items'] = $idealItemsUpdated;
            }
            $ration->hasil = $hasilCopy;

            // Cek apakah jumlah hari yang lebih sedikit mencukupi stok SEGAR (As-Fed) di gudang saat ini
            $hariSaatIni = (int) ($ration->jumlah_hari ?: 3);
            $pakanPerHari = max(1.0, (float) ($farm->jumlah_ayam * ($farm->konsumsi_per_ekor ?: 0.11)));
            $stdUsed = $ration->standard ?? $farm->standards()->first();
            if ($stdUsed && $hariSaatIni > 1) {
                $opt = app(\App\Services\RationOptimizer::class);
                $bahanOpt = $fisAktif->map(function ($fi) {
                    $bk = (float) ($fi->ingredient->bk ?? 88.0);
                    $stokSegar = (float) $fi->stok_kg;

                    return [
                        'id' => $fi->ingredient_id,
                        'nama' => $fi->ingredient->nama,
                        'harga' => (float) $fi->harga,
                        'stok' => round($stokSegar * (max(1.0, $bk) / 100.0), 4),
                        'protein' => (float) $fi->ingredient->protein,
                        'kalsium' => (float) $fi->ingredient->kalsium,
                        'fosfor' => (float) $fi->ingredient->fosfor,
                        'serat' => (float) $fi->ingredient->serat,
                        'energi' => (float) $fi->ingredient->energi,
                        'bk' => $bk,
                        'min_persen' => $fi->min_persen !== null ? (float) $fi->min_persen : (float) ($fi->ingredient->min_persen ?? 0.0),
                        'max_persen' => $fi->max_persen !== null ? (float) $fi->max_persen : (float) ($fi->ingredient->max_persen ?? 100.0),
                    ];
                })->values()->all();
                $targetOpt = $stdUsed->toTarget();
                for ($d = $hariSaatIni - 1; $d >= 1; $d--) {
                    $coba = $opt->optimize($bahanOpt, $targetOpt, round($d * $pakanPerHari, 2));
                    if ($coba['feasible']) {
                        $hariMaksStok = $d;
                        break;
                    }
                }
            }
        }

        return view('ransum.show', compact('farm', 'ration', 'baris', 'skala', 'hargaUsang', 'asFed', 'mixerKg', 'mixingSheet', 'keamanan', 'unconstrainedParams', 'stokKurangList', 'bisaDiterapkan', 'stokDipakai', 'hariMaksStok'));
    }

    /** Hitung ulang memakai harga & stok TERBARU, serta penyesuaian jumlah hari bila dikirim. */
    public function hitungUlang(Request $r, Ration $ration, RationCalculator $calc)
    {
        $this->own($ration);
        $farm = $this->farm();
        $std = $ration->standard;
        $pesan = 'Dihitung ulang dengan harga dan stok terbaru.';

        if (! $std) {
            $std = app(NutrientStandardService::class)->getRecommendedStandard($farm)
                ?? $farm->standards()->where('verified', true)->first()
                ?? $farm->standards()->first();

            if (! $std) {
                return redirect()->route('ransum.show', $ration)->withErrors([
                    'standard_id' => 'Tidak ada acuan gizi aktif untuk menghitung ulang ransum ini.',
                ]);
            }

            $pesan = "Acuan lama sudah tidak ada di katalog aktif; dihitung ulang memakai acuan \"{$std->nama}\" dengan harga dan stok terbaru.";
        }

        $hari = $r->filled('jumlah_hari') ? max(1, min(365, (int) $r->input('jumlah_hari'))) : $ration->jumlah_hari;
        if ($r->filled('jumlah_hari')) {
            $pakanHarian = max(1.0, (float) ($farm->jumlah_ayam * ($farm->konsumsi_per_ekor ?: 0.11)));
            $totalKg = round($hari * $pakanHarian, 2);
            $pesan = "Formula dihitung ulang untuk kebutuhan {$hari} hari ({$totalKg} kg).";
        } else {
            $totalKg = (float) $ration->total_kg;
        }

        $baru = $calc->hitung($farm, $totalKg, $hari, $std);

        return redirect()->route('ransum.show', $baru)->with('ok', $pesan);
    }

    public function jelaskan(Ration $ration, AiAssistant $ai)
    {
        $this->own($ration);
        $ration->update(['penjelasan' => $ai->jelaskan($ration)]);

        return redirect()->route('ransum.show', $ration)->withFragment('penjelasan');
    }

    /**
     * Terapkan formula ransum ini dan kurangi stok bahan pakan di gudang secara otomatis.
     */
    public function terapkan(Ration $ration): RedirectResponse
    {
        $this->own($ration);
        $farm = $this->farm();

        if (! $ration->layak()) {
            return back()->withErrors(['terapkan' => 'Ransum tidak layak nutrisi tidak dapat diterapkan ke gudang.']);
        }

        $hasil = $ration->hasil ?? [];
        if (! empty($hasil['stok_dipotong'])) {
            return back()->with('info', 'Ransum ini sudah pernah diterapkan ke gudang pakan.');
        }

        $items = $ration->items;
        $dipotong = [];

        foreach ($items as $it) {
            $kgKurang = round((float) ($it->kg_as_fed ?? $it->kg), 2);
            if ($kgKurang <= 0) {
                continue;
            }

            $fi = $farm->farmIngredients()
                ->where('ingredient_id', $it->ingredient_id)
                ->first();

            if (! $fi) {
                $fi = $farm->farmIngredients()
                    ->whereHas('ingredient', fn ($q) => $q->where('nama', $it->nama))
                    ->first();
            }

            if ($fi) {
                $stokAwal = (float) $fi->stok_kg;
                $stokAkhir = max(0.0, round($stokAwal - $kgKurang, 2));
                $fi->update(['stok_kg' => $stokAkhir]);
                $dipotong[] = "{$it->nama} (-" . Fmt::num($kgKurang, 2) . " kg)";
            }
        }

        $hasil['stok_dipotong'] = true;
        $hasil['applied_at'] = now()->toIso8601String();
        $ration->update(['hasil' => $hasil]);

        return back()->with('ok', 'Racikan pakan berhasil diterapkan! Stok gudang telah dipotong otomatis: ' . implode(', ', $dipotong));
    }

    /**
     * Batalkan penerapan formula ransum dan kembalikan stok bahan pakan ke gudang.
     */
    public function batalTerapkan(Ration $ration): RedirectResponse
    {
        $this->own($ration);
        $farm = $this->farm();

        $hasil = $ration->hasil ?? [];
        if (empty($hasil['stok_dipotong'])) {
            return back()->with('info', 'Ransum ini belum pernah diterapkan.');
        }

        $items = $ration->items;
        foreach ($items as $it) {
            $kgBalik = round((float) ($it->kg_as_fed ?? $it->kg), 2);
            if ($kgBalik > 0) {
                $fi = $farm->farmIngredients()
                    ->where('ingredient_id', $it->ingredient_id)
                    ->first();

                if (! $fi) {
                    $fi = $farm->farmIngredients()
                        ->whereHas('ingredient', fn ($q) => $q->where('nama', $it->nama))
                        ->first();
                }

                if ($fi) {
                    $stokAwal = (float) $fi->stok_kg;
                    $fi->update(['stok_kg' => round($stokAwal + $kgBalik, 2)]);
                }
            }
        }

        unset($hasil['stok_dipotong'], $hasil['applied_at']);
        $ration->update(['hasil' => $hasil]);

        return back()->with('ok', 'Penerapan racikan dibatalkan. Stok gudang berhasil dikembalikan.');
    }

    public function destroy(Ration $ration)
    {
        $this->own($ration)->delete();

        return redirect()->route('ransum.index')->with('ok', 'Resep dihapus.');
    }
}
