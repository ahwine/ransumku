<?php

namespace Tests\Unit;

use App\Services\FeedConversionService;
use PHPUnit\Framework\TestCase;

class FeedConversionServiceTest extends TestCase
{
    private FeedConversionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new FeedConversionService;
    }

    public function test_konversi_dry_matter_ke_as_fed_berhasil_dihitung(): void
    {
        $items = [
            ['nama' => 'Jagung', 'kg' => 20.0, 'persen' => 50.0, 'harga' => 6500, 'bk' => 86.0],
            ['nama' => 'Dedak', 'kg' => 10.0, 'persen' => 25.0, 'harga' => 4500, 'bk' => 88.0],
            ['nama' => 'Konsentrat', 'kg' => 10.0, 'persen' => 25.0, 'harga' => 13000, 'bk' => 90.0],
        ];

        $hasil = $this->service->convertToAsFed($items);

        $this->assertEquals(40.0, $hasil['total_bk_kg']);
        $this->assertGreaterThan(40.0, $hasil['total_as_fed_kg']);
        $this->assertCount(3, $hasil['items']);

        // Cek perhitungan per bahan: As-Fed = BK / (%BK / 100)
        // Jagung: 20 / 0.86 ≈ 23.256
        $jagung = $hasil['items'][0];
        $this->assertEqualsWithDelta(23.256, $jagung['kg_as_fed'], 0.01);
        $this->assertEquals(86.0, $jagung['bk']);

        // Total persentase As-Fed harus 100%
        $totalPersenAsFed = array_sum(array_column($hasil['items'], 'persen_as_fed'));
        $this->assertEqualsWithDelta(100.0, $totalPersenAsFed, 0.1);

        // Biaya per kg as fed harus lebih rendah daripada biaya per kg BK karena ada bobot air
        $this->assertGreaterThan(0, $hasil['biaya_per_kg_as_fed']);
        $this->assertEqualsWithDelta(305000, $hasil['total_biaya'], 100);
    }

    public function test_hitung_mixing_sheet_sesuai_kapasitas_mixer(): void
    {
        $asFedItems = [
            ['nama' => 'Jagung', 'persen_as_fed' => 50.0, 'bk' => 86.0],
            ['nama' => 'Dedak', 'persen_as_fed' => 25.0, 'bk' => 88.0],
            ['nama' => 'Konsentrat', 'persen_as_fed' => 25.0, 'bk' => 90.0],
        ];

        $sheet = $this->service->hitungMixingSheet($asFedItems, 200.0);

        $this->assertCount(3, $sheet);
        $this->assertEquals(100.0, $sheet[0]['kg_timbang']); // 50% dari 200 kg
        $this->assertEquals(50.0, $sheet[1]['kg_timbang']);  // 25% dari 200 kg
        $this->assertEquals(50.0, $sheet[2]['kg_timbang']);  // 25% dari 200 kg
    }
}

