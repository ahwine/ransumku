<?php

namespace Tests\Feature;

use App\Models\Farm;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FarmManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_halaman_kelola_kandang_dapat_diakses(): void
    {
        $user = User::factory()->create();
        $farm = Farm::create([
            'user_id' => $user->id,
            'nama' => 'Kandang A',
            'lokasi_kota' => 'Surabaya',
            'jumlah_ayam' => 250,
            'fase' => 'produksi',
        ]);

        $res = $this->actingAs($user)->get(route('kandang.index'));

        $res->assertStatus(200);
        $res->assertSee('Kelola Kandang');
        $res->assertSee('Kandang A');
        $res->assertSee('250 Ekor');
        $res->assertSee($user->name);
    }

    public function test_tambah_kandang_baru_dan_otomatis_aktif(): void
    {
        $user = User::factory()->create();
        $farmA = Farm::create([
            'user_id' => $user->id,
            'nama' => 'Kandang Lama',
            'lokasi_kota' => 'Surabaya',
            'jumlah_ayam' => 200,
            'fase' => 'produksi',
        ]);

        $res = $this->actingAs($user)->post(route('kandang.store'), [
            'nama' => 'Kandang B - Pullet',
            'jumlah_ayam' => 500,
            'fase' => 'grower',
            'lokasi_kota' => 'Malang',
        ]);

        $res->assertRedirect(route('beranda'));
        $res->assertSessionHas('ok');

        $this->assertDatabaseHas('farms', [
            'user_id' => $user->id,
            'nama' => 'Kandang B - Pullet',
            'jumlah_ayam' => 500,
            'fase' => 'grower',
            'lokasi_kota' => 'Malang',
        ]);

        $newFarm = Farm::where('nama', 'Kandang B - Pullet')->first();
        $this->assertEquals($newFarm->id, session('active_farm_id'));
    }

    public function test_ganti_kandang_aktif(): void
    {
        $user = User::factory()->create();
        $farm1 = Farm::create([
            'user_id' => $user->id,
            'nama' => 'Kandang 1',
            'lokasi_kota' => 'Surabaya',
            'jumlah_ayam' => 200,
            'fase' => 'produksi',
        ]);
        $farm2 = Farm::create([
            'user_id' => $user->id,
            'nama' => 'Kandang 2',
            'lokasi_kota' => 'Blitar',
            'jumlah_ayam' => 400,
            'fase' => 'produksi',
        ]);

        $res = $this->actingAs($user)->post(route('kandang.switch'), [
            'farm_id' => $farm2->id,
        ]);

        $res->assertSessionHas('ok');
        $this->assertEquals($farm2->id, session('active_farm_id'));
    }

    public function test_perbarui_data_kandang(): void
    {
        $user = User::factory()->create();
        $farm = Farm::create([
            'user_id' => $user->id,
            'nama' => 'Kandang Awal',
            'lokasi_kota' => 'Surabaya',
            'jumlah_ayam' => 200,
            'fase' => 'produksi',
        ]);

        $res = $this->actingAs($user)->put(route('kandang.update', $farm), [
            'nama' => 'Kandang Maju Sukses',
            'jumlah_ayam' => 350,
            'fase' => 'grower',
            'lokasi_kota' => 'Boyolali',
        ]);

        $res->assertSessionHas('ok');
        $farm->refresh();
        $this->assertEquals('Kandang Maju Sukses', $farm->nama);
        $this->assertEquals(350, $farm->jumlah_ayam);
        $this->assertEquals('grower', $farm->fase);
        $this->assertEquals('Boyolali', $farm->lokasi_kota);
    }

    public function test_hapus_kandang_dan_proteksi_kandang_terakhir(): void
    {
        $user = User::factory()->create();
        $farm1 = Farm::create([
            'user_id' => $user->id,
            'nama' => 'Kandang Satu',
            'lokasi_kota' => 'Surabaya',
            'jumlah_ayam' => 200,
            'fase' => 'produksi',
        ]);

        // Coba hapus saat hanya ada 1 kandang -> harus ditolak
        $res = $this->actingAs($user)->delete(route('kandang.destroy', $farm1));
        $res->assertSessionHasErrors('hapus');
        $this->assertDatabaseHas('farms', ['id' => $farm1->id]);

        // Buat kandang ke-2
        $farm2 = Farm::create([
            'user_id' => $user->id,
            'nama' => 'Kandang Dua',
            'lokasi_kota' => 'Malang',
            'jumlah_ayam' => 300,
            'fase' => 'grower',
        ]);

        // Hapus kandang ke-2 -> harus berhasil
        $res2 = $this->actingAs($user)->delete(route('kandang.destroy', $farm2));
        $res2->assertRedirect(route('kandang.index'));
        $this->assertDatabaseMissing('farms', ['id' => $farm2->id]);
    }

    public function test_perbarui_profil_peternak(): void
    {
        $user = User::factory()->create([
            'name' => 'Pak Budi',
            'email' => 'budi.lama@ransumku.test',
        ]);
        Farm::create([
            'user_id' => $user->id,
            'nama' => 'Kandang Budi',
            'lokasi_kota' => 'Surabaya',
            'jumlah_ayam' => 200,
            'fase' => 'produksi',
        ]);

        $res = $this->actingAs($user)->post(route('profil.update'), [
            'name' => 'Budi Santoso',
            'email' => 'budi.baru@ransumku.test',
        ]);

        $res->assertSessionHas('ok');
        $user->refresh();
        $this->assertEquals('Budi Santoso', $user->name);
        $this->assertEquals('budi.baru@ransumku.test', $user->email);
    }
}
