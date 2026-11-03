<?php

namespace Tests\Feature;

use App\Models\Farm;
use App\Models\User;
use App\Services\FarmBootstrap;
use App\Services\NutrientStandardService;
use App\Services\RationCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NutrientStandardOfficialSyncTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed');
    }

    public function test_jumlah_katalog_dan_penghapusan_entri_lama_sesuai_spesifikasi(): void
    {
        $catalog = NutrientStandardService::officialStandards();
        $expectedCodes = [
            'LOHMANN_CLASSIC_1',
            'LOHMANN_CLASSIC_2',
            'LOHMANN_CLASSIC_3',
            'SNI_8290_3_M1',
            'SNI_8290_3_M2',
            'SNI_8290_5_M1',
            'SNI_8290_2_M1',
            'SNI_8290_1_PRE_STARTER',
            'SNI_7783_3_2013',
            'ISA_BROWN',
            'NRC_1994',
        ];

        $this->assertCount(count($expectedCodes), $catalog);
        $this->assertSame($expectedCodes, NutrientStandardService::validCatalogCodes());

        $user = User::where('email', 'demo@ransumku.test')->firstOrFail();
        $farm = $user->farm;

        $farm->standards()->create([
            'kode' => 'HYLINE_BROWN',
            'nama' => 'Hy-Line Brown Management Guide',
            'kategori_fase' => 'produksi',
            'is_resmi' => true,
            'verified' => false,
            'dataset_version' => '2026.2',
        ]);

        $service = app(NutrientStandardService::class);
        $this->assertTrue($service->needsSync($farm));

        $syncedCount = $service->syncOfficialStandards($farm);
        $this->assertSame(count($expectedCodes), $syncedCount);
        $this->assertFalse($service->needsSync($farm->fresh()));

        foreach ([
            'SNI_PRE_LAYER',
            'SNI_LAYER_2',
            'SNI_BURAS_LAYER',
            'HYLINE_BROWN',
            'NOVOGEN_BROWN',
            'HISEX_BROWN',
            'ROSTAGNO_TROPIS',
        ] as $deletedCode) {
            $this->assertDatabaseMissing('nutrient_standards', [
                'farm_id' => $farm->id,
                'kode' => $deletedCode,
            ]);
        }

        $secondFarm = Farm::create([
            'user_id' => $user->id,
            'nama' => 'Kandang Cabang Dua',
            'jumlah_ayam' => 150,
            'fase' => 'produksi',
        ]);
        app(FarmBootstrap::class)->setupFarm($secondFarm, false);

        $this->assertSame(count($expectedCodes), $secondFarm->standards()->count());
        $this->assertDatabaseMissing('nutrient_standards', ['farm_id' => null]);
    }

    public function test_setiap_standar_memiliki_source_edition_year_dan_koreksi_spesifik(): void
    {
        $catalog = collect(NutrientStandardService::officialStandards())->keyBy('kode');

        foreach ($catalog as $kode => $std) {
            $this->assertArrayHasKey('source', $std);
            $this->assertArrayHasKey('edition_year', $std);
            $this->assertArrayHasKey('verified', $std);
            $this->assertNotEmpty(trim((string) $std['source']), "Field source kosong pada {$kode}");
            $this->assertNotEmpty(trim((string) $std['edition_year']), "Field edition_year kosong pada {$kode}");

            if (str_starts_with($kode, 'SNI_')) {
                $this->assertStringContainsString(
                    'sumber sekunder, cek ke dokumen BSN',
                    $std['source'],
                    "Catatan sumber sekunder wajib ada pada {$kode}"
                );
                $this->assertFalse($std['verified'], "{$kode} berasal dari sumber sekunder sehingga harus verified=false");
            }
        }

        // Item 1: SNI_8290_3_M1 & M2 verified=false dengan status sumber_sekunder
        $this->assertFalse($catalog['SNI_8290_3_M1']['verified']);
        $this->assertFalse($catalog['SNI_8290_3_M2']['verified']);
        $this->assertSame('sumber_sekunder', $catalog['SNI_8290_3_M1']['parameter_tambahan']['status_verifikasi']);
        $this->assertSame('sumber_sekunder', $catalog['SNI_8290_3_M2']['parameter_tambahan']['status_verifikasi']);

        // Item 2: Lohmann edition_year = 'belum diketahui'
        foreach (['LOHMANN_CLASSIC_1', 'LOHMANN_CLASSIC_2', 'LOHMANN_CLASSIC_3'] as $lohmannCode) {
            $this->assertSame('belum diketahui', $catalog[$lohmannCode]['edition_year']);
            $this->assertTrue($catalog[$lohmannCode]['verified']);
        }

        // Item 3: NRC_1994 angka dikosongkan (null) karena tabel primer belum dibuka langsung
        $nrc = $catalog['NRC_1994'];
        $this->assertFalse($nrc['verified']);
        $this->assertNull($nrc['protein_min']);
        $this->assertNull($nrc['energi_min']);
        $this->assertNull($nrc['kalsium_min']);
        $this->assertNull($nrc['kalsium_max']);
        $this->assertNull($nrc['fosfor_min']);
        $this->assertNull($nrc['fosfor_max']);
        $this->assertNull($nrc['serat_max']);
        $this->assertArrayNotHasKey('fosfor_tersedia_min', $nrc['parameter_tambahan']);
        $this->assertArrayNotHasKey('lisin_min', $nrc['parameter_tambahan']);
        $this->assertArrayNotHasKey('metionin_min', $nrc['parameter_tambahan']);

        // Item 5: Judul SNI_8290_1_PRE_STARTER
        $this->assertSame('SNI 8290-1 (angka edisi 2016)', $catalog['SNI_8290_1_PRE_STARTER']['nama']);
    }

    public function test_auto_match_tidak_pernah_memilih_standar_verified_false(): void
    {
        $user = User::where('email', 'demo@ransumku.test')->firstOrFail();
        $farm = $user->farm;
        $service = app(NutrientStandardService::class);

        // Fase pembesaran tanpa standar verified=true tidak boleh memilih verified=false maupun pakan layer
        foreach ([
            ['fase' => 'starter', 'umur_awal_minggu' => 2, 'strain_ayam' => ''],
            ['fase' => 'starter', 'umur_awal_minggu' => 7, 'strain_ayam' => ''],
            ['fase' => 'grower', 'umur_awal_minggu' => 14, 'strain_ayam' => ''],
        ] as $rearingScenario) {
            $farm->update([
                'fase' => $rearingScenario['fase'],
                'tgl_chick_in' => now(),
                'umur_awal_minggu' => $rearingScenario['umur_awal_minggu'],
                'strain_ayam' => $rearingScenario['strain_ayam'],
            ]);

            $this->assertNull($service->getRecommendedStandard($farm->fresh()));
        }

        // Fase produksi memilih standar Lohmann yang verified=true sesuai rentang minggu
        $productionScenarios = [
            ['fase' => 'produksi', 'umur_awal_minggu' => 28, 'strain_ayam' => 'Isa Brown', 'expected' => 'LOHMANN_CLASSIC_1'],
            ['fase' => 'produksi', 'umur_awal_minggu' => 30, 'strain_ayam' => 'Ayam Kampung', 'expected' => 'LOHMANN_CLASSIC_1'],
            ['fase' => 'produksi', 'umur_awal_minggu' => 58, 'strain_ayam' => 'Lohmann Brown', 'expected' => 'LOHMANN_CLASSIC_2'],
            ['fase' => 'produksi', 'umur_awal_minggu' => 75, 'strain_ayam' => 'Lohmann Brown', 'expected' => 'LOHMANN_CLASSIC_3'],
        ];

        foreach ($productionScenarios as $scenario) {
            $farm->update([
                'fase' => $scenario['fase'],
                'tgl_chick_in' => now(),
                'umur_awal_minggu' => $scenario['umur_awal_minggu'],
                'strain_ayam' => $scenario['strain_ayam'],
            ]);

            $recommended = $service->getRecommendedStandard($farm->fresh());
            $this->assertNotNull($recommended);
            $this->assertTrue($recommended->verified);
            $this->assertSame($scenario['expected'], $recommended->kode);
        }

        // Jika dokumen SNI 8290-3 telah diverifikasi manual, Auto-Match fase grower memilihnya
        $growerStd = $farm->standards()->where('kode', 'SNI_8290_3_M1')->firstOrFail();
        $growerStd->update(['verified' => true]);
        $farm->update([
            'fase' => 'grower',
            'tgl_chick_in' => now(),
            'umur_awal_minggu' => 14,
            'strain_ayam' => '',
        ]);
        $matchedGrower = $service->getRecommendedStandard($farm->fresh());
        $this->assertNotNull($matchedGrower);
        $this->assertSame('SNI_8290_3_M1', $matchedGrower->kode);

        $farm->standards()->update(['verified' => false]);
        $this->assertNull($service->getRecommendedStandard($farm->fresh()));
    }

    public function test_halaman_acuan_menampilkan_badge_sumber_sekunder_dan_mengizinkan_edit_manual(): void
    {
        $user = User::where('email', 'demo@ransumku.test')->firstOrFail();
        $this->actingAs($user);

        $response = $this->get(route('acuan.index'));
        $response->assertStatus(200);
        $response->assertSee('Belum terverifikasi');
        $response->assertSee('Sumber sekunder');
        $response->assertSee('SNI 8290-1 (angka edisi 2016)');
        $response->assertSee('belum diketahui');
        $response->assertDontSee('resmi terverifikasi', false);

        $verifiedStd = $user->farm->standards()->where('kode', 'LOHMANN_CLASSIC_1')->firstOrFail();
        $sniGrowerStd = $user->farm->standards()->where('kode', 'SNI_8290_3_M1')->firstOrFail();
        $this->assertFalse($sniGrowerStd->verified);
        $this->assertTrue($sniGrowerStd->isSumberSekunder());

        $this->get(route('acuan.edit', $verifiedStd))
            ->assertRedirect(route('acuan.index'));

        $this->get(route('acuan.edit', $sniGrowerStd))
            ->assertStatus(200)
            ->assertSee('Lengkapi / Ubah Acuan Gizi');
    }

    public function test_layar_hasil_ransum_menampilkan_peringatan_parameter_null(): void
    {
        $user = User::where('email', 'demo@ransumku.test')->firstOrFail();
        $this->actingAs($user);

        $farm = $user->farm;
        $lohmann = $farm->standards()->where('kode', 'LOHMANN_CLASSIC_1')->firstOrFail();
        $ration = app(RationCalculator::class)->hitung($farm, 40, null, $lohmann);

        $show = $this->get(route('ransum.show', $ration));
        $show->assertStatus(200);
        $show->assertSee('Parameter Energi metabolis tidak dibatasi oleh standar ini.');
        $show->assertSee('Parameter Serat kasar tidak dibatasi oleh standar ini.');
    }

    public function test_sinkronisasi_menghapus_standar_lama_tanpa_merusak_ransum_tersimpan(): void
    {
        $user = User::where('email', 'demo@ransumku.test')->firstOrFail();
        $this->actingAs($user);

        $farm = $user->farm;
        $obsoleteStd = $farm->standards()->create([
            'kode' => 'HYLINE_BROWN',
            'nama' => 'Hy-Line Brown Management Guide',
            'kategori_fase' => 'produksi',
            'edition_year' => 'belum diketahui',
            'source' => 'Contoh standar lama sebelum audit',
            'sumber' => 'Contoh standar lama sebelum audit',
            'protein_min' => 16.5,
            'kalsium_min' => 3.5,
            'kalsium_max' => 4.2,
            'fosfor_min' => 0.5,
            'energi_min' => null,
            'is_resmi' => true,
            'verified' => false,
            'dataset_version' => '2026.2',
        ]);

        $ration = app(RationCalculator::class)->hitung($farm, 40, null, $obsoleteStd);
        $this->assertSame($obsoleteStd->id, $ration->standard_id);

        // Membuka GET /acuan tidak boleh diam-diam menghapus standar tanpa persetujuan (tampil banner peringatan)
        $this->get(route('acuan.index'))
            ->assertStatus(200)
            ->assertSee('Data kandang Anda tidak diubah otomatis sebelum Anda menyetujui sinkronisasi');
        $this->assertDatabaseHas('nutrient_standards', ['id' => $obsoleteStd->id]);

        // Setelah pengguna menyetujui POST /acuan/sync, standar usang dihapus namun ransum tersimpan tetap utuh
        $this->post(route('acuan.sync'))
            ->assertRedirect(route('acuan.index'));

        $this->assertDatabaseMissing('nutrient_standards', ['id' => $obsoleteStd->id]);

        $ration->refresh();
        $this->assertNull($ration->standard_id);
        $this->assertStringContainsString('Hy-Line Brown Management Guide', $ration->namaStandar());

        $this->get(route('ransum.index'))
            ->assertStatus(200)
            ->assertSee('Hy-Line Brown Management Guide');

        $this->get(route('ransum.show', $ration))
            ->assertStatus(200)
            ->assertSee('Hy-Line Brown Management Guide')
            ->assertSee('Parameter Energi metabolis tidak dibatasi oleh standar ini.');

        $this->post(route('ransum.jelaskan', $ration))
            ->assertRedirect();

        $this->post(route('ransum.hitung-ulang', $ration))
            ->assertRedirect()
            ->assertSessionHas('ok');
    }
}
