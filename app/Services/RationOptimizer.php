<?php

namespace App\Services;

/**
 * Pengoptimal ransum berbasis linear programming (simplex dua fase), murni PHP.
 *
 * Masalah: minimalkan sum(harga_i * kg_i)
 *   dengan sum(kg_i) = total, 0 <= kg_i <= stok_i, dan kandungan gizi campuran
 *   memenuhi target (protein min, kalsium min-maks, fosfor min-maks, energi min, serat maks).
 *
 * Semua angka dihitung di sini (BUKAN oleh LLM).
 */
class RationOptimizer
{
    private const EPS = 1e-9;

    /**
     * @param  array<int,array{id:int|string,nama:string,harga:float,stok:float|null,protein:float,kalsium:float,fosfor:float,serat:float,energi:float}>  $ingredients
     *                                                                                                                                                                  stok null = tidak dibatasi.
     * @param  array{protein_min:float,kalsium_min:float,kalsium_max:float,fosfor_min:float,fosfor_max:float,serat_max:?float,energi_min:float}  $target
     * @return array{feasible:bool,items:array,total_biaya:float,biaya_per_kg:float,gizi:array,pesan:array}
     */
    public function optimize(array $ingredients, array $target, float $totalKg): array
    {
        $ingredients = array_values($ingredients);
        $n = count($ingredients);

        if ($n === 0 || $totalKg <= 0) {
            return $this->gagal(['Tambahkan minimal satu bahan dan isi jumlah campuran lebih dari 0 kg.']);
        }

        $adaTarget = false;
        foreach (['protein_min', 'kalsium_min', 'kalsium_max', 'fosfor_min', 'fosfor_max', 'energi_min', 'serat_max'] as $kTarget) {
            if (($target[$kTarget] ?? null) !== null) {
                $adaTarget = true;
                break;
            }
        }
        if (! $adaTarget) {
            return $this->gagal([
                'Standar acuan yang dipilih belum memiliki angka batas nutrisi. Lengkapi data acuan terlebih dahulu atau pilih standar yang sudah terverifikasi.',
            ]);
        }

        // Pengecekan awal yang ramah: stok total & gizi per nutrisi
        $pesan = $this->diagnosa($ingredients, $target, $totalKg);
        if ($pesan !== []) {
            return $this->gagal($pesan);
        }

        $c = array_map(fn ($i) => (float) $i['harga'], $ingredients);

        $rows = [];
        $nol = array_fill(0, $n, 0.0);

        // total berat = jumlah campuran
        $rows[] = [array_fill(0, $n, 1.0), '=', $totalKg];

        // tiap bahan <= stok, <= batas maksimum persen, dan >= batas minimum persen
        foreach ($ingredients as $k => $ing) {
            if ($ing['stok'] !== null) {
                $r = $nol;
                $r[$k] = 1.0;
                $rows[] = [$r, '<=', (float) $ing['stok']];
            }

            $maxP = isset($ing['max_persen']) && $ing['max_persen'] !== null ? (float) $ing['max_persen'] : null;
            if ($maxP !== null && $maxP < 100.0) {
                $r = $nol;
                $r[$k] = 1.0;
                $rows[] = [$r, '<=', (float) (($maxP / 100.0) * $totalKg)];
            }

            $minP = isset($ing['min_persen']) && $ing['min_persen'] !== null ? (float) $ing['min_persen'] : null;
            if ($minP !== null && $minP > 0.0) {
                $r = $nol;
                $r[$k] = 1.0;
                $rows[] = [$r, '>=', (float) (($minP / 100.0) * $totalKg)];
            }
        }

        $coef = fn (string $key) => array_map(fn ($i) => (float) $i[$key], $ingredients);

        if (($target['protein_min'] ?? null) !== null) {
            $rows[] = [$coef('protein'), '>=', $target['protein_min'] * $totalKg];
        }
        if (($target['kalsium_min'] ?? null) !== null) {
            $rows[] = [$coef('kalsium'), '>=', $target['kalsium_min'] * $totalKg];
        }
        if (($target['kalsium_max'] ?? null) !== null) {
            $rows[] = [$coef('kalsium'), '<=', $target['kalsium_max'] * $totalKg];
        }
        if (($target['fosfor_min'] ?? null) !== null) {
            $rows[] = [$coef('fosfor'), '>=', $target['fosfor_min'] * $totalKg];
        }
        if (($target['fosfor_max'] ?? null) !== null) {
            $rows[] = [$coef('fosfor'), '<=', $target['fosfor_max'] * $totalKg];
        }
        if (($target['energi_min'] ?? null) !== null) {
            $rows[] = [$coef('energi'), '>=', $target['energi_min'] * $totalKg];
        }
        if (($target['serat_max'] ?? null) !== null) {
            $rows[] = [$coef('serat'), '<=', $target['serat_max'] * $totalKg];
        }

        try {
            $x = $this->simplex($c, $rows);
        } catch (\Throwable $e) {
            return $this->gagal(['Perhitungan gagal diproses. Coba ubah bahan atau stok, lalu hitung ulang.']);
        }

        if ($x === null) {
            return $this->gagal([
                'Kombinasi target gizi tidak bisa dipenuhi bersamaan dengan bahan dan stok yang ada. '
                .'Coba tambah bahan sumber protein/kalsium atau tambah stok.',
            ]);
        }

        return $this->susunHasil($ingredients, $x, $totalKg);
    }

    /** Hasil sukses: item, biaya, dan kandungan gizi aktual. */
    private function susunHasil(array $ingredients, array $x, float $totalKg): array
    {
        $items = [];
        $totalBiaya = 0.0;
        $gizi = ['protein' => 0.0, 'kalsium' => 0.0, 'fosfor' => 0.0, 'serat' => 0.0, 'energi' => 0.0];

        foreach ($ingredients as $k => $ing) {
            $kg = max(0.0, $x[$k]);
            if ($kg < 1e-6) {
                continue;
            }
            $biaya = $kg * $ing['harga'];
            $totalBiaya += $biaya;
            foreach ($gizi as $key => $_) {
                $gizi[$key] += $kg * $ing[$key];
            }
            $items[] = [
                'id' => $ing['id'],
                'nama' => $ing['nama'],
                'kg' => $kg,
                'persen' => $kg / $totalKg * 100,
                'bk' => (float) ($ing['bk'] ?? 88.0),
                'min_persen' => (float) ($ing['min_persen'] ?? 0.0),
                'max_persen' => (float) ($ing['max_persen'] ?? 100.0),
                'catatan_keamanan' => $ing['catatan_keamanan'] ?? null,
                'harga' => $ing['harga'],
                'biaya' => $biaya,
            ];
        }

        foreach ($gizi as $key => $v) {
            $gizi[$key] = $v / $totalKg;
        }

        return [
            'feasible' => true,
            'items' => $items,
            'total_biaya' => $totalBiaya,
            'biaya_per_kg' => $totalBiaya / $totalKg,
            'gizi' => $gizi,
            'pesan' => [],
        ];
    }

    private function gagal(array $pesan): array
    {
        return [
            'feasible' => false,
            'items' => [],
            'total_biaya' => 0.0,
            'biaya_per_kg' => 0.0,
            'gizi' => [],
            'pesan' => $pesan,
        ];
    }

    /**
     * Cek tiap kendala secara terpisah supaya pesan error menjelaskan nutrisi mana
     * yang tidak mungkin terpenuhi (bukan error mentah dari solver).
     */
    private function diagnosa(array $ingredients, array $target, float $totalKg): array
    {
        $pesan = [];

        $stokTotal = 0.0;
        $takTerbatas = false;
        foreach ($ingredients as $ing) {
            if ($ing['stok'] === null) {
                $takTerbatas = true;
            } else {
                $stokTotal += (float) $ing['stok'];
            }
        }
        if (! $takTerbatas && $stokTotal + 1e-9 < $totalKg) {
            $pesan[] = sprintf(
                'Stok total bahan (%s kg) kurang dari jumlah campuran (%s kg). Tambah stok sekitar %s kg atau kurangi jumlah campuran.',
                $this->fmt($stokTotal), $this->fmt($totalKg), $this->fmt($totalKg - $stokTotal)
            );

            return $pesan;
        }

        $sumMin = 0.0;
        $sumMax = 0.0;
        foreach ($ingredients as $ing) {
            $minP = isset($ing['min_persen']) && $ing['min_persen'] !== null ? (float) $ing['min_persen'] : 0.0;
            $maxP = isset($ing['max_persen']) && $ing['max_persen'] !== null ? (float) $ing['max_persen'] : 100.0;
            $sumMin += $minP;
            $sumMax += $maxP;

            if ($minP > 0.0 && $ing['stok'] !== null) {
                $minKg = ($minP / 100.0) * $totalKg;
                if ((float) $ing['stok'] + 1e-9 < $minKg) {
                    $pesan[] = sprintf(
                        'Bahan %s memerlukan minimal %s kg (batas min %s%%), tetapi stok yang tersedia hanya %s kg.',
                        $ing['nama'], $this->fmt($minKg), $this->fmt($minP), $this->fmt((float) $ing['stok'])
                    );
                }
            }
        }

        if ($sumMin > 100.0 + 1e-9) {
            $pesan[] = sprintf(
                'Total batas minimum penggunaan bahan (%s%%) melebihi 100%%. Kurangi batas minimum bahan.',
                $this->fmt($sumMin)
            );

            return $pesan;
        }

        if ($sumMax + 1e-9 < 100.0) {
            $pesan[] = sprintf(
                'Total batas maksimum penggunaan bahan (%s%%) kurang dari 100%%. Naikkan batas maksimum bahan agar campuran 100%% bisa dicapai.',
                $this->fmt($sumMax)
            );

            return $pesan;
        }

        $cek = [
            ['protein', 'Protein', '%', $target['protein_min'] ?? null, null, 'min'],
            ['kalsium', 'Kalsium', '%', $target['kalsium_min'] ?? null, $target['kalsium_max'] ?? null, 'rentang'],
            ['fosfor', 'Fosfor', '%', $target['fosfor_min'] ?? null, $target['fosfor_max'] ?? null, 'rentang'],
            ['energi', 'Energi', ' kkal/kg', $target['energi_min'] ?? null, null, 'min'],
        ];

        foreach ($cek as [$key, $label, $sat, $min, $max]) {
            if ($min === null && $max === null) {
                continue;
            }
            $hi = $this->rentangMaks($ingredients, $key, $totalKg, true);
            $lo = $this->rentangMaks($ingredients, $key, $totalKg, false);
            if ($min !== null && $hi + 1e-9 < $min) {
                $pesan[] = sprintf(
                    '%s tidak terpenuhi: target minimal %s%s, paling tinggi yang bisa dicapai %s%s dengan bahan dan stok ini. Tambah bahan sumber %s atau tambah stoknya.',
                    $label, $this->fmt($min), $sat, $this->fmt($hi), $sat, strtolower($label)
                );
            }
            if ($max !== null && $lo - 1e-9 > $max) {
                $pesan[] = sprintf(
                    '%s terlalu tinggi: batas maksimal %s%s, paling rendah yang bisa dicapai %s%s. Tambah bahan dengan %s lebih rendah.',
                    $label, $this->fmt($max), $sat, $this->fmt($lo), $sat, strtolower($label)
                );
            }
        }

        if (($target['serat_max'] ?? null) !== null) {
            $lo = $this->rentangMaks($ingredients, 'serat', $totalKg, false);
            if ($lo - 1e-9 > $target['serat_max']) {
                $pesan[] = sprintf(
                    'Serat kasar terlalu tinggi: batas maksimal %s%%, paling rendah yang bisa dicapai %s%%. Tambah bahan berserat rendah.',
                    $this->fmt($target['serat_max']), $this->fmt($lo)
                );
            }
        }

        return $pesan;
    }

    /**
     * Nilai gizi campuran tertinggi ($tertinggi=true) atau terendah yang mungkin
     * dengan mengisi bahan terbaik dulu sampai batas stok (knapsack pecahan).
     */
    private function rentangMaks(array $ingredients, string $key, float $totalKg, bool $tertinggi): float
    {
        usort($ingredients, fn ($a, $b) => $tertinggi ? $b[$key] <=> $a[$key] : $a[$key] <=> $b[$key]);
        $sisa = $totalKg;
        $sum = 0.0;
        foreach ($ingredients as $ing) {
            $ambil = $ing['stok'] === null ? $sisa : min($sisa, (float) $ing['stok']);
            $sum += $ambil * $ing[$key];
            $sisa -= $ambil;
            if ($sisa <= 1e-12) {
                break;
            }
        }

        return $sum / $totalKg;
    }

    private function fmt(float $v): string
    {
        return rtrim(rtrim(number_format($v, 2, ',', '.'), '0'), ',');
    }

    // ------------------------------------------------------------------
    // Simplex dua fase (aturan Bland agar tidak berputar)
    // ------------------------------------------------------------------

    /**
     * Minimalkan c·x dengan batasan baris [koef[], tipe('<=','>=','='), rhs], x >= 0.
     *
     * @return array<int,float>|null solusi x, atau null bila tidak layak
     */
    private function simplex(array $c, array $rows): ?array
    {
        $n = count($c);
        $m = count($rows);

        // Normalisasi: rhs harus >= 0
        foreach ($rows as &$r) {
            if ($r[2] < 0) {
                $r[0] = array_map(fn ($v) => -$v, $r[0]);
                $r[2] = -$r[2];
                $r[1] = ['<=' => '>=', '>=' => '<=', '=' => '='][$r[1]];
            }
        }
        unset($r);

        // Hitung jumlah kolom tambahan
        $nSlack = 0;
        $nArt = 0;
        foreach ($rows as $r) {
            if ($r[1] === '<=') {
                $nSlack++;
            } elseif ($r[1] === '>=') {
                $nSlack++;
                $nArt++;
            } else {
                $nArt++;
            }
        }

        $cols = $n + $nSlack + $nArt;
        $T = [];
        $basis = [];
        $si = $n;            // indeks slack berikutnya
        $ai = $n + $nSlack;  // indeks artificial berikutnya
        $artCols = [];

        foreach ($rows as $i => $r) {
            $line = array_fill(0, $cols + 1, 0.0);
            foreach ($r[0] as $j => $v) {
                $line[$j] = (float) $v;
            }
            $line[$cols] = (float) $r[2];

            if ($r[1] === '<=') {
                $line[$si] = 1.0;
                $basis[$i] = $si++;
            } elseif ($r[1] === '>=') {
                $line[$si++] = -1.0;
                $line[$ai] = 1.0;
                $artCols[] = $ai;
                $basis[$i] = $ai++;
            } else {
                $line[$ai] = 1.0;
                $artCols[] = $ai;
                $basis[$i] = $ai++;
            }
            $T[$i] = $line;
        }

        // Fase 1: minimalkan jumlah variabel artificial
        if ($nArt > 0) {
            $c1 = array_fill(0, $cols, 0.0);
            foreach ($artCols as $a) {
                $c1[$a] = 1.0;
            }
            $this->jalankan($T, $basis, $c1, $cols, []);
            $obj = 0.0;
            foreach ($basis as $i => $b) {
                $obj += $c1[$b] * $T[$i][$cols];
            }
            if ($obj > 1e-6) {
                return null; // tidak layak
            }

            // Keluarkan artificial yang masih di basis (bernilai 0)
            foreach ($basis as $i => $b) {
                if (in_array($b, $artCols, true)) {
                    for ($j = 0; $j < $n + $nSlack; $j++) {
                        if (abs($T[$i][$j]) > 1e-7) {
                            $this->pivot($T, $basis, $i, $j, $cols);
                            break;
                        }
                    }
                }
            }
        }

        // Fase 2: fungsi tujuan asli; kolom artificial dilarang masuk basis
        $c2 = array_fill(0, $cols, 0.0);
        foreach ($c as $j => $v) {
            $c2[$j] = (float) $v;
        }
        $ok = $this->jalankan($T, $basis, $c2, $cols, $artCols);
        if (! $ok) {
            return null; // tak terbatas (tidak mungkin pada kasus ini)
        }

        $x = array_fill(0, $n, 0.0);
        foreach ($basis as $i => $b) {
            if ($b < $n) {
                $x[$b] = $T[$i][$cols];
            }
        }

        return $x;
    }

    /** Iterasi simplex hingga optimal. Return false jika tak terbatas. */
    private function jalankan(array &$T, array &$basis, array $cost, int $cols, array $terlarang): bool
    {
        $m = count($T);
        $maxIter = 5000;

        while ($maxIter-- > 0) {
            // biaya tereduksi: c_j - sum(c_B * T[i][j])
            $masuk = -1;
            for ($j = 0; $j < $cols; $j++) {
                if (in_array($j, $terlarang, true) || in_array($j, $basis, true)) {
                    continue;
                }
                $rc = $cost[$j];
                for ($i = 0; $i < $m; $i++) {
                    $rc -= $cost[$basis[$i]] * $T[$i][$j];
                }
                if ($rc < -1e-9) {
                    $masuk = $j; // aturan Bland: indeks terkecil
                    break;
                }
            }
            if ($masuk === -1) {
                return true;
            }

            // uji rasio minimum
            $keluar = -1;
            $best = INF;
            for ($i = 0; $i < $m; $i++) {
                if ($T[$i][$masuk] > 1e-9) {
                    $ratio = $T[$i][$cols] / $T[$i][$masuk];
                    if ($ratio < $best - 1e-12 || (abs($ratio - $best) <= 1e-12 && $basis[$i] < $basis[$keluar])) {
                        $best = $ratio;
                        $keluar = $i;
                    }
                }
            }
            if ($keluar === -1) {
                return false;
            }

            $this->pivot($T, $basis, $keluar, $masuk, $cols);
        }

        throw new \RuntimeException('Simplex melebihi batas iterasi');
    }

    private function pivot(array &$T, array &$basis, int $baris, int $kolom, int $cols): void
    {
        $p = $T[$baris][$kolom];
        for ($j = 0; $j <= $cols; $j++) {
            $T[$baris][$j] /= $p;
        }
        foreach ($T as $i => $_) {
            if ($i === $baris) {
                continue;
            }
            $f = $T[$i][$kolom];
            if (abs($f) < self::EPS) {
                continue;
            }
            for ($j = 0; $j <= $cols; $j++) {
                $T[$i][$j] -= $f * $T[$baris][$j];
            }
        }
        $basis[$baris] = $kolom;
    }
}
