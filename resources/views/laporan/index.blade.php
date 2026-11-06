@extends('layouts.app')
@section('judul', 'Laporan Sistem')
@section('isi')
@use('App\Support\Fmt')
@use('Illuminate\Support\Str')

@php
    $ini = $laporan['ini'];
    $lalu = $laporan['lalu'];
    $punyaPembanding = $rentangHari > 0 && ($lalu['hari'] ?? 0) > 0;

    $telurDiff = ($punyaPembanding && $lalu['telur'] > 0)
        ? round((($ini['telur'] - $lalu['telur']) / $lalu['telur']) * 100, 1)
        : null;
    $pakanDiff = ($punyaPembanding && $lalu['pakan'] > 0)
        ? round((($ini['pakan'] - $lalu['pakan']) / $lalu['pakan']) * 100, 1)
        : null;
    $hdpDiff = ($punyaPembanding && $lalu['hdp_rata'] > 0)
        ? round($ini['hdp_rata'] - $lalu['hdp_rata'], 1)
        : null;
    $marginDiff = ($punyaPembanding && $lalu['margin_kotor'] != 0)
        ? round((($ini['margin_kotor'] - $lalu['margin_kotor']) / abs($lalu['margin_kotor'])) * 100, 1)
        : null;

    $lastLog = $logs->last();
    $lastDateStr = $lastLog ? strtoupper($lastLog->tanggal->translatedFormat('j M')) : strtoupper(now()->translatedFormat('j M'));
    $rataPakan = $ini['hari'] > 0 ? round($ini['pakan'] / $ini['hari'], 1) : 0.0;
    $rataBiaya = $ini['hari'] > 0 ? round($ini['biaya_pakan'] / $ini['hari']) : 0;
    $lastEfektif = $lastLog?->telurKgEfektif() ?? 0;
    $lastEfisiensi = ($lastLog && $lastEfektif > 0) ? round($lastLog->pakan_kg * $harga / $lastEfektif) : 0;

    $persenKematian = $jumlahAyam > 0 ? ($ini['mati'] / $jumlahAyam) * 100 : 0.0;
    $giziRansum = $ransumAktif?->hasil['gizi'] ?? [];
@endphp

{{-- SELURUH KONTEN LAYAR WEB (TERSEMBUNYI OTOMATIS SAAT PRINT A4) --}}
<div class="laporan-screen-only no-print space-y-5">

{{-- 1. HEADER UTAMA & TOOLBAR PERIODE / EKSPOR --}}
<div class="space-y-3">
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3">
        <div>
            <div class="flex items-center gap-2 flex-wrap">
                <h1 class="text-xl sm:text-2xl font-black text-ink tracking-tight">Laporan Sistem Terpadu</h1>
                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-extrabold bg-emerald-100 text-emerald-800 border border-emerald-200">
                    <span class="material-symbols-outlined text-[14px]">verified</span>
                    Menyeluruh
                </span>
            </div>
            <p class="text-xs sm:text-sm text-muted mt-0.5">
                Rekapitulasi menyeluruh produksi, keuangan, formulasi ransum, stok gudang, mikroklimat IoT, anomali &amp; aktivitas AI untuk
                <strong class="text-ink font-bold">{{ $farm->nama }}</strong>
                ({{ number_format($jumlahAyam, 0, ',', '.') }} ekor · Mg ke-{{ $farm->umurMinggu() }} · {{ $faseDetail['nama'] }}).
            </p>
        </div>

        <div class="flex items-center gap-2 flex-wrap shrink-0">
            <a href="{{ route('kandang.index') }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-full text-xs font-bold text-ink hover:text-brand bg-white hover:bg-surface border border-line shadow-2xs transition">
                <span class="material-symbols-outlined text-[16px] text-brand">warehouse</span>
                <span>Ganti Kandang</span>
            </a>
            <button type="button" id="btn-buka-laporan-modal" data-buka-laporan-modal class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-full text-xs font-bold text-ink hover:text-brand bg-white hover:bg-surface border border-line shadow-2xs transition active:scale-95 cursor-pointer" title="Buka Pratinjau & Cetak Dokumen Resmi A4">
                <span class="material-symbols-outlined text-[16px] text-brand">print</span>
                <span>Pratinjau Cetak A4</span>
            </button>
            <a href="{{ route('laporan.pdf', ['rentang' => $rentangParam]) }}" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-full text-xs font-bold text-white bg-brand hover:bg-brand-dark shadow-xs transition active:scale-95" title="Unduh Dokumen Resmi PDF A4 Rapi (Langsung 1-Klik)">
                <span class="material-symbols-outlined text-[17px]">picture_as_pdf</span>
                <span>Unduh PDF Resmi</span>
            </a>
        </div>
    </div>

    {{-- Baris Filter Periode & Ekspor Multi-Format Rapi (.xlsx / .csv / WA) --}}
    <div class="bg-white rounded-2xl border border-line p-3 sm:p-3.5 shadow-2xs flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3">
        <div class="flex items-center justify-between sm:justify-start gap-2.5 flex-wrap">
            <span class="text-xs font-bold text-muted uppercase tracking-wider px-1">Periode Evaluasi:</span>
            <div class="inline-flex p-1 bg-[#e6eeea] rounded-full border border-line/60 text-xs font-bold select-none shadow-2xs">
                <a href="{{ route('laporan.index', ['rentang' => 'minggu']) }}" class="px-3.5 py-1.5 rounded-full transition-all {{ $rentangParam === 'minggu' ? 'bg-white text-ink shadow-2xs font-extrabold' : 'text-ink/70 hover:text-ink font-semibold' }}">
                    7 Hari
                </a>
                <a href="{{ route('laporan.index', ['rentang' => 'bulan']) }}" class="px-3.5 py-1.5 rounded-full transition-all {{ $rentangParam === 'bulan' ? 'bg-white text-ink shadow-2xs font-extrabold' : 'text-ink/70 hover:text-ink font-semibold' }}">
                    30 Hari
                </a>
                <a href="{{ route('laporan.index', ['rentang' => 'semua']) }}" class="px-3.5 py-1.5 rounded-full transition-all {{ $rentangParam === 'semua' ? 'bg-white text-ink shadow-2xs font-extrabold' : 'text-ink/70 hover:text-ink font-semibold' }}">
                    Semua Data
                </a>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 lg:flex items-center gap-2">
            <a href="{{ route('laporan.excel', ['rentang' => $rentangParam]) }}" class="inline-flex items-center justify-center gap-1.5 px-3.5 py-2 rounded-xl sm:rounded-full border border-emerald-200 bg-emerald-50/80 hover:bg-emerald-100/80 text-emerald-900 text-xs font-extrabold shadow-2xs transition active:scale-95" title="Unduh Buku Kerja Microsoft Excel (.xlsx) 5 Sheet Rapi Berwarna">
                <span class="material-symbols-outlined text-[16px] text-emerald-700">table_view</span>
                <span>Unduh Excel (.xlsx)</span>
            </a>
            <a href="{{ route('laporan.csv', ['rentang' => $rentangParam]) }}" class="inline-flex items-center justify-center gap-1.5 px-3.5 py-2 rounded-xl sm:rounded-full border border-line bg-surface/60 hover:bg-surface text-ink text-xs font-bold transition active:scale-95" title="Unduh Tabel CSV Terstruktur (UTF-8 BOM)">
                <span class="material-symbols-outlined text-[16px] text-brand">download</span>
                <span>Unduh CSV</span>
            </a>
            <button type="button" id="btn-copy-wa" data-copy-wa class="inline-flex items-center justify-center gap-1.5 px-3.5 py-2 rounded-xl sm:rounded-full border border-line bg-white hover:bg-surface text-ink text-xs font-bold shadow-2xs transition active:scale-95 cursor-pointer" title="Salin Ringkasan Menyeluruh untuk WhatsApp">
                <span class="material-symbols-outlined text-[16px] text-emerald-600">content_copy</span>
                <span>Salin Laporan WA</span>
            </button>
        </div>
    </div>

    {{-- Navigasi Cepat Antar Modul Sistem --}}
    <div class="flex items-center gap-1.5 overflow-x-auto pb-1 no-scrollbar text-xs">
        <a href="#sec-eksekutif" class="px-3 py-1.5 rounded-full bg-white hover:bg-surface border border-line font-bold text-ink whitespace-nowrap shadow-2xs transition">1. Produksi & Keuangan</a>
        <a href="#sec-grafik" class="px-3 py-1.5 rounded-full bg-white hover:bg-surface border border-line font-semibold text-muted hover:text-ink whitespace-nowrap shadow-2xs transition">2. Grafik Tren</a>
        <a href="#sec-ransum" class="px-3 py-1.5 rounded-full bg-white hover:bg-surface border border-line font-semibold text-muted hover:text-ink whitespace-nowrap shadow-2xs transition">3. Formulasi Ransum</a>
        <a href="#sec-gudang" class="px-3 py-1.5 rounded-full bg-white hover:bg-surface border border-line font-semibold text-muted hover:text-ink whitespace-nowrap shadow-2xs transition">4. Stok & Belanja</a>
        <a href="#sec-iot" class="px-3 py-1.5 rounded-full bg-white hover:bg-surface border border-line font-semibold text-muted hover:text-ink whitespace-nowrap shadow-2xs transition">5. Mikroklimat & IoT</a>
        <a href="#sec-anomali" class="px-3 py-1.5 rounded-full bg-white hover:bg-surface border border-line font-semibold text-muted hover:text-ink whitespace-nowrap shadow-2xs transition">6. Anomali & AI</a>
        <a href="#sec-harian" class="px-3 py-1.5 rounded-full bg-white hover:bg-surface border border-line font-semibold text-muted hover:text-ink whitespace-nowrap shadow-2xs transition">7. Rekap Harian</a>
    </div>
</div>

{{-- ======================================================================= --}}
{{-- PILAR 1: RINGKASAN EKSEKUTIF PRODUKSI & KEUANGAN                         --}}
{{-- ======================================================================= --}}
<section id="sec-eksekutif" class="card p-4 sm:p-6 space-y-4 border border-line shadow-2xs scroll-mt-20">
    <div class="flex flex-wrap items-center justify-between gap-2 pb-3 border-b border-line">
        <div class="flex items-center gap-2.5">
            <div class="w-9 h-9 rounded-xl bg-brand/10 text-brand grid place-items-center shrink-0">
                <span class="material-symbols-outlined text-[20px]">analytics</span>
            </div>
            <div>
                <h2 class="text-sm sm:text-base font-black text-ink tracking-tight">1. Ringkasan Eksekutif Produksi &amp; Keuangan</h2>
                <p class="text-xs text-muted">Periode {{ $rentangLabel }} ({{ $ini['hari'] }} hari pencatatan aktif) · Strain {{ $farm->strain ?? 'Lohmann Brown' }}</p>
            </div>
        </div>
        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold {{ $faseDetail['badge_class'] }} border">
            <span>Mg-{{ $farm->umurMinggu() }} · Target HDP {{ $faseDetail['standar_hdp'] }}</span>
        </span>
    </div>

    {{-- Banner Keuangan Operasional --}}
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-3.5">
        <div class="lg:col-span-5 bg-[#186b3e] text-white rounded-2xl p-4 sm:p-5 shadow-xs flex flex-col justify-between space-y-2">
            <div>
                <span class="text-[11px] font-bold text-white/80 uppercase tracking-wider block">ESTIMASI MARGIN BERSIH OPERASIONAL</span>
                <div class="text-2xl sm:text-3xl font-black tracking-tight mt-1 tabular-nums">
                    {{ $ini['margin_bersih'] >= 0 ? '+' : '-' }}{{ Fmt::rp(abs($ini['margin_bersih'])) }}
                </div>
            </div>
            <div class="pt-2 border-t border-white/15 flex items-center justify-between text-xs text-white/90 gap-2 flex-wrap">
                <span>Margin Kotor (Omzet - Pakan): <strong>{{ $ini['margin_kotor'] >= 0 ? '+' : '-' }}{{ Fmt::rp(abs($ini['margin_kotor'])) }}</strong></span>
                @if ($marginDiff !== null)
                    <span class="px-2 py-0.5 rounded-full text-[11px] font-extrabold bg-white/15 text-white">
                        {{ $marginDiff >= 0 ? '↑ +' : '↓ ' }}{{ Fmt::num($marginDiff, 1) }}% vs lalu
                    </span>
                @endif
            </div>
        </div>

        <div class="lg:col-span-7 grid grid-cols-1 sm:grid-cols-3 gap-3">
            <div class="p-4 rounded-2xl bg-surface/60 border border-line flex flex-col justify-between">
                <span class="text-[11px] font-bold text-muted uppercase tracking-wider">Est. Omzet Telur</span>
                <strong class="text-lg sm:text-xl font-black text-emerald-700 tabular-nums mt-1">{{ Fmt::rp($ini['pendapatan_telur']) }}</strong>
                <span class="text-[11px] text-muted mt-1">Acuan {{ Fmt::rp($hargaTelurAcuan) }}/kg × {{ Fmt::num($ini['telur_kg'], 1) }} kg</span>
            </div>
            <div class="p-4 rounded-2xl bg-surface/60 border border-line flex flex-col justify-between">
                <span class="text-[11px] font-bold text-muted uppercase tracking-wider">Est. Biaya Pakan</span>
                <strong class="text-lg sm:text-xl font-black text-ink tabular-nums mt-1">{{ Fmt::rp($ini['biaya_pakan']) }}</strong>
                <span class="text-[11px] text-muted mt-1">@ {{ Fmt::rp($harga) }}/kg × {{ Fmt::num($ini['pakan'], 1) }} kg</span>
            </div>
            <div class="p-4 rounded-2xl bg-surface/60 border border-line flex flex-col justify-between">
                <span class="text-[11px] font-bold text-muted uppercase tracking-wider">Biaya Operasional Lain</span>
                <strong class="text-lg sm:text-xl font-black text-ink tabular-nums mt-1">{{ Fmt::rp($ini['biaya_lain']) }}</strong>
                <span class="text-[11px] text-muted mt-1">Total Beban: {{ Fmt::rp($ini['total_biaya_operasional']) }}</span>
            </div>
        </div>
    </div>

    {{-- Grid 6 Indikator Teknis Kandang --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
        <div class="p-3.5 rounded-xl bg-[#fbfdfc] border border-line/80 flex flex-col justify-between">
            <span class="text-[10px] font-bold text-muted uppercase tracking-wider">Total Telur</span>
            <div class="text-lg sm:text-xl font-black text-ink tabular-nums mt-1">
                {{ Fmt::num($ini['telur'], 0) }} <span class="text-xs font-semibold text-muted">btr</span>
            </div>
            <div class="text-[11px] mt-1 flex items-center justify-between gap-1">
                <span class="text-muted">{{ Fmt::num($ini['telur_kg'], 1) }} kg</span>
                @if ($telurDiff !== null)
                    <span class="font-bold {{ $telurDiff >= 0 ? 'text-emerald-700' : 'text-rose-600' }}">{{ $telurDiff >= 0 ? '+' : '' }}{{ Fmt::num($telurDiff, 1) }}%</span>
                @endif
            </div>
        </div>

        <div class="p-3.5 rounded-xl bg-[#fbfdfc] border border-line/80 flex flex-col justify-between">
            <span class="text-[10px] font-bold text-muted uppercase tracking-wider">Hen Day Production</span>
            <div class="text-lg sm:text-xl font-black {{ $ini['hdp_rata'] >= 80 ? 'text-emerald-700' : ($ini['hdp_rata'] >= 65 ? 'text-teal-700' : 'text-amber-700') }} tabular-nums mt-1">
                {{ Fmt::num($ini['hdp_rata'], 1) }}%
            </div>
            <div class="text-[11px] mt-1 flex items-center justify-between gap-1">
                <span class="text-muted">Rata {{ Fmt::num(round($ini['rata_telur']), 0) }} btr/hr</span>
                @if ($hdpDiff !== null)
                    <span class="font-bold {{ $hdpDiff >= 0 ? 'text-emerald-700' : 'text-rose-600' }}">{{ $hdpDiff >= 0 ? '+' : '' }}{{ Fmt::num($hdpDiff, 1) }}%</span>
                @endif
            </div>
        </div>

        <div class="p-3.5 rounded-xl bg-[#fbfdfc] border border-line/80 flex flex-col justify-between">
            <span class="text-[10px] font-bold text-muted uppercase tracking-wider">Konsumsi Pakan</span>
            <div class="text-lg sm:text-xl font-black text-ink tabular-nums mt-1">
                {{ Fmt::num($ini['pakan'], 1) }} <span class="text-xs font-semibold text-muted">kg</span>
            </div>
            <div class="text-[11px] mt-1 flex items-center justify-between gap-1">
                <span class="text-muted">{{ Fmt::num($rataPakan, 1) }} kg/hr</span>
                @if ($pakanDiff !== null)
                    <span class="font-bold {{ $pakanDiff <= 0 ? 'text-emerald-700' : 'text-amber-700' }}">{{ $pakanDiff >= 0 ? '+' : '' }}{{ Fmt::num($pakanDiff, 1) }}%</span>
                @endif
            </div>
        </div>

        <div class="p-3.5 rounded-xl bg-[#fbfdfc] border border-line/80 flex flex-col justify-between">
            <span class="text-[10px] font-bold text-muted uppercase tracking-wider">Asupan / Ekor</span>
            <div class="text-lg sm:text-xl font-black text-ink tabular-nums mt-1">
                {{ Fmt::num($ini['gram_per_ekor'], 1) }} <span class="text-xs font-semibold text-muted">g/hr</span>
            </div>
            <span class="text-[11px] text-muted mt-1 truncate" title="Standar: {{ $faseDetail['standar_pakan'] }}">Std: {{ $faseDetail['standar_pakan'] }}</span>
        </div>

        <div class="p-3.5 rounded-xl bg-[#fbfdfc] border border-line/80 flex flex-col justify-between">
            <span class="text-[10px] font-bold text-muted uppercase tracking-wider">FCR & HPP Telur</span>
            <div class="text-lg sm:text-xl font-black text-ink tabular-nums mt-1">
                {{ Fmt::num($ini['fcr_rata'], 2) }}
            </div>
            <span class="text-[11px] text-muted mt-1">{{ Fmt::rp($ini['biaya_kg_telur']) }}/kg telur</span>
        </div>

        <div class="p-3.5 rounded-xl bg-[#fbfdfc] border border-line/80 flex flex-col justify-between">
            <span class="text-[10px] font-bold text-muted uppercase tracking-wider">Mortalitas Ayam</span>
            <div class="text-lg sm:text-xl font-black {{ $ini['mati'] > 0 ? 'text-rose-600' : 'text-emerald-700' }} tabular-nums mt-1">
                {{ $ini['mati'] }} <span class="text-xs font-semibold text-muted">ekor</span>
            </div>
            <span class="text-[11px] text-muted mt-1">{{ Fmt::num($persenKematian, 2) }}% dari populasi</span>
        </div>
    </div>
</section>

{{-- ======================================================================= --}}
{{-- PILAR 2: GRAFIK TREN PERFORMA & EFISIENSI                                --}}
{{-- ======================================================================= --}}
<section id="sec-grafik" class="space-y-3.5 scroll-mt-20">
    <div class="flex items-center justify-between gap-2 px-1">
        <div>
            <h2 class="text-sm sm:text-base font-black text-ink tracking-tight">2. Grafik Tren Operasional &amp; Efisiensi</h2>
            <p class="text-xs text-muted">Visualisasi pergerakan pakan, produksi telur, pengeluaran harian, dan efisiensi HPP pada {{ $rentangLabel }}</p>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4" data-buku-grafik-container>
        {{-- Grafik 1: Pakan --}}
        <div class="card p-4 sm:p-5 space-y-2.5 buku-card-fadein">
            <div class="flex items-center justify-between pb-2 border-b border-line">
                <div class="flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-xs bg-[#c97a1e] inline-block shrink-0"></span>
                    <h3 class="text-xs font-bold text-ink uppercase tracking-wider">Konsumsi Pakan (kg)</h3>
                </div>
                <span class="text-[11px] text-muted">Rata-rata: <strong class="text-ink font-bold">{{ Fmt::num($rataPakan, 1) }} kg/hari</strong></span>
            </div>
            <div class="h-48 pt-1">
                <canvas data-buku-chart='@json($grafik['pakan'])' data-chart-name="pakan" aria-label="Grafik konsumsi pakan"></canvas>
            </div>
        </div>

        {{-- Grafik 2: Telur --}}
        <div class="card p-4 sm:p-5 space-y-2.5 buku-card-fadein">
            <div class="flex items-center justify-between pb-2 border-b border-line">
                <div class="flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-xs bg-[#116a3a] inline-block shrink-0"></span>
                    <h3 class="text-xs font-bold text-ink uppercase tracking-wider">Produksi Telur (butir)</h3>
                </div>
                <span class="text-[11px] text-muted">Rata-rata: <strong class="text-ink font-bold">{{ Fmt::num(round($ini['rata_telur']), 0) }} btr/hari</strong></span>
            </div>
            <div class="h-48 pt-1">
                <canvas data-buku-chart='@json($grafik['telur'])' data-chart-name="telur" aria-label="Grafik produksi telur"></canvas>
            </div>
        </div>

        {{-- Grafik 3: Biaya Pakan Harian --}}
        <div class="card p-4 sm:p-5 space-y-2.5 buku-card-fadein">
            <div class="flex items-center justify-between pb-2 border-b border-line">
                <div class="flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-xs bg-[#0e7490] inline-block shrink-0"></span>
                    <h3 class="text-xs font-bold text-ink uppercase tracking-wider">Biaya Pakan Harian (Est)</h3>
                </div>
                <span class="text-[11px] text-muted">Rata-rata: <strong class="text-ink font-bold">{{ Fmt::rp($rataBiaya) }}/hari</strong></span>
            </div>
            <div class="h-48 pt-1">
                <canvas data-buku-chart='@json($grafik['biaya'])' data-chart-name="biaya" aria-label="Grafik biaya pakan harian"></canvas>
            </div>
        </div>

        {{-- Grafik 4: Efisiensi Biaya / Kg Telur --}}
        <div class="card p-4 sm:p-5 space-y-2.5 buku-card-fadein">
            <div class="flex items-center justify-between pb-2 border-b border-line">
                <div class="flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-xs bg-[#7c3aed] inline-block shrink-0"></span>
                    <h3 class="text-xs font-bold text-ink uppercase tracking-wider">Efisiensi Biaya / Kg Telur</h3>
                </div>
                <span class="text-[11px] text-muted">Rata-rata: <strong class="text-ink font-bold">{{ Fmt::rp($ini['biaya_kg_telur']) }}/kg</strong></span>
            </div>
            <div class="h-48 pt-1">
                <canvas data-buku-chart='@json($grafik['efisiensi'])' data-chart-name="efisiensi" aria-label="Grafik efisiensi biaya per kg telur"></canvas>
            </div>
        </div>
    </div>
</section>

{{-- ======================================================================= --}}
{{-- PILAR 3: LAPORAN FORMULASI RANSUM & KEPATUHAN STANDAR GIZI               --}}
{{-- ======================================================================= --}}
<section id="sec-ransum" class="card p-4 sm:p-6 space-y-4 border border-line shadow-2xs scroll-mt-20">
    <div class="flex flex-wrap items-center justify-between gap-2 pb-3 border-b border-line">
        <div class="flex items-center gap-2.5">
            <div class="w-9 h-9 rounded-xl bg-amber-50 text-amber-700 border border-amber-200/70 grid place-items-center shrink-0">
                <span class="material-symbols-outlined text-[20px]">calculate</span>
            </div>
            <div>
                <h2 class="text-sm sm:text-base font-black text-ink tracking-tight">3. Laporan Formulasi Ransum &amp; Kepatuhan Gizi</h2>
                <p class="text-xs text-muted">Total {{ $totalRiwayatRansum }} riwayat formulasi tersimpan · {{ $totalStandarGizi }} standar gizi terdaftar di sistem</p>
            </div>
        </div>
        <a href="{{ route('ransum.index') }}" class="inline-flex items-center gap-1 text-xs font-bold text-brand hover:underline">
            <span>Buka Peracik Ransum</span>
            <span class="material-symbols-outlined text-[16px]">arrow_forward</span>
        </a>
    </div>

    @if ($ransumAktif)
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
            <div class="p-3.5 rounded-xl bg-surface/60 border border-line">
                <span class="text-[10px] font-bold text-muted uppercase tracking-wider block">Acuan Standar Gizi</span>
                <strong class="text-xs sm:text-sm font-black text-ink block mt-1 truncate" title="{{ $ransumAktif->namaStandar() }}">{{ $ransumAktif->namaStandar() }}</strong>
                <span class="text-[11px] text-muted">Disusun {{ $ransumAktif->created_at->translatedFormat('d M Y') }}</span>
            </div>
            <div class="p-3.5 rounded-xl bg-surface/60 border border-line">
                <span class="text-[10px] font-bold text-muted uppercase tracking-wider block">Biaya Ransum / Kg</span>
                <strong class="text-base sm:text-lg font-black text-brand block mt-1 tabular-nums">{{ Fmt::rp($ransumAktif->biaya_per_kg) }}</strong>
                <span class="text-[11px] text-muted">Least-Cost Linear Simplex</span>
            </div>
            <div class="p-3.5 rounded-xl bg-surface/60 border border-line">
                <span class="text-[10px] font-bold text-muted uppercase tracking-wider block">Volume & Biaya Batch</span>
                <strong class="text-base sm:text-lg font-black text-ink block mt-1 tabular-nums">{{ Fmt::num($ransumAktif->total_kg, 1) }} kg</strong>
                <span class="text-[11px] text-muted">Total {{ Fmt::rp($ransumAktif->total_biaya) }}</span>
            </div>
            <div class="p-3.5 rounded-xl bg-surface/60 border border-line">
                <span class="text-[10px] font-bold text-muted uppercase tracking-wider block">Status Kelayakan</span>
                <strong class="text-sm sm:text-base font-black text-emerald-700 block mt-1">Layak & Optimal</strong>
                <span class="text-[11px] text-muted">{{ $ransumAktif->items->count() }} bahan penyusun</span>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 pt-1">
            {{-- Komposisi Bahan Ransum Aktif --}}
            <div class="lg:col-span-7 space-y-2">
                <h3 class="text-xs font-extrabold text-ink uppercase tracking-wider">Komposisi Bahan Ransum Aktif</h3>
                <div class="overflow-x-auto rounded-xl border border-line">
                    <table class="w-full text-xs">
                        <thead>
                            <tr class="bg-surface/60 text-muted border-b border-line text-[10.5px] uppercase tracking-wider font-bold">
                                <th class="py-2.5 px-3 text-left">Bahan Baku</th>
                                <th class="py-2.5 px-2.5 text-right">Porsi BK</th>
                                <th class="py-2.5 px-2.5 text-right">Takaran Batch</th>
                                <th class="py-2.5 px-2.5 text-right">Harga/kg</th>
                                <th class="py-2.5 px-3 text-right">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-line/50">
                            @foreach ($ransumAktif->items as $item)
                                <tr>
                                    <td class="py-2 px-3 font-semibold text-ink">{{ $item->nama }}</td>
                                    <td class="py-2 px-2.5 text-right tabular-nums font-bold text-brand">{{ Fmt::num($item->persen, 1) }}%</td>
                                    <td class="py-2 px-2.5 text-right tabular-nums text-ink">{{ Fmt::num($item->kg_as_fed ?? $item->kg, 2) }} kg</td>
                                    <td class="py-2 px-2.5 text-right tabular-nums text-muted">{{ Fmt::rp($item->harga_saat_hitung) }}</td>
                                    <td class="py-2 px-3 text-right tabular-nums font-semibold text-ink">{{ Fmt::rp($item->biaya) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Evaluasi Pemenuhan Nutrisi --}}
            <div class="lg:col-span-5 space-y-2">
                <h3 class="text-xs font-extrabold text-ink uppercase tracking-wider">Evaluasi Parameter Gizi (SNI / Strain)</h3>
                <div class="rounded-xl border border-line divide-y divide-line/50 bg-[#fbfdfc]">
                    @forelse ($giziRansum as $g)
                        <div class="p-3 flex items-center justify-between gap-2 text-xs">
                            <div>
                                <span class="font-bold text-ink block">{{ $g['label'] }}</span>
                                <span class="text-[11px] text-muted">Target: {{ $g['target'] }}</span>
                            </div>
                            <div class="text-right">
                                <strong class="font-black tabular-nums block {{ !empty($g['ok']) ? 'text-emerald-700' : 'text-rose-600' }}">
                                    {{ Fmt::num($g['nilai'], 2) }}{{ $g['satuan'] }}
                                </strong>
                                <span class="text-[10px] font-bold uppercase {{ !empty($g['ok']) ? 'text-emerald-600' : 'text-rose-600' }}">
                                    {{ !empty($g['ok']) ? 'Terpenuhi' : 'Di Luar Target' }}
                                </span>
                            </div>
                        </div>
                    @empty
                        <div class="p-4 text-xs text-muted text-center">Detail parameter nutrisi belum tersedia.</div>
                    @endforelse
                </div>
            </div>
        </div>
    @else
        <div class="p-5 rounded-2xl bg-surface/60 border border-line text-center space-y-2">
            <p class="text-xs sm:text-sm font-semibold text-ink">Belum ada formulasi ransum aktif yang dihitung untuk kandang ini.</p>
            <a href="{{ route('ransum.create') }}" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-full bg-brand text-white text-xs font-bold shadow-2xs hover:bg-brand-dark transition">
                <span class="material-symbols-outlined text-[16px]">add</span>
                <span>Susun Ransum Pertama</span>
            </a>
        </div>
    @endif
</section>

{{-- ======================================================================= --}}
{{-- PILAR 4: LAPORAN GUDANG STOK BAHAN & AUDIT BELANJA PAKAN                 --}}
{{-- ======================================================================= --}}
<section id="sec-gudang" class="card p-4 sm:p-6 space-y-4 border border-line shadow-2xs scroll-mt-20">
    <div class="flex flex-wrap items-center justify-between gap-2 pb-3 border-b border-line">
        <div class="flex items-center gap-2.5">
            <div class="w-9 h-9 rounded-xl bg-teal-50 text-teal-700 border border-teal-200/70 grid place-items-center shrink-0">
                <span class="material-symbols-outlined text-[20px]">inventory_2</span>
            </div>
            <div>
                <h2 class="text-sm sm:text-base font-black text-ink tracking-tight">4. Laporan Gudang Stok Bahan &amp; Audit Belanja Pakan</h2>
                <p class="text-xs text-muted">{{ $ringkasanGudang['total_bahan_aktif'] }} bahan aktif dari {{ $ringkasanGudang['total_katalog_bahan'] }} katalog bahan · {{ $ringkasanBelanja['semua_transaksi_count'] }} total histori pembelian</p>
            </div>
        </div>
        <div class="flex items-center gap-3 text-xs font-bold">
            <a href="{{ route('bahan.index') }}" class="text-brand hover:underline">Kelola Bahan</a>
            <span class="text-muted/40">•</span>
            <a href="{{ route('catatan.index', ['tab' => 'beli']) }}" class="text-brand hover:underline">Catat Belanja</a>
        </div>
    </div>

    {{-- 4 KPI Gudang & Pembelian --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
        <div class="p-3.5 rounded-xl bg-surface/60 border border-line">
            <span class="text-[10px] font-bold text-muted uppercase tracking-wider block">Total Stok Gudang</span>
            <strong class="text-lg font-black text-ink block mt-1 tabular-nums">{{ Fmt::num($ringkasanGudang['total_stok_kg'], 1) }} kg</strong>
            <span class="text-[11px] text-muted">Nilai aset: {{ Fmt::rp($ringkasanGudang['total_nilai_stok_rp']) }}</span>
        </div>
        <div class="p-3.5 rounded-xl bg-surface/60 border border-line">
            <span class="text-[10px] font-bold text-muted uppercase tracking-wider block">Est. Ketahanan Stok</span>
            <strong class="text-lg font-black {{ $ringkasanGudang['estimasi_ketahanan_hari'] < 5 ? 'text-rose-600' : 'text-emerald-700' }} block mt-1 tabular-nums">
                {{ Fmt::num($ringkasanGudang['estimasi_ketahanan_hari'], 1) }} hari
            </strong>
            <span class="text-[11px] text-muted">Pakai ≈ {{ Fmt::num($ringkasanGudang['konsumsi_harian_kg'], 1) }} kg/hari</span>
        </div>
        <div class="p-3.5 rounded-xl bg-surface/60 border border-line">
            <span class="text-[10px] font-bold text-muted uppercase tracking-wider block">Status Kesiapan Bahan</span>
            <strong class="text-lg font-black {{ $ringkasanGudang['stok_menipis_count'] > 0 ? 'text-amber-700' : 'text-emerald-700' }} block mt-1">
                {{ $ringkasanGudang['stok_menipis_count'] }} Menipis
            </strong>
            <span class="text-[11px] text-muted">{{ $ringkasanGudang['harga_usang_count'] }} bahan harga &gt; 7 hari</span>
        </div>
        <div class="p-3.5 rounded-xl bg-surface/60 border border-line">
            <span class="text-[10px] font-bold text-muted uppercase tracking-wider block">Belanja ({{ $rentangLabel }})</span>
            <strong class="text-lg font-black text-ink block mt-1 tabular-nums">{{ Fmt::rp($ringkasanBelanja['total_pengeluaran_rp']) }}</strong>
            <span class="text-[11px] {{ $ringkasanBelanja['total_selisih_rp'] > 0 ? 'text-rose-600 font-semibold' : 'text-emerald-700 font-semibold' }}">
                Deviasi: {{ $ringkasanBelanja['total_selisih_rp'] >= 0 ? '+' : '-' }}{{ Fmt::rp(abs($ringkasanBelanja['total_selisih_rp'])) }}
            </span>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 pt-1">
        {{-- Inventaris Stok Gudang --}}
        <div class="lg:col-span-6 space-y-2">
            <h3 class="text-xs font-extrabold text-ink uppercase tracking-wider">Inventaris Stok Bahan Aktif</h3>
            <div class="overflow-x-auto rounded-xl border border-line">
                <table class="w-full text-xs">
                    <thead>
                        <tr class="bg-surface/60 text-muted border-b border-line text-[10.5px] uppercase tracking-wider font-bold">
                            <th class="py-2.5 px-3 text-left">Bahan</th>
                            <th class="py-2.5 px-2.5 text-right">Stok</th>
                            <th class="py-2.5 px-2.5 text-right">Harga/kg</th>
                            <th class="py-2.5 px-3 text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line/50">
                        @forelse ($stokGudang as $fi)
                            @php
                                $menipis = (float) $fi->stok_kg < 50;
                                $usang = $fi->hargaUsang();
                            @endphp
                            <tr>
                                <td class="py-2 px-3 font-semibold text-ink">{{ $fi->ingredient?->nama ?? '-' }}</td>
                                <td class="py-2 px-2.5 text-right tabular-nums font-bold {{ $menipis ? 'text-rose-600' : 'text-ink' }}">{{ Fmt::num($fi->stok_kg, 1) }} kg</td>
                                <td class="py-2 px-2.5 text-right tabular-nums text-muted">{{ Fmt::rp($fi->harga) }}</td>
                                <td class="py-2 px-3 text-center">
                                    @if ($menipis)
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-50 text-rose-700 border border-rose-200">Menipis</span>
                                    @elseif ($usang)
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200">Harga Usang</span>
                                    @else
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">Aman</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="py-5 text-center text-muted italic">Belum ada data stok bahan aktif di gudang.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Riwayat Audit Belanja Pakan --}}
        <div class="lg:col-span-6 space-y-2">
            <h3 class="text-xs font-extrabold text-ink uppercase tracking-wider">Audit Transaksi Belanja Pakan Terbaru</h3>
            <div class="overflow-x-auto rounded-xl border border-line">
                <table class="w-full text-xs">
                    <thead>
                        <tr class="bg-surface/60 text-muted border-b border-line text-[10.5px] uppercase tracking-wider font-bold">
                            <th class="py-2.5 px-3 text-left">Tanggal & Bahan</th>
                            <th class="py-2.5 px-2 text-right">Volume</th>
                            <th class="py-2.5 px-2.5 text-right">Harga Toko</th>
                            <th class="py-2.5 px-3 text-right">Selisih Est.</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line/50">
                        @forelse ($riwayatBelanjaTerbaru as $p)
                            @php $sel = $p->selisih(); @endphp
                            <tr>
                                <td class="py-2 px-3">
                                    <span class="font-semibold text-ink block">{{ $p->ingredient?->nama ?? '-' }}</span>
                                    <span class="text-[10.5px] text-muted">{{ $p->tanggal->translatedFormat('d M Y') }}</span>
                                </td>
                                <td class="py-2 px-2 text-right tabular-nums text-ink">{{ Fmt::num($p->kg, 1) }} kg</td>
                                <td class="py-2 px-2.5 text-right tabular-nums font-semibold text-ink">{{ Fmt::rp($p->harga_nyata) }}</td>
                                <td class="py-2 px-3 text-right tabular-nums font-bold {{ $sel > 0 ? 'text-rose-600' : ($sel < 0 ? 'text-emerald-700' : 'text-muted') }}">
                                    {{ $sel > 0 ? '+' : ($sel < 0 ? '-' : '') }}{{ $sel == 0 ? 'Sesuai' : Fmt::rp(abs($sel)) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="py-5 text-center text-muted italic">Belum ada transaksi pembelian bahan tercatat.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>

{{-- ======================================================================= --}}
{{-- PILAR 5: LAPORAN MIKROKLIMAT KANDANG (THI) & SMART CONTROLLER IOT        --}}
{{-- ======================================================================= --}}
<section id="sec-iot" class="card p-4 sm:p-6 space-y-4 border border-line shadow-2xs scroll-mt-20">
    <div class="flex flex-wrap items-center justify-between gap-2 pb-3 border-b border-line">
        <div class="flex items-center gap-2.5">
            <div class="w-9 h-9 rounded-xl bg-purple-50 text-purple-700 border border-purple-200/70 grid place-items-center shrink-0">
                <span class="material-symbols-outlined text-[20px]">tune</span>
            </div>
            <div>
                <h2 class="text-sm sm:text-base font-black text-ink tracking-tight">5. Laporan Mikroklimat Kandang (THI) &amp; Smart Controller IoT</h2>
                <p class="text-xs text-muted">
                    Sumber Telemetri:
                    <strong class="text-ink">{{ $ringkasanIot['sensor_live'] ? 'Sensor IoT ESP32/DHT22 Live' : 'Cuaca Mikro ' . ($cuacaMikro['kota'] ?? 'Blitar') }}</strong>
                    · Diperbarui {{ $cuacaMikro['updated_at'] ?? '-' }}
                </p>
            </div>
        </div>
        <a href="{{ route('kontroler.index') }}" class="inline-flex items-center gap-1 text-xs font-bold text-brand hover:underline">
            <span>Buka Smart Controller 3D</span>
            <span class="material-symbols-outlined text-[16px]">arrow_forward</span>
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-4">
        {{-- Panel Iklim Mikro & Indeks Stres Panas (THI) --}}
        <div class="lg:col-span-5 p-4 rounded-2xl bg-surface/50 border border-line space-y-3">
            <div class="flex items-center justify-between gap-2">
                <span class="text-xs font-extrabold text-ink uppercase tracking-wider">Kondisi Iklim & Heat Stress</span>
                <span class="px-2.5 py-0.5 rounded-full text-[10.5px] font-extrabold border {{ $cuacaMikro['badge_class'] ?? 'bg-emerald-100 text-emerald-800 border-emerald-200' }}">
                    THI {{ Fmt::num($cuacaMikro['thi'] ?? 0, 1) }} · {{ $cuacaMikro['thi_kategori_singkat'] ?? 'Normal' }}
                </span>
            </div>

            <div class="grid grid-cols-3 gap-2.5 text-center">
                <div class="p-3 rounded-xl bg-white border border-line">
                    <span class="text-[10px] font-bold text-muted uppercase block">Suhu</span>
                    <strong class="text-base sm:text-lg font-black text-ink tabular-nums">{{ Fmt::num($cuacaMikro['suhu'] ?? 0, 1) }}°C</strong>
                </div>
                <div class="p-3 rounded-xl bg-white border border-line">
                    <span class="text-[10px] font-bold text-muted uppercase block">Kelembaban</span>
                    <strong class="text-base sm:text-lg font-black text-ink tabular-nums">{{ Fmt::num($cuacaMikro['kelembaban'] ?? 0, 0) }}%</strong>
                </div>
                <div class="p-3 rounded-xl bg-white border border-line">
                    <span class="text-[10px] font-bold text-muted uppercase block">Indeks THI</span>
                    <strong class="text-base sm:text-lg font-black text-ink tabular-nums">{{ Fmt::num($cuacaMikro['thi'] ?? 0, 1) }}</strong>
                </div>
            </div>

            <div class="text-xs text-muted space-y-1 pt-1">
                <strong class="text-ink block font-bold">{{ $cuacaMikro['thi_kategori'] ?? 'Zona Nyaman' }}</strong>
                @if (!empty($cuacaMikro['saran_manajemen']))
                    <ul class="list-disc list-inside space-y-0.5 text-[11.5px]">
                        @foreach (array_slice($cuacaMikro['saran_manajemen'], 0, 2) as $saran)
                            <li>{{ $saran }}</li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>

        {{-- Panel Status 4 Aktuator & Ambang Otomasi --}}
        <div class="lg:col-span-7 space-y-3">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <span class="text-xs font-extrabold text-ink uppercase tracking-wider">Status Aktuator Kandang ({{ $ringkasanIot['aktif_count'] }}/4 Menyala)</span>
                <div class="flex items-center gap-2 text-[11px] font-bold">
                    <span class="px-2.5 py-0.5 rounded-full border {{ $ringkasanIot['controller_siap'] ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-slate-100 text-slate-600 border-slate-200' }}">
                        Hardware: {{ $ringkasanIot['controller_siap'] ? 'Terhubung' : 'Simulasi / Standby' }}
                    </span>
                    <span class="px-2.5 py-0.5 rounded-full bg-purple-50 text-purple-700 border border-purple-200 uppercase">
                        Mode {{ $ringkasanIot['mode_kendali'] }}
                    </span>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 text-xs">
                <div class="p-3 rounded-xl border border-line bg-[#fbfdfc] flex items-center justify-between gap-2">
                    <div>
                        <strong class="font-bold text-ink block">Kipas Exhaust / Blower</strong>
                        <span class="text-[11px] text-muted">Auto: Suhu ≥ {{ $kontrolerSetting['kipas']['suhu_nyala'] }}°C / THI ≥ {{ $kontrolerSetting['kipas']['thi_nyala'] }}</span>
                    </div>
                    <span class="px-2.5 py-1 rounded-full text-[10px] font-extrabold uppercase {{ $ringkasanIot['relay_kipas'] ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-600' }}">
                        {{ $ringkasanIot['relay_kipas'] ? 'MENYALA' : 'MATI' }}
                    </span>
                </div>

                <div class="p-3 rounded-xl border border-line bg-[#fbfdfc] flex items-center justify-between gap-2">
                    <div>
                        <strong class="font-bold text-ink block">Pompa Cooling / Misting</strong>
                        <span class="text-[11px] text-muted">Auto: THI ≥ {{ $kontrolerSetting['misting']['thi_nyala'] }} &amp; RH ≤ {{ $kontrolerSetting['misting']['rh_maksimal'] }}%</span>
                    </div>
                    <span class="px-2.5 py-1 rounded-full text-[10px] font-extrabold uppercase {{ $ringkasanIot['relay_misting'] ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-600' }}">
                        {{ $ringkasanIot['relay_misting'] ? 'MENYALA' : 'MATI' }}
                    </span>
                </div>

                <div class="p-3 rounded-xl border border-line bg-[#fbfdfc] flex items-center justify-between gap-2">
                    <div>
                        <strong class="font-bold text-ink block">Lampu Pencahayaan Layer</strong>
                        <span class="text-[11px] text-muted">Jadwal: {{ $kontrolerSetting['lampu']['jam_pagi_mulai'] }}-{{ $kontrolerSetting['lampu']['jam_pagi_selesai'] }} &amp; {{ $kontrolerSetting['lampu']['jam_sore_mulai'] }}-{{ $kontrolerSetting['lampu']['jam_sore_selesai'] }}</span>
                    </div>
                    <span class="px-2.5 py-1 rounded-full text-[10px] font-extrabold uppercase {{ $ringkasanIot['relay_lampu'] ? 'bg-amber-100 text-amber-800' : 'bg-slate-100 text-slate-600' }}">
                        {{ $ringkasanIot['relay_lampu'] ? 'MENYALA' : 'MATI' }}
                    </span>
                </div>

                <div class="p-3 rounded-xl border border-line bg-[#fbfdfc] flex items-center justify-between gap-2">
                    <div>
                        <strong class="font-bold text-ink block">Shuttle Feeder Pakan</strong>
                        <span class="text-[11px] text-muted">Jadwal: {{ $kontrolerSetting['pakan']['jam_pagi'] }} &amp; {{ $kontrolerSetting['pakan']['jam_sore'] }} ({{ $kontrolerSetting['pakan']['durasi_detik'] }} dtk)</span>
                    </div>
                    <span class="px-2.5 py-1 rounded-full text-[10px] font-extrabold uppercase {{ $ringkasanIot['relay_pakan'] ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-600' }}">
                        {{ $ringkasanIot['relay_pakan'] ? 'BERJALAN' : 'STANDBY' }}
                    </span>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ======================================================================= --}}
{{-- PILAR 6: DETEKSI DINI ANOMALI & AKTIVITAS ASISTEN CERDAS AI              --}}
{{-- ======================================================================= --}}
<section id="sec-anomali" class="grid grid-cols-1 lg:grid-cols-12 gap-4 scroll-mt-20">
    {{-- Deteksi Dini Anomali --}}
    <div class="lg:col-span-8 card p-4 sm:p-6 space-y-3.5 border border-line shadow-2xs">
        <div class="flex flex-wrap items-center justify-between gap-2 pb-2.5 border-b border-line">
            <div class="flex items-center gap-2.5">
                <div class="w-9 h-9 rounded-xl bg-rose-50 text-rose-600 border border-rose-200/70 grid place-items-center shrink-0">
                    <span class="material-symbols-outlined text-[20px]">notification_important</span>
                </div>
                <div>
                    <h2 class="text-sm sm:text-base font-black text-ink tracking-tight">6. Laporan Deteksi Dini Anomali Statistik</h2>
                    <p class="text-xs text-muted">{{ $ringkasanAnomali['belum_dibaca'] }} peringatan aktif belum ditinjau dari {{ $ringkasanAnomali['total_semua'] }} riwayat temuan</p>
                </div>
            </div>
            <span class="px-2.5 py-1 rounded-full text-xs font-bold {{ $ringkasanAnomali['belum_dibaca'] > 0 ? 'bg-rose-50 text-rose-700 border border-rose-200' : 'bg-emerald-50 text-emerald-700 border border-emerald-200' }}">
                {{ $ringkasanAnomali['belum_dibaca'] > 0 ? $ringkasanAnomali['kritis_aktif'] . ' Kritis · ' . $ringkasanAnomali['perhatian_aktif'] . ' Perhatian' : 'Kandang Terkendali' }}
            </span>
        </div>

        <div class="space-y-2">
            @forelse ($daftarAnomali->take(5) as $al)
                @php
                    $jenisJudul = match($al->jenis) {
                        'pakan_naik_telur_turun' => 'Anomali Konsumsi Pakan & Penurunan Telur',
                        'lonjakan_kematian' => 'Lonjakan Kematian Ayam Harian',
                        'biaya_per_kg_telur_naik' => 'Lonjakan Biaya Pakan per Kg Telur',
                        'cuaca_ekstrem', 'heat_stress' => 'Peringatan Risiko Heat Stress',
                        default => ucwords(str_replace('_', ' ', $al->jenis)),
                    };
                @endphp
                <div class="p-3 rounded-xl border {{ !$al->dibaca ? 'bg-rose-50/30 border-rose-200/80' : 'bg-surface/40 border-line' }} flex items-start justify-between gap-3 text-xs">
                    <div class="space-y-0.5">
                        <div class="flex items-center gap-2 flex-wrap">
                            <strong class="font-bold text-ink">{{ $jenisJudul }}</strong>
                            <span class="text-[10.5px] text-muted font-mono">{{ $al->tanggal_data?->format('d/m/Y') ?? '-' }}</span>
                        </div>
                        <p class="text-muted leading-relaxed">{{ $al->pesan }}</p>
                    </div>
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold shrink-0 {{ !$al->dibaca ? 'bg-rose-100 text-rose-800' : 'bg-slate-100 text-slate-600' }}">
                        {{ !$al->dibaca ? 'Aktif' : 'Ditinjau' }}
                    </span>
                </div>
            @empty
                <div class="p-5 rounded-xl bg-surface/50 border border-line text-center text-xs text-muted">
                    Tidak ada anomali produksi, kematian, maupun lonjakan HPP telur yang terdeteksi oleh sistem.
                </div>
            @endforelse
        </div>
    </div>

    {{-- Aktivitas Konsultasi Asisten Cerdas AI --}}
    <div class="lg:col-span-4 card p-4 sm:p-6 flex flex-col justify-between space-y-4 border border-line shadow-2xs">
        <div class="space-y-3">
            <div class="flex items-center gap-2.5 pb-2.5 border-b border-line">
                <div class="w-9 h-9 rounded-xl bg-brand/10 text-brand grid place-items-center shrink-0">
                    <span class="material-symbols-outlined text-[20px]">psychology</span>
                </div>
                <div>
                    <h2 class="text-sm sm:text-base font-black text-ink tracking-tight">Asisten Cerdas AI</h2>
                    <p class="text-xs text-muted">Log konsultasi manajemen & nutrisi</p>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-2.5">
                <div class="p-3.5 rounded-xl bg-surface/60 border border-line">
                    <span class="text-[10px] font-bold text-muted uppercase block">Sesi Konsultasi</span>
                    <strong class="text-xl font-black text-ink tabular-nums mt-0.5 block">{{ $ringkasanAi['total_sesi'] }}</strong>
                </div>
                <div class="p-3.5 rounded-xl bg-surface/60 border border-line">
                    <span class="text-[10px] font-bold text-muted uppercase block">Total Pesan AI</span>
                    <strong class="text-xl font-black text-brand tabular-nums mt-0.5 block">{{ $ringkasanAi['total_pesan'] }}</strong>
                </div>
            </div>

            <div class="p-3 rounded-xl bg-[#fbfdfc] border border-line text-xs space-y-1">
                <div class="flex items-center justify-between">
                    <span class="text-muted font-semibold">Status Mesin AI</span>
                    <span class="font-bold {{ $ringkasanAi['tersedia'] ? 'text-emerald-700' : 'text-amber-700' }}">
                        {{ $ringkasanAi['tersedia'] ? 'Online · Siap Analisis' : 'Belum Dikonfigurasi' }}
                    </span>
                </div>
                <p class="text-[11px] text-muted leading-relaxed">
                    Asisten AI membaca seluruh metrik laporan di halaman ini secara langsung untuk membantu evaluasi teknis harian.
                </p>
            </div>
        </div>

        <a href="{{ route('asisten.index') }}" class="w-full py-2.5 px-4 rounded-xl bg-brand hover:bg-brand-dark text-white text-xs font-bold flex items-center justify-center gap-1.5 shadow-2xs transition">
            <span class="material-symbols-outlined text-[17px]">chat</span>
            <span>Konsultasikan Laporan ke AI</span>
        </a>
    </div>
</section>

{{-- ======================================================================= --}}
{{-- PILAR 7: TABEL REKAPITULASI HARIAN BUKU KANDANG                          --}}
{{-- ======================================================================= --}}
<section id="sec-harian" class="card p-4 sm:p-6 space-y-4 border border-line shadow-2xs scroll-mt-20">
    <div class="flex flex-wrap items-center justify-between gap-2 pb-3 border-b border-line">
        <div class="flex items-center gap-2.5">
            <div class="w-9 h-9 rounded-xl bg-brand/10 text-brand grid place-items-center shrink-0">
                <span class="material-symbols-outlined text-[20px]">menu_book</span>
            </div>
            <div>
                <h2 class="text-sm sm:text-base font-black text-ink tracking-tight">7. Tabel Rekapitulasi Harian Buku Kandang</h2>
                <p class="text-xs text-muted">Rincian catatan harian pada {{ $rentangLabel }} ({{ $logs->count() }} baris data)</p>
            </div>
        </div>
        <a href="{{ route('catatan.index') }}" class="inline-flex items-center gap-1 text-xs font-bold text-brand hover:underline">
            <span>Input / Edit Catatan Harian</span>
            <span class="material-symbols-outlined text-[16px]">arrow_forward</span>
        </a>
    </div>

    <div class="overflow-x-auto rounded-xl border border-line">
        <table class="w-full text-xs text-left border-collapse">
            <thead>
                <tr class="bg-surface/70 text-ink font-bold border-b border-line text-[10.5px] uppercase tracking-wider">
                    <th class="py-2.5 px-3">Tanggal</th>
                    <th class="py-2.5 px-2.5 text-right">Pakan (kg)</th>
                    <th class="py-2.5 px-2.5 text-right">g/Ekor</th>
                    <th class="py-2.5 px-2.5 text-right">Telur (btr)</th>
                    <th class="py-2.5 px-2.5 text-right">Telur (kg)</th>
                    <th class="py-2.5 px-2.5 text-center">HDP</th>
                    <th class="py-2.5 px-2.5 text-center">FCR</th>
                    <th class="py-2.5 px-2.5 text-center">Mati</th>
                    <th class="py-2.5 px-2.5 text-right">Est. Biaya Pakan</th>
                    <th class="py-2.5 px-2.5 text-right">Biaya Lain</th>
                    <th class="py-2.5 px-3 text-left">Catatan</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-line/50">
                @forelse ($logs as $l)
                    @php
                        $rHdp = $jumlahAyam > 0 ? ($l->telur_butir / $jumlahAyam) * 100 : 0;
                        $rGr = $jumlahAyam > 0 ? ($l->pakan_kg * 1000) / $jumlahAyam : 0;
                        $rKg = $l->telurKgEfektif();
                        $rFcr = $rKg > 0 ? $l->pakan_kg / $rKg : 0;
                        $rBiayaPakan = $l->pakan_kg * $harga;
                    @endphp
                    <tr class="hover:bg-surface/40 transition-colors">
                        <td class="py-2.5 px-3 font-semibold text-ink whitespace-nowrap">{{ $l->tanggal->translatedFormat('d M Y') }}</td>
                        <td class="py-2.5 px-2.5 text-right tabular-nums">{{ Fmt::num($l->pakan_kg, 1) }}</td>
                        <td class="py-2.5 px-2.5 text-right tabular-nums text-muted">{{ round($rGr) }}</td>
                        <td class="py-2.5 px-2.5 text-right tabular-nums font-bold text-ink">{{ Fmt::num($l->telur_butir, 0) }}</td>
                        <td class="py-2.5 px-2.5 text-right tabular-nums text-muted">{{ Fmt::num($rKg, 1) }}</td>
                        <td class="py-2.5 px-2.5 text-center tabular-nums font-bold {{ $rHdp >= 80 ? 'text-emerald-700' : ($rHdp >= 65 ? 'text-teal-700' : 'text-amber-700') }}">{{ Fmt::num($rHdp, 1) }}%</td>
                        <td class="py-2.5 px-2.5 text-center tabular-nums">{{ Fmt::num($rFcr, 2) }}</td>
                        <td class="py-2.5 px-2.5 text-center tabular-nums {{ $l->ayam_mati > 0 ? 'text-rose-600 font-bold' : 'text-muted' }}">{{ $l->ayam_mati > 0 ? $l->ayam_mati : '-' }}</td>
                        <td class="py-2.5 px-2.5 text-right tabular-nums">{{ Fmt::rp($rBiayaPakan) }}</td>
                        <td class="py-2.5 px-2.5 text-right tabular-nums text-muted">{{ $l->biaya_lain > 0 ? Fmt::rp($l->biaya_lain) : '-' }}</td>
                        <td class="py-2.5 px-3 text-muted max-w-[180px] truncate" title="{{ $l->catatan }}">{{ $l->catatan ?: '-' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="11" class="py-6 text-center text-muted italic">Belum ada catatan harian pada rentang periode ini.</td>
                    </tr>
                @endforelse
            </tbody>
            <tfoot>
                <tr class="bg-surface font-black text-ink border-t-2 border-line">
                    <td class="py-2.5 px-3 uppercase text-[11px] tracking-wider">TOTAL / RATA-RATA</td>
                    <td class="py-2.5 px-2.5 text-right tabular-nums">{{ Fmt::num($ini['pakan'], 1) }}</td>
                    <td class="py-2.5 px-2.5 text-right tabular-nums">{{ round($ini['gram_per_ekor']) }}</td>
                    <td class="py-2.5 px-2.5 text-right tabular-nums">{{ Fmt::num($ini['telur'], 0) }}</td>
                    <td class="py-2.5 px-2.5 text-right tabular-nums">{{ Fmt::num($ini['telur_kg'], 1) }}</td>
                    <td class="py-2.5 px-2.5 text-center tabular-nums text-brand">{{ Fmt::num($ini['hdp_rata'], 1) }}%</td>
                    <td class="py-2.5 px-2.5 text-center tabular-nums">{{ Fmt::num($ini['fcr_rata'], 2) }}</td>
                    <td class="py-2.5 px-2.5 text-center tabular-nums">{{ $ini['mati'] }}</td>
                    <td class="py-2.5 px-2.5 text-right tabular-nums">{{ Fmt::rp($ini['biaya_pakan']) }}</td>
                    <td class="py-2.5 px-2.5 text-right tabular-nums">{{ Fmt::rp($ini['biaya_lain']) }}</td>
                    <td class="py-2.5 px-3 text-[11px] font-semibold text-muted">{{ $ini['hari'] }} Hari Rekap</td>
                </tr>
            </tfoot>
        </table>
    </div>
</section>

</div>
{{-- AKHIR KONTEN LAYAR WEB (.laporan-screen-only.no-print) --}}

{{-- ======================================================================= --}}
{{-- MODAL PRATINJAU & CETAK DOKUMEN LAPORAN SISTEM RESMI A4 / PDF            --}}
{{-- ======================================================================= --}}
<div id="modal-laporan-resmi" class="hidden fixed inset-0 z-50 overflow-y-auto bg-slate-950/80 backdrop-blur-xs p-3 sm:p-6 flex flex-col items-center">
    {{-- Toolbar Layar Monitor (Otomatis Tersembunyi Saat Print) --}}
    <div class="modal-toolbar max-w-4xl w-full bg-slate-900/95 backdrop-blur-md text-white rounded-2xl p-3 sm:px-5 sm:py-3.5 mb-4 shadow-2xl flex flex-wrap items-center justify-between gap-3 border border-slate-700/80 sticky top-3 z-30">
        <div class="flex items-center gap-3">
            <div class="w-9 h-9 rounded-full bg-brand flex items-center justify-center text-white shadow-xs">
                <span class="material-symbols-outlined text-[19px]">picture_as_pdf</span>
            </div>
            <div>
                <h3 class="font-bold text-sm leading-tight text-white flex items-center gap-2">
                    <span>Pratinjau Dokumen Laporan Sistem (Format A4)</span>
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">Siap Cetak / PDF</span>
                </h3>
                <p class="text-[11px] text-slate-400">Standar A4 Portrait · Laporan Menyeluruh ({{ $rentangLabel }}) · {{ $farm->nama }}</p>
            </div>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('laporan.pdf', ['rentang' => $rentangParam]) }}" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-full bg-brand hover:bg-brand-dark text-white text-xs font-bold shadow-md hover:shadow-brand/20 transition active:scale-95">
                <span class="material-symbols-outlined text-[17px]">download</span>
                <span>Unduh File PDF</span>
            </a>
            <a href="{{ route('laporan.excel', ['rentang' => $rentangParam]) }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-full bg-emerald-950/80 hover:bg-emerald-900 text-emerald-200 border border-emerald-700/60 text-xs font-bold transition active:scale-95">
                <span class="material-symbols-outlined text-[16px]">table_view</span>
                <span>Excel (.xlsx)</span>
            </a>
            <button type="button" id="btn-cetak-dokumen" class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-full bg-slate-800 hover:bg-slate-700 text-white border border-slate-600/80 text-xs font-bold transition active:scale-95 cursor-pointer">
                <span class="material-symbols-outlined text-[16px]">print</span>
                <span>Cetak Printer</span>
            </button>
            <button type="button" id="btn-tutup-laporan-modal" class="inline-flex items-center gap-1 px-3 py-2 rounded-full bg-slate-800/80 hover:bg-rose-950/80 text-slate-300 hover:text-rose-200 text-xs font-semibold transition active:scale-95 cursor-pointer" title="Tutup pratinjau (Esc)">
                <span class="material-symbols-outlined text-[17px]">close</span>
                <span>Tutup</span>
            </button>
        </div>
    </div>

    {{-- Lembar Dokumen Kertas A4 Formal --}}
    <div id="dokumen-laporan-resmi" class="dokumen-kertas-a4 max-w-4xl w-full rounded-2xl p-6 sm:p-9 text-slate-900 space-y-4 bg-white shadow-2xl border border-slate-200">
        {{-- 1. KOP RESMI LAPORAN SISTEM TERPADU --}}
        <div class="border-b-2 border-brand pb-3.5">
            <div class="flex items-start justify-between gap-4">
                <div class="flex items-center gap-3.5">
                    <div class="w-12 h-12 rounded-xl bg-brand text-white flex items-center justify-center font-black text-2xl shadow-xs shrink-0 border border-brand-dark p-2">
                        <img src="{{ asset('images/logo.png') }}" alt="Logo RansumKu" class="w-full h-full object-contain rounded-lg" onerror="this.outerHTML='<span class=\'material-symbols-outlined text-[26px]\'>egg</span>'">
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h1 class="text-xl sm:text-2xl font-black tracking-tight text-slate-950">RansumKu</h1>
                            <span class="text-xs font-bold text-slate-400">· Smart Poultry Farm</span>
                            <span class="hidden sm:inline-block px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-800 border border-emerald-200 uppercase tracking-wider">Dokumen Resmi</span>
                        </div>
                        <p class="text-xs sm:text-sm font-bold text-brand uppercase tracking-wider mt-0.5">Laporan Rekapitulasi Menyeluruh Sistem Kandang Layer</p>
                        <p class="text-[11px] text-slate-500">Produksi, Keuangan, Formulasi Ransum SNI, Inventaris Gudang, Mikroklimat IoT &amp; Deteksi Anomali</p>
                    </div>
                </div>
                <div class="text-right text-[11px] text-slate-600 shrink-0">
                    <p class="font-bold text-slate-900 font-mono tracking-wide">REF: LP-{{ $farm->id }}-{{ now()->format('Ymd') }}</p>
                    <p class="text-slate-500 mt-0.5">Dicetak: {{ now()->translatedFormat('d M Y, H:i') }} WIB</p>
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[9.5px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200 uppercase mt-1 font-mono">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                        Terverifikasi Sistem
                    </span>
                </div>
            </div>
        </div>

        {{-- 2. TABEL METADATA PETERNAKAN --}}
        <div class="grid grid-cols-2 sm:grid-cols-5 gap-2 sm:gap-0 divide-y sm:divide-y-0 sm:divide-x divide-slate-200 rounded-xl bg-slate-50 border border-slate-200 p-2.5 text-xs page-break-avoid">
            <div class="px-2.5 py-0.5">
                <span class="text-slate-500 block text-[9.5px] uppercase font-bold tracking-wider">Unit Kandang</span>
                <strong class="text-slate-900 text-xs font-black block truncate mt-0.5">{{ $farm->nama }}</strong>
                <span class="text-[10px] text-slate-500 block">{{ $faseDetail['nama'] }}</span>
            </div>
            <div class="px-2.5 py-0.5">
                <span class="text-slate-500 block text-[9.5px] uppercase font-bold tracking-wider">Populasi &amp; Strain</span>
                <strong class="text-slate-900 text-xs font-black block mt-0.5">{{ number_format($jumlahAyam, 0, ',', '.') }} Ekor</strong>
                <span class="text-[10px] text-slate-500 block truncate">{{ $farm->strain ?? 'Lohmann Brown' }}</span>
            </div>
            <div class="px-2.5 py-0.5">
                <span class="text-slate-500 block text-[9.5px] uppercase font-bold tracking-wider">Umur &amp; Lokasi</span>
                <strong class="text-slate-900 text-xs font-black block mt-0.5">Mg ke-{{ $farm->umurMinggu() }}</strong>
                <span class="text-[10px] text-slate-500 block truncate">{{ $farm->lokasi_kota ?: 'Blitar' }}</span>
            </div>
            <div class="px-2.5 py-0.5">
                <span class="text-slate-500 block text-[9.5px] uppercase font-bold tracking-wider">Periode Evaluasi</span>
                <strong class="text-slate-900 text-xs font-black block mt-0.5">{{ $rentangLabel }}</strong>
                <span class="text-[10px] text-slate-500 block font-mono">{{ $logs->first()?->tanggal?->format('d/m/y') ?? '-' }} – {{ $logs->last()?->tanggal?->format('d/m/y') ?? '-' }}</span>
            </div>
            <div class="px-2.5 py-0.5">
                <span class="text-slate-500 block text-[9.5px] uppercase font-bold tracking-wider">Penanggung Jawab</span>
                <strong class="text-slate-900 text-xs font-black block truncate mt-0.5">{{ auth()->user()->name ?? 'Peternak' }}</strong>
                <span class="text-[10px] text-slate-500 block">Supervisor Kandang</span>
            </div>
        </div>

        {{-- BAGIAN I: RINGKASAN EKSEKUTIF PRODUKSI & NERACA KEUANGAN --}}
        <div class="space-y-1.5 page-break-avoid">
            <h2 class="text-[11px] font-black text-emerald-900 uppercase tracking-wider flex items-center gap-1.5">
                <span class="w-1.5 h-3.5 rounded-xs bg-brand inline-block"></span>
                <span>Bagian I. Ringkasan Eksekutif Produksi &amp; Neraca Keuangan Operasional</span>
            </h2>
            <div class="overflow-x-auto rounded-lg border border-slate-200">
                <table class="print-table w-full text-xs border-collapse">
                    <thead>
                        <tr class="bg-emerald-50/90 text-emerald-950 font-bold text-[10px] uppercase tracking-wider border-b border-slate-200">
                            <th class="py-1.5 px-2.5 text-left w-[26%]">Indikator Produksi</th>
                            <th class="py-1.5 px-2.5 text-right w-[24%] border-r border-slate-200">Capaian Teknis</th>
                            <th class="py-1.5 px-2.5 text-left w-[26%]">Komponen Keuangan</th>
                            <th class="py-1.5 px-2.5 text-right w-[24%]">Nilai Rupiah</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 text-slate-800">
                        <tr>
                            <td class="py-1.5 px-2.5 font-semibold">Total Produksi Telur</td>
                            <td class="py-1.5 px-2.5 text-right font-mono font-bold border-r border-slate-200">{{ Fmt::num($ini['telur'], 0) }} btr ({{ Fmt::num($ini['telur_kg'], 1) }} kg)</td>
                            <td class="py-1.5 px-2.5 font-semibold">Est. Omzet Penjualan Telur</td>
                            <td class="py-1.5 px-2.5 text-right font-mono font-bold text-emerald-800">{{ Fmt::rp($ini['pendapatan_telur']) }}</td>
                        </tr>
                        <tr class="bg-slate-50/60">
                            <td class="py-1.5 px-2.5 font-semibold">Hen Day Production (HDP)</td>
                            <td class="py-1.5 px-2.5 text-right font-mono font-bold border-r border-slate-200">{{ Fmt::num($ini['hdp_rata'], 1) }}% (Target {{ $faseDetail['standar_hdp'] }})</td>
                            <td class="py-1.5 px-2.5 font-semibold">Est. Biaya Konsumsi Pakan</td>
                            <td class="py-1.5 px-2.5 text-right font-mono">{{ Fmt::rp($ini['biaya_pakan']) }}</td>
                        </tr>
                        <tr>
                            <td class="py-1.5 px-2.5 font-semibold">Total &amp; Rata-rata Pakan</td>
                            <td class="py-1.5 px-2.5 text-right font-mono border-r border-slate-200">{{ Fmt::num($ini['pakan'], 1) }} kg ({{ Fmt::num($ini['gram_per_ekor'], 1) }} g/ekor/hr)</td>
                            <td class="py-1.5 px-2.5 font-semibold">Biaya Operasional Lainnya</td>
                            <td class="py-1.5 px-2.5 text-right font-mono">{{ Fmt::rp($ini['biaya_lain']) }}</td>
                        </tr>
                        <tr class="bg-slate-50/60">
                            <td class="py-1.5 px-2.5 font-semibold">Feed Conversion Ratio (FCR)</td>
                            <td class="py-1.5 px-2.5 text-right font-mono font-bold border-r border-slate-200">{{ Fmt::num($ini['fcr_rata'], 2) }} ({{ Fmt::rp($ini['biaya_kg_telur']) }}/kg telur)</td>
                            <td class="py-1.5 px-2.5 font-semibold">Margin Kotor (Omzet - Pakan)</td>
                            <td class="py-1.5 px-2.5 text-right font-mono font-semibold">{{ $ini['margin_kotor'] >= 0 ? '+' : '-' }}{{ Fmt::rp(abs($ini['margin_kotor'])) }}</td>
                        </tr>
                        <tr>
                            <td class="py-1.5 px-2.5 font-semibold">Mortalitas Ayam Periode</td>
                            <td class="py-1.5 px-2.5 text-right font-mono border-r border-slate-200">{{ $ini['mati'] }} ekor ({{ Fmt::num($persenKematian, 2) }}%)</td>
                            <td class="py-1.5 px-2.5 font-black text-slate-950">EST. MARGIN BERSIH</td>
                            <td class="py-1.5 px-2.5 text-right font-mono font-black {{ $ini['margin_bersih'] >= 0 ? 'text-emerald-800' : 'text-rose-700' }}">
                                {{ $ini['margin_bersih'] >= 0 ? '+' : '-' }}{{ Fmt::rp(abs($ini['margin_bersih'])) }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        {{-- BAGIAN II: FORMULASI RANSUM AKTIF & EVALUASI GIZI SNI --}}
        <div class="space-y-1.5 page-break-avoid">
            <div class="flex items-center justify-between gap-2">
                <h2 class="text-[11px] font-black text-emerald-900 uppercase tracking-wider flex items-center gap-1.5">
                    <span class="w-1.5 h-3.5 rounded-xs bg-brand inline-block"></span>
                    <span>Bagian II. Komposisi Formulasi Ransum Aktif &amp; Kepatuhan Gizi SNI</span>
                </h2>
                @if ($ransumAktif)
                    <span class="text-[10px] font-semibold text-slate-600 font-mono">
                        {{ $ransumAktif->namaStandar() }} · {{ Fmt::rp($ransumAktif->biaya_per_kg) }}/kg · Batch {{ Fmt::num($ransumAktif->total_kg, 1) }} kg
                    </span>
                @endif
            </div>

            @if ($ransumAktif)
                <div class="grid grid-cols-1 sm:grid-cols-12 gap-2.5">
                    <div class="sm:col-span-7 overflow-x-auto rounded-lg border border-slate-200">
                        <table class="print-table w-full text-xs border-collapse">
                            <thead>
                                <tr class="bg-emerald-50/90 text-emerald-950 font-bold text-[10px] uppercase tracking-wider border-b border-slate-200">
                                    <th class="py-1.5 px-2 text-left">Bahan Penyusun</th>
                                    <th class="py-1.5 px-2 text-right">Porsi (%)</th>
                                    <th class="py-1.5 px-2 text-right">Batch (kg)</th>
                                    <th class="py-1.5 px-2 text-right">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200 text-slate-800">
                                @foreach ($ransumAktif->items as $it)
                                    <tr>
                                        <td class="py-1 px-2 font-semibold">{{ $it->nama }}</td>
                                        <td class="py-1 px-2 text-right font-mono">{{ Fmt::num($it->persen, 1) }}%</td>
                                        <td class="py-1 px-2 text-right font-mono">{{ Fmt::num($it->kg_as_fed ?? $it->kg, 2) }}</td>
                                        <td class="py-1 px-2 text-right font-mono">{{ Fmt::rp($it->biaya) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr class="bg-slate-100 font-black text-slate-950 border-t border-slate-300">
                                    <td class="py-1.5 px-2 text-left text-[10px] uppercase">TOTAL BATCH</td>
                                    <td class="py-1.5 px-2 text-right font-mono">100,0%</td>
                                    <td class="py-1.5 px-2 text-right font-mono">{{ Fmt::num($ransumAktif->total_kg, 2) }}</td>
                                    <td class="py-1.5 px-2 text-right font-mono">{{ Fmt::rp($ransumAktif->total_biaya) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <div class="sm:col-span-5 overflow-x-auto rounded-lg border border-slate-200">
                        <table class="print-table w-full text-xs border-collapse">
                            <thead>
                                <tr class="bg-emerald-50/90 text-emerald-950 font-bold text-[10px] uppercase tracking-wider border-b border-slate-200">
                                    <th class="py-1.5 px-2 text-left">Nutrisi SNI</th>
                                    <th class="py-1.5 px-1.5 text-right">Aktual</th>
                                    <th class="py-1.5 px-1.5 text-center">Target</th>
                                    <th class="py-1.5 px-1.5 text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200 text-slate-800">
                                @forelse ($giziRansum as $gz)
                                    <tr>
                                        <td class="py-1 px-2 font-semibold">{{ $gz['label'] }}</td>
                                        <td class="py-1 px-1.5 text-right font-mono font-bold">{{ Fmt::num($gz['nilai'], 2) }}{{ $gz['satuan'] }}</td>
                                        <td class="py-1 px-1.5 text-center font-mono text-[10px] text-slate-600">{{ $gz['target'] }}</td>
                                        <td class="py-1 px-1.5 text-center text-[10px] font-bold {{ !empty($gz['ok']) ? 'text-emerald-800' : 'text-rose-700' }}">
                                            {{ !empty($gz['ok']) ? 'Sesuai' : 'Evaluasi' }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="py-3 text-center text-slate-400 italic">Data parameter nutrisi belum tersedia.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            @else
                <div class="p-2.5 rounded-lg border border-slate-200 bg-slate-50 text-xs text-slate-600 italic">
                    Belum ada formulasi ransum aktif yang tersimpan pada kandang ini.
                </div>
            @endif
        </div>

        {{-- BAGIAN III: INVENTARIS STOK GUDANG & AUDIT BELANJA PAKAN --}}
        <div class="space-y-1.5 page-break-avoid">
            <div class="flex items-center justify-between gap-2">
                <h2 class="text-[11px] font-black text-emerald-900 uppercase tracking-wider flex items-center gap-1.5">
                    <span class="w-1.5 h-3.5 rounded-xs bg-brand inline-block"></span>
                    <span>Bagian III. Inventaris Stok Gudang &amp; Audit Pembelian Bahan Pakan</span>
                </h2>
                <span class="text-[10px] font-semibold text-slate-600 font-mono">
                    Ketahanan Stok ≈ {{ Fmt::num($ringkasanGudang['estimasi_ketahanan_hari'], 1) }} hari · Belanja Periode: {{ $ringkasanBelanja['jumlah_transaksi'] }} transaksi ({{ Fmt::rp($ringkasanBelanja['total_pengeluaran_rp']) }})
                </span>
            </div>

            <div class="overflow-x-auto rounded-lg border border-slate-200">
                <table class="print-table w-full text-xs border-collapse">
                    <thead>
                        <tr class="bg-emerald-50/90 text-emerald-950 font-bold text-[10px] uppercase tracking-wider border-b border-slate-200">
                            <th class="py-1.5 px-2.5 text-left">Bahan Baku Aktif</th>
                            <th class="py-1.5 px-2 text-right">Stok Tersedia</th>
                            <th class="py-1.5 px-2 text-right">Harga Acuan/kg</th>
                            <th class="py-1.5 px-2.5 text-right">Nilai Aset Stok</th>
                            <th class="py-1.5 px-2 text-center">Status Kesiapan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 text-slate-800">
                        @forelse ($stokGudang as $fi)
                            @php
                                $stokVal = (float) $fi->stok_kg;
                                $nilaiFi = (int) round($stokVal * (float) $fi->harga);
                                $isMenipis = $stokVal < 50;
                                $isUsang = $fi->hargaUsang();
                            @endphp
                            <tr>
                                <td class="py-1 px-2.5 font-semibold">{{ $fi->ingredient?->nama ?? '-' }}</td>
                                <td class="py-1 px-2 text-right font-mono font-bold {{ $isMenipis ? 'text-rose-700' : '' }}">{{ Fmt::num($stokVal, 1) }} kg</td>
                                <td class="py-1 px-2 text-right font-mono">{{ Fmt::rp($fi->harga) }}</td>
                                <td class="py-1 px-2.5 text-right font-mono">{{ Fmt::rp($nilaiFi) }}</td>
                                <td class="py-1 px-2 text-center text-[10px] font-bold {{ $isMenipis ? 'text-rose-700' : ($isUsang ? 'text-amber-700' : 'text-emerald-800') }}">
                                    {{ $isMenipis ? 'Stok Menipis' : ($isUsang ? 'Harga > 7 Hari' : 'Aman') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-3 text-center text-slate-400 italic">Belum ada bahan aktif tercatat di gudang.</td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr class="bg-slate-100 font-black text-slate-950 border-t border-slate-300">
                            <td class="py-1.5 px-2.5 text-left text-[10px] uppercase">TOTAL ASET STOK GUDANG</td>
                            <td class="py-1.5 px-2 text-right font-mono">{{ Fmt::num($ringkasanGudang['total_stok_kg'], 1) }} kg</td>
                            <td class="py-1.5 px-2 text-right font-mono text-[10px] font-semibold text-slate-600">Pakai ≈ {{ Fmt::num($ringkasanGudang['konsumsi_harian_kg'], 1) }} kg/hr</td>
                            <td class="py-1.5 px-2.5 text-right font-mono">{{ Fmt::rp($ringkasanGudang['total_nilai_stok_rp']) }}</td>
                            <td class="py-1.5 px-2 text-center text-[10px] font-bold">{{ $ringkasanGudang['stok_menipis_count'] }} Menipis</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        {{-- BAGIAN IV: MIKROKLIMAT (THI), SMART CONTROLLER IOT, ANOMALI & AI --}}
        <div class="space-y-1.5 page-break-avoid">
            <h2 class="text-[11px] font-black text-emerald-900 uppercase tracking-wider flex items-center gap-1.5">
                <span class="w-1.5 h-3.5 rounded-xs bg-brand inline-block"></span>
                <span>Bagian IV. Telemetri Mikroklimat (THI), Smart Controller IoT, Anomali &amp; AI</span>
            </h2>
            <div class="overflow-x-auto rounded-lg border border-slate-200">
                <table class="print-table w-full text-xs border-collapse">
                    <thead>
                        <tr class="bg-emerald-50/90 text-emerald-950 font-bold text-[10px] uppercase tracking-wider border-b border-slate-200">
                            <th class="py-1.5 px-2.5 text-left w-[28%]">Subsistem Cerdas</th>
                            <th class="py-1.5 px-2.5 text-left w-[52%]">Kondisi &amp; Konfigurasi Operasional</th>
                            <th class="py-1.5 px-2.5 text-center w-[20%]">Status Sistem</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 text-slate-800">
                        <tr>
                            <td class="py-1.5 px-2.5 font-semibold">Mikroklimat &amp; Indeks THI</td>
                            <td class="py-1.5 px-2.5">
                                Suhu <strong>{{ Fmt::num($cuacaMikro['suhu'] ?? 0, 1) }}°C</strong> · Kelembaban <strong>{{ Fmt::num($cuacaMikro['kelembaban'] ?? 0, 0) }}%</strong> · Indeks THI <strong>{{ Fmt::num($cuacaMikro['thi'] ?? 0, 1) }}</strong> ({{ $ringkasanIot['sensor_live'] ? 'Sensor ESP32/DHT22 Live' : 'Cuaca Mikro ' . ($cuacaMikro['kota'] ?? 'Blitar') }})
                            </td>
                            <td class="py-1.5 px-2.5 text-center font-bold text-[10.5px]">{{ $cuacaMikro['thi_kategori_singkat'] ?? 'Normal' }}</td>
                        </tr>
                        <tr class="bg-slate-50/60">
                            <td class="py-1.5 px-2.5 font-semibold">Smart Controller 4-Relay IoT</td>
                            <td class="py-1.5 px-2.5">
                                Kipas: <strong>{{ $ringkasanIot['relay_kipas'] ? 'ON' : 'OFF' }}</strong> · Cooling: <strong>{{ $ringkasanIot['relay_misting'] ? 'ON' : 'OFF' }}</strong> · Lampu: <strong>{{ $ringkasanIot['relay_lampu'] ? 'ON' : 'OFF' }}</strong> · Feeder: <strong>{{ $ringkasanIot['relay_pakan'] ? 'ON' : 'OFF' }}</strong> ({{ $ringkasanIot['aktif_count'] }}/4 aktif)
                            </td>
                            <td class="py-1.5 px-2.5 text-center font-bold text-[10.5px] uppercase">Mode {{ $ringkasanIot['mode_kendali'] }}</td>
                        </tr>
                        <tr>
                            <td class="py-1.5 px-2.5 font-semibold">Deteksi Dini Anomali Statistik</td>
                            <td class="py-1.5 px-2.5">
                                {{ $ringkasanAnomali['belum_dibaca'] }} peringatan aktif belum ditinjau ({{ $ringkasanAnomali['kritis_aktif'] }} kritis, {{ $ringkasanAnomali['perhatian_aktif'] }} perhatian) dari {{ $ringkasanAnomali['total_semua'] }} riwayat temuan
                            </td>
                            <td class="py-1.5 px-2.5 text-center font-bold text-[10.5px] {{ $ringkasanAnomali['belum_dibaca'] > 0 ? 'text-rose-700' : 'text-emerald-800' }}">
                                {{ $ringkasanAnomali['belum_dibaca'] > 0 ? 'Perlu Tinjauan' : 'Terkendali' }}
                            </td>
                        </tr>
                        <tr class="bg-slate-50/60">
                            <td class="py-1.5 px-2.5 font-semibold">Asisten Cerdas AI</td>
                            <td class="py-1.5 px-2.5">
                                Tercatat <strong>{{ $ringkasanAi['total_sesi'] }}</strong> sesi diskusi dengan <strong>{{ $ringkasanAi['total_pesan'] }}</strong> pesan konsultasi teknis kandang &amp; nutrisi
                            </td>
                            <td class="py-1.5 px-2.5 text-center font-bold text-[10.5px] text-emerald-800">
                                {{ $ringkasanAi['tersedia'] ? 'AI Online' : 'Standby' }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        {{-- BAGIAN V: TABEL REKAPITULASI HARIAN BUKU KANDANG --}}
        <div class="space-y-1.5">
            <h2 class="text-[11px] font-black text-emerald-900 uppercase tracking-wider flex items-center gap-1.5">
                <span class="w-1.5 h-3.5 rounded-xs bg-brand inline-block"></span>
                <span>Bagian V. Tabel Rekapitulasi Harian Buku Kandang ({{ $rentangLabel }})</span>
            </h2>
            <div class="overflow-x-auto rounded-lg border border-slate-200 shadow-2xs">
                <table class="print-table w-full text-xs text-left border-collapse">
                    <thead>
                        <tr class="bg-emerald-50/90 text-emerald-950 font-bold text-center border-b-2 border-slate-200 text-[10px] uppercase tracking-wider">
                            <th class="py-1.5 px-2 border-r border-slate-200 w-8 text-center">No</th>
                            <th class="py-1.5 px-2 border-r border-slate-200 text-left">Tanggal</th>
                            <th class="py-1.5 px-2 border-r border-slate-200 text-right">Pakan (kg)</th>
                            <th class="py-1.5 px-2 border-r border-slate-200 text-right">g/Ekor</th>
                            <th class="py-1.5 px-2 border-r border-slate-200 text-right">Telur (btr)</th>
                            <th class="py-1.5 px-2 border-r border-slate-200 text-right">Telur (kg)</th>
                            <th class="py-1.5 px-2 border-r border-slate-200 text-center">HDP (%)</th>
                            <th class="py-1.5 px-2 border-r border-slate-200 text-center">FCR</th>
                            <th class="py-1.5 px-2 border-r border-slate-200 text-center">Mati</th>
                            <th class="py-1.5 px-2 border-r border-slate-200 text-right">Biaya Pakan</th>
                            <th class="py-1.5 px-2 text-left">Catatan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 text-slate-800">
                        @forelse ($logs as $idx => $l)
                            @php
                                $rowHdp = $jumlahAyam > 0 ? ($l->telur_butir / $jumlahAyam) * 100 : 0;
                                $rowGr = $jumlahAyam > 0 ? ($l->pakan_kg * 1000) / $jumlahAyam : 0;
                                $rowKg = $l->telurKgEfektif();
                                $rowFcr = $rowKg > 0 ? $l->pakan_kg / $rowKg : 0;
                                $rowBiaya = $l->pakan_kg * $harga;
                            @endphp
                            <tr class="{{ $idx % 2 === 1 ? 'bg-slate-50/60' : 'bg-white' }} page-break-avoid">
                                <td class="py-1.5 px-2 border-r border-slate-200 text-center font-mono text-slate-500">{{ $idx + 1 }}</td>
                                <td class="py-1.5 px-2 border-r border-slate-200 font-semibold text-slate-900 whitespace-nowrap">{{ $l->tanggal->translatedFormat('d/m/Y') }}</td>
                                <td class="py-1.5 px-2 border-r border-slate-200 text-right font-mono tabular-nums">{{ Fmt::num($l->pakan_kg, 1) }}</td>
                                <td class="py-1.5 px-2 border-r border-slate-200 text-right font-mono tabular-nums">{{ round($rowGr) }}</td>
                                <td class="py-1.5 px-2 border-r border-slate-200 text-right font-mono tabular-nums font-bold text-slate-950">{{ Fmt::num($l->telur_butir, 0) }}</td>
                                <td class="py-1.5 px-2 border-r border-slate-200 text-right font-mono tabular-nums">{{ Fmt::num($rowKg, 1) }}</td>
                                <td class="py-1.5 px-2 border-r border-slate-200 text-center font-mono tabular-nums font-bold">{{ Fmt::num($rowHdp, 1) }}%</td>
                                <td class="py-1.5 px-2 border-r border-slate-200 text-center font-mono tabular-nums">{{ Fmt::num($rowFcr, 2) }}</td>
                                <td class="py-1.5 px-2 border-r border-slate-200 text-center font-mono tabular-nums">{{ $l->ayam_mati > 0 ? $l->ayam_mati : '-' }}</td>
                                <td class="py-1.5 px-2 border-r border-slate-200 text-right font-mono tabular-nums">{{ Fmt::rp($rowBiaya) }}</td>
                                <td class="py-1.5 px-2 text-slate-600 text-[10.5px]">{{ $l->catatan ? Str::limit($l->catatan, 32) : '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="11" class="py-5 text-center text-slate-400 italic">Belum ada catatan harian pada periode ini.</td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr class="bg-slate-100 text-slate-950 font-black border-t-2 border-slate-300 page-break-avoid">
                            <td colspan="2" class="py-2 px-2 border-r border-slate-200 text-center uppercase text-[10.5px] tracking-wider">TOTAL / RATA-RATA</td>
                            <td class="py-2 px-2 border-r border-slate-200 text-right font-mono tabular-nums">{{ Fmt::num($ini['pakan'], 1) }}</td>
                            <td class="py-2 px-2 border-r border-slate-200 text-right font-mono tabular-nums">{{ round($ini['gram_per_ekor']) }}</td>
                            <td class="py-2 px-2 border-r border-slate-200 text-right font-mono tabular-nums">{{ Fmt::num($ini['telur'], 0) }}</td>
                            <td class="py-2 px-2 border-r border-slate-200 text-right font-mono tabular-nums">{{ Fmt::num($ini['telur_kg'], 1) }}</td>
                            <td class="py-2 px-2 border-r border-slate-200 text-center font-mono tabular-nums">{{ Fmt::num($ini['hdp_rata'], 1) }}%</td>
                            <td class="py-2 px-2 border-r border-slate-200 text-center font-mono tabular-nums">{{ Fmt::num($ini['fcr_rata'], 2) }}</td>
                            <td class="py-2 px-2 border-r border-slate-200 text-center font-mono tabular-nums">{{ $ini['mati'] }}</td>
                            <td class="py-2 px-2 border-r border-slate-200 text-right font-mono tabular-nums">{{ Fmt::rp($ini['biaya_pakan']) }}</td>
                            <td class="py-2 px-2 text-center text-[10px] font-semibold text-slate-700">{{ $ini['hari'] }} Hari Rekap</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        {{-- BAGIAN VI: EVALUASI TEKNIS & LEMBAR PENGESAHAN RESMI --}}
        <div class="pt-1 page-break-avoid space-y-4">
            <div class="p-3 rounded-xl bg-emerald-50/50 border border-emerald-200 text-xs text-slate-800 leading-relaxed">
                <strong class="text-emerald-950 font-bold block mb-0.5">Kesimpulan Evaluasi Sistem (Umur Minggu ke-{{ $farm->umurMinggu() }} · {{ $faseDetail['nama'] }}):</strong>
                <p class="text-slate-700">
                    @if ($ini['hdp_rata'] >= 85)
                        Performa produksi sangat optimal (HDP rata-rata <strong>{{ Fmt::num($ini['hdp_rata'], 1) }}%</strong> dengan FCR <strong>{{ Fmt::num($ini['fcr_rata'], 2) }}</strong>). Pertahankan keseimbangan formulasi ransum, ketersediaan stok bahan baku gudang, serta mitigasi suhu mikroklimat kandang.
                    @elseif ($ini['hdp_rata'] >= 70)
                        Performa produksi berada pada kategori terkendali (HDP <strong>{{ Fmt::num($ini['hdp_rata'], 1) }}%</strong>, FCR <strong>{{ Fmt::num($ini['fcr_rata'], 2) }}</strong>). Pantau bahan pakan yang menipis ({{ $ringkasanGudang['stok_menipis_count'] }} bahan) dan jaga indeks stres panas (THI {{ Fmt::num($cuacaMikro['thi'] ?? 0, 1) }}) agar tetap di zona nyaman.
                    @else
                        Performa produksi memerlukan evaluasi menyeluruh (HDP <strong>{{ Fmt::num($ini['hdp_rata'], 1) }}%</strong> di bawah target {{ $faseDetail['standar_hdp'] }}). Disarankan mengevaluasi kecukupan energi-protein ransum, memastikan stok bahan utama mencukupi, dan meninjau peringatan dini anomali.
                    @endif
                </p>
            </div>

            <div class="flex justify-between items-end pt-2 text-xs text-slate-900">
                <div class="text-center w-56">
                    <p class="text-slate-500 mb-1 text-[11px]">Dicatat &amp; Disusun Oleh,</p>
                    <p class="font-extrabold text-slate-900">Operator / Petugas Kandang</p>
                    <div class="h-16 flex items-end justify-center pb-1">
                        <span class="text-[11px] text-slate-300 italic select-none">( Tanda Tangan )</span>
                    </div>
                    <div class="w-44 mx-auto border-b border-slate-400"></div>
                    <p class="font-bold text-slate-900 mt-1.5">{{ auth()->user()->name ?? 'Operator Kandang' }}</p>
                    <p class="text-[10px] text-slate-500">Petugas Operasional Kandang</p>
                </div>

                <div class="text-center w-56">
                    <p class="text-slate-500 mb-1 text-[11px]">{{ $farm->lokasi_kota ?: 'Blitar' }}, {{ now()->translatedFormat('d F Y') }}</p>
                    <p class="font-extrabold text-slate-900">Pimpinan / Pemilik Peternakan</p>
                    <div class="h-16 flex items-end justify-center pb-1">
                        <span class="text-[11px] text-slate-300 italic select-none">( Tanda Tangan &amp; Stempel )</span>
                    </div>
                    <div class="w-44 mx-auto border-b border-slate-400"></div>
                    <p class="font-bold text-slate-900 mt-1.5">Mengetahui &amp; Menyetujui,</p>
                    <p class="text-[10px] text-slate-500">{{ $farm->nama }}</p>
                </div>
            </div>
        </div>

        {{-- 7. FOOTER OTENTIKASI SISTEM --}}
        <div class="border-t border-slate-200 pt-2 text-[9.5px] text-slate-400 flex items-center justify-between">
            <span>Dokumen resmi diterbitkan otomatis oleh <strong>RansumKu Smart Poultry Farm System</strong>.</span>
            <span>Verifikasi: {{ strtoupper(substr(md5($farm->id . $farm->nama . now()->toDateString()), 0, 10)) }}</span>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // 1. Modal Pratinjau & Cetak Dokumen Resmi A4
    const modalLaporan = document.getElementById('modal-laporan-resmi');
    const btnsBukaLaporan = document.querySelectorAll('#btn-buka-laporan-modal, [data-buka-laporan-modal]');
    const btnTutupLaporan = document.getElementById('btn-tutup-laporan-modal');
    const btnCetakDokumen = document.getElementById('btn-cetak-dokumen');

    function bukaModalLaporan() {
        if (modalLaporan) {
            modalLaporan.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }
    }

    function tutupModalLaporan() {
        if (modalLaporan) {
            modalLaporan.classList.add('hidden');
            document.body.style.overflow = '';
        }
    }

    btnsBukaLaporan.forEach(btn => btn.addEventListener('click', bukaModalLaporan));
    if (btnTutupLaporan) {
        btnTutupLaporan.addEventListener('click', tutupModalLaporan);
    }
    if (btnCetakDokumen) {
        btnCetakDokumen.addEventListener('click', function () {
            window.print();
        });
    }
    if (modalLaporan) {
        modalLaporan.addEventListener('click', function (e) {
            if (e.target === modalLaporan) {
                tutupModalLaporan();
            }
        });
    }
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && modalLaporan && !modalLaporan.classList.contains('hidden')) {
            tutupModalLaporan();
        }
    });

    // 2. Salin Laporan Sistem Menyeluruh ke WhatsApp / Clipboard
    const btnsCopyWa = document.querySelectorAll('#btn-copy-wa, [data-copy-wa]');
    btnsCopyWa.forEach(btn => {
        btn.addEventListener('click', function () {
            const text = `📊 *LAPORAN SISTEM TERPADU - RANSUMKU*\n`
                + `🏠 Kandang: {{ $farm->nama }} ({{ number_format($jumlahAyam, 0, ',', '.') }} ekor · Mg-{{ $farm->umurMinggu() }})\n`
                + `🗓️ Periode: {{ $rentangLabel }}\n`
                + `━━━━━━━━━━━━━━━━━━━━━━━\n`
                + `🥚 *PRODUKSI & KEUANGAN*\n`
                + `• Total Telur: {{ Fmt::num($ini['telur'], 0) }} btr ({{ Fmt::num($ini['telur_kg'], 1) }} kg)\n`
                + `• HDP & FCR: {{ Fmt::num($ini['hdp_rata'], 1) }}% · FCR {{ Fmt::num($ini['fcr_rata'], 2) }}\n`
                + `• Konsumsi Pakan: {{ Fmt::num($ini['pakan'], 1) }} kg ({{ Fmt::num($ini['gram_per_ekor'], 1) }} g/ekor/hr)\n`
                + `• Ayam Mati: {{ $ini['mati'] }} ekor\n`
                + `• Est. Omzet Telur: {{ Fmt::rp($ini['pendapatan_telur']) }}\n`
                + `• Est. Biaya Pakan: {{ Fmt::rp($ini['biaya_pakan']) }}\n`
                + `• Est. Margin Bersih: {{ $ini['margin_bersih'] >= 0 ? '+' : '-' }}{{ Fmt::rp(abs($ini['margin_bersih'])) }}\n`
                + `━━━━━━━━━━━━━━━━━━━━━━━\n`
                + `🌾 *RANSUM & STOK GUDANG*\n`
                + `• Ransum Aktif: {{ $ransumAktif ? $ransumAktif->namaStandar() . ' (' . Fmt::rp($ransumAktif->biaya_per_kg) . '/kg)' : 'Belum disusun' }}\n`
                + `• Total Stok Gudang: {{ Fmt::num($ringkasanGudang['total_stok_kg'], 1) }} kg (Ketahanan ±{{ Fmt::num($ringkasanGudang['estimasi_ketahanan_hari'], 1) }} hari)\n`
                + `• Bahan Menipis: {{ $ringkasanGudang['stok_menipis_count'] }} bahan\n`
                + `━━━━━━━━━━━━━━━━━━━━━━━\n`
                + `🌡️ *MIKROKLIMAT IOT & PERINGATAN*\n`
                + `• Iklim: {{ Fmt::num($cuacaMikro['suhu'] ?? 0, 1) }}°C · RH {{ Fmt::num($cuacaMikro['kelembaban'] ?? 0, 0) }}% · THI {{ Fmt::num($cuacaMikro['thi'] ?? 0, 1) }} ({{ $cuacaMikro['thi_kategori_singkat'] ?? 'Normal' }})\n`
                + `• Kontroler IoT: Mode {{ strtoupper($ringkasanIot['mode_kendali']) }} ({{ $ringkasanIot['aktif_count'] }}/4 aktuator aktif)\n`
                + `• Peringatan Anomali Aktif: {{ $ringkasanAnomali['belum_dibaca'] }} temuan\n`
                + `━━━━━━━━━━━━━━━━━━━━━━━\n`
                + `_Diterbitkan otomatis oleh RansumKu Smart Poultry Farm_`;

            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(text).then(() => {
                    window.showToast('Laporan sistem menyeluruh berhasil disalin ke clipboard!', 'success', 'Berhasil Menyalin');
                }).catch(() => copyFallback(text));
            } else {
                copyFallback(text);
            }
        });
    });

    function copyFallback(text) {
        const ta = document.createElement('textarea');
        ta.value = text;
        ta.style.position = 'fixed';
        ta.style.opacity = '0';
        document.body.appendChild(ta);
        ta.select();
        try {
            document.execCommand('copy');
            window.showToast('Laporan sistem menyeluruh berhasil disalin ke clipboard!', 'success', 'Berhasil Menyalin');
        } catch (e) {
            window.showToast('Gagal menyalin teks laporan.', 'error', 'Gagal Menyalin');
        }
        document.body.removeChild(ta);
    }
});
</script>
@endsection
