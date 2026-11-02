<?php

namespace Tests\Feature;

use App\Models\Farm;
use App\Models\User;
use App\Services\NutrientStandardService;
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

    public function test_halaman_acuan_memuat_pustaka_resmi_dan_status_sinkronisasi(): void
    {
        $user = User::where('email', 'demo@ransumku.test')->first();
        $this->actingAs($user);

        $response = $this->get(route('acuan.index'));
        $response->assertStatus(200);
        $response->assertSee('Acuan Gizi');
        $response->assertSee('SNI 01-3929-2006');
        $response->assertSee('BSN');
        
        // Memastikan tidak ada tombol tambah atau hapus acuan bagi peternak
        $response->assertDontSee('Buat Kustom');
        $response->assertDontSee('Hapus Acuan');
    }

    public function test_sinkronisasi_resmi_mengisi_standar_sni_dan_breeder(): void
    {
        $user = User::where('email', 'demo@ransumku.test')->first();
        $this->actingAs($user);

        $farm = $user->farm;
        $service = app(NutrientStandardService::class);
        $count = $service->syncOfficialStandards($farm);

        $this->assertGreaterThanOrEqual(4, $count);
        $this->assertDatabaseHas('nutrient_standards', [
            'farm_id' => $farm->id,
            'kode' => 'SNI_LAYER_1',
            'is_resmi' => true,
        ]);
        $this->assertDatabaseHas('nutrient_standards', [
            'farm_id' => $farm->id,
            'kode' => 'LOHMANN_PEAK',
            'is_resmi' => true,
        ]);
    }

    public function test_peternak_tidak_bisa_menambah_atau_mengedit_acuan_manual(): void
    {
        $user = User::where('email', 'demo@ransumku.test')->first();
        $this->actingAs($user);

        $std = $user->farm->standards()->first();

        $create = $this->get(route('acuan.create'));
        $create->assertRedirect(route('acuan.index'));

        $edit = $this->get(route('acuan.edit', $std));
        $edit->assertRedirect(route('acuan.index'));

        $delete = $this->delete(route('acuan.destroy', $std));
        $delete->assertRedirect(route('acuan.index'));
    }

    public function test_halaman_ransum_baru_otomatis_merekomendasikan_standar_sesuai_fase(): void
    {
        $user = User::where('email', 'demo@ransumku.test')->first();
        $this->actingAs($user);

        $response = $this->get(route('ransum.create'));
        $response->assertStatus(200);
        $response->assertSee('Auto-Match');
        $response->assertSee('Acuan Gizi Target');
    }
}
