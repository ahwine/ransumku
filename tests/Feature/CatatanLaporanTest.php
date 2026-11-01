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

    public function test_riwayat_catatan_dapat_difilter_berdasarkan_periode_dan_tanggal(): void
    {
        $user = User::where('email', 'demo@ransumku.test')->first();
        $this->actingAs($user);

        $response = $this->get(route('catatan.index', ['tab' => 'form', 'periode' => 'bulan_ini']));
        $response->assertStatus(200);
        $response->assertSee('Bulan Ini');
        $response->assertSee('Catatan Harian Terbaru');

        $responseCari = $this->get(route('catatan.index', ['tab' => 'form', 'cari_tanggal' => today()->toDateString()]));
        $responseCari->assertStatus(200);
        $responseCari->assertSee('Catatan Harian Terbaru');
    }

    public function test_halaman_catatan_memuat_fitur_kalkulator_rak_telur(): void
    {
        $user = User::where('email', 'demo@ransumku.test')->first();
        $this->actingAs($user);

        $response = $this->get(route('catatan.index', ['tab' => 'form']));
        $response->assertStatus(200);
        $response->assertSee('Hitung per Rak (30)');
        $response->assertSee('Kalkulator Rak Telur');
        $response->assertSee('1 Rak = 30 butir');
        $response->assertSee('calc_rak_input');
        $response->assertSee('calc_sisa_input');
    }
}

