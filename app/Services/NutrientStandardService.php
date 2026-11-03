<?php

namespace App\Services;

use App\Models\Farm;
use App\Models\NutrientStandard;
use App\Models\Ration;

class NutrientStandardService
{
    public const DATASET_VERSION = '2026.4';

    /**
     * Katalog standar kebutuhan nutrisi ayam petelur beserta status verifikasi sumber.
     *
     * @return array<int, array{
     *     kode: string,
     *     nama: string,
     *     kategori_fase: string,
     *     tahun: int|null,
     *     edition_year: string,
     *     protein_min: float|null,
     *     energi_min: int|null,
     *     kalsium_min: float|null,
     *     kalsium_max: float|null,
     *     fosfor_min: float|null,
     *     fosfor_max: float|null,
     *     serat_max: float|null,
     *     source: string,
     *     sumber: string,
     *     organisasi_sumber: string,
     *     is_resmi: bool,
     *     verified: bool,
     *     parameter_tambahan: array<string, mixed>,
     *     deskripsi: string
     * }>
     */
    public static function officialStandards(): array
    {
        return [
            [
                'kode' => 'LOHMANN_CLASSIC_1',
                'nama' => 'Lohmann Brown Classic - Fase 1 (19–50 Minggu)',
                'kategori_fase' => 'produksi',
                'tahun' => null,
                'edition_year' => 'belum diketahui',
                'protein_min' => 16.8,
                'energi_min' => null,
                'kalsium_min' => 3.73,
                'kalsium_max' => null,
                'fosfor_min' => 0.55,
                'fosfor_max' => null,
                'serat_max' => null,
                'source' => 'Lohmann Brown-Classic Management Guide (panduan kandang baterai), asupan 110 g/ekor/hari, P tanpa fitase',
                'sumber' => 'Lohmann Brown-Classic Management Guide (panduan kandang baterai), asupan 110 g/ekor/hari, P tanpa fitase',
                'organisasi_sumber' => 'Lohmann Breeders',
                'is_resmi' => true,
                'verified' => true,
                'parameter_tambahan' => [
                    'asupan_g_hari' => 110,
                    'fosfor_tersedia_min' => 0.38,
                    'lisin_min' => 0.88,
                    'metionin_min' => 0.44,
                    'energi_label' => 'Tidak di sumber',
                ],
                'deskripsi' => 'Panduan kandang baterai pada asupan pakan 110 g/ekor/hari (P tanpa fitase). Nilai bergantung asupan pakan harian; EM tidak tercantum di sumber.',
            ],
            [
                'kode' => 'LOHMANN_CLASSIC_2',
                'nama' => 'Lohmann Brown Classic - Fase 2 (50–70 Minggu)',
                'kategori_fase' => 'produksi',
                'tahun' => null,
                'edition_year' => 'belum diketahui',
                'protein_min' => 16.4,
                'energi_min' => null,
                'kalsium_min' => 4.00,
                'kalsium_max' => null,
                'fosfor_min' => 0.52,
                'fosfor_max' => null,
                'serat_max' => null,
                'source' => 'Lohmann Brown-Classic Management Guide (panduan kandang baterai), asupan 110 g/ekor/hari, P tanpa fitase',
                'sumber' => 'Lohmann Brown-Classic Management Guide (panduan kandang baterai), asupan 110 g/ekor/hari, P tanpa fitase',
                'organisasi_sumber' => 'Lohmann Breeders',
                'is_resmi' => true,
                'verified' => true,
                'parameter_tambahan' => [
                    'asupan_g_hari' => 110,
                    'fosfor_tersedia_min' => 0.37,
                    'lisin_min' => 0.86,
                    'metionin_min' => 0.43,
                    'energi_label' => 'Tidak di sumber',
                ],
                'deskripsi' => 'Panduan kandang baterai minggu 50–70 pada asupan pakan 110 g/ekor/hari (P tanpa fitase). Nilai bergantung asupan pakan harian; EM tidak tercantum di sumber.',
            ],
            [
                'kode' => 'LOHMANN_CLASSIC_3',
                'nama' => 'Lohmann Brown Classic - Fase 3 (>70 Minggu)',
                'kategori_fase' => 'produksi',
                'tahun' => null,
                'edition_year' => 'belum diketahui',
                'protein_min' => 15.5,
                'energi_min' => null,
                'kalsium_min' => 4.09,
                'kalsium_max' => null,
                'fosfor_min' => 0.50,
                'fosfor_max' => null,
                'serat_max' => null,
                'source' => 'Lohmann Brown-Classic Management Guide (panduan kandang baterai), asupan 110 g/ekor/hari, P tanpa fitase',
                'sumber' => 'Lohmann Brown-Classic Management Guide (panduan kandang baterai), asupan 110 g/ekor/hari, P tanpa fitase',
                'organisasi_sumber' => 'Lohmann Breeders',
                'is_resmi' => true,
                'verified' => true,
                'parameter_tambahan' => [
                    'asupan_g_hari' => 110,
                    'fosfor_tersedia_min' => 0.35,
                    'lisin_min' => 0.82,
                    'metionin_min' => 0.41,
                    'energi_label' => 'Tidak di sumber',
                ],
                'deskripsi' => 'Panduan kandang baterai setelah minggu 70 pada asupan pakan 110 g/ekor/hari (P tanpa fitase). Nilai bergantung asupan pakan harian; EM tidak tercantum di sumber.',
            ],
            [
                'kode' => 'SNI_8290_3_M1',
                'nama' => 'SNI 8290-3:2024 Mutu 1 (Dara 11–16 Minggu)',
                'kategori_fase' => 'grower',
                'tahun' => 2024,
                'edition_year' => '2024',
                'protein_min' => 15.0,
                'energi_min' => null,
                'kalsium_min' => 0.70,
                'kalsium_max' => 1.20,
                'fosfor_min' => 0.40,
                'fosfor_max' => null,
                'serat_max' => 8.0,
                'source' => 'Kepka BSN 635/KEP/BSN/12/2024 (SNI 8290-3:2024 Mutu 1), salinan Dinas Peternakan Lampung & Blitar — sumber sekunder, cek ke dokumen BSN',
                'sumber' => 'Kepka BSN 635/KEP/BSN/12/2024 (SNI 8290-3:2024 Mutu 1), salinan Dinas Peternakan Lampung & Blitar — sumber sekunder, cek ke dokumen BSN',
                'organisasi_sumber' => 'BSN',
                'is_resmi' => true,
                'verified' => false,
                'parameter_tambahan' => [
                    'status_verifikasi' => 'sumber_sekunder',
                    'air_max' => 13.0,
                    'abu_max' => 8.0,
                    'lemak_min' => 3.0,
                    'lisin_min' => 0.70,
                    'metionin_min' => 0.30,
                    'energi_label' => 'Tidak di sumber',
                ],
                'deskripsi' => 'Pakan ayam ras petelur fase dara (11–16 minggu) kelas Mutu 1 dari salinan sekunder Dinas Peternakan Lampung & Blitar (belum dicek ke dokumen BSN).',
            ],
            [
                'kode' => 'SNI_8290_3_M2',
                'nama' => 'SNI 8290-3:2024 Mutu 2 (Dara 11–16 Minggu)',
                'kategori_fase' => 'grower',
                'tahun' => 2024,
                'edition_year' => '2024',
                'protein_min' => 14.0,
                'energi_min' => null,
                'kalsium_min' => 0.65,
                'kalsium_max' => 1.20,
                'fosfor_min' => 0.30,
                'fosfor_max' => null,
                'serat_max' => 9.0,
                'source' => 'Kepka BSN 635/KEP/BSN/12/2024 (SNI 8290-3:2024 Mutu 2), salinan Dinas Peternakan Lampung & Blitar — sumber sekunder, cek ke dokumen BSN',
                'sumber' => 'Kepka BSN 635/KEP/BSN/12/2024 (SNI 8290-3:2024 Mutu 2), salinan Dinas Peternakan Lampung & Blitar — sumber sekunder, cek ke dokumen BSN',
                'organisasi_sumber' => 'BSN',
                'is_resmi' => true,
                'verified' => false,
                'parameter_tambahan' => [
                    'status_verifikasi' => 'sumber_sekunder',
                    'abu_max' => 9.0,
                    'lisin_min' => 0.60,
                    'metionin_min' => 0.25,
                    'energi_label' => 'Tidak di sumber',
                ],
                'deskripsi' => 'Pakan ayam ras petelur fase dara (11–16 minggu) kelas Mutu 2 dari salinan sekunder Dinas Peternakan Lampung & Blitar (belum dicek ke dokumen BSN).',
            ],
            [
                'kode' => 'SNI_8290_5_M1',
                'nama' => 'SNI 8290-5:2024 Mutu 1 (Layer 19–50 Minggu)',
                'kategori_fase' => 'produksi',
                'tahun' => 2024,
                'edition_year' => '2024',
                'protein_min' => 16.5,
                'energi_min' => null,
                'kalsium_min' => 3.20,
                'kalsium_max' => 4.30,
                'fosfor_min' => null,
                'fosfor_max' => null,
                'serat_max' => 7.0,
                'source' => 'Kepka BSN 637/KEP/BSN/12/2024 (SNI 8290-5:2024 Mutu 1) & versi 2016, salinan Dinas Peternakan Lampung & Blitar — sumber sekunder, cek ke dokumen BSN',
                'sumber' => 'Kepka BSN 637/KEP/BSN/12/2024 (SNI 8290-5:2024 Mutu 1) & versi 2016, salinan Dinas Peternakan Lampung & Blitar — sumber sekunder, cek ke dokumen BSN',
                'organisasi_sumber' => 'BSN',
                'is_resmi' => true,
                'verified' => false,
                'parameter_tambahan' => [
                    'status_verifikasi' => 'sumber_sekunder',
                    'air_max' => 13.0,
                    'abu_max' => 14.0,
                    'lemak_min' => 3.0,
                    'lisin_min' => 0.70,
                    'metionin_min' => 0.35,
                    'energi_label' => 'EM 2024 belum terverifikasi',
                    'fosfor_label' => 'P belum terverifikasi',
                    'em_2016_min' => 2700,
                    'ca_2016' => '3,25–4,25%',
                    'fosfor_konflik' => 'P min 0,45% bertentangan antar sumber (0,45/0,55 vs 0,6–0,9%)',
                ],
                'deskripsi' => 'Versi 2016 mencantumkan Ca 3,25–4,25% dan EM min 2.700 kkal/kg (EM edisi 2024 belum terverifikasi). Nilai P total bertentangan antar sumber (0,45/0,55 vs 0,6–0,9%).',
            ],
            [
                'kode' => 'SNI_8290_2_M1',
                'nama' => 'SNI 8290-2 Mutu 1 (Starter 5–10 Minggu)',
                'kategori_fase' => 'starter',
                'tahun' => null,
                'edition_year' => '2016/2024 (belum pasti)',
                'protein_min' => 19.0,
                'energi_min' => null,
                'kalsium_min' => 0.70,
                'kalsium_max' => 1.20,
                'fosfor_min' => 0.55,
                'fosfor_max' => null,
                'serat_max' => 7.0,
                'source' => 'Salinan SNI 8290-2 Mutu 1 (tahun edisi 2016 atau 2024 belum pasti), Dinas Peternakan Lampung & Blitar — sumber sekunder, cek ke dokumen BSN',
                'sumber' => 'Salinan SNI 8290-2 Mutu 1 (tahun edisi 2016 atau 2024 belum pasti), Dinas Peternakan Lampung & Blitar — sumber sekunder, cek ke dokumen BSN',
                'organisasi_sumber' => 'BSN',
                'is_resmi' => true,
                'verified' => false,
                'parameter_tambahan' => [
                    'status_verifikasi' => 'sumber_sekunder',
                    'air_max' => 13.0,
                    'abu_max' => 8.0,
                    'lemak_min' => 3.0,
                    'lisin_min' => 0.90,
                    'metionin_min' => 0.40,
                    'aflatoksin_max_ug_kg' => 40,
                    'energi_label' => 'Tidak di sumber',
                ],
                'deskripsi' => 'Pakan starter 5–10 minggu Mutu 1. Tahun edisi (2016 atau 2024) pada salinan sekunder belum dapat dipastikan sehingga ditandai belum terverifikasi.',
            ],
            [
                'kode' => 'SNI_8290_1_PRE_STARTER',
                'nama' => 'SNI 8290-1 (angka edisi 2016)',
                'kategori_fase' => 'pre-starter',
                'tahun' => 2016,
                'edition_year' => '2016 (revisi 2024 belum ada angka)',
                'protein_min' => 20.0,
                'energi_min' => null,
                'kalsium_min' => null,
                'kalsium_max' => null,
                'fosfor_min' => null,
                'fosfor_max' => null,
                'serat_max' => null,
                'source' => 'Kepka BSN 634/KEP/BSN/12/2024; angka 2024 belum ada, air maks 13% & protein min 20% dari versi 2016 (Dinas Peternakan Lampung & Blitar — sumber sekunder, cek ke dokumen BSN)',
                'sumber' => 'Kepka BSN 634/KEP/BSN/12/2024; angka 2024 belum ada, air maks 13% & protein min 20% dari versi 2016 (Dinas Peternakan Lampung & Blitar — sumber sekunder, cek ke dokumen BSN)',
                'organisasi_sumber' => 'BSN',
                'is_resmi' => true,
                'verified' => false,
                'parameter_tambahan' => [
                    'status_verifikasi' => 'sumber_sekunder',
                    'air_max' => 13.0,
                    'energi_label' => 'Belum ada data',
                ],
                'deskripsi' => 'Pre-starter 1–4 minggu. Revisi Kepka BSN 634/KEP/BSN/12/2024 belum memiliki angka; air maks 13% dan protein min 20% berasal dari edisi 2016 (belum terverifikasi).',
            ],
            [
                'kode' => 'SNI_7783_3_2013',
                'nama' => 'SNI 7783.3-2013 (Ayam Buras Petelur)',
                'kategori_fase' => 'produksi',
                'tahun' => 2013,
                'edition_year' => '2013',
                'protein_min' => null,
                'energi_min' => null,
                'kalsium_min' => 2.75,
                'kalsium_max' => 4.25,
                'fosfor_min' => null,
                'fosfor_max' => null,
                'serat_max' => null,
                'source' => 'SNI 7783.3-2013 Pakan Ayam Buras Bagian 3: Bertelur — sumber sekunder, cek ke dokumen BSN (hanya Ca 2,75–4,25% yang terverifikasi)',
                'sumber' => 'SNI 7783.3-2013 Pakan Ayam Buras Bagian 3: Bertelur — sumber sekunder, cek ke dokumen BSN (hanya Ca 2,75–4,25% yang terverifikasi)',
                'organisasi_sumber' => 'BSN',
                'is_resmi' => true,
                'verified' => false,
                'parameter_tambahan' => [
                    'status_verifikasi' => 'sumber_sekunder',
                    'energi_label' => 'Belum terverifikasi',
                    'fosfor_label' => 'Belum terverifikasi',
                ],
                'deskripsi' => 'Pakan ayam buras masa bertelur (SNI 7783.3-2013). Hanya rentang kalsium 2,75–4,25% yang terverifikasi; parameter lainnya belum terverifikasi.',
            ],
            [
                'kode' => 'ISA_BROWN',
                'nama' => 'Isa Brown Nutrition Guide (Fase Produksi)',
                'kategori_fase' => 'produksi',
                'tahun' => null,
                'edition_year' => 'belum diketahui',
                'protein_min' => null,
                'energi_min' => null,
                'kalsium_min' => null,
                'kalsium_max' => null,
                'fosfor_min' => null,
                'fosfor_max' => null,
                'serat_max' => null,
                'source' => 'Hendrix Genetics - Isa Brown Nutrition Guide (angka fase produksi belum tersedia; menunggu dokumen resmi breeder)',
                'sumber' => 'Hendrix Genetics - Isa Brown Nutrition Guide (angka fase produksi belum tersedia; menunggu dokumen resmi breeder)',
                'organisasi_sumber' => 'Hendrix Genetics',
                'is_resmi' => true,
                'verified' => false,
                'parameter_tambahan' => [
                    'energi_label' => 'Belum ada data',
                    'fosfor_label' => 'Belum ada data',
                ],
                'deskripsi' => 'Pedoman strain Isa Brown fase produksi. Angka nutrisi belum dimasukkan hingga dokumen resmi breeder diverifikasi.',
            ],
            [
                'kode' => 'NRC_1994',
                'nama' => 'NRC Nutrient Requirements of Poultry (Edisi ke-9, 1994)',
                'kategori_fase' => 'produksi',
                'tahun' => 1994,
                'edition_year' => '1994 (Edisi ke-9)',
                'protein_min' => null,
                'energi_min' => null,
                'kalsium_min' => null,
                'kalsium_max' => null,
                'fosfor_min' => null,
                'fosfor_max' => null,
                'serat_max' => null,
                'source' => 'National Research Council (NRC), Nutrient Requirements of Poultry, 9th Revised Edition (1994) — tabel primer belum dibuka langsung, angka dikosongkan (cek NRC 1994 atau NASEM Edisi ke-10 tahun 2026)',
                'sumber' => 'National Research Council (NRC), Nutrient Requirements of Poultry, 9th Revised Edition (1994) — tabel primer belum dibuka langsung, angka dikosongkan (cek NRC 1994 atau NASEM Edisi ke-10 tahun 2026)',
                'organisasi_sumber' => 'NRC (1994)',
                'is_resmi' => true,
                'verified' => false,
                'parameter_tambahan' => [
                    'energi_label' => 'Belum ada data',
                    'fosfor_label' => 'Belum ada data',
                ],
                'deskripsi' => 'Rujukan NRC Edisi ke-9 (1994). Angka nutrisi dikosongkan karena tabel dokumen primer belum dibuka langsung; dapat dilengkapi setelah verifikasi ke dokumen NRC 1994 atau NASEM Edisi ke-10 (2026).',
            ],
        ];
    }

    /**
     * Daftar kode katalog aktif saat ini.
     *
     * @return array<int, string>
     */
    public static function validCatalogCodes(): array
    {
        return array_values(array_map(
            static fn (array $item): string => $item['kode'],
            self::officialStandards()
        ));
    }

    /**
     * Periksa apakah katalog pada kandang perlu ditimpa dengan versi katalog terbaru.
     */
    public function needsSync(Farm $farm): bool
    {
        $validCodes = self::validCatalogCodes();
        $expectedTotal = count($validCodes);

        if (NutrientStandard::whereNull('farm_id')->exists()) {
            return true;
        }

        if (
            $farm->standards()
                ->where(function ($query) use ($validCodes): void {
                    $query->where('is_resmi', true)->whereNotIn('kode', $validCodes);
                })
                ->orWhere(function ($query) use ($farm): void {
                    $query->where('farm_id', $farm->id)
                        ->whereNull('kode')
                        ->where('nama', 'like', '%SNI 2016%');
                })
                ->exists()
        ) {
            return true;
        }

        $syncedCurrentVersion = $farm->standards()
            ->where('is_resmi', true)
            ->where('dataset_version', self::DATASET_VERSION)
            ->count();

        return $syncedCurrentVersion !== $expectedTotal;
    }

    /**
     * Simpan snapshot nama dan batasan standar ke seluruh ransum tersimpan yang merujuk standar ini
     * sebelum standar dihapus dari katalog, sehingga riwayat ransum tidak kehilangan konteks.
     */
    public function archiveStandardOnRations(NutrientStandard $standard): void
    {
        $rations = Ration::where('standard_id', $standard->id)->get();

        foreach ($rations as $ration) {
            $hasil = is_array($ration->hasil) ? $ration->hasil : [];
            $hasil['standar_nama'] = $hasil['standar_nama'] ?? $standard->nama;
            $hasil['standar_kode'] = $hasil['standar_kode'] ?? $standard->kode;
            $hasil['standar_terhapus'] = true;
            $hasil['parameter_tidak_dibatasi'] = $hasil['parameter_tidak_dibatasi'] ?? $standard->unconstrainedParameters();

            $ration->update(['hasil' => $hasil]);
        }
    }

    /**
     * Sinkronkan katalog ke kandang aktif dan hapus seluruh entri katalog lama yang sudah dicabut.
     */
    public function syncOfficialStandards(Farm $farm): int
    {
        $standards = self::officialStandards();
        $validCodes = self::validCatalogCodes();

        $globalObsolete = NutrientStandard::whereNull('farm_id')->get();
        foreach ($globalObsolete as $obsolete) {
            $this->archiveStandardOnRations($obsolete);
            $obsolete->delete();
        }

        $farmObsolete = $farm->standards()
            ->where('is_resmi', true)
            ->where(function ($query) use ($validCodes): void {
                $query->whereNull('kode')->orWhereNotIn('kode', $validCodes);
            })
            ->get();

        foreach ($farmObsolete as $obsolete) {
            $this->archiveStandardOnRations($obsolete);
            $obsolete->delete();
        }

        $legacySni = $farm->standards()
            ->whereNull('kode')
            ->where('nama', 'like', '%SNI 2016%')
            ->get();

        foreach ($legacySni as $obsolete) {
            $this->archiveStandardOnRations($obsolete);
            $obsolete->delete();
        }

        $syncedCount = 0;

        foreach ($standards as $std) {
            $payload = [
                'kode' => $std['kode'],
                'nama' => $std['nama'],
                'kategori_fase' => $std['kategori_fase'],
                'tahun' => $std['tahun'],
                'edition_year' => $std['edition_year'],
                'protein_min' => $std['protein_min'],
                'energi_min' => $std['energi_min'],
                'kalsium_min' => $std['kalsium_min'],
                'kalsium_max' => $std['kalsium_max'],
                'fosfor_min' => $std['fosfor_min'],
                'fosfor_max' => $std['fosfor_max'],
                'serat_max' => $std['serat_max'],
                'source' => $std['source'],
                'sumber' => $std['sumber'],
                'organisasi_sumber' => $std['organisasi_sumber'],
                'is_resmi' => true,
                'verified' => $std['verified'],
                'dataset_version' => self::DATASET_VERSION,
                'parameter_tambahan' => $std['parameter_tambahan'],
            ];

            $existing = $farm->standards()->where('kode', $std['kode'])->first();

            if (! $existing) {
                $farm->standards()->create($payload);
            } else {
                $existing->update($payload);
            }

            $syncedCount++;
        }

        return $syncedCount;
    }

    /**
     * Cari rekomendasi standar terbaik berdasarkan profil fase dan umur kandang.
     * Hanya standar dengan verified=true yang boleh dipilih oleh Auto-Match.
     */
    public function getRecommendedStandard(Farm $farm): ?NutrientStandard
    {
        if (! $farm->standards()->exists()) {
            $this->syncOfficialStandards($farm);
        }

        $verifiedQuery = static fn () => $farm->standards()->where('verified', true);

        $minggu = $farm->umurMinggu();
        $fase = strtolower((string) ($farm->fase ?? 'produksi'));
        $isRearingPhase = in_array($fase, ['pre-starter', 'starter', 'grower'], true) || $minggu < 19;

        if ($isRearingPhase) {
            if ($fase === 'pre-starter' || $minggu <= 4) {
                return $verifiedQuery()->where('kategori_fase', 'pre-starter')->first();
            }

            if ($fase === 'starter' || ($minggu >= 5 && $minggu <= 10)) {
                return $verifiedQuery()->where('kategori_fase', 'starter')->first();
            }

            return $verifiedQuery()->where('kategori_fase', 'grower')->first();
        }

        if ($minggu > 70) {
            $std = $verifiedQuery()->where('kode', 'LOHMANN_CLASSIC_3')->first();
            if ($std) {
                return $std;
            }
        }

        if ($minggu > 50) {
            $std = $verifiedQuery()->where('kode', 'LOHMANN_CLASSIC_2')->first();
            if ($std) {
                return $std;
            }
        }

        $layerVerified = $verifiedQuery()->where('kode', 'LOHMANN_CLASSIC_1')->first()
            ?? $verifiedQuery()->where('kategori_fase', 'produksi')->first();

        if ($layerVerified) {
            return $layerVerified;
        }

        return $verifiedQuery()->orderBy('id')->first();
    }

    /**
     * Dapatkan status metadata sinkronisasi katalog.
     *
     * @return array{
     *     status: string,
     *     versi: string,
     *     terakhir_sinkron: string,
     *     penyedia: string,
     *     otoritas: string,
     *     total_katalog: int
     * }
     */
    public static function getSyncMetadata(): array
    {
        return [
            'status' => 'synced',
            'versi' => self::DATASET_VERSION,
            'terakhir_sinkron' => now()->translatedFormat('d F Y, H:i') . ' WIB',
            'penyedia' => 'Repositori Katalog Nutrisi RansumKu',
            'otoritas' => 'Salinan SNI (BSN), Lohmann Breeders, Hendrix Genetics & NRC',
            'total_katalog' => count(self::officialStandards()),
        ];
    }
}
