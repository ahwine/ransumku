<?php

namespace App\Services;

use App\Models\Alert;
use App\Models\DailyLog;
use App\Models\Farm;

/**
 * Peringatan dini berbasis aturan & statistik (BUKAN LLM, BUKAN diagnosis penyakit).
 * Ambang batas ada di config/ransumku.php.
 */
class AnomalyDetector
{
    /** @return array<int,array{jenis:string,level:string,pesan:string,tanggal:string}> */
    public function deteksi(Farm $farm): array
    {
        $cfg = config('ransumku.anomali');
        $logs = $farm->logs()->orderByDesc('tanggal')->limit(3 + $cfg['hari_dasar'])->get()->reverse()->values();

        if ($logs->count() < 6) {
            return []; // data belum cukup untuk pola
        }

        $recent = $logs->slice(-3)->values();
        $base = $logs->slice(0, $logs->count() - 3)->values();
        $tgl = $recent->last()->tanggal->toDateString();
        $hasil = [];

        $avg = fn ($c) => $c->count() ? $c->sum() / $c->count() : 0.0;
        $basePakan = $avg($base->pluck('pakan_kg')->map(fn ($v) => (float) $v));
        $baseTelur = $avg($base->pluck('telur_butir')->map(fn ($v) => (float) $v));

        // 1) Pakan naik >= X% 3 hari berturut-turut sementara produksi telur turun
        if ($basePakan > 0 && $baseTelur > 0) {
            $naik = $recent->every(fn (DailyLog $l) => (float) $l->pakan_kg >= $basePakan * (1 + $cfg['pakan_naik_persen'] / 100));
            $turun = $recent->every(fn (DailyLog $l) => $l->telur_butir <= $baseTelur * (1 - $cfg['telur_turun_persen'] / 100));
            if ($naik && $turun) {
                $hasil[] = [
                    'jenis' => 'pakan_naik_telur_turun',
                    'level' => 'waspada',
                    'pesan' => sprintf(
                        'Pakan naik %d%% atau lebih selama 3 hari berturut-turut (rata-rata %s kg vs biasanya %s kg), sementara telur turun (rata-rata %d butir vs biasanya %d butir).',
                        $cfg['pakan_naik_persen'],
                        number_format($avg($recent->pluck('pakan_kg')->map(fn ($v) => (float) $v)), 1, ',', '.'),
                        number_format($basePakan, 1, ',', '.'),
                        round($avg($recent->pluck('telur_butir')->map(fn ($v) => (float) $v))),
                        round($baseTelur)
                    ),
                    'tanggal' => $tgl,
                ];
            }
        }

        // 2) Lonjakan kematian
        $matiBase = $base->pluck('ayam_mati')->map(fn ($v) => (float) $v);
        $mean = $avg($matiBase);
        $sd = $this->sd($matiBase->all(), $mean);
        $mati = (float) $recent->last()->ayam_mati;
        if ($mati >= $cfg['kematian_min'] && $mati > $mean + 2 * $sd) {
            $hasil[] = [
                'jenis' => 'lonjakan_kematian',
                'level' => 'perhatian',
                'pesan' => sprintf('Ayam mati hari terakhir %d ekor, lebih tinggi dari biasanya (rata-rata %s ekor/hari).', $mati, number_format($mean, 1, ',', '.')),
                'tanggal' => $tgl,
            ];
        }

        // 3) Biaya pakan per kg telur naik tajam (z-score terhadap rata-rata bergerak)
        $harga = $farm->hargaPakanPerKg() ?: 7000;
        $biaya = fn (DailyLog $l) => $l->telurKgEfektif() > 0 ? $l->pakan_kg * $harga / $l->telurKgEfektif() : null;
        $serieBase = $base->map($biaya)->filter()->values()->all();
        $akhir = $biaya($recent->last());
        if ($akhir !== null && count($serieBase) >= 5) {
            $m = array_sum($serieBase) / count($serieBase);
            $s = $this->sd($serieBase, $m);
            $z = $s > 1e-9 ? ($akhir - $m) / $s : ($akhir > $m * 1.15 ? INF : 0);
            if ($z >= $cfg['zscore']) {
                $hasil[] = [
                    'jenis' => 'biaya_per_kg_telur_naik',
                    'level' => 'perhatian',
                    'pesan' => sprintf('Biaya pakan per kg telur (estimasi) naik tajam: sekitar Rp %s vs biasanya Rp %s.', number_format($akhir, 0, ',', '.'), number_format($m, 0, ',', '.')),
                    'tanggal' => $tgl,
                ];
            }
        }

        return $hasil;
    }

    /** Hitung & simpan (tanpa duplikat untuk jenis+tanggal yang sama). */
    public function evaluate(Farm $farm): void
    {
        foreach ($this->deteksi($farm) as $a) {
            Alert::firstOrCreate(
                ['farm_id' => $farm->id, 'jenis' => $a['jenis'], 'tanggal_data' => $a['tanggal']],
                ['pesan' => $a['pesan'], 'level' => $a['level']]
            );
        }
    }

    private function sd(array $v, float $mean): float
    {
        $n = count($v);
        if ($n < 2) {
            return 0.0;
        }

        return sqrt(array_sum(array_map(fn ($x) => ($x - $mean) ** 2, $v)) / ($n - 1));
    }
}
