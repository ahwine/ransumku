<?php

namespace App\Services;

class RationSafetyValidator
{
    /**
     * Memeriksa ambang batas aman penggunaan bahan dan keseimbangan nutrisi pakan unggas.
     *
     * @param  array<int, array{
     *     nama: string,
     *     persen: float,
     *     kg: float,
     *     min_persen?: float|null,
     *     max_persen?: float|null,
     *     catatan_keamanan?: string|null
     * }>  $items
     * @param  array{
     *     protein?: float,
     *     kalsium?: float,
     *     fosfor?: float,
     *     serat?: float,
     *     energi?: float
     * }  $gizi
     * @param  array<string, mixed>|null  $target
     * @return array{
     *     aman: bool,
     *     peringatan: array<int, array{
     *         level: string,
     *         judul: string,
     *         pesan: string,
     *         bahan: ?string
     *     }>
     * }
     */
    public function validate(array $items, array $gizi, ?array $target = null): array
    {
        $peringatan = [];

        // 1. Validasi batas toleransi minimum dan maksimum per bahan
        foreach ($items as $item) {
            $nama = (string) $item['nama'];
            $persen = (float) ($item['persen'] ?? 0.0);
            $maxPersen = isset($item['max_persen']) ? (float) $item['max_persen'] : 100.0;
            $minPersen = isset($item['min_persen']) ? (float) $item['min_persen'] : 0.0;
            $catatan = $item['catatan_keamanan'] ?? null;

            // Larangan NPN / Urea untuk unggas
            if (stripos($nama, 'urea') !== false) {
                $peringatan[] = [
                    'level' => 'danger',
                    'judul' => 'Bahan Berbahaya / Toksik',
                    'pesan' => "Bahan '{$nama}' mengandung urea/NPN yang tidak dapat dicerna oleh saluran pencernaan unggas dan berisiko keracunan fatal.",
                    'bahan' => $nama,
                ];
            }

            // Batas maksimum
            if ($persen > $maxPersen + 0.05) {
                $peringatan[] = [
                    'level' => 'danger',
                    'judul' => "Melebihi Batas Maksimum: {$nama}",
                    'pesan' => sprintf(
                        'Penggunaan %s (%.1f%%) melebihi batas aman maksimal (%.1f%%). %s',
                        $nama, $persen, $maxPersen, $catatan ?: 'Dapat memicu gangguan pencernaan atau penurunan nafsu makan.'
                    ),
                    'bahan' => $nama,
                ];
            } elseif ($persen >= $maxPersen * 0.9 && $maxPersen < 100.0) {
                $peringatan[] = [
                    'level' => 'info',
                    'judul' => "Mendekati Batas Maksimum: {$nama}",
                    'pesan' => sprintf(
                        'Penggunaan %s (%.1f%%) mendekati batas anjuran (maksimal %.1f%%).',
                        $nama, $persen, $maxPersen
                    ),
                    'bahan' => $nama,
                ];
            }

            // Batas minimum
            if ($minPersen > 0.0 && $persen < $minPersen - 0.05) {
                $peringatan[] = [
                    'level' => 'warning',
                    'judul' => "Kurang dari Batas Minimum: {$nama}",
                    'pesan' => sprintf(
                        'Penggunaan %s (%.1f%%) di bawah batas minimum yang disyaratkan (%.1f%%).',
                        $nama, $persen, $minPersen
                    ),
                    'bahan' => $nama,
                ];
            }
        }

        // 2. Validasi Keseimbangan Serat Kasar
        $serat = (float) ($gizi['serat'] ?? 0.0);
        if ($serat > 7.0) {
            $peringatan[] = [
                'level' => 'warning',
                'judul' => 'Kandungan Serat Kasar Terlalu Tinggi',
                'pesan' => sprintf(
                    'Serat kasar ransum mencapai %.2f%% (di atas batas wajar ayam petelur 6.0-7.0%%). Serat berlebih mempercepat laju pakan di usus dan menurunkan penyerapan nutrisi penting.',
                    $serat
                ),
                'bahan' => null,
            ];
        }

        // 3. Validasi Rasio Mineral Ca : P
        $kalsium = (float) ($gizi['kalsium'] ?? 0.0);
        $fosfor = (float) ($gizi['fosfor'] ?? 0.0);

        if ($fosfor > 0.0) {
            $ratio = $kalsium / $fosfor;

            if ($ratio < 1.0) {
                $peringatan[] = [
                    'level' => 'danger',
                    'judul' => 'Rasio Ca : P Terbalik',
                    'pesan' => sprintf(
                        'Rasio Kalsium terhadap Fosfor terbalik (%.2f : 1). Fosfor lebih tinggi dari Kalsium berisiko mengganggu absorpsi mineral dan merusak integritas tulang.',
                        $ratio
                    ),
                    'bahan' => 'Kalsium & Fosfor',
                ];
            } elseif ($kalsium > 4.6) {
                $peringatan[] = [
                    'level' => 'warning',
                    'judul' => 'Kadar Kalsium Terlalu Tinggi',
                    'pesan' => sprintf(
                        'Kadar Kalsium total (%.2f%%) melampaui batas atas wajar (4.25-4.50%%). Kalsium berlebih dapat memicu kotoran basah (wet droppings) dan mengganggu fungsi ginjal.',
                        $kalsium
                    ),
                    'bahan' => 'Kalsium',
                ];
            }
        }

        $adaDanger = false;
        foreach ($peringatan as $p) {
            if ($p['level'] === 'danger') {
                $adaDanger = true;
                break;
            }
        }

        return [
            'aman' => ! $adaDanger,
            'peringatan' => $peringatan,
        ];
    }
}
