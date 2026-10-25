<?php

namespace Tests\Unit;

use App\Services\RationOptimizer;
use PHPUnit\Framework\TestCase;

/**
 * Test wajib optimizer. Data = angka ILUSTRASI dari seeder (bukan data resmi).
 */
class RationOptimizerTest extends TestCase
{
    private function bahan(int $hargaDedak = 4500): array
    {
        return [
            ['id' => 1, 'nama' => 'Jagung', 'harga' => 6500, 'stok' => 50, 'protein' => 8.5, 'kalsium' => 0.03, 'fosfor' => 0.28, 'serat' => 2.2, 'energi' => 3300],
            ['id' => 2, 'nama' => 'Dedak', 'harga' => $hargaDedak, 'stok' => 30, 'protein' => 12, 'kalsium' => 0.10, 'fosfor' => 1.2, 'serat' => 11, 'energi' => 2400],
            ['id' => 3, 'nama' => 'Konsentrat', 'harga' => 13000, 'stok' => 20, 'protein' => 38, 'kalsium' => 5.0, 'fosfor' => 1.5, 'serat' => 6, 'energi' => 2300],
            ['id' => 4, 'nama' => 'Kapur', 'harga' => 2500, 'stok' => 10, 'protein' => 0, 'kalsium' => 38, 'fosfor' => 0, 'serat' => 0, 'energi' => 0],
        ];
    }

    private function target(): array
    {
        return [
            'protein_min' => 16.5, 'kalsium_min' => 3.25, 'kalsium_max' => 4.25,
            'fosfor_min' => 0.6, 'fosfor_max' => 0.9, 'serat_max' => null, 'energi_min' => 2700,
        ];
    }

    private function kg(array $hasil): array
    {
        $out = [];
        foreach ($hasil['items'] as $i) {
            $out[$i['nama']] = $i['kg'];
        }

        return $out;
    }

    public function test_komposisi_optimal_data_seeder_40kg(): void
    {
        $r = (new RationOptimizer)->optimize($this->bahan(), $this->target(), 40);
        $this->assertTrue($r['feasible']);

        $kg = $this->kg($r);
        $this->assertEqualsWithDelta(19.8, $kg['Jagung'], 0.6);
        $this->assertEqualsWithDelta(7.6, $kg['Dedak'], 0.6);
        $this->assertEqualsWithDelta(10.5, $kg['Konsentrat'], 0.6);
        $this->assertEqualsWithDelta(2.0, $kg['Kapur'], 0.4);

        // biaya ≈ Rp 305.000 (toleransi ± Rp 7.600/kg = ± Rp 304.000 / 40 kg ... diambil Rp 7.600 per kg-campuran)
        $this->assertEqualsWithDelta(305000, $r['total_biaya'], 7600);

        $this->assertEqualsWithDelta(16.5, $r['gizi']['protein'], 0.05);
        $this->assertEqualsWithDelta(3.25, $r['gizi']['kalsium'], 0.05);
        $this->assertGreaterThanOrEqual(0.6, $r['gizi']['fosfor']);
        $this->assertLessThanOrEqual(0.9, $r['gizi']['fosfor']);
        $this->assertEqualsWithDelta(0.76, $r['gizi']['fosfor'], 0.08);
        $this->assertEqualsWithDelta(2700, $r['gizi']['energi'], 15);
    }

    public function test_harga_dedak_naik_mengubah_komposisi_dan_biaya(): void
    {
        $opt = new RationOptimizer;
        $awal = $opt->optimize($this->bahan(4500), $this->target(), 40);
        $naik = $opt->optimize($this->bahan(7000), $this->target(), 40);

        $this->assertTrue($naik['feasible']);
        $this->assertGreaterThan($awal['total_biaya'], $naik['total_biaya']);
        $this->assertNotEqualsWithDelta($this->kg($awal)['Dedak'], $this->kg($naik)['Dedak'] ?? 0, 0.05);
    }

    public function test_tidak_layak_menghasilkan_pesan_rapi(): void
    {
        $bahan = $this->bahan();
        $bahan[2]['stok'] = 1; // konsentrat nyaris habis -> protein mustahil tercapai
        $r = (new RationOptimizer)->optimize($bahan, $this->target(), 40);

        $this->assertFalse($r['feasible']);
        $this->assertNotEmpty($r['pesan']);
        $this->assertStringContainsString('Protein', implode(' ', $r['pesan']));
        $this->assertStringNotContainsString('Exception', implode(' ', $r['pesan']));
    }

    public function test_stok_total_kurang_dari_jumlah_campuran(): void
    {
        $r = (new RationOptimizer)->optimize($this->bahan(), $this->target(), 500);
        $this->assertFalse($r['feasible']);
        $this->assertStringContainsString('Stok total', $r['pesan'][0]);
    }

    public function test_campuran_tebakan_gagal_protein_dan_kalsium(): void
    {
        // Tebakan 22/10/8/0 kg dihitung manual (pembanding)
        $tebakan = ['Jagung' => 22, 'Dedak' => 10, 'Konsentrat' => 8, 'Kapur' => 0];
        $total = array_sum($tebakan);
        $protein = $kalsium = 0.0;
        foreach ($this->bahan() as $b) {
            $protein += $tebakan[$b['nama']] * $b['protein'];
            $kalsium += $tebakan[$b['nama']] * $b['kalsium'];
        }
        $this->assertLessThan(16.5, $protein / $total);
        $this->assertLessThan(3.25, $kalsium / $total);
    }
}
