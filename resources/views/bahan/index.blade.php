@extends('layouts.app')
@section('judul', 'Stok Pakan Gudang')
@section('isi')
@use('App\Support\Fmt')

{{-- ======================================================== --}}
{{-- 1. TAMPILAN DESKTOP (> 510px) - Desain Klasik Komprehensif --}}
{{-- ======================================================== --}}
<div class="hidden sm:block space-y-5">
    {{-- Header Halaman Bersih & Humanis --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-lg sm:text-xl font-bold text-ink tracking-tight">Stok Pakan Gudang</h1>
            <p class="text-xs text-muted mt-0.5">Inventaris pakan kandang aktif, pantau batas aman pemesanan (Safety Stock), dan takaran pakan.</p>
        </div>

        {{-- Tombol Aksi Utama: Tambah Bahan ke Gudang --}}
        <div>
            <button type="button" 
                    onclick="bukaModal('modalTambahBahan')"
                    class="inline-flex items-center gap-1.5 px-4 py-2 rounded-full text-xs font-bold text-white bg-brand hover:bg-brand-dark transition shadow-2xs hover:shadow-xs active:scale-95 cursor-pointer">
                <span class="material-symbols-outlined text-[17px]">add</span>
                <span>Tambah Bahan ke Gudang</span>
            </button>
        </div>
    </div>

    {{-- Baris 3 KPI Stok Gudang --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3.5">
        {{-- KPI 1: Total Stok Tersimpan --}}
        <div class="card p-4 rounded-xl flex flex-col justify-between space-y-1.5 shadow-2xs">
            <span class="text-xs font-bold text-muted uppercase tracking-wider">Total Fisik Stok</span>
            <div>
                <div class="text-xl sm:text-2xl font-black text-ink">
                    {{ Fmt::num($totalStokKg, 1) }} <span class="text-xs font-normal text-muted">kg Tersedia</span>
                </div>
                <p class="text-[11px] text-muted mt-0.5">Estimasi nilai aset pakan: <strong class="text-ink font-semibold">{{ Fmt::rp($totalNilaiRp) }}</strong></p>
            </div>
        </div>

        {{-- KPI 2: Status Safety Stock --}}
        <div class="card p-4 rounded-xl flex flex-col justify-between space-y-1.5 shadow-2xs">
            <span class="text-xs font-bold text-muted uppercase tracking-wider">Status Safety Stock</span>
            <div>
                @if ($kritisCount > 0)
                    <div class="text-xl sm:text-2xl font-black text-amber-600 flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-[22px]">warning</span>
                        <span>{{ $kritisCount }} Bahan Perlu Pesan</span>
                    </div>
                    <p class="text-[11px] text-amber-800 mt-0.5">Stok tersisa menipis di bawah kebutuhan 3 hari</p>
                @else
                    <div class="text-xl sm:text-2xl font-black text-emerald-700 flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-[22px]">verified</span>
                        <span>Semua Bahan Aman</span>
                    </div>
                    <p class="text-[11px] text-emerald-800 mt-0.5">Persediaan mencukupi siklus pakan kandang</p>
                @endif
            </div>
        </div>

        {{-- KPI 3: Estimasi Kebutuhan Harian --}}
        <div class="card p-4 rounded-xl flex flex-col justify-between space-y-1.5 shadow-2xs">
            <span class="text-xs font-bold text-muted uppercase tracking-wider">Kebutuhan Pakan Harian</span>
            <div>
                <div class="text-xl sm:text-2xl font-black text-ink">
                    {{ Fmt::num($pakanHarianKg, 1) }} <span class="text-xs font-normal text-muted">kg / hari</span>
                </div>
                <p class="text-[11px] text-muted mt-0.5">Untuk {{ number_format($farm->jumlah_ayam, 0, ',', '.') }} ekor ayam petelur aktif</p>
            </div>
        </div>
    </div>

    {{-- Pencarian Cepat Live --}}
    <div class="relative flex items-center">
        <span class="material-symbols-outlined absolute left-4 text-[18px] text-muted pointer-events-none">search</span>
        <input type="text" 
               id="search-stok-live"
               oninput="filterBahanStok(this)"
               placeholder="Cari bahan di gudang (misal: Jagung, Dedak, Konsentrat, Kapur)..." 
               class="w-full pl-11 pr-10 py-2.5 bg-white border border-line rounded-full text-xs sm:text-sm font-medium text-ink placeholder:text-muted focus:outline-none focus:ring-2 focus:ring-brand/15 focus:border-brand shadow-2xs transition"
               autocomplete="off">
    </div>

    {{-- Subheader Gudang --}}
    <div class="space-y-3.5">
        <div class="flex items-center justify-between pb-1 border-b border-line/60">
            <span class="text-xs font-bold text-muted uppercase tracking-wider">
                Daftar Bahan di Gudang ({{ count($stokGudangData) }})
            </span>
            <span class="text-[11px] text-muted hidden sm:inline">Peringatan otomatis muncul bila stok tersisa &le; kebutuhan 3 hari</span>
        </div>

        {{-- Grid Kartu Bahan Stok Gudang --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            @forelse ($stokGudangData as $d)
                @php
                    $rawNama = $d['fi']->ingredient->nama ?? '';
                    $catEntry = $masterCatalog[$rawNama] ?? null;
                    $kategoriLabel = $catEntry['kategori'] ?? 'Bahan Baku Pakan';
                    $estimasiSak = $d['stok_kg'] > 0 ? round($d['stok_kg'] / 50, 1) : 0;
                @endphp
                <div class="bg-white rounded-xl border border-line p-4 sm:p-5 shadow-2xs hover:shadow-xs transition flex flex-col justify-between space-y-3" 
                     data-stok-card 
                     data-stok-nama="{{ strtolower($rawNama) }}">
                    
                    <div class="space-y-3">
                        {{-- Baris Atas: Nama Bahan & Status Safety Stock --}}
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <h2 class="text-base font-bold text-ink tracking-tight truncate">
                                        {{ $rawNama }}
                                    </h2>
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-brand/10 text-brand border border-brand/20 shrink-0">
                                        {{ $kategoriLabel }}
                                    </span>
                                </div>
                                <span class="text-[11px] text-muted block mt-0.5">
                                    Batas aman minimum: <strong class="text-ink font-semibold">{{ Fmt::num($d['safety_buffer_kg'], 1) }} kg</strong>
                                </span>
                            </div>

                            {{-- Status Safety Stock Badge --}}
                            <span class="px-3 py-1 rounded-full text-xs font-bold border shrink-0 {{ $d['badge_class'] }}">
                                {{ $d['status_label'] }}
                            </span>
                        </div>

                        {{-- Strip 3 Metrik Terbagi --}}
                        <div class="grid grid-cols-3 divide-x divide-line rounded-lg border border-line/60 bg-[#f8fbf9] py-2 text-center">
                            <div class="px-1.5">
                                <span class="text-[10px] font-semibold text-muted uppercase tracking-wider block">Stok Gudang</span>
                                <span class="text-xs sm:text-sm font-bold text-ink mt-0.5 block tabular-nums">
                                    {{ Fmt::num($d['stok_kg'], 1) }} <span class="text-[10px] font-normal text-muted">kg</span>
                                </span>
                                @if ($d['stok_kg'] >= 50)
                                    <span class="text-[10px] text-muted block tabular-nums">~{{ $estimasiSak }} sak</span>
                                @endif
                            </div>
                            <div class="px-1.5">
                                <span class="text-[10px] font-semibold text-muted uppercase tracking-wider block">Ketahanan</span>
                                <span class="text-xs sm:text-sm font-bold {{ $d['status'] === 'menipis' ? 'text-amber-600' : ($d['status'] === 'habis' ? 'text-rose-600' : 'text-brand') }} mt-0.5 block tabular-nums">
                                    {{ $d['hari_tersisa'] > 0 ? $d['hari_tersisa'] . ' Hari' : 'Habis' }}
                                </span>
                                <span class="text-[10px] text-muted block">Pakai {{ Fmt::num($d['kebutuhan_harian_kg'], 1) }} kg/hr</span>
                            </div>
                            <div class="px-1.5">
                                <span class="text-[10px] font-semibold text-muted uppercase tracking-wider block">Harga Beli</span>
                                <span class="text-xs sm:text-sm font-bold text-ink mt-0.5 block truncate tabular-nums">
                                    {{ Fmt::rp($d['harga']) }} <span class="text-[10px] font-normal text-muted">/kg</span>
                                </span>
                                <span class="text-[10px] text-muted block">HPP Pakan</span>
                            </div>
                        </div>

                        {{-- Ringkasan Nutrisi Paten Fapet UGM --}}
                        @if ($d['fi']->ingredient)
                            <div class="p-2.5 rounded-lg bg-surface/60 border border-line/60 flex items-center justify-between text-[11px] text-muted">
                                <span>Nutrisi Baku:</span>
                                <div class="flex items-center gap-2 font-medium text-ink">
                                    <span>PK: <strong>{{ Fmt::num($d['fi']->ingredient->protein, 1) }}%</strong></span>
                                    <span>•</span>
                                    <span>EM: <strong>{{ number_format($d['fi']->ingredient->energi, 0, ',', '.') }} kkal</strong></span>
                                    <span>•</span>
                                    <span>Ca: <strong>{{ Fmt::num($d['fi']->ingredient->kalsium, 2) }}%</strong></span>
                                </div>
                            </div>
                        @endif
                    </div>

                    {{-- Footer Aksi CRUD --}}
                    <div class="pt-2.5 border-t border-line flex items-center justify-between gap-2">
                        <span class="text-[11px] text-muted truncate">
                            {{ $d['fi']->harga_updated_at ? 'Diperbarui '.$d['fi']->harga_updated_at->diffForHumans() : 'Harga acuan baku' }}
                        </span>
                        
                        <div class="flex items-center gap-1.5 shrink-0">
                            {{-- Tombol Edit / Update Stok --}}
                            <button type="button" 
                                    onclick="bukaModalUpdateStok({{ $d['fi']->id }}, '{{ addslashes($rawNama) }}', {{ (float)$d['stok_kg'] }}, {{ (int)$d['harga'] }})"
                                    class="inline-flex items-center gap-1 px-3 py-1.5 rounded-full text-xs font-bold text-brand hover:text-brand-dark bg-brand/5 hover:bg-brand/10 border border-brand/20 transition cursor-pointer shadow-2xs active:scale-95"
                                    title="Perbarui stok atau harga beli">
                                <span class="material-symbols-outlined text-[15px]">edit</span>
                                <span>Edit</span>
                            </button>

                            {{-- Tombol Hapus dari Gudang --}}
                            <form method="POST" 
                                  action="{{ route('bahan.gudang.destroy', $d['fi']) }}"
                                  data-confirm="Hapus {{ $rawNama }} dari gudang pakan Anda? Bahan ini tidak akan muncul lagi di peracik ransum."
                                  data-confirm-title="Hapus Bahan dari Gudang"
                                  data-confirm-btn="Ya, Hapus Bahan">
                                @csrf
                                @method('DELETE')
                                <button type="submit" 
                                        class="inline-flex items-center justify-center w-8 h-8 rounded-full text-rose-600 hover:text-rose-700 bg-rose-50 hover:bg-rose-100 border border-rose-200 transition cursor-pointer active:scale-95"
                                        title="Hapus bahan dari gudang">
                                    <span class="material-symbols-outlined text-[16px]">delete</span>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-span-full bg-white rounded-2xl border border-dashed border-amber-300 p-8 sm:p-12 text-center space-y-3">
                    <span class="material-symbols-outlined text-[44px] text-amber-500 block">inventory_2</span>
                    <div class="space-y-1">
                        <h3 class="text-base font-bold text-ink">Gudang Pakan Masih Kosong</h3>
                        <p class="text-xs text-muted max-w-md mx-auto leading-relaxed">
                            Belum ada bahan pakan yang terdaftar di gudang kandang ini. Tambahkan bahan pakan yang Anda miliki (seperti Jagung, Dedak, atau Konsentrat) untuk mulai memantau stok dan meracik ransum.
                        </p>
                    </div>
                    <button type="button" 
                            onclick="bukaModal('modalTambahBahan')"
                            class="inline-flex items-center gap-1.5 px-5 py-2.5 rounded-full text-xs font-bold text-white bg-brand hover:bg-brand-dark transition shadow-2xs active:scale-95 cursor-pointer">
                        <span class="material-symbols-outlined text-[18px]">add</span>
                        <span>Tambah Bahan Pertama ke Gudang</span>
                    </button>
                </div>
            @endforelse

            {{-- Empty Search Filter --}}
            <div id="stok-empty-desktop" class="hidden col-span-full bg-white rounded-xl border border-line text-center py-10 text-muted space-y-1">
                <span class="material-symbols-outlined text-[36px] text-muted/50 mb-1 block">search_off</span>
                <p class="font-bold text-ink text-sm">Bahan Gudang Tidak Ditemukan</p>
                <p class="text-xs text-muted">Tidak ada bahan pakan di gudang yang sesuai dengan kata kunci pencarian Anda.</p>
            </div>
        </div>
    </div>
</div>



{{-- ======================================================== --}}
{{-- 2. TAMPILAN MOBILE (<= 510px) - Sesuai Mockup Referensi  --}}
{{-- ======================================================== --}}
<div class="sm:hidden space-y-4">
    {{-- 1. Header Halaman Mobile --}}
    <div class="space-y-0.5">
        <h1 class="text-xl font-black text-ink tracking-tight">Stok pakan gudang</h1>
        <p class="text-xs text-muted font-medium">Pantau stok, batas aman, dan harga bahan.</p>
    </div>

    {{-- 2. Card Banner Hijau (Stok Terendah & Ringkasan Gudang) --}}
    <div class="bg-[#185a36] text-white rounded-2xl p-4 shadow-xs space-y-3.5">
        <div>
            <span class="text-[10px] font-bold text-emerald-200/90 tracking-wider uppercase block">STOK TERENDAH</span>
            <h2 class="text-base font-black text-white tracking-tight mt-0.5">
                @if ($bahanTerendah)
                    {{ $bahanTerendah['fi']->ingredient->nama }} tinggal {{ Fmt::num($bahanTerendah['hari_tersisa'], $bahanTerendah['hari_tersisa'] == round($bahanTerendah['hari_tersisa']) ? 0 : 1) }} hari
                @else
                    Semua stok bahan aman
                @endif
            </h2>
            <p class="text-xs text-emerald-100/80 mt-0.5 leading-relaxed">
                @if ($bahanTerendah && $bahanTerendah['status'] !== 'aman')
                    Segera belanja sebelum mendekati batas aman.
                @else
                    Seluruh stok pakan di gudang terpantau mencukupi siklus kebutuhan kandang.
                @endif
            </p>
        </div>

        {{-- Divider Tipis --}}
        <div class="border-t border-emerald-600/40"></div>

        {{-- 3 Kolom Metrik Ringkasan --}}
        <div class="grid grid-cols-3 gap-2 text-left">
            <div>
                <span class="text-[10px] font-bold text-emerald-200/80 uppercase tracking-wider block">TOTAL</span>
                <div class="text-base font-black text-white mt-0.5 tabular-nums">
                    {{ Fmt::num($totalStokKg, 0) }} kg
                </div>
            </div>
            <div>
                <span class="text-[10px] font-bold text-emerald-200/80 uppercase tracking-wider block">NILAI</span>
                <div class="text-base font-black text-white mt-0.5 tabular-nums">
                    {{ $totalNilaiSingkat }}
                </div>
            </div>
            <div>
                <span class="text-[10px] font-bold text-emerald-200/80 uppercase tracking-wider block">BUTUH</span>
                <div class="text-base font-black text-white mt-0.5 tabular-nums">
                    {{ Fmt::num($pakanHarianKg, 0) }} kg/hr
                </div>
            </div>
        </div>

        {{-- Tombol Aksi + Catat belanja --}}
        <div>
            <button type="button" 
                    onclick="bukaModal('modalTambahBahan')"
                    class="w-full flex items-center justify-center gap-1.5 py-2.5 rounded-xl bg-white text-[#185a36] font-bold text-xs hover:bg-emerald-50 transition shadow-2xs active:scale-98 cursor-pointer">
                <span class="text-sm font-black">+</span>
                <span>Catat belanja</span>
            </button>
        </div>
    </div>

    {{-- 3. Baris Input Pencarian Cepat Mobile (Full Width) --}}
    <div class="relative w-full">
        <span class="material-symbols-outlined absolute left-3.5 top-1/2 -translate-y-1/2 text-[17px] text-muted pointer-events-none">search</span>
        <input type="text" 
               id="search-stok-mobile"
               oninput="filterBahanStok(this)"
               placeholder="Cari bahan pakan" 
               class="w-full pl-9 pr-3 py-2.5 bg-white border border-[#dce3de] rounded-xl text-xs font-medium text-ink placeholder:text-muted focus:outline-none focus:ring-2 focus:ring-[#185a36]/20 focus:border-[#185a36] shadow-2xs transition"
               autocomplete="off">
    </div>

    {{-- 4. Subheader Bahan di Gudang & Sorting Label --}}
    <div class="flex items-center justify-between text-xs font-bold text-ink px-0.5">
        <span id="label-bahan-count-mobile">
            {{ count($stokGudangData) }} bahan di gudang
        </span>
        <span class="text-[11px] text-muted">
            Urut: paling cepat habis
        </span>
    </div>

    {{-- 5. List Kartu Bahan Stok Mobile --}}
    <div class="space-y-3.5">
        @forelse ($stokGudangData as $d)
            @php
                $rawNama = $d['fi']->ingredient->nama ?? '';
                $safetyKg = max(0.1, (float)$d['safety_buffer_kg']);
                $stokKg = max(0.0, (float)$d['stok_kg']);
                $isHabis = $d['status'] === 'habis' || $stokKg <= 0.05;
                $isMenipis = !$isHabis && ($d['status'] === 'menipis' || $stokKg <= $safetyKg || (float)$d['hari_tersisa'] <= 3.0);
            @endphp
            <div class="bg-white rounded-2xl border border-[#e2e8e4] p-4 shadow-2xs space-y-3 relative overflow-hidden" 
                 data-stok-card 
                 data-stok-nama="{{ strtolower($rawNama) }}">

                {{-- Header Kartu: Nama Bahan, Sub Kategori, Badge Status --}}
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <h3 class="text-base font-black text-ink tracking-tight truncate">
                            {{ $rawNama }}
                        </h3>
                        <p class="text-xs text-muted mt-0.5 truncate capitalize">
                            {{ $d['sub_kategori'] }}
                        </p>
                    </div>

                    {{-- Badge Status Pill --}}
                    <div>
                        @if ($isMenipis)
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-[#fef3c7] text-[#92400e] border border-[#fed7aa] shrink-0">
                                Hampir batas
                            </span>
                        @elseif ($isHabis)
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-[#fee2e2] text-[#991b1b] border border-[#fecaca] shrink-0">
                                Habis
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-[#d1fae5] text-[#065f46] shrink-0">
                                <span class="text-xs">✓</span> Aman
                            </span>
                        @endif
                    </div>
                </div>

                {{-- Ketahanan Hari & Keterangan Stok --}}
                <div class="space-y-1.5 pt-0.5">
                    <div class="flex items-baseline gap-1.5">
                        <span class="text-2xl font-black text-ink tracking-tight tabular-nums">
                            {{ Fmt::num($d['hari_tersisa'], $d['hari_tersisa'] == round($d['hari_tersisa']) ? 0 : 1) }}
                        </span>
                        <span class="text-xs font-bold text-ink">hari lagi</span>
                    </div>

                    {{-- Keterangan Stok & Batas Aman --}}
                    <div class="flex items-center justify-between text-[11px] text-muted font-medium">
                        <span>Stok {{ Fmt::num($d['stok_kg'], $d['stok_kg'] == round($d['stok_kg']) ? 0 : 1) }} kg</span>
                        <span>Batas aman {{ Fmt::num($d['safety_buffer_kg'], 1) }} kg</span>
                    </div>
                </div>

                {{-- 2 Kotak Metrik Berdampingan (Pemakaian & Harga Beli) --}}
                <div class="grid grid-cols-2 gap-2 pt-0.5">
                    <div class="bg-[#f8faf9] border border-[#edf2ee] rounded-xl p-2.5 space-y-0.5">
                        <span class="text-[10px] font-bold text-muted uppercase tracking-wider block">PEMAKAIAN</span>
                        <div class="text-xs sm:text-sm font-black text-ink tabular-nums">
                            {{ Fmt::num($d['kebutuhan_harian_kg'], 1) }} kg/hari
                        </div>
                    </div>
                    <div class="bg-[#f8faf9] border border-[#edf2ee] rounded-xl p-2.5 space-y-0.5">
                        <span class="text-[10px] font-bold text-muted uppercase tracking-wider block">HARGA BELI</span>
                        <div class="text-xs sm:text-sm font-black text-ink tabular-nums truncate">
                            {{ Fmt::rp($d['harga']) }}/kg
                        </div>
                    </div>
                </div>

                {{-- Footer: Nutrisi Singkat, Diperbarui, Tombol Edit & Menu Titik Tiga --}}
                <div class="pt-2 border-t border-[#f0f4f1] flex items-center justify-between gap-2">
                    <div class="min-w-0">
                        @if ($d['fi']->ingredient)
                            <div class="text-[11px] text-muted truncate">
                                PK {{ Fmt::num($d['fi']->ingredient->protein, 0) }}% · EM {{ number_format($d['fi']->ingredient->energi, 0, ',', '.') }} kkal · Ca {{ Fmt::num($d['fi']->ingredient->kalsium, 0) }}%
                            </div>
                        @endif
                        <div class="text-[10px] text-muted/80 mt-0.5 truncate">
                            {{ $d['fi']->harga_updated_at ? 'Diperbarui '.$d['fi']->harga_updated_at->diffForHumans() : 'Diperbarui 2 minggu lalu' }}
                        </div>
                    </div>

                    <div class="flex items-center gap-1.5 shrink-0">
                        {{-- Tombol Edit --}}
                        <button type="button" 
                                onclick="bukaModalUpdateStok({{ $d['fi']->id }}, '{{ addslashes($rawNama) }}', {{ (float)$d['stok_kg'] }}, {{ (int)$d['harga'] }})"
                                class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl text-xs font-bold text-ink bg-white border border-[#d5ded8] hover:border-[#185a36] shadow-2xs transition active:scale-95 cursor-pointer">
                            <span class="material-symbols-outlined text-[15px]">edit</span>
                            <span>Edit</span>
                        </button>

                        {{-- Tombol Dropdown ⋮ Menu --}}
                        <div class="relative" data-dropdown-wrap>
                            <button type="button" 
                                    onclick="toggleDropdownMenu(this, event)"
                                    class="inline-flex items-center justify-center w-8 h-8 rounded-xl text-ink bg-white border border-[#d5ded8] hover:border-[#185a36] shadow-2xs transition active:scale-95 cursor-pointer"
                                    title="Menu lainnya">
                                <span class="material-symbols-outlined text-[18px]">more_vert</span>
                            </button>

                            {{-- Menu Popup Hapus --}}
                            <div class="dropdown-menu-target hidden absolute right-0 bottom-full mb-1 w-44 bg-white rounded-xl shadow-lg border border-[#e2e8e4] py-1 z-30 text-xs">
                                <form method="POST" 
                                      action="{{ route('bahan.gudang.destroy', $d['fi']) }}"
                                      data-confirm="Hapus {{ $rawNama }} dari gudang pakan Anda? Bahan ini tidak akan muncul lagi di peracik ransum."
                                      data-confirm-title="Hapus Bahan dari Gudang"
                                      data-confirm-btn="Ya, Hapus Bahan">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" 
                                            class="w-full flex items-center gap-2 px-3 py-1.5 text-rose-600 hover:bg-rose-50 font-semibold text-left transition cursor-pointer">
                                        <span class="material-symbols-outlined text-[16px]">delete</span>
                                        <span>Hapus dari Gudang</span>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="bg-white rounded-2xl border border-dashed border-amber-300 p-8 text-center space-y-3">
                <span class="material-symbols-outlined text-[44px] text-amber-500 block">inventory_2</span>
                <div class="space-y-1">
                    <h3 class="text-base font-bold text-ink">Gudang Pakan Masih Kosong</h3>
                    <p class="text-xs text-muted max-w-md mx-auto leading-relaxed">
                        Belum ada bahan pakan yang terdaftar di gudang kandang ini. Tambahkan bahan pakan yang Anda miliki untuk mulai memantau stok dan meracik ransum.
                    </p>
                </div>
                <button type="button" 
                        onclick="bukaModal('modalTambahBahan')"
                        class="inline-flex items-center gap-1.5 px-5 py-2.5 rounded-full text-xs font-bold text-white bg-brand hover:bg-brand-dark transition shadow-2xs active:scale-95 cursor-pointer">
                    <span class="material-symbols-outlined text-[18px]">add</span>
                    <span>Tambah Bahan Pertama ke Gudang</span>
                </button>
            </div>
        @endforelse

        {{-- Empty Search Filter Mobile --}}
        <div id="stok-empty-mobile" class="hidden bg-white rounded-xl border border-line text-center py-10 text-muted space-y-1">
            <span class="material-symbols-outlined text-[36px] text-muted/50 mb-1 block">search_off</span>
            <p class="font-bold text-ink text-sm">Bahan Gudang Tidak Ditemukan</p>
            <p class="text-xs text-muted">Tidak ada bahan pakan di gudang yang sesuai dengan kata kunci pencarian Anda.</p>
        </div>
    </div>
</div>

{{-- ======================================================== --}}
{{-- 1. MODAL TAMBAH BAHAN KE GUDANG (CENTERED & UI DROPDOWN) --}}
{{-- ======================================================== --}}
<div id="modalTambahBahan" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-xs transition-opacity duration-200 hidden opacity-0 pointer-events-none" role="dialog" aria-modal="true">
    <div class="absolute inset-0" onclick="tutupModal('modalTambahBahan')"></div>
    <div data-modal-card class="relative z-10 w-full max-w-lg bg-white rounded-2xl p-5 sm:p-6 shadow-2xl border border-line transform transition-all duration-200 scale-95 opacity-0 space-y-4 max-h-[92vh] overflow-y-auto">
        {{-- Header Modal --}}
        <div class="flex items-center justify-between pb-3 border-b border-line">
            <div class="flex items-center gap-2.5">
                <div class="w-9 h-9 rounded-xl bg-brand/10 text-brand grid place-items-center shrink-0">
                    <span class="material-symbols-outlined text-[20px]">add_circle</span>
                </div>
                <div>
                    <h3 class="text-sm sm:text-base font-bold text-ink">Tambah Bahan ke Gudang</h3>
                    <p class="text-[11px] text-muted">Pilih bahan pakan resmi, nutrisi terisi otomatis.</p>
                </div>
            </div>
            <button type="button" onclick="tutupModal('modalTambahBahan')" class="p-1.5 rounded-lg text-muted hover:text-ink hover:bg-surface cursor-pointer">
                <span class="material-symbols-outlined text-[18px]">close</span>
            </button>
        </div>

        <form method="POST" action="{{ route('bahan.store') }}" class="space-y-4">
            @csrf

            {{-- 1. Custom UI Dropdown Bahan Pakan --}}
            <div class="space-y-1.5">
                <label class="lbl text-xs font-bold" for="input-nama-template">
                    <span>Pilih Bahan Pakan Ayam Petelur</span>
                    <span class="text-danger">*</span>
                </label>

                <div class="relative w-full text-left custom-dropdown-container z-30" id="dropdown-master-bahan">
                    <input type="hidden" name="nama_template" id="input-nama-template" value="" class="dropdown-hidden-input" required>

                    {{-- Trigger Button UI --}}
                    <button type="button" 
                            onclick="toggleCustomDropdown('dropdown-master-bahan', event)" 
                            class="dropdown-trigger w-full min-h-11 px-3.5 py-2.5 bg-white border border-line rounded-xl text-xs sm:text-sm font-semibold text-ink shadow-2xs hover:border-brand/40 focus:border-brand focus:ring-2 focus:ring-brand/15 transition cursor-pointer flex items-center justify-between"
                            aria-haspopup="true"
                            aria-expanded="false">
                        <div class="flex items-center gap-2.5 min-w-0 pr-1">
                            <span class="material-symbols-outlined text-[18px] text-muted dropdown-selected-icon">inventory_2</span>
                            <span class="truncate dropdown-selected-label text-muted" id="label-selected-bahan">Pilih Bahan Pakan...</span>
                        </div>
                        <span class="material-symbols-outlined text-[18px] text-muted transition-transform duration-200 dropdown-caret shrink-0">expand_more</span>
                    </button>

                    {{-- Dropdown Card Menu --}}
                    <div class="dropdown-menu hidden absolute left-0 right-0 top-full mt-1.5 rounded-2xl bg-white border border-line shadow-2xl p-2 z-50 select-none custom-dropdown-menu flex flex-col">
                        {{-- Search Input inside Dropdown --}}
                        <div class="p-1.5 border-b border-line/60">
                            <div class="relative flex items-center">
                                <span class="material-symbols-outlined absolute left-2.5 text-[16px] text-muted pointer-events-none">search</span>
                                <input type="text"
                                       data-dropdown-search
                                       oninput="filterMasterBahanDropdown(this)"
                                       placeholder="Cari bahan (mis: Jagung, Dedak)..."
                                       class="w-full pl-8 pr-3 py-1.5 bg-surface/60 border border-line rounded-lg text-xs text-ink placeholder:text-muted focus:outline-none focus:ring-1 focus:ring-brand focus:border-brand transition"
                                       autocomplete="off">
                            </div>
                        </div>

                        {{-- List Pilihan Bahan Pakan --}}
                        <div class="overflow-y-auto space-y-1 p-1 max-h-56" id="list-master-bahan">
                            @foreach ($masterCatalog as $key => $item)
                                @php $alreadyIn = in_array($item['nama'], $existingNames, true); @endphp
                                <button type="button"
                                        data-master-item
                                        data-key="{{ $key }}"
                                        data-nama="{{ strtolower($item['label'] ?? $item['nama']) }}"
                                        data-label="{{ $item['label'] ?? $item['nama'] }}"
                                        data-kategori="{{ $item['kategori'] ?? '' }}"
                                        data-protein="{{ $item['protein'] ?? 0 }}"
                                        data-energi="{{ $item['energi'] ?? 0 }}"
                                        data-kalsium="{{ $item['kalsium'] ?? 0 }}"
                                        data-fosfor="{{ $item['fosfor'] ?? 0 }}"
                                        data-serat="{{ $item['serat'] ?? 0 }}"
                                        data-harga="{{ $item['harga_default'] ?? 0 }}"
                                        data-deskripsi="{{ $item['deskripsi'] ?? $item['catatan_keamanan'] ?? '' }}"
                                        onclick="pilihMasterBahanUi(this)"
                                        class="w-full flex items-center justify-between px-3 py-2 rounded-xl text-left text-xs transition-all cursor-pointer group hover:bg-surface {{ $alreadyIn ? 'opacity-70 bg-surface/40' : '' }}">
                                    <div class="flex items-center gap-2.5 min-w-0 pr-2">
                                        <div class="w-7 h-7 rounded-lg bg-surface-2 group-hover:bg-brand/10 text-muted group-hover:text-brand flex items-center justify-center shrink-0 transition-colors">
                                            <span class="material-symbols-outlined text-[15px]">inventory_2</span>
                                        </div>
                                        <div class="min-w-0">
                                            <span class="font-bold text-xs text-ink group-hover:text-brand block truncate">
                                                {{ $item['label'] ?? $item['nama'] }}
                                            </span>
                                            <span class="text-[10px] text-muted block truncate">
                                                PK {{ $item['protein'] }}% • EM {{ $item['energi'] }} kkal
                                            </span>
                                        </div>
                                    </div>
                                    @if ($alreadyIn)
                                        <span class="text-[10px] font-semibold text-muted bg-surface-2 px-2 py-0.5 rounded-full shrink-0">
                                            Di Gudang
                                        </span>
                                    @else
                                        <span class="text-[10px] font-bold text-brand bg-brand-soft px-2 py-0.5 rounded-full opacity-0 group-hover:opacity-100 transition-opacity shrink-0">
                                            Pilih
                                        </span>
                                    @endif
                                </button>
                            @endforeach
                            <div id="master-item-empty" class="hidden p-4 text-center text-xs text-muted">
                                Tidak ada bahan pakan yang cocok.
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Preview Nutrisi Otomatis Terkunci (Live JS) --}}
            <div id="preview-nutrisi-box" class="hidden p-3.5 rounded-xl bg-emerald-50/70 border border-emerald-200/80 space-y-2">
                <div class="flex items-center justify-between">
                    <span id="preview-kategori" class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800">
                        Kategori
                    </span>
                    <span class="text-[10px] font-bold text-emerald-700 flex items-center gap-1">
                        <span class="material-symbols-outlined text-[13px]">verified</span>
                        <span>Acuan Baku Fapet UGM</span>
                    </span>
                </div>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 text-center text-xs pt-1">
                    <div class="p-1.5 rounded-lg bg-white/80 border border-emerald-100">
                        <span class="text-[10px] text-muted block">Protein (PK)</span>
                        <strong id="preview-protein" class="text-xs font-bold text-ink">0%</strong>
                    </div>
                    <div class="p-1.5 rounded-lg bg-white/80 border border-emerald-100">
                        <span class="text-[10px] text-muted block">Energi (EM)</span>
                        <strong id="preview-energi" class="text-xs font-bold text-ink">0 kkal</strong>
                    </div>
                    <div class="p-1.5 rounded-lg bg-white/80 border border-emerald-100">
                        <span class="text-[10px] text-muted block">Kalsium (Ca)</span>
                        <strong id="preview-kalsium" class="text-xs font-bold text-ink">0%</strong>
                    </div>
                    <div class="p-1.5 rounded-lg bg-white/80 border border-emerald-100">
                        <span class="text-[10px] text-muted block">Fosfor (P)</span>
                        <strong id="preview-fosfor" class="text-xs font-bold text-ink">0%</strong>
                    </div>
                </div>
                <p id="preview-deskripsi" class="text-[11px] text-emerald-900/80 italic pt-1"></p>
            </div>

            {{-- 2. Input Jumlah Stok (Kg) --}}
            <div>
                <label class="lbl text-xs font-bold" for="input-tambah-stok">
                    <span>Jumlah Stok yang Masuk / Dimiliki</span>
                    <span class="text-danger">*</span>
                </label>
                <div class="relative flex items-center">
                    <input type="number" 
                           id="input-tambah-stok" 
                           name="stok_kg" 
                           min="0" 
                           step="0.1" 
                           placeholder="Contoh: 500" 
                           class="field font-semibold text-ink text-sm !pr-10" 
                           required>
                    <span class="absolute right-3.5 text-xs font-bold text-muted pointer-events-none select-none">kg</span>
                </div>
                <p class="text-[11px] text-muted mt-1">Petunjuk: 1 karung sak = ~50 kg (Contoh: 10 sak = 500 kg).</p>
            </div>

            {{-- 3. Input Harga Beli (Rp/kg) --}}
            <div>
                <label class="lbl text-xs font-bold" for="input-tambah-harga">
                    <span>Harga Beli per Kilogram</span>
                    <span class="text-danger">*</span>
                </label>
                <div class="relative flex items-center">
                    <span class="absolute left-3.5 text-xs font-bold text-muted pointer-events-none select-none">Rp</span>
                    <input type="number" 
                           id="input-tambah-harga" 
                           name="harga" 
                           min="0" 
                           step="50" 
                           placeholder="Contoh: 6500" 
                           class="field !pl-10 font-semibold text-ink text-sm" 
                           required>
                    <span class="absolute right-3.5 text-xs font-normal text-muted pointer-events-none select-none">/ kg</span>
                </div>
                <p class="text-[11px] text-muted mt-1">Harga beli riil di sentra Anda untuk perhitungan akurat HPP pakan.</p>
            </div>

            {{-- Tombol Aksi Modal --}}
            <div class="flex items-center justify-end gap-2 pt-3 border-t border-line">
                <button type="button" 
                        onclick="tutupModal('modalTambahBahan')" 
                        class="px-4 py-2 rounded-full text-xs font-semibold text-ink bg-white hover:bg-surface border border-line transition shadow-2xs cursor-pointer">
                    Batal
                </button>
                <button type="submit" 
                        class="px-5 py-2 rounded-full text-xs font-bold text-white bg-brand hover:bg-brand-dark transition shadow-xs hover:shadow-sm cursor-pointer">
                    Tambahkan ke Gudang
                </button>
            </div>
        </form>
    </div>
</div>

{{-- ======================================================== --}}
{{-- 2. MODAL EDIT / UPDATE STOK & HARGA GUDANG (CENTERED)    --}}
{{-- ======================================================== --}}
<div id="modalUpdateStok" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-xs transition-opacity duration-200 hidden opacity-0 pointer-events-none" role="dialog" aria-modal="true">
    <div class="absolute inset-0" onclick="tutupModal('modalUpdateStok')"></div>
    <div data-modal-card class="relative z-10 w-full max-w-md bg-white rounded-2xl p-5 sm:p-6 shadow-2xl border border-line transform transition-all duration-200 scale-95 opacity-0 space-y-4 max-h-[92vh] overflow-y-auto">
        <div class="flex items-center justify-between pb-3 border-b border-line">
            <div>
                <h3 class="text-sm sm:text-base font-bold text-ink">Edit Stok & Harga Bahan</h3>
                <p class="text-xs text-muted" id="modal-update-nama">Bahan Pakan</p>
            </div>
            <button type="button" onclick="tutupModal('modalUpdateStok')" class="p-1 rounded-lg text-muted hover:text-ink hover:bg-surface cursor-pointer">
                <span class="material-symbols-outlined text-[18px]">close</span>
            </button>
        </div>

        <form method="POST" action="{{ route('bahan.stok.update') }}" class="space-y-4">
            @csrf
            <input type="hidden" id="modal-update-id" name="farm_ingredient_id" value="">

            <div>
                <label class="lbl text-xs font-bold" for="modal-input-stok">Stok Fisik di Gudang</label>
                <div class="relative flex items-center">
                    <input type="number" 
                           id="modal-input-stok" 
                           name="stok_kg" 
                           min="0" 
                           step="0.1" 
                           inputmode="decimal" 
                           class="field font-semibold text-ink text-sm !pr-10" 
                           required>
                    <span class="absolute right-3.5 text-xs font-bold text-muted pointer-events-none select-none">kg</span>
                </div>
                <p class="text-[11px] text-muted mt-1">Perbarui jumlah kilogram persediaan nyata di gudang.</p>
            </div>

            <div>
                <label class="lbl text-xs font-bold" for="modal-input-harga">Harga Beli Terkini (per kg)</label>
                <div class="relative flex items-center">
                    <span class="absolute left-3.5 text-xs font-bold text-muted pointer-events-none select-none">Rp</span>
                    <input type="number" 
                           id="modal-input-harga" 
                           name="harga" 
                           min="0" 
                           step="50" 
                           inputmode="numeric" 
                           class="field !pl-10 font-semibold text-ink text-sm">
                    <span class="absolute right-3.5 text-xs font-normal text-muted pointer-events-none select-none">/ kg</span>
                </div>
                <p class="text-[11px] text-muted mt-1">Harga beli per kilogram untuk perhitungan akurasi HPP pakan.</p>
            </div>

            <div class="flex items-center justify-end gap-2 pt-2 border-t border-line">
                <button type="button" 
                        onclick="tutupModal('modalUpdateStok')" 
                        class="px-4 py-2 rounded-full text-xs font-semibold text-ink bg-white hover:bg-surface border border-line transition shadow-2xs cursor-pointer">
                    Batal
                </button>
                <button type="submit" 
                        class="px-5 py-2 rounded-full text-xs font-bold text-white bg-brand hover:bg-brand-dark transition shadow-xs hover:shadow-sm cursor-pointer">
                    Simpan Perubahan
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function pilihMasterBahanUi(btn) {
    const key = btn.getAttribute('data-key');
    const label = btn.getAttribute('data-label');
    const kategori = btn.getAttribute('data-kategori') || 'Bahan Pakan';
    const protein = btn.getAttribute('data-protein') || '0';
    const energi = btn.getAttribute('data-energi') || '0';
    const kalsium = btn.getAttribute('data-kalsium') || '0';
    const fosfor = btn.getAttribute('data-fosfor') || '0';
    const harga = btn.getAttribute('data-harga') || '';
    const deskripsi = btn.getAttribute('data-deskripsi') || '';

    // Set hidden input value
    document.getElementById('input-nama-template').value = key;

    // Update trigger UI
    const labelEl = document.getElementById('label-selected-bahan');
    if (labelEl) {
        labelEl.textContent = label;
        labelEl.classList.remove('text-muted');
        labelEl.classList.add('text-ink', 'font-bold');
    }

    const triggerIcon = document.querySelector('#dropdown-master-bahan .dropdown-selected-icon');
    if (triggerIcon) {
        triggerIcon.classList.remove('text-muted');
        triggerIcon.classList.add('text-brand');
    }

    // Close dropdown
    const container = document.getElementById('dropdown-master-bahan');
    if (container) {
        const menu = container.querySelector('.dropdown-menu');
        const caret = container.querySelector('.dropdown-caret');
        const trigger = container.querySelector('.dropdown-trigger');
        if (menu) menu.classList.add('hidden');
        if (caret) caret.classList.remove('rotate-180');
        if (trigger) trigger.setAttribute('aria-expanded', 'false');
    }

    // Update live nutrition preview box
    document.getElementById('preview-kategori').textContent = kategori;
    document.getElementById('preview-protein').textContent = protein + '%';
    document.getElementById('preview-energi').textContent = Number(energi).toLocaleString('id-ID') + ' kkal';
    document.getElementById('preview-kalsium').textContent = kalsium + '%';
    document.getElementById('preview-fosfor').textContent = fosfor + '%';
    document.getElementById('preview-deskripsi').textContent = deskripsi;

    const hargaInput = document.getElementById('input-tambah-harga');
    if (hargaInput && !hargaInput.value) {
        hargaInput.value = harga;
    }

    const box = document.getElementById('preview-nutrisi-box');
    if (box) {
        box.classList.remove('hidden');
    }
}

function filterMasterBahanDropdown(input) {
    const query = input.value.toLowerCase().trim();
    const items = document.querySelectorAll('#list-master-bahan [data-master-item]');
    const emptyEl = document.getElementById('master-item-empty');
    let visibleCount = 0;

    items.forEach(item => {
        const nama = item.getAttribute('data-nama') || '';
        const match = query === '' || nama.includes(query);
        item.style.display = match ? 'flex' : 'none';
        if (match) visibleCount++;
    });

    if (emptyEl) {
        emptyEl.classList.toggle('hidden', visibleCount > 0);
    }
}

function bukaModalUpdateStok(id, nama, stok, harga) {
    document.getElementById('modal-update-id').value = id;
    document.getElementById('modal-update-nama').textContent = nama;
    document.getElementById('modal-input-stok').value = stok;
    document.getElementById('modal-input-harga').value = harga;
    bukaModal('modalUpdateStok');
}

function filterBahanStok(input) {
    const query = input.value.toLowerCase().trim();
    
    // Sinkronkan input pencarian desktop & mobile jika ada
    const searchDesktop = document.getElementById('search-stok-live');
    const searchMobile = document.getElementById('search-stok-mobile');
    if (input === searchDesktop && searchMobile && searchMobile.value !== input.value) {
        searchMobile.value = input.value;
    } else if (input === searchMobile && searchDesktop && searchDesktop.value !== input.value) {
        searchDesktop.value = input.value;
    }

    const cards = document.querySelectorAll('[data-stok-card]');
    const emptyDesktop = document.getElementById('stok-empty-desktop');
    const emptyMobile = document.getElementById('stok-empty-mobile');
    const labelCount = document.getElementById('label-bahan-count-mobile');
    let visibleDesktop = 0;
    let visibleMobile = 0;

    cards.forEach(card => {
        const nama = card.getAttribute('data-stok-nama') || '';
        const match = query === '' || nama.includes(query);
        card.style.display = match ? '' : 'none';
        if (match) {
            if (card.closest('.hidden.sm\:block')) {
                visibleDesktop++;
            } else {
                visibleMobile++;
            }
        }
    });

    if (emptyDesktop) {
        emptyDesktop.classList.toggle('hidden', visibleDesktop > 0);
    }
    if (emptyMobile) {
        emptyMobile.classList.toggle('hidden', visibleMobile > 0);
    }
    if (labelCount) {
        labelCount.textContent = `${visibleMobile} bahan di gudang`;
    }
}

function toggleDropdownMenu(btn, e) {
    e.stopPropagation();
    const wrap = btn.closest('[data-dropdown-wrap]');
    if (!wrap) return;
    const menu = wrap.querySelector('.dropdown-menu-target');
    if (!menu) return;
    const isHidden = menu.classList.contains('hidden');
    document.querySelectorAll('.dropdown-menu-target').forEach(m => m.classList.add('hidden'));
    if (isHidden) {
        menu.classList.remove('hidden');
    }
}

document.addEventListener('click', function (e) {
    if (!e.target.closest('[data-dropdown-wrap]')) {
        document.querySelectorAll('.dropdown-menu-target').forEach(m => m.classList.add('hidden'));
    }
});
</script>
@endsection
