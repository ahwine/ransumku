<?php

namespace App\Services;

use App\Support\Fmt;

class PdfReportService
{
    private const PAGE_W = 595.28;

    private const PAGE_H = 841.89;

    private const MARGIN_X = 36.0;

    private const CONTENT_W = 523.28;

    private const MAX_Y = 788.0;

    /** @var array<int, string> */
    private array $pages = [];

    private int $currentPage = -1;

    private float $y = 36.0;

    private string $docTitle = '';

    private string $docRef = '';

    /**
     * Menghasilkan biner dokumen PDF A4 Portrait resmi yang merangkum seluruh modul sistem RansumKu.
     *
     * @param  array<string, mixed>  $data
     */
    public function generateSystemReportPdf(array $data): string
    {
        $this->pages = [];
        $this->currentPage = -1;

        $farm = $data['farm'];
        $ini = $data['laporan']['ini'];
        $lalu = $data['laporan']['lalu'];
        $jumlahAyam = (int) $data['jumlahAyam'];
        $hargaPakan = (float) $data['harga'];
        $hargaTelur = (int) $data['hargaTelurAcuan'];
        $ransumAktif = $data['ransumAktif'];
        $gudang = $data['ringkasanGudang'];
        $belanja = $data['ringkasanBelanja'];
        $cuaca = $data['cuacaMikro'];
        $iot = $data['ringkasanIot'];
        $set = $data['kontrolerSetting'];
        $anomali = $data['ringkasanAnomali'];
        $ai = $data['ringkasanAi'];

        $this->docTitle = 'Laporan Sistem Terpadu — '.$farm->nama;
        $this->docRef = 'LP-'.$farm->id.'-'.now()->format('Ymd');

        $this->addPage(true);

        // 1. KOP SURAT RESMI RANSUMKU
        $this->drawCoverHeader($data);

        // 2. KOTAK METADATA KANDANG
        $this->drawFarmMetadataBox($data);

        // 3. BAGIAN I: RINGKASAN EKSEKUTIF PRODUKSI & KEUANGAN
        $this->drawSectionHeading('I. RINGKASAN EKSEKUTIF PRODUKSI & NERACA KEUANGAN');
        $this->drawTable(
            ['Komponen Keuangan & Produksi', 'Periode Ini', 'Periode Lalu', 'Acuan / Keterangan Teknis'],
            [
                ['Total Produksi Telur', Fmt::num($ini['telur'], 0).' btr ('.Fmt::num($ini['telur_kg'], 1).' kg)', Fmt::num($lalu['telur'] ?? 0, 0).' btr', 'Rata-rata '.Fmt::num(round($ini['rata_telur']), 0).' butir/hari'],
                ['Hen Day Production (HDP) & FCR', 'HDP '.Fmt::num($ini['hdp_rata'], 1).'%  |  FCR '.Fmt::num($ini['fcr_rata'], 2), 'HDP '.Fmt::num($lalu['hdp_rata'] ?? 0, 1).'%', 'Target fase: HDP '.$data['faseDetail']['standar_hdp']],
                ['Total Konsumsi Pakan & Asupan', Fmt::num($ini['pakan'], 1).' kg ('.Fmt::num($ini['gram_per_ekor'], 1).' g/ekor)', Fmt::num($lalu['pakan'] ?? 0, 1).' kg', 'Standar asupan: '.$data['faseDetail']['standar_pakan']],
                ['Mortalitas / Kematian Ayam', $ini['mati'].' ekor', ($lalu['mati'] ?? 0).' ekor', 'Populasi aktif '.number_format($jumlahAyam, 0, ',', '.').' ekor'],
                ['Estimasi Omzet Penjualan Telur', Fmt::rp($ini['pendapatan_telur']), Fmt::rp($lalu['pendapatan_telur'] ?? 0), 'Harga telur acuan '.Fmt::rp($hargaTelur).'/kg'],
                ['Estimasi Biaya Konsumsi Pakan', Fmt::rp($ini['biaya_pakan']), Fmt::rp($lalu['biaya_pakan'] ?? 0), 'Harga pakan '.Fmt::rp($hargaPakan).'/kg (HPP '.Fmt::rp($ini['biaya_kg_telur']).'/kg telur)'],
                ['Biaya Operasional Lainnya', Fmt::rp($ini['biaya_lain']), Fmt::rp($lalu['biaya_lain'] ?? 0), 'Vitamin, vaksin, listrik & perawatan kandang'],
            ],
            [155.0, 118.0, 85.0, 165.28],
            ['L', 'R', 'R', 'L'],
            [
                'ESTIMASI MARGIN BERSIH OPERASIONAL',
                ($ini['margin_bersih'] >= 0 ? '+' : '-').Fmt::rp(abs($ini['margin_bersih'])),
                (($lalu['margin_bersih'] ?? 0) >= 0 ? '+' : '-').Fmt::rp(abs($lalu['margin_bersih'] ?? 0)),
                'Margin Kotor (IOFC): '.($ini['margin_kotor'] >= 0 ? '+' : '-').Fmt::rp(abs($ini['margin_kotor'])),
            ]
        );

        // 4. BAGIAN II: FORMULASI RANSUM & KEPATUHAN GIZI SNI
        $this->drawSectionHeading('II. LAPORAN FORMULASI RANSUM AKTIF & KEPATUHAN GIZI (SNI / STRAIN)');
        if ($ransumAktif && $ransumAktif->items->isNotEmpty()) {
            $this->drawSubNote(
                'Acuan Aktif: '.$ransumAktif->namaStandar()
                .'  |  Biaya Ransum: '.Fmt::rp($ransumAktif->biaya_per_kg).'/kg'
                .'  |  Volume Batch: '.Fmt::num($ransumAktif->total_kg, 1).' kg ('.Fmt::rp($ransumAktif->total_biaya).')'
                .'  |  Riwayat: '.$data['totalRiwayatRansum'].' resep'
            );

            $ransumRows = [];
            foreach ($ransumAktif->items as $idx => $it) {
                $ransumRows[] = [
                    (string) ($idx + 1),
                    (string) $it->nama,
                    Fmt::num($it->persen, 1).'%',
                    Fmt::num($it->kg_as_fed ?? $it->kg, 2).' kg',
                    Fmt::rp($it->harga_saat_hitung),
                    Fmt::rp($it->biaya),
                ];
            }

            $this->drawTable(
                ['No', 'Bahan Baku Penyusun', 'Porsi BK', 'Takaran Batch', 'Harga / kg', 'Subtotal Biaya'],
                $ransumRows,
                [26.0, 175.28, 68.0, 84.0, 82.0, 88.0],
                ['C', 'L', 'R', 'R', 'R', 'R'],
                ['', 'TOTAL FORMULASI RANSUM', '100,0%', Fmt::num($ransumAktif->total_kg, 2).' kg', Fmt::rp($ransumAktif->biaya_per_kg).'/kg', Fmt::rp($ransumAktif->total_biaya)]
            );

            $giziList = $ransumAktif->hasil['gizi'] ?? [];
            if (! empty($giziList)) {
                $giziRows = [];
                foreach ($giziList as $g) {
                    $giziRows[] = [
                        (string) $g['label'],
                        Fmt::num($g['nilai'], 2).$g['satuan'],
                        (string) $g['target'],
                        ! empty($g['ok']) ? 'TERPENUHI' : 'EVALUASI',
                    ];
                }
                $this->drawTable(
                    ['Parameter Nutrisi Ransum', 'Kandungan Aktual', 'Target Standar Acuan', 'Status'],
                    $giziRows,
                    [165.0, 110.0, 168.28, 80.0],
                    ['L', 'R', 'L', 'C']
                );
            }
        } else {
            $this->drawSubNote('Belum ada resep formulasi ransum aktif yang disusun pada kandang ini.');
        }

        // 5. BAGIAN III: INVENTARIS STOK GUDANG & AUDIT BELANJA
        $this->drawSectionHeading('III. INVENTARIS GUDANG BAHAN BAKU & AUDIT BELANJA PAKAN');
        $this->drawSubNote(
            'Total Stok: '.Fmt::num($gudang['total_stok_kg'], 1).' kg'
            .'  |  Nilai Aset Gudang: '.Fmt::rp($gudang['total_nilai_stok_rp'])
            .'  |  Ketahanan Pakan: ~'.Fmt::num($gudang['estimasi_ketahanan_hari'], 1).' hari'
            .'  |  Belanja Periode: '.Fmt::rp($belanja['total_pengeluaran_rp']).' ('.$belanja['jumlah_transaksi'].' transaksi)'
        );

        $stokRows = [];
        foreach ($data['stokGudang'] as $idx => $fi) {
            $menipis = (float) $fi->stok_kg < 50;
            $usang = $fi->hargaUsang();
            $stokRows[] = [
                (string) ($idx + 1),
                (string) ($fi->ingredient?->nama ?? '-'),
                Fmt::num($fi->stok_kg, 1).' kg',
                Fmt::rp($fi->harga),
                Fmt::rp((float) $fi->stok_kg * (float) $fi->harga),
                $menipis ? 'MENIPIS' : ($usang ? 'HARGA USANG' : 'AMAN'),
            ];
        }
        if (! empty($stokRows)) {
            $this->drawTable(
                ['No', 'Bahan Baku Aktif', 'Stok Gudang', 'Harga Acuan', 'Nilai Aset', 'Status'],
                $stokRows,
                [26.0, 169.28, 82.0, 82.0, 90.0, 74.0],
                ['C', 'L', 'R', 'R', 'R', 'C'],
                ['', 'TOTAL INVENTARIS GUDANG', Fmt::num($gudang['total_stok_kg'], 1).' kg', '-', Fmt::rp($gudang['total_nilai_stok_rp']), '~'.Fmt::num($gudang['estimasi_ketahanan_hari'], 1).' Hari']
            );
        }

        // 6. BAGIAN IV: MIKROKLIMAT IOT, SMART CONTROLLER, ANOMALI & AI
        $this->drawSectionHeading('IV. MIKROKLIMAT KANDANG (THI), SMART CONTROLLER IOT & DETEKSI ANOMALI');
        $sumberIot = $iot['sensor_live'] ? 'Sensor IoT ESP32 Live' : 'Cuaca Mikro '.($cuaca['kota'] ?? 'Blitar');
        $this->drawTable(
            ['Subsistem Pemantauan', 'Status / Pembacaan Aktual', 'Konfigurasi & Ambang Batas Sistem'],
            [
                ['Iklim Mikro & Stres Panas', 'Suhu '.Fmt::num($cuaca['suhu'] ?? 0, 1).' °C  |  RH '.Fmt::num($cuaca['kelembaban'] ?? 0, 0).'%  |  THI '.Fmt::num($cuaca['thi'] ?? 0, 1), ($cuaca['thi_kategori'] ?? 'Normal').' (Sumber: '.$sumberIot.')'],
                ['Kipas Exhaust / Blower', ($iot['relay_kipas'] ? 'MENYALA' : 'MATI').' (Mode '.strtoupper((string) $iot['mode_kendali']).')', 'Otomatis menyala saat Suhu >= '.$set['kipas']['suhu_nyala'].' °C atau THI >= '.$set['kipas']['thi_nyala']],
                ['Pompa Cooling / Misting', ($iot['relay_misting'] ? 'MENYALA' : 'MATI').' (Mode '.strtoupper((string) $iot['mode_kendali']).')', 'Otomatis menyala saat THI >= '.$set['misting']['thi_nyala'].' & RH <= '.$set['misting']['rh_maksimal'].'%'],
                ['Lampu & Shuttle Feeder', 'Lampu '.($iot['relay_lampu'] ? 'ON' : 'OFF').'  |  Feeder '.($iot['relay_pakan'] ? 'ON' : 'STANDBY'), 'Feeder pukul '.$set['pakan']['jam_pagi'].' & '.$set['pakan']['jam_sore'].' ('.$set['pakan']['durasi_detik'].' dtk)'],
                ['Deteksi Dini Anomali & AI', $anomali['belum_dibaca'].' aktif dari '.$anomali['total_semua'].' temuan', 'Konsultasi Asisten AI: '.$ai['total_sesi'].' sesi diskusi ('.$ai['total_pesan'].' pesan)'],
            ],
            [135.0, 155.0, 233.28],
            ['L', 'L', 'L']
        );

        // 7. BAGIAN V: TABEL REKAPITULASI HARIAN BUKU KANDANG
        $this->drawSectionHeading('V. TABEL REKAPITULASI OPERASIONAL HARIAN BUKU KANDANG');
        $harianRows = [];
        foreach ($data['logs'] as $idx => $l) {
            $rKg = (float) $l->telurKgEfektif();
            $rHdp = $jumlahAyam > 0 ? ($l->telur_butir / $jumlahAyam) * 100 : 0.0;
            $rGr = $jumlahAyam > 0 ? ($l->pakan_kg * 1000) / $jumlahAyam : 0.0;
            $rFcr = $rKg > 0 ? $l->pakan_kg / $rKg : 0.0;
            $rBiaya = (int) round($l->pakan_kg * $hargaPakan);

            $harianRows[] = [
                (string) ($idx + 1),
                $l->tanggal->format('d/m/Y'),
                Fmt::num($l->pakan_kg, 1),
                (string) round($rGr),
                Fmt::num($l->telur_butir, 0),
                Fmt::num($rKg, 1),
                Fmt::num($rHdp, 1).'%',
                Fmt::num($rFcr, 2),
                $l->ayam_mati > 0 ? (string) $l->ayam_mati : '-',
                Fmt::rp($rBiaya),
                $l->catatan ? mb_strimwidth((string) $l->catatan, 0, 24, '...') : '-',
            ];
        }

        if (empty($harianRows)) {
            $harianRows[] = ['-', '-', '0,0', '0', '0', '0,0', '0,0%', '0,00', '-', 'Rp 0', 'Belum ada catatan periode ini'];
        }

        $this->drawTable(
            ['No', 'Tanggal', 'Pakan', 'g/Ek', 'Telur', 'Kg', 'HDP', 'FCR', 'Mati', 'Biaya Pakan', 'Catatan'],
            $harianRows,
            [22.0, 52.0, 38.0, 32.0, 40.0, 34.0, 38.0, 32.0, 28.0, 74.0, 133.28],
            ['C', 'C', 'R', 'R', 'R', 'R', 'R', 'R', 'C', 'R', 'L'],
            [
                '',
                'TOTAL / RATA',
                Fmt::num($ini['pakan'], 1),
                (string) round($ini['gram_per_ekor']),
                Fmt::num($ini['telur'], 0),
                Fmt::num($ini['telur_kg'], 1),
                Fmt::num($ini['hdp_rata'], 1).'%',
                Fmt::num($ini['fcr_rata'], 2),
                (string) $ini['mati'],
                Fmt::rp($ini['biaya_pakan']),
                $ini['hari'].' Hari Rekap',
            ]
        );

        // 8. BAGIAN VI: KESIMPULAN EVALUASI & LEMBAR PENGESAHAN
        $this->drawEvaluationAndSignatures($data);

        return $this->compilePdfDocument();
    }

    /**
     * Menggambar Kop Surat Resmi RansumKu pada halaman pertama.
     *
     * @param  array<string, mixed>  $data
     */
    private function drawCoverHeader(array $data): void
    {
        $x = self::MARGIN_X;
        $w = self::CONTENT_W;

        // Pita Hijau Tua Kop Atas
        $this->fillRect($x, $this->y, $w, 48.0, [15, 61, 38]);
        $this->writeText($x + 14.0, $this->y + 9.0, 'RANSUMKU  ·  SMART POULTRY FARM SYSTEM', 13.0, 'B', [255, 255, 255]);
        $this->writeText($x + 14.0, $this->y + 26.0, 'LAPORAN REKAPITULASI MENYELURUH SISTEM MANAJEMEN KANDANG LAYER', 8.5, 'B', [187, 247, 208]);

        $this->writeTextAligned($x + $w - 14.0, $this->y + 10.0, 'REF: '.$this->docRef, 8.5, 'B', [255, 255, 255], 'R');
        $this->writeTextAligned($x + $w - 14.0, $this->y + 25.0, 'Terbit: '.now()->translatedFormat('d M Y, H:i').' WIB', 7.8, 'R', [226, 232, 240], 'R');

        $this->y += 56.0;
    }

    /**
     * Menggambar kotak metadata identitas kandang.
     *
     * @param  array<string, mixed>  $data
     */
    private function drawFarmMetadataBox(array $data): void
    {
        $farm = $data['farm'];
        $x = self::MARGIN_X;
        $w = self::CONTENT_W;
        $h = 46.0;

        $this->fillStrokeRect($x, $this->y, $w, $h, [248, 250, 252], [203, 213, 225], 0.6);

        $colW = $w / 3.0;
        $items = [
            ['KANDANG & LOKASI', $farm->nama.' ('.($farm->lokasi_kota ?: 'Blitar').')', 'Fase '.$data['faseDetail']['nama']],
            ['POPULASI & BIBIT', number_format((int) $data['jumlahAyam'], 0, ',', '.').' Ekor  ·  Mg ke-'.$farm->umurMinggu(), 'Strain '.($farm->strain ?? 'Lohmann Brown')],
            ['PERIODE & PENANGGUNG JAWAB', $data['rentangLabel'].' ('.$data['laporan']['ini']['hari'].' hari)', (auth()->user()->name ?? 'Peternak').' (Supervisor)'],
        ];

        foreach ($items as $i => $it) {
            $cx = $x + ($i * $colW) + 10.0;
            if ($i > 0) {
                $this->drawLine($x + ($i * $colW), $this->y + 6.0, $x + ($i * $colW), $this->y + $h - 6.0, [203, 213, 225], 0.5);
            }
            $this->writeText($cx, $this->y + 6.5, $it[0], 6.8, 'B', [100, 116, 139]);
            $this->writeText($cx, $this->y + 18.0, mb_strimwidth($it[1], 0, 34, '...'), 8.8, 'B', [15, 23, 42]);
            $this->writeText($cx, $this->y + 31.0, mb_strimwidth($it[2], 0, 36, '...'), 7.6, 'R', [71, 85, 105]);
        }

        $this->y += $h + 10.0;
    }

    private function drawSectionHeading(string $title): void
    {
        $this->ensureSpace(38.0);
        $this->y += 3.0;
        $this->fillStrokeRect(self::MARGIN_X, $this->y, self::CONTENT_W, 18.0, [234, 243, 238], [187, 215, 198], 0.5);
        $this->fillRect(self::MARGIN_X, $this->y, 3.5, 18.0, [24, 107, 62]);
        $this->writeText(self::MARGIN_X + 9.0, $this->y + 4.5, $title, 8.2, 'B', [15, 61, 38]);
        $this->y += 22.0;
    }

    private function drawSubNote(string $text): void
    {
        $this->ensureSpace(16.0);
        $this->writeText(self::MARGIN_X + 2.0, $this->y, $text, 7.6, 'I', [71, 85, 105]);
        $this->y += 12.5;
    }

    /**
     * Menggambar tabel vektor rapi dengan auto-wrap teks dan pengulangan header otomatis saat pindah halaman.
     *
     * @param  array<int, string>  $headers
     * @param  array<int, array<int, string>>  $rows
     * @param  array<int, float>  $colWidths
     * @param  array<int, string>  $aligns
     * @param  array<int, string>|null  $footerRow
     */
    private function drawTable(array $headers, array $rows, array $colWidths, array $aligns, ?array $footerRow = null): void
    {
        $headerH = 17.0;
        $fontSize = 7.4;
        $lineH = 9.5;
        $padX = 4.5;
        $padY = 3.5;

        $drawHeader = function () use ($headers, $colWidths, $aligns, $headerH, $padX): void {
            $cx = self::MARGIN_X;
            $this->fillStrokeRect($cx, $this->y, self::CONTENT_W, $headerH, [24, 107, 62], [15, 61, 38], 0.5);
            foreach ($headers as $i => $hText) {
                $w = $colWidths[$i];
                if ($i > 0) {
                    $this->drawLine($cx, $this->y, $cx, $this->y + $headerH, [45, 130, 84], 0.4);
                }
                $align = $aligns[$i] ?? 'L';
                $tx = $align === 'R' ? ($cx + $w - $padX) : ($align === 'C' ? ($cx + $w / 2.0) : ($cx + $padX));
                $this->writeTextAligned($tx, $this->y + 4.5, $hText, 7.4, 'B', [255, 255, 255], $align);
                $cx += $w;
            }
            $this->y += $headerH;
        };

        $this->ensureSpace($headerH + 18.0);
        $drawHeader();

        foreach ($rows as $rIdx => $row) {
            $wrappedCols = [];
            $maxLines = 1;
            foreach ($row as $cIdx => $cellText) {
                $availW = max(12.0, $colWidths[$cIdx] - ($padX * 2.0));
                $lines = $this->wrapText((string) $cellText, $availW, $fontSize, false);
                $wrappedCols[$cIdx] = $lines;
                $maxLines = max($maxLines, count($lines));
            }

            $rowH = max(15.0, ($maxLines * $lineH) + ($padY * 2.0) - 1.5);
            if ($this->y + $rowH > self::MAX_Y) {
                $this->addPage();
                $drawHeader();
            }

            $bg = ($rIdx % 2 === 1) ? [248, 250, 252] : [255, 255, 255];
            $this->fillStrokeRect(self::MARGIN_X, $this->y, self::CONTENT_W, $rowH, $bg, [203, 213, 225], 0.4);

            $cx = self::MARGIN_X;
            foreach ($wrappedCols as $cIdx => $lines) {
                $w = $colWidths[$cIdx];
                if ($cIdx > 0) {
                    $this->drawLine($cx, $this->y, $cx, $this->y + $rowH, [226, 232, 240], 0.4);
                }
                $align = $aligns[$cIdx] ?? 'L';
                foreach ($lines as $lIdx => $lineStr) {
                    $ty = $this->y + $padY + ($lIdx * $lineH);
                    $tx = $align === 'R' ? ($cx + $w - $padX) : ($align === 'C' ? ($cx + $w / 2.0) : ($cx + $padX));
                    $this->writeTextAligned($tx, $ty, $lineStr, $fontSize, 'R', [15, 23, 42], $align);
                }
                $cx += $w;
            }
            $this->y += $rowH;
        }

        if ($footerRow !== null) {
            $footerH = 16.5;
            if ($this->y + $footerH > self::MAX_Y) {
                $this->addPage();
            }
            $this->fillStrokeRect(self::MARGIN_X, $this->y, self::CONTENT_W, $footerH, [234, 243, 238], [15, 61, 38], 0.6);
            $cx = self::MARGIN_X;
            foreach ($footerRow as $cIdx => $fText) {
                $w = $colWidths[$cIdx];
                if ($cIdx > 0) {
                    $this->drawLine($cx, $this->y, $cx, $this->y + $footerH, [187, 215, 198], 0.4);
                }
                $align = $aligns[$cIdx] ?? 'L';
                $tx = $align === 'R' ? ($cx + $w - $padX) : ($align === 'C' ? ($cx + $w / 2.0) : ($cx + $padX));
                $this->writeTextAligned($tx, $this->y + 4.2, (string) $fText, 7.3, 'B', [15, 61, 38], $align);
                $cx += $w;
            }
            $this->y += $footerH;
        }

        $this->y += 7.0;
    }

    /**
     * Menggambar kotak kesimpulan evaluasi teknis dan blok tanda tangan pengesahan resmi.
     *
     * @param  array<string, mixed>  $data
     */
    private function drawEvaluationAndSignatures(array $data): void
    {
        $this->ensureSpace(135.0);

        $farm = $data['farm'];
        $ini = $data['laporan']['ini'];
        $gudang = $data['ringkasanGudang'];
        $cuaca = $data['cuacaMikro'];

        if ($ini['hdp_rata'] >= 85) {
            $kesimpulan = 'Produktivitas kandang berada pada kategori SANGAT OPTIMAL (rata-rata HDP '.Fmt::num($ini['hdp_rata'], 1).'% dengan rasio FCR '.Fmt::num($ini['fcr_rata'], 2).'). Pertahankan konsistensi formulasi ransum berstandar SNI, ketersediaan stok bahan gudang, serta stabilitas suhu dan sirkulasi udara kandang.';
        } elseif ($ini['hdp_rata'] >= 70) {
            $kesimpulan = 'Produktivitas kandang berada pada kategori STABIL & TERKENDALI (rata-rata HDP '.Fmt::num($ini['hdp_rata'], 1).'%, FCR '.Fmt::num($ini['fcr_rata'], 2).'). Disarankan memantau bahan baku yang menipis ('.$gudang['stok_menipis_count'].' bahan) serta menjaga indeks stres panas (THI '.Fmt::num($cuaca['thi'] ?? 0, 1).') di zona nyaman.';
        } else {
            $kesimpulan = 'Produktivitas kandang memerlukan EVALUASI TEKNIS MENYELURUH (rata-rata HDP '.Fmt::num($ini['hdp_rata'], 1).'% di bawah target fase '.$data['faseDetail']['standar_hdp'].'). Segera tinjau kecukupan energi-protein ransum aktif, ketersediaan stok gudang, serta mitigasi faktor pemicu stres panas dan anomali harian.';
        }

        $lines = $this->wrapText($kesimpulan, self::CONTENT_W - 20.0, 7.8, false);
        $boxH = 22.0 + (count($lines) * 10.5);

        $this->fillStrokeRect(self::MARGIN_X, $this->y, self::CONTENT_W, $boxH, [248, 250, 252], [203, 213, 225], 0.5);
        $this->writeText(self::MARGIN_X + 10.0, $this->y + 6.0, 'KESIMPULAN EVALUASI SISTEM (UMUR MINGGU KE-'.$farm->umurMinggu().' · '.strtoupper((string) $data['faseDetail']['nama']).'):', 7.8, 'B', [15, 61, 38]);
        foreach ($lines as $idx => $ln) {
            $this->writeText(self::MARGIN_X + 10.0, $this->y + 18.0 + ($idx * 10.5), $ln, 7.8, 'R', [51, 65, 85]);
        }

        $this->y += $boxH + 14.0;

        // Blok Tanda Tangan 2 Kolom
        $leftCenter = self::MARGIN_X + 105.0;
        $rightCenter = self::MARGIN_X + self::CONTENT_W - 105.0;

        $this->writeTextAligned($leftCenter, $this->y, 'Dicatat & Disusun Oleh,', 7.8, 'R', [100, 116, 139], 'C');
        $this->writeTextAligned($leftCenter, $this->y + 11.0, 'Operator / Petugas Kandang', 8.5, 'B', [15, 23, 42], 'C');

        $this->writeTextAligned($rightCenter, $this->y, ($farm->lokasi_kota ?: 'Blitar').', '.now()->translatedFormat('d F Y'), 7.8, 'R', [100, 116, 139], 'C');
        $this->writeTextAligned($rightCenter, $this->y + 11.0, 'Pimpinan / Pemilik Peternakan', 8.5, 'B', [15, 23, 42], 'C');

        $lineY = $this->y + 56.0;
        $this->drawLine($leftCenter - 70.0, $lineY, $leftCenter + 70.0, $lineY, [100, 116, 139], 0.6);
        $this->drawLine($rightCenter - 70.0, $lineY, $rightCenter + 70.0, $lineY, [100, 116, 139], 0.6);

        $this->writeTextAligned($leftCenter, $lineY + 4.0, (string) (auth()->user()->name ?? 'Operator Kandang'), 8.2, 'B', [15, 23, 42], 'C');
        $this->writeTextAligned($leftCenter, $lineY + 14.0, 'Petugas Operasional Kandang', 7.2, 'R', [100, 116, 139], 'C');

        $this->writeTextAligned($rightCenter, $lineY + 4.0, 'Mengetahui & Menyetujui', 8.2, 'B', [15, 23, 42], 'C');
        $this->writeTextAligned($rightCenter, $lineY + 14.0, (string) $farm->nama, 7.2, 'R', [100, 116, 139], 'C');
    }

    private function addPage(bool $isFirst = false): void
    {
        $this->currentPage++;
        $this->pages[$this->currentPage] = '';
        $this->y = 32.0;

        if (! $isFirst) {
            $this->writeText(self::MARGIN_X, $this->y, $this->docTitle, 7.5, 'B', [100, 116, 139]);
            $this->writeTextAligned(self::MARGIN_X + self::CONTENT_W, $this->y, 'REF: '.$this->docRef, 7.5, 'R', [100, 116, 139], 'R');
            $this->drawLine(self::MARGIN_X, $this->y + 11.0, self::MARGIN_X + self::CONTENT_W, $this->y + 11.0, [203, 213, 225], 0.5);
            $this->y += 18.0;
        }
    }

    private function ensureSpace(float $neededHeight): void
    {
        if ($this->y + $neededHeight > self::MAX_Y) {
            $this->addPage();
        }
    }

    /**
     * @param  array{0: int, 1: int, 2: int}  $rgb
     */
    private function fillRect(float $x, float $topY, float $w, float $h, array $rgb): void
    {
        $py = self::PAGE_H - ($topY + $h);
        $this->appendCmd(sprintf(
            "%.3f %.3f %.3f rg %.2f %.2f %.2f %.2f re f\n",
            $rgb[0] / 255,
            $rgb[1] / 255,
            $rgb[2] / 255,
            $x,
            $py,
            $w,
            $h
        ));
    }

    /**
     * @param  array{0: int, 1: int, 2: int}  $fillRgb
     * @param  array{0: int, 1: int, 2: int}  $strokeRgb
     */
    private function fillStrokeRect(float $x, float $topY, float $w, float $h, array $fillRgb, array $strokeRgb, float $lineW = 0.5): void
    {
        $py = self::PAGE_H - ($topY + $h);
        $this->appendCmd(sprintf(
            "%.2f w %.3f %.3f %.3f rg %.3f %.3f %.3f RG %.2f %.2f %.2f %.2f re B\n",
            $lineW,
            $fillRgb[0] / 255,
            $fillRgb[1] / 255,
            $fillRgb[2] / 255,
            $strokeRgb[0] / 255,
            $strokeRgb[1] / 255,
            $strokeRgb[2] / 255,
            $x,
            $py,
            $w,
            $h
        ));
    }

    /**
     * @param  array{0: int, 1: int, 2: int}  $rgb
     */
    private function drawLine(float $x1, float $topY1, float $x2, float $topY2, array $rgb, float $lineW = 0.5): void
    {
        $py1 = self::PAGE_H - $topY1;
        $py2 = self::PAGE_H - $topY2;
        $this->appendCmd(sprintf(
            "%.2f w %.3f %.3f %.3f RG %.2f %.2f m %.2f %.2f l S\n",
            $lineW,
            $rgb[0] / 255,
            $rgb[1] / 255,
            $rgb[2] / 255,
            $x1,
            $py1,
            $x2,
            $py2
        ));
    }

    /**
     * @param  array{0: int, 1: int, 2: int}  $rgb
     */
    private function writeText(float $x, float $topY, string $text, float $fontSize, string $style, array $rgb): void
    {
        $fontRef = match ($style) {
            'B' => '/F2',
            'I' => '/F3',
            default => '/F1',
        };
        $py = self::PAGE_H - ($topY + ($fontSize * 0.82));
        $encoded = $this->encodePdfText($text);

        $this->appendCmd(sprintf(
            "BT %s %.2f Tf %.3f %.3f %.3f rg %.2f %.2f Td (%s) Tj ET\n",
            $fontRef,
            $fontSize,
            $rgb[0] / 255,
            $rgb[1] / 255,
            $rgb[2] / 255,
            $x,
            $py,
            $encoded
        ));
    }

    /**
     * @param  array{0: int, 1: int, 2: int}  $rgb
     */
    private function writeTextAligned(float $anchorX, float $topY, string $text, float $fontSize, string $style, array $rgb, string $align): void
    {
        $width = $this->measureTextWidth($text, $fontSize, $style === 'B');
        $x = match ($align) {
            'R' => $anchorX - $width,
            'C' => $anchorX - ($width / 2.0),
            default => $anchorX,
        };
        $this->writeText($x, $topY, $text, $fontSize, $style, $rgb);
    }

    /**
     * Memecah teks panjang menjadi beberapa baris sesuai lebar kolom PDF.
     *
     * @return array<int, string>
     */
    private function wrapText(string $text, float $maxWidth, float $fontSize, bool $bold): array
    {
        $clean = trim(preg_replace('/\s+/', ' ', $text) ?? '');
        if ($clean === '') {
            return ['-'];
        }

        $words = explode(' ', $clean);
        $lines = [];
        $current = '';

        foreach ($words as $word) {
            $candidate = $current === '' ? $word : $current.' '.$word;
            if ($this->measureTextWidth($candidate, $fontSize, $bold) <= $maxWidth) {
                $current = $candidate;
            } else {
                if ($current !== '') {
                    $lines[] = $current;
                }
                $current = $word;
            }
        }

        if ($current !== '') {
            $lines[] = $current;
        }

        return $lines ?: ['-'];
    }

    private function measureTextWidth(string $text, float $fontSize, bool $bold): float
    {
        $normalized = $this->normalizeUnicodeForWinAnsi($text);
        $len = mb_strlen($normalized, 'UTF-8');
        $units = 0.0;

        for ($i = 0; $i < $len; $i++) {
            $ch = mb_substr($normalized, $i, 1, 'UTF-8');
            if (in_array($ch, [' ', '.', ',', ':', ';', '!', '|', 'i', 'l', 'j', 'I', '1', '\'', '"'], true)) {
                $units += 0.28;
            } elseif (in_array($ch, ['m', 'w', 'M', 'W', '%', '@'], true)) {
                $units += 0.82;
            } elseif (ctype_upper($ch)) {
                $units += 0.66;
            } else {
                $units += 0.52;
            }
        }

        return $units * $fontSize * ($bold ? 1.05 : 1.0);
    }

    private function normalizeUnicodeForWinAnsi(string $text): string
    {
        return strtr($text, [
            '≈' => '~',
            '≥' => '>=',
            '≤' => '<=',
            '↑' => '^',
            '↓' => 'v',
            '—' => '-',
            '–' => '-',
            '•' => '-',
        ]);
    }

    private function encodePdfText(string $text): string
    {
        $norm = $this->normalizeUnicodeForWinAnsi($text);
        $win = @iconv('UTF-8', 'Windows-1252//TRANSLIT//IGNORE', $norm);
        if ($win === false) {
            $win = preg_replace('/[^\x20-\x7E]/', '', $norm) ?? '';
        }

        return strtr($win, [
            '\\' => '\\\\',
            '(' => '\\(',
            ')' => '\\)',
            "\r" => '',
            "\n" => ' ',
        ]);
    }

    private function appendCmd(string $cmd): void
    {
        $this->pages[$this->currentPage] .= $cmd;
    }

    /**
     * Merakit seluruh halaman, nomor halaman footer, objek font, dan tabel xref PDF 1.4.
     */
    private function compilePdfDocument(): string
    {
        $totalPages = count($this->pages);

        // Tambahkan footer resmi & penomoran halaman pada setiap halaman
        for ($i = 0; $i < $totalPages; $i++) {
            $this->currentPage = $i;
            $this->drawLine(self::MARGIN_X, 804.0, self::MARGIN_X + self::CONTENT_W, 804.0, [203, 213, 225], 0.5);
            $this->writeText(self::MARGIN_X, 810.0, 'Diterbitkan otomatis oleh RansumKu Smart Poultry Farm System · Terverifikasi Sistem', 7.0, 'R', [100, 116, 139]);
            $this->writeTextAligned(self::MARGIN_X + self::CONTENT_W, 810.0, 'Halaman '.($i + 1).' dari '.$totalPages, 7.0, 'B', [71, 85, 105], 'R');
        }

        // Susun objek PDF:
        // 1: Catalog, 2: Pages, 3: Font Regular, 4: Font Bold, 5: Font Oblique
        // 6..N: pasangan Page & Content Stream
        $objects = [];
        $objects[1] = '<< /Type /Catalog /Pages 2 0 R >>';
        $objects[3] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>';
        $objects[4] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>';
        $objects[5] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Oblique /Encoding /WinAnsiEncoding >>';

        $kids = [];
        $nextObjId = 6;

        foreach ($this->pages as $streamContent) {
            $pageObjId = $nextObjId++;
            $contentObjId = $nextObjId++;
            $kids[] = "{$pageObjId} 0 R";

            $compressed = function_exists('gzcompress') ? gzcompress($streamContent, 6) : false;
            if (is_string($compressed)) {
                $len = strlen($compressed);
                $objects[$contentObjId] = "<< /Filter /FlateDecode /Length {$len} >>\nstream\n{$compressed}\nendstream";
            } else {
                $len = strlen($streamContent);
                $objects[$contentObjId] = "<< /Length {$len} >>\nstream\n{$streamContent}\nendstream";
            }

            $objects[$pageObjId] = sprintf(
                '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 %.2f %.2f] /Resources << /Font << /F1 3 0 R /F2 4 0 R /F3 5 0 R >> >> /Contents %d 0 R >>',
                self::PAGE_W,
                self::PAGE_H,
                $contentObjId
            );
        }

        $objects[2] = '<< /Type /Pages /Kids ['.implode(' ', $kids).'] /Count '.$totalPages.' >>';
        ksort($objects);

        $pdf = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";
        $offsets = [0 => 0];

        foreach ($objects as $id => $body) {
            $offsets[$id] = strlen($pdf);
            $pdf .= "{$id} 0 obj\n{$body}\nendobj\n";
        }

        $xrefPos = strlen($pdf);
        $maxId = max(array_keys($objects));
        $pdf .= 'xref'."\n".'0 '.($maxId + 1)."\n";
        $pdf .= "0000000000 65535 f \n";

        for ($id = 1; $id <= $maxId; $id++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$id]);
        }

        $pdf .= "trailer\n<< /Size ".($maxId + 1)." /Root 1 0 R >>\nstartxref\n{$xrefPos}\n%%EOF";

        return $pdf;
    }
}

