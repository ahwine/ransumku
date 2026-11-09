<?php

namespace App\Services;

use App\Models\Farm;
use App\Models\NutrientStandard;
use App\Models\Ration;
use Illuminate\Support\Facades\DB;

/**
 * Menjembatani data kandang (harga & stok) dengan RationOptimizer, lalu menyimpan hasilnya.
 * Resep disimpan dalam PERSEN agar bisa dipakai ulang untuk jumlah lain.
 */
class RationCalculator
{
    public function __construct(
        private RationOptimizer $optimizer,
        private FeedConversionService $converter,
        private RationSafetyValidator $safetyValidator
    ) {}

    public function hitung(Farm $farm, float $totalKg, ?int $hari, NutrientStandard $std): Ration
    {
        $fis = $farm->farmIngredients()->with('ingredient')->where('aktif', true)->get();

        $bahan = $fis->map(function ($fi) {
            $bk = (float) ($fi->ingredient->bk ?? 88.0);
            $stokSegar = (float) $fi->stok_kg;
            // Konversi stok fisik segar di gudang (As-Fed) menjadi batas stok Bahan Kering (BK)
            // agar hasil optimasi saat dikonversi kembali ke Timbangan Segar tidak pernah melebihi stok gudang.
            $stokBk = round($stokSegar * (max(1.0, $bk) / 100.0), 4);

            return [
                'id' => $fi->ingredient_id,
                'nama' => $fi->ingredient->nama,
                'harga' => (float) $fi->harga,
                'stok' => $stokBk,
                'stok_segar' => $stokSegar,
                'protein' => (float) $fi->ingredient->protein,
                'kalsium' => (float) $fi->ingredient->kalsium,
                'fosfor' => (float) $fi->ingredient->fosfor,
                'serat' => (float) $fi->ingredient->serat,
                'energi' => (float) $fi->ingredient->energi,
                'bk' => $bk,
                'min_persen' => $fi->min_persen !== null ? (float) $fi->min_persen : (float) ($fi->ingredient->min_persen ?? 0.0),
                'max_persen' => $fi->max_persen !== null ? (float) $fi->max_persen : (float) ($fi->ingredient->max_persen ?? 100.0),
                'catatan_keamanan' => $fi->ingredient->catatan_keamanan,
            ];
        })->values()->all();

        $target = $std->toTarget();

        $hasil = $this->optimizer->optimize($bahan, $target, $totalKg);

        // Resep ideal tanpa batas stok -> dasar daftar belanja
        $tanpaStok = array_map(fn ($b) => ['stok' => null] + $b, $bahan);
        $ideal = $this->optimizer->optimize($tanpaStok, $target, $totalKg);

        $stokMap = collect($bahan)->keyBy('id');
        $belanja = [];
        $idealAsFed = $ideal['feasible'] ? $this->converter->convertToAsFed($ideal['items']) : null;
        $idealAsFedMap = isset($idealAsFed['items']) ? collect($idealAsFed['items'])->keyBy('nama') : collect();

        if ($ideal['feasible']) {
            foreach ($ideal['items'] as $it) {
                $af = $idealAsFedMap->get($it['nama']);
                $kebutuhanSegar = round((float) ($af['kg_as_fed'] ?? $it['kg']), 2);
                $stokAda = round((float) ($stokMap[$it['id']]['stok_segar'] ?? $stokMap[$it['id']]['stok'] ?? 0), 2);
                $kurang = round($kebutuhanSegar - $stokAda, 2);
                if ($kurang > 0.05) {
                    $belanja[] = [
                        'nama' => $it['nama'],
                        'stok_ada' => $stokAda,
                        'kebutuhan_kg' => $kebutuhanSegar,
                        'kekurangan_kg' => $kurang,
                        'harga_satuan' => (int) round((float) $it['harga']),
                        'biaya' => (int) round($kurang * $it['harga']),
                    ];
                }
            }
        }

        $pesanFinal = $hasil['pesan'];
        if (! $hasil['feasible'] && $ideal['feasible'] && ! empty($belanja)) {
            $rincianKurang = collect($belanja)->map(function ($b) {
                $kurangStr = rtrim(rtrim(number_format($b['kekurangan_kg'], 2, ',', '.'), '0'), ',');
                $butuhStr = rtrim(rtrim(number_format($b['kebutuhan_kg'], 2, ',', '.'), '0'), ',');
                $adaStr = rtrim(rtrim(number_format($b['stok_ada'], 2, ',', '.'), '0'), ',');

                return "{$b['nama']} kurang {$kurangStr} kg (butuh {$butuhStr} kg segar, stok gudang {$adaStr} kg)";
            })->implode('; ');
            $pesanFinal = ["Stok bahan pakan di gudang belum mencukupi takaran segar: {$rincianKurang}."];
        }

        $data = [
            'standard_id' => $std->id,
            'total_kg' => $totalKg,
            'jumlah_hari' => $hari,
            'status' => $hasil['feasible'] ? 'layak' : 'tidak_layak',
            'pesan' => $pesanFinal,
        ];

        $detail = [
            'belanja' => $belanja,
            'ideal_layak' => $ideal['feasible'],
            'standar_nama' => $std->nama,
            'standar_kode' => $std->kode,
            'parameter_tidak_dibatasi' => $std->unconstrainedParameters(),
        ];
        $asFed = null;

        if ($hasil['feasible']) {
            $data['total_biaya'] = (int) round($hasil['total_biaya']);
            $data['biaya_per_kg'] = (int) round($hasil['biaya_per_kg']);
            $detail['gizi'] = $this->cekGizi($hasil['gizi'], $target);

            $asFed = $this->converter->convertToAsFed($hasil['items']);
            $asFedMap = collect($asFed['items'] ?? [])->keyBy('nama');

            // Hitung sisa stok gudang menggunakan bobot timbangan segar (As-Fed) riil
            $detail['sisa_stok'] = collect($hasil['items'])->map(function ($it) use ($stokMap, $asFedMap) {
                $af = $asFedMap->get($it['nama']);
                $kgSegar = (float) ($af['kg_as_fed'] ?? $it['kg']);
                $stokAwal = (float) ($stokMap[$it['id']]['stok_segar'] ?? $stokMap[$it['id']]['stok'] ?? 0);
                return [
                    'nama' => $it['nama'],
                    'sisa_kg' => max(0.0, round($stokAwal - $kgSegar, 2)),
                ];
            })->all();

            $safety = $this->safetyValidator->validate($hasil['items'], $hasil['gizi'], $target);

            $detail['as_fed'] = $asFed;
            $detail['keamanan'] = $safety;
        } elseif ($ideal['feasible']) {
            $detail['ideal'] = [
                'total_biaya' => (int) round($ideal['total_biaya']),
                'total_as_fed_kg' => (float) ($idealAsFed['total_as_fed_kg'] ?? $totalKg),
                'items' => array_map(function ($i) use ($idealAsFedMap) {
                    $af = $idealAsFedMap->get($i['nama']);
                    return [
                        'nama' => $i['nama'],
                        'kg' => round((float) ($af['kg_as_fed'] ?? $i['kg']), 2),
                        'kg_bk' => round((float) $i['kg'], 2),
                        'persen' => round((float) ($af['persen_as_fed'] ?? $i['persen']), 1),
                        'persen_bk' => round((float) $i['persen'], 1),
                    ];
                }, $ideal['items']),
            ];
        }
        $data['hasil'] = $detail;

        return DB::transaction(function () use ($farm, $data, $hasil, $asFed) {
            // Jangan biarkan percobaan yang belum bisa disusun menumpuk di database
            $farm->rations()->where('status', 'tidak_layak')->delete();

            $ration = $farm->rations()->create($data);
            $asFedMap = isset($asFed['items']) ? collect($asFed['items'])->keyBy('nama') : collect();
            foreach ($hasil['items'] as $it) {
                $asFedItem = $asFedMap->get($it['nama']);
                $ration->items()->create([
                    'ingredient_id' => $it['id'],
                    'nama' => $it['nama'],
                    'kg' => round($it['kg'], 3),
                    'persen' => round($it['persen'], 3),
                    'bk' => (float) ($it['bk'] ?? 88.0),
                    'kg_as_fed' => $asFedItem ? round($asFedItem['kg_as_fed'], 3) : null,
                    'persen_as_fed' => $asFedItem ? round($asFedItem['persen_as_fed'], 3) : null,
                    'harga_saat_hitung' => (int) $it['harga'],
                    'biaya' => (int) round($it['biaya']),
                ]);
            }

            return $ration;
        });
    }

    /** Bandingkan gizi aktual dengan target; parameter null ditampilkan sesuai ketersediaan sumber. */
    private function cekGizi(array $g, array $t): array
    {
        $tol = 0.005;
        $baris = [];

        if (($t['protein_min'] ?? null) !== null) {
            $baris[] = ['Protein kasar', $g['protein'], '%', fn ($v) => $v + $tol >= $t['protein_min'], 'min ' . $this->f((float) $t['protein_min']) . '%'];
        } else {
            $baris[] = ['Protein kasar', $g['protein'], '%', fn () => true, 'tidak dibatasi oleh standar ini'];
        }

        $caMin = $t['kalsium_min'] ?? null;
        $caMax = $t['kalsium_max'] ?? null;
        if ($caMin !== null && $caMax !== null) {
            $baris[] = ['Kalsium', $g['kalsium'], '%', fn ($v) => $v + $tol >= $caMin && $v - $tol <= $caMax, $this->f((float) $caMin) . '–' . $this->f((float) $caMax) . '%'];
        } elseif ($caMin !== null) {
            $baris[] = ['Kalsium', $g['kalsium'], '%', fn ($v) => $v + $tol >= $caMin, 'min ' . $this->f((float) $caMin) . '%'];
        } elseif ($caMax !== null) {
            $baris[] = ['Kalsium', $g['kalsium'], '%', fn ($v) => $v - $tol <= $caMax, 'maks ' . $this->f((float) $caMax) . '%'];
        } else {
            $baris[] = ['Kalsium', $g['kalsium'], '%', fn () => true, 'tidak dibatasi oleh standar ini'];
        }

        $pMin = $t['fosfor_min'] ?? null;
        $pMax = $t['fosfor_max'] ?? null;
        if ($pMin !== null && $pMax !== null) {
            $baris[] = ['Fosfor', $g['fosfor'], '%', fn ($v) => $v + $tol >= $pMin && $v - $tol <= $pMax, $this->f((float) $pMin) . '–' . $this->f((float) $pMax) . '%'];
        } elseif ($pMin !== null) {
            $baris[] = ['Fosfor', $g['fosfor'], '%', fn ($v) => $v + $tol >= $pMin, 'min ' . $this->f((float) $pMin) . '%'];
        } elseif ($pMax !== null) {
            $baris[] = ['Fosfor', $g['fosfor'], '%', fn ($v) => $v - $tol <= $pMax, 'maks ' . $this->f((float) $pMax) . '%'];
        } else {
            $baris[] = ['Fosfor', $g['fosfor'], '%', fn () => true, 'tidak dibatasi oleh standar ini'];
        }

        if (($t['energi_min'] ?? null) !== null) {
            $baris[] = ['Energi metabolis', $g['energi'], ' kkal/kg', fn ($v) => $v + 1 >= $t['energi_min'], 'min ' . number_format((float) $t['energi_min'], 0, ',', '.') . ' kkal/kg'];
        } else {
            $baris[] = ['Energi metabolis', $g['energi'], ' kkal/kg', fn () => true, 'tidak dibatasi oleh standar ini'];
        }

        if (($t['serat_max'] ?? null) !== null) {
            $baris[] = ['Serat kasar', $g['serat'], '%', fn ($v) => $v - $tol <= $t['serat_max'], 'maks ' . $this->f((float) $t['serat_max']) . '%'];
        } else {
            $baris[] = ['Serat kasar', $g['serat'], '%', fn () => true, 'tidak dibatasi oleh standar ini'];
        }

        return array_map(fn ($b) => [
            'label' => $b[0],
            'nilai' => round($b[1], 2),
            'satuan' => $b[2],
            'target' => $b[4],
            'ok' => (bool) $b[3]($b[1]),
        ], $baris);
    }

    private function f(float $v): string
    {
        return rtrim(rtrim(number_format($v, 2, ',', '.'), '0'), ',');
    }
}
