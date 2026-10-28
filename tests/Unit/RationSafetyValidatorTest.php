<?php

namespace Tests\Unit;

use App\Services\RationSafetyValidator;
use PHPUnit\Framework\TestCase;

class RationSafetyValidatorTest extends TestCase
{
    private RationSafetyValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = new RationSafetyValidator;
    }

    public function test_ransum_normal_dinyatakan_aman(): void
    {
        $items = [
            ['nama' => 'Jagung', 'persen' => 55.0, 'kg' => 22.0, 'max_persen' => 65.0, 'min_persen' => 0.0],
            ['nama' => 'Dedak', 'persen' => 15.0, 'kg' => 6.0, 'max_persen' => 30.0, 'min_persen' => 0.0],
            ['nama' => 'Konsentrat', 'persen' => 30.0, 'kg' => 12.0, 'max_persen' => 40.0, 'min_persen' => 0.0],
        ];

        $gizi = [
            'protein' => 17.0,
            'kalsium' => 3.5,
            'fosfor' => 0.7,
            'serat' => 4.5,
            'energi' => 2750.0,
        ];

        $hasil = $this->validator->validate($items, $gizi);

        $this->assertTrue($hasil['aman']);
        $this->assertEmpty($hasil['peringatan']);
    }

    public function test_peringatan_melebihi_batas_maksimum(): void
    {
        $items = [
            ['nama' => 'Dedak', 'persen' => 38.0, 'kg' => 15.2, 'max_persen' => 30.0, 'min_persen' => 0.0, 'catatan_keamanan' => 'Maksimal 30% pada layer'],
            ['nama' => 'Jagung', 'persen' => 62.0, 'kg' => 24.8, 'max_persen' => 70.0, 'min_persen' => 0.0],
        ];

        $gizi = ['protein' => 14.0, 'kalsium' => 1.0, 'fosfor' => 0.6, 'serat' => 5.0, 'energi' => 2700.0];

        $hasil = $this->validator->validate($items, $gizi);

        $this->assertFalse($hasil['aman']);
        $dangerAlerts = array_filter($hasil['peringatan'], fn ($p) => $p['level'] === 'danger');
        $this->assertNotEmpty($dangerAlerts);
        $this->assertStringContainsString('melebihi batas aman maksimal', array_values($dangerAlerts)[0]['pesan']);
    }

    public function test_deteksi_urea_sebagai_bahan_berbahaya_unggas(): void
    {
        $items = [
            ['nama' => 'Pupuk Urea', 'persen' => 1.0, 'kg' => 0.4, 'max_persen' => 100.0, 'min_persen' => 0.0],
            ['nama' => 'Jagung', 'persen' => 99.0, 'kg' => 39.6, 'max_persen' => 100.0, 'min_persen' => 0.0],
        ];

        $gizi = ['protein' => 16.0, 'kalsium' => 2.0, 'fosfor' => 0.5, 'serat' => 2.0, 'energi' => 3000.0];

        $hasil = $this->validator->validate($items, $gizi);

        $this->assertFalse($hasil['aman']);
        $this->assertStringContainsString('urea', strtolower($hasil['peringatan'][0]['pesan']));
    }

    public function test_peringatan_serat_kasar_dan_rasio_mineral(): void
    {
        $items = [
            ['nama' => 'Jagung', 'persen' => 50.0, 'kg' => 20.0, 'max_persen' => 100.0, 'min_persen' => 0.0],
        ];

        // Serat 8.5% (> 7%), Kalsium 0.4% vs Fosfor 0.6% (rasio < 1.0)
        $gizi = ['protein' => 15.0, 'kalsium' => 0.4, 'fosfor' => 0.6, 'serat' => 8.5, 'energi' => 2600.0];

        $hasil = $this->validator->validate($items, $gizi);

        $this->assertFalse($hasil['aman']); // karena rasio Ca:P < 1 adalah danger
        $pesanGabungan = implode(' ', array_column($hasil['peringatan'], 'pesan'));
        $this->assertStringContainsString('Serat kasar', $pesanGabungan);
        $this->assertStringContainsString('Rasio Kalsium terhadap Fosfor terbalik', $pesanGabungan);
    }
}

