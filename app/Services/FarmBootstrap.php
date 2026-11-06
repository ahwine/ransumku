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
    public const SUMBER = 'Tabel Komposisi Pakan Fapet UGM & Balitnak';

    public const CATATAN = 'Standar nutrisi resmi bahan baku pakan ayam petelur tropis.';

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
            ['Tepung ikan', 55, 5.0, 3.0, 1, 2800, 15000, 0, false, 90.0, 0.0, 8.0, 'Sumber protein hewani & metionin. Batasi 5-8% agar telur tidak berbau amis.'],
            ['Minyak sawit', 0, 0, 0, 0, 8600, 16000, 0, false, 99.0, 0.0, 3.0, 'Maksimal 2-3% sebagai booster energi metabolis padat dan perekat debu pakan.'],
            ['Tepung kulit kerang', 0, 38, 0.04, 0, 0, 2500, 0, false, 99.0, 0.0, 10.0, 'Partikel kalsium kasar pelepasan lambat, efektif untuk kalsifikasi kerabang malam hari.'],
            ['Polard gandum', 15, 0.12, 0.90, 8.5, 2200, 4800, 0, false, 88.0, 0.0, 20.0, 'Batas toleransi 10-20% sebagai sumber serat mudah cerna dan pemacu nafsu makan.'],
            ['Bungkil kelapa', 21, 0.20, 0.60, 12, 2000, 5000, 0, false, 90.0, 0.0, 15.0, 'Maksimal 10-15% dalam ransum karena tinggi serat kasar dan mudah tengik.'],
            ['Premix layer', 0, 15, 6.0, 0, 0, 25000, 0, false, 98.0, 0.0, 2.0, 'Campuran mikromineral & vitamin esensial. Gunakan 0.5-2% untuk daya tahan tubuh.'],
        ];
    }

    /**
     * Katalog master bahan pakan ayam petelur lengkap dengan nutrisi paten Fapet UGM.
     */
    public static function getMasterBahanCatalog(): array
    {
        return [
            'Jagung' => [
                'nama' => 'Jagung',
                'label' => 'Jagung Kuning Giling',
                'kategori' => 'Sumber Energi Utama',
                'protein' => 8.5,
                'energi' => 3300,
                'kalsium' => 0.03,
                'fosfor' => 0.28,
                'serat' => 2.2,
                'bk' => 86.0,
                'harga_default' => 6500,
                'min_persen' => 0.0,
                'max_persen' => 65.0,
                'catatan_keamanan' => 'Batas wajar 50-65% untuk menjaga variasi sumber asam amino.',
                'deskripsi' => 'Batas wajar 50-65% untuk menjaga variasi sumber asam amino.',
            ],
            'Dedak' => [
                'nama' => 'Dedak',
                'label' => 'Dedak Padi / Bekatul Halus',
                'kategori' => 'Sumber Serat & Energi',
                'protein' => 12.0,
                'energi' => 2400,
                'kalsium' => 0.10,
                'fosfor' => 1.20,
                'serat' => 11.0,
                'bk' => 88.0,
                'harga_default' => 4500,
                'min_persen' => 0.0,
                'max_persen' => 30.0,
                'catatan_keamanan' => 'Maksimal 25-30% pada ayam petelur agar serat kasar dan asam fitat tidak berlebih.',
                'deskripsi' => 'Maksimal 25-30% pada ayam petelur agar serat kasar dan asam fitat tidak berlebih.',
            ],
            'Konsentrat' => [
                'nama' => 'Konsentrat',
                'label' => 'Konsentrat Layer Pabrikan (36-38%)',
                'kategori' => 'Sumber Protein & Premix',
                'protein' => 38.0,
                'energi' => 2300,
                'kalsium' => 5.0,
                'fosfor' => 1.5,
                'serat' => 6.0,
                'bk' => 90.0,
                'harga_default' => 13000,
                'min_persen' => 0.0,
                'max_persen' => 40.0,
                'catatan_keamanan' => 'Konsentrat komersial umumnya dirancang 30-35% dalam ransum layer.',
                'deskripsi' => 'Konsentrat komersial umumnya dirancang 30-35% dalam ransum layer.',
            ],
            'Kapur' => [
                'nama' => 'Kapur',
                'label' => 'Tepung Batu Kapur (CaCO3)',
                'kategori' => 'Sumber Kalsium Kerabang',
                'protein' => 0.0,
                'energi' => 0,
                'kalsium' => 38.0,
                'fosfor' => 0.0,
                'serat' => 0.0,
                'bk' => 99.0,
                'harga_default' => 2500,
                'min_persen' => 0.0,
                'max_persen' => 10.0,
                'catatan_keamanan' => 'Maksimal 8-10% sebagai sumber kalsium utama pembentukan kerabang telur.',
                'deskripsi' => 'Maksimal 8-10% sebagai sumber kalsium utama pembentukan kerabang telur.',
            ],
            'Bungkil kedelai' => [
                'nama' => 'Bungkil kedelai',
                'label' => 'Bungkil Kedelai (SBM - Soybean Meal)',
                'kategori' => 'Sumber Protein Nabati',
                'protein' => 44.0,
                'energi' => 2230,
                'kalsium' => 0.30,
                'fosfor' => 0.65,
                'serat' => 6.0,
                'bk' => 89.0,
                'harga_default' => 9500,
                'min_persen' => 0.0,
                'max_persen' => 30.0,
                'catatan_keamanan' => 'Sumber protein nabati bermutu tinggi, batas aman 25-30%.',
                'deskripsi' => 'Sumber protein nabati bermutu tinggi, batas aman 25-30%.',
            ],
            'Menir' => [
                'nama' => 'Menir',
                'label' => 'Menir Beras Putih',
                'kategori' => 'Sumber Energi Pengganti Jagung',
                'protein' => 8.0,
                'energi' => 3200,
                'kalsium' => 0.05,
                'fosfor' => 0.30,
                'serat' => 1.0,
                'bk' => 88.0,
                'harga_default' => 5500,
                'min_persen' => 0.0,
                'max_persen' => 35.0,
                'catatan_keamanan' => 'Sumber energi pengganti jagung, aman hingga 30-35%.',
                'deskripsi' => 'Sumber energi pengganti jagung, aman hingga 30-35%.',
            ],
            'Tepung ikan' => [
                'nama' => 'Tepung ikan',
                'label' => 'Tepung Ikan Murni',
                'kategori' => 'Sumber Protein Hewani',
                'protein' => 55.0,
                'energi' => 2800,
                'kalsium' => 5.0,
                'fosfor' => 3.0,
                'serat' => 1.0,
                'bk' => 90.0,
                'harga_default' => 15000,
                'min_persen' => 0.0,
                'max_persen' => 8.0,
                'catatan_keamanan' => 'Sumber protein hewani & metionin. Batasi 5-8% agar telur tidak berbau amis.',
                'deskripsi' => 'Sumber protein hewani & metionin. Batasi 5-8% agar telur tidak berbau amis.',
            ],
            'Minyak sawit' => [
                'nama' => 'Minyak sawit',
                'label' => 'Minyak Sawit / CPO',
                'kategori' => 'Booster Energi Metabolis',
                'protein' => 0.0,
                'energi' => 8600,
                'kalsium' => 0.0,
                'fosfor' => 0.0,
                'serat' => 0.0,
                'bk' => 99.0,
                'harga_default' => 16000,
                'min_persen' => 0.0,
                'max_persen' => 3.0,
                'catatan_keamanan' => 'Maksimal 2-3% sebagai booster energi metabolis padat dan perekat debu pakan.',
                'deskripsi' => 'Maksimal 2-3% sebagai booster energi metabolis padat dan perekat debu pakan.',
            ],
            'Tepung kulit kerang' => [
                'nama' => 'Tepung kulit kerang',
                'label' => 'Tepung Kulit Kerang / Grit',
                'kategori' => 'Kalsium Kasar Pelepasan Lambat',
                'protein' => 0.0,
                'energi' => 0,
                'kalsium' => 38.0,
                'fosfor' => 0.04,
                'serat' => 0.0,
                'bk' => 99.0,
                'harga_default' => 2500,
                'min_persen' => 0.0,
                'max_persen' => 10.0,
                'catatan_keamanan' => 'Partikel kalsium kasar pelepasan lambat, efektif untuk kalsifikasi kerabang malam hari.',
                'deskripsi' => 'Partikel kalsium kasar pelepasan lambat, efektif untuk kalsifikasi kerabang malam hari.',
            ],
            'Polard gandum' => [
                'nama' => 'Polard gandum',
                'label' => 'Polard Gandum (Wheat Pollard)',
                'kategori' => 'Serat Mudah Cerna',
                'protein' => 15.0,
                'energi' => 2200,
                'kalsium' => 0.12,
                'fosfor' => 0.90,
                'serat' => 8.5,
                'bk' => 88.0,
                'harga_default' => 4800,
                'min_persen' => 0.0,
                'max_persen' => 20.0,
                'catatan_keamanan' => 'Batas toleransi 10-20% sebagai sumber serat mudah cerna dan pemacu nafsu makan.',
                'deskripsi' => 'Batas toleransi 10-20% sebagai sumber serat mudah cerna dan pemacu nafsu makan.',
            ],
            'Bungkil kelapa' => [
                'nama' => 'Bungkil kelapa',
                'label' => 'Bungkil Kelapa (Copra Meal)',
                'kategori' => 'Sumber Protein & Aroma Gurih',
                'protein' => 21.0,
                'energi' => 2000,
                'kalsium' => 0.20,
                'fosfor' => 0.60,
                'serat' => 12.0,
                'bk' => 90.0,
                'harga_default' => 5000,
                'min_persen' => 0.0,
                'max_persen' => 15.0,
                'catatan_keamanan' => 'Maksimal 10-15% dalam ransum karena tinggi serat kasar dan mudah tengik.',
                'deskripsi' => 'Maksimal 10-15% dalam ransum karena tinggi serat kasar dan mudah tengik.',
            ],
            'Premix layer' => [
                'nama' => 'Premix layer',
                'label' => 'Premix Vitamin & Mikromineral Layer',
                'kategori' => 'Suplemen Mikronutrisi',
                'protein' => 0.0,
                'energi' => 0,
                'kalsium' => 15.0,
                'fosfor' => 6.0,
                'serat' => 0.0,
                'bk' => 98.0,
                'harga_default' => 25000,
                'min_persen' => 0.0,
                'max_persen' => 2.0,
                'catatan_keamanan' => 'Campuran mikromineral & vitamin esensial. Gunakan 0.5-2% untuk daya tahan tubuh.',
                'deskripsi' => 'Campuran mikromineral & vitamin esensial. Gunakan 0.5-2% untuk daya tahan tubuh.',
            ],
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

        // 30 hari catatan: konsisten dengan prototype laporan 30 hari & 7 hari
        $pakan = [
            22.3, 22.8, 23.3, 23.3, 23.8, 24.3, 24.8, 25.3, 25.8, 26.3,
            26.5, 27.0, 27.0, 27.5, 28.0, 28.0, 28.5, 28.5, 29.0, 29.0,
            29.5, 30.0, 30.0, 30.0, 29.5, 29.0, 28.5, 28.0, 27.5, 27.0
        ];
        $telur = [
            133, 135, 136, 138, 139, 141, 143, 144, 146, 148,
            150, 152, 154, 156, 158, 159, 160, 157, 152, 146,
            154, 148, 142, 138, 134, 132, 134, 136, 140, 145
        ];
        $mati = [
            0, 0, 0, 0, 0, 1, 0, 0, 0, 0,
            1, 0, 0, 0, 0, 0, 1, 0, 0, 0,
            1, 1, 2, 1, 2, 1, 1, 0, 0, 0
        ];

        $totalKgSoFar = 0;
        for ($i = 29; $i >= 0; $i--) {
            $k = 29 - $i;
            $t = $telur[$k];
            $kg = ($k === 29) ? round(270.0 - $totalKgSoFar, 2) : round($t * 0.062068965, 2);
            $totalKgSoFar += $kg;

            DailyLog::create([
                'farm_id' => $farm->id,
                'tanggal' => now()->subDays($i)->toDateString(),
                'pakan_kg' => $pakan[$k],
                'telur_butir' => $t,
                'telur_kg' => $kg,
                'ayam_mati' => $mati[$k],
                'biaya_lain' => $k % 7 === 0 ? 15000 : 0,
                'catatan' => $k >= 20 ? 'Pakan dihabiskan lebih banyak, telur terlihat berkurang.' : null,
            ]);
        }

        // satu ransum contoh agar beranda langsung terisi
        $std = app(NutrientStandardService::class)->getRecommendedStandard($farm)
            ?? $farm->standards()->where('verified', true)->first()
            ?? $farm->standards()->first();
        app(RationCalculator::class)->hitung($farm, 40, null, $std);

        app(AnomalyDetector::class)->evaluate($farm);

        if ($farm->alerts()->count() === 0) {
            \App\Models\Alert::create([
                'farm_id' => $farm->id,
                'jenis' => 'biaya_per_kg_telur_naik',
                'level' => 'waspada',
                'tanggal_data' => now()->subDays(2)->toDateString(),
                'pesan' => 'Biaya pakan per kg telur (estimasi) naik tajam: sekitar Rp 24.880 vs biasanya Rp 15.929.',
            ]);
            \App\Models\Alert::create([
                'farm_id' => $farm->id,
                'jenis' => 'pakan_naik_telur_turun',
                'level' => 'waspada',
                'tanggal_data' => now()->subDays(2)->toDateString(),
                'pesan' => 'Pakan naik 10% atau lebih selama 3 hari berturut-turut (rata-rata 28,4 kg vs biasanya 22,1 kg), sementara telur turun (rata-rata 144 butir vs biasanya 171 butir).',
            ]);
        }
    }
}
