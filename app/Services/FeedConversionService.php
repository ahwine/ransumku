<?php

namespace App\Services;

class FeedConversionService
{
    /**
     * Konversi campuran pakan berbasis Bahan Kering (BK) ke takaran pakan segar (As-Fed) di lapangan.
     *
     * @param  array<int, array{nama:string, kg:float, persen:float, harga:float, bk?:float|null, id?:int|string|null}>  $items
     * @return array{
     *     total_bk_kg: float,
     *     total_as_fed_kg: float,
     *     total_biaya: float,
     *     biaya_per_kg_as_fed: float,
     *     items: array<int, array{
     *         id: int|string|null,
     *         nama: string,
     *         bk: float,
     *         kg_bk: float,
     *         persen_bk: float,
     *         kg_as_fed: float,
     *         persen_as_fed: float,
     *         biaya: float
     *     }>
     * }
     */
    public function convertToAsFed(array $items): array
    {
        $converted = [];
        $totalBkKg = 0.0;
        $totalAsFedKg = 0.0;
        $totalBiaya = 0.0;

        foreach ($items as $item) {
            $kgBk = (float) ($item['kg'] ?? 0.0);
            $bkPercent = (float) ($item['bk'] ?? 88.0);
            if ($bkPercent <= 0.0) {
                $bkPercent = 88.0;
            }

            // Berat As-Fed (segar) = Berat BK / (%BK / 100)
            $kgAsFed = $kgBk / ($bkPercent / 100.0);
            $harga = (float) ($item['harga'] ?? 0.0);
            $biaya = (float) ($item['biaya'] ?? ($kgBk * $harga));

            $totalBkKg += $kgBk;
            $totalAsFedKg += $kgAsFed;
            $totalBiaya += $biaya;

            $converted[] = [
                'id' => $item['id'] ?? null,
                'nama' => (string) $item['nama'],
                'bk' => round($bkPercent, 1),
                'kg_bk' => round($kgBk, 3),
                'persen_bk' => (float) ($item['persen'] ?? 0.0),
                'kg_as_fed' => round($kgAsFed, 3),
                'persen_as_fed' => 0.0, // dihitung setelah total As-Fed diketahui
                'biaya' => round($biaya),
            ];
        }

        // Hitung proporsi persentase As-Fed untuk mixing sheet anak kandang
        foreach ($converted as &$row) {
            $row['persen_as_fed'] = $totalAsFedKg > 0.0
                ? round(($row['kg_as_fed'] / $totalAsFedKg) * 100.0, 2)
                : 0.0;
        }
        unset($row);

        $biayaPerKgAsFed = $totalAsFedKg > 0.0 ? round($totalBiaya / $totalAsFedKg) : 0.0;

        return [
            'total_bk_kg' => round($totalBkKg, 2),
            'total_as_fed_kg' => round($totalAsFedKg, 2),
            'total_biaya' => round($totalBiaya),
            'biaya_per_kg_as_fed' => (float) $biayaPerKgAsFed,
            'items' => $converted,
        ];
    }

    /**
     * Hitung lembar aduk (Mixing Sheet) untuk kapasitas wadah mixer tertentu (misal 100 kg segar).
     *
     * @param  array<int, array{nama:string, persen_as_fed:float, bk:float}>  $asFedItems
     * @param  float  $targetBatchKg  Kapasitas mixer (kg segar)
     * @return array<int, array{nama:string, persen_as_fed:float, kg_timbang:float, bk:float}>
     */
    public function hitungMixingSheet(array $asFedItems, float $targetBatchKg): array
    {
        $sheet = [];
        foreach ($asFedItems as $item) {
            $persen = (float) ($item['persen_as_fed'] ?? 0.0);
            $sheet[] = [
                'nama' => $item['nama'],
                'persen_as_fed' => $persen,
                'kg_timbang' => round(($persen / 100.0) * $targetBatchKg, 2),
                'bk' => (float) ($item['bk'] ?? 88.0),
            ];
        }

        return $sheet;
    }
}

