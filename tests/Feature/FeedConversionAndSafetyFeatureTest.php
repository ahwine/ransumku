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
        $show->assertSee('Total Timbangan Segar');

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

    public function test_update_stok_gudang_berhasil(): void
    {
        $user = User::where('email', 'demo@ransumku.test')->first();
        $this->actingAs($user);

        $farm = $user->farm;
        $fi = $farm->farmIngredients()->first();
        $this->assertNotNull($fi);

        $response = $this->post(route('bahan.stok.update'), [
            'farm_ingredient_id' => $fi->id,
            'stok_kg' => 150.5,
            'harga' => 6200,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('ok');

        $fi->refresh();
        $this->assertEquals(150.5, (float) $fi->stok_kg);
        $this->assertEquals(6200, $fi->harga);
    }

    public function test_terapkan_dan_batalkan_pemotongan_stok_ransum(): void
    {
        $user = User::where('email', 'demo@ransumku.test')->first();
        $this->actingAs($user);

        $farm = $user->farm;
        $fis = $farm->farmIngredients()->with('ingredient')->where('aktif', true)->get();

        // Siapkan stok awal yang cukup
        foreach ($fis as $fi) {
            $fi->update(['stok_kg' => 200.0]);
        }

        $bahanPayload = [];
        foreach ($fis as $fi) {
            $bahanPayload[$fi->id] = [
                'harga' => $fi->harga,
                'stok' => 200,
                'min_persen' => $fi->min_persen ?? 0,
                'max_persen' => $fi->max_persen ?? 100,
                'aktif' => 1,
            ];
        }

        $this->post(route('ransum.store'), [
            'mode' => 'kg',
            'total_kg' => 30,
            'standard_id' => $farm->standards()->first()->id,
            'bahan' => $bahanPayload,
        ]);

        $ration = $farm->rations()->latest('id')->first();
        $this->assertNotNull($ration);
        $this->assertEquals('layak', $ration->status);

        // 1. Terapkan pemotongan stok
        $responseTerapkan = $this->post(route('ransum.terapkan', $ration));
        $responseTerapkan->assertRedirect();
        $responseTerapkan->assertSessionHas('ok');

        $ration->refresh();
        $this->assertTrue(! empty($ration->hasil['stok_dipotong']));

        // Cek stok berkurang
        foreach ($ration->items as $it) {
            $fi = $farm->farmIngredients()->where('ingredient_id', $it->ingredient_id)->first();
            if ($fi && ($it->kg_as_fed ?? $it->kg) > 0) {
                $this->assertLessThan(200.0, (float) $fi->stok_kg);
            }
        }

        // 2. Cegah pemotongan ganda
        $responseGanda = $this->post(route('ransum.terapkan', $ration));
        $responseGanda->assertRedirect();
        $responseGanda->assertSessionHas('info');

        // 3. Batalkan pemotongan (rollback)
        $responseBatal = $this->post(route('ransum.batal-terapkan', $ration));
        $responseBatal->assertRedirect();
        $responseBatal->assertSessionHas('ok');

        $ration->refresh();
        $this->assertEmpty($ration->hasil['stok_dipotong'] ?? null);

        // Stok kembali ke nilai semula (200 kg)
        foreach ($ration->items as $it) {
            $fi = $farm->farmIngredients()->where('ingredient_id', $it->ingredient_id)->first();
            if ($fi) {
                $this->assertEquals(200.0, (float) $fi->stok_kg);
            }
        }
    }
}

