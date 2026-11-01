<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RansumKuFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed');
    }

    public function test_tamu_dialihkan_ke_halaman_masuk(): void
    {
        $response = $this->get('/');
        $response->assertRedirect(route('masuk'));
    }

    public function test_login_demo_bisa_membuka_beranda(): void
    {
        $response = $this->post(route('masuk'), [
            'email' => 'demo@ransumku.test',
            'password' => 'password',
        ]);

        $response->assertRedirect(route('beranda'));
        $this->assertAuthenticated();

        $beranda = $this->get(route('beranda'));
        $beranda->assertStatus(200);
        $beranda->assertSee('Kandang Maju Jaya');
        $beranda->assertSee('Peringatan dini');
    }

    public function test_halaman_ransum_dan_hitung(): void
    {
        $user = User::where('email', 'demo@ransumku.test')->first();
        $this->actingAs($user);

        $create = $this->get(route('ransum.create'));
        $create->assertStatus(200);
        $create->assertSee('Jagung');
        $create->assertSee('Dedak');

        $store = $this->post(route('ransum.store'), [
            'mode' => 'kg',
            'total_kg' => 40,
            'standard_id' => $user->farm->standards()->first()->id,
            'bahan' => [
                $user->farm->farmIngredients[0]->id => ['harga' => 6500, 'stok' => 50, 'aktif' => 1],
                $user->farm->farmIngredients[1]->id => ['harga' => 4500, 'stok' => 30, 'aktif' => 1],
                $user->farm->farmIngredients[2]->id => ['harga' => 13000, 'stok' => 20, 'aktif' => 1],
                $user->farm->farmIngredients[3]->id => ['harga' => 2500, 'stok' => 10, 'aktif' => 1],
            ],
        ]);

        $store->assertRedirect();
        $this->assertDatabaseHas('rations', [
            'farm_id' => $user->farm->id,
            'status' => 'layak',
        ]);
    }

    public function test_buku_kandang_dan_asisten_berjalan(): void
    {
        $user = User::where('email', 'demo@ransumku.test')->first();
        $this->actingAs($user);

        $catatan = $this->get(route('catatan.index'));
        $catatan->assertStatus(200);
        $catatan->assertSee('Catat Data Hari Ini');

        $asisten = $this->get(route('asisten.index'));
        $asisten->assertStatus(200);
        $asisten->assertSee('Peringatan Dini');

        // Pastikan membuka tab peringatan dini otomatis menandai peringatan belum dibaca menjadi dibaca (menghilangkan badge titik merah)
        $alert = $user->farm->alerts()->create([
            'jenis' => 'uji_deteksi',
            'level' => 'perhatian',
            'pesan' => 'Uji deteksi otomatis',
            'tanggal_data' => now()->toDateString(),
            'dibaca' => false,
        ]);
        $this->get(route('asisten.index', ['tab' => 'peringatan']))->assertStatus(200);
        $this->assertTrue($alert->fresh()->dibaca);

        // Test tanya asisten AI (fallback mode tanpa API key)
        $chat = $this->post(route('asisten.chat'), [
            'pesan' => 'Apa bahan pengganti jagung?',
        ]);
        $chat->assertRedirect();
        $this->assertDatabaseHas('chat_messages', [
            'farm_id' => $user->farm->id,
            'role' => 'user',
        ]);
    }

    public function test_simpan_dan_perbarui_catatan_harian_tanpa_duplicate_key_error(): void
    {
        $user = User::where('email', 'demo@ransumku.test')->first();
        $this->actingAs($user);

        // 1. Simpan catatan hari ini
        $response1 = $this->post(route('catatan.store'), [
            'tanggal' => today()->toDateString(),
            'pakan_kg' => 22.0,
            'telur_butir' => 170,
            'telur_kg' => 10.5,
            'ayam_mati' => 0,
            'biaya_lain' => 0,
            'catatan' => 'Pencatatan pertama',
        ]);
        $response1->assertRedirect(route('catatan.index', ['tab' => 'form']));
        $this->assertDatabaseHas('daily_logs', [
            'farm_id' => $user->farm->id,
            'telur_butir' => 170,
        ]);

        // 2. Perbarui catatan tanggal yang sama persis (harus update, bukan duplicate error)
        $response2 = $this->post(route('catatan.store'), [
            'tanggal' => today()->toDateString(),
            'pakan_kg' => 23.0,
            'telur_butir' => 180,
            'telur_kg' => 11.2,
            'ayam_mati' => 0,
            'biaya_lain' => 0,
            'catatan' => 'Pencatatan diperbarui',
        ]);
        $response2->assertRedirect(route('catatan.index', ['tab' => 'form']));
        $this->assertDatabaseHas('daily_logs', [
            'farm_id' => $user->farm->id,
            'pakan_kg' => 23.0,
            'telur_butir' => 180,
            'catatan' => 'Pencatatan diperbarui',
        ]);
    }
}
