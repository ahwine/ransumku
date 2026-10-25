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
    public function __construct(private RationOptimizer $optimizer) {}

    public function hitung(Farm $farm, float $totalKg, ?int $hari, NutrientStandard $std): Ration
    {
        $fis = $farm->farmIngredients()->with('ingredient')->where('aktif', true)->get();

        $bahan = $fis->map(fn ($fi) => [
            'id' => $fi->ingredient_id,
            'nama' => $fi->ingredient->nama,
            'harga' => (float) $fi->harga,
            'stok' => (float) $fi->stok_kg,
            'protein' => (float) $fi->ingredient->protein,
            'kalsium' => (float) $fi->ingredient->kalsium,
            'fosfor' => (float) $fi->ingredient->fosfor,
            'serat' => (float) $fi->ingredient->serat,
            'energi' => (float) $fi->ingredient->energi,
        ])->values()->all();

        $target = $std->toTarget();

        $hasil = $this->optimizer->optimize($bahan, $target, $totalKg);

        // Resep ideal tanpa batas stok -> dasar daftar belanja
        $tanpaStok = array_map(fn ($b) => ['stok' => null] + $b, $bahan);
        $ideal = $this->optimizer->optimize($tanpaStok, $target, $totalKg);

        $stokMap = collect($bahan)->keyBy('id');
        $belanja = [];
        if ($ideal['feasible']) {
            foreach ($ideal['items'] as $it) {
                $kurang = $it['kg'] - ($stokMap[$it['id']]['stok'] ?? 0);
                if ($kurang > 0.05) {
                    $belanja[] = [
                        'nama' => $it['nama'],
                        'kekurangan_kg' => round($kurang, 2),
                        'biaya' => (int) round($kurang * $it['harga']),
                    ];
                }
            }
        }

        $data = [
            'standard_id' => $std->id,
            'total_kg' => $totalKg,
            'jumlah_hari' => $hari,
            'status' => $hasil['feasible'] ? 'layak' : 'tidak_layak',
            'pesan' => $hasil['pesan'],
        ];

        $detail = ['belanja' => $belanja, 'ideal_layak' => $ideal['feasible']];

        if ($hasil['feasible']) {
            $data['total_biaya'] = (int) round($hasil['total_biaya']);
            $data['biaya_per_kg'] = (int) round($hasil['biaya_per_kg']);
            $detail['gizi'] = $this->cekGizi($hasil['gizi'], $target);
            $detail['sisa_stok'] = collect($hasil['items'])->map(fn ($it) => [
                'nama' => $it['nama'],
                'sisa_kg' => round(($stokMap[$it['id']]['stok'] ?? 0) - $it['kg'], 2),
            ])->all();
        } elseif ($ideal['feasible']) {
            $detail['ideal'] = [
                'total_biaya' => (int) round($ideal['total_biaya']),
                'items' => array_map(fn ($i) => [
                    'nama' => $i['nama'], 'kg' => round($i['kg'], 2), 'persen' => round($i['persen'], 2),
                ], $ideal['items']),
            ];
        }
        $data['hasil'] = $detail;

        return DB::transaction(function () use ($farm, $data, $hasil) {
            $ration = $farm->rations()->create($data);
            foreach ($hasil['items'] as $it) {
                $ration->items()->create([
                    'ingredient_id' => $it['id'],
                    'nama' => $it['nama'],
                    'kg' => round($it['kg'], 3),
                    'persen' => round($it['persen'], 3),
                    'harga_saat_hitung' => (int) $it['harga'],
                    'biaya' => (int) round($it['biaya']),
                ]);
            }

            return $ration;
        });
    }

    /** Bandingkan gizi aktual dengan target; tanda ✔/✘ ditentukan di sini. */
    private function cekGizi(array $g, array $t): array
    {
        $tol = 0.005;
        $baris = [
            ['Protein', $g['protein'], '%', fn ($v) => $v + $tol >= $t['protein_min'], 'min '.$this->f($t['protein_min']).'%'],
            ['Kalsium', $g['kalsium'], '%', fn ($v) => $v + $tol >= $t['kalsium_min'] && $v - $tol <= $t['kalsium_max'], $this->f($t['kalsium_min']).'–'.$this->f($t['kalsium_max']).'%'],
            ['Fosfor', $g['fosfor'], '%', fn ($v) => $v + $tol >= $t['fosfor_min'] && $v - $tol <= $t['fosfor_max'], $this->f($t['fosfor_min']).'–'.$this->f($t['fosfor_max']).'%'],
            ['Energi metabolis', $g['energi'], ' kkal/kg', fn ($v) => $v + 1 >= $t['energi_min'], 'min '.number_format($t['energi_min'], 0, ',', '.').' kkal/kg'],
        ];
        if (($t['serat_max'] ?? null) !== null) {
            $baris[] = ['Serat kasar', $g['serat'], '%', fn ($v) => $v - $tol <= $t['serat_max'], 'maks '.$this->f($t['serat_max']).'%'];
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
