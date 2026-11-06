<?php

namespace App\Http\Controllers;

use App\Models\DailyLog;
use App\Models\Farm;
use App\Services\AiAssistant;
use App\Services\PdfReportService;
use App\Services\SpreadsheetExportService;
use App\Services\WeatherService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    /**
     * Menampilkan halaman Laporan Sistem Terpadu yang merangkum seluruh modul RansumKu.
     */
    public function index(Request $r, WeatherService $weatherService, AiAssistant $aiAssistant): View
    {
        return view('laporan.index', $this->kumpulkanDataLaporan($r, $weatherService, $aiAssistant));
    }

    /**
     * Mengunduh berkas Dokumen Resmi PDF A4 menyeluruh secara langsung (1-klik tanpa dialog cetak browser).
     */
    public function unduhPdf(
        Request $r,
        WeatherService $weatherService,
        AiAssistant $aiAssistant,
        PdfReportService $pdfService
    ): Response {
        $data = $this->kumpulkanDataLaporan($r, $weatherService, $aiAssistant);
        $pdfBinary = $pdfService->generateSystemReportPdf($data);
        $filename = 'Laporan_Sistem_RansumKu_'.Str::slug((string) $data['farm']->nama, '_').'_'.now()->format('Y-m-d').'.pdf';

        return response($pdfBinary, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'Content-Length' => (string) strlen($pdfBinary),
            'Cache-Control' => 'no-store, no-cache',
        ]);
    }

    /**
     * Mengunduh berkas Microsoft Excel (.xlsx) 5-sheet rapi dengan border, warna header & format Rupiah asli.
     */
    public function unduhExcel(
        Request $r,
        WeatherService $weatherService,
        AiAssistant $aiAssistant,
        SpreadsheetExportService $spreadsheetService
    ): Response {
        $data = $this->kumpulkanDataLaporan($r, $weatherService, $aiAssistant);
        $xlsxBinary = $spreadsheetService->generateXlsx($data);
        $filename = 'Laporan_Sistem_RansumKu_'.Str::slug((string) $data['farm']->nama, '_').'_'.now()->format('Y-m-d').'.xlsx';

        return response($xlsxBinary, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'Content-Length' => (string) strlen($xlsxBinary),
            'Cache-Control' => 'no-store, no-cache',
        ]);
    }

    /**
     * Mengunduh berkas CSV UTF-8 BOM yang terstruktur rapi saat dibuka di Excel maupun Google Sheets.
     */
    public function unduhCsv(
        Request $r,
        WeatherService $weatherService,
        AiAssistant $aiAssistant,
        SpreadsheetExportService $spreadsheetService
    ): StreamedResponse {
        $data = $this->kumpulkanDataLaporan($r, $weatherService, $aiAssistant);
        $filename = 'Laporan_Sistem_RansumKu_'.Str::slug((string) $data['farm']->nama, '_').'_'.now()->format('Y-m-d').'.csv';

        return $spreadsheetService->streamCsv($data, $filename);
    }

    /**
     * Mengumpulkan seluruh data lintas 6 pilar sistem RansumKu untuk layar, PDF, Excel, dan CSV.
     *
     * @return array<string, mixed>
     */
    private function kumpulkanDataLaporan(Request $r, WeatherService $weatherService, AiAssistant $aiAssistant): array
    {
        $farm = $this->farm();
        $rentangParam = (string) $r->query('rentang', 'minggu');
        if (! in_array($rentangParam, ['minggu', 'bulan', 'semua'], true)) {
            $rentangParam = 'minggu';
        }

        $rentangHari = match ($rentangParam) {
            'bulan' => 30,
            'semua' => 0,
            default => 7,
        };

        $rentangLabel = match ($rentangParam) {
            'bulan' => '30 Hari Terakhir',
            'semua' => 'Seluruh Periode',
            default => '7 Hari Terakhir',
        };

        $hargaPakan = $farm->hargaPakanPerKg();
        $hargaTelurAcuan = (int) config('ransumku.harga_telur_default', 27969);
        $jumlahAyam = max(1, (int) $farm->jumlah_ayam);
        $faseDetail = $farm->faseUmurDetail();

        // 1. Data Buku Kandang, KPI Produksi, Keuangan & Grafik Tren
        $logsQuery = $farm->logs()->orderBy('tanggal');
        if ($rentangHari > 0) {
            $logsQuery->where('tanggal', '>=', now()->subDays($rentangHari - 1)->toDateString());
        }
        $logs = $logsQuery->get();
        $allLogs = $farm->logs()->orderByDesc('tanggal')->get();

        $laporan = $this->hitungLaporanPeriodik($farm, $logs, $hargaPakan, $hargaTelurAcuan, $jumlahAyam, $rentangHari);

        $labels = $logs->map(fn ($l) => $l->tanggal->format('j/n'))->all();
        $rataPakan = $laporan['ini']['hari'] > 0 ? round($laporan['ini']['pakan'] / $laporan['ini']['hari'], 1) : 0.0;
        $rataTelur = round($laporan['ini']['rata_telur']);
        $rataBiaya = $laporan['ini']['hari'] > 0 ? round($laporan['ini']['biaya_pakan'] / $laporan['ini']['hari']) : 0.0;
        $rataEfisiensi = round($laporan['ini']['biaya_kg_telur']);

        $grafik = [
            'pakan' => $this->chart('bar', $labels, 'Pakan (kg)', $logs->pluck('pakan_kg')->map(fn ($v) => (float) $v)->all(), '#c97a1e', $rataPakan),
            'telur' => $this->chart('line', $labels, 'Telur (butir)', $logs->pluck('telur_butir')->all(), '#116a3a', $rataTelur),
            'biaya' => $this->chart('line', $labels, 'Biaya pakan (Rp, estimasi)', $logs->map(fn ($l) => round($l->pakan_kg * $hargaPakan))->all(), '#0e7490', $rataBiaya),
            'efisiensi' => $this->chart('line', $labels, 'Biaya pakan per kg telur (Rp, estimasi)', $logs->map(fn ($l) => $l->telurKgEfektif() > 0 ? round($l->pakan_kg * $hargaPakan / $l->telurKgEfektif()) : 0)->all(), '#7c3aed', $rataEfisiensi),
        ];

        // 2. Modul Formulasi Ransum & Kepatuhan Standar Gizi (SNI / Strain)
        $ransumAktif = $farm->lastRation()?->load('items', 'standard');
        $riwayatRansum = $farm->rations()->with('standard')->latest('id')->limit(5)->get();
        $totalRiwayatRansum = $farm->rations()->count();
        $totalStandarGizi = $farm->standards()->count();

        // 3. Modul Gudang Stok Bahan Baku & Audit Pembelian Pakan
        $stokGudang = $farm->farmIngredients()
            ->with('ingredient')
            ->where('aktif', true)
            ->get()
            ->sortBy(fn ($fi) => $fi->ingredient?->nama ?? '')
            ->values();

        $konsumsiHarianAcuan = $laporan['ini']['hari'] > 0
            ? ($laporan['ini']['pakan'] / $laporan['ini']['hari'])
            : ($jumlahAyam * (float) ($farm->konsumsi_per_ekor ?: config('ransumku.konsumsi_default', 0.11)));

        $totalStokKg = (float) $stokGudang->sum('stok_kg');
        $totalNilaiStokRp = (int) round($stokGudang->sum(fn ($fi) => (float) $fi->stok_kg * (float) $fi->harga));
        $estimasiKetahananHari = $konsumsiHarianAcuan > 0 ? round($totalStokKg / $konsumsiHarianAcuan, 1) : 0.0;

        $ringkasanGudang = [
            'total_bahan_aktif' => $stokGudang->count(),
            'total_katalog_bahan' => $farm->ingredients()->count(),
            'total_stok_kg' => round($totalStokKg, 1),
            'total_nilai_stok_rp' => $totalNilaiStokRp,
            'konsumsi_harian_kg' => round($konsumsiHarianAcuan, 1),
            'estimasi_ketahanan_hari' => $estimasiKetahananHari,
            'stok_menipis_count' => $stokGudang->filter(fn ($fi) => (float) $fi->stok_kg < 50)->count(),
            'harga_usang_count' => $stokGudang->filter(fn ($fi) => $fi->hargaUsang())->count(),
        ];

        $pembelianQuery = $farm->purchases()->with('ingredient')->orderByDesc('tanggal')->orderByDesc('id');
        if ($rentangHari > 0) {
            $pembelianQuery->where('tanggal', '>=', now()->subDays($rentangHari - 1)->toDateString());
        }
        $pembelianPeriode = $pembelianQuery->get();
        $riwayatBelanjaTerbaru = $farm->purchases()->with('ingredient')->latest('tanggal')->latest('id')->limit(8)->get();

        $ringkasanBelanja = [
            'jumlah_transaksi' => $pembelianPeriode->count(),
            'total_volume_kg' => round((float) $pembelianPeriode->sum('kg'), 1),
            'total_pengeluaran_rp' => (int) round($pembelianPeriode->sum(fn ($p) => $p->harga_nyata * $p->kg)),
            'total_selisih_rp' => (int) round($pembelianPeriode->sum(fn ($p) => $p->selisih())),
            'semua_transaksi_count' => $farm->purchases()->count(),
        ];

        // 4. Modul Mikroklimat Kandang (THI) & Smart Controller IoT
        try {
            $cuacaMikro = $weatherService->getWeather($farm);
        } catch (\Throwable) {
            $cuacaMikro = [
                'sumber' => 'estimasi',
                'kota' => $farm->lokasi_kota ?: 'Blitar',
                'suhu' => 28.5,
                'kelembaban' => 72.0,
                'thi' => 78.2,
                'thi_kategori' => 'Waspada Stres Panas Ringan',
                'thi_kategori_singkat' => 'Waspada',
                'thi_level' => 'waspada',
                'badge_class' => 'bg-amber-100 text-amber-800 border-amber-300',
                'saran_manajemen' => ['Pastikan sirkulasi udara dan ketersediaan air minum segar mencukupi.'],
                'updated_at' => now()->translatedFormat('H:i \W\I\B'),
            ];
        }

        $kontrolerSetting = $farm->getSettingsKontroler();
        $sensorIotLive = $weatherService->hasActiveSensor($farm);
        $ringkasanIot = [
            'sensor_live' => $sensorIotLive,
            'controller_siap' => (bool) $farm->controller_siap,
            'mode_kendali' => $farm->controller_mode ?: 'manual',
            'relay_kipas' => (bool) $farm->relay_kipas,
            'relay_misting' => (bool) $farm->relay_misting,
            'relay_lampu' => (bool) $farm->relay_lampu,
            'relay_pakan' => (bool) $farm->relay_pakan,
            'aktif_count' => collect([
                $farm->relay_kipas,
                $farm->relay_misting,
                $farm->relay_lampu,
                $farm->relay_pakan,
            ])->filter()->count(),
        ];

        // 5. Modul Deteksi Dini Anomali & Aktivitas Asisten AI
        $daftarAnomali = $farm->alerts()->latest('tanggal_data')->latest('id')->limit(10)->get();
        $ringkasanAnomali = [
            'total_semua' => $farm->alerts()->count(),
            'belum_dibaca' => $farm->alerts()->where('dibaca', false)->count(),
            'kritis_aktif' => $farm->alerts()->where('dibaca', false)->whereIn('level', ['waspada', 'bahaya'])->count(),
            'perhatian_aktif' => $farm->alerts()->where('dibaca', false)->where('level', 'perhatian')->count(),
        ];

        $ringkasanAi = [
            'tersedia' => $aiAssistant->tersedia(),
            'total_sesi' => $farm->chatSessions()->count(),
            'total_pesan' => $farm->chats()->count(),
        ];

        return [
            'farm' => $farm,
            'rentangParam' => $rentangParam,
            'rentang' => $rentangHari > 0 ? $rentangHari : max(1, $logs->count()),
            'rentangHari' => $rentangHari,
            'rentangLabel' => $rentangLabel,
            'harga' => $hargaPakan,
            'hargaTelurAcuan' => $hargaTelurAcuan,
            'jumlahAyam' => $jumlahAyam,
            'faseDetail' => $faseDetail,
            'logs' => $logs,
            'allLogs' => $allLogs,
            'laporan' => $laporan,
            'grafik' => $grafik,
            'ransumAktif' => $ransumAktif,
            'riwayatRansum' => $riwayatRansum,
            'totalRiwayatRansum' => $totalRiwayatRansum,
            'totalStandarGizi' => $totalStandarGizi,
            'stokGudang' => $stokGudang,
            'ringkasanGudang' => $ringkasanGudang,
            'pembelianPeriode' => $pembelianPeriode,
            'riwayatBelanjaTerbaru' => $riwayatBelanjaTerbaru,
            'ringkasanBelanja' => $ringkasanBelanja,
            'cuacaMikro' => $cuacaMikro,
            'kontrolerSetting' => $kontrolerSetting,
            'ringkasanIot' => $ringkasanIot,
            'daftarAnomali' => $daftarAnomali,
            'ringkasanAnomali' => $ringkasanAnomali,
            'ringkasanAi' => $ringkasanAi,
        ];
    }

    /**
     * Menghitung agregat produksi, efisiensi, dan keuangan periode terpilih dibanding periode sebelumnya.
     *
     * @param  Collection<int, DailyLog>  $iniLogs
     * @return array{
     *     ini: array<string, int|float>,
     *     lalu: array<string, int|float>
     * }
     */
    private function hitungLaporanPeriodik(
        Farm $farm,
        Collection $iniLogs,
        float $hargaPakan,
        int $hargaTelurAcuan,
        int $jumlahAyam,
        int $rentangHari
    ): array {
        if ($rentangHari > 0) {
            $days = max(1, $rentangHari - 1);
            $laluLogs = $farm->logs()->whereBetween('tanggal', [
                now()->subDays($days * 2 + 1)->toDateString(),
                now()->subDays($days + 1)->toDateString(),
            ])->get();
        } else {
            $laluLogs = collect();
        }

        $hitung = function (Collection $c) use ($hargaPakan, $hargaTelurAcuan, $jumlahAyam): array {
            $hari = $c->count();
            $pembagiHari = max(1, $hari);
            $telurButir = (int) $c->sum('telur_butir');
            $telurKg = (float) $c->sum(fn ($l) => $l->telurKgEfektif());
            $pakanKg = (float) $c->sum('pakan_kg');
            $mati = (int) $c->sum('ayam_mati');
            $biayaLain = (int) $c->sum('biaya_lain');
            $pendapatanTelur = (int) round($telurKg * $hargaTelurAcuan);
            $biayaPakan = (int) round($pakanKg * $hargaPakan);
            $marginKotor = $pendapatanTelur - $biayaPakan;
            $marginBersih = $pendapatanTelur - ($biayaPakan + $biayaLain);

            $hdpRata = ($hari > 0 && $jumlahAyam > 0)
                ? round(($telurButir / ($jumlahAyam * $hari)) * 100, 1)
                : 0.0;
            $fcrRata = $telurKg > 0 ? round($pakanKg / $telurKg, 2) : 0.0;
            $gramPerEkor = ($hari > 0 && $jumlahAyam > 0)
                ? round(($pakanKg * 1000) / ($jumlahAyam * $hari), 1)
                : 0.0;

            return [
                'hari' => $hari,
                'pakan' => $pakanKg,
                'telur' => $telurButir,
                'telur_kg' => round($telurKg, 2),
                'mati' => $mati,
                'biaya_pakan' => $biayaPakan,
                'biaya_lain' => $biayaLain,
                'total_biaya_operasional' => $biayaPakan + $biayaLain,
                'pendapatan_telur' => $pendapatanTelur,
                'margin_kotor' => $marginKotor,
                'margin_bersih' => $marginBersih,
                'biaya_kg_telur' => $telurKg > 0 ? $biayaPakan / $telurKg : 0.0,
                'rasio' => $telurButir > 0 ? ($pakanKg * 1000) / $telurButir : 0.0,
                'rata_telur' => $telurButir / $pembagiHari,
                'hdp_rata' => $hdpRata,
                'fcr_rata' => $fcrRata,
                'gram_per_ekor' => $gramPerEkor,
            ];
        };

        return [
            'ini' => $hitung($iniLogs),
            'lalu' => $hitung($laluLogs),
        ];
    }

    /**
     * Menyusun konfigurasi dataset Chart.js seragam untuk halaman laporan.
     *
     * @param  array<int, string>  $labels
     * @param  array<int, int|float>  $data
     * @return array<string, mixed>
     */
    private function chart(string $type, array $labels, string $label, array $data, string $warna, ?float $rataRata = null): array
    {
        $n = count($data);
        $pointRadius = $type === 'line' ? array_fill(0, $n, 0) : 0;
        if ($type === 'line' && $n > 0) {
            $pointRadius[$n - 1] = 4;
        }
        $pointHoverRadius = $type === 'line' ? array_fill(0, $n, 4) : 0;
        if ($type === 'line' && $n > 0) {
            $pointHoverRadius[$n - 1] = 6;
        }

        $fillColor = match ($warna) {
            '#116a3a' => 'rgba(17, 106, 58, 0.12)',
            '#0e7490' => 'rgba(14, 116, 144, 0.12)',
            '#7c3aed' => 'rgba(124, 58, 237, 0.12)',
            default => $type === 'bar' ? $warna : $warna.'22',
        };

        $datasets = [
            [
                'label' => $label,
                'type' => $type,
                'data' => $data,
                'borderColor' => $warna,
                'backgroundColor' => $type === 'bar' ? $warna : $fillColor,
                'fill' => $type === 'line',
                'tension' => .35,
                'borderWidth' => 2.5,
                'pointRadius' => $pointRadius,
                'pointHoverRadius' => $pointHoverRadius,
                'pointBackgroundColor' => '#FFFFFF',
                'pointBorderColor' => $warna,
                'pointBorderWidth' => 2,
                'borderRadius' => $type === 'bar' ? 4 : 0,
                'maxBarThickness' => 24,
            ],
        ];

        if ($rataRata !== null && $n > 0) {
            $datasets[] = [
                'label' => 'Rata-rata',
                'type' => 'line',
                'data' => array_fill(0, $n, round($rataRata, 2)),
                'borderColor' => '#64748b',
                'borderWidth' => 1.5,
                'borderDash' => [4, 4],
                'pointRadius' => 0,
                'pointHoverRadius' => 0,
                'fill' => false,
            ];
        }

        return [
            'type' => $type,
            'labels' => $labels,
            'datasets' => $datasets,
            'zero' => false,
        ];
    }
}
