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

    public function test_menu_laporan_sistem_terpisah_dan_memuat_seluruh_modul_serta_modal_a4(): void
    {
        $user = User::where('email', 'demo@ransumku.test')->first();
        $this->actingAs($user);

        $response = $this->get(route('laporan.index'));
        $response->assertStatus(200);
        $response->assertSee('Laporan Sistem Terpadu');
        $response->assertSee('laporan-screen-only');
        $response->assertSee('1. Ringkasan Eksekutif Produksi & Keuangan');
        $response->assertSee('2. Grafik Tren Operasional & Efisiensi');
        $response->assertSee('3. Laporan Formulasi Ransum & Kepatuhan Gizi');
        $response->assertSee('4. Laporan Gudang Stok Bahan & Audit Belanja Pakan');
        $response->assertSee('5. Laporan Mikroklimat Kandang (THI) & Smart Controller IoT');
        $response->assertSee('6. Laporan Deteksi Dini Anomali Statistik');
        $response->assertSee('7. Tabel Rekapitulasi Harian Buku Kandang');
        $response->assertSee('Unduh PDF Resmi');
        $response->assertSee('Unduh Excel (.xlsx)');
        $response->assertSee('Unduh CSV');
        $response->assertSee('Pratinjau Cetak A4');
        $response->assertSee('modal-laporan-resmi');
        $response->assertSee('dokumen-laporan-resmi');
        $response->assertSee('Laporan Rekapitulasi Menyeluruh Sistem Kandang Layer');
        $response->assertSee('Bagian I. Ringkasan Eksekutif Produksi & Neraca Keuangan Operasional');
        $response->assertSee('Bagian V. Tabel Rekapitulasi Harian Buku Kandang');
        $response->assertSee('TOTAL / RATA-RATA');
        $response->assertSee('Operator / Petugas Kandang');
        $response->assertSee('Pimpinan / Pemilik Peternakan');

        // Filter periode 30 hari & seluruh periode berjalan lancar
        $this->get(route('laporan.index', ['rentang' => 'bulan']))
            ->assertStatus(200)
            ->assertSee('30 Hari Terakhir');

        $this->get(route('laporan.index', ['rentang' => 'semua']))
            ->assertStatus(200)
            ->assertSee('Seluruh Periode');
    }

    public function test_unduh_pdf_excel_dan_csv_menghasilkan_berkas_rapi_dan_valid(): void
    {
        $user = User::where('email', 'demo@ransumku.test')->first();
        $this->actingAs($user);

        // 1. Unduh PDF A4 Resmi (server-generated PDF 1.4)
        $pdfRes = $this->get(route('laporan.pdf', ['rentang' => 'bulan']));
        $pdfRes->assertStatus(200);
        $pdfRes->assertHeader('Content-Type', 'application/pdf');
        $pdfContent = $pdfRes->getContent();
        $this->assertNotFalse($pdfContent);
        $this->assertStringStartsWith('%PDF-1.4', $pdfContent);
        $this->assertStringContainsString('%%EOF', $pdfContent);
        $this->assertGreaterThan(1500, strlen($pdfContent));

        // 2. Unduh Excel (.xlsx) 5-Worksheet ECMA-376 Valid XML
        $xlsxRes = $this->get(route('laporan.excel', ['rentang' => 'semua']));
        $xlsxRes->assertStatus(200);
        $xlsxRes->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $xlsxBinary = $xlsxRes->getContent();
        $this->assertNotFalse($xlsxBinary);
        $this->assertStringStartsWith('PK', $xlsxBinary);

        $tmpXlsx = tempnam(sys_get_temp_dir(), 'rk_xlsx_');
        file_put_contents($tmpXlsx, $xlsxBinary);

        $zip = new \ZipArchive;
        $this->assertSame(true, $zip->open($tmpXlsx));

        $expectedEntries = [
            '[Content_Types].xml',
            'xl/workbook.xml',
            'xl/styles.xml',
            'xl/worksheets/sheet1.xml',
            'xl/worksheets/sheet2.xml',
            'xl/worksheets/sheet3.xml',
            'xl/worksheets/sheet4.xml',
            'xl/worksheets/sheet5.xml',
        ];

        foreach ($expectedEntries as $entry) {
            $xml = $zip->getFromName($entry);
            $this->assertNotFalse($xml, "File XML {$entry} wajib ada di dalam .xlsx");
            $dom = new \DOMDocument;
            $this->assertTrue($dom->loadXML($xml), "XML {$entry} wajib valid sesuai standar ECMA-376");
        }

        $workbookXml = (string) $zip->getFromName('xl/workbook.xml');
        $this->assertStringContainsString('1. Ringkasan Eksekutif', $workbookXml);
        $this->assertStringContainsString('2. Rekap Harian', $workbookXml);
        $this->assertStringContainsString('3. Ransum &amp; Gizi', $workbookXml);
        $this->assertStringContainsString('4. Stok &amp; Belanja', $workbookXml);
        $this->assertStringContainsString('5. IoT, Anomali &amp; AI', $workbookXml);

        $zip->close();
        @unlink($tmpXlsx);

        // 3. Unduh CSV UTF-8 BOM Terstruktur
        $csvRes = $this->get(route('laporan.csv', ['rentang' => 'minggu']));
        $csvRes->assertStatus(200);
        $csvContent = $csvRes->streamedContent();
        $this->assertStringStartsWith("\xEF\xBB\xBF", $csvContent);
        $this->assertStringContainsString('LAPORAN SISTEM TERPADU RANSUMKU', $csvContent);
        $this->assertStringContainsString('TOTAL / RATA-RATA', $csvContent);
    }

    public function test_buku_kandang_terpisah_dari_laporan_dan_redirect_tab_lama(): void
    {
        $user = User::where('email', 'demo@ransumku.test')->first();
        $this->actingAs($user);

        // Akses URL lama ?tab=dashboard otomatis diarahkan ke menu /laporan mandiri
        $legacy = $this->get(route('catatan.index', ['tab' => 'dashboard', 'rentang' => 'bulan']));
        $legacy->assertRedirect(route('laporan.index', ['rentang' => 'bulan']));

        // Buku Kandang kini fokus pada 2 tab: Catat Harian & Belanja Pakan
        $catatan = $this->get(route('catatan.index'));
        $catatan->assertStatus(200);
        $catatan->assertSee('Catat Harian');
        $catatan->assertSee('Belanja Pakan');
        $catatan->assertDontSee('Laporan Performa');
        $catatan->assertSee('Laporan Sistem');
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
