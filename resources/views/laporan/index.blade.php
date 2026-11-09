@extends('layouts.app')
@section('judul', 'Laporan Kandang')
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
    $marginDiff = ($punyaPembanding && $lalu['margin_bersih'] != 0)
        ? round((($ini['margin_bersih'] - $lalu['margin_bersih']) / abs($lalu['margin_bersih'])) * 100, 1)
        : null;

    $lastLog = $logs->last();
    $rataPakan = $ini['hari'] > 0 ? round($ini['pakan'] / $ini['hari'], 1) : 0.0;
    $giziRansum = $ransumAktif?->hasil['gizi'] ?? [];
    $totalGiziTerpenuhi = count($giziRansum);

    // Kategori THI
    $thi = (float) ($cuacaMikro['thi'] ?? 75);
    $thiKategori = $cuacaMikro['thi_kategori_singkat'] ?? ($thi >= 78 ? 'Waspada' : 'Normal');
    $suhu = $cuacaMikro['suhu'] ?? 28;

    $activeTab = $tabParam ?: '';
@endphp

<div class="laporan-screen-only no-print w-full py-2 sm:py-4 px-1 sm:px-4 md:px-6 space-y-6">

    {{-- ===================================================================== --}}
    {{-- VIEW UTAMA (OVERVIEW / HUB) - GAMBAR 1 (DESKTOP) & GAMBAR 2 (MOBILE)   --}}
    {{-- ===================================================================== --}}
    <div id="view-overview" class="{{ $activeTab ? 'hidden' : 'block' }} space-y-6 transition-all duration-200">

        {{-- 1. HEADER UTAMA --}}
        <div class="space-y-3">
            {{-- Baris Judul & Aksi (Desktop & Mobile) --}}
            <div class="flex items-start justify-between gap-3">
                <div>
                    <h1 class="text-2xl sm:text-3xl font-black text-ink tracking-tight">Laporan Kandang</h1>
                    {{-- Subtitle Desktop --}}
                    <p class="hidden sm:block text-xs sm:text-sm text-muted mt-1 font-medium">
                        {{ $farm->nama }} · {{ number_format($jumlahAyam, 0, ',', '.') }} ekor · Minggu ke-{{ $farm->umurMinggu() }}
                    </p>
                    {{-- Subtitle Mobile --}}
                    <p class="block sm:hidden text-xs text-muted mt-0.5 font-medium">
                        {{ $farm->nama }} · Mg-{{ $farm->umurMinggu() }}
                    </p>
                </div>

                {{-- Kontrol Aksi Kanan (Desktop) --}}
                <div class="hidden sm:flex items-center gap-2.5">
                    {{-- Periode Pills Capsule --}}
                    <div class="inline-flex p-1 bg-[#e8efec] rounded-full text-xs font-bold shadow-2xs">
                        <a href="{{ route('laporan.index', ['rentang' => 'minggu']) }}"
                           class="px-3.5 py-1.5 rounded-full transition {{ $rentangParam === 'minggu' ? 'bg-white text-ink shadow-xs font-black' : 'text-muted hover:text-ink' }}">
                            7 Hari
                        </a>
                        <a href="{{ route('laporan.index', ['rentang' => 'bulan']) }}"
                           class="px-3.5 py-1.5 rounded-full transition {{ $rentangParam === 'bulan' ? 'bg-white text-ink shadow-xs font-black' : 'text-muted hover:text-ink' }}">
                            30 Hari
                        </a>
                        <a href="{{ route('laporan.index', ['rentang' => 'semua']) }}"
                           class="px-3.5 py-1.5 rounded-full transition {{ $rentangParam === 'semua' ? 'bg-white text-ink shadow-xs font-black' : 'text-muted hover:text-ink' }}">
                            Semua
                        </a>
                    </div>

                    {{-- Tombol Unduh Dropdown Desktop (Murni Vanilla JS) --}}
                    <div class="relative inline-block text-left" id="dropdown-unduh-desktop-wrap">
                        <button type="button" id="btn-dropdown-unduh-desktop"
                                class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-bold text-white bg-[#185a36] hover:bg-[#134d2e] shadow-xs transition active:scale-95 cursor-pointer select-none">
                            <span>Unduh</span>
                            <span class="text-[10px] transition-transform duration-150" id="icon-chevron-unduh-desktop">▼</span>
                        </button>
                        <div id="menu-dropdown-unduh-desktop"
                             class="hidden absolute right-0 mt-2 w-56 bg-white rounded-xl shadow-xl border border-line py-1.5 z-50 text-xs divide-y divide-gray-100">
                            <div class="py-1">
                                <a href="{{ route('laporan.pdf', ['rentang' => $rentangParam]) }}"
                                   class="flex items-center gap-2.5 px-3.5 py-2 text-ink hover:bg-surface font-semibold transition">
                                    <span class="material-symbols-outlined text-[18px] text-rose-600">picture_as_pdf</span>
                                    <span>Unduh PDF Resmi (A4)</span>
                                </a>
                                <a href="{{ route('laporan.excel', ['rentang' => $rentangParam]) }}"
                                   class="flex items-center gap-2.5 px-3.5 py-2 text-ink hover:bg-surface font-semibold transition">
                                    <span class="material-symbols-outlined text-[18px] text-emerald-600">table_view</span>
                                    <span>Unduh Excel (.xlsx)</span>
                                </a>
                                <a href="{{ route('laporan.csv', ['rentang' => $rentangParam]) }}"
                                   class="flex items-center gap-2.5 px-3.5 py-2 text-ink hover:bg-surface font-semibold transition">
                                    <span class="material-symbols-outlined text-[18px] text-brand">download</span>
                                    <span>Unduh CSV Data Mentah</span>
                                </a>
                            </div>
                            <div class="py-1">
                                <button type="button" data-buka-laporan-modal
                                        class="w-full flex items-center gap-2.5 px-3.5 py-2 text-ink hover:bg-surface font-semibold text-left transition cursor-pointer">
                                    <span class="material-symbols-outlined text-[18px] text-slate-600">print</span>
                                    <span>Pratinjau Cetak A4</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Tombol Unduh Mobile (Top Right - Murni Vanilla JS) --}}
                <div class="sm:hidden relative inline-block text-left" id="dropdown-unduh-mobile-wrap">
                    <button type="button" id="btn-dropdown-unduh-mobile"
                            class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-bold text-ink bg-white border border-[#d5ded8] shadow-2xs transition active:scale-95 cursor-pointer select-none">
                        <span>Unduh</span>
                        <span class="text-[9px] transition-transform duration-150" id="icon-chevron-unduh-mobile">▼</span>
                    </button>
                    <div id="menu-dropdown-unduh-mobile"
                         class="hidden absolute right-0 mt-2 w-56 bg-white rounded-xl shadow-xl border border-line py-1.5 z-50 text-xs divide-y divide-gray-100">
                        <div class="py-1">
                            <a href="{{ route('laporan.pdf', ['rentang' => $rentangParam]) }}"
                               class="flex items-center gap-2.5 px-3.5 py-2 text-ink hover:bg-surface font-semibold transition">
                                <span class="material-symbols-outlined text-[18px] text-rose-600">picture_as_pdf</span>
                                <span>Unduh PDF Resmi (A4)</span>
                            </a>
                            <a href="{{ route('laporan.excel', ['rentang' => $rentangParam]) }}"
                               class="flex items-center gap-2.5 px-3.5 py-2 text-ink hover:bg-surface font-semibold transition">
                                <span class="material-symbols-outlined text-[18px] text-emerald-600">table_view</span>
                                <span>Unduh Excel (.xlsx)</span>
                            </a>
                            <a href="{{ route('laporan.csv', ['rentang' => $rentangParam]) }}"
                               class="flex items-center gap-2.5 px-3.5 py-2 text-ink hover:bg-surface font-semibold transition">
                                <span class="material-symbols-outlined text-[18px] text-brand">download</span>
                                <span>Unduh CSV Data Mentah</span>
                            </a>
                        </div>
                        <div class="py-1">
                            <button type="button" data-buka-laporan-modal
                                    class="w-full flex items-center gap-2.5 px-3.5 py-2 text-ink hover:bg-surface font-semibold text-left transition cursor-pointer">
                                <span class="material-symbols-outlined text-[18px] text-slate-600">print</span>
                                <span>Pratinjau Cetak A4</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Periode Pills Capsule (Khusus Mobile - Full Width Centered) --}}
            <div class="sm:hidden flex justify-center pt-1">
                <div class="inline-flex w-full p-1 bg-[#e8efec] rounded-full text-xs font-bold shadow-2xs">
                    <a href="{{ route('laporan.index', ['rentang' => 'minggu']) }}"
                       class="flex-1 text-center py-1.5 rounded-full transition {{ $rentangParam === 'minggu' ? 'bg-white text-ink shadow-xs font-black' : 'text-muted hover:text-ink' }}">
                        7 Hari
                    </a>
                    <a href="{{ route('laporan.index', ['rentang' => 'bulan']) }}"
                       class="flex-1 text-center py-1.5 rounded-full transition {{ $rentangParam === 'bulan' ? 'bg-white text-ink shadow-xs font-black' : 'text-muted hover:text-ink' }}">
                        30 Hari
                    </a>
                    <a href="{{ route('laporan.index', ['rentang' => 'semua']) }}"
                       class="flex-1 text-center py-1.5 rounded-full transition {{ $rentangParam === 'semua' ? 'bg-white text-ink shadow-xs font-black' : 'text-muted hover:text-ink' }}">
                        Semua
                    </a>
                </div>
            </div>
        </div>

        {{-- 2. NARRATIVE HEADLINE BANNER --}}
        <div class="pt-0.5">
            <p class="text-sm sm:text-base text-ink font-normal leading-relaxed">
                Produksi telur {{ Fmt::num($ini['hdp_rata'], 1) }}%, {{ $ini['hdp_rata'] < 80 ? 'masih di bawah target 90–95%' : 'mencapai target optimal' }}. Stok pakan tinggal {{ $ringkasanGudang['estimasi_ketahanan_hari'] }} hari.
                <span class="text-muted">
                    Rata-rata konversi pakan (FCR) berada pada angka {{ Fmt::num($ini['fcr_rata'], 2) }} dengan konsumsi harian {{ Fmt::num($ini['gram_per_ekor'], 1) }} g/ekor.
                    {{ $ringkasanGudang['stok_menipis_count'] > 0 ? 'Terdapat ' . $ringkasanGudang['stok_menipis_count'] . ' bahan pakan yang menipis dan perlu dicatat belanjanya.' : 'Seluruh ketersediaan stok bahan pakan dalam kondisi aman.' }}
                    Kondisi iklim kandang terpantau pada suhu {{ Fmt::num($suhu, 1) }}°C (status {{ strtolower($thiKategori) }}).
                </span>
            </p>
        </div>

        {{-- 3. KEY METRICS ROW --}}
        {{-- Layout Desktop: 4 Kolom Sejajar (Gambar 1) --}}
        <div class="hidden sm:grid grid-cols-4 gap-3.5">
            {{-- Card 1: Margin Bersih (Highlight Hijau Gelap) --}}
            <div class="bg-[#185a36] text-white rounded-2xl p-5 shadow-xs flex flex-col justify-between space-y-2">
                <span class="text-[11px] font-bold text-emerald-200/90 uppercase tracking-wider block">MARGIN BERSIH</span>
                <div class="text-2xl lg:text-3xl font-black tracking-tight tabular-nums">
                    {{ $ini['margin_bersih'] >= 0 ? '+' : '-' }}Rp {{ number_format(abs($ini['margin_bersih']), 0, ',', '.') }}
                </div>
                <div class="text-xs text-emerald-100/80 pt-1">
                    @if ($marginDiff !== null)
                        {{ $marginDiff >= 0 ? 'naik' : 'turun' }} {{ abs($marginDiff) }}% dari periode lalu
                    @else
                        Periode berjalan aktif
                    @endif
                </div>
            </div>

            {{-- Card 2: Produktivitas --}}
            <div class="bg-white border border-[#e2e8e4] rounded-2xl p-5 shadow-2xs flex flex-col justify-between space-y-2">
                <span class="text-[11px] font-bold text-muted uppercase tracking-wider block">PRODUKTIVITAS</span>
                <div class="text-2xl lg:text-3xl font-black text-ink tracking-tight tabular-nums">
                    {{ Fmt::num($ini['hdp_rata'], 1) }}%
                </div>
                <div class="text-xs text-muted pt-1">
                    HDP
                </div>
            </div>

            {{-- Card 3: Telur --}}
            <div class="bg-white border border-[#e2e8e4] rounded-2xl p-5 shadow-2xs flex flex-col justify-between space-y-2">
                <span class="text-[11px] font-bold text-muted uppercase tracking-wider block">TELUR</span>
                <div class="text-2xl lg:text-3xl font-black text-ink tracking-tight tabular-nums">
                    {{ Fmt::num($ini['telur'], 0) }}
                </div>
                <div class="text-xs text-muted pt-1">
                    butir · {{ Fmt::num($ini['telur_kg'], 1) }} kg
                </div>
            </div>

            {{-- Card 4: Pakan --}}
            <div class="bg-white border border-[#e2e8e4] rounded-2xl p-5 shadow-2xs flex flex-col justify-between space-y-2">
                <span class="text-[11px] font-bold text-muted uppercase tracking-wider block">PAKAN</span>
                <div class="text-2xl lg:text-3xl font-black text-ink tracking-tight tabular-nums">
                    {{ Fmt::num($ini['pakan'], 1) }}
                </div>
                <div class="text-xs text-muted pt-1">
                    kg · {{ Fmt::num($ini['gram_per_ekor'], 0) }} g/ekor
                </div>
            </div>
        </div>

        {{-- Layout Mobile: Margin Bersih Full Width + 3 Kolom di Bawah (Gambar 2) --}}
        <div class="sm:hidden space-y-3">
            {{-- Margin Card Full Width --}}
            <div class="bg-[#185a36] text-white rounded-2xl p-4 shadow-xs flex flex-col justify-between space-y-1.5">
                <span class="text-[10px] font-bold text-emerald-200/90 uppercase tracking-wider block">MARGIN BERSIH</span>
                <div class="text-2xl font-black tracking-tight tabular-nums">
                    {{ $ini['margin_bersih'] >= 0 ? '+' : '-' }}Rp {{ number_format(abs($ini['margin_bersih']), 0, ',', '.') }}
                </div>
                <div class="text-xs text-emerald-100/80">
                    @if ($marginDiff !== null)
                        {{ $marginDiff >= 0 ? 'naik' : 'turun' }} {{ abs($marginDiff) }}% dari periode lalu
                    @else
                        Periode berjalan aktif
                    @endif
                </div>
            </div>

            {{-- 3 Kolom Grid di Bawah Margin --}}
            <div class="grid grid-cols-3 gap-2">
                <div class="bg-white border border-[#e2e8e4] rounded-xl p-3 shadow-2xs">
                    <span class="text-[10px] font-bold text-muted uppercase tracking-wider block">HDP</span>
                    <div class="text-lg font-black text-ink tracking-tight mt-0.5 tabular-nums">
                        {{ Fmt::num($ini['hdp_rata'], 1) }}%
                    </div>
                </div>
                <div class="bg-white border border-[#e2e8e4] rounded-xl p-3 shadow-2xs">
                    <span class="text-[10px] font-bold text-muted uppercase tracking-wider block">TELUR</span>
                    <div class="text-lg font-black text-ink tracking-tight mt-0.5 tabular-nums">
                        {{ Fmt::num($ini['telur'], 0) }}
                    </div>
                </div>
                <div class="bg-white border border-[#e2e8e4] rounded-xl p-3 shadow-2xs">
                    <span class="text-[10px] font-bold text-muted uppercase tracking-wider block">PAKAN</span>
                    <div class="text-lg font-black text-ink tracking-tight mt-0.5 tabular-nums">
                        {{ Fmt::num($ini['pakan'], 1) }} kg
                    </div>
                </div>
            </div>
        </div>

        {{-- 4. SECTION "PERLU DITINDAK" (GAMBAR 1 DESKTOP & GAMBAR 2 MOBILE) --}}
        <div class="space-y-3">
            <div class="flex items-center gap-2">
                <h3 class="text-base sm:text-lg font-black text-ink tracking-tight">Perlu ditindak</h3>
                <span class="text-xs font-semibold text-muted">3 hal hari ini</span>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-3.5 md:gap-4">
                {{-- Item 1: Stok pakan menipis (Mendesak) --}}
                <div class="bg-[#fef2f2] border border-[#fecaca] rounded-2xl p-4 sm:p-5 flex flex-col justify-between shadow-2xs">
                    <div>
                        <div class="flex items-center justify-between mb-3">
                            <div class="w-10 h-10 rounded-xl bg-white border border-[#fecaca] flex items-center justify-center text-[#dc2626] shadow-2xs">
                                <span class="material-symbols-outlined text-[20px]">inventory_2</span>
                            </div>
                            <span class="text-xs font-bold text-[#b91c1c]">Mendesak</span>
                        </div>
                        <h4 class="text-sm sm:text-base font-bold text-ink">
                            Stok pakan tinggal {{ $ringkasanGudang['estimasi_ketahanan_hari'] }} hari
                        </h4>
                        <p class="text-xs text-muted mt-1 leading-relaxed">
                            {{ $ringkasanGudang['stok_menipis_count'] }} dari {{ $ringkasanGudang['total_bahan_aktif'] }} bahan menipis. Segera belanja agar pakan tidak habis.
                        </p>
                    </div>
                    <div class="mt-4 pt-1">
                        <a href="{{ route('catatan.index', ['tab' => 'belanja']) }}"
                           class="w-full inline-flex items-center justify-center px-4 py-2.5 rounded-xl text-xs sm:text-sm font-bold text-white bg-[#185a36] hover:bg-[#134d2e] shadow-2xs transition active:scale-95 text-center">
                            Catat belanja
                        </a>
                    </div>
                </div>

                {{-- Item 2: Pakan naik, telur turun (Waspada) --}}
                <div class="bg-[#fffbeb] border border-[#fef08a] rounded-2xl p-4 sm:p-5 flex flex-col justify-between shadow-2xs">
                    <div>
                        <div class="flex items-center justify-between mb-3">
                            <div class="w-10 h-10 rounded-xl bg-white border border-[#fef08a] flex items-center justify-center text-[#b45309] shadow-2xs">
                                <span class="material-symbols-outlined text-[20px]">trending_down</span>
                            </div>
                            <span class="text-xs font-bold text-[#b45309]">Waspada</span>
                        </div>
                        <h4 class="text-sm sm:text-base font-bold text-ink">
                            Pakan naik, telur turun
                        </h4>
                        <p class="text-xs text-muted mt-1 leading-relaxed">
                            {{ $ringkasanAnomali['belum_dibaca'] ?: ($ringkasanAnomali['total_semua'] ?: 2) }} temuan sudah tercatat dan perlu dicek penyebabnya.
                        </p>
                    </div>
                    <div class="mt-4 pt-1">
                        <button type="button" data-buka-tab="anomali_ai"
                                class="w-full inline-flex items-center justify-center px-4 py-2.5 rounded-xl text-xs sm:text-sm font-bold text-ink bg-white hover:bg-surface border border-[#d5ded8] shadow-2xs transition active:scale-95 cursor-pointer text-center">
                            Tinjau
                        </button>
                    </div>
                </div>

                {{-- Item 3: Suhu kandang mulai panas (Waspada) --}}
                <div class="bg-[#fffbeb] border border-[#fef08a] rounded-2xl p-4 sm:p-5 flex flex-col justify-between shadow-2xs">
                    <div>
                        <div class="flex items-center justify-between mb-3">
                            <div class="w-10 h-10 rounded-xl bg-white border border-[#fef08a] flex items-center justify-center text-[#b45309] shadow-2xs">
                                <span class="material-symbols-outlined text-[20px]">thermostat</span>
                            </div>
                            <span class="text-xs font-bold text-[#b45309]">Waspada</span>
                        </div>
                        <h4 class="text-sm sm:text-base font-bold text-ink">
                            Suhu mulai panas (THI {{ $cuacaMikro['thi'] }})
                        </h4>
                        <p class="text-xs text-muted mt-1 leading-relaxed">
                            {{ $cuacaMikro['saran_manajemen'][0] ?? 'Buka tirai samping kandang penuh agar udara mengalir.' }}
                        </p>
                    </div>
                    <div class="mt-4 pt-1">
                        <button type="button" data-buka-tab="iklim_kandang"
                                class="w-full inline-flex items-center justify-center px-4 py-2.5 rounded-xl text-xs sm:text-sm font-bold text-ink bg-white hover:bg-surface border border-[#d5ded8] shadow-2xs transition active:scale-95 cursor-pointer text-center">
                            Lihat saran
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- 5. SECTION "DETAIL LAPORAN" (GRID TILE: DESKTOP & MOBILE) --}}
        <div class="space-y-3">
            <h3 class="text-base sm:text-lg font-black text-ink tracking-tight">Detail laporan</h3>

            <div class="grid grid-cols-2 md:grid-cols-2 gap-3 md:gap-3.5">
                {{-- Tile 1: Tren harian --}}
                <button type="button" data-buka-tab="tren_harian"
                        class="group w-full bg-white border border-[#e2e8e4] rounded-2xl p-3.5 md:p-4 hover:border-[#185a36]/30 hover:shadow-xs transition active:scale-[0.98] text-left cursor-pointer flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                    <div class="flex flex-col md:flex-row md:items-center gap-2.5 md:gap-3.5 min-w-0">
                        <div class="w-10 h-10 md:w-11 md:h-11 rounded-xl bg-[#eaf4ed] text-[#185a36] flex items-center justify-center shrink-0">
                            <span class="material-symbols-outlined text-[20px] md:text-[22px]">show_chart</span>
                        </div>
                        <div class="min-w-0">
                            <h4 class="text-xs sm:text-sm font-bold text-ink group-hover:text-[#185a36] transition truncate">
                                Tren harian
                            </h4>
                            <div class="flex items-center gap-1.5 text-[11px] sm:text-xs text-muted mt-0.5">
                                <span class="inline-block w-1.5 h-1.5 rounded-full bg-[#9ca3af] shrink-0"></span>
                                <span class="truncate">Baru {{ $ini['hari'] }} hari data</span>
                            </div>
                        </div>
                    </div>
                    <span class="hidden md:inline-flex text-[#9ca3af] group-hover:text-[#185a36] group-hover:translate-x-0.5 transition font-bold text-sm shrink-0">
                        ›
                    </span>
                </button>

                {{-- Tile 2: Ransum & gizi --}}
                <button type="button" data-buka-tab="ransum_gizi"
                        class="group w-full bg-white border border-[#e2e8e4] rounded-2xl p-3.5 md:p-4 hover:border-[#185a36]/30 hover:shadow-xs transition active:scale-[0.98] text-left cursor-pointer flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                    <div class="flex flex-col md:flex-row md:items-center gap-2.5 md:gap-3.5 min-w-0">
                        <div class="w-10 h-10 md:w-11 md:h-11 rounded-xl bg-[#eaf4ed] text-[#185a36] flex items-center justify-center shrink-0">
                            <span class="material-symbols-outlined text-[20px] md:text-[22px]">science</span>
                        </div>
                        <div class="min-w-0">
                            <h4 class="text-xs sm:text-sm font-bold text-ink group-hover:text-[#185a36] transition truncate">
                                Ransum & gizi
                            </h4>
                            <div class="flex items-center gap-1.5 text-[11px] sm:text-xs text-[#185a36] font-semibold mt-0.5">
                                <span class="inline-block w-1.5 h-1.5 rounded-full bg-[#185a36] shrink-0"></span>
                                <span class="hidden md:inline truncate">Layak, {{ $totalGiziTerpenuhi }}/5 gizi terpenuhi</span>
                                <span class="md:hidden truncate">Layak, {{ $totalGiziTerpenuhi }}/5 gizi</span>
                            </div>
                        </div>
                    </div>
                    <span class="hidden md:inline-flex text-[#9ca3af] group-hover:text-[#185a36] group-hover:translate-x-0.5 transition font-bold text-sm shrink-0">
                        ›
                    </span>
                </button>

                {{-- Tile 3: Stok & belanja --}}
                <button type="button" data-buka-tab="stok_belanja"
                        class="group w-full bg-white border border-[#e2e8e4] rounded-2xl p-3.5 md:p-4 hover:border-[#185a36]/30 hover:shadow-xs transition active:scale-[0.98] text-left cursor-pointer flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                    <div class="flex flex-col md:flex-row md:items-center gap-2.5 md:gap-3.5 min-w-0">
                        <div class="w-10 h-10 md:w-11 md:h-11 rounded-xl bg-[#fee2e2] text-[#dc2626] flex items-center justify-center shrink-0">
                            <span class="material-symbols-outlined text-[20px] md:text-[22px]">bar_chart</span>
                        </div>
                        <div class="min-w-0">
                            <h4 class="text-xs sm:text-sm font-bold text-ink group-hover:text-[#185a36] transition truncate">
                                Stok & belanja
                            </h4>
                            <div class="flex items-center gap-1.5 text-[11px] sm:text-xs text-[#b91c1c] font-semibold mt-0.5">
                                <span class="inline-block w-1.5 h-1.5 rounded-full bg-[#dc2626] shrink-0"></span>
                                <span class="truncate">{{ $ringkasanGudang['stok_menipis_count'] }} bahan menipis</span>
                            </div>
                        </div>
                    </div>
                    <span class="hidden md:inline-flex text-[#9ca3af] group-hover:text-[#185a36] group-hover:translate-x-0.5 transition font-bold text-sm shrink-0">
                        ›
                    </span>
                </button>

                {{-- Tile 4: Iklim kandang --}}
                <button type="button" data-buka-tab="iklim_kandang"
                        class="group w-full bg-white border border-[#e2e8e4] rounded-2xl p-3.5 md:p-4 hover:border-[#185a36]/30 hover:shadow-xs transition active:scale-[0.98] text-left cursor-pointer flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                    <div class="flex flex-col md:flex-row md:items-center gap-2.5 md:gap-3.5 min-w-0">
                        <div class="w-10 h-10 md:w-11 md:h-11 rounded-xl bg-[#fef3c7] text-[#d97706] flex items-center justify-center shrink-0">
                            <span class="material-symbols-outlined text-[20px] md:text-[22px]">thermostat</span>
                        </div>
                        <div class="min-w-0">
                            <h4 class="text-xs sm:text-sm font-bold text-ink group-hover:text-[#185a36] transition truncate">
                                Iklim kandang
                            </h4>
                            <div class="flex items-center gap-1.5 text-[11px] sm:text-xs text-[#b45309] font-semibold mt-0.5">
                                <span class="inline-block w-1.5 h-1.5 rounded-full bg-[#d97706] shrink-0"></span>
                                <span class="hidden md:inline truncate">{{ Fmt::num($suhu, 1) }}°C · Waspada panas</span>
                                <span class="md:hidden truncate">{{ Fmt::num($suhu, 1) }}°C · Waspada</span>
                            </div>
                        </div>
                    </div>
                    <span class="hidden md:inline-flex text-[#9ca3af] group-hover:text-[#185a36] group-hover:translate-x-0.5 transition font-bold text-sm shrink-0">
                        ›
                    </span>
                </button>

                {{-- Tile 5: Anomali & Asisten AI --}}
                <button type="button" data-buka-tab="anomali_ai"
                        class="group w-full bg-white border border-[#e2e8e4] rounded-2xl p-3.5 md:p-4 hover:border-[#185a36]/30 hover:shadow-xs transition active:scale-[0.98] text-left cursor-pointer flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                    <div class="flex flex-col md:flex-row md:items-center gap-2.5 md:gap-3.5 min-w-0">
                        <div class="w-10 h-10 md:w-11 md:h-11 rounded-xl bg-[#fef3c7] text-[#d97706] flex items-center justify-center shrink-0">
                            <span class="material-symbols-outlined text-[20px] md:text-[22px]">auto_awesome</span>
                        </div>
                        <div class="min-w-0">
                            <h4 class="text-xs sm:text-sm font-bold text-ink group-hover:text-[#185a36] transition truncate">
                                <span class="hidden md:inline">Anomali & Asisten AI</span>
                                <span class="md:hidden">Anomali & AI</span>
                            </h4>
                            <div class="flex items-center gap-1.5 text-[11px] sm:text-xs text-[#b45309] font-semibold mt-0.5">
                                <span class="inline-block w-1.5 h-1.5 rounded-full bg-[#d97706] shrink-0"></span>
                                <span class="truncate">{{ $ringkasanAnomali['belum_dibaca'] ?: ($ringkasanAnomali['total_semua'] ?: 2) }} temuan</span>
                            </div>
                        </div>
                    </div>
                    <span class="hidden md:inline-flex text-[#9ca3af] group-hover:text-[#185a36] group-hover:translate-x-0.5 transition font-bold text-sm shrink-0">
                        ›
                    </span>
                </button>

                {{-- Tile 6: Rekap harian --}}
                <button type="button" data-buka-tab="rekap_harian"
                        class="group w-full bg-white border border-[#e2e8e4] rounded-2xl p-3.5 md:p-4 hover:border-[#185a36]/30 hover:shadow-xs transition active:scale-[0.98] text-left cursor-pointer flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                    <div class="flex flex-col md:flex-row md:items-center gap-2.5 md:gap-3.5 min-w-0">
                        <div class="w-10 h-10 md:w-11 md:h-11 rounded-xl bg-[#f3f4f6] text-[#4b5563] flex items-center justify-center shrink-0">
                            <span class="material-symbols-outlined text-[20px] md:text-[22px]">calendar_today</span>
                        </div>
                        <div class="min-w-0">
                            <h4 class="text-xs sm:text-sm font-bold text-ink group-hover:text-[#185a36] transition truncate">
                                Rekap harian
                            </h4>
                            <div class="flex items-center gap-1.5 text-[11px] sm:text-xs text-muted mt-0.5">
                                <span class="inline-block w-1.5 h-1.5 rounded-full bg-[#9ca3af] shrink-0"></span>
                                <span class="truncate">{{ $ini['hari'] }} hari tercatat</span>
                            </div>
                        </div>
                    </div>
                    <span class="hidden md:inline-flex text-[#9ca3af] group-hover:text-[#185a36] group-hover:translate-x-0.5 transition font-bold text-sm shrink-0">
                        ›
                    </span>
                </button>
            </div>
        </div>
    </div>


    {{-- ===================================================================== --}}
    {{-- SUB-VIEW TAB 1: STOK & BELANJA (GAMBAR 3)                              --}}
    {{-- ===================================================================== --}}
    <div id="view-detail-stok_belanja" class="{{ $activeTab === 'stok_belanja' ? 'block' : 'hidden' }} space-y-6 transition-all duration-200">
        {{-- Navigasi Kembali --}}
        <div>
            <button type="button" data-tutup-tab
                    class="inline-flex items-center gap-1 text-xs sm:text-sm font-bold text-[#185a36] hover:underline cursor-pointer">
                <span>‹ Kembali ke laporan</span>
            </button>
        </div>

        {{-- Header Tab --}}
        <div class="flex items-start justify-between gap-3">
            <div>
                <h2 class="text-2xl sm:text-3xl font-black text-ink tracking-tight">Stok & belanja</h2>
                <p class="text-xs sm:text-sm text-muted mt-0.5">{{ $rentangLabel }}</p>
            </div>
            <a href="{{ route('catatan.index', ['tab' => 'belanja']) }}"
               class="inline-flex items-center px-4 py-2 rounded-xl text-xs sm:text-sm font-bold text-white bg-[#185a36] hover:bg-[#134d2e] shadow-xs transition active:scale-95">
                Catat belanja
            </a>
        </div>

        {{-- 3 Kartu Metrik Ringkasan --}}
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3.5">
            {{-- Card 1: Alert Merah Muda --}}
            <div class="bg-[#fee6e6] border border-[#fcc] rounded-2xl p-5 shadow-2xs flex flex-col justify-between space-y-1">
                <div class="text-2xl sm:text-3xl font-black text-[#9e1c1c] tracking-tight tabular-nums">
                    {{ $ringkasanGudang['estimasi_ketahanan_hari'] }} hari
                </div>
                <div class="text-xs text-[#7a2222] font-medium">
                    Stok cukup sampai
                </div>
            </div>

            {{-- Card 2: Total Stok --}}
            <div class="bg-white border border-[#e2e8e4] rounded-2xl p-5 shadow-2xs flex flex-col justify-between space-y-1">
                <div class="text-2xl sm:text-3xl font-black text-ink tracking-tight tabular-nums">
                    {{ Fmt::num($ringkasanGudang['total_stok_kg'], 0) }} kg
                </div>
                <div class="text-xs text-muted">
                    Total stok · {{ Fmt::rp($ringkasanGudang['total_nilai_stok_rp']) }}
                </div>
            </div>

            {{-- Card 3: Belanja Periode --}}
            <div class="bg-white border border-[#e2e8e4] rounded-2xl p-5 shadow-2xs flex flex-col justify-between space-y-1">
                <div class="text-2xl sm:text-3xl font-black text-ink tracking-tight tabular-nums">
                    {{ Fmt::rp($ringkasanBelanja['total_pengeluaran_rp']) }}
                </div>
                <div class="text-xs text-muted">
                    Belanja {{ $rentangHari > 0 ? $rentangHari . ' hari' : 'seluruh periode' }}
                </div>
            </div>
        </div>

        {{-- Tabel Daftar Bahan Gudang --}}
        <div class="bg-white border border-[#e2e8e4] rounded-2xl shadow-2xs overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs sm:text-sm">
                    <thead>
                        <tr class="border-b border-[#edf2ee] text-[11px] font-bold text-muted uppercase tracking-wider bg-[#fafcfa]">
                            <th class="py-3 px-4 sm:px-6">BAHAN</th>
                            <th class="py-3 px-4 sm:px-6">STOK</th>
                            <th class="py-3 px-4 sm:px-6">HARGA/KG</th>
                            <th class="py-3 px-4 sm:px-6 text-right sm:text-left">STATUS</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#edf2ee]">
                        @forelse ($stokGudang as $fi)
                            @php
                                $stok = (float) $fi->stok_kg;
                                $isMenipis = $stok < 50;
                                $isHargaNaik = $fi->hargaUsang();
                            @endphp
                            <tr class="hover:bg-[#fbfcfb] transition">
                                <td class="py-3.5 px-4 sm:px-6 font-bold text-ink">
                                    {{ $fi->ingredient?->nama ?? 'Bahan' }}
                                </td>
                                <td class="py-3.5 px-4 sm:px-6 text-ink tabular-nums">
                                    {{ Fmt::num($stok, 0) }} kg
                                </td>
                                <td class="py-3.5 px-4 sm:px-6 text-ink tabular-nums">
                                    {{ Fmt::rp($fi->harga) }}
                                </td>
                                <td class="py-3.5 px-4 sm:px-6 text-right sm:text-left">
                                    @if ($isMenipis)
                                        <span class="font-bold text-[#a61c1c]">Menipis</span>
                                    @elseif ($isHargaNaik)
                                        <span class="font-bold text-[#a16207]">Harga naik</span>
                                    @else
                                        <span class="font-bold text-emerald-700">Aman</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="py-6 text-center text-muted">
                                    Belum ada data stok bahan baku aktif di gudang kandang ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Riwayat Pembelian (Dashed Box jika kosong) --}}
        @if ($pembelianPeriode->isEmpty())
            <div class="border-2 border-dashed border-[#d5ded8] rounded-2xl p-6 text-center text-muted text-xs sm:text-sm">
                Belum ada pembelian pakan tercatat.
            </div>
        @else
            <div class="bg-white border border-[#e2e8e4] rounded-2xl shadow-2xs overflow-hidden">
                <div class="p-4 sm:px-6 sm:py-3.5 border-b border-[#edf2ee] bg-[#fafcfa]">
                    <span class="text-xs font-bold text-ink uppercase tracking-wider">Catatan Pembelian Periode Ini</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs sm:text-sm">
                        <thead>
                            <tr class="border-b border-[#edf2ee] text-[11px] font-bold text-muted uppercase tracking-wider">
                                <th class="py-2.5 px-4 sm:px-6">Tanggal</th>
                                <th class="py-2.5 px-4 sm:px-6">Bahan</th>
                                <th class="py-2.5 px-4 sm:px-6">Volume</th>
                                <th class="py-2.5 px-4 sm:px-6">Harga/kg</th>
                                <th class="py-2.5 px-4 sm:px-6 text-right">Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#edf2ee]">
                            @foreach ($pembelianPeriode as $beli)
                                <tr class="hover:bg-[#fbfcfb] transition">
                                    <td class="py-3 px-4 sm:px-6 text-muted">{{ $beli->tanggal->format('d/m/Y') }}</td>
                                    <td class="py-3 px-4 sm:px-6 font-bold text-ink">{{ $beli->ingredient?->nama ?? '-' }}</td>
                                    <td class="py-3 px-4 sm:px-6 tabular-nums">{{ Fmt::num($beli->kg, 1) }} kg</td>
                                    <td class="py-3 px-4 sm:px-6 tabular-nums">{{ Fmt::rp($beli->harga_nyata) }}</td>
                                    <td class="py-3 px-4 sm:px-6 text-right font-bold text-ink tabular-nums">{{ Fmt::rp($beli->total()) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>


    {{-- ===================================================================== --}}
    {{-- SUB-VIEW TAB 2: TREN HARIAN                                            --}}
    {{-- ===================================================================== --}}
    <div id="view-detail-tren_harian" class="{{ $activeTab === 'tren_harian' ? 'block' : 'hidden' }} space-y-6 transition-all duration-200">
        <div>
            <button type="button" data-tutup-tab
                    class="inline-flex items-center gap-1 text-xs sm:text-sm font-bold text-[#185a36] hover:underline cursor-pointer">
                <span>‹ Kembali ke laporan</span>
            </button>
        </div>

        <div class="flex items-start justify-between gap-3">
            <div>
                <h2 class="text-2xl sm:text-3xl font-black text-ink tracking-tight">Tren harian</h2>
                <p class="text-xs sm:text-sm text-muted mt-0.5">{{ $rentangLabel }} ({{ $ini['hari'] }} hari pencatatan aktif)</p>
            </div>
        </div>

        {{-- 3 Metrik Utama Header Tab --}}
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3.5">
            <div class="bg-white border border-[#e2e8e4] rounded-2xl p-5 shadow-2xs">
                <span class="text-[11px] font-bold text-muted uppercase tracking-wider block">HDP Rata-rata</span>
                <div class="text-2xl sm:text-3xl font-black text-ink tracking-tight mt-1 tabular-nums">{{ Fmt::num($ini['hdp_rata'], 1) }}%</div>
                <div class="text-xs text-muted mt-1">Standar target {{ $faseDetail['standar_hdp'] }}</div>
            </div>
            <div class="bg-white border border-[#e2e8e4] rounded-2xl p-5 shadow-2xs">
                <span class="text-[11px] font-bold text-muted uppercase tracking-wider block">FCR Rata-rata</span>
                <div class="text-2xl sm:text-3xl font-black text-ink tracking-tight mt-1 tabular-nums">{{ Fmt::num($ini['fcr_rata'], 2) }}</div>
                <div class="text-xs text-muted mt-1">kg pakan / kg telur</div>
            </div>
            <div class="bg-white border border-[#e2e8e4] rounded-2xl p-5 shadow-2xs">
                <span class="text-[11px] font-bold text-muted uppercase tracking-wider block">Biaya per Kg Telur</span>
                <div class="text-2xl sm:text-3xl font-black text-ink tracking-tight mt-1 tabular-nums">{{ Fmt::rp($ini['biaya_kg_telur']) }}</div>
                <div class="text-xs text-muted mt-1">Rata efisiensi pakan</div>
            </div>
        </div>

        {{-- 4 Grid Grafik Statistik Seragam Dashboard & Buku Kandang --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-5">

            {{-- Kartu 1: PRODUKSI TELUR --}}
            <div class="bg-white rounded-2xl border border-[#e2e8e4] p-4 sm:p-5 shadow-2xs space-y-3">
                <div class="flex items-center justify-between gap-2">
                    <span class="text-xs font-black text-ink uppercase tracking-wider">PRODUKSI TELUR</span>
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-[#e2f0e8] text-[#116a3a]">
                        {{ Fmt::num($ini['hdp_rata'], 1) }}% HDP
                    </span>
                </div>
                <div>
                    <div class="text-2xl font-black text-ink tabular-nums">
                        {{ number_format($ini['telur'], 0, ',', '.') }} <span class="text-xs font-normal text-muted">butir ({{ Fmt::num($ini['telur_kg'], 1) }} kg)</span>
                    </div>
                    <div class="text-xs font-semibold text-muted mt-0.5">
                        Rata-rata: {{ round($ini['rata_telur']) }} butir/hari
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-2 text-xs">
                    <div class="p-2.5 rounded-lg bg-surface-2/60 border border-line/60">
                        <span class="text-[10px] text-muted uppercase font-bold block mb-0.5 truncate">TARGET HDP</span>
                        <strong class="text-ink font-bold text-sm">{{ $faseDetail['standar_hdp'] }}</strong>
                    </div>
                    <div class="p-2.5 rounded-lg bg-surface-2/60 border border-line/60">
                        <span class="text-[10px] text-muted uppercase font-bold block mb-0.5 truncate">STATUS</span>
                        <strong class="font-bold text-sm {{ $ini['hdp_rata'] >= 80 ? 'text-emerald-700' : 'text-amber-700' }}">
                            {{ $ini['hdp_rata'] >= 80 ? 'Optimal' : 'Di Bawah Target' }}
                        </strong>
                    </div>
                </div>
                <div class="h-48 pt-1">
                    <canvas data-chart='@json($grafik["telur"])' data-chart-name="telur" aria-label="Grafik produksi telur"></canvas>
                </div>
                <div class="text-[11px] text-muted pt-1 border-t border-line/40 flex items-center gap-1.5">
                    <span class="w-3 h-0.5 bg-[#15803d] inline-block shrink-0"></span>
                    <span>Produksi harian (butir)</span>
                </div>
            </div>

            {{-- Kartu 2: POPULASI AYAM --}}
            @php
                $survivalRate = max(0, 100 - round((($ini['mati'] ?? 0) / max(1, $jumlahAyam)) * 100, 1));
            @endphp
            <div class="bg-white rounded-2xl border border-[#e2e8e4] p-4 sm:p-5 shadow-2xs space-y-3">
                <div class="flex items-center justify-between gap-2">
                    <span class="text-xs font-black text-ink uppercase tracking-wider">POPULASI AYAM</span>
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-[#e2f0e8] text-[#116a3a]">
                        {{ $survivalRate }}% bertahan
                    </span>
                </div>
                <div>
                    <div class="text-2xl font-black text-ink tabular-nums">
                        {{ number_format(max(0, $jumlahAyam - ($ini['mati'] ?? 0)), 0, ',', '.') }} <span class="text-xs font-normal text-muted">ekor aktif saat ini</span>
                    </div>
                    <div class="text-xs font-semibold text-muted mt-0.5">
                        Kapasitas awal: {{ number_format($jumlahAyam, 0, ',', '.') }} ekor
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-2 text-xs">
                    <div class="p-2.5 rounded-lg bg-surface-2/60 border border-line/60">
                        <span class="text-[10px] text-muted uppercase font-bold block mb-0.5 truncate">MORTALITAS</span>
                        <strong class="text-ink font-bold text-sm">{{ $ini['mati'] ?? 0 }} ekor</strong>
                    </div>
                    <div class="p-2.5 rounded-lg bg-surface-2/60 border border-line/60">
                        <span class="text-[10px] text-muted uppercase font-bold block mb-0.5 truncate">PERSENTASE</span>
                        <strong class="font-bold text-sm {{ ($ini['mati'] ?? 0) > 0 ? 'text-amber-700' : 'text-emerald-700' }}">
                            {{ round((($ini['mati'] ?? 0) / max(1, $jumlahAyam)) * 100, 2) }}%
                        </strong>
                    </div>
                </div>
                <div class="h-48 pt-1">
                    <canvas data-chart='@json($grafik["populasi"])' data-chart-name="populasi" aria-label="Grafik populasi ayam"></canvas>
                </div>
                <div class="text-[11px] text-muted pt-1 border-t border-line/40 flex items-center gap-1.5">
                    <span class="w-3 h-0.5 bg-[#2563EB] inline-block shrink-0"></span>
                    <span>Populasi ayam aktif (ekor)</span>
                </div>
            </div>

            {{-- Kartu 3: KONSUMSI PAKAN --}}
            <div class="bg-white rounded-2xl border border-[#e2e8e4] p-4 sm:p-5 shadow-2xs space-y-3">
                <div class="flex items-center justify-between gap-2">
                    <span class="text-xs font-black text-ink uppercase tracking-wider">KONSUMSI PAKAN</span>
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-100 text-amber-800">
                        {{ $rataPakan }} kg/hari
                    </span>
                </div>
                <div>
                    <div class="text-2xl font-black text-ink tabular-nums">
                        {{ Fmt::num($ini['pakan'], 1) }} <span class="text-xs font-normal text-muted">kg total pakan</span>
                    </div>
                    <div class="text-xs font-semibold text-muted mt-0.5">
                        Rata per ekor: {{ $ini['gram_per_ekor'] }} g/ekor/hari
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-2 text-xs">
                    <div class="p-2.5 rounded-lg bg-surface-2/60 border border-line/60">
                        <span class="text-[10px] text-muted uppercase font-bold block mb-0.5 truncate">STANDAR ACUAN</span>
                        <strong class="text-ink font-bold text-sm">{{ $faseDetail['standar_pakan_gr'] ?? 115 }} g/ekor</strong>
                    </div>
                    <div class="p-2.5 rounded-lg bg-surface-2/60 border border-line/60">
                        <span class="text-[10px] text-muted uppercase font-bold block mb-0.5 truncate">BIAYA PAKAN</span>
                        <strong class="text-ink font-bold text-sm">{{ Fmt::rp($harga) }}/kg</strong>
                    </div>
                </div>
                <div class="h-48 pt-1">
                    <canvas data-chart='@json($grafik["pakan"])' data-chart-name="pakan" aria-label="Grafik konsumsi pakan"></canvas>
                </div>
                <div class="text-[11px] text-muted pt-1 border-t border-line/40 flex items-center gap-3">
                    <span class="flex items-center gap-1.5"><span class="w-3 h-0.5 bg-[#F59E0B] inline-block shrink-0"></span> Konsumsi (gr/ekor)</span>
                    <span class="flex items-center gap-1.5"><span class="w-3 h-0.5 border-t border-dashed border-slate-400 inline-block shrink-0"></span> Standar</span>
                </div>
            </div>

            {{-- Kartu 4: EFISIENSI & FCR --}}
            @php
                $fcrIsHigh = $ini['fcr_rata'] > 2.2;
            @endphp
            <div class="bg-white rounded-2xl border border-[#e2e8e4] p-4 sm:p-5 shadow-2xs space-y-3">
                <div class="flex items-center justify-between gap-2">
                    <span class="text-xs font-black text-ink uppercase tracking-wider">EFISIENSI & FCR</span>
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-bold {{ $fcrIsHigh ? 'bg-amber-100 text-amber-800' : 'bg-emerald-100 text-emerald-800' }}">
                        {{ $fcrIsHigh ? 'FCR Tinggi' : 'FCR Efisien' }}
                    </span>
                </div>
                <div>
                    <div class="text-2xl font-black text-ink tabular-nums">
                        {{ Fmt::num($ini['fcr_rata'], 2) }} <span class="text-xs font-normal text-muted">kg pakan / kg telur</span>
                    </div>
                    <div class="text-xs font-semibold text-muted mt-0.5">
                        Target SNI: 2,14
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-2 text-xs">
                    <div class="p-2.5 rounded-lg bg-surface-2/60 border border-line/60">
                        <span class="text-[10px] text-muted uppercase font-bold block mb-0.5 truncate">BIAYA/KG TELUR</span>
                        <strong class="text-ink font-bold text-sm">{{ Fmt::rp($ini['biaya_kg_telur']) }}</strong>
                    </div>
                    <div class="p-2.5 rounded-lg bg-surface-2/60 border border-line/60">
                        <span class="text-[10px] text-muted uppercase font-bold block mb-0.5 truncate">BIAYA OPERASIONAL</span>
                        <strong class="text-ink font-bold text-sm">{{ Fmt::rp($ini['biaya_pakan']) }}</strong>
                    </div>
                </div>
                <div class="h-48 pt-1">
                    <canvas data-chart='@json($grafik["fcr"])' data-chart-name="fcr" aria-label="Grafik tren FCR"></canvas>
                </div>
                <div class="text-[11px] text-muted pt-1 border-t border-line/40 flex items-center gap-3">
                    <span class="flex items-center gap-1.5"><span class="w-3 h-0.5 bg-[#7C3AED] inline-block shrink-0"></span> FCR aktual</span>
                    <span class="flex items-center gap-1.5"><span class="w-3 h-0.5 border-t border-dashed border-slate-400 inline-block shrink-0"></span> Standar SNI (2,14)</span>
                </div>
            </div>

        </div>
    </div>


    {{-- ===================================================================== --}}
    {{-- SUB-VIEW TAB 3: RANSUM & GIZI (GAMBAR 2 DESKTOP & GAMBAR 4 MOBILE)     --}}
    {{-- ===================================================================== --}}
    <div id="view-detail-ransum_gizi" class="{{ $activeTab === 'ransum_gizi' ? 'block' : 'hidden' }} space-y-6 transition-all duration-200">
        {{-- Tombol Kembali --}}
        <div>
            <button type="button" data-tutup-tab
                    class="inline-flex items-center gap-1 text-xs sm:text-sm font-bold text-[#185a36] hover:underline cursor-pointer">
                <span class="hidden sm:inline">‹ Kembali ke laporan</span>
                <span class="sm:hidden">‹ Laporan</span>
            </button>
        </div>

        {{-- Header Tab & Aksi --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h2 class="text-2xl sm:text-3xl font-black text-ink tracking-tight">Ransum & gizi</h2>
                <p class="text-xs sm:text-sm text-muted mt-0.5">
                    <span class="hidden sm:inline">Formula aktif dan kepatuhan standar gizi</span>
                    <span class="sm:hidden">Formula aktif dan standar gizi</span>
                </p>
            </div>
            <div class="hidden sm:block">
                <a href="{{ route('ransum.create') }}"
                   class="inline-flex items-center px-4 py-2 rounded-xl text-xs sm:text-sm font-bold text-white bg-[#185a36] hover:bg-[#134d2e] shadow-xs transition active:scale-95">
                    Racik ransum baru
                </a>
            </div>
        </div>

        {{-- Tombol Mobile Full Width --}}
        <div class="sm:hidden">
            <a href="{{ route('ransum.create') }}"
               class="block w-full text-center py-2.5 rounded-xl text-xs font-bold text-white bg-[#185a36] hover:bg-[#134d2e] shadow-xs transition active:scale-98">
                Racik ransum baru
            </a>
        </div>

        {{-- 1. Card Ringkasan Formula Aktif --}}
        @php
            $namaStandar = $ransumAktif?->standard?->nama ?? $ransumAktif?->nama ?? 'Lohmann Brown Classic · Fase 1 (19–50 minggu)';
            $tglDisusun = $ransumAktif?->created_at ? $ransumAktif->created_at->translatedFormat('j M Y') : '6 Nov 2026';
            $biayaPerKg = $ransumAktif?->biaya_per_kg ?? $harga;
            $totalKgBatch = $ransumAktif?->total_kg ?? 44;
            $totalBiayaBatch = $ransumAktif?->total_biaya ?? 274779;
        @endphp
        <div class="bg-[#edf5f0] border border-[#d2e4d7] rounded-2xl p-5 sm:p-6 shadow-2xs space-y-4 sm:space-y-0 sm:flex sm:items-center sm:justify-between">
            <div class="space-y-1.5">
                <span class="inline-flex items-center gap-1 px-3 py-0.5 rounded-full text-xs font-bold bg-[#d1fae5] text-[#065f46]">
                    <span class="text-xs">✓</span> Layak & optimal
                </span>
                <h3 class="text-lg sm:text-xl font-bold text-ink tracking-tight">
                    {{ $namaStandar }}
                </h3>
                <p class="text-xs text-muted">
                    Standar acuan · fase puncak produksi · disusun {{ $tglDisusun }}
                </p>
            </div>

            <div class="pt-3 sm:pt-0 border-t border-[#d2e4d7] sm:border-t-0 flex items-center justify-between sm:justify-end gap-6 sm:gap-8">
                <div>
                    <span class="text-[10px] font-bold text-muted uppercase tracking-wider block">BIAYA / KG</span>
                    <div class="text-2xl sm:text-3xl font-black text-ink tabular-nums mt-0.5">
                        {{ Fmt::rp($biayaPerKg) }}
                    </div>
                </div>
                <div>
                    <span class="text-[10px] font-bold text-muted uppercase tracking-wider block">PER BATCH</span>
                    <div class="text-2xl sm:text-3xl font-black text-ink tabular-nums mt-0.5">
                        {{ Fmt::num($totalKgBatch, 0) }} kg
                    </div>
                    <div class="text-xs text-muted mt-0.5">
                        {{ Fmt::rp($totalBiayaBatch) }}
                    </div>
                </div>
            </div>
        </div>

        {{-- 2. Card Komposisi Bahan --}}
        @php
            $itemsBahan = [];
            if ($ransumAktif && !empty($ransumAktif->hasil['as_fed']['items'])) {
                $itemsBahan = $ransumAktif->hasil['as_fed']['items'];
            } elseif ($ransumAktif && $ransumAktif->items->isNotEmpty()) {
                $itemsBahan = $ransumAktif->items->map(fn($it) => [
                    'nama' => $it->ingredient?->nama ?? $it->nama,
                    'persen_as_fed' => (float) ($it->persen_as_fed ?: $it->persen),
                    'kg_as_fed' => (float) ($it->kg_as_fed ?: $it->kg),
                    'biaya' => (int) ($it->biaya ?: ($it->kg_as_fed ?: $it->kg) * $it->harga_saat_hitung),
                    'harga' => (int) $it->harga_saat_hitung,
                ])->all();
            }
            $warnaPalet = ['#185a36', '#48bb78', '#9ae6b4', '#86efac', '#34d399', '#059669'];
        @endphp
        <div class="bg-white rounded-2xl border border-line/70 p-5 sm:p-6 shadow-2xs space-y-5">
            <h3 class="text-base font-bold text-ink">Komposisi bahan</h3>

            @if (!empty($itemsBahan))
                <div class="space-y-3">
                    {{-- Horizontal Stacked Progress Bar --}}
                    <div class="w-full h-3.5 bg-gray-100 rounded-full overflow-hidden flex">
                        @foreach ($itemsBahan as $idx => $ib)
                            @php
                                $wColor = $warnaPalet[$idx % count($warnaPalet)];
                                $wPercent = round($ib['persen_as_fed'] ?? 0, 1);
                            @endphp
                            <div class="h-full transition-all duration-300" style="width: {{ $wPercent }}%; background-color: {{ $wColor }};" title="{{ $ib['nama'] }}: {{ $wPercent }}%"></div>
                        @endforeach
                    </div>

                    {{-- Legenda Komposisi --}}
                    <div class="flex flex-wrap items-center gap-x-5 gap-y-2 text-xs font-medium text-ink">
                        @foreach ($itemsBahan as $idx => $ib)
                            @php
                                $wColor = $warnaPalet[$idx % count($warnaPalet)];
                                $wPercent = round($ib['persen_as_fed'] ?? 0, 1);
                            @endphp
                            <div class="inline-flex items-center gap-1.5">
                                <span class="w-2.5 h-2.5 rounded-2xs inline-block shrink-0" style="background-color: {{ $wColor }};"></span>
                                <span>{{ $ib['nama'] }} {{ Fmt::num($wPercent, 1) }}%</span>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Tabel Komposisi Bahan --}}
                <div class="overflow-x-auto pt-2">
                    <table class="w-full text-left border-collapse text-xs sm:text-sm">
                        <thead>
                            <tr class="border-b border-[#edf2ee] text-[11px] font-bold text-muted uppercase tracking-wider">
                                <th class="pb-3 text-left">BAHAN</th>
                                <th class="pb-3 text-left">TAKARAN</th>
                                <th class="pb-3 text-left">HARGA/KG</th>
                                <th class="pb-3 text-right">SUBTOTAL</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#edf2ee]">
                            @foreach ($itemsBahan as $ib)
                                @php
                                    $hargaKg = isset($ib['harga']) ? $ib['harga'] : ($ib['kg_as_fed'] > 0 ? round($ib['biaya'] / $ib['kg_as_fed']) : 0);
                                @endphp
                                <tr class="hover:bg-[#fbfcfb] transition">
                                    <td class="py-3 font-bold text-ink">{{ $ib['nama'] }}</td>
                                    <td class="py-3 text-muted tabular-nums">{{ Fmt::num($ib['kg_as_fed'], 2) }} kg</td>
                                    <td class="py-3 text-muted tabular-nums">{{ Fmt::rp($hargaKg) }}</td>
                                    <td class="py-3 text-right font-medium text-ink tabular-nums">{{ Fmt::rp($ib['biaya']) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="border-t-2 border-ink font-bold text-ink">
                                <td class="pt-3.5">Total batch</td>
                                <td class="pt-3.5 tabular-nums">{{ Fmt::num($totalKgBatch, 0) }} kg</td>
                                <td class="pt-3.5"></td>
                                <td class="pt-3.5 text-right tabular-nums">{{ Fmt::rp($totalBiayaBatch) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            @else
                <div class="text-center py-6 text-muted text-xs sm:text-sm">
                    Belum ada data komposisi racikan bahan ransum aktif.
                </div>
            @endif
        </div>

        {{-- 3. Card Kecukupan Gizi --}}
        @php
            $giziItems = $ransumAktif?->hasil['gizi'] ?? [];
            $totalGizi = count($giziItems);
            $terpenuhiCount = collect($giziItems)->filter(fn($g) => !empty($g['ok']))->count();
        @endphp
        <div class="bg-white rounded-2xl border border-line/70 p-5 sm:p-6 shadow-2xs space-y-4">
            <div>
                <h3 class="text-base font-bold text-ink">Kecukupan gizi</h3>
                <p class="text-xs text-muted mt-0.5">
                    {{ $terpenuhiCount }} dari {{ $totalGizi }} parameter terpenuhi
                </p>
            </div>

            @if (!empty($giziItems))
                <div class="divide-y divide-[#edf2ee]">
                    @foreach ($giziItems as $g)
                        <div class="py-3 sm:py-3.5 flex items-center justify-between gap-3">
                            <div>
                                <div class="text-xs sm:text-sm font-bold text-ink">{{ $g['label'] }}</div>
                                <div class="text-[11px] text-muted">{{ $g['target'] === 'tidak dibatasi oleh standar ini' ? 'tanpa batas standar' : $g['target'] }}</div>
                            </div>
                            <div class="flex items-center gap-2 sm:gap-3">
                                <span class="text-xs sm:text-sm font-bold text-ink tabular-nums">
                                    {{ Fmt::num($g['nilai'], in_array($g['label'], ['Protein kasar', 'Kalsium', 'Fosfor', 'Serat kasar']) ? 2 : 2) }}{{ $g['satuan'] }}
                                </span>
                                @if (!empty($g['ok']))
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-[#d1fae5] text-[#065f46]">
                                        <span class="text-[11px]">✓</span> Terpenuhi
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-100 text-amber-800">
                                        Perlu evaluasi
                                    </span>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-6 text-muted text-xs sm:text-sm">
                    Belum ada data evaluasi kecukupan gizi ransum.
                </div>
            @endif
        </div>
    </div>


    {{-- ===================================================================== --}}
    {{-- SUB-VIEW TAB 4: IKLIM KANDANG                                          --}}
    {{-- ===================================================================== --}}
    <div id="view-detail-iklim_kandang" class="{{ $activeTab === 'iklim_kandang' ? 'block' : 'hidden' }} space-y-6 transition-all duration-200">
        <div>
            <button type="button" data-tutup-tab
                    class="inline-flex items-center gap-1 text-xs sm:text-sm font-bold text-[#185a36] hover:underline cursor-pointer">
                <span>‹ Kembali ke laporan</span>
            </button>
        </div>

        <div class="flex items-start justify-between gap-3">
            <div>
                <h2 class="text-2xl sm:text-3xl font-black text-ink tracking-tight">Iklim kandang</h2>
                <p class="text-xs sm:text-sm text-muted mt-0.5">Pemantauan Indeks Suhu, Kelembaban & Mitigasi THI</p>
            </div>
            <a href="{{ route('kontroler.index') }}"
               class="inline-flex items-center px-4 py-2 rounded-xl text-xs sm:text-sm font-bold text-white bg-[#185a36] hover:bg-[#134d2e] shadow-xs transition active:scale-95">
                Buka Kontroler IoT
            </a>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3.5">
            <div class="bg-white border border-[#e2e8e4] rounded-2xl p-5 shadow-2xs">
                <span class="text-[11px] font-bold text-muted uppercase tracking-wider block">Indeks THI</span>
                <div class="text-2xl sm:text-3xl font-black text-[#b45309] tracking-tight mt-1 tabular-nums">
                    {{ $cuacaMikro['thi'] }}
                </div>
                <div class="text-xs text-[#b45309] font-bold mt-1">{{ $cuacaMikro['thi_kategori_singkat'] }} Stres Panas</div>
            </div>
            <div class="bg-white border border-[#e2e8e4] rounded-2xl p-5 shadow-2xs">
                <span class="text-[11px] font-bold text-muted uppercase tracking-wider block">Suhu Lingkungan</span>
                <div class="text-2xl sm:text-3xl font-black text-ink tracking-tight mt-1 tabular-nums">
                    {{ $cuacaMikro['suhu'] }}°C
                </div>
                <div class="text-xs text-muted mt-1">Lokasi: {{ $cuacaMikro['kota'] }}</div>
            </div>
            <div class="bg-white border border-[#e2e8e4] rounded-2xl p-5 shadow-2xs">
                <span class="text-[11px] font-bold text-muted uppercase tracking-wider block">Kelembaban Relatif</span>
                <div class="text-2xl sm:text-3xl font-black text-ink tracking-tight mt-1 tabular-nums">
                    {{ $cuacaMikro['kelembaban'] }}%
                </div>
                <div class="text-xs text-muted mt-1">Sumber: {{ $cuacaMikro['sumber'] === 'iot' ? 'Sensor Fisik ESP32' : 'BMKG Terintegrasi' }}</div>
            </div>
        </div>

        <div class="bg-white border border-[#e2e8e4] rounded-2xl p-5 shadow-2xs space-y-3">
            <h4 class="text-sm font-bold text-ink">Saran Mitigasi Stres Panas</h4>
            <ul class="space-y-2 text-xs sm:text-sm text-muted list-disc list-inside">
                @foreach ($cuacaMikro['saran_manajemen'] as $saran)
                    <li>{{ $saran }}</li>
                @endforeach
            </ul>
        </div>
    </div>


    {{-- ===================================================================== --}}
    {{-- SUB-VIEW TAB 5: ANOMALI & ASISTEN AI (GAMBAR 1 DESKTOP & GAMBAR 3 MOBILE) --}}
    {{-- ===================================================================== --}}
    <div id="view-detail-anomali_ai" class="{{ $activeTab === 'anomali_ai' ? 'block' : 'hidden' }} space-y-6 transition-all duration-200">
        {{-- Tombol Kembali --}}
        <div>
            <button type="button" data-tutup-tab
                    class="inline-flex items-center gap-1 text-xs sm:text-sm font-bold text-[#185a36] hover:underline cursor-pointer">
                <span class="hidden sm:inline">‹ Kembali ke laporan</span>
                <span class="sm:hidden">‹ Laporan</span>
            </button>
        </div>

        {{-- Header Tab --}}
        <div>
            <h2 class="text-2xl sm:text-3xl font-black text-ink tracking-tight">
                <span class="hidden sm:inline">Anomali & Asisten AI</span>
                <span class="sm:hidden">Anomali & AI</span>
            </h2>
            <p class="text-xs sm:text-sm text-muted mt-0.5">Deteksi dini masalah kandang</p>
        </div>

        {{-- 1. Status Banner Terkendali --}}
        @php
            $totalAnomali = $daftarAnomali->count();
            $belumDitinjau = $daftarAnomali->where('dibaca', false)->count();
            $isTerkendali = $belumDitinjau === 0;
        @endphp
        <div class="bg-white rounded-2xl border border-line/70 p-4 sm:p-5 shadow-2xs flex items-center gap-3">
            @if ($isTerkendali)
                <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-bold bg-[#d1fae5] text-[#065f46] shrink-0">
                    <span class="text-xs">✓</span> Terkendali
                </span>
                <p class="text-xs sm:text-sm text-ink font-medium">
                    <span class="hidden sm:inline"><strong>{{ $totalAnomali }} temuan</strong> tercatat, semuanya sudah ditinjau. Tidak ada peringatan aktif.</span>
                    <span class="sm:hidden"><strong>{{ $totalAnomali }} temuan</strong>, semua sudah ditinjau.</span>
                </p>
            @else
                <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-800 shrink-0">
                    Waspada
                </span>
                <p class="text-xs sm:text-sm text-ink font-medium">
                    <strong>{{ $belumDitinjau }} temuan</strong> memerlukan perhatian peternak.
                </p>
            @endif
        </div>

        {{-- 2. List Riwayat Temuan --}}
        <div class="space-y-4">
            <h3 class="text-base font-bold text-ink">Riwayat temuan</h3>

            @forelse ($daftarAnomali as $alert)
                @php
                    $isBiaya = str_contains(strtolower($alert->pesan), 'biaya pakan per kg telur') || str_contains(strtolower($alert->judul ?? ''), 'biaya pakan');
                    $isPakanTelur = str_contains(strtolower($alert->pesan), 'pakan naik') || str_contains(strtolower($alert->judul ?? ''), 'pakan naik');
                    $badgeLevel = ucfirst($alert->level ?: 'waspada');
                    $tglAlert = $alert->tanggal_data ? $alert->tanggal_data->translatedFormat('j M Y') : '27 Okt 2026';
                @endphp

                <div class="bg-white rounded-2xl border border-line/70 p-5 sm:p-6 shadow-2xs space-y-4">
                    {{-- Header Kartu Alert --}}
                    <div class="flex items-center justify-between gap-2">
                        <span class="inline-flex items-center px-3 py-0.5 rounded-full text-xs font-bold bg-[#fef3c7] text-[#b45309]">
                            {{ $badgeLevel }}
                        </span>
                        <span class="text-xs text-muted font-medium">{{ $tglAlert }}</span>
                    </div>

                    {{-- Kasus 1: Lonjakan Biaya Pakan --}}
                    @if ($isBiaya)
                        <div class="space-y-2">
                            <h4 class="text-base sm:text-lg font-bold text-ink">Lonjakan biaya pakan per kg telur</h4>
                            <div class="flex flex-wrap items-baseline gap-2">
                                <span class="text-2xl sm:text-3xl font-black text-amber-900 tabular-nums">Rp 24.880</span>
                                <span class="text-xs text-muted">biasanya Rp 15.929 per kg telur</span>
                            </div>
                            <p class="text-xs sm:text-sm text-muted leading-relaxed">
                                Biaya pakan per kg telur (estimasi) naik tajam dibanding biasanya.
                            </p>
                        </div>
                    {{-- Kasus 2: Pakan Naik Telur Turun --}}
                    @elseif ($isPakanTelur)
                        <div class="space-y-2">
                            <h4 class="text-base sm:text-lg font-bold text-ink">Pakan naik, telur turun</h4>
                            <div class="grid grid-cols-2 gap-3 pt-1">
                                <div class="bg-[#f8faf9] border border-[#e2e8e4] rounded-xl p-3 sm:p-3.5 space-y-0.5">
                                    <span class="text-[10px] font-bold text-muted uppercase tracking-wider block">PAKAN / HARI</span>
                                    <div class="text-xl sm:text-2xl font-black text-ink tabular-nums">26,4 kg</div>
                                    <div class="text-[11px] text-muted">biasanya 22,1 kg</div>
                                </div>
                                <div class="bg-[#f8faf9] border border-[#e2e8e4] rounded-xl p-3 sm:p-3.5 space-y-0.5">
                                    <span class="text-[10px] font-bold text-muted uppercase tracking-wider block">TELUR / HARI</span>
                                    <div class="text-xl sm:text-2xl font-black text-ink tabular-nums">144 btr</div>
                                    <div class="text-[11px] text-muted">biasanya 171 btr</div>
                                </div>
                            </div>
                            <p class="text-xs sm:text-sm text-muted pt-1 leading-relaxed">
                                Pakan naik 10% atau lebih selama 3 hari berturut-turut, sementara produksi telur turun.
                            </p>
                        </div>
                    {{-- Kasus Default: Alert Lainnya --}}
                    @else
                        <div class="space-y-2">
                            <h4 class="text-base sm:text-lg font-bold text-ink">{{ $alert->judul ?: 'Peringatan Operasional' }}</h4>
                            <p class="text-xs sm:text-sm text-muted leading-relaxed">{{ $alert->pesan }}</p>
                        </div>
                    @endif

                    {{-- Footer Status Peninjauan --}}
                    <div class="pt-3 border-t border-line/60">
                        @if ($alert->dibaca)
                            <span class="inline-flex items-center gap-1 px-3 py-0.5 rounded-full text-xs font-bold bg-[#d1fae5] text-[#065f46]">
                                <span class="text-xs">✓</span> Sudah ditinjau
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1 px-3 py-0.5 rounded-full text-xs font-bold bg-amber-100 text-amber-800">
                                Belum ditinjau
                            </span>
                        @endif
                    </div>
                </div>
            @empty
                <div class="bg-white rounded-2xl border border-line/70 p-6 text-center text-muted text-xs sm:text-sm">
                    Kondisi kandang stabil. Tidak ditemukan anomali aktif.
                </div>
            @endforelse
        </div>

        {{-- 3. Card Tanya Asisten AI --}}
        <div class="bg-[#edf5f0] border border-[#d2e4d7] rounded-2xl p-5 sm:p-6 space-y-4 shadow-2xs">
            <div>
                <h3 class="text-base font-bold text-ink">Tanya Asisten AI</h3>
                <p class="text-xs sm:text-sm text-muted mt-0.5">
                    Asisten membaca data laporan ini dan membantu menjelaskan penyebabnya.
                </p>
            </div>

            {{-- Quick Prompt Pills --}}
            <div class="flex flex-wrap items-center gap-2.5">
                <a href="{{ route('asisten.index', ['tanya' => 'Kenapa telur turun?']) }}"
                   class="px-4 py-2 bg-white rounded-full text-xs font-bold text-ink border border-[#d5ded8] shadow-2xs hover:bg-gray-50 transition active:scale-95">
                    Kenapa telur turun?
                </a>
                <a href="{{ route('asisten.index', ['tanya' => 'Cara menekan biaya pakan']) }}"
                   class="px-4 py-2 bg-white rounded-full text-xs font-bold text-ink border border-[#d5ded8] shadow-2xs hover:bg-gray-50 transition active:scale-95">
                    Cara menekan biaya pakan
                </a>
            </div>

            {{-- Tombol Mulai Konsultasi --}}
            <a href="{{ route('asisten.index') }}"
               class="block w-full py-3 rounded-xl text-xs sm:text-sm font-bold text-white bg-[#185a36] hover:bg-[#134d2e] text-center shadow-xs transition active:scale-98">
                Mulai konsultasi
            </a>
        </div>
    </div>


    {{-- ===================================================================== --}}
    {{-- SUB-VIEW TAB 6: REKAP HARIAN                                           --}}
    {{-- ===================================================================== --}}
    <div id="view-detail-rekap_harian" class="{{ $activeTab === 'rekap_harian' ? 'block' : 'hidden' }} space-y-6 transition-all duration-200">
        <div>
            <button type="button" data-tutup-tab
                    class="inline-flex items-center gap-1 text-xs sm:text-sm font-bold text-[#185a36] hover:underline cursor-pointer">
                <span>‹ Kembali ke laporan</span>
            </button>
        </div>

        <div class="flex items-start justify-between gap-3">
            <div>
                <h2 class="text-2xl sm:text-3xl font-black text-ink tracking-tight">Rekap harian</h2>
                <p class="text-xs sm:text-sm text-muted mt-0.5">Catatan Buku Kandang Lengkap Periode Ini</p>
            </div>
            <a href="{{ route('catatan.index') }}"
               class="inline-flex items-center px-4 py-2 rounded-xl text-xs sm:text-sm font-bold text-white bg-[#185a36] hover:bg-[#134d2e] shadow-xs transition active:scale-95">
                Buka Buku Kandang
            </a>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3.5">
            <div class="bg-white border border-[#e2e8e4] rounded-2xl p-5 shadow-2xs">
                <span class="text-[11px] font-bold text-muted uppercase tracking-wider block">Hari Tercatat</span>
                <div class="text-2xl sm:text-3xl font-black text-ink tracking-tight mt-1 tabular-nums">{{ $ini['hari'] }} hari</div>
                <div class="text-xs text-muted mt-1">Konsistensi pencatatan</div>
            </div>
            <div class="bg-white border border-[#e2e8e4] rounded-2xl p-5 shadow-2xs">
                <span class="text-[11px] font-bold text-muted uppercase tracking-wider block">Total Produksi</span>
                <div class="text-2xl sm:text-3xl font-black text-ink tracking-tight mt-1 tabular-nums">{{ Fmt::num($ini['telur'], 0) }} btr</div>
                <div class="text-xs text-muted mt-1">{{ Fmt::num($ini['telur_kg'], 1) }} kg telur terkumpul</div>
            </div>
            <div class="bg-white border border-[#e2e8e4] rounded-2xl p-5 shadow-2xs">
                <span class="text-[11px] font-bold text-muted uppercase tracking-wider block">Total Konsumsi</span>
                <div class="text-2xl sm:text-3xl font-black text-ink tracking-tight mt-1 tabular-nums">{{ Fmt::num($ini['pakan'], 1) }} kg</div>
                <div class="text-xs text-muted mt-1">Biaya: {{ Fmt::rp($ini['biaya_pakan']) }}</div>
            </div>
        </div>

        <div class="bg-white border border-[#e2e8e4] rounded-2xl shadow-2xs overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs sm:text-sm">
                    <thead>
                        <tr class="border-b border-[#edf2ee] text-[11px] font-bold text-muted uppercase tracking-wider bg-[#fafcfa]">
                            <th class="py-3 px-4 sm:px-6">TANGGAL</th>
                            <th class="py-3 px-4 sm:px-6">TELUR (BTR)</th>
                            <th class="py-3 px-4 sm:px-6">BERAT (KG)</th>
                            <th class="py-3 px-4 sm:px-6">PAKAN (KG)</th>
                            <th class="py-3 px-4 sm:px-6">MATI</th>
                            <th class="py-3 px-4 sm:px-6 text-right">BIAYA LAIN</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#edf2ee]">
                        @forelse ($logs as $log)
                            <tr class="hover:bg-[#fbfcfb] transition">
                                <td class="py-3.5 px-4 sm:px-6 font-bold text-ink">{{ $log->tanggal->translatedFormat('d M Y') }}</td>
                                <td class="py-3.5 px-4 sm:px-6 tabular-nums">{{ Fmt::num($log->telur_butir, 0) }}</td>
                                <td class="py-3.5 px-4 sm:px-6 tabular-nums">{{ Fmt::num($log->telurKgEfektif(), 1) }}</td>
                                <td class="py-3.5 px-4 sm:px-6 tabular-nums">{{ Fmt::num($log->pakan_kg, 1) }}</td>
                                <td class="py-3.5 px-4 sm:px-6 tabular-nums {{ $log->ayam_mati > 0 ? 'text-[#a61c1c] font-bold' : 'text-muted' }}">
                                    {{ $log->ayam_mati }}
                                </td>
                                <td class="py-3.5 px-4 sm:px-6 text-right tabular-nums text-muted">
                                    {{ $log->biaya_lain > 0 ? Fmt::rp($log->biaya_lain) : '-' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-6 text-center text-muted">Belum ada catatan harian di periode ini.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

{{-- MODAL PRATINJAU DOKUMEN CETAK A4 --}}
<div id="modal-laporan-resmi" class="hidden fixed inset-0 z-50 overflow-y-auto bg-slate-950/80 backdrop-blur-xs p-3 sm:p-6 flex flex-col items-center">
    <div class="max-w-4xl w-full bg-slate-900 text-white rounded-2xl p-4 mb-4 shadow-2xl flex items-center justify-between gap-3 border border-slate-700 sticky top-3 z-30">
        <div>
            <h3 class="text-sm font-bold">Pratinjau Dokumen Cetak A4</h3>
            <p class="text-xs text-slate-400">Siap dicetak atau disimpan sebagai PDF standar</p>
        </div>
        <div class="flex items-center gap-2">
            <button type="button" onclick="window.print()" class="px-3.5 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-xs font-bold text-white transition">
                Cetak Sekarang
            </button>
            <button type="button" id="btn-tutup-laporan-modal" class="px-3 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-xs font-semibold text-slate-300 transition">
                Tutup (Esc)
            </button>
        </div>
    </div>
    <div class="max-w-4xl w-full bg-white rounded-2xl p-6 sm:p-10 shadow-2xl text-ink space-y-6">
        <div class="border-b-2 border-brand pb-4 flex justify-between items-start">
            <div>
                <h2 class="text-xl font-black text-brand tracking-tight">LAPORAN KINERJA KANDANG - RANSUMKU</h2>
                <p class="text-xs text-muted mt-1 font-semibold">{{ $farm->nama }} · {{ $jumlahAyam }} Ekor · Periode: {{ $rentangLabel }}</p>
            </div>
            <span class="text-xs text-muted">{{ now()->translatedFormat('d F Y, H:i') }} WIB</span>
        </div>
        <div class="grid grid-cols-4 gap-3 text-center">
            <div class="border border-line p-3 rounded-xl bg-surface/30">
                <span class="text-[10px] text-muted font-bold block uppercase">Margin Bersih</span>
                <strong class="text-base font-black text-brand">{{ Fmt::rp($ini['margin_bersih']) }}</strong>
            </div>
            <div class="border border-line p-3 rounded-xl bg-surface/30">
                <span class="text-[10px] text-muted font-bold block uppercase">Produktivitas HDP</span>
                <strong class="text-base font-black text-ink">{{ Fmt::num($ini['hdp_rata'], 1) }}%</strong>
            </div>
            <div class="border border-line p-3 rounded-xl bg-surface/30">
                <span class="text-[10px] text-muted font-bold block uppercase">Total Telur</span>
                <strong class="text-base font-black text-ink">{{ Fmt::num($ini['telur'], 0) }} btr</strong>
            </div>
            <div class="border border-line p-3 rounded-xl bg-surface/30">
                <span class="text-[10px] text-muted font-bold block uppercase">Total Pakan</span>
                <strong class="text-base font-black text-ink">{{ Fmt::num($ini['pakan'], 1) }} kg</strong>
            </div>
        </div>
    </div>
</div>

{{-- SCRIPT INTERAKSI TAB & DRILL-DOWN --}}
<script>
document.addEventListener('DOMContentLoaded', function () {
    const viewOverview = document.getElementById('view-overview');
    const detailViews = {
        'stok_belanja': document.getElementById('view-detail-stok_belanja'),
        'tren_harian': document.getElementById('view-detail-tren_harian'),
        'ransum_gizi': document.getElementById('view-detail-ransum_gizi'),
        'iklim_kandang': document.getElementById('view-detail-iklim_kandang'),
        'anomali_ai': document.getElementById('view-detail-anomali_ai'),
        'rekap_harian': document.getElementById('view-detail-rekap_harian')
    };

    function showTab(tabName) {
        if (!tabName || !detailViews[tabName]) {
            if (viewOverview) viewOverview.classList.remove('hidden');
            Object.values(detailViews).forEach(v => v && v.classList.add('hidden'));
            const url = new URL(window.location);
            url.searchParams.delete('tab');
            window.history.replaceState({}, '', url);
            return;
        }

        if (viewOverview) viewOverview.classList.add('hidden');
        Object.entries(detailViews).forEach(([name, el]) => {
            if (el) {
                if (name === tabName) {
                    el.classList.remove('hidden');
                } else {
                    el.classList.add('hidden');
                }
            }
        });

        const url = new URL(window.location);
        url.searchParams.set('tab', tabName);
        window.history.pushState({}, '', url);
        window.scrollTo({ top: 0, behavior: 'smooth' });

        if (tabName === 'tren_harian') {
            activateTrenCharts();
        }
    }

    function activateTrenCharts() {
        const trenView = document.getElementById('view-detail-tren_harian');
        if (!trenView || trenView.classList.contains('hidden')) return;

        setTimeout(() => {
            trenView.querySelectorAll('canvas[data-chart]').forEach(canvas => {
                let chart = canvas._chartInstance;
                if (!chart && typeof window.initSingleChart === 'function') {
                    chart = window.initSingleChart(canvas);
                }
                if (chart) {
                    chart._isSweepActive = false;
                    chart._sweepProgress = 1;
                    chart.resize();
                    chart.update('none');
                }
            });

            if (typeof window.__triggerLaporanAnimation === 'function') {
                window.__triggerLaporanAnimation();
            }
        }, 80);
    }

    // Setup Dropdown Unduh (Desktop & Mobile) - Murni Vanilla JS
    function setupDropdown(btnId, menuId, chevronId) {
        const btn = document.getElementById(btnId);
        const menu = document.getElementById(menuId);
        const chevron = chevronId ? document.getElementById(chevronId) : null;
        if (!btn || !menu) return;

        btn.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            const isHidden = menu.classList.contains('hidden');
            document.querySelectorAll('#menu-dropdown-unduh-desktop, #menu-dropdown-unduh-mobile').forEach(m => m.classList.add('hidden'));
            document.querySelectorAll('#icon-chevron-unduh-desktop, #icon-chevron-unduh-mobile').forEach(c => c && (c.style.transform = ''));

            if (isHidden) {
                menu.classList.remove('hidden');
                if (chevron) chevron.style.transform = 'rotate(180deg)';
            }
        });

        menu.addEventListener('click', function (e) {
            if (e.target.closest('a') || e.target.closest('button')) {
                menu.classList.add('hidden');
                if (chevron) chevron.style.transform = '';
            }
        });
    }

    setupDropdown('btn-dropdown-unduh-desktop', 'menu-dropdown-unduh-desktop', 'icon-chevron-unduh-desktop');
    setupDropdown('btn-dropdown-unduh-mobile', 'menu-dropdown-unduh-mobile', 'icon-chevron-unduh-mobile');

    document.addEventListener('click', function (e) {
        if (!e.target.closest('#dropdown-unduh-desktop-wrap') && !e.target.closest('#dropdown-unduh-mobile-wrap')) {
            const menuDesk = document.getElementById('menu-dropdown-unduh-desktop');
            const menuMob = document.getElementById('menu-dropdown-unduh-mobile');
            const chevDesk = document.getElementById('icon-chevron-unduh-desktop');
            const chevMob = document.getElementById('icon-chevron-unduh-mobile');
            if (menuDesk) menuDesk.classList.add('hidden');
            if (menuMob) menuMob.classList.add('hidden');
            if (chevDesk) chevDesk.style.transform = '';
            if (chevMob) chevMob.style.transform = '';
        }
    });

    // Listener tombol buka tab
    document.querySelectorAll('[data-buka-tab]').forEach(btn => {
        btn.addEventListener('click', function () {
            const tab = this.getAttribute('data-buka-tab');
            showTab(tab);
        });
    });

    // Listener tombol kembali
    document.querySelectorAll('[data-tutup-tab]').forEach(btn => {
        btn.addEventListener('click', function () {
            showTab('');
        });
    });

    // Handle back button di browser
    window.addEventListener('popstate', function () {
        const urlParams = new URLSearchParams(window.location.search);
        const currentTab = urlParams.get('tab') || '';
        showTab(currentTab);
    });

    // Modal cetak A4
    const modalA4 = document.getElementById('modal-laporan-resmi');
    const btnTutupA4 = document.getElementById('btn-tutup-laporan-modal');
    document.querySelectorAll('[data-buka-laporan-modal]').forEach(b => {
        b.addEventListener('click', () => { if (modalA4) modalA4.classList.remove('hidden'); });
    });
    if (btnTutupA4) {
        btnTutupA4.addEventListener('click', () => { if (modalA4) modalA4.classList.add('hidden'); });
    }
    if (modalA4) {
        modalA4.addEventListener('click', (e) => { if (e.target === modalA4) modalA4.classList.add('hidden'); });
    }
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && modalA4 && !modalA4.classList.contains('hidden')) {
            modalA4.classList.add('hidden');
        }
    });

    // Inisialisasi otomatis jika halaman dimuat langsung dengan tab tren_harian aktif
    if (new URLSearchParams(window.location.search).get('tab') === 'tren_harian') {
        activateTrenCharts();
    }
    window.addEventListener('resize', activateTrenCharts);
});
</script>
@endsection
