@extends('layouts.app')
@section('judul', 'Buku Kandang')
@section('isi')
@use('App\Support\Fmt')
@use('Illuminate\Support\Str')

{{-- Mobile Header (Image 1 style) --}}
<div class="block md:hidden space-y-1.5 mb-2">
    <div class="flex items-center justify-between gap-3">
        <h1 class="text-2xl font-black text-[#0f3d26] tracking-tight">Produksi</h1>
        <a href="{{ route('kandang.index') }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-2xl text-xs font-bold text-[#0f3d26] bg-white border border-[#d9e6dd] shadow-2xs hover:bg-[#f4f8f5] transition shrink-0" title="Kelola Multi-Kandang">
            <span class="material-symbols-outlined text-[18px] text-[#1f7a4a]">segment</span>
            <span>Ganti Kandang</span>
        </a>
    </div>
    <p class="text-xs text-[#567263] leading-relaxed">Catatan produksi harian, konversi pakan (FCR), dan efisiensi kandang.</p>
</div>

{{-- Desktop Header --}}
<div class="hidden md:flex flex-wrap items-center justify-between gap-3 mb-2">
    <div>
        <div class="flex items-baseline gap-2">
            <h1 class="text-lg md:text-xl font-extrabold text-ink tracking-tight">{{ $farm->nama ?? 'Kandang Maju Jaya' }}</h1>
            <span class="text-xs sm:text-sm text-muted font-medium">({{ number_format($jumlahAyam, 0, ',', '.') }} ekor)</span>
        </div>
        <p class="text-muted text-xs sm:text-sm mt-0.5">Catatan produksi harian, konversi pakan (FCR), dan efisiensi kandang</p>
    </div>

    <div class="flex items-center gap-2 shrink-0">
        <a href="{{ route('kandang.index') }}" class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full text-xs font-bold text-ink hover:text-brand bg-white hover:bg-surface-2 border border-line hover:border-brand/40 shadow-2xs hover:shadow-xs transition" title="Kelola Multi-Kandang">
            <span class="material-symbols-outlined text-[15px] text-brand">tune</span>
            <span>Ganti Kandang</span>
        </a>
    </div>
</div>

{{-- Tabs --}}
@php
    $tabAktif = request()->query('tab', 'form');
@endphp

<div data-tabs class="space-y-4">
    <div class="flex justify-start">
        <div data-tab-nav class="tab-segmented-track relative flex items-center p-1 rounded-2xl md:rounded-full text-center select-none w-full max-w-lg" style="background-color: #e4eee7;">
            <div data-tab-slider class="tab-slider !rounded-xl md:!rounded-full" aria-hidden="true"></div>
            <button type="button" data-tab="form" class="flex-1 relative z-10 py-2 px-1.5 rounded-xl md:rounded-full transition-colors duration-200 cursor-pointer outline-none focus:outline-none focus:ring-0 select-none whitespace-nowrap text-[11px] sm:text-xs font-bold {{ $tabAktif === 'form' ? 'active-tab bg-white shadow-xs text-[#0f3d26]' : 'text-[#567263] hover:text-[#0f3d26]' }}">
                Catat Harian
            </button>
            <button type="button" data-tab="dashboard" class="flex-1 relative z-10 py-2 px-1.5 rounded-xl md:rounded-full transition-colors duration-200 cursor-pointer outline-none focus:outline-none focus:ring-0 select-none whitespace-nowrap text-[11px] sm:text-xs font-bold {{ $tabAktif === 'dashboard' ? 'active-tab bg-white shadow-xs text-[#0f3d26]' : 'text-[#567263] hover:text-[#0f3d26]' }}">
                Laporan Performa
            </button>
            <button type="button" data-tab="beli" class="flex-1 relative z-10 py-2 px-1.5 rounded-xl md:rounded-full transition-colors duration-200 cursor-pointer outline-none focus:outline-none focus:ring-0 select-none whitespace-nowrap text-[11px] sm:text-xs font-bold {{ $tabAktif === 'beli' ? 'active-tab bg-white shadow-xs text-[#0f3d26]' : 'text-[#567263] hover:text-[#0f3d26]' }}">
                Belanja Pakan
            </button>
        </div>
    </div>

    {{-- Panel 1: Form Catatan Harian Cerdas --}}
    <section data-panel="form" class="{{ $tabAktif === 'form' ? '' : 'hidden' }} space-y-4">
        {{-- Grid 2 Kolom: Kiri (Form Input) & Kanan (Live Cockpit & Pratinjau Analitik) --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-5 items-stretch">
            {{-- KOLOM KIRI (1/2): Formulir Input Catatan Harian (Image 1 style) --}}
            <div class="flex flex-col">
                <form method="POST" action="{{ route('catatan.store') }}" class="bg-white rounded-3xl p-4 sm:p-5 shadow-xs space-y-4 flex-1 flex flex-col justify-between" style="border: none;">
                    @csrf
                    <div class="space-y-4">
                        {{-- Header Form Bersih & Tegas (Image 1 style) --}}
                        <div class="flex items-center gap-3 pb-3 border-b border-[#eef4f0]">
                            <div class="w-10 h-10 rounded-2xl flex items-center justify-center shrink-0" style="background-color: #dcefe2; color: #1f7a4a;">
                                <span class="material-symbols-outlined text-[22px]">edit</span>
                            </div>
                            <div>
                                <h2 class="text-base font-extrabold text-[#0f3d26] tracking-tight leading-tight">Catat Data Hari Ini</h2>
                                <p class="text-xs text-[#567263] font-medium">{{ $farm->nama }}</p>
                            </div>
                        </div>

                        {{-- Banner Mode Edit (Muncul jika tanggal yang dipilih sudah ada catatan) --}}
                        <div id="mode-status-banner" class="{{ $isEdit ? '' : 'hidden' }} p-3 rounded-2xl flex items-center justify-between gap-3 shadow-2xs transition-all" style="background-color: #fffbeb; border: 1px solid #fef3c7;">
                            <div class="flex items-center gap-2.5 min-w-0">
                                <div class="w-9 h-9 rounded-xl flex items-center justify-center shrink-0 shadow-2xs" style="background-color: #fef3c7; color: #b45309;">
                                    <span class="material-symbols-outlined text-[20px]">edit_calendar</span>
                                </div>
                                <div class="min-w-0">
                                    <div class="flex items-center gap-1.5 flex-wrap">
                                        <span class="px-2 py-0.5 rounded-md text-[10px] font-extrabold uppercase tracking-wide" style="background-color: #fde68a; color: #78350f;">Mode Edit</span>
                                        <span class="text-xs font-bold text-[#0f3d26]">Catatan <span id="mode-status-date">{{ $form->tanggal->translatedFormat('d M Y') }}</span></span>
                                    </div>
                                    <p class="text-[11px] text-[#567263] mt-0.5 truncate">Form ini akan memperbarui data yang tersimpan.</p>
                                </div>
                            </div>
                            <a href="{{ route('catatan.index', ['tab' => 'form']) }}" class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl text-xs font-bold transition shadow-2xs hover:shadow-xs active:scale-95 shrink-0" style="background-color: #ffffff; color: #92400e; border: 1px solid #fde68a;" title="Kembali ke formulir hari ini">
                                <span class="material-symbols-outlined text-[15px]" style="color: #b45309;">restart_alt</span>
                                <span>Reset</span>
                            </a>
                        </div>

                        {{-- Quick Fill dari Catatan Kemarin --}}
                        @if ($yesterdayLog)
                        <div class="flex items-center justify-between gap-2 bg-[#edf5f0] p-2.5 rounded-xl" style="border: none;">
                            <div class="text-xs text-[#567263] truncate">
                                <span class="font-bold text-[#0f3d26]">Catatan Kemarin ({{ $yesterdayLog->tanggal->translatedFormat('d M') }}):</span>
                                {{ Fmt::num($yesterdayLog->pakan_kg, 1) }} kg · {{ Fmt::num($yesterdayLog->telur_butir, 0) }} btr
                            </div>
                            <button type="button" id="btn-quick-fill"
                                    data-pakan="{{ (float) $yesterdayLog->pakan_kg }}"
                                    data-telur="{{ (int) $yesterdayLog->telur_butir }}"
                                    data-berat="{{ $yesterdayLog->telur_kg !== null ? (float) $yesterdayLog->telur_kg : '' }}"
                                    class="px-2.5 py-1 rounded-lg text-xs font-bold text-[#1f7a4a] hover:text-[#18643c] bg-white transition shadow-2xs flex items-center gap-1 cursor-pointer shrink-0"
                                    style="border: 1px solid #d9e6dd; color: #1f7a4a;">
                                <span class="material-symbols-outlined text-[15px] text-amber-500 icon-fill">bolt</span>
                                Salin Angka Kemarin
                            </button>
                        </div>
                        @endif

                        {{-- Form Inputs Stack (Image 1 style) --}}
                        <div class="space-y-3.5">
                            {{-- Field 1: Tanggal Pencatatan --}}
                            <div>
                                <label class="block text-xs font-bold text-[#0f3d26] mb-1.5" for="tanggal">Tanggal Pencatatan</label>
                                <div class="relative cursor-pointer">
                                    <input id="tanggal" name="tanggal" type="date" value="{{ old('tanggal', $form->tanggal->toDateString()) }}" max="{{ today()->toDateString() }}" class="w-full h-11 rounded-xl bg-white px-3.5 pr-10 text-sm font-semibold text-[#0f3d26] focus:border-[#1f7a4a] focus:ring-1 focus:ring-[#1f7a4a] outline-none cursor-pointer opacity-0 absolute inset-0 z-10" style="border: 1px solid #d9e6dd;" required>
                                    <div id="tanggal-display" class="w-full h-11 rounded-xl bg-white px-3.5 pr-10 text-sm font-semibold text-[#0f3d26] flex items-center pointer-events-none" style="border: 1px solid #d9e6dd;">
                                        {{ $form->tanggal->translatedFormat('j F Y') }}
                                    </div>
                                    <span class="absolute right-3.5 top-1/2 -translate-y-1/2 material-symbols-outlined text-[18px] pointer-events-none z-20" style="color: #1f7a4a;">calendar_today</span>
                                </div>
                            </div>

                            {{-- Field 2: Ayam Mati --}}
                            <div>
                                <label class="block text-xs font-bold text-[#0f3d26] mb-1.5" for="ayam_mati">Ayam Mati</label>
                                <div class="relative">
                                    <input id="ayam_mati" name="ayam_mati" type="number" min="0" class="w-full h-11 rounded-xl bg-white px-3.5 pr-12 text-sm font-semibold text-[#0f3d26] focus:border-[#1f7a4a] focus:ring-1 focus:ring-[#1f7a4a] outline-none" style="border: 1px solid #d9e6dd; color: #0f3d26;" value="{{ old('ayam_mati', $form->ayam_mati ?? 0) }}" placeholder="0">
                                    <span class="absolute right-3.5 top-1/2 -translate-y-1/2 text-xs font-semibold text-[#567263] pointer-events-none">ekor</span>
                                </div>
                            </div>

                            {{-- Field 3: Jumlah Telur --}}
                            <div class="relative">
                                <div class="flex items-center justify-between mb-1.5">
                                    <label class="block text-xs font-bold text-[#0f3d26]" for="telur_butir">Jumlah Telur</label>
                                    <button type="button" id="btn-toggle-rak-calc" class="inline-flex items-center gap-1 text-xs font-bold cursor-pointer" style="color: #1f7a4a;">
                                        <span class="material-symbols-outlined text-[16px]">grid_view</span>
                                        <span>Hitung per rak (30)</span>
                                    </button>
                                </div>
                                <div class="relative">
                                    <input id="telur_butir" name="telur_butir" type="number" min="0" class="w-full h-11 rounded-xl bg-white px-3.5 pr-14 text-sm font-semibold text-[#0f3d26] focus:border-[#1f7a4a] focus:ring-1 focus:ring-[#1f7a4a] outline-none" style="border: 1px solid #d9e6dd; color: #0f3d26;" value="{{ old('telur_butir', $form->telur_butir ?? 0) }}" required placeholder="0">
                                    <span class="absolute right-3.5 top-1/2 -translate-y-1/2 text-xs font-semibold text-[#567263] pointer-events-none">butir</span>
                                </div>

                                {{-- Popover Mini Kalkulator Rak Telur --}}
                                <div id="popover-calc-rak" class="hidden absolute top-full left-0 right-0 z-30 mt-1.5 p-3.5 rounded-2xl bg-white border border-[#1f7a4a]/30 shadow-xl space-y-2.5">
                                    <div class="flex items-center justify-between pb-1.5 border-b border-[#eef4f0] text-xs">
                                        <span class="font-bold text-[#0f3d26] flex items-center gap-1.5">
                                            <span class="material-symbols-outlined text-[#1f7a4a] text-[18px]">calculate</span>
                                            Kalkulator Rak Telur
                                        </span>
                                        <span class="text-[10px] text-[#1f7a4a] font-bold bg-[#e4eee7] px-2 py-0.5 rounded-full">1 Rak = 30 butir</span>
                                    </div>
                                    <div class="grid grid-cols-2 gap-2 text-xs">
                                        <div>
                                            <label class="block text-[11px] text-[#567263] font-semibold mb-1" for="calc_rak_input">Jumlah Rak</label>
                                            <div class="relative">
                                                <input type="number" id="calc_rak_input" min="0" step="1" placeholder="0" class="w-full h-9 px-3 pr-8 rounded-xl bg-surface border border-[#d9e6dd] text-xs font-bold text-[#0f3d26] focus:border-[#1f7a4a] focus:ring-1 focus:ring-[#1f7a4a] outline-none">
                                                <span class="absolute right-2.5 top-1/2 -translate-y-1/2 text-[11px] text-[#567263] font-medium pointer-events-none">rak</span>
                                            </div>
                                        </div>
                                        <div>
                                            <label class="block text-[11px] text-[#567263] font-semibold mb-1" for="calc_sisa_input">Sisa Butir</label>
                                            <div class="relative">
                                                <input type="number" id="calc_sisa_input" min="0" step="1" placeholder="0" class="w-full h-9 px-3 pr-8 rounded-xl bg-surface border border-[#d9e6dd] text-xs font-bold text-[#0f3d26] focus:border-[#1f7a4a] focus:ring-1 focus:ring-[#1f7a4a] outline-none">
                                                <span class="absolute right-2.5 top-1/2 -translate-y-1/2 text-[11px] text-[#567263] font-medium pointer-events-none">btr</span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="p-2.5 rounded-xl bg-emerald-50 border border-emerald-200/70 flex items-center justify-between text-xs">
                                        <span class="text-emerald-900 text-xs font-medium">Hasil Total:</span>
                                        <strong id="calc_rak_total_preview" class="text-sm font-black text-emerald-800">0 butir</strong>
                                    </div>

                                    <div class="flex items-center justify-end gap-2 pt-1">
                                        <button type="button" id="btn-cancel-rak-calc" class="px-3 py-1.5 rounded-xl text-xs font-semibold text-[#567263] hover:text-[#0f3d26] hover:bg-[#f4f8f5] transition cursor-pointer">
                                            Batal
                                        </button>
                                        <button type="button" id="btn-apply-rak-calc" class="px-3.5 py-1.5 rounded-xl bg-[#1f7a4a] hover:bg-[#18643c] text-white text-xs font-bold shadow-2xs transition active:scale-95 cursor-pointer">
                                            Terapkan ke Form
                                        </button>
                                    </div>
                                </div>
                            </div>

                            {{-- Field 4: Pakan Terpakai --}}
                            <div>
                                <label class="block text-xs font-bold text-[#0f3d26] mb-1.5" for="pakan_kg">Pakan Terpakai</label>
                                <div class="relative">
                                    <input id="pakan_kg" name="pakan_kg" type="number" step="0.1" min="0" class="w-full h-11 rounded-xl bg-white px-3.5 pr-10 text-sm font-semibold text-[#0f3d26] focus:border-[#1f7a4a] focus:ring-1 focus:ring-[#1f7a4a] outline-none" style="border: 1px solid #d9e6dd; color: #0f3d26;" value="{{ old('pakan_kg', (float) ($form->pakan_kg ?? 0)) }}" required placeholder="0">
                                    <span class="absolute right-3.5 top-1/2 -translate-y-1/2 text-xs font-semibold text-[#567263] pointer-events-none">kg</span>
                                </div>
                            </div>

                            {{-- Field 5: Total Berat Telur --}}
                            <div>
                                <div class="flex items-center justify-between mb-1.5">
                                    <label class="block text-xs font-bold text-[#0f3d26]" for="telur_kg">Total Berat Telur</label>
                                    <span class="text-xs text-[#567263] font-normal">Opsional</span>
                                </div>
                                <div class="relative">
                                    <input id="telur_kg" name="telur_kg" type="number" step="0.1" min="0" class="w-full h-11 rounded-xl bg-white px-3.5 pr-10 text-sm font-semibold text-[#0f3d26] placeholder:text-[#889f92] focus:border-[#1f7a4a] focus:ring-1 focus:ring-[#1f7a4a] outline-none" style="border: 1px solid #d9e6dd; color: #0f3d26;" value="{{ old('telur_kg', $form->telur_kg) }}" placeholder="Otomatis ~62 g/butir">
                                    <span class="absolute right-3.5 top-1/2 -translate-y-1/2 text-xs font-semibold text-[#567263] pointer-events-none">kg</span>
                                </div>
                            </div>

                            {{-- Field 6: Biaya Tambahan Lainnya (opsional) --}}
                            <div>
                                <label class="block text-xs font-bold text-[#0f3d26] mb-1.5" for="biaya_lain">
                                    Biaya Tambahan Lainnya <span class="font-normal text-[#567263]">(opsional)</span>
                                </label>
                                <div class="relative">
                                    <input id="biaya_lain" name="biaya_lain" type="number" min="0" step="500" class="w-full h-11 rounded-xl bg-white px-3.5 pr-10 text-sm font-semibold text-[#0f3d26] focus:border-[#1f7a4a] focus:ring-1 focus:ring-[#1f7a4a] outline-none" style="border: 1px solid #d9e6dd; color: #0f3d26;" value="{{ old('biaya_lain', $form->biaya_lain ?? 0) }}" placeholder="0">
                                    <span class="absolute right-3.5 top-1/2 -translate-y-1/2 text-xs font-semibold text-[#567263] pointer-events-none">Rp</span>
                                </div>
                            </div>

                            {{-- Catatan Gejala / Kondisi (Desktop only) --}}
                            <div class="hidden lg:block">
                                <label class="block text-xs font-bold text-[#0f3d26] mb-1.5" for="catatan">Catatan Kondisi Kandang / Gejala Ayam</label>
                                <textarea id="catatan" name="catatan" rows="2" class="w-full rounded-xl bg-white p-3 text-xs font-medium text-[#0f3d26] focus:border-[#1f7a4a] focus:ring-1 focus:ring-[#1f7a4a] outline-none" style="border: 1px solid #d9e6dd; color: #0f3d26;" placeholder="Contoh: Cuaca panas terik, ayam minum lebih banyak dari biasanya...">{{ old('catatan', $form->catatan) }}</textarea>
                            </div>
                        </div>

                        {{-- Notice Banner (Image 1 style) --}}
                        <div class="p-3 rounded-2xl flex items-center gap-3" style="background-color: #eef6f0; border: none;">
                            <span class="material-symbols-outlined text-[20px] shrink-0" style="color: #1f7a4a;">segment</span>
                            <p class="text-xs text-[#567263] leading-snug">
                                Standar pakan <strong class="font-bold text-[#0f3d26]">110–120 g/ekor/hari</strong>. Kalkulasi otomatis.
                            </p>
                        </div>
                    </div>

                    {{-- CTA Button (Image 1 style) --}}
                    <button type="submit" class="w-full py-3.5 px-4 rounded-2xl text-white font-bold text-sm shadow-xs transition active:scale-[0.99] flex items-center justify-center cursor-pointer mt-2" style="background-color: #1f7a4a; color: #ffffff; border: none;">
                        <span id="btn-submit-text">{{ $isEdit ? 'Perbarui Catatan' : 'Simpan Catatan' }}</span>
                    </button>
                </form>
            </div>

            {{-- KOLOM KANAN (1/2): Live Cockpit & Pratinjau Performa Harian (Tampil di Mobile & Desktop - Sesuai Gambar 2) --}}
            <div class="flex flex-col">
                <div id="live-preview-box" class="bg-white rounded-3xl p-4 sm:p-5 shadow-xs space-y-4 flex-1 flex flex-col justify-between" style="border: none;">
                    <div class="space-y-4">
                        {{-- Header Cockpit (Sesuai Gambar 2) --}}
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <span class="material-symbols-outlined text-[22px]" style="color: #1f7a4a;">trending_up</span>
                                <h3 class="text-xs font-extrabold text-[#0f3d26] uppercase tracking-wider">PRATINJAU PERFORMA HARIAN</h3>
                            </div>
                            <span class="text-xs text-[#567263] font-medium">Populasi: <strong class="text-[#0f3d26] font-bold">{{ number_format($jumlahAyam, 0, ',', '.') }} ekor</strong></span>
                        </div>

                        {{-- 4 Kotak Metrik 2x2 (Sesuai Gambar 2) --}}
                        <div class="grid grid-cols-2 gap-3 text-center">
                            {{-- 1. HEN-DAY (HDP) --}}
                            <div class="p-3.5 rounded-2xl flex flex-col items-center justify-between" style="background-color: #f4f8f5; border: none;">
                                <span class="text-[10px] text-[#567263] uppercase tracking-wider font-bold block mb-1">HEN-DAY (HDP)</span>
                                <strong id="preview-hdp-val" class="text-2xl sm:text-3xl text-[#0f3d26] block font-black mb-1">0.0%</strong>
                                <span id="preview-hdp-badge" class="inline-flex items-center px-3 py-1 rounded-full text-[10px] font-bold" style="background-color: #e8f2eb; color: #476b55;">Menunggu data</span>
                            </div>

                            {{-- 2. PAKAN / EKOR --}}
                            <div class="p-3.5 rounded-2xl flex flex-col items-center justify-between" style="background-color: #f4f8f5; border: none;">
                                <span class="text-[10px] text-[#567263] uppercase tracking-wider font-bold block mb-1">PAKAN / EKOR</span>
                                <strong id="preview-feed-val" class="text-2xl sm:text-3xl text-[#0f3d26] block font-black mb-1">0 <span class="text-xs font-normal text-[#567263]">gr</span></strong>
                                <span id="preview-feed-badge" class="inline-flex items-center px-3 py-1 rounded-full text-[10px] font-bold" style="background-color: #e8f2eb; color: #476b55;">std: 110g</span>
                            </div>

                            {{-- 3. FCR INSTAN --}}
                            <div class="p-3.5 rounded-2xl flex flex-col items-center justify-between" style="background-color: #f4f8f5; border: none;">
                                <span class="text-[10px] text-[#567263] uppercase tracking-wider font-bold block mb-1">FCR INSTAN</span>
                                <strong id="preview-fcr-val" class="text-2xl sm:text-3xl text-[#0f3d26] block font-black mb-1">0.00</strong>
                                <span id="preview-fcr-badge" class="inline-flex items-center px-3 py-1 rounded-full text-[10px] font-bold" style="background-color: #e8f2eb; color: #476b55;">Efisiensi</span>
                            </div>

                            {{-- 4. BIAYA PAKAN EST --}}
                            <div class="p-3.5 rounded-2xl flex flex-col items-center justify-between" style="background-color: #f4f8f5; border: none;">
                                <span class="text-[10px] text-[#567263] uppercase tracking-wider font-bold block mb-1">
                                     BIAYA PAKAN <span class="ml-1 px-1.5 py-0.5 rounded text-[9px] font-bold uppercase" style="background-color: #e4eee7; color: #476b55;">EST</span>
                                </span>
                                <strong id="preview-cost-val" class="text-xl sm:text-2xl text-[#0f3d26] block font-black mb-1">Rp 0</strong>
                                <span id="preview-margin-sub" class="inline-block text-[10px] text-[#567263] font-medium">@ {{ Fmt::rp($harga) }}/kg</span>
                            </div>
                        </div>

                        {{-- Alert Box Dinamis --}}
                        <div id="preview-alert-box" class="hidden p-2.5 rounded-xl text-xs font-semibold flex items-center gap-2">
                            <span id="preview-alert-icon" class="material-symbols-outlined text-[18px]"></span>
                            <span id="preview-alert-text"></span>
                        </div>

                        {{-- Ringkasan Acuan Fase & Standar Bibit (Sesuai Gambar 2) --}}
                        <div class="p-3.5 rounded-2xl flex items-center justify-between text-xs" style="background-color: #f4f8f5; border: none;">
                            <div class="space-y-0.5">
                                <span class="text-[10px] text-[#567263] uppercase tracking-wider font-bold block">FASE & STRAIN AYAM</span>
                                <strong class="font-extrabold text-[#0f3d26] text-xs sm:text-sm block">Mg ke-{{ $farm->umurMinggu() }} · {{ $farm->strain_ayam ?? 'Lohmann Brown' }}</strong>
                            </div>
                            <div class="text-right space-y-0.5">
                                <span class="text-[10px] text-[#567263] uppercase tracking-wider font-bold block text-right">TARGET PRODUKSI (HDP)</span>
                                <strong class="font-extrabold text-xs sm:text-sm block text-right" style="color: #1f7a4a;">85% – 92%</strong>
                            </div>
                        </div>
                    </div>

                    {{-- Footer Acuan Konsumsi (Sesuai Gambar 2) --}}
                    <div class="pt-3 border-t border-[#eef4f0] flex items-center justify-between text-xs text-[#567263]">
                        <span class="flex items-center gap-1.5 font-bold text-[#0f3d26]">
                            <span class="material-symbols-outlined text-[18px]" style="color: #1f7a4a;">tune</span>
                            Standar Pakan:
                        </span>
                        <strong class="text-[#0f3d26] font-bold text-xs">110–120 g/ekor/hari</strong>
                        <span class="font-medium text-[#567263]">Kalkulasi Otomatis</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Riwayat Catatan Harian (Image 2 style) --}}
        <div class="bg-white rounded-3xl p-4 sm:p-5 shadow-xs space-y-3.5" style="border: none;">
            {{-- Card Header --}}
            <div class="flex items-center gap-3 pb-3 border-b border-[#eef4f0]">
                <div class="w-10 h-10 rounded-2xl flex items-center justify-center shrink-0" style="background-color: #dcefe2; color: #1f7a4a;">
                    <span class="material-symbols-outlined text-[22px]">history</span>
                </div>
                <div>
                    <h2 class="text-base font-extrabold text-[#0f3d26] tracking-tight leading-tight">Catatan Harian Terbaru</h2>
                    <p class="text-xs text-[#567263] font-medium">Total {{ $riwayat->total() }} catatan {{ ($filterPeriode !== 'semua' || $cariTanggal) ? '(terfilter)' : '' }}</p>
                </div>
            </div>

            {{-- Filter Pills Track (Image 2 style) --}}
            <div class="flex items-center p-1 rounded-2xl w-full" style="background-color: #e4eee7;">
                <a href="{{ route('catatan.index', ['tab' => 'form', 'periode' => 'semua']) }}"
                   class="flex-1 py-2 text-center text-xs font-bold rounded-xl transition-all {{ ($filterPeriode === 'semua' && !$cariTanggal) ? 'bg-white text-[#0f3d26] shadow-xs' : 'text-[#567263] hover:text-[#0f3d26]' }}">
                    Semua
                </a>
                <a href="{{ route('catatan.index', ['tab' => 'form', 'periode' => 'bulan_ini']) }}"
                   class="flex-1 py-2 text-center text-xs font-bold rounded-xl transition-all {{ $filterPeriode === 'bulan_ini' ? 'bg-white text-[#0f3d26] shadow-xs' : 'text-[#567263] hover:text-[#0f3d26]' }}">
                    Bulan Ini
                </a>
                <a href="{{ route('catatan.index', ['tab' => 'form', 'periode' => 'bulan_lalu']) }}"
                   class="flex-1 py-2 text-center text-xs font-bold rounded-xl transition-all {{ $filterPeriode === 'bulan_lalu' ? 'bg-white text-[#0f3d26] shadow-xs' : 'text-[#567263] hover:text-[#0f3d26]' }}">
                    Bulan Lalu
                </a>
                <a href="{{ route('catatan.index', ['tab' => 'form', 'periode' => '30_hari']) }}"
                   class="flex-1 py-2 text-center text-xs font-bold rounded-xl transition-all {{ $filterPeriode === '30_hari' ? 'bg-white text-[#0f3d26] shadow-xs' : 'text-[#567263] hover:text-[#0f3d26]' }}">
                    30 Hari
                </a>
            </div>

            {{-- Date Picker Filter Input (Image 2 style) --}}
            <form method="GET" action="{{ route('catatan.index') }}" class="relative">
                <input type="hidden" name="tab" value="form">
                <div class="relative flex items-center cursor-pointer">
                    <span class="absolute left-3.5 top-1/2 -translate-y-1/2 material-symbols-outlined text-[18px] pointer-events-none z-20" style="color: #1f7a4a;">calendar_today</span>
                    <input type="date" name="cari_tanggal" id="cari_tanggal" value="{{ $cariTanggal }}" max="{{ today()->toDateString() }}"
                           onchange="this.form.submit()"
                           class="w-full h-11 pl-10 pr-10 rounded-2xl text-xs font-semibold focus:border-[#1f7a4a] focus:ring-1 focus:ring-[#1f7a4a] outline-none cursor-pointer opacity-0 absolute inset-0 z-10"
                           style="border: 1px solid #e4eee7; background-color: #f8fbf9; color: #0f3d26;">
                    <div class="w-full h-11 pl-10 pr-10 rounded-2xl text-xs font-semibold {{ $cariTanggal ? 'text-[#0f3d26]' : 'text-[#567263]' }} flex items-center pointer-events-none"
                         style="border: 1px solid #e4eee7; background-color: #f8fbf9;">
                        {{ $cariTanggal ? \Carbon\Carbon::parse($cariTanggal)->translatedFormat('d M Y') : 'Pilih tanggal' }}
                    </div>
                    @if ($cariTanggal)
                        <a href="{{ route('catatan.index', ['tab' => 'form']) }}" class="absolute right-3.5 top-1/2 -translate-y-1/2 text-[#567263] hover:text-rose-600 z-20" title="Reset tanggal">
                            <span class="material-symbols-outlined text-[16px]">close</span>
                        </a>
                    @endif
                </div>
            </form>

            {{-- MOBILE CARDS VIEW (md:hidden) (Image 2 style) --}}
            <div class="space-y-3 md:hidden pt-1">
                @forelse ($riwayat as $r)
                    @php
                        $itemHdp = $jumlahAyam > 0 ? round(($r->telur_butir / $jumlahAyam) * 100, 1) : 0;
                        $hdpFormatted = str_replace('.', ',', (string) $itemHdp);
                    @endphp
                    <a href="{{ route('catatan.index', ['tanggal' => $r->tanggal->toDateString(), 'tab' => 'form']) }}" class="block rounded-2xl p-3.5 shadow-2xs hover:shadow-xs transition space-y-2" style="background-color: #f8fbf9; border: none;">
                        <div class="flex items-center justify-between">
                            <span class="font-extrabold text-sm text-[#0f3d26]">{{ $r->tanggal->translatedFormat('d M Y') }}</span>
                            @if ($itemHdp >= 85)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold text-white" style="background-color: #1f7a4a; color: #ffffff;">
                                    HDP {{ $hdpFormatted }}%
                                </span>
                            @elseif ($itemHdp >= 75)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold" style="background-color: #dcefe2; color: #0f3d26;">
                                    HDP {{ $hdpFormatted }}%
                                </span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold" style="background-color: #fdf0cf; color: #7a4d05;">
                                    HDP {{ $hdpFormatted }}%
                                </span>
                            @endif
                        </div>
                        <div class="grid grid-cols-2 gap-3 pt-0.5">
                            <div>
                                <span class="text-[11px] text-[#567263] font-medium block">Produksi telur</span>
                                <div class="flex items-baseline gap-1 mt-0.5">
                                    <strong class="font-black text-sm text-[#0f3d26]">{{ Fmt::num($r->telur_butir, 0) }} btr</strong>
                                    <span class="text-xs text-[#567263] font-semibold">{{ str_replace('.', ',', (string) round($r->telurKgEfektif(), 1)) }} kg</span>
                                </div>
                            </div>
                            <div>
                                <span class="text-[11px] text-[#567263] font-medium block">Pakan terpakai</span>
                                <div class="mt-0.5">
                                    <strong class="font-black text-sm text-[#0f3d26]">{{ str_replace('.', ',', (string) round($r->pakan_kg, 1)) }} kg</strong>
                                </div>
                            </div>
                        </div>
                    </a>
                @empty
                    <div class="py-8 text-center text-[#567263]">
                        <div class="flex flex-col items-center justify-center gap-1.5">
                            <span class="material-symbols-outlined text-[26px] text-[#567263]/40">search_off</span>
                            <p class="text-xs font-semibold text-[#0f3d26]">Tidak ada catatan pada filter yang dipilih</p>
                            <p class="text-[11px] text-[#567263]">Coba ubah filter periode atau tanggal pencarian Anda.</p>
                        </div>
                    </div>
                @endforelse
            </div>

            {{-- DESKTOP TABLE VIEW (hidden md:block) --}}
            <div class="hidden md:block overflow-x-auto -mx-4 sm:-mx-5 px-4 sm:px-5">
                <table class="w-full text-xs text-left border-collapse">
                    <thead>
                        <tr class="border-b border-line text-[11px] font-bold text-muted uppercase tracking-wider">
                            <th class="py-2.5 pr-3">Tanggal</th>
                            <th class="py-2.5 px-3">Produksi Telur</th>
                            <th class="py-2.5 px-3">Pakan Terpakai</th>
                            <th class="py-2.5 px-3 text-center">HDP</th>
                            <th class="py-2.5 px-3 text-center">Pakan/Ekor</th>
                            <th class="py-2.5 px-3 text-center">Mati</th>
                            <th class="py-2.5 px-3">Catatan</th>
                            <th class="py-2.5 pl-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line/60">
                        @forelse ($riwayat as $r)
                            @php
                                $itemHdp = $jumlahAyam > 0 ? round(($r->telur_butir / $jumlahAyam) * 100, 1) : 0;
                                $itemGr = $jumlahAyam > 0 ? round(($r->pakan_kg * 1000) / $jumlahAyam, 1) : 0;
                            @endphp
                            <tr class="hover:bg-surface/50 transition-colors group">
                                <td class="py-3 pr-3 font-bold text-ink whitespace-nowrap">
                                    {{ $r->tanggal->translatedFormat('d M Y') }}
                                </td>
                                <td class="py-3 px-3 whitespace-nowrap">
                                    <span class="font-bold text-ink">{{ Fmt::num($r->telur_butir, 0) }} btr</span>
                                    <span class="text-muted text-[11px] ml-1">({{ Fmt::num($r->telurKgEfektif(), 1) }} kg)</span>
                                </td>
                                <td class="py-3 px-3 whitespace-nowrap font-medium text-ink">
                                    {{ Fmt::num($r->pakan_kg, 1) }} kg
                                </td>
                                <td class="py-3 px-3 text-center whitespace-nowrap">
                                    <span class="font-bold {{ $itemHdp >= 80 ? 'text-emerald-700' : ($itemHdp >= 65 ? 'text-teal-700' : 'text-amber-700') }}">
                                        {{ $itemHdp }}%
                                    </span>
                                </td>
                                <td class="py-3 px-3 text-center whitespace-nowrap text-muted font-medium">
                                    {{ $itemGr }} g
                                </td>
                                <td class="py-3 px-3 text-center whitespace-nowrap">
                                    @if ($r->ayam_mati > 0)
                                        <span class="font-bold text-rose-600">
                                            {{ $r->ayam_mati }} ekor
                                        </span>
                                    @else
                                        <span class="text-muted/40 font-mono">-</span>
                                    @endif
                                </td>
                                <td class="py-3 px-3 max-w-[180px] truncate text-muted text-[11px] italic" title="{{ $r->catatan }}">
                                    {{ $r->catatan ? Str::limit($r->catatan, 30) : '-' }}
                                </td>
                                <td class="py-3 pl-3 text-right whitespace-nowrap">
                                    <div class="inline-flex items-center gap-1.5">
                                        <a href="{{ route('catatan.index', ['tanggal' => $r->tanggal->toDateString(), 'tab' => 'form']) }}" class="px-2.5 py-1 rounded-md text-xs font-bold text-[#1f7a4a] hover:text-[#18643c] hover:bg-surface border border-[#d9e6dd] transition cursor-pointer" title="Edit catatan">
                                            Edit
                                        </a>
                                        <form method="POST" action="{{ route('catatan.destroy', $r) }}" data-confirm="Hapus catatan produksi tanggal {{ $r->tanggal->translatedFormat('d M Y') }}?" data-confirm-title="Hapus Catatan Harian" data-confirm-btn="Ya, Hapus Catatan">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="p-1 rounded-md text-muted hover:text-rose-600 hover:bg-rose-50 transition cursor-pointer" title="Hapus catatan">
                                                <span class="material-symbols-outlined text-[16px]">delete</span>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="py-8 text-center text-muted">
                                    <div class="flex flex-col items-center justify-center gap-1.5">
                                        <span class="material-symbols-outlined text-[26px] text-muted/40">search_off</span>
                                        <p class="text-xs font-semibold text-ink">Tidak ada catatan pada filter yang dipilih</p>
                                        <p class="text-[11px] text-muted">Coba ubah filter periode atau tanggal pencarian Anda.</p>
                                        @if ($cariTanggal || $filterPeriode !== 'semua')
                                            <a href="{{ route('catatan.index', ['tab' => 'form']) }}" class="mt-1 text-xs font-bold text-[#1f7a4a] hover:underline inline-flex items-center gap-1">
                                                <span class="material-symbols-outlined text-[14px]">refresh</span>
                                                Reset ke Semua Catatan
                                            </a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Navigasi Pagination (Sesuai Gambar Referensi) --}}
            @if ($riwayat->hasPages())
                @php
                    $totalPages = $riwayat->lastPage();
                    $currPage = $riwayat->currentPage();
                    if ($totalPages <= 3) {
                        $startPage = 1;
                        $endPage = $totalPages;
                    } elseif ($currPage <= 2) {
                        $startPage = 1;
                        $endPage = min(3, $totalPages);
                    } elseif ($currPage >= $totalPages - 1) {
                        $startPage = max(1, $totalPages - 2);
                        $endPage = $totalPages;
                    } else {
                        $startPage = $currPage - 1;
                        $endPage = $currPage + 1;
                    }
                @endphp
                <div class="pt-4 pb-2 flex flex-col items-center justify-center gap-2.5">
                    <div class="flex items-center justify-center gap-2.5 sm:gap-3">
                        {{-- Tombol Sebelumnya (<) --}}
                        @if ($riwayat->onFirstPage())
                            <span class="w-11 h-11 rounded-2xl bg-white opacity-35 flex items-center justify-center cursor-not-allowed shadow-2xs" style="border: 1px solid #d9e6dd; color: #0f3d26;">
                                <span class="material-symbols-outlined text-[20px]">chevron_left</span>
                            </span>
                        @else
                            <a href="{{ $riwayat->previousPageUrl() }}" class="w-11 h-11 rounded-2xl bg-white flex items-center justify-center hover:bg-[#f4f8f5] transition shadow-2xs cursor-pointer" style="border: 1px solid #d9e6dd; color: #0f3d26;">
                                <span class="material-symbols-outlined text-[20px]">chevron_left</span>
                            </a>
                        @endif

                        {{-- Angka Halaman --}}
                        @foreach ($riwayat->getUrlRange($startPage, $endPage) as $page => $url)
                            @if ($page == $currPage)
                                <span class="w-11 h-11 rounded-2xl font-bold text-sm flex items-center justify-center shadow-xs" style="background-color: #1f7a4a; color: #ffffff; border: none;">{{ $page }}</span>
                            @else
                                <a href="{{ $url }}" class="w-11 h-11 rounded-2xl bg-white font-bold text-sm flex items-center justify-center hover:bg-[#f4f8f5] transition shadow-2xs cursor-pointer" style="border: 1px solid #d9e6dd; color: #0f3d26;">{{ $page }}</a>
                            @endif
                        @endforeach

                        {{-- Tombol Selanjutnya (>) --}}
                        @if ($riwayat->hasMorePages())
                            <a href="{{ $riwayat->nextPageUrl() }}" class="w-11 h-11 rounded-2xl bg-white flex items-center justify-center hover:bg-[#f4f8f5] transition shadow-2xs cursor-pointer" style="border: 1px solid #d9e6dd; color: #0f3d26;">
                                <span class="material-symbols-outlined text-[20px]">chevron_right</span>
                            </a>
                        @else
                            <span class="w-11 h-11 rounded-2xl bg-white opacity-35 flex items-center justify-center cursor-not-allowed shadow-2xs" style="border: 1px solid #d9e6dd; color: #0f3d26;">
                                <span class="material-symbols-outlined text-[20px]">chevron_right</span>
                            </span>
                        @endif
                    </div>

                    {{-- Keterangan Halaman & Jumlah Catatan --}}
                    <p class="text-center text-xs text-[#567263] font-medium leading-relaxed">
                        Halaman <strong class="text-[#0f3d26] font-bold">{{ $currPage }}</strong> dari <strong class="text-[#0f3d26] font-bold">{{ $totalPages }}</strong> · Menampilkan <strong class="text-[#0f3d26] font-bold">{{ $riwayat->firstItem() }}–{{ $riwayat->lastItem() }}</strong> dari <strong class="text-[#0f3d26] font-bold">{{ $riwayat->total() }}</strong> catatan
                    </p>
                </div>
            @endif
        </div>
    </section>

    {{-- Panel 2: Dashboard & Laporan --}}
    <section data-panel="dashboard" class="{{ $tabAktif === 'dashboard' ? '' : 'hidden' }} space-y-4">
        {{-- Filter Rentang & Aksi Ekspor --}}
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-2">
                <span class="text-xs font-semibold text-muted">Rentang data:</span>
                <div class="inline-flex p-1 bg-surface rounded-full border border-line text-xs font-bold select-none shadow-2xs">
                    <a href="{{ route('catatan.index', ['tab' => 'dashboard', 'rentang' => 'minggu']) }}" class="px-3.5 py-1 rounded-full transition-all {{ $rentang === 7 ? 'bg-white text-ink shadow-2xs font-extrabold' : 'text-muted hover:text-ink font-semibold' }}">7 Hari</a>
                    <a href="{{ route('catatan.index', ['tab' => 'dashboard', 'rentang' => 'bulan']) }}" class="px-3.5 py-1 rounded-full transition-all {{ $rentang === 30 ? 'bg-white text-ink shadow-2xs font-extrabold' : 'text-muted hover:text-ink font-semibold' }}">30 Hari</a>
                </div>
            </div>
            <div class="flex items-center gap-2 flex-wrap">
                <button type="button" id="btn-buka-laporan-modal" class="inline-flex items-center gap-1.5 px-4 py-1.5 rounded-full text-xs font-bold text-white bg-brand hover:bg-brand-dark shadow-2xs hover:shadow-xs transition active:scale-95 cursor-pointer" title="Pratinjau & Unduh Dokumen Laporan (PDF)">
                    <span class="material-symbols-outlined text-[17px]">download</span>
                    <span>Unduh Laporan</span>
                </button>
                <button type="button" id="btn-copy-wa" class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full border border-line bg-white hover:bg-surface text-ink hover:text-brand text-xs font-bold shadow-2xs hover:shadow-xs transition active:scale-95 cursor-pointer" title="Salin Ringkasan Laporan ke Clipboard">
                    <span class="material-symbols-outlined text-[16px] text-emerald-600">content_copy</span>
                    <span>Salin Laporan</span>
                </button>
            </div>
        </div>

        {{-- Laporan Mingguan Ringkas --}}
        <div class="card space-y-3.5">
            <div class="flex flex-wrap items-center justify-between gap-2 pb-2.5 border-b border-line">
                <div class="flex items-center gap-2 flex-wrap">
                    <h2 class="text-xs font-bold text-ink uppercase tracking-wider">Laporan Ringkas {{ $rentang }} Hari</h2>
                    @php
                        $faseDetail = $farm->faseUmurDetail();
                    @endphp
                    <span class="text-muted/40">•</span>
                    <span class="text-xs font-semibold text-muted">Minggu ke-{{ $farm->umurMinggu() }} ({{ $faseDetail['nama'] }})</span>
                    <span class="text-muted/40 hidden md:inline">•</span>
                    <span class="hidden md:inline text-xs text-muted">Target: <strong class="text-ink font-semibold">HDP {{ $faseDetail['standar_hdp'] }}</strong></span>
                </div>
                <span class="text-tiny font-medium text-muted">vs {{ $rentang }} hari sebelumnya</span>
            </div>

            {{-- 5 Kartu KPI Bersih Tanpa Ikon Berlebih --}}
            <div class="grid grid-cols-2 sm:grid-cols-5 gap-2.5 text-xs">
                {{-- KPI 1: Total Telur --}}
                @php
                    $telurDiff = $laporan['lalu']['telur'] ? round(($laporan['ini']['telur'] - $laporan['lalu']['telur']) / $laporan['lalu']['telur'] * 100) : null;
                @endphp
                <div class="p-3 rounded-xl bg-surface/50 border border-line flex flex-col justify-between">
                    <span class="text-[10px] text-muted uppercase tracking-wider font-semibold block mb-1">Total Telur</span>
                    <strong class="text-base sm:text-lg text-ink font-black block">
                        {{ Fmt::num($laporan['ini']['telur'], 0) }} <span class="text-xs font-normal text-muted">btr</span>
                    </strong>
                    <div class="text-[11px] mt-1 font-semibold">
                        @if ($telurDiff !== null)
                            <span class="{{ $telurDiff >= 0 ? 'text-emerald-700' : 'text-rose-600' }}">
                                {{ $telurDiff >= 0 ? '+' : '' }}{{ $telurDiff }}%
                            </span>
                            <span class="text-muted font-normal">vs lalu</span>
                        @else
                            <span class="text-muted font-normal">-</span>
                        @endif
                    </div>
                </div>

                {{-- KPI 2: Konsumsi Pakan --}}
                <div class="p-3 rounded-xl bg-surface/50 border border-line flex flex-col justify-between">
                    <span class="text-[10px] text-muted uppercase tracking-wider font-semibold block mb-1">Konsumsi Pakan</span>
                    <strong class="text-base sm:text-lg text-ink font-black block">
                        {{ Fmt::num($laporan['ini']['pakan'], 1) }} <span class="text-xs font-normal text-muted">kg</span>
                    </strong>
                    <span class="text-[11px] mt-1 text-muted font-normal">
                        {{ $laporan['ini']['hari'] ? round($laporan['ini']['pakan'] / $laporan['ini']['hari'], 1) : 0 }} kg/hari
                    </span>
                </div>

                {{-- KPI 3: Biaya Pakan --}}
                <div class="p-3 rounded-xl bg-surface/50 border border-line flex flex-col justify-between">
                    <span class="text-[10px] text-muted uppercase tracking-wider font-semibold block mb-1">Biaya Pakan <span class="tag-est">est</span></span>
                    <strong class="text-base sm:text-lg text-ink font-black block">
                        {{ Fmt::rp($laporan['ini']['biaya_pakan']) }}
                    </strong>
                    <span class="text-[11px] mt-1 text-muted font-normal">
                        @ {{ Fmt::rp($harga) }}/kg
                    </span>
                </div>

                {{-- KPI 4: Biaya / Kg Telur --}}
                <div class="p-3 rounded-xl bg-surface/50 border border-line flex flex-col justify-between">
                    <span class="text-[10px] text-muted uppercase tracking-wider font-semibold block mb-1">Biaya / Kg Telur <span class="tag-est">est</span></span>
                    <strong class="text-base sm:text-lg text-ink font-black block">
                        {{ Fmt::rp($laporan['ini']['biaya_kg_telur']) }}
                    </strong>
                    <span class="text-[11px] mt-1 text-muted font-normal">
                        Rasio {{ Fmt::num($laporan['ini']['rasio'], 0) }} g/btr
                    </span>
                </div>

                {{-- KPI 5: Est. Margin Kotor --}}
                @php $marginKotor = $laporan['ini']['margin_kotor'] ?? 0; @endphp
                <div class="p-3 rounded-xl bg-surface/50 border border-line flex flex-col justify-between col-span-2 sm:col-span-1">
                    <span class="text-[10px] text-muted uppercase tracking-wider font-semibold block mb-1">Est. Margin Kotor</span>
                    <strong class="text-base sm:text-lg font-black block {{ $marginKotor >= 0 ? 'text-emerald-700' : 'text-rose-600' }}">
                        {{ $marginKotor >= 0 ? '+' : '' }}{{ Fmt::rp($marginKotor) }}
                    </strong>
                    <span class="text-[11px] mt-1 text-muted font-normal truncate" title="Omzet: {{ Fmt::rp($laporan['ini']['pendapatan_telur'] ?? 0) }}">
                        Omzet: {{ Fmt::rp($laporan['ini']['pendapatan_telur'] ?? 0) }}
                    </span>
                </div>
            </div>
        </div>

        {{-- 4 Grafik Chart.js Bersih dengan Info Rata-rata Minimalis (Opsi B) --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4" data-buku-grafik-container>
            {{-- Grafik 1: Pakan --}}
            <div class="card space-y-2 buku-card-fadein">
                <div class="flex items-center justify-between pb-2 border-b border-line">
                    <h3 class="text-xs font-bold text-ink uppercase tracking-wider">Konsumsi Pakan (kg)</h3>
                    <span class="text-[11px] text-muted font-normal">
                        Rata-rata: <strong class="text-ink font-semibold">{{ $laporan['ini']['hari'] ? round($laporan['ini']['pakan'] / $laporan['ini']['hari'], 1) : 0 }} kg/hari</strong>
                    </span>
                </div>
                <div class="h-48 pt-2">
                    <canvas data-buku-chart='@json($grafik['pakan'])' data-chart-name="pakan" aria-label="Grafik pakan"></canvas>
                </div>
            </div>

            {{-- Grafik 2: Telur --}}
            <div class="card space-y-2 buku-card-fadein">
                <div class="flex items-center justify-between pb-2 border-b border-line">
                    <h3 class="text-xs font-bold text-ink uppercase tracking-wider">Produksi Telur (butir)</h3>
                    <span class="text-[11px] text-muted font-normal">
                        Rata-rata: <strong class="text-ink font-semibold">{{ round($laporan['ini']['rata_telur'], 0) }} btr/hari</strong>
                    </span>
                </div>
                <div class="h-48 pt-2">
                    <canvas data-buku-chart='@json($grafik['telur'])' data-chart-name="telur" aria-label="Grafik telur"></canvas>
                </div>
            </div>

            {{-- Grafik 3: Biaya Pakan Harian --}}
            <div class="card space-y-2 buku-card-fadein">
                <div class="flex items-center justify-between pb-2 border-b border-line">
                    <h3 class="text-xs font-bold text-ink uppercase tracking-wider">Biaya Pakan Harian <span class="tag-est font-normal lowercase">est</span></h3>
                    <span class="text-[11px] text-muted font-normal">
                        Rata-rata: <strong class="text-ink font-semibold">{{ Fmt::rp($laporan['ini']['hari'] ? round($laporan['ini']['biaya_pakan'] / $laporan['ini']['hari']) : 0) }}/hari</strong>
                    </span>
                </div>
                <div class="h-48 pt-2">
                    <canvas data-buku-chart='@json($grafik['biaya'])' data-chart-name="biaya" aria-label="Grafik biaya pakan"></canvas>
                </div>
            </div>

            {{-- Grafik 4: Efisiensi --}}
            <div class="card space-y-2 buku-card-fadein">
                <div class="flex items-center justify-between pb-2 border-b border-line">
                    <h3 class="text-xs font-bold text-ink uppercase tracking-wider">Efisiensi: Biaya / Kg Telur <span class="tag-est font-normal lowercase">est</span></h3>
                    <span class="text-[11px] text-muted font-normal">
                        Rata-rata: <strong class="text-ink font-semibold">{{ Fmt::rp($laporan['ini']['biaya_kg_telur']) }}/kg</strong>
                    </span>
                </div>
                <div class="h-48 pt-2">
                    <canvas data-buku-chart='@json($grafik['efisiensi'])' data-chart-name="efisiensi" aria-label="Grafik efisiensi"></canvas>
                </div>
            </div>
        </div>
    </section>

    {{-- Panel 3: Pembelian & Selisih Estimasi vs Kenyataan --}}
    @php
        $totalBelanjaRiil = (int) $beli->sum(fn ($p) => $p->harga_nyata * $p->kg);
        $totalKgBeli = (float) $beli->sum('kg');
        $jumlahTransaksiBeli = count($beli);
        $bahanPrices = $bahan->mapWithKeys(function ($b) use ($farm) {
            $est = (int) ($farm->farmIngredients()->where('ingredient_id', $b->id)->value('harga') ?? $b->harga_default);
            return [$b->id => $est];
        });
    @endphp
    <section data-panel="beli" class="{{ $tabAktif === 'beli' ? '' : 'hidden' }} space-y-5">
        {{-- 1. Baris Ringkasan KPI Eksekutif --}}
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3.5">
            {{-- Kartu 1: Selisih Biaya (Deviasi Ransum) --}}
            <div class="card p-4 flex flex-col justify-between space-y-2.5 shadow-2xs hover:shadow-xs transition">
                <div class="flex items-center justify-between gap-2">
                    <span class="text-xs font-bold text-muted uppercase tracking-wider">Deviasi Biaya Ransum</span>
                    @if ($selisihTotal > 0)
                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-rose-50 text-rose-700 border border-rose-200">
                            Biaya Riil Lebih Tinggi
                        </span>
                    @elseif ($selisihTotal < 0)
                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-emerald-50 text-emerald-700 border border-emerald-200">
                            Biaya Riil Lebih Hemat
                        </span>
                    @else
                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-600 border border-slate-200">
                            Sesuai Estimasi
                        </span>
                    @endif
                </div>
                <div>
                    <div class="text-2xl font-black {{ $selisihTotal > 0 ? 'text-rose-700' : ($selisihTotal < 0 ? 'text-emerald-700' : 'text-ink') }}">
                        {{ $selisihTotal > 0 ? '+' : '' }}{{ Fmt::rp($selisihTotal) }}
                    </div>
                    <p class="text-[11px] text-muted mt-0.5">Akumulasi selisih harga toko vs acuan formulasi</p>
                </div>
            </div>

            {{-- Kartu 2: Total Belanja Riil --}}
            <div class="card p-4 flex flex-col justify-between space-y-2.5 shadow-2xs hover:shadow-xs transition">
                <div class="flex items-center justify-between gap-2">
                    <span class="text-xs font-bold text-muted uppercase tracking-wider">Total Pengeluaran</span>
                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-700 border border-slate-200">
                        {{ $jumlahTransaksiBeli }} Transaksi
                    </span>
                </div>
                <div>
                    <div class="text-2xl font-black text-ink">
                        {{ Fmt::rp($totalBelanjaRiil) }}
                    </div>
                    <p class="text-[11px] text-muted mt-0.5">Total nominal rupiah pembelian bahan pakan</p>
                </div>
            </div>

            {{-- Kartu 3: Total Volume Bahan Masuk --}}
            <div class="card p-4 flex flex-col justify-between space-y-2.5 shadow-2xs hover:shadow-xs transition">
                <div class="flex items-center justify-between gap-2">
                    <span class="text-xs font-bold text-muted uppercase tracking-wider">Total Volume Pakan</span>
                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                        Log Gudang
                    </span>
                </div>
                <div>
                    <div class="text-2xl font-black text-ink">
                        {{ Fmt::num($totalKgBeli, 1) }} <span class="text-sm font-semibold text-muted">kg</span>
                    </div>
                    <p class="text-[11px] text-muted mt-0.5">Total bahan pakan masuk ke peternakan</p>
                </div>
            </div>
        </div>

        {{-- 2. Layout 2 Kolom Seimbang (Formulir di Kiri & Riwayat di Kanan) --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-5 items-stretch">
            {{-- KOLOM KIRI (1/2): Formulir Input Pembelian Bahan --}}
            <div class="flex flex-col">
                <form method="POST" action="{{ route('pembelian.store') }}" class="card space-y-4 flex-1 flex flex-col justify-between">
                    @csrf
                    <div class="space-y-4">
                        {{-- Header Form Bersih & Humanis (Title Case) --}}
                        <div class="flex items-center justify-between pb-2.5 border-b border-line">
                            <div class="flex items-center gap-2">
                                <span class="material-symbols-outlined text-[20px] text-brand">shopping_cart</span>
                                <h2 class="text-xs font-bold text-ink">Catat Pembelian Bahan</h2>
                            </div>
                            <span class="text-tiny font-medium text-muted">Kandang: <strong class="text-ink">{{ $farm->nama }}</strong></span>
                        </div>

                        {{-- Grid Input: Bahan Pakan & Tanggal Beli --}}
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                            <div>
                                <label class="lbl text-xs font-bold text-ink">Pilih Bahan Pakan</label>
                                @include('partials.dropdown-pilih-bahan', ['bahan' => $bahan])
                            </div>
                            <div>
                                <label class="lbl text-xs font-bold text-ink" for="beli_tanggal">Tanggal Pembelian</label>
                                <input id="beli_tanggal" name="tanggal" type="date" value="{{ today()->toDateString() }}" max="{{ today()->toDateString() }}" class="field font-semibold" required>
                            </div>
                        </div>

                        {{-- Grid Input: Jumlah Beli (kg) & Harga Nyata Toko (Rp/kg) dengan Unit Visual --}}
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                            <div>
                                <label class="lbl text-xs font-bold text-ink" for="kg">Jumlah Beli</label>
                                <div class="relative">
                                    <input id="kg" name="kg" type="number" step="0.5" min="0.5" class="field pr-10 text-base font-bold text-ink" placeholder="50" required>
                                    <span class="absolute right-3.5 top-1/2 -translate-y-1/2 text-xs font-bold text-muted pointer-events-none select-none">kg</span>
                                </div>
                            </div>
                            <div>
                                <label class="lbl text-xs font-bold text-ink" for="harga_nyata">Harga Nyata Toko</label>
                                <div class="relative">
                                    <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-xs font-bold text-muted pointer-events-none select-none">Rp</span>
                                    <input id="harga_nyata" name="harga_nyata" type="number" step="50" min="0" class="field pl-9 pr-12 text-base font-bold text-ink" placeholder="6700" required>
                                    <span class="absolute right-3.5 top-1/2 -translate-y-1/2 text-[11px] font-semibold text-muted pointer-events-none select-none">/kg</span>
                                </div>
                            </div>
                        </div>

                        {{-- Live Estimasi Box (Kalkulator Otomatis) --}}
                        <div id="beli-live-calc-box" class="p-3 rounded-xl bg-surface/70 border border-line/80 space-y-1.5 transition">
                            <div class="flex items-center justify-between text-xs">
                                <span class="text-muted font-medium">Estimasi Subtotal Belanja:</span>
                                <strong id="beli-subtotal-val" class="font-black text-ink text-sm">Rp 0</strong>
                            </div>
                            <div class="flex items-center justify-between text-xs pt-1 border-t border-line/50">
                                <span class="text-muted font-medium">Status Selisih vs Formulasi:</span>
                                <span id="beli-deviasi-badge" class="px-2 py-0.5 rounded-full text-[10.5px] font-bold bg-slate-100 text-muted">
                                    Menunggu input kg & harga
                                </span>
                            </div>
                        </div>

                    </div>

                    {{-- Input Tersembunyi: Sinkronisasi Stok Otomatis --}}
                    <input type="hidden" name="tambah_stok" value="1">

                    {{-- Tombol Submit Berbentuk Pill Badge Elegan --}}
                    <div class="pt-2 space-y-2">
                        <button type="submit" class="w-full inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-full bg-brand hover:bg-brand-dark text-white text-xs font-bold shadow-md hover:shadow-brand/20 transition active:scale-95 cursor-pointer">
                            <span class="material-symbols-outlined text-[18px]">receipt_long</span>
                            <span>Catat Belanja Bahan</span>
                        </button>
                        <p class="text-[11px] text-muted text-center flex items-center justify-center gap-1.5">
                            <span class="material-symbols-outlined text-[15px] text-brand">sync</span>
                            <span>Otomatis menambah stok gudang & memperbarui harga bahan</span>
                        </p>
                    </div>
                </form>
            </div>

            {{-- KOLOM KANAN (1/2): Riwayat Pembelian Bahan --}}
            <div class="flex flex-col">
                <div class="card space-y-3.5 flex-1 flex flex-col justify-between">
                    <div>
                        {{-- Header Riwayat Bersih (Title Case) --}}
                        <div class="flex items-center justify-between pb-2.5 border-b border-line">
                            <div class="flex items-center gap-2">
                                <span class="material-symbols-outlined text-[20px] text-brand">history</span>
                                <h2 class="text-xs font-bold text-ink">Riwayat Belanja Terkini</h2>
                            </div>
                            <span class="text-xs font-bold px-2.5 py-0.5 rounded-full bg-surface border border-line text-muted">
                                {{ $jumlahTransaksiBeli }} Tercatat
                            </span>
                        </div>

                        @if ($beli->isNotEmpty())
                            {{-- Daftar Transaksi Belanja --}}
                            <div class="divide-y divide-line text-sm space-y-1">
                                @foreach ($beli as $p)
                                    @php
                                        $sel = ($p->harga_nyata - $p->harga_estimasi) * $p->kg;
                                        $subtotalP = $p->harga_nyata * $p->kg;
                                    @endphp
                                    <div class="py-3 flex items-center justify-between gap-3 hover:bg-surface/40 px-2.5 -mx-1 rounded-xl transition-colors">
                                        <div class="min-w-0">
                                            <div class="flex items-center gap-2 flex-wrap">
                                                <span class="font-bold text-ink text-sm">{{ $p->ingredient->nama }}</span>
                                                <span class="text-muted/40">•</span>
                                                <span class="text-xs text-muted font-medium">{{ $p->tanggal->translatedFormat('d M Y') }}</span>
                                            </div>
                                            <p class="text-xs text-muted mt-1">
                                                <span class="font-bold text-ink">{{ Fmt::num($p->kg, 1) }} kg</span> · 
                                                <span>{{ Fmt::rp($p->harga_nyata) }}/kg</span>
                                                <span class="text-muted/70">(Acuan: {{ Fmt::rp($p->harga_estimasi) }})</span>
                                            </p>
                                        </div>
                                        <div class="text-right shrink-0">
                                            <span class="font-black text-sm block text-ink">
                                                {{ Fmt::rp($subtotalP) }}
                                            </span>
                                            <span class="inline-block mt-0.5 px-2 py-0.5 rounded-full text-[10px] font-extrabold {{ $sel > 0 ? 'bg-rose-50 text-rose-700 border border-rose-200' : 'bg-emerald-50 text-emerald-700 border border-emerald-200' }}">
                                                {{ $sel > 0 ? '+' : '' }}{{ Fmt::rp($sel) }} ({{ $sel > 0 ? 'Lebih Tinggi' : 'Lebih Hemat' }})
                                            </span>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    @if ($beli->isEmpty())
                        {{-- Empty State yang Menawan & Rata Tengah Sempurna --}}
                        <div class="flex-1 flex flex-col items-center justify-center py-12 px-4 text-center my-auto">
                            <div class="w-14 h-14 rounded-2xl bg-surface border border-line/80 flex items-center justify-center text-muted mb-3 shadow-2xs">
                                <span class="material-symbols-outlined text-[28px] text-muted/80">shopping_bag</span>
                            </div>
                            <h3 class="text-sm font-bold text-ink">Belum Ada Transaksi Belanja</h3>
                            <p class="text-xs text-muted max-w-xs mt-1 leading-relaxed">
                                Catat pembelian jagung, konsentrat, atau dedak untuk memantau deviasi biaya formulasi ransum secara nyata.
                            </p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </section>
</div>

{{-- Modal Pratinjau & Dokumen Cetak Laporan Resmi A4/PDF --}}
<div id="modal-laporan-resmi" class="hidden fixed inset-0 z-50 overflow-y-auto bg-slate-950/80 backdrop-blur-xs p-3 sm:p-6 flex flex-col items-center">
    {{-- Modal Toolbar (Hanya Tampil di Layar Monitor, Tersembunyi Otomatis Saat Cetak) --}}
    <div class="modal-toolbar max-w-4xl w-full bg-slate-900/95 backdrop-blur-md text-white rounded-2xl p-3 sm:px-5 sm:py-3.5 mb-4 shadow-2xl flex flex-wrap items-center justify-between gap-3 border border-slate-700/80 sticky top-3 z-30">
        <div class="flex items-center gap-3">
            <div class="w-9 h-9 rounded-full bg-brand flex items-center justify-center text-white shadow-xs">
                <span class="material-symbols-outlined text-[19px]">picture_as_pdf</span>
            </div>
            <div>
                <h3 class="font-bold text-sm leading-tight text-white flex items-center gap-2">
                    <span>Pratinjau Dokumen Resmi (Format A4)</span>
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">Siap Cetak / PDF</span>
                </h3>
                <p class="text-[11px] text-slate-400">Standar cetak A4 Portrait · Rekapitulasi {{ $rentang }} Hari · {{ $farm->nama }}</p>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <button type="button" id="btn-cetak-dokumen" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-full bg-brand hover:bg-brand-dark text-white text-xs font-bold shadow-md hover:shadow-brand/20 transition active:scale-95 cursor-pointer">
                <span class="material-symbols-outlined text-[17px]">print</span>
                <span>Cetak / Simpan PDF</span>
            </button>
            <button type="button" id="btn-tutup-laporan-modal" class="inline-flex items-center gap-1 px-3.5 py-2 rounded-full bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white text-xs font-semibold transition active:scale-95 cursor-pointer" title="Tutup pratinjau (Esc)">
                <span class="material-symbols-outlined text-[17px]">close</span>
                <span>Tutup</span>
            </button>
        </div>
    </div>

    {{-- Dokumen Laporan Kertas A4 Formal --}}
    <div id="dokumen-laporan-resmi" class="dokumen-kertas-a4 max-w-4xl w-full rounded-2xl p-6 sm:p-9 text-slate-900 space-y-4 bg-white shadow-2xl border border-slate-200">
        {{-- 1. KOP RESMI LAPORAN --}}
        <div class="border-b-2 border-brand pb-4">
            <div class="flex items-start justify-between gap-4">
                <div class="flex items-center gap-3.5">
                    <div class="w-12 h-12 rounded-xl bg-brand text-white flex items-center justify-center font-black text-2xl shadow-xs shrink-0 border border-brand-dark p-2">
                        <img src="{{ asset('images/logo.png') }}" alt="Logo RansumKu" class="w-full h-full object-contain rounded-lg" onerror="this.outerHTML='<span class=\'material-symbols-outlined text-[26px]\'>egg</span>'">
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h1 class="text-xl sm:text-2xl font-black tracking-tight text-slate-950">RansumKu</h1>
                            <span class="text-xs font-bold text-slate-400">· Smart Poultry Farm</span>
                            <span class="hidden sm:inline-block px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-700 border border-slate-300 uppercase tracking-wider">Dokumen Resmi</span>
                        </div>
                        <p class="text-xs sm:text-sm font-bold text-brand uppercase tracking-wider mt-0.5">Laporan Rekapitulasi Operasional Buku Kandang Layer</p>
                        <p class="text-[11px] text-slate-500">Pencatatan Harian Produksi Telur, Konsumsi Pakan, Mortalitas & Analisis Efisiensi</p>
                    </div>
                </div>
                <div class="text-right text-[11px] text-slate-600 shrink-0">
                    <p class="font-bold text-slate-900 font-mono tracking-wide">REF: BK-{{ $farm->id }}-{{ now()->format('Ymd') }}</p>
                    <p class="text-slate-500 mt-0.5">Dicetak: {{ now()->translatedFormat('d M Y, H:i') }} WIB</p>
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[9.5px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200 uppercase mt-1 font-mono">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                        Terverifikasi Sistem
                    </span>
                </div>
            </div>
        </div>

        {{-- Kalkulasi Agregat Laporan --}}
        @php
            $totalPakan = (float) $logs->sum('pakan_kg');
            $totalTelurButir = (int) $logs->sum('telur_butir');
            $totalTelurKg = (float) $logs->sum(fn ($l) => $l->telurKgEfektif());
            $totalMati = (int) $logs->sum('ayam_mati');
            $totalBiayaPakan = $totalPakan * $harga;
            $totalBiayaLain = (int) $logs->sum('biaya_lain');
            $totalBiayaSemua = $totalBiayaPakan + $totalBiayaLain;
            $hariCount = max(1, $logs->count());
            $avgHdp = ($jumlahAyam > 0 && $hariCount > 0) ? ($totalTelurButir / ($jumlahAyam * $hariCount)) * 100 : 0;
            $avgFeedPerBird = ($jumlahAyam > 0 && $hariCount > 0) ? ($totalPakan * 1000) / ($jumlahAyam * $hariCount) : 0;
            $avgFcr = $totalTelurKg > 0 ? $totalPakan / $totalTelurKg : 0;
            $avgCostPerKgTelur = $totalTelurKg > 0 ? $totalBiayaPakan / $totalTelurKg : 0;
            $persenKematian = $jumlahAyam > 0 ? ($totalMati / $jumlahAyam) * 100 : 0;
            $faseModal = $farm->faseUmurDetail();
            $hargaTelurModal = (int) config('ransumku.harga_telur_default', 26000);
            $totalNilaiTelur = (int) round($totalTelurKg * $hargaTelurModal);
            $totalMarginKotorModal = $totalNilaiTelur - $totalBiayaPakan;
        @endphp

        {{-- 2. METADATA PETERNAKAN & REKAP --}}
        <div class="grid grid-cols-2 sm:grid-cols-5 gap-2 sm:gap-0 divide-y sm:divide-y-0 sm:divide-x divide-slate-200 rounded-xl bg-slate-50/80 border border-slate-200/90 p-3 sm:py-2.5 text-xs">
            <div class="px-2.5 py-1">
                <span class="text-slate-500 block text-[10px] uppercase font-bold tracking-wider">Kandang</span>
                <strong class="text-slate-900 text-xs sm:text-[13px] font-black block truncate mt-0.5">{{ $farm->nama }}</strong>
                <span class="text-[10.5px] text-slate-500 block">Fase {{ $faseModal['nama'] }}</span>
            </div>
            <div class="px-2.5 py-1">
                <span class="text-slate-500 block text-[10px] uppercase font-bold tracking-wider">Populasi Ayam</span>
                <strong class="text-slate-900 text-xs sm:text-[13px] font-black block mt-0.5">{{ number_format($jumlahAyam, 0, ',', '.') }} Ekor</strong>
                <span class="text-[10.5px] text-slate-500 block">Ayam Petelur</span>
            </div>
            <div class="px-2.5 py-1">
                <span class="text-slate-500 block text-[10px] uppercase font-bold tracking-wider">Umur & Strain</span>
                <strong class="text-slate-900 text-xs sm:text-[13px] font-black block mt-0.5">Mg ke-{{ $farm->umurMinggu() }}</strong>
                <span class="text-[10.5px] text-slate-500 block truncate">{{ $farm->strain ?? 'Lohmann Brown' }}</span>
            </div>
            <div class="px-2.5 py-1">
                <span class="text-slate-500 block text-[10px] uppercase font-bold tracking-wider">Periode Rekap</span>
                <strong class="text-slate-900 text-xs sm:text-[13px] font-black block mt-0.5">{{ $rentang }} Hari</strong>
                <span class="text-[10.5px] text-slate-500 block font-mono">{{ $logs->first()?->tanggal?->format('d/m') ?? '-' }} – {{ $logs->last()?->tanggal?->format('d/m') ?? '-' }}</span>
            </div>
            <div class="px-2.5 py-1">
                <span class="text-slate-500 block text-[10px] uppercase font-bold tracking-wider">Penanggung Jawab</span>
                <strong class="text-slate-900 text-xs sm:text-[13px] font-black block truncate mt-0.5">{{ auth()->user()->name ?? 'Peternak' }}</strong>
                <span class="text-[10.5px] text-slate-500 block">Supervisor</span>
            </div>
        </div>

        {{-- 3. KARTU RINGKASAN METRIK UTAMA (EXECUTIVE SUMMARY) --}}
        <div class="grid grid-cols-2 sm:grid-cols-5 gap-2.5 text-xs">
            <div class="p-3 rounded-xl border border-slate-200/90 bg-white shadow-2xs">
                <span class="text-slate-500 text-[10.5px] uppercase font-bold tracking-wider block mb-1">Total Produksi Telur</span>
                <strong class="text-base sm:text-lg text-slate-950 font-black block">{{ number_format($totalTelurButir, 0, ',', '.') }} <span class="text-xs font-normal text-slate-500">btr</span></strong>
                <span class="text-[11px] text-slate-500 block mt-1 font-mono">≈ {{ number_format($totalTelurKg, 1, ',', '.') }} kg telur</span>
            </div>
            <div class="p-3 rounded-xl border border-slate-200/90 bg-white shadow-2xs">
                <span class="text-slate-500 text-[10.5px] uppercase font-bold tracking-wider block mb-1">Konsumsi Pakan</span>
                <strong class="text-base sm:text-lg text-slate-950 font-black block">{{ number_format($totalPakan, 1, ',', '.') }} <span class="text-xs font-normal text-slate-500">kg</span></strong>
                <span class="text-[11px] text-slate-500 block mt-1 font-mono">Rata-rata {{ number_format($avgFeedPerBird, 0) }} g/ekor/hari</span>
            </div>
            <div class="p-3 rounded-xl border border-slate-200/90 bg-white shadow-2xs">
                <span class="text-slate-500 text-[10.5px] uppercase font-bold tracking-wider block mb-1">HDP & FCR Rata-rata</span>
                <strong class="text-base sm:text-lg text-slate-950 font-black block">HDP {{ number_format($avgHdp, 1, ',', '.') }}%</strong>
                <span class="text-[11px] text-slate-500 block mt-1 font-mono">FCR {{ number_format($avgFcr, 2, ',', '.') }} · Std: {{ $faseModal['standar_hdp'] }}</span>
            </div>
            <div class="p-3 rounded-xl border border-slate-200/90 bg-white shadow-2xs">
                <span class="text-slate-500 text-[10.5px] uppercase font-bold tracking-wider block mb-1">Est. Biaya Pakan</span>
                <strong class="text-base sm:text-lg text-slate-950 font-black block">Rp {{ number_format($totalBiayaPakan, 0, ',', '.') }}</strong>
                <span class="text-[11px] {{ $totalMati > 0 ? 'text-rose-600 font-bold' : 'text-slate-500' }} block mt-1">Mati: {{ $totalMati }} ekor ({{ number_format($persenKematian, 2) }}%)</span>
            </div>
            <div class="p-3 rounded-xl border border-slate-200/90 bg-white shadow-2xs col-span-2 sm:col-span-1">
                <span class="text-slate-500 text-[10.5px] uppercase font-bold tracking-wider block mb-1">Est. Margin Laba Kotor</span>
                <strong class="text-base sm:text-lg {{ $totalMarginKotorModal >= 0 ? 'text-emerald-700' : 'text-rose-700' }} font-black block">
                    {{ $totalMarginKotorModal >= 0 ? '+' : '' }}Rp {{ number_format($totalMarginKotorModal, 0, ',', '.') }}
                </strong>
                <span class="text-[11px] text-slate-500 block mt-1 truncate font-mono" title="Omzet: Rp {{ number_format($totalNilaiTelur, 0, ',', '.') }}">
                    Pendapatan: Rp {{ number_format($totalNilaiTelur, 0, ',', '.') }}
                </span>
            </div>
        </div>

        {{-- 4. TABEL REKAPITULASI HARIAN RESMI --}}
        <div class="overflow-x-auto rounded-xl border border-slate-200 shadow-2xs">
            <table class="print-table w-full text-xs text-left border-collapse">
                <thead>
                    <tr class="bg-slate-100 text-slate-700 font-bold text-center border-b-2 border-slate-200 text-[10.5px] uppercase tracking-wider">
                        <th class="py-2.5 px-2.5 border-r border-slate-200 w-9 text-slate-500">No</th>
                        <th class="py-2.5 px-2.5 border-r border-slate-200 w-24 text-left">Tanggal</th>
                        <th class="py-2.5 px-2.5 border-r border-slate-200 text-right">Pakan (kg)</th>
                        <th class="py-2.5 px-2.5 border-r border-slate-200 text-right">g/Ekor</th>
                        <th class="py-2.5 px-2.5 border-r border-slate-200 text-right">Telur (btr)</th>
                        <th class="py-2.5 px-2.5 border-r border-slate-200 text-right">Telur (kg)</th>
                        <th class="py-2.5 px-2.5 border-r border-slate-200 text-center">HDP (%)</th>
                        <th class="py-2.5 px-2.5 border-r border-slate-200 text-center">FCR</th>
                        <th class="py-2.5 px-2.5 border-r border-slate-200 text-center">Mati</th>
                        <th class="py-2.5 px-2.5 border-r border-slate-200 text-right">Biaya Pakan</th>
                        <th class="py-2.5 px-2.5 text-left">Catatan / Keterangan</th>
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
                        <tr class="{{ $idx % 2 === 1 ? 'bg-slate-50/60' : 'bg-white' }} page-break-avoid transition-colors hover:bg-slate-100/50">
                            <td class="py-2 px-2.5 border-r border-slate-200 text-center font-mono text-slate-400">{{ $idx + 1 }}</td>
                            <td class="py-2 px-2.5 border-r border-slate-200 text-left font-semibold text-slate-900 whitespace-nowrap">{{ $l->tanggal->translatedFormat('d/m/Y') }}</td>
                            <td class="py-2 px-2.5 border-r border-slate-200 text-right font-mono tabular-nums">{{ number_format($l->pakan_kg, 1, ',', '.') }}</td>
                            <td class="py-2 px-2.5 border-r border-slate-200 text-right font-mono tabular-nums text-slate-600">{{ round($rowGr) }}</td>
                            <td class="py-2 px-2.5 border-r border-slate-200 text-right font-mono tabular-nums font-bold text-slate-950">{{ number_format($l->telur_butir, 0, ',', '.') }}</td>
                            <td class="py-2 px-2.5 border-r border-slate-200 text-right font-mono tabular-nums text-slate-600">{{ number_format($rowKg, 1, ',', '.') }}</td>
                            <td class="py-2 px-2.5 border-r border-slate-200 text-center font-mono tabular-nums font-bold {{ $rowHdp >= 80 ? 'text-emerald-700' : ($rowHdp >= 65 ? 'text-teal-700' : 'text-amber-700') }}">
                                {{ number_format($rowHdp, 1, ',', '.') }}%
                            </td>
                            <td class="py-2 px-2.5 border-r border-slate-200 text-center font-mono tabular-nums text-slate-700">{{ number_format($rowFcr, 2, ',', '.') }}</td>
                            <td class="py-2 px-2.5 border-r border-slate-200 text-center font-mono tabular-nums {{ $l->ayam_mati > 0 ? 'text-rose-600 font-bold' : 'text-slate-400' }}">
                                {{ $l->ayam_mati > 0 ? $l->ayam_mati : '-' }}
                            </td>
                            <td class="py-2 px-2.5 border-r border-slate-200 text-right font-mono tabular-nums text-slate-800">Rp {{ number_format($rowBiaya, 0, ',', '.') }}</td>
                            <td class="py-2 px-2.5 text-slate-600 text-[11px] truncate max-w-[150px]" title="{{ $l->catatan }}">
                                {{ $l->catatan ? Str::limit($l->catatan, 35) : '-' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" class="py-6 text-center text-slate-400 italic">Belum ada catatan pada periode ini.</td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr class="bg-slate-100 text-slate-950 font-black border-t-2 border-slate-300 page-break-avoid">
                        <td colspan="2" class="py-2.5 px-2.5 border-r border-slate-200 text-center uppercase text-[11px] tracking-wider">TOTAL / RATA-RATA</td>
                        <td class="py-2.5 px-2.5 border-r border-slate-200 text-right font-mono tabular-nums">{{ number_format($totalPakan, 1, ',', '.') }}</td>
                        <td class="py-2.5 px-2.5 border-r border-slate-200 text-right font-mono tabular-nums">{{ round($avgFeedPerBird) }}</td>
                        <td class="py-2.5 px-2.5 border-r border-slate-200 text-right font-mono tabular-nums">{{ number_format($totalTelurButir, 0, ',', '.') }}</td>
                        <td class="py-2.5 px-2.5 border-r border-slate-200 text-right font-mono tabular-nums">{{ number_format($totalTelurKg, 1, ',', '.') }}</td>
                        <td class="py-2.5 px-2.5 border-r border-slate-200 text-center font-mono tabular-nums">{{ number_format($avgHdp, 1, ',', '.') }}%</td>
                        <td class="py-2.5 px-2.5 border-r border-slate-200 text-center font-mono tabular-nums">{{ number_format($avgFcr, 2, ',', '.') }}</td>
                        <td class="py-2.5 px-2.5 border-r border-slate-200 text-center font-mono tabular-nums">{{ $totalMati }}</td>
                        <td class="py-2.5 px-2.5 border-r border-slate-200 text-right font-mono tabular-nums">Rp {{ number_format($totalBiayaPakan, 0, ',', '.') }}</td>
                        <td class="py-2.5 px-2.5 text-center text-[10.5px] font-semibold text-slate-700">{{ $hariCount }} Hari Rekap</td>
                    </tr>
                </tfoot>
            </table>
        </div>

        {{-- 5. CATATAN EVALUASI & LEMBAR PENGESAHAN --}}
        <div class="pt-2 page-break-avoid space-y-4">
            <div class="p-3.5 sm:p-4 rounded-xl bg-slate-50 border border-slate-200 text-xs text-slate-800 leading-relaxed shadow-2xs">
                <div class="flex items-center gap-2 mb-1.5">
                    <span class="w-2 h-2 rounded-full bg-brand"></span>
                    <strong class="text-slate-950 font-bold">Catatan Evaluasi Teknis (Umur Minggu ke-{{ $farm->umurMinggu() }} · Fase {{ $faseModal['nama'] }}):</strong>
                </div>
                <p class="text-slate-700 pl-4">
                    @if ($avgHdp >= 85)
                        Produktivitas bertelur sangat prima (Rata-rata HDP <strong>{{ number_format($avgHdp, 1) }}%</strong> memenuhi target standar bibit {{ $farm->strain ?? 'Lohmann Brown' }} pada rentang {{ $faseModal['standar_hdp'] }}). Rasio FCR dan konversi pakan terpantau terkendali dengan baik serta menghasilkan margin laba kotor positif.
                    @elseif ($avgHdp >= 70)
                        Produktivitas bertelur dalam kategori stabil (HDP <strong>{{ number_format($avgHdp, 1) }}%</strong>). Pertahankan konsistensi formulasi ransum dan biosekuriti kandang untuk menjaga stabilitas produksi.
                    @else
                        Produktivitas bertelur memerlukan evaluasi (HDP <strong>{{ number_format($avgHdp, 1) }}%</strong> berada di bawah standar {{ $faseModal['standar_hdp'] }}). Dianjurkan meninjau kecukupan nutrisi ransum dan mikroklimat kandang.
                    @endif
                </p>
            </div>

            <div class="flex justify-between items-end pt-3 text-xs text-slate-900">
                <div class="text-center w-56">
                    <p class="text-slate-500 mb-1 text-[11px]">Dicatat & Disusun Oleh,</p>
                    <p class="font-extrabold text-slate-900">Operator / Petugas Kandang</p>
                    <div class="h-16 flex items-end justify-center pb-1">
                        <span class="text-[11px] text-slate-300 italic select-none">( Tanda Tangan )</span>
                    </div>
                    <div class="w-44 mx-auto border-b border-slate-400"></div>
                    <p class="font-bold text-slate-900 mt-1.5">{{ auth()->user()->name ?? 'Operator Kandang' }}</p>
                    <p class="text-[10px] text-slate-500">Petugas Pencatatan Harian</p>
                </div>

                <div class="text-center w-56">
                    <p class="text-slate-500 mb-1 text-[11px]">{{ $farm->nama }}, {{ now()->translatedFormat('d F Y') }}</p>
                    <p class="font-extrabold text-slate-900">Pimpinan / Pemilik Peternakan</p>
                    <div class="h-16 flex items-end justify-center pb-1">
                        <span class="text-[11px] text-slate-300 italic select-none">( Tanda Tangan & Stempel )</span>
                    </div>
                    <div class="w-44 mx-auto border-b border-slate-400"></div>
                    <p class="font-bold text-slate-900 mt-1.5">Mengetahui & Menyetujui,</p>
                    <p class="text-[10px] text-slate-500">{{ $farm->nama }}</p>
                </div>
            </div>
        </div>

        {{-- 6. FOOTER OTENTIKASI SISTEM --}}
        <div class="border-t border-slate-200 pt-2 text-[9.5px] text-slate-400 flex items-center justify-between">
            <span>Dokumen resmi diterbitkan otomatis oleh <strong>RansumKu Smart Poultry Farm System</strong>.</span>
            <span>Halaman 1 dari 1 · Verifikasi: {{ strtoupper(substr(md5($farm->id . $farm->nama . now()->toDateString()), 0, 10)) }}</span>
        </div>
    </div>
</div>

{{-- Skrip Interaktivitas Smart Buku Kandang --}}
<script>
document.addEventListener('DOMContentLoaded', function () {
    const jumlahAyam = {{ $jumlahAyam }};
    const hargaPakanPerKg = {{ $harga }};
    const existingLogs = @json($existingLogs);

    const inputTanggal = document.getElementById('tanggal');
    const inputPakan = document.getElementById('pakan_kg');
    const inputTelur = document.getElementById('telur_butir');
    const inputBerat = document.getElementById('telur_kg');
    const inputMati = document.getElementById('ayam_mati');
    const inputBiayaLain = document.getElementById('biaya_lain');
    const inputCatatan = document.getElementById('catatan');

    const previewHdpVal = document.getElementById('preview-hdp-val');
    const previewHdpBadge = document.getElementById('preview-hdp-badge');
    const previewFeedVal = document.getElementById('preview-feed-val');
    const previewFeedBadge = document.getElementById('preview-feed-badge');
    const previewFcrVal = document.getElementById('preview-fcr-val');
    const previewFcrBadge = document.getElementById('preview-fcr-badge');
    const previewCostVal = document.getElementById('preview-cost-val');
    const previewAlertBox = document.getElementById('preview-alert-box');
    const previewAlertIcon = document.getElementById('preview-alert-icon');
    const previewAlertText = document.getElementById('preview-alert-text');
    const modeStatusBanner = document.getElementById('mode-status-banner');
    const modeStatusDate = document.getElementById('mode-status-date');
    const btnSubmitText = document.getElementById('btn-submit-text');

    function updateLivePreview() {
        const pakan = parseFloat(inputPakan.value) || 0;
        const butir = parseInt(inputTelur.value) || 0;
        let berat = parseFloat(inputBerat.value);
        if (isNaN(berat) || berat <= 0) {
            berat = butir * 0.062;
        }
        const mati = parseInt(inputMati ? inputMati.value : 0) || 0;

        // 1. HDP %
        const hdp = jumlahAyam > 0 ? (butir / jumlahAyam) * 100 : 0;
        previewHdpVal.textContent = hdp > 0 ? hdp.toFixed(1) + '%' : '0.0%';
        if (butir === 0) {
            previewHdpBadge.className = 'inline-flex items-center px-3 py-1 rounded-full text-[10px] font-bold bg-[#e8f2eb] text-[#476b55]';
            previewHdpBadge.textContent = 'Menunggu data';
        } else if (hdp >= 85) {
            previewHdpBadge.className = 'inline-flex items-center px-3 py-1 rounded-full text-[10px] font-bold bg-[#1f7a4a] text-white';
            previewHdpBadge.textContent = 'Prima / Sangat Baik';
        } else if (hdp >= 75) {
            previewHdpBadge.className = 'inline-flex items-center px-3 py-1 rounded-full text-[10px] font-bold bg-[#dcefe2] text-[#0f3d26]';
            previewHdpBadge.textContent = 'Normal';
        } else {
            previewHdpBadge.className = 'inline-flex items-center px-3 py-1 rounded-full text-[10px] font-bold bg-[#fdf0cf] text-[#7a4d05]';
            previewHdpBadge.textContent = 'Perhatian (<75%)';
        }

        // 2. Konsumsi gr/ekor/hari
        const grPerEkor = (jumlahAyam > 0 && pakan > 0) ? (pakan * 1000) / jumlahAyam : 0;
        previewFeedVal.innerHTML = `${Math.round(grPerEkor)} <span class="text-xs font-normal">gr</span>`;
        if (pakan === 0) {
            previewFeedBadge.className = 'inline-flex items-center px-3 py-1 rounded-full text-[10px] font-bold bg-[#e8f2eb] text-[#476b55]';
            previewFeedBadge.textContent = 'std: 110g';
        } else if (grPerEkor >= 105 && grPerEkor <= 125) {
            previewFeedBadge.className = 'inline-flex items-center px-3 py-1 rounded-full text-[10px] font-bold bg-[#dcefe2] text-[#0f3d26]';
            previewFeedBadge.textContent = 'Ideal (110-125g)';
        } else if (grPerEkor < 105) {
            previewFeedBadge.className = 'inline-flex items-center px-3 py-1 rounded-full text-[10px] font-bold bg-[#fdf0cf] text-[#7a4d05]';
            previewFeedBadge.textContent = 'Rendah (<105g)';
        } else {
            previewFeedBadge.className = 'inline-flex items-center px-3 py-1 rounded-full text-[10px] font-bold bg-[#ffe4e6] text-[#be123c]';
            previewFeedBadge.textContent = 'Boros (>125g)';
        }

        // 3. FCR Instan
        const fcr = (berat > 0 && pakan > 0) ? (pakan / berat) : 0;
        previewFcrVal.textContent = fcr > 0 ? fcr.toFixed(2) : '0.00';
        if (fcr === 0) {
            previewFcrBadge.className = 'inline-flex items-center px-3 py-1 rounded-full text-[10px] font-bold bg-[#e8f2eb] text-[#476b55]';
            previewFcrBadge.textContent = 'Efisiensi';
        } else if (fcr <= 2.15) {
            previewFcrBadge.className = 'inline-flex items-center px-3 py-1 rounded-full text-[10px] font-bold bg-[#dcefe2] text-[#0f3d26]';
            previewFcrBadge.textContent = 'Sangat Efisien';
        } else if (fcr <= 2.45) {
            previewFcrBadge.className = 'inline-flex items-center px-3 py-1 rounded-full text-[10px] font-bold bg-[#e8f2eb] text-[#476b55]';
            previewFcrBadge.textContent = 'Normal';
        } else {
            previewFcrBadge.className = 'inline-flex items-center px-3 py-1 rounded-full text-[10px] font-bold bg-[#ffe4e6] text-[#be123c]';
            previewFcrBadge.textContent = 'Tinggi (>2.45)';
        }

        // 4. Biaya Pakan Hari Ini & Est. Margin Kotor
        const totalBiaya = pakan * hargaPakanPerKg;
        previewCostVal.textContent = 'Rp ' + Math.round(totalBiaya).toLocaleString('id-ID');
        const previewMarginSub = document.getElementById('preview-margin-sub');
        if (previewMarginSub) {
            const hargaTelur = {{ (int) config('ransumku.harga_telur_default', 26000) }};
            const estKg = berat > 0 ? berat : (butir * 0.062);
            const estRevenue = estKg * hargaTelur;
            if (estRevenue > 0 && totalBiaya > 0) {
                const margin = estRevenue - totalBiaya;
                const sign = margin >= 0 ? '+' : '';
                previewMarginSub.textContent = `Est. Margin: ${sign}Rp ${Math.round(margin).toLocaleString('id-ID')}`;
                previewMarginSub.className = margin >= 0 ? 'inline-block mt-0.5 text-[10px] font-bold text-emerald-700' : 'inline-block mt-0.5 text-[10px] font-bold text-rose-700';
            } else {
                previewMarginSub.textContent = '@ {{ Fmt::rp($harga) }}/kg';
                previewMarginSub.className = 'inline-block mt-0.5 text-[10px] text-muted font-medium';
            }
        }

        // Dynamic Alert Message
        if (mati >= 5) {
            previewAlertBox.className = 'p-2 rounded-xl text-xs font-semibold flex items-center gap-2 bg-rose-100 text-rose-900 border border-rose-300';
            previewAlertIcon.textContent = 'warning';
            previewAlertText.textContent = `Perhatian: Ada ${mati} ekor ayam mati tercatat hari ini. Pantau sirkulasi dan gejala penyakit!`;
            previewAlertBox.classList.remove('hidden');
        } else if (hdp > 0 && hdp < 65) {
            previewAlertBox.className = 'p-2 rounded-xl text-xs font-semibold flex items-center gap-2 bg-amber-100 text-amber-900 border border-amber-300';
            previewAlertIcon.textContent = 'info';
            previewAlertText.textContent = `Produksi telur rendah (${hdp.toFixed(1)}%). Cek kecukupan nutrisi pakan atau potensi stres suhu.`;
            previewAlertBox.classList.remove('hidden');
        } else if (hdp >= 88 && fcr > 0 && fcr <= 2.15) {
            previewAlertBox.className = 'p-2 rounded-xl text-xs font-semibold flex items-center gap-2 bg-emerald-100 text-emerald-900 border border-emerald-300';
            previewAlertIcon.textContent = 'verified';
            previewAlertText.textContent = `Luar biasa! Performa kandang hari ini sangat prima (HDP ${hdp.toFixed(1)}%, FCR ${fcr.toFixed(2)}).`;
            previewAlertBox.classList.remove('hidden');
        } else {
            previewAlertBox.classList.add('hidden');
        }
    }

    [inputPakan, inputTelur, inputBerat, inputMati].forEach(el => {
        if (el) {
            el.addEventListener('input', updateLivePreview);
        }
    });

    // Button: Hitung ~62g/butir
    const btnCalcEgg = document.getElementById('btn-calc-egg-weight');
    if (btnCalcEgg && inputTelur && inputBerat) {
        btnCalcEgg.addEventListener('click', function () {
            const butir = parseInt(inputTelur.value) || 0;
            if (butir > 0) {
                const estKg = (butir * 0.062).toFixed(1);
                inputBerat.value = estKg;
                updateLivePreview();
            } else {
                window.showToast('Silakan isi jumlah butir telur terlebih dahulu.', 'warning', 'Perhatian');
                inputTelur.focus();
            }
        });
    }

    // Kalkulator Bantu Hitung Rak Telur (1 Rak = 30 butir)
    const btnToggleRak = document.getElementById('btn-toggle-rak-calc');
    const popoverRak = document.getElementById('popover-calc-rak');
    const inputCalcRak = document.getElementById('calc_rak_input');
    const inputCalcSisa = document.getElementById('calc_sisa_input');
    const previewRakTotal = document.getElementById('calc_rak_total_preview');
    const btnApplyRak = document.getElementById('btn-apply-rak-calc');
    const btnCancelRak = document.getElementById('btn-cancel-rak-calc');

    function updateRakPreviewTotal() {
        const rak = parseInt(inputCalcRak ? inputCalcRak.value : 0) || 0;
        const sisa = parseInt(inputCalcSisa ? inputCalcSisa.value : 0) || 0;
        const total = (rak * 30) + sisa;
        if (previewRakTotal) {
            previewRakTotal.textContent = `${total.toLocaleString('id-ID')} butir (${rak} rak + ${sisa} btr)`;
        }
        return total;
    }

    if (btnToggleRak && popoverRak) {
        btnToggleRak.addEventListener('click', function (e) {
            e.stopPropagation();
            const isHidden = popoverRak.classList.contains('hidden');
            if (isHidden) {
                popoverRak.classList.remove('hidden');
                const currentTotal = parseInt(inputTelur?.value) || 0;
                if (currentTotal > 0 && inputCalcRak && inputCalcSisa) {
                    inputCalcRak.value = Math.floor(currentTotal / 30);
                    inputCalcSisa.value = currentTotal % 30;
                }
                updateRakPreviewTotal();
                if (inputCalcRak) inputCalcRak.focus();
            } else {
                popoverRak.classList.add('hidden');
            }
        });
    }

    [inputCalcRak, inputCalcSisa].forEach(el => {
        if (el) {
            el.addEventListener('input', updateRakPreviewTotal);
            el.addEventListener('keydown', function (e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    if (btnApplyRak) btnApplyRak.click();
                }
            });
        }
    });

    if (btnApplyRak && inputTelur && popoverRak) {
        btnApplyRak.addEventListener('click', function () {
            const total = updateRakPreviewTotal();
            inputTelur.value = total;
            popoverRak.classList.add('hidden');
            updateLivePreview();
            if (window.showToast) {
                window.showToast(`Berhasil menerapkan ${total.toLocaleString('id-ID')} butir telur.`, 'success', 'Kalkulator Rak');
            }
        });
    }

    if (btnCancelRak && popoverRak) {
        btnCancelRak.addEventListener('click', function () {
            popoverRak.classList.add('hidden');
        });
    }

    document.addEventListener('click', function (e) {
        if (popoverRak && !popoverRak.classList.contains('hidden')) {
            if (!popoverRak.contains(e.target) && e.target !== btnToggleRak && !btnToggleRak.contains(e.target)) {
                popoverRak.classList.add('hidden');
            }
        }
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && popoverRak && !popoverRak.classList.contains('hidden')) {
            popoverRak.classList.add('hidden');
        }
    });

    // Button: Quick fill from yesterday
    const btnQuickFill = document.getElementById('btn-quick-fill');
    if (btnQuickFill) {
        btnQuickFill.addEventListener('click', function () {
            const pakan = this.dataset.pakan;
            const telur = this.dataset.telur;
            const berat = this.dataset.berat;
            if (pakan) inputPakan.value = pakan;
            if (telur) inputTelur.value = telur;
            if (berat) inputBerat.value = berat;
            updateLivePreview();
        });
    }

    // Detect date changes and switch edit mode
    const tanggalDisplay = document.getElementById('tanggal-display');
    function updateTanggalDisplay(val) {
        if (!val || !tanggalDisplay) return;
        const parts = val.split('-');
        if (parts.length === 3) {
            const months = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
            const day = parseInt(parts[2], 10);
            const month = months[parseInt(parts[1], 10) - 1];
            const year = parts[0];
            tanggalDisplay.textContent = `${day} ${month} ${year}`;
        }
    }

    if (inputTanggal) {
        inputTanggal.addEventListener('input', function () {
            updateTanggalDisplay(this.value);
        });

        inputTanggal.addEventListener('change', function () {
            const tgl = this.value;
            updateTanggalDisplay(tgl);
            if (existingLogs && existingLogs[tgl]) {
                const log = existingLogs[tgl];
                if (modeStatusBanner) {
                    modeStatusBanner.classList.remove('hidden');
                    if (modeStatusDate) {
                        const parts = tgl.split('-');
                        if (parts.length === 3) {
                            const months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
                            const day = parseInt(parts[2], 10);
                            const month = months[parseInt(parts[1], 10) - 1];
                            const year = parts[0];
                            modeStatusDate.textContent = `${day} ${month} ${year}`;
                        } else {
                            modeStatusDate.textContent = tgl;
                        }
                    }
                }
                if (btnSubmitText) btnSubmitText.textContent = 'Perbarui Catatan';
                inputPakan.value = log.pakan_kg;
                inputTelur.value = log.telur_butir;
                inputBerat.value = log.telur_kg !== null ? log.telur_kg : '';
                if (inputMati) inputMati.value = log.ayam_mati;
                if (inputBiayaLain) inputBiayaLain.value = log.biaya_lain;
                if (inputCatatan) inputCatatan.value = log.catatan;
            } else {
                if (modeStatusBanner) modeStatusBanner.classList.add('hidden');
                if (btnSubmitText) btnSubmitText.textContent = 'Simpan Catatan';
            }
            updateLivePreview();
        });
    }

    // Salin Laporan Mingguan ke WhatsApp
    const btnCopyWa = document.getElementById('btn-copy-wa');
    if (btnCopyWa) {
        btnCopyWa.addEventListener('click', function () {
            const text = `📊 *LAPORAN PERFORMA KANDANG - RANSUMKU*\n`
                + `🏠 Kandang: {{ $farm->nama }}\n`
                + `🗓️ Periode: 7 Hari Terakhir\n`
                + `-----------------------------------\n`
                + `🥚 *Total Telur*: {{ Fmt::num($laporan['ini']['telur'], 0) }} butir\n`
                + `🌾 *Konsumsi Pakan*: {{ Fmt::num($laporan['ini']['pakan'], 1) }} kg ({{ $laporan['ini']['hari'] ? round($laporan['ini']['pakan'] / $laporan['ini']['hari'], 1) : 0 }} kg/hari)\n`
                + `💰 *Est. Biaya Pakan*: {{ Fmt::rp($laporan['ini']['biaya_pakan']) }}\n`
                + `⚖️ *Est. Biaya / Kg Telur*: {{ Fmt::rp($laporan['ini']['biaya_kg_telur']) }}\n`
                + `🐔 *Ayam Mati*: {{ $laporan['ini']['mati'] }} ekor\n`
                + `-----------------------------------\n`
                + `_Dicatat otomatis via RansumKu Smart Poultry Farm_`;

            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(text).then(() => {
                    window.showToast('Laporan mingguan berhasil disalin ke clipboard! Silakan paste (Ctrl+V) ke chat WhatsApp.', 'success', 'Berhasil Menyalin');
                }).catch(() => {
                    copyViaTextarea(text);
                });
            } else {
                copyViaTextarea(text);
            }
        });
    }

    function copyViaTextarea(text) {
        const ta = document.createElement('textarea');
        ta.value = text;
        ta.style.position = 'fixed';
        ta.style.opacity = '0';
        document.body.appendChild(ta);
        ta.select();
        try {
            document.execCommand('copy');
            window.showToast('Laporan mingguan berhasil disalin ke clipboard! Silakan paste (Ctrl+V) ke chat WhatsApp.', 'success', 'Berhasil Menyalin');
        } catch (e) {
            window.showToast('Gagal menyalin teks laporan.', 'error', 'Gagal Menyalin');
        }
        document.body.removeChild(ta);
    }

    // Unduh CSV Catatan Riwayat
    const btnExportCsv = document.getElementById('btn-export-csv');
    if (btnExportCsv) {
        btnExportCsv.addEventListener('click', function () {
            const rows = [
                ["Tanggal", "Pakan (kg)", "Telur (butir)", "Telur (kg)", "Ayam Mati", "Biaya Lain (Rp)", "Catatan"]
            ];
            @foreach ($allLogs as $r)
            rows.push([
                "{{ $r->tanggal->toDateString() }}",
                "{{ $r->pakan_kg }}",
                "{{ $r->telur_butir }}",
                "{{ $r->telurKgEfektif() }}",
                "{{ $r->ayam_mati }}",
                "{{ $r->biaya_lain }}",
                "{{ str_replace(['\r', '\n', '"'], ['', '', ''], $r->catatan ?? '') }}"
            ]);
            @endforeach

            let csvContent = "data:text/csv;charset=utf-8," + rows.map(e => e.join(",")).join("\n");
            let encodedUri = encodeURI(csvContent);
            let link = document.createElement("a");
            link.setAttribute("href", encodedUri);
            link.setAttribute("download", "buku_kandang_{{ Str::slug($farm->nama) }}_" + new Date().toISOString().slice(0,10) + ".csv");
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        });
    }

    // Modal Pratinjau & Cetak Laporan Resmi A4/PDF
    const modalLaporan = document.getElementById('modal-laporan-resmi');
    const btnBukaLaporan = document.getElementById('btn-buka-laporan-modal');
    const btnBukaLaporanForm = document.getElementById('btn-buka-laporan-modal-form');
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

    if (btnBukaLaporan) btnBukaLaporan.addEventListener('click', bukaModalLaporan);
    if (btnBukaLaporanForm) btnBukaLaporanForm.addEventListener('click', bukaModalLaporan);
    if (btnTutupLaporan) btnTutupLaporan.addEventListener('click', tutupModalLaporan);

    if (btnCetakDokumen) {
        btnCetakDokumen.addEventListener('click', function () {
            window.print();
        });
    }

    // Tutup modal jika klik di luar area dokumen atau tombol Esc
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

    // Live Kalkulator Belanja Pakan (Subtotal & Deviasi)
    const inputBeliKg = document.getElementById('kg');
    const inputBeliHarga = document.getElementById('harga_nyata');
    const inputIngredientId = document.querySelector('input[name="ingredient_id"]');
    const beliSubtotalVal = document.getElementById('beli-subtotal-val');
    const beliDeviasiBadge = document.getElementById('beli-deviasi-badge');
    const bahanPrices = @json($bahanPrices);

    function updateBeliPreview() {
        if (!inputBeliKg || !inputBeliHarga || !beliSubtotalVal || !beliDeviasiBadge) return;
        const kg = parseFloat(inputBeliKg.value) || 0;
        const harga = parseFloat(inputBeliHarga.value) || 0;
        const ingId = inputIngredientId ? inputIngredientId.value : null;
        const hargaAcuan = (ingId && bahanPrices[ingId]) ? bahanPrices[ingId] : null;

        const subtotal = kg * harga;
        beliSubtotalVal.textContent = 'Rp ' + Math.round(subtotal).toLocaleString('id-ID');

        if (kg <= 0 || harga <= 0) {
            beliDeviasiBadge.className = 'px-2 py-0.5 rounded-full text-[10.5px] font-bold bg-slate-100 text-muted';
            beliDeviasiBadge.textContent = 'Menunggu input kg & harga';
            return;
        }

        if (hargaAcuan) {
            const deviasiPerKg = harga - hargaAcuan;
            const totalDeviasi = deviasiPerKg * kg;
            if (deviasiPerKg > 0) {
                beliDeviasiBadge.className = 'px-2 py-0.5 rounded-full text-[10.5px] font-extrabold bg-rose-50 text-rose-700 border border-rose-200';
                beliDeviasiBadge.textContent = `+Rp ${Math.round(totalDeviasi).toLocaleString('id-ID')} (Lebih Tinggi dari Acuan Rp ${hargaAcuan.toLocaleString('id-ID')}/kg)`;
            } else if (deviasiPerKg < 0) {
                beliDeviasiBadge.className = 'px-2 py-0.5 rounded-full text-[10.5px] font-extrabold bg-emerald-50 text-emerald-700 border border-emerald-200';
                beliDeviasiBadge.textContent = `Hemat Rp ${Math.abs(Math.round(totalDeviasi)).toLocaleString('id-ID')} (Lebih Murah dari Acuan Rp ${hargaAcuan.toLocaleString('id-ID')}/kg)`;
            } else {
                beliDeviasiBadge.className = 'px-2 py-0.5 rounded-full text-[10.5px] font-bold bg-teal-50 text-teal-700 border border-teal-200';
                beliDeviasiBadge.textContent = `Sesuai Harga Acuan Formulasi (Rp ${hargaAcuan.toLocaleString('id-ID')}/kg)`;
            }
        } else {
            beliDeviasiBadge.className = 'px-2 py-0.5 rounded-full text-[10.5px] font-bold bg-slate-100 text-slate-700 border border-slate-200';
            beliDeviasiBadge.textContent = 'Harga Belanja Normal';
        }
    }

    if (inputBeliKg) inputBeliKg.addEventListener('input', updateBeliPreview);
    if (inputBeliHarga) inputBeliHarga.addEventListener('input', updateBeliPreview);
    if (inputIngredientId) inputIngredientId.addEventListener('change', updateBeliPreview);

    // Jalankan kalkulasi awal saat halaman dimuat
    updateLivePreview();
    updateBeliPreview();
});
</script>
@endsection
