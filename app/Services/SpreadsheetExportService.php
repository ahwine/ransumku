<?php

namespace App\Services;

use App\Support\Fmt;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use ZipArchive;

class SpreadsheetExportService
{
    private const STYLE_DEFAULT = 0;

    private const STYLE_TITLE = 1;

    private const STYLE_SUBTITLE = 2;

    private const STYLE_SECTION = 3;

    private const STYLE_TH_LEFT = 4;

    private const STYLE_TH_CENTER = 5;

    private const STYLE_TH_RIGHT = 6;

    private const STYLE_TD_LEFT = 7;

    private const STYLE_TD_CENTER = 8;

    private const STYLE_TD_RIGHT = 9;

    private const STYLE_TD_INT = 10;

    private const STYLE_TD_DEC1 = 11;

    private const STYLE_TD_DEC2 = 12;

    private const STYLE_TD_RP = 13;

    private const STYLE_TD_LEFT_ZEBRA = 14;

    private const STYLE_TD_CENTER_ZEBRA = 15;

    private const STYLE_TD_INT_ZEBRA = 16;

    private const STYLE_TD_DEC1_ZEBRA = 17;

    private const STYLE_TD_DEC2_ZEBRA = 18;

    private const STYLE_TD_RP_ZEBRA = 19;

    private const STYLE_TOTAL_LABEL = 20;

    private const STYLE_TOTAL_INT = 21;

    private const STYLE_TOTAL_DEC1 = 22;

    private const STYLE_TOTAL_DEC2 = 23;

    private const STYLE_TOTAL_RP = 24;

    private const STYLE_META_KEY = 25;

    private const STYLE_META_VAL = 26;

    private const STYLE_BADGE_OK = 27;

    private const STYLE_BADGE_WARN = 28;

    /**
     * Menghasilkan biner file Microsoft Excel (.xlsx) asli dengan 5 lembar kerja rapi & berformat.
     *
     * @param  array<string, mixed>  $data
     */
    public function generateXlsx(array $data): string
    {
        $sheets = [
            $this->buildSheetRingkasan($data),
            $this->buildSheetRekapHarian($data),
            $this->buildSheetRansum($data),
            $this->buildSheetGudangBelanja($data),
            $this->buildSheetIotAnomaliAi($data),
        ];

        $files = [
            '[Content_Types].xml' => $this->buildContentTypesXml(count($sheets)),
            '_rels/.rels' => $this->buildRootRelsXml(),
            'xl/workbook.xml' => $this->buildWorkbookXml($sheets),
            'xl/_rels/workbook.xml.rels' => $this->buildWorkbookRelsXml(count($sheets)),
            'xl/styles.xml' => $this->buildStylesXml(),
        ];

        foreach ($sheets as $idx => $sheet) {
            $num = $idx + 1;
            $files["xl/worksheets/sheet{$num}.xml"] = $this->buildWorksheetXml($sheet);
        }

        return $this->packZipArchive($files);
    }

    /**
     * Menghasilkan respons unduhan CSV UTF-8 dengan BOM yang rapi saat dibuka di Excel maupun Google Sheets.
     *
     * @param  array<string, mixed>  $data
     */
    public function streamCsv(array $data, string $filename): StreamedResponse
    {
        return response()->streamDownload(function () use ($data): void {
            $out = fopen('php://output', 'wb');
            if ($out === false) {
                return;
            }

            // UTF-8 BOM agar Excel langsung mengenali karakter UTF-8 dengan rapi
            fwrite($out, "\xEF\xBB\xBF");

            $farm = $data['farm'];
            $ini = $data['laporan']['ini'];
            $jumlahAyam = (int) $data['jumlahAyam'];
            $hargaPakan = (float) $data['harga'];
            $hargaTelur = (int) $data['hargaTelurAcuan'];
            $ransumAktif = $data['ransumAktif'];
            $gudang = $data['ringkasanGudang'];
            $belanja = $data['ringkasanBelanja'];
            $cuaca = $data['cuacaMikro'];
            $iot = $data['ringkasanIot'];
            $anomali = $data['ringkasanAnomali'];
            $ai = $data['ringkasanAi'];

            $put = function (array $row) use ($out): void {
                fputcsv($out, $row, ',');
            };

            $put(['LAPORAN SISTEM TERPADU RANSUMKU - SMART POULTRY FARM']);
            $put(['Kandang', $farm->nama, 'Populasi (Ekor)', $jumlahAyam, 'Umur / Fase', 'Mg ke-'.$farm->umurMinggu().' ('.$data['faseDetail']['nama'].')']);
            $put(['Strain', $farm->strain ?? 'Lohmann Brown', 'Periode Evaluasi', $data['rentangLabel'], 'Tanggal Cetak', now()->translatedFormat('d F Y H:i').' WIB']);
            $put([]);

            $put(['I. RINGKASAN EKSEKUTIF LINTAS MODUL SISTEM']);
            $put(['Kategori Modul', 'Indikator Utama', 'Nilai Aktual', 'Satuan', 'Keterangan / Acuan']);
            $put(['Produksi', 'Total Produksi Telur', $ini['telur'], 'butir', 'Setara '.number_format((float) $ini['telur_kg'], 2, '.', '').' kg']);
            $put(['Produksi', 'Rata-rata Hen Day Production (HDP)', number_format((float) $ini['hdp_rata'], 1, '.', ''), '%', 'Target Fase: '.$data['faseDetail']['standar_hdp']]);
            $put(['Produksi', 'Feed Conversion Ratio (FCR)', number_format((float) $ini['fcr_rata'], 2, '.', ''), 'kg/kg', 'Rasio pakan terhadap berat telur']);
            $put(['Produksi', 'Total Konsumsi Pakan', number_format((float) $ini['pakan'], 1, '.', ''), 'kg', number_format((float) $ini['gram_per_ekor'], 1, '.', '').' g/ekor/hari']);
            $put(['Produksi', 'Mortalitas / Kematian', $ini['mati'], 'ekor', $ini['hari'].' hari pencatatan aktif']);
            $put(['Keuangan', 'Estimasi Omzet Penjualan Telur', $ini['pendapatan_telur'], 'Rp', 'Acuan Rp '.number_format($hargaTelur, 0, ',', '.').'/kg']);
            $put(['Keuangan', 'Estimasi Biaya Pakan', $ini['biaya_pakan'], 'Rp', 'Acuan Rp '.number_format($hargaPakan, 0, ',', '.').'/kg']);
            $put(['Keuangan', 'Biaya Operasional Lainnya', $ini['biaya_lain'], 'Rp', 'Obat, vitamin, listrik, operasional']);
            $put(['Keuangan', 'Estimasi Margin Kotor (IOFC)', $ini['margin_kotor'], 'Rp', 'Omzet Telur dikurangi Biaya Pakan']);
            $put(['Keuangan', 'Estimasi Margin Bersih Operasional', $ini['margin_bersih'], 'Rp', 'Omzet dikurangi seluruh biaya operasional']);
            $put(['Ransum SNI', 'Resep Ransum Aktif', $ransumAktif ? $ransumAktif->namaStandar() : 'Belum ada', '-', $ransumAktif ? 'Rp '.number_format((float) $ransumAktif->biaya_per_kg, 0, ',', '.').'/kg' : '-']);
            $put(['Gudang Stok', 'Total Stok Bahan Baku Aktif', number_format((float) $gudang['total_stok_kg'], 1, '.', ''), 'kg', 'Nilai aset Rp '.number_format((int) $gudang['total_nilai_stok_rp'], 0, ',', '.')]);
            $put(['Gudang Stok', 'Estimasi Ketahanan Pakan', number_format((float) $gudang['estimasi_ketahanan_hari'], 1, '.', ''), 'hari', $gudang['stok_menipis_count'].' bahan berstatus menipis']);
            $put(['Belanja Pakan', 'Total Pengeluaran Belanja Periode', $belanja['total_pengeluaran_rp'], 'Rp', $belanja['jumlah_transaksi'].' transaksi ('.number_format((float) $belanja['total_volume_kg'], 1, '.', '').' kg)']);
            $put(['Mikroklimat IoT', 'Suhu, Kelembaban & THI Kandang', number_format((float) ($cuaca['thi'] ?? 0), 1, '.', ''), 'THI', number_format((float) ($cuaca['suhu'] ?? 0), 1, '.', '').' C / RH '.number_format((float) ($cuaca['kelembaban'] ?? 0), 0, '.', '').'% ('.($cuaca['thi_kategori'] ?? 'Normal').')']);
            $put(['Smart Controller', 'Mode Kendali & Aktuator Aktif', strtoupper((string) $iot['mode_kendali']), 'mode', $iot['aktif_count'].'/4 aktuator menyala']);
            $put(['Anomali & AI', 'Peringatan Dini & Konsultasi AI', $anomali['belum_dibaca'], 'anomali aktif', $ai['total_sesi'].' sesi AI ('.$ai['total_pesan'].' pesan)']);
            $put([]);

            $put(['II. REKAPITULASI HARIAN BUKU KANDANG']);
            $put([
                'No',
                'Tanggal',
                'Pakan (kg)',
                'Asupan (g/ekor)',
                'Telur (butir)',
                'Telur (kg)',
                'HDP (%)',
                'FCR',
                'Ayam Mati (ekor)',
                'Est. Biaya Pakan (Rp)',
                'Biaya Lain (Rp)',
                'Est. Omzet Telur (Rp)',
                'Est. Margin Bersih (Rp)',
                'Catatan Harian',
            ]);

            foreach ($data['logs'] as $idx => $log) {
                $telurKg = (float) $log->telurKgEfektif();
                $hdp = $jumlahAyam > 0 ? round(($log->telur_butir / $jumlahAyam) * 100, 1) : 0.0;
                $grEkor = $jumlahAyam > 0 ? round(($log->pakan_kg * 1000) / $jumlahAyam, 1) : 0.0;
                $fcr = $telurKg > 0 ? round($log->pakan_kg / $telurKg, 2) : 0.0;
                $biayaPakanHarian = (int) round($log->pakan_kg * $hargaPakan);
                $omzetHarian = (int) round($telurKg * $hargaTelur);
                $marginHarian = $omzetHarian - ($biayaPakanHarian + (int) $log->biaya_lain);

                $put([
                    $idx + 1,
                    $log->tanggal->format('Y-m-d'),
                    number_format((float) $log->pakan_kg, 1, '.', ''),
                    number_format($grEkor, 1, '.', ''),
                    (int) $log->telur_butir,
                    number_format($telurKg, 2, '.', ''),
                    number_format($hdp, 1, '.', ''),
                    number_format($fcr, 2, '.', ''),
                    (int) $log->ayam_mati,
                    $biayaPakanHarian,
                    (int) $log->biaya_lain,
                    $omzetHarian,
                    $marginHarian,
                    preg_replace('/\s+/', ' ', trim((string) ($log->catatan ?? '-'))) ?: '-',
                ]);
            }

            $put([
                'TOTAL / RATA-RATA',
                $ini['hari'].' Hari',
                number_format((float) $ini['pakan'], 1, '.', ''),
                number_format((float) $ini['gram_per_ekor'], 1, '.', ''),
                (int) $ini['telur'],
                number_format((float) $ini['telur_kg'], 2, '.', ''),
                number_format((float) $ini['hdp_rata'], 1, '.', ''),
                number_format((float) $ini['fcr_rata'], 2, '.', ''),
                (int) $ini['mati'],
                (int) $ini['biaya_pakan'],
                (int) $ini['biaya_lain'],
                (int) $ini['pendapatan_telur'],
                (int) $ini['margin_bersih'],
                'Rekap Terverifikasi Sistem',
            ]);

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'no-store, no-cache',
        ]);
    }

    /**
     * Sheet 1: Ringkasan Eksekutif Sistem.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function buildSheetRingkasan(array $data): array
    {
        $farm = $data['farm'];
        $ini = $data['laporan']['ini'];
        $lalu = $data['laporan']['lalu'];
        $gudang = $data['ringkasanGudang'];
        $belanja = $data['ringkasanBelanja'];
        $cuaca = $data['cuacaMikro'];
        $iot = $data['ringkasanIot'];
        $anomali = $data['ringkasanAnomali'];
        $ai = $data['ringkasanAi'];

        $rows = [];
        $merges = [];

        $rows[] = $this->bannerRow(1, 'LAPORAN SISTEM TERPADU RANSUMKU — SMART POULTRY FARM', 5, self::STYLE_TITLE, 28);
        $merges[] = 'A1:E1';

        $rows[] = $this->bannerRow(2, 'Rekapitulasi Menyeluruh Produksi, Keuangan, Ransum SNI, Gudang Pakan, Mikroklimat IoT & Asisten AI', 5, self::STYLE_SUBTITLE, 20);
        $merges[] = 'A2:E2';

        $rows[] = ['r' => 3, 'ht' => 10, 'cells' => []];

        // Metadata Kandang
        $rows[] = $this->bannerRow(4, 'I. IDENTITAS & PROFIL KANDANG', 5, self::STYLE_SECTION, 22);
        $merges[] = 'A4:E4';

        $metaPairs = [
            ['Nama Kandang', $farm->nama, 'Periode Evaluasi', $data['rentangLabel'].' ('.$ini['hari'].' hari aktif)'],
            ['Populasi & Strain', number_format((int) $data['jumlahAyam'], 0, ',', '.').' ekor · '.($farm->strain ?? 'Lohmann Brown'), 'Umur & Fase Biologis', 'Minggu ke-'.$farm->umurMinggu().' · '.$data['faseDetail']['nama']],
            ['Lokasi Kandang', $farm->lokasi_kota ?: 'Blitar', 'Tanggal Diterbitkan', now()->translatedFormat('d F Y, H:i').' WIB'],
        ];

        $r = 5;
        foreach ($metaPairs as $mp) {
            $rows[] = [
                'r' => $r,
                'ht' => 20,
                'cells' => [
                    $this->strCell(1, $mp[0], self::STYLE_META_KEY),
                    $this->strCell(2, $mp[1], self::STYLE_META_VAL),
                    $this->strCell(3, $mp[2], self::STYLE_META_KEY),
                    $this->strCell(4, $mp[3], self::STYLE_META_VAL),
                    $this->strCell(5, '', self::STYLE_META_VAL),
                ],
            ];
            $merges[] = "D{$r}:E{$r}";
            $r++;
        }

        $rows[] = ['r' => $r++, 'ht' => 10, 'cells' => []];

        // Keuangan Eksekutif
        $rows[] = $this->bannerRow($r, 'II. NERACA KEUANGAN OPERASIONAL KANDANG (ESTIMASI)', 5, self::STYLE_SECTION, 22);
        $merges[] = "A{$r}:E{$r}";
        $r++;

        $rows[] = [
            'r' => $r++,
            'ht' => 22,
            'cells' => [
                $this->strCell(1, 'Komponen Keuangan', self::STYLE_TH_LEFT),
                $this->strCell(2, 'Periode Ini (Rp)', self::STYLE_TH_RIGHT),
                $this->strCell(3, 'Periode Sebelumnya (Rp)', self::STYLE_TH_RIGHT),
                $this->strCell(4, 'Dasar Perhitungan / Keterangan', self::STYLE_TH_LEFT),
                $this->strCell(5, 'Status', self::STYLE_TH_CENTER),
            ],
        ];

        $keuanganRows = [
            ['Estimasi Omzet Penjualan Telur', $ini['pendapatan_telur'], $lalu['pendapatan_telur'] ?? 0, 'Harga acuan '.Fmt::rp($data['hargaTelurAcuan']).'/kg × '.Fmt::num($ini['telur_kg'], 2).' kg', 'Pendapatan'],
            ['Estimasi Biaya Konsumsi Pakan', $ini['biaya_pakan'], $lalu['biaya_pakan'] ?? 0, 'Harga ransum '.Fmt::rp($data['harga']).'/kg × '.Fmt::num($ini['pakan'], 1).' kg', 'Beban Pokok'],
            ['Biaya Operasional Tambahan', $ini['biaya_lain'], $lalu['biaya_lain'] ?? 0, 'Akumulasi biaya obat, vitamin, listrik & perawatan harian', 'Beban Lain'],
            ['Estimasi Margin Kotor (IOFC)', $ini['margin_kotor'], $lalu['margin_kotor'] ?? 0, 'Selisih Omzet Telur dikurangi Biaya Konsumsi Pakan', $ini['margin_kotor'] >= 0 ? 'Surplus' : 'Defisit'],
        ];

        foreach ($keuanganRows as $idx => $kr) {
            $z = $idx % 2 === 1;
            $rows[] = [
                'r' => $r++,
                'ht' => 20,
                'cells' => [
                    $this->strCell(1, $kr[0], $z ? self::STYLE_TD_LEFT_ZEBRA : self::STYLE_TD_LEFT),
                    $this->numCell(2, $kr[1], $z ? self::STYLE_TD_RP_ZEBRA : self::STYLE_TD_RP),
                    $this->numCell(3, $kr[2], $z ? self::STYLE_TD_RP_ZEBRA : self::STYLE_TD_RP),
                    $this->strCell(4, $kr[3], $z ? self::STYLE_TD_LEFT_ZEBRA : self::STYLE_TD_LEFT),
                    $this->strCell(5, $kr[4], $kr[4] === 'Defisit' ? self::STYLE_BADGE_WARN : self::STYLE_BADGE_OK),
                ],
            ];
        }

        // Baris Total Margin Bersih
        $rows[] = [
            'r' => $r++,
            'ht' => 22,
            'cells' => [
                $this->strCell(1, 'ESTIMASI MARGIN BERSIH OPERASIONAL', self::STYLE_TOTAL_LABEL),
                $this->numCell(2, $ini['margin_bersih'], self::STYLE_TOTAL_RP),
                $this->numCell(3, $lalu['margin_bersih'] ?? 0, self::STYLE_TOTAL_RP),
                $this->strCell(4, 'Omzet Telur dikurangi Total Biaya Pakan & Operasional Lain', self::STYLE_TOTAL_LABEL),
                $this->strCell(5, $ini['margin_bersih'] >= 0 ? 'POSITIF' : 'NEGATIF', $ini['margin_bersih'] >= 0 ? self::STYLE_BADGE_OK : self::STYLE_BADGE_WARN),
            ],
        ];

        $rows[] = ['r' => $r++, 'ht' => 10, 'cells' => []];

        // Ringkasan 6 Pilar Sistem
        $rows[] = $this->bannerRow($r, 'III. RINGKASAN EKSEKUTIF SELURUH MODUL SISTEM', 5, self::STYLE_SECTION, 22);
        $merges[] = "A{$r}:E{$r}";
        $r++;

        $rows[] = [
            'r' => $r++,
            'ht' => 22,
            'cells' => [
                $this->strCell(1, 'Modul Sistem', self::STYLE_TH_LEFT),
                $this->strCell(2, 'Indikator Kunci', self::STYLE_TH_LEFT),
                $this->strCell(3, 'Capaian Aktual', self::STYLE_TH_CENTER),
                $this->strCell(4, 'Standar / Acuan Pembanding', self::STYLE_TH_LEFT),
                $this->strCell(5, 'Evaluasi', self::STYLE_TH_CENTER),
            ],
        ];

        $ransumAktif = $data['ransumAktif'];
        $pilarRows = [
            ['Buku Kandang', 'Total Produksi Telur', Fmt::num($ini['telur'], 0).' butir ('.Fmt::num($ini['telur_kg'], 1).' kg)', 'Rata-rata '.Fmt::num(round($ini['rata_telur']), 0).' butir/hari', 'Tercatat'],
            ['Buku Kandang', 'Hen Day Production (HDP)', Fmt::num($ini['hdp_rata'], 1).'%', 'Standar fase: '.$data['faseDetail']['standar_hdp'], $ini['hdp_rata'] >= 75 ? 'Optimal' : 'Evaluasi'],
            ['Buku Kandang', 'FCR & Asupan Pakan Harian', 'FCR '.Fmt::num($ini['fcr_rata'], 2).' ('.Fmt::num($ini['gram_per_ekor'], 1).' g/ekor)', 'Standar pakan: '.$data['faseDetail']['standar_pakan'], $ini['fcr_rata'] > 0 && $ini['fcr_rata'] <= 2.4 ? 'Efisien' : 'Pantau'],
            ['Buku Kandang', 'Mortalitas / Kematian Ayam', $ini['mati'].' ekor', 'Populasi aktif '.number_format((int) $data['jumlahAyam'], 0, ',', '.').' ekor', $ini['mati'] === 0 ? 'Aman' : 'Perhatian'],
            ['Peracik Ransum', 'Formula Ransum Aktif', $ransumAktif ? $ransumAktif->namaStandar() : 'Belum disusun', $ransumAktif ? Fmt::rp($ransumAktif->biaya_per_kg).'/kg · Batch '.Fmt::num($ransumAktif->total_kg, 1).' kg' : 'Susun di menu Peracik Ransum', $ransumAktif ? 'Layak SNI' : 'Kosong'],
            ['Gudang Bahan', 'Stok Gudang & Ketahanan', Fmt::num($gudang['total_stok_kg'], 1).' kg (≈ '.Fmt::num($gudang['estimasi_ketahanan_hari'], 1).' hari)', 'Nilai aset '.Fmt::rp($gudang['total_nilai_stok_rp']), $gudang['stok_menipis_count'] > 0 ? $gudang['stok_menipis_count'].' Menipis' : 'Cukup'],
            ['Audit Belanja', 'Realisasi Pembelian Bahan', Fmt::rp($belanja['total_pengeluaran_rp']).' ('.$belanja['jumlah_transaksi'].' trx)', 'Deviasi vs estimasi: '.($belanja['total_selisih_rp'] >= 0 ? '+' : '-').Fmt::rp(abs($belanja['total_selisih_rp'])), $belanja['total_selisih_rp'] > 0 ? 'Harga Naik' : 'Terkendali'],
            ['Mikroklimat IoT', 'Suhu, Kelembaban & Indeks THI', Fmt::num($cuaca['suhu'] ?? 0, 1).' °C · RH '.Fmt::num($cuaca['kelembaban'] ?? 0, 0).'% · THI '.Fmt::num($cuaca['thi'] ?? 0, 1), $cuaca['thi_kategori'] ?? 'Zona Nyaman', ($cuaca['thi'] ?? 0) >= 78 ? 'Waspada Panas' : 'Nyaman'],
            ['Smart Controller', 'Kendali Otomasi & Relay Aktuator', 'Mode '.strtoupper((string) $iot['mode_kendali']).' ('.$iot['aktif_count'].'/4 ON)', 'Kipas, Cooling Misting, Lampu, Shuttle Feeder', $iot['controller_siap'] ? 'Terhubung' : 'Simulasi'],
            ['Anomali & AI', 'Deteksi Dini & Konsultasi AI', $anomali['belum_dibaca'].' anomali aktif · '.$ai['total_sesi'].' sesi AI', 'Total '.$anomali['total_semua'].' riwayat anomali · '.$ai['total_pesan'].' pesan AI', $anomali['belum_dibaca'] > 0 ? 'Perlu Tinjau' : 'Terkendali'],
        ];

        foreach ($pilarRows as $idx => $pr) {
            $z = $idx % 2 === 1;
            $isWarn = in_array($pr[4], ['Evaluasi', 'Perhatian', 'Waspada Panas', 'Perlu Tinjau', 'Harga Naik'], true) || str_contains($pr[4], 'Menipis');
            $rows[] = [
                'r' => $r++,
                'ht' => 20,
                'cells' => [
                    $this->strCell(1, $pr[0], $z ? self::STYLE_TD_LEFT_ZEBRA : self::STYLE_TD_LEFT),
                    $this->strCell(2, $pr[1], $z ? self::STYLE_TD_LEFT_ZEBRA : self::STYLE_TD_LEFT),
                    $this->strCell(3, $pr[2], $z ? self::STYLE_TD_CENTER_ZEBRA : self::STYLE_TD_CENTER),
                    $this->strCell(4, $pr[3], $z ? self::STYLE_TD_LEFT_ZEBRA : self::STYLE_TD_LEFT),
                    $this->strCell(5, $pr[4], $isWarn ? self::STYLE_BADGE_WARN : self::STYLE_BADGE_OK),
                ],
            ];
        }

        return [
            'name' => '1. Ringkasan Eksekutif',
            'cols' => [28, 30, 26, 44, 18],
            'rows' => $rows,
            'merges' => $merges,
            'freezeRow' => 0,
        ];
    }

    /**
     * Sheet 2: Rekap Harian Buku Kandang.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function buildSheetRekapHarian(array $data): array
    {
        $ini = $data['laporan']['ini'];
        $jumlahAyam = (int) $data['jumlahAyam'];
        $hargaPakan = (float) $data['harga'];
        $hargaTelur = (int) $data['hargaTelurAcuan'];

        $rows = [];
        $merges = [];

        $rows[] = $this->bannerRow(1, 'REKAPITULASI HARIAN BUKU KANDANG — '.strtoupper((string) $data['farm']->nama), 13, self::STYLE_TITLE, 26);
        $merges[] = 'A1:M1';

        $rows[] = $this->bannerRow(2, 'Periode: '.$data['rentangLabel'].' · Populasi: '.number_format($jumlahAyam, 0, ',', '.').' ekor · Harga Pakan Acuan: '.Fmt::rp($hargaPakan).'/kg · Harga Telur Acuan: '.Fmt::rp($hargaTelur).'/kg', 13, self::STYLE_SUBTITLE, 19);
        $merges[] = 'A2:M2';

        $headers = [
            [1, 'No', self::STYLE_TH_CENTER],
            [2, 'Tanggal', self::STYLE_TH_CENTER],
            [3, 'Pakan (kg)', self::STYLE_TH_RIGHT],
            [4, 'g/Ekor/Hari', self::STYLE_TH_RIGHT],
            [5, 'Telur (butir)', self::STYLE_TH_RIGHT],
            [6, 'Telur (kg)', self::STYLE_TH_RIGHT],
            [7, 'HDP (%)', self::STYLE_TH_RIGHT],
            [8, 'FCR', self::STYLE_TH_RIGHT],
            [9, 'Mati (ekor)', self::STYLE_TH_CENTER],
            [10, 'Est. Biaya Pakan', self::STYLE_TH_RIGHT],
            [11, 'Biaya Lain', self::STYLE_TH_RIGHT],
            [12, 'Est. Margin Bersih', self::STYLE_TH_RIGHT],
            [13, 'Catatan Lapangan', self::STYLE_TH_LEFT],
        ];

        $rows[] = [
            'r' => 3,
            'ht' => 24,
            'cells' => array_map(fn ($h) => $this->strCell($h[0], $h[1], $h[2]), $headers),
        ];

        $r = 4;
        foreach ($data['logs'] as $idx => $log) {
            $z = $idx % 2 === 1;
            $telurKg = round((float) $log->telurKgEfektif(), 2);
            $gr = $jumlahAyam > 0 ? round(($log->pakan_kg * 1000) / $jumlahAyam, 1) : 0.0;
            $hdp = $jumlahAyam > 0 ? round(($log->telur_butir / $jumlahAyam) * 100, 1) : 0.0;
            $fcr = $telurKg > 0 ? round($log->pakan_kg / $telurKg, 2) : 0.0;
            $biayaPakan = (int) round($log->pakan_kg * $hargaPakan);
            $omzet = (int) round($telurKg * $hargaTelur);
            $marginBersih = $omzet - ($biayaPakan + (int) $log->biaya_lain);

            $rows[] = [
                'r' => $r++,
                'ht' => 20,
                'cells' => [
                    $this->numCell(1, $idx + 1, $z ? self::STYLE_TD_CENTER_ZEBRA : self::STYLE_TD_CENTER),
                    $this->strCell(2, $log->tanggal->format('d/m/Y'), $z ? self::STYLE_TD_CENTER_ZEBRA : self::STYLE_TD_CENTER),
                    $this->numCell(3, (float) $log->pakan_kg, $z ? self::STYLE_TD_DEC1_ZEBRA : self::STYLE_TD_DEC1),
                    $this->numCell(4, $gr, $z ? self::STYLE_TD_DEC1_ZEBRA : self::STYLE_TD_DEC1),
                    $this->numCell(5, (int) $log->telur_butir, $z ? self::STYLE_TD_INT_ZEBRA : self::STYLE_TD_INT),
                    $this->numCell(6, $telurKg, $z ? self::STYLE_TD_DEC2_ZEBRA : self::STYLE_TD_DEC2),
                    $this->numCell(7, $hdp, $z ? self::STYLE_TD_DEC1_ZEBRA : self::STYLE_TD_DEC1),
                    $this->numCell(8, $fcr, $z ? self::STYLE_TD_DEC2_ZEBRA : self::STYLE_TD_DEC2),
                    $this->numCell(9, (int) $log->ayam_mati, $z ? self::STYLE_TD_INT_ZEBRA : self::STYLE_TD_INT),
                    $this->numCell(10, $biayaPakan, $z ? self::STYLE_TD_RP_ZEBRA : self::STYLE_TD_RP),
                    $this->numCell(11, (int) $log->biaya_lain, $z ? self::STYLE_TD_RP_ZEBRA : self::STYLE_TD_RP),
                    $this->numCell(12, $marginBersih, $z ? self::STYLE_TD_RP_ZEBRA : self::STYLE_TD_RP),
                    $this->strCell(13, $log->catatan ?: '-', $z ? self::STYLE_TD_LEFT_ZEBRA : self::STYLE_TD_LEFT),
                ],
            ];
        }

        if ($data['logs']->isEmpty()) {
            $rows[] = $this->bannerRow($r, 'Belum ada data pencatatan harian pada rentang periode ini.', 13, self::STYLE_TD_CENTER, 22);
            $merges[] = "A{$r}:M{$r}";
            $r++;
        }

        $rows[] = [
            'r' => $r,
            'ht' => 23,
            'cells' => [
                $this->strCell(1, 'TOTAL / RATA-RATA', self::STYLE_TOTAL_LABEL),
                $this->strCell(2, $ini['hari'].' Hari', self::STYLE_TOTAL_LABEL),
                $this->numCell(3, (float) $ini['pakan'], self::STYLE_TOTAL_DEC1),
                $this->numCell(4, (float) $ini['gram_per_ekor'], self::STYLE_TOTAL_DEC1),
                $this->numCell(5, (int) $ini['telur'], self::STYLE_TOTAL_INT),
                $this->numCell(6, (float) $ini['telur_kg'], self::STYLE_TOTAL_DEC2),
                $this->numCell(7, (float) $ini['hdp_rata'], self::STYLE_TOTAL_DEC1),
                $this->numCell(8, (float) $ini['fcr_rata'], self::STYLE_TOTAL_DEC2),
                $this->numCell(9, (int) $ini['mati'], self::STYLE_TOTAL_INT),
                $this->numCell(10, (int) $ini['biaya_pakan'], self::STYLE_TOTAL_RP),
                $this->numCell(11, (int) $ini['biaya_lain'], self::STYLE_TOTAL_RP),
                $this->numCell(12, (int) $ini['margin_bersih'], self::STYLE_TOTAL_RP),
                $this->strCell(13, 'Omzet: '.Fmt::rp($ini['pendapatan_telur']), self::STYLE_TOTAL_LABEL),
            ],
        ];

        return [
            'name' => '2. Rekap Harian',
            'cols' => [7, 13, 13, 13, 14, 12, 11, 10, 12, 18, 16, 19, 34],
            'rows' => $rows,
            'merges' => $merges,
            'freezeRow' => 3,
        ];
    }

    /**
     * Sheet 3: Formulasi Ransum & Kepatuhan Gizi SNI.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function buildSheetRansum(array $data): array
    {
        $ransum = $data['ransumAktif'];
        $rows = [];
        $merges = [];

        $rows[] = $this->bannerRow(1, 'LAPORAN FORMULASI RANSUM & KEPATUHAN STANDAR GIZI (SNI / STRAIN)', 6, self::STYLE_TITLE, 26);
        $merges[] = 'A1:F1';

        $sub = $ransum
            ? 'Acuan Aktif: '.$ransum->namaStandar().' · Biaya Ransum: '.Fmt::rp($ransum->biaya_per_kg).'/kg · Target Batch: '.Fmt::num($ransum->total_kg, 1).' kg ('.Fmt::rp($ransum->total_biaya).')'
            : 'Belum ada resep ransum aktif yang diformulasikan pada kandang ini.';
        $rows[] = $this->bannerRow(2, $sub, 6, self::STYLE_SUBTITLE, 20);
        $merges[] = 'A2:F2';

        $r = 4;
        $rows[] = $this->bannerRow($r, 'I. KOMPOSISI BAHAN BAKU RANSUM AKTIF', 6, self::STYLE_SECTION, 22);
        $merges[] = "A{$r}:F{$r}";
        $r++;

        $rows[] = [
            'r' => $r++,
            'ht' => 22,
            'cells' => [
                $this->strCell(1, 'No', self::STYLE_TH_CENTER),
                $this->strCell(2, 'Nama Bahan Pakan', self::STYLE_TH_LEFT),
                $this->strCell(3, 'Porsi BK (%)', self::STYLE_TH_RIGHT),
                $this->strCell(4, 'Takaran Batch (kg)', self::STYLE_TH_RIGHT),
                $this->strCell(5, 'Harga Saat Hitung', self::STYLE_TH_RIGHT),
                $this->strCell(6, 'Subtotal Biaya', self::STYLE_TH_RIGHT),
            ],
        ];

        if ($ransum && $ransum->items->isNotEmpty()) {
            foreach ($ransum->items as $idx => $item) {
                $z = $idx % 2 === 1;
                $rows[] = [
                    'r' => $r++,
                    'ht' => 20,
                    'cells' => [
                        $this->numCell(1, $idx + 1, $z ? self::STYLE_TD_CENTER_ZEBRA : self::STYLE_TD_CENTER),
                        $this->strCell(2, $item->nama, $z ? self::STYLE_TD_LEFT_ZEBRA : self::STYLE_TD_LEFT),
                        $this->numCell(3, (float) $item->persen, $z ? self::STYLE_TD_DEC2_ZEBRA : self::STYLE_TD_DEC2),
                        $this->numCell(4, (float) ($item->kg_as_fed ?? $item->kg), $z ? self::STYLE_TD_DEC2_ZEBRA : self::STYLE_TD_DEC2),
                        $this->numCell(5, (int) $item->harga_saat_hitung, $z ? self::STYLE_TD_RP_ZEBRA : self::STYLE_TD_RP),
                        $this->numCell(6, (int) $item->biaya, $z ? self::STYLE_TD_RP_ZEBRA : self::STYLE_TD_RP),
                    ],
                ];
            }

            $rows[] = [
                'r' => $r++,
                'ht' => 22,
                'cells' => [
                    $this->strCell(1, '', self::STYLE_TOTAL_LABEL),
                    $this->strCell(2, 'TOTAL FORMULASI RANSUM', self::STYLE_TOTAL_LABEL),
                    $this->numCell(3, 100.0, self::STYLE_TOTAL_DEC2),
                    $this->numCell(4, (float) $ransum->total_kg, self::STYLE_TOTAL_DEC2),
                    $this->numCell(5, (int) round((float) $ransum->biaya_per_kg), self::STYLE_TOTAL_RP),
                    $this->numCell(6, (int) round((float) $ransum->total_biaya), self::STYLE_TOTAL_RP),
                ],
            ];
        } else {
            $rows[] = $this->bannerRow($r, 'Belum ada item komposisi ransum aktif.', 6, self::STYLE_TD_CENTER, 20);
            $merges[] = "A{$r}:F{$r}";
            $r++;
        }

        $r++;
        $rows[] = $this->bannerRow($r, 'II. EVALUASI PEMENUHAN PARAMETER GIZI', 6, self::STYLE_SECTION, 22);
        $merges[] = "A{$r}:F{$r}";
        $r++;

        $rows[] = [
            'r' => $r++,
            'ht' => 22,
            'cells' => [
                $this->strCell(1, 'No', self::STYLE_TH_CENTER),
                $this->strCell(2, 'Parameter Nutrisi', self::STYLE_TH_LEFT),
                $this->strCell(3, 'Kandungan Aktual', self::STYLE_TH_RIGHT),
                $this->strCell(4, 'Satuan', self::STYLE_TH_CENTER),
                $this->strCell(5, 'Target Standar Acuan', self::STYLE_TH_LEFT),
                $this->strCell(6, 'Status Kepatuhan', self::STYLE_TH_CENTER),
            ],
        ];

        $giziList = $ransum?->hasil['gizi'] ?? [];
        if (! empty($giziList)) {
            foreach ($giziList as $idx => $g) {
                $z = $idx % 2 === 1;
                $ok = ! empty($g['ok']);
                $rows[] = [
                    'r' => $r++,
                    'ht' => 20,
                    'cells' => [
                        $this->numCell(1, $idx + 1, $z ? self::STYLE_TD_CENTER_ZEBRA : self::STYLE_TD_CENTER),
                        $this->strCell(2, (string) $g['label'], $z ? self::STYLE_TD_LEFT_ZEBRA : self::STYLE_TD_LEFT),
                        $this->numCell(3, (float) $g['nilai'], $z ? self::STYLE_TD_DEC2_ZEBRA : self::STYLE_TD_DEC2),
                        $this->strCell(4, trim((string) $g['satuan']), $z ? self::STYLE_TD_CENTER_ZEBRA : self::STYLE_TD_CENTER),
                        $this->strCell(5, (string) $g['target'], $z ? self::STYLE_TD_LEFT_ZEBRA : self::STYLE_TD_LEFT),
                        $this->strCell(6, $ok ? 'TERPENUHI' : 'BELUM SESUAI', $ok ? self::STYLE_BADGE_OK : self::STYLE_BADGE_WARN),
                    ],
                ];
            }
        } else {
            $rows[] = $this->bannerRow($r, 'Data parameter nutrisi belum tersedia.', 6, self::STYLE_TD_CENTER, 20);
            $merges[] = "A{$r}:F{$r}";
        }

        return [
            'name' => '3. Ransum & Gizi',
            'cols' => [7, 28, 16, 18, 24, 20],
            'rows' => $rows,
            'merges' => $merges,
            'freezeRow' => 0,
        ];
    }

    /**
     * Sheet 4: Gudang Stok Bahan & Audit Belanja Pakan.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function buildSheetGudangBelanja(array $data): array
    {
        $gudang = $data['ringkasanGudang'];
        $belanja = $data['ringkasanBelanja'];
        $rows = [];
        $merges = [];

        $rows[] = $this->bannerRow(1, 'LAPORAN INVENTARIS GUDANG BAHAN BAKU & AUDIT BELANJA PAKAN', 7, self::STYLE_TITLE, 26);
        $merges[] = 'A1:G1';

        $rows[] = $this->bannerRow(2, 'Total Stok Aktif: '.Fmt::num($gudang['total_stok_kg'], 1).' kg · Nilai Aset Gudang: '.Fmt::rp($gudang['total_nilai_stok_rp']).' · Estimasi Ketahanan: '.Fmt::num($gudang['estimasi_ketahanan_hari'], 1).' hari', 7, self::STYLE_SUBTITLE, 20);
        $merges[] = 'A2:G2';

        $r = 4;
        $rows[] = $this->bannerRow($r, 'I. INVENTARIS STOK BAHAN BAKU AKTIF DI GUDANG', 7, self::STYLE_SECTION, 22);
        $merges[] = "A{$r}:G{$r}";
        $r++;

        $rows[] = [
            'r' => $r++,
            'ht' => 22,
            'cells' => [
                $this->strCell(1, 'No', self::STYLE_TH_CENTER),
                $this->strCell(2, 'Nama Bahan Baku', self::STYLE_TH_LEFT),
                $this->strCell(3, 'Kategori', self::STYLE_TH_CENTER),
                $this->strCell(4, 'Stok Gudang (kg)', self::STYLE_TH_RIGHT),
                $this->strCell(5, 'Harga Acuan / kg', self::STYLE_TH_RIGHT),
                $this->strCell(6, 'Nilai Aset Bahan', self::STYLE_TH_RIGHT),
                $this->strCell(7, 'Status Kesiapan', self::STYLE_TH_CENTER),
            ],
        ];

        foreach ($data['stokGudang'] as $idx => $fi) {
            $z = $idx % 2 === 1;
            $menipis = (float) $fi->stok_kg < 50;
            $usang = $fi->hargaUsang();
            $nilaiAset = (int) round((float) $fi->stok_kg * (float) $fi->harga);
            $statusLabel = $menipis ? 'STOK MENIPIS' : ($usang ? 'HARGA USANG' : 'AMAN');

            $rows[] = [
                'r' => $r++,
                'ht' => 20,
                'cells' => [
                    $this->numCell(1, $idx + 1, $z ? self::STYLE_TD_CENTER_ZEBRA : self::STYLE_TD_CENTER),
                    $this->strCell(2, $fi->ingredient?->nama ?? '-', $z ? self::STYLE_TD_LEFT_ZEBRA : self::STYLE_TD_LEFT),
                    $this->strCell(3, ucfirst((string) ($fi->ingredient?->kategori ?? 'bahan')), $z ? self::STYLE_TD_CENTER_ZEBRA : self::STYLE_TD_CENTER),
                    $this->numCell(4, (float) $fi->stok_kg, $z ? self::STYLE_TD_DEC1_ZEBRA : self::STYLE_TD_DEC1),
                    $this->numCell(5, (int) $fi->harga, $z ? self::STYLE_TD_RP_ZEBRA : self::STYLE_TD_RP),
                    $this->numCell(6, $nilaiAset, $z ? self::STYLE_TD_RP_ZEBRA : self::STYLE_TD_RP),
                    $this->strCell(7, $statusLabel, ($menipis || $usang) ? self::STYLE_BADGE_WARN : self::STYLE_BADGE_OK),
                ],
            ];
        }

        $rows[] = [
            'r' => $r++,
            'ht' => 22,
            'cells' => [
                $this->strCell(1, '', self::STYLE_TOTAL_LABEL),
                $this->strCell(2, 'TOTAL ASET INVENTARIS GUDANG', self::STYLE_TOTAL_LABEL),
                $this->strCell(3, $gudang['total_bahan_aktif'].' Bahan', self::STYLE_TOTAL_LABEL),
                $this->numCell(4, (float) $gudang['total_stok_kg'], self::STYLE_TOTAL_DEC1),
                $this->strCell(5, '-', self::STYLE_TOTAL_LABEL),
                $this->numCell(6, (int) $gudang['total_nilai_stok_rp'], self::STYLE_TOTAL_RP),
                $this->strCell(7, '≈ '.Fmt::num($gudang['estimasi_ketahanan_hari'], 1).' Hari', self::STYLE_TOTAL_LABEL),
            ],
        ];

        $r++;
        $rows[] = $this->bannerRow($r, 'II. AUDIT TRANSAKSI PEMBELIAN BAHAN PAKAN ('.$data['rentangLabel'].')', 7, self::STYLE_SECTION, 22);
        $merges[] = "A{$r}:G{$r}";
        $r++;

        $rows[] = [
            'r' => $r++,
            'ht' => 22,
            'cells' => [
                $this->strCell(1, 'No', self::STYLE_TH_CENTER),
                $this->strCell(2, 'Tanggal Beli', self::STYLE_TH_CENTER),
                $this->strCell(3, 'Nama Bahan', self::STYLE_TH_LEFT),
                $this->strCell(4, 'Volume (kg)', self::STYLE_TH_RIGHT),
                $this->strCell(5, 'Harga Toko / kg', self::STYLE_TH_RIGHT),
                $this->strCell(6, 'Total Belanja', self::STYLE_TH_RIGHT),
                $this->strCell(7, 'Deviasi vs Estimasi', self::STYLE_TH_RIGHT),
            ],
        ];

        $daftarBeli = $data['pembelianPeriode']->isNotEmpty() ? $data['pembelianPeriode'] : $data['riwayatBelanjaTerbaru'];
        if ($daftarBeli->isNotEmpty()) {
            foreach ($daftarBeli as $idx => $p) {
                $z = $idx % 2 === 1;
                $totalBeli = (int) round($p->harga_nyata * $p->kg);
                $sel = (int) round($p->selisih());

                $rows[] = [
                    'r' => $r++,
                    'ht' => 20,
                    'cells' => [
                        $this->numCell(1, $idx + 1, $z ? self::STYLE_TD_CENTER_ZEBRA : self::STYLE_TD_CENTER),
                        $this->strCell(2, $p->tanggal->format('d/m/Y'), $z ? self::STYLE_TD_CENTER_ZEBRA : self::STYLE_TD_CENTER),
                        $this->strCell(3, $p->ingredient?->nama ?? '-', $z ? self::STYLE_TD_LEFT_ZEBRA : self::STYLE_TD_LEFT),
                        $this->numCell(4, (float) $p->kg, $z ? self::STYLE_TD_DEC1_ZEBRA : self::STYLE_TD_DEC1),
                        $this->numCell(5, (int) $p->harga_nyata, $z ? self::STYLE_TD_RP_ZEBRA : self::STYLE_TD_RP),
                        $this->numCell(6, $totalBeli, $z ? self::STYLE_TD_RP_ZEBRA : self::STYLE_TD_RP),
                        $this->numCell(7, $sel, $z ? self::STYLE_TD_RP_ZEBRA : self::STYLE_TD_RP),
                    ],
                ];
            }

            $rows[] = [
                'r' => $r++,
                'ht' => 22,
                'cells' => [
                    $this->strCell(1, '', self::STYLE_TOTAL_LABEL),
                    $this->strCell(2, 'TOTAL PERIODE', self::STYLE_TOTAL_LABEL),
                    $this->strCell(3, $belanja['jumlah_transaksi'].' Transaksi', self::STYLE_TOTAL_LABEL),
                    $this->numCell(4, (float) $belanja['total_volume_kg'], self::STYLE_TOTAL_DEC1),
                    $this->strCell(5, '-', self::STYLE_TOTAL_LABEL),
                    $this->numCell(6, (int) $belanja['total_pengeluaran_rp'], self::STYLE_TOTAL_RP),
                    $this->numCell(7, (int) $belanja['total_selisih_rp'], self::STYLE_TOTAL_RP),
                ],
            ];
        } else {
            $rows[] = $this->bannerRow($r, 'Belum ada transaksi pembelian pakan yang tercatat.', 7, self::STYLE_TD_CENTER, 20);
            $merges[] = "A{$r}:G{$r}";
        }

        return [
            'name' => '4. Stok & Belanja',
            'cols' => [7, 24, 20, 16, 18, 20, 20],
            'rows' => $rows,
            'merges' => $merges,
            'freezeRow' => 0,
        ];
    }

    /**
     * Sheet 5: Mikroklimat IoT, Smart Controller, Peringatan Dini Anomali & Aktivitas AI.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function buildSheetIotAnomaliAi(array $data): array
    {
        $cuaca = $data['cuacaMikro'];
        $iot = $data['ringkasanIot'];
        $set = $data['kontrolerSetting'];
        $anomali = $data['ringkasanAnomali'];
        $ai = $data['ringkasanAi'];

        $rows = [];
        $merges = [];

        $rows[] = $this->bannerRow(1, 'LAPORAN MIKROKLIMAT KANDANG (THI), SMART CONTROLLER IOT & DETEKSI ANOMALI', 5, self::STYLE_TITLE, 26);
        $merges[] = 'A1:E1';

        $sumber = $iot['sensor_live'] ? 'Sensor IoT ESP32/DHT22 Live' : 'Cuaca Mikro '.($cuaca['kota'] ?? 'Blitar');
        $rows[] = $this->bannerRow(2, 'Sumber Telemetri: '.$sumber.' · Suhu '.Fmt::num($cuaca['suhu'] ?? 0, 1).' °C · RH '.Fmt::num($cuaca['kelembaban'] ?? 0, 0).'% · Indeks THI '.Fmt::num($cuaca['thi'] ?? 0, 1).' ('.($cuaca['thi_kategori'] ?? 'Normal').')', 5, self::STYLE_SUBTITLE, 20);
        $merges[] = 'A2:E2';

        $r = 4;
        $rows[] = $this->bannerRow($r, 'I. STATUS AKTUATOR & KONFIGURASI SMART CONTROLLER IOT', 5, self::STYLE_SECTION, 22);
        $merges[] = "A{$r}:E{$r}";
        $r++;

        $rows[] = [
            'r' => $r++,
            'ht' => 22,
            'cells' => [
                $this->strCell(1, 'Perangkat Aktuator', self::STYLE_TH_LEFT),
                $this->strCell(2, 'Mode Kendali', self::STYLE_TH_CENTER),
                $this->strCell(3, 'Ambang Batas / Jadwal Otomasi', self::STYLE_TH_LEFT),
                $this->strCell(4, 'Koneksi Hardware', self::STYLE_TH_CENTER),
                $this->strCell(5, 'Status Relay', self::STYLE_TH_CENTER),
            ],
        ];

        $aktuatorRows = [
            ['Kipas Exhaust / Blower', strtoupper((string) $iot['mode_kendali']), 'Aktif saat Suhu >= '.$set['kipas']['suhu_nyala'].' °C atau THI >= '.$set['kipas']['thi_nyala'], $iot['controller_siap'] ? 'Terhubung' : 'Simulasi', $iot['relay_kipas'] ? 'MENYALA' : 'MATI'],
            ['Pompa Cooling / Misting', strtoupper((string) $iot['mode_kendali']), 'Aktif saat THI >= '.$set['misting']['thi_nyala'].' & RH <= '.$set['misting']['rh_maksimal'].'%', $iot['controller_siap'] ? 'Terhubung' : 'Simulasi', $iot['relay_misting'] ? 'MENYALA' : 'MATI'],
            ['Lampu Pencahayaan Layer', strtoupper((string) $iot['mode_kendali']), 'Pagi '.$set['lampu']['jam_pagi_mulai'].'-'.$set['lampu']['jam_pagi_selesai'].' & Sore '.$set['lampu']['jam_sore_mulai'].'-'.$set['lampu']['jam_sore_selesai'], $iot['controller_siap'] ? 'Terhubung' : 'Simulasi', $iot['relay_lampu'] ? 'MENYALA' : 'MATI'],
            ['Shuttle Feeder Pakan', strtoupper((string) $iot['mode_kendali']), 'Jadwal '.$set['pakan']['jam_pagi'].' & '.$set['pakan']['jam_sore'].' (Durasi '.$set['pakan']['durasi_detik'].' detik)', $iot['controller_siap'] ? 'Terhubung' : 'Simulasi', $iot['relay_pakan'] ? 'BERJALAN' : 'STANDBY'],
        ];

        foreach ($aktuatorRows as $idx => $ar) {
            $z = $idx % 2 === 1;
            $rows[] = [
                'r' => $r++,
                'ht' => 20,
                'cells' => [
                    $this->strCell(1, $ar[0], $z ? self::STYLE_TD_LEFT_ZEBRA : self::STYLE_TD_LEFT),
                    $this->strCell(2, $ar[1], $z ? self::STYLE_TD_CENTER_ZEBRA : self::STYLE_TD_CENTER),
                    $this->strCell(3, $ar[2], $z ? self::STYLE_TD_LEFT_ZEBRA : self::STYLE_TD_LEFT),
                    $this->strCell(4, $ar[3], $z ? self::STYLE_TD_CENTER_ZEBRA : self::STYLE_TD_CENTER),
                    $this->strCell(5, $ar[4], self::STYLE_BADGE_OK),
                ],
            ];
        }

        $r++;
        $rows[] = $this->bannerRow($r, 'II. RIWAYAT DETEKSI DINI ANOMALI & KONSULTASI ASISTEN AI ('.$ai['total_sesi'].' Sesi / '.$ai['total_pesan'].' Pesan)', 5, self::STYLE_SECTION, 22);
        $merges[] = "A{$r}:E{$r}";
        $r++;

        $rows[] = [
            'r' => $r++,
            'ht' => 22,
            'cells' => [
                $this->strCell(1, 'Tanggal Data', self::STYLE_TH_CENTER),
                $this->strCell(2, 'Kategori Anomali', self::STYLE_TH_LEFT),
                $this->strCell(3, 'Rincian Temuan Statistik', self::STYLE_TH_LEFT),
                $this->strCell(4, 'Tingkat Risiko', self::STYLE_TH_CENTER),
                $this->strCell(5, 'Status Tinjauan', self::STYLE_TH_CENTER),
            ],
        ];

        if ($data['daftarAnomali']->isNotEmpty()) {
            foreach ($data['daftarAnomali'] as $idx => $al) {
                $z = $idx % 2 === 1;
                $judul = match ($al->jenis) {
                    'pakan_naik_telur_turun' => 'Anomali Pakan & Penurunan Telur',
                    'lonjakan_kematian' => 'Lonjakan Kematian Ayam',
                    'biaya_per_kg_telur_naik' => 'Lonjakan Biaya Pakan / Kg Telur',
                    'cuaca_ekstrem', 'heat_stress' => 'Risiko Heat Stress Kandang',
                    default => ucwords(str_replace('_', ' ', (string) $al->jenis)),
                };

                $rows[] = [
                    'r' => $r++,
                    'ht' => 22,
                    'cells' => [
                        $this->strCell(1, $al->tanggal_data?->format('d/m/Y') ?? '-', $z ? self::STYLE_TD_CENTER_ZEBRA : self::STYLE_TD_CENTER),
                        $this->strCell(2, $judul, $z ? self::STYLE_TD_LEFT_ZEBRA : self::STYLE_TD_LEFT),
                        $this->strCell(3, (string) $al->pesan, $z ? self::STYLE_TD_LEFT_ZEBRA : self::STYLE_TD_LEFT),
                        $this->strCell(4, strtoupper((string) $al->level), in_array($al->level, ['waspada', 'bahaya'], true) ? self::STYLE_BADGE_WARN : self::STYLE_TD_CENTER),
                        $this->strCell(5, $al->dibaca ? 'SUDAH DITINJAU' : 'BELUM DIBACA', $al->dibaca ? self::STYLE_BADGE_OK : self::STYLE_BADGE_WARN),
                    ],
                ];
            }
        } else {
            $rows[] = $this->bannerRow($r, 'Tidak ada temuan anomali statistik pada kandang (Kondisi Terkendali).', 5, self::STYLE_TD_CENTER, 20);
            $merges[] = "A{$r}:E{$r}";
        }

        return [
            'name' => '5. IoT, Anomali & AI',
            'cols' => [16, 28, 52, 18, 18],
            'rows' => $rows,
            'merges' => $merges,
            'freezeRow' => 0,
        ];
    }

    /**
     * Membuat baris judul / banner dengan pengisian gaya seragam sepanjang kolom yang di-merge.
     *
     * @return array{r: int, ht: int, cells: array<int, array<string, mixed>>}
     */
    private function bannerRow(int $rowNum, string $text, int $colSpan, int $style, int $height = 24): array
    {
        $cells = [$this->strCell(1, $text, $style)];
        for ($c = 2; $c <= $colSpan; $c++) {
            $cells[] = $this->strCell($c, '', $style);
        }

        return ['r' => $rowNum, 'ht' => $height, 'cells' => $cells];
    }

    /**
     * @return array{col: int, type: string, val: string, style: int}
     */
    private function strCell(int $col, string $val, int $style): array
    {
        return ['col' => $col, 'type' => 's', 'val' => $val, 'style' => $style];
    }

    /**
     * @return array{col: int, type: string, val: int|float, style: int}
     */
    private function numCell(int $col, int|float $val, int $style): array
    {
        return ['col' => $col, 'type' => 'n', 'val' => $val, 'style' => $style];
    }

    /**
     * Menyusun XML sebuah worksheet sesuai urutan skema ECMA-376 SpreadsheetML.
     *
     * @param  array<string, mixed>  $sheet
     */
    private function buildWorksheetXml(array $sheet): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';
        $xml .= '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">';

        // 1. sheetViews (opsional freeze header)
        $freezeRow = (int) ($sheet['freezeRow'] ?? 0);
        if ($freezeRow > 0) {
            $topLeft = 'A'.($freezeRow + 1);
            $xml .= '<sheetViews><sheetView workbookViewId="0"><pane ySplit="'.$freezeRow.'" topLeftCell="'.$topLeft.'" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews>';
        } else {
            $xml .= '<sheetViews><sheetView workbookViewId="0" showGridLines="1"/></sheetViews>';
        }

        // 2. cols
        $xml .= '<cols>';
        foreach ($sheet['cols'] as $idx => $width) {
            $colNum = $idx + 1;
            $xml .= '<col min="'.$colNum.'" max="'.$colNum.'" width="'.$width.'" customWidth="1"/>';
        }
        $xml .= '</cols>';

        // 3. sheetData
        $xml .= '<sheetData>';
        foreach ($sheet['rows'] as $row) {
            $rNum = (int) $row['r'];
            $ht = (int) ($row['ht'] ?? 20);
            $xml .= '<row r="'.$rNum.'" ht="'.$ht.'" customHeight="1">';
            foreach ($row['cells'] as $cell) {
                $ref = $this->colLetter((int) $cell['col']).$rNum;
                $style = (int) $cell['style'];
                if ($cell['type'] === 'n') {
                    $numStr = is_int($cell['val']) ? (string) $cell['val'] : rtrim(rtrim(number_format((float) $cell['val'], 4, '.', ''), '0'), '.');
                    if ($numStr === '' || $numStr === '-') {
                        $numStr = '0';
                    }
                    $xml .= '<c r="'.$ref.'" s="'.$style.'"><v>'.$numStr.'</v></c>';
                } else {
                    $escaped = $this->xmlEscape((string) $cell['val']);
                    $xml .= '<c r="'.$ref.'" s="'.$style.'" t="inlineStr"><is><t xml:space="preserve">'.$escaped.'</t></is></c>';
                }
            }
            $xml .= '</row>';
        }
        $xml .= '</sheetData>';

        // 4. mergeCells
        if (! empty($sheet['merges'])) {
            $xml .= '<mergeCells count="'.count($sheet['merges']).'">';
            foreach ($sheet['merges'] as $mRef) {
                $xml .= '<mergeCell ref="'.$mRef.'"/>';
            }
            $xml .= '</mergeCells>';
        }

        // 5. pageMargins & pageSetup
        $xml .= '<pageMargins left="0.4" right="0.4" top="0.5" bottom="0.5" header="0.25" footer="0.25"/>';
        $xml .= '<pageSetup paperSize="9" orientation="landscape" fitToWidth="1" fitToHeight="0"/>';
        $xml .= '</worksheet>';

        return $xml;
    }

    private function buildContentTypesXml(int $sheetCount): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';
        $xml .= '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">';
        $xml .= '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>';
        $xml .= '<Default Extension="xml" ContentType="application/xml"/>';
        $xml .= '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>';
        $xml .= '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>';
        for ($i = 1; $i <= $sheetCount; $i++) {
            $xml .= '<Override PartName="/xl/worksheets/sheet'.$i.'.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
        }
        $xml .= '</Types>';

        return $xml;
    }

    private function buildRootRelsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            .'</Relationships>';
    }

    /**
     * @param  array<int, array<string, mixed>>  $sheets
     */
    private function buildWorkbookXml(array $sheets): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';
        $xml .= '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">';
        $xml .= '<sheets>';
        foreach ($sheets as $idx => $sheet) {
            $id = $idx + 1;
            $name = $this->xmlEscape(mb_substr((string) $sheet['name'], 0, 31));
            $xml .= '<sheet name="'.$name.'" sheetId="'.$id.'" r:id="rId'.$id.'"/>';
        }
        $xml .= '</sheets>';
        $xml .= '</workbook>';

        return $xml;
    }

    private function buildWorkbookRelsXml(int $sheetCount): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';
        $xml .= '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">';
        for ($i = 1; $i <= $sheetCount; $i++) {
            $xml .= '<Relationship Id="rId'.$i.'" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet'.$i.'.xml"/>';
        }
        $styleRelId = $sheetCount + 1;
        $xml .= '<Relationship Id="rId'.$styleRelId.'" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>';
        $xml .= '</Relationships>';

        return $xml;
    }

    /**
     * Stylesheet SpreadsheetML dengan tipografi modern, warna emerald RansumKu, format Rupiah, dan border tabel rapi.
     */
    private function buildStylesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<numFmts count="4">'
            .'<numFmt numFmtId="164" formatCode="&quot;Rp &quot;#,##0;-&quot;Rp &quot;#,##0;&quot;Rp &quot;0"/>'
            .'<numFmt numFmtId="165" formatCode="#,##0.0"/>'
            .'<numFmt numFmtId="166" formatCode="#,##0.00"/>'
            .'<numFmt numFmtId="167" formatCode="#,##0"/>'
            .'</numFmts>'
            .'<fonts count="8">'
            .'<font><sz val="10"/><color rgb="FF0F172A"/><name val="Calibri"/></font>'
            .'<font><b/><sz val="13"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font>'
            .'<font><i/><sz val="9.5"/><color rgb="FF334155"/><name val="Calibri"/></font>'
            .'<font><b/><sz val="10.5"/><color rgb="FF0F3D26"/><name val="Calibri"/></font>'
            .'<font><b/><sz val="10"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font>'
            .'<font><b/><sz val="10"/><color rgb="FF0F172A"/><name val="Calibri"/></font>'
            .'<font><b/><sz val="9.5"/><color rgb="FF116A3A"/><name val="Calibri"/></font>'
            .'<font><b/><sz val="9.5"/><color rgb="FFBE123C"/><name val="Calibri"/></font>'
            .'</fonts>'
            .'<fills count="8">'
            .'<fill><patternFill patternType="none"/></fill>'
            .'<fill><patternFill patternType="gray125"/></fill>'
            .'<fill><patternFill patternType="solid"><fgColor rgb="FF0F3D26"/></patternFill></fill>'
            .'<fill><patternFill patternType="solid"><fgColor rgb="FFEAF3EE"/></patternFill></fill>'
            .'<fill><patternFill patternType="solid"><fgColor rgb="FF186B3E"/></patternFill></fill>'
            .'<fill><patternFill patternType="solid"><fgColor rgb="FFF8FAFC"/></patternFill></fill>'
            .'<fill><patternFill patternType="solid"><fgColor rgb="FFECFDF5"/></patternFill></fill>'
            .'<fill><patternFill patternType="solid"><fgColor rgb="FFFFF1F2"/></patternFill></fill>'
            .'</fills>'
            .'<borders count="3">'
            .'<border><left/><right/><top/><bottom/><diagonal/></border>'
            .'<border>'
            .'<left style="thin"><color rgb="FFCBD5E1"/></left>'
            .'<right style="thin"><color rgb="FFCBD5E1"/></right>'
            .'<top style="thin"><color rgb="FFCBD5E1"/></top>'
            .'<bottom style="thin"><color rgb="FFCBD5E1"/></bottom>'
            .'<diagonal/>'
            .'</border>'
            .'<border>'
            .'<left style="thin"><color rgb="FF94A3B8"/></left>'
            .'<right style="thin"><color rgb="FF94A3B8"/></right>'
            .'<top style="medium"><color rgb="FF0F3D26"/></top>'
            .'<bottom style="double"><color rgb="FF0F3D26"/></bottom>'
            .'<diagonal/>'
            .'</border>'
            .'</borders>'
            .'<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            .'<cellXfs count="29">'
            // 0: DEFAULT
            .'<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
            // 1: TITLE
            .'<xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1" applyAlignment="1"><alignment horizontal="left" vertical="center" indent="1"/></xf>'
            // 2: SUBTITLE
            .'<xf numFmtId="0" fontId="2" fillId="3" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="left" vertical="center" indent="1"/></xf>'
            // 3: SECTION
            .'<xf numFmtId="0" fontId="3" fillId="3" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="left" vertical="center"/></xf>'
            // 4..6: TH LEFT, CENTER, RIGHT
            .'<xf numFmtId="0" fontId="4" fillId="4" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="left" vertical="center"/></xf>'
            .'<xf numFmtId="0" fontId="4" fillId="4" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>'
            .'<xf numFmtId="0" fontId="4" fillId="4" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="right" vertical="center"/></xf>'
            // 7..13: TD white (LEFT, CENTER, RIGHT, INT, DEC1, DEC2, RP)
            .'<xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyBorder="1" applyAlignment="1"><alignment horizontal="left" vertical="center" wrapText="1"/></xf>'
            .'<xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>'
            .'<xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyBorder="1" applyAlignment="1"><alignment horizontal="right" vertical="center"/></xf>'
            .'<xf numFmtId="167" fontId="0" fillId="0" borderId="1" xfId="0" applyNumberFormat="1" applyBorder="1" applyAlignment="1"><alignment horizontal="right" vertical="center"/></xf>'
            .'<xf numFmtId="165" fontId="0" fillId="0" borderId="1" xfId="0" applyNumberFormat="1" applyBorder="1" applyAlignment="1"><alignment horizontal="right" vertical="center"/></xf>'
            .'<xf numFmtId="166" fontId="0" fillId="0" borderId="1" xfId="0" applyNumberFormat="1" applyBorder="1" applyAlignment="1"><alignment horizontal="right" vertical="center"/></xf>'
            .'<xf numFmtId="164" fontId="0" fillId="0" borderId="1" xfId="0" applyNumberFormat="1" applyBorder="1" applyAlignment="1"><alignment horizontal="right" vertical="center"/></xf>'
            // 14..19: TD zebra (LEFT, CENTER, INT, DEC1, DEC2, RP)
            .'<xf numFmtId="0" fontId="0" fillId="5" borderId="1" xfId="0" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="left" vertical="center" wrapText="1"/></xf>'
            .'<xf numFmtId="0" fontId="0" fillId="5" borderId="1" xfId="0" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>'
            .'<xf numFmtId="167" fontId="0" fillId="5" borderId="1" xfId="0" applyNumberFormat="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="right" vertical="center"/></xf>'
            .'<xf numFmtId="165" fontId="0" fillId="5" borderId="1" xfId="0" applyNumberFormat="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="right" vertical="center"/></xf>'
            .'<xf numFmtId="166" fontId="0" fillId="5" borderId="1" xfId="0" applyNumberFormat="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="right" vertical="center"/></xf>'
            .'<xf numFmtId="164" fontId="0" fillId="5" borderId="1" xfId="0" applyNumberFormat="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="right" vertical="center"/></xf>'
            // 20..24: TOTAL ROW (LABEL, INT, DEC1, DEC2, RP)
            .'<xf numFmtId="0" fontId="3" fillId="3" borderId="2" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="left" vertical="center"/></xf>'
            .'<xf numFmtId="167" fontId="3" fillId="3" borderId="2" xfId="0" applyNumberFormat="1" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="right" vertical="center"/></xf>'
            .'<xf numFmtId="165" fontId="3" fillId="3" borderId="2" xfId="0" applyNumberFormat="1" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="right" vertical="center"/></xf>'
            .'<xf numFmtId="166" fontId="3" fillId="3" borderId="2" xfId="0" applyNumberFormat="1" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="right" vertical="center"/></xf>'
            .'<xf numFmtId="164" fontId="3" fillId="3" borderId="2" xfId="0" applyNumberFormat="1" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="right" vertical="center"/></xf>'
            // 25..26: META KEY & META VAL
            .'<xf numFmtId="0" fontId="5" fillId="5" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="left" vertical="center"/></xf>'
            .'<xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyBorder="1" applyAlignment="1"><alignment horizontal="left" vertical="center"/></xf>'
            // 27..28: BADGE OK & WARN
            .'<xf numFmtId="0" fontId="6" fillId="6" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>'
            .'<xf numFmtId="0" fontId="7" fillId="7" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>'
            .'</cellXfs>'
            .'</styleSheet>';
    }

    private function colLetter(int $col): string
    {
        $letter = '';
        while ($col > 0) {
            $mod = ($col - 1) % 26;
            $letter = chr(65 + $mod).$letter;
            $col = intdiv($col - 1, 26);
        }

        return $letter;
    }

    private function xmlEscape(string $str): string
    {
        $clean = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $str) ?? '';

        return htmlspecialchars($clean, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    /**
     * Membungkus kumpulan berkas XML menjadi arsip ZIP biner (.xlsx).
     *
     * @param  array<string, string>  $files
     */
    private function packZipArchive(array $files): string
    {
        if (class_exists(ZipArchive::class)) {
            $tmpFile = tempnam(sys_get_temp_dir(), 'rk_xlsx_');
            if ($tmpFile !== false) {
                $zip = new ZipArchive;
                if ($zip->open($tmpFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
                    foreach ($files as $name => $content) {
                        $zip->addFromString($name, $content);
                    }
                    $zip->close();
                    $binary = file_get_contents($tmpFile);
                    @unlink($tmpFile);
                    if (is_string($binary) && $binary !== '') {
                        return $binary;
                    }
                }
                @unlink($tmpFile);
            }
        }

        return $this->packPurePhpZip($files);
    }

    /**
     * Fallback pembuat arsip ZIP murni PHP (tanpa ekstensi eksternal) bila ZipArchive tidak tersedia.
     *
     * @param  array<string, string>  $files
     */
    private function packPurePhpZip(array $files): string
    {
        if (! function_exists('gzdeflate')) {
            throw new RuntimeException('Ekstensi zip atau zlib diperlukan untuk membuat file Excel.');
        }

        $dataSection = '';
        $centralDir = '';
        $offset = 0;
        $count = 0;

        foreach ($files as $name => $raw) {
            $compressed = gzdeflate($raw, 6);
            if ($compressed === false) {
                $compressed = $raw;
                $method = 0;
            } else {
                $method = 8;
            }

            $crc = crc32($raw);
            $compLen = strlen($compressed);
            $rawLen = strlen($raw);
            $nameLen = strlen($name);

            $localHeader = pack('VvvvvvVVVvv', 0x04034B50, 20, 0, $method, 0, 0, $crc, $compLen, $rawLen, $nameLen, 0).$name;
            $dataSection .= $localHeader.$compressed;

            $centralDir .= pack('VvvvvvvVVVvvvvvVV', 0x02014B50, 20, 20, 0, $method, 0, 0, $crc, $compLen, $rawLen, $nameLen, 0, 0, 0, 0, 32, $offset).$name;

            $offset += strlen($localHeader) + $compLen;
            $count++;
        }

        $eocd = pack('VvvvvVVv', 0x06054B50, 0, 0, $count, $count, strlen($centralDir), strlen($dataSection), 0);

        return $dataSection.$centralDir.$eocd;
    }
}

