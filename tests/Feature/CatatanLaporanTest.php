<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatatanLaporanTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed');
    }

    public function test_halaman_catatan_memuat_tombol_dan_modal_laporan_resmi_a4(): void
    {
        $user = User::where('email', 'demo@ransumku.test')->first();
        $this->actingAs($user);

        $response = $this->get(route('catatan.index', ['tab' => 'dashboard']));
        $response->assertStatus(200);
        $response->assertSee('Unduh Laporan');
        $response->assertSee('modal-laporan-resmi');
        $response->assertSee('dokumen-laporan-resmi');
        $response->assertSee('Laporan Rekapitulasi Operasional Buku Kandang Layer');
        $response->assertSee('TOTAL / RATA-RATA');
        $response->assertSee('Operator / Petugas Kandang');
        $response->assertSee('Pimpinan / Pemilik Peternakan');
    }

    public function test_halaman_tentang_memuat_profil_dan_4_pilar_fungsional(): void
    {
        $user = User::where('email', 'demo@ransumku.test')->first();
        $this->actingAs($user);

        $response = $this->get(route('tentang'));
        $response->assertStatus(200);
        $response->assertSee('RansumKu');
        $response->assertSee('Peracik Ransum Presisi');
        $response->assertSee('Buku Kandang');
        $response->assertSee('Asisten Cerdas');
        $response->assertSee('Kontrol Stok');
        $response->assertSee('Linear Simplex');
        $response->assertSee('SNI Unggas');
        $response->assertSee('Universitas Gadjah Mada');
    }
}
