<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FeedConversionAndSafetyFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed');
    }

    public function test_halaman_create_menampilkan_info_bk_dan_batas(): void
    {
        $user = User::where('email', 'demo@ransumku.test')->first();
        $this->actingAs($user);

        $response = $this->get(route('ransum.create'));
        $response->assertStatus(200);
        $response->assertSee('BK 86%'); // Jagung
        $response->assertSee('Batas Ambang Penggunaan');
    }

    public function test_hitung_ransum_menyimpan_as_fed_dan_ditampilkan_di_show(): void
    {
        $user = User::where('email', 'demo@ransumku.test')->first();
        $this->actingAs($user);

        $farm = $user->farm;
        $fis = $farm->farmIngredients()->with('ingredient')->where('aktif', true)->get();

        $bahanPayload = [];
        foreach ($fis as $fi) {
            $bahanPayload[$fi->id] = [
                'harga' => $fi->harga,
                'stok' => 50,
                'min_persen' => $fi->min_persen ?? 0,
                'max_persen' => $fi->max_persen ?? 100,
                'aktif' => 1,
            ];
        }

        $response = $this->post(route('ransum.store'), [
            'mode' => 'kg',
            'total_kg' => 40,
            'standard_id' => $farm->standards()->first()->id,
            'bahan' => $bahanPayload,
        ]);

        $response->assertRedirect();
        $ration = $farm->rations()->latest('id')->first();
        $this->assertNotNull($ration);
        $this->assertEquals('layak', $ration->status);

        // Akses halaman show
        $show = $this->get(route('ransum.show', $ration));
        $show->assertStatus(200);
        $show->assertSee('Timbangan Segar (As-Fed)');
        $show->assertSee('Formulasi Kering (BK)');
        $show->assertSee('Cetak Lembar Aduk Wadah Mixer');

        // Pastikan ration item tersimpan dengan data As-Fed dan BK
        $item = $ration->items()->first();
        $this->assertNotNull($item->bk);
        $this->assertNotNull($item->kg_as_fed);
        $this->assertNotNull($item->persen_as_fed);
    }

    public function test_tambah_bahan_baru_dengan_bk_dan_batas_aman(): void
    {
        $user = User::where('email', 'demo@ransumku.test')->first();
        $this->actingAs($user);

        $response = $this->post(route('bahan.store'), [
            'nama' => 'Bungkil Sawit',
            'protein' => 16.0,
            'energi' => 2100,
            'kalsium' => 0.35,
            'fosfor' => 0.65,
            'serat' => 16.0,
            'harga_default' => 3000,
            'bk' => 90.0,
            'min_persen' => 0.0,
            'max_persen' => 20.0,
            'catatan_keamanan' => 'Maksimal 15-20% karena serat tinggi dan kecernaan rendah.',
            'sumber' => 'Uji Lab Pakan',
            'catatan' => 'Pakan alternatif',
        ]);

        $response->assertRedirect(route('bahan.index'));
        $this->assertDatabaseHas('ingredients', [
            'nama' => 'Bungkil Sawit',
            'bk' => 90.0,
            'max_persen' => 20.0,
            'catatan_keamanan' => 'Maksimal 15-20% karena serat tinggi dan kecernaan rendah.',
        ]);
    }
}

