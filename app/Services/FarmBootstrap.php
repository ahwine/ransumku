<?php

namespace App\Services;

use App\Models\DailyLog;
use App\Models\Farm;
use App\Models\FarmIngredient;
use App\Models\Ingredient;
use App\Models\NutrientStandard;

/**
 * Menyiapkan data bawaan (template bahan + acuan) dan data demo.
 * SEMUA nilai gizi di sini adalah ANGKA ILUSTRASI, bukan data resmi.
 */
class FarmBootstrap
{
    public const SUMBER = 'Angka ilustrasi (bukan data resmi)';

    public const CATATAN = 'Sesuaikan dengan label kemasan atau tabel komposisi bahan pakan resmi.';

    /** nama, protein, kalsium, fosfor, serat, energi, harga, stok demo, aktif, bk, min_persen, max_persen, catatan_keamanan */
    public static function templates(): array
    {
        return [
            ['Jagung', 8.5, 0.03, 0.28, 2.2, 3300, 6500, 50, true, 86.0, 0.0, 65.0, 'Batas wajar 50-65% untuk menjaga variasi sumber asam amino.'],
            ['Dedak', 12, 0.10, 1.2, 11, 2400, 4500, 30, true, 88.0, 0.0, 30.0, 'Maksimal 25-30% pada ayam petelur agar serat kasar dan asam fitat tidak berlebih.'],
            ['Konsentrat', 38, 5.0, 1.5, 6, 2300, 13000, 20, true, 90.0, 0.0, 40.0, 'Konsentrat komersial umumnya dirancang 30-35% dalam ransum layer.'],
            ['Kapur', 0, 38, 0, 0, 0, 2500, 10, true, 99.0, 0.0, 10.0, 'Maksimal 8-10% sebagai sumber kalsium utama pembentukan kerabang telur.'],
            ['Bungkil kedelai', 44, 0.30, 0.65, 6, 2230, 9500, 0, false, 89.0, 0.0, 30.0, 'Sumber protein nabati bermutu tinggi, batas aman 25-30%.'],
            ['Menir', 8, 0.05, 0.30, 1, 3200, 5500, 0, false, 88.0, 0.0, 35.0, 'Sumber energi pengganti jagung, aman hingga 30-35%.'],
        ];
    }

    public static function standardDefault(): array
    {
        return [
            'nama' => 'SNI 2016 (kutipan jurnal)',
            'tahun' => 2016,
            'protein_min' => 16.5,
            'kalsium_min' => 3.25,
            'kalsium_max' => 4.25,
            'fosfor_min' => 0.6,
            'fosfor_max' => 0.9,
            'serat_max' => null,
            'energi_min' => 2700,
            'sumber' => 'Kutipan jurnal; dapat diedit sesuai acuan yang Anda pakai',
        ];
    }

    /** Template global (farm_id NULL), dipakai seeder. */
    public function seedTemplates(): void
    {
        if (Ingredient::whereNull('farm_id')->exists()) {
            return;
        }
        foreach (self::templates() as [$nama, $p, $ca, $f, $s, $e, $h, $stok, $aktif, $bk, $min, $max, $catatanKeamanan]) {
            Ingredient::create([
                'farm_id' => null, 'nama' => $nama, 'protein' => $p, 'kalsium' => $ca, 'fosfor' => $f,
                'serat' => $s, 'energi' => $e, 'harga_default' => $h,
                'bk' => $bk, 'min_persen' => $min, 'max_persen' => $max, 'catatan_keamanan' => $catatanKeamanan,
                'sumber' => self::SUMBER, 'catatan' => self::CATATAN,
            ]);
        }
    }

    /** Salin bahan & acuan ke kandang baru. */
    public function setupFarm(Farm $farm, bool $denganStok = true): void
    {
        $this->seedTemplates();

        foreach (self::templates() as [$nama, $p, $ca, $f, $s, $e, $h, $stok, $aktif, $bk, $min, $max, $catatanKeamanan]) {
            $ing = $farm->ingredients()->firstOrCreate(['nama' => $nama], [
                'protein' => $p, 'kalsium' => $ca, 'fosfor' => $f, 'serat' => $s, 'energi' => $e,
                'harga_default' => $h, 'bk' => $bk, 'min_persen' => $min, 'max_persen' => $max,
                'catatan_keamanan' => $catatanKeamanan,
                'sumber' => self::SUMBER, 'catatan' => self::CATATAN,
            ]);
            FarmIngredient::firstOrCreate(
                ['farm_id' => $farm->id, 'ingredient_id' => $ing->id],
                [
                    'harga' => $h,
                    'stok_kg' => $denganStok ? $stok : 0,
                    'min_persen' => $min,
                    'max_persen' => $max,
                    'aktif' => $aktif,
                    'harga_updated_at' => now(),
                ]
            );
        }

        $standardService = app(NutrientStandardService::class);
        if ($standardService->needsSync($farm)) {
            $standardService->syncOfficialStandards($farm);
        }
    }

    /** Setel ulang kandang ke kondisi demo: 200 ayam, 14 hari catatan, 3 hari terakhir anomali. */
    public function resetDemo(Farm $farm): void
    {
        $farm->alerts()->delete();
        $farm->chats()->delete();
        $farm->purchases()->delete();
        $farm->rations()->delete();
        $farm->logs()->delete();
        $farm->farmIngredients()->delete();

        $farm->update(['nama' => $farm->nama ?: 'Kandang Maju Jaya', 'jumlah_ayam' => 200, 'fase' => 'produksi', 'konsumsi_per_ekor' => 0.11]);

        $this->setupFarm($farm, true);

        // harga/stok kembali ke angka contoh
        foreach ($farm->farmIngredients()->with('ingredient')->get() as $fi) {
            $tpl = collect(self::templates())->firstWhere(0, $fi->ingredient->nama);
            if ($tpl) {
                $fi->update(['harga' => $tpl[6], 'stok_kg' => $tpl[7], 'aktif' => $tpl[8], 'harga_updated_at' => now()->subDays(2)]);
                $fi->ingredient->update(['harga_default' => $tpl[6]]);
            }
        }

        // 14 hari catatan: 11 hari normal, 3 hari terakhir anomali (pakan naik, telur turun)
        $pakan = [21.8, 22.2, 22.0, 21.6, 22.4, 22.1, 21.9, 22.3, 22.0, 21.8, 22.1, 25.2, 26.4, 27.5];
        $telur = [171, 169, 172, 168, 170, 173, 169, 171, 170, 172, 169, 152, 143, 136];
        $mati = [0, 0, 1, 0, 0, 0, 1, 0, 0, 0, 0, 1, 1, 2];

        for ($i = 13; $i >= 0; $i--) {
            $k = 13 - $i;
            DailyLog::create([
                'farm_id' => $farm->id,
                'tanggal' => now()->subDays($i)->toDateString(),
                'pakan_kg' => $pakan[$k],
                'telur_butir' => $telur[$k],
                'telur_kg' => round($telur[$k] * 0.062, 2),
                'ayam_mati' => $mati[$k],
                'biaya_lain' => $k % 5 === 0 ? 15000 : 0,
                'catatan' => $k >= 11 ? 'Pakan dihabiskan lebih banyak, telur terlihat berkurang.' : null,
            ]);
        }

        // satu ransum contoh agar beranda langsung terisi
        $std = app(NutrientStandardService::class)->getRecommendedStandard($farm)
            ?? $farm->standards()->where('verified', true)->first()
            ?? $farm->standards()->first();
        app(RationCalculator::class)->hitung($farm, 40, null, $std);

        app(AnomalyDetector::class)->evaluate($farm);
    }
}
