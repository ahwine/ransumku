<?php

namespace Tests\Unit;

use App\Services\RationOptimizer;
use PHPUnit\Framework\TestCase;

class RationOptimizerInclusionTest extends TestCase
{
    private function bahan(): array
    {
        return [
            ['id' => 1, 'nama' => 'Jagung', 'harga' => 6500, 'stok' => 50, 'protein' => 8.5, 'kalsium' => 0.03, 'fosfor' => 0.28, 'serat' => 2.2, 'energi' => 3300, 'max_persen' => 65.0, 'min_persen' => 0.0],
            ['id' => 2, 'nama' => 'Dedak', 'harga' => 2000, 'stok' => 50, 'protein' => 12, 'kalsium' => 0.10, 'fosfor' => 1.2, 'serat' => 11, 'energi' => 2400, 'max_persen' => 20.0, 'min_persen' => 0.0],
            ['id' => 3, 'nama' => 'Konsentrat', 'harga' => 13000, 'stok' => 50, 'protein' => 38, 'kalsium' => 5.0, 'fosfor' => 1.5, 'serat' => 6, 'energi' => 2300, 'max_persen' => 40.0, 'min_persen' => 10.0],
            ['id' => 4, 'nama' => 'Kapur', 'harga' => 2500, 'stok' => 50, 'protein' => 0, 'kalsium' => 38, 'fosfor' => 0, 'serat' => 0, 'energi' => 0, 'max_persen' => 10.0, 'min_persen' => 0.0],
        ];
    }

    private function target(): array
    {
        return [
            'protein_min' => 16.5,
            'kalsium_min' => 3.25,
            'kalsium_max' => 4.25,
            'fosfor_min' => 0.6,
            'fosfor_max' => 0.9,
            'serat_max' => null,
            'energi_min' => 2700,
        ];
    }

    public function test_optimizer_mematuhi_batas_maksimum_dedak_meski_harga_sangat_murah(): void
    {
        $opt = new RationOptimizer;
        // Dedak harganya Rp 2.000 (sangat murah), tanpa batas simplex akan mengambil dedak sebanyak-banyaknya.
        // Namun karena max_persen = 20%, pada 40 kg dedak maksimal 8 kg (20%).
        $r = $opt->optimize($this->bahan(), $this->target(), 40);

        $this->assertTrue($r['feasible']);
        $dedak = collect($r['items'])->firstWhere('nama', 'Dedak');
        $this->assertNotNull($dedak);
        $this->assertLessThanOrEqual(8.001, $dedak['kg']);
        $this->assertLessThanOrEqual(20.001, $dedak['persen']);
    }

    public function test_optimizer_mematuhi_batas_minimum_konsentrat(): void
    {
        $opt = new RationOptimizer;
        // Konsentrat min_persen = 10% (minimal 4 kg pada 40 kg campuran)
        $r = $opt->optimize($this->bahan(), $this->target(), 40);

        $this->assertTrue($r['feasible']);
        $konsentrat = collect($r['items'])->firstWhere('nama', 'Konsentrat');
        $this->assertNotNull($konsentrat);
        $this->assertGreaterThanOrEqual(3.999, $konsentrat['kg']);
        $this->assertGreaterThanOrEqual(9.999, $konsentrat['persen']);
    }

    public function test_diagnosa_gagal_bila_total_min_persen_melebihi_100(): void
    {
        $bahan = $this->bahan();
        $bahan[0]['min_persen'] = 60.0;
        $bahan[1]['min_persen'] = 50.0; // 60 + 50 = 110% > 100%

        $opt = new RationOptimizer;
        $r = $opt->optimize($bahan, $this->target(), 40);

        $this->assertFalse($r['feasible']);
        $this->assertStringContainsString('melebihi 100%', $r['pesan'][0]);
    }

    public function test_diagnosa_gagal_bila_total_max_persen_kurang_dari_100(): void
    {
        $bahan = $this->bahan();
        $bahan[0]['max_persen'] = 30.0;
        $bahan[1]['max_persen'] = 20.0;
        $bahan[2]['max_persen'] = 20.0;
        $bahan[3]['max_persen'] = 10.0; // 30+20+20+10 = 80% < 100%

        $opt = new RationOptimizer;
        $r = $opt->optimize($bahan, $this->target(), 40);

        $this->assertFalse($r['feasible']);
        $this->assertStringContainsString('kurang dari 100%', $r['pesan'][0]);
    }
}

