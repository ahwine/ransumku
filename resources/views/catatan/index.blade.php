@extends('layouts.app')
@section('judul', 'Buku Kandang')
@section('isi')
@use('App\Support\Fmt')
@use('Illuminate\Support\Str')

@php
    $tabAktif = in_array(request()->query('tab'), ['form', 'beli'], true) ? request()->query('tab') : 'form';
@endphp

{{-- Mobile Header (Image 1 style) --}}
<div data-mobile-catatan-header class="block md:hidden space-y-1.5 mb-2">
    <div class="flex items-center justify-between gap-3">
        <h1 class="text-2xl font-black text-[#0f3d26] tracking-tight">Buku Kandang</h1>
        <a href="{{ route('kandang.index') }}" class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full text-xs font-bold text-[#0f3d26] bg-white border border-[#d9e6dd] shadow-2xs hover:bg-[#f4f8f5] transition shrink-0" title="Kelola Multi-Kandang">
            <span class="material-symbols-outlined text-[18px] text-[#1f7a4a]">segment</span>
            <span>Ganti Kandang</span>
        </a>
    </div>
    <p class="text-xs text-[#567263] leading-relaxed">Pencatatan produksi harian dan riwayat belanja bahan baku pakan.</p>
</div>

{{-- Desktop Header --}}
<div class="hidden md:flex flex-wrap items-center justify-between gap-3 mb-2">
    <div>
        <div class="flex items-baseline gap-2">
            <h1 class="text-lg md:text-xl font-extrabold text-ink tracking-tight">{{ $farm->nama ?? 'Kandang Maju Jaya' }}</h1>
            <span class="text-xs sm:text-sm text-muted font-medium">({{ number_format($jumlahAyam, 0, ',', '.') }} ekor)</span>
        </div>
        <p class="text-muted text-xs sm:text-sm mt-0.5">Pencatatan produksi harian dan riwayat belanja bahan baku pakan</p>
    </div>

    <div class="flex items-center gap-2 shrink-0">
        <a href="{{ route('kandang.index') }}" class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full text-xs font-bold text-ink hover:text-brand bg-white hover:bg-surface-2 border border-line hover:border-brand/40 shadow-2xs hover:shadow-xs transition" title="Kelola Multi-Kandang">
            <span class="material-symbols-outlined text-[15px] text-brand">tune</span>
            <span>Ganti Kandang</span>
        </a>
    </div>
</div>

{{-- Tabs (2 Tab Fokus: Catat Harian & Belanja Pakan) --}}
<div data-tabs class="space-y-4">
    <div class="w-full">
        <div data-tab-nav class="tab-segmented-track relative grid grid-cols-2 gap-1 p-1 rounded-full text-center select-none w-full bg-[#e6eeea] md:bg-[#e4eee7] border border-line/60 shadow-2xs overflow-hidden">
            <div data-tab-slider class="tab-slider !rounded-full" aria-hidden="true"></div>
            <button type="button" data-tab="form" class="w-full relative z-10 py-2.5 md:py-2 px-1.5 md:px-4 rounded-full transition-colors duration-200 cursor-pointer outline-none focus:outline-none focus:ring-0 select-none whitespace-nowrap text-xs sm:text-sm md:text-xs font-bold {{ $tabAktif === 'form' ? 'active-tab bg-white shadow-xs text-[#0f3d26] font-black' : 'text-ink/75 md:text-[#567263] hover:text-ink md:hover:text-[#0f3d26] font-bold' }} flex items-center justify-center">
                <span class="md:hidden">Catat Harian</span>
                <span class="hidden md:inline">Catat Harian</span>
            </button>
            <button type="button" data-tab="beli" class="w-full relative z-10 py-2.5 md:py-2 px-1.5 md:px-4 rounded-full transition-colors duration-200 cursor-pointer outline-none focus:outline-none focus:ring-0 select-none whitespace-nowrap text-xs sm:text-sm md:text-xs font-bold {{ $tabAktif === 'beli' ? 'active-tab bg-white shadow-xs text-[#0f3d26] font-black' : 'text-ink/75 md:text-[#567263] hover:text-ink md:hover:text-[#0f3d26] font-bold' }} flex items-center justify-center">
                <span class="md:hidden">Belanja Pakan</span>
                <span class="hidden md:inline">Belanja Pakan</span>
            </button>
        </div>
    </div>

    {{-- Panel 1: Form Catatan Harian Cerdas --}}
    <section data-panel="form" class="{{ $tabAktif === 'form' ? '' : 'hidden' }} space-y-4">
        {{-- ======================================================== --}}
        {{-- A. TAMPILAN MOBILE CATAT HARIAN (block md:hidden)         --}}
        {{-- ======================================================== --}}
        <div class="block md:hidden space-y-4" id="catat-mobile-container">
        {{-- Grid 2 Kolom: Kiri (Form Input) & Kanan (Live Cockpit & Pratinjau Analitik) --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-5 items-stretch">
            {{-- KOLOM KIRI (1/2): Formulir Input Catatan Harian (Image 1 style) --}}
            <div class="flex flex-col">
                <form method="POST" action="{{ route('catatan.store') }}" class="bg-white rounded-lg sm:rounded-xl p-4 sm:p-5 shadow-xs space-y-4 flex-1 flex flex-col justify-between" style="border: none;">
                    @csrf
                    <div class="space-y-4">
                        {{-- Header Form Bersih & Tegas (Image 1 style) --}}
                        <div class="flex items-center gap-3 pb-3 border-b border-[#eef4f0]">
                            <div class="w-10 h-10 rounded-lg flex items-center justify-center shrink-0" style="background-color: #dcefe2; color: #1f7a4a;">
                                <span class="material-symbols-outlined text-[22px]">edit</span>
                            </div>
                            <div>
                                <h2 class="text-base font-extrabold text-[#0f3d26] tracking-tight leading-tight">Catat Data Hari Ini</h2>
                                <p class="text-xs text-[#567263] font-medium">{{ $farm->nama }}</p>
                            </div>
                        </div>

                        {{-- Banner Mode Edit (Muncul jika tanggal yang dipilih sudah ada catatan) --}}
                        <div id="mode-status-banner" class="{{ $isEdit ? '' : 'hidden' }} p-3.5 sm:p-4 rounded-md sm:rounded-xl flex items-center justify-between gap-3 transition-all" style="background-color: #fffdf5; border: 1.5px solid #fde68a;">
                            <div class="flex items-center gap-3 sm:gap-3.5 min-w-0">
                                <div class="w-11 h-11 sm:w-12 sm:h-12 rounded-lg flex items-center justify-center shrink-0 shadow-2xs" style="background-color: #fef3c7; color: #92400e;">
                                    <span class="material-symbols-outlined text-[24px] sm:text-[26px]">edit_calendar</span>
                                </div>
                                <div class="min-w-0 space-y-1">
                                    <div>
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold inline-block" style="background-color: #fef3c7; color: #92400e;">Mode edit</span>
                                    </div>
                                    <span id="mode-status-date" class="text-sm sm:text-base font-black text-ink block leading-tight tracking-tight">
                                        {{ $form->tanggal ? $form->tanggal->translatedFormat('d F Y') : now()->translatedFormat('d F Y') }}
                                    </span>
                                    <p class="text-[11px] sm:text-xs text-[#567263] font-normal leading-tight">Mengubah catatan yang sudah tersimpan.</p>
                                </div>
                            </div>
                            <a href="{{ route('catatan.index', ['tab' => 'form']) }}" id="btn-batal-edit" class="inline-flex items-center gap-1.5 px-3 sm:px-3.5 py-1.5 sm:py-2 rounded-lg text-xs font-bold bg-white text-[#92400e] hover:bg-amber-50/70 active:scale-95 transition shadow-2xs shrink-0" style="border: 1.5px solid #fde68a;" title="Reset formulir ke hari ini">
                                <span class="material-symbols-outlined text-[16px] text-[#b45309]">restart_alt</span>
                                <span>Reset</span>
                            </a>
                        </div>

                        {{-- Quick Fill dari Catatan Kemarin (Sesuai Mockup Foto 1) --}}
                        @if ($yesterdayLog)
                        <div class="p-4 sm:p-5 rounded-md sm:rounded-xl bg-[#f2f7f4] border border-[#d7e6dc] space-y-3.5 transition-all">
                            <div class="flex items-start justify-between gap-3">
                                <div class="flex items-center gap-3 min-w-0">
                                    <div class="w-12 h-12 rounded-lg flex items-center justify-center shrink-0 bg-[#e3eee6] border border-[#d2e3d7]">
                                        <span class="material-symbols-outlined text-[24px] text-[#0f3d26]">calendar_today</span>
                                    </div>
                                    <div class="min-w-0 space-y-0.5">
                                        <h3 class="text-base font-extrabold text-[#0f3d26] leading-tight">Catatan Kemarin</h3>
                                        <p class="text-xs sm:text-sm font-semibold text-[#0f3d26] leading-tight">
                                            {{ Fmt::num($yesterdayLog->pakan_kg, 1) }} kg pakan &nbsp;·&nbsp; {{ Fmt::num($yesterdayLog->telur_butir, 0) }} butir telur
                                        </p>
                                    </div>
                                </div>
                                <span class="px-3 py-1 rounded-full text-xs font-bold bg-[#e3eee6] text-[#0f3d26] shrink-0 border border-[#d2e3d7]/60">
                                    {{ $yesterdayLog->tanggal->translatedFormat('d M') }}
                                </span>
                            </div>
                            <button type="button" id="btn-quick-fill"
                                    data-pakan="{{ (float) $yesterdayLog->pakan_kg }}"
                                    data-telur="{{ (int) $yesterdayLog->telur_butir }}"
                                    data-berat="{{ $yesterdayLog->telur_kg !== null ? (float) $yesterdayLog->telur_kg : '' }}"
                                    class="w-full py-3.5 px-4 rounded-xl text-sm font-bold text-white bg-[#115e37] hover:bg-[#0d4a2b] active:scale-[0.99] transition shadow-xs flex items-center justify-center gap-2 cursor-pointer">
                                <span class="material-symbols-outlined text-[20px] text-white icon-fill">bolt</span>
                                <span>Salin Angka Kemarin</span>
                            </button>
                        </div>
                        @endif

                        {{-- Form Inputs Stack --}}
                        <div class="space-y-3.5">
                            {{-- Field 1: Tanggal Pencatatan --}}
                            <div>
                                <div class="flex items-center justify-between mb-1.5 min-h-[20px]">
                                    <label class="block text-xs font-bold text-[#0f3d26]" for="tanggal">Tanggal Pencatatan</label>
                                </div>
                                <div class="relative cursor-pointer" onclick="const inp = document.getElementById('tanggal'); if (inp) { try { inp.showPicker(); } catch(e) { inp.focus(); } }">
                                    <input id="tanggal" name="tanggal" type="date" value="{{ old('tanggal', $form->tanggal->toDateString()) }}" max="{{ today()->toDateString() }}"
                                           onclick="try { this.showPicker(); } catch(e) {}"
                                           class="mobile-date-picker-input w-full h-11 rounded-xl bg-white px-3.5 pr-10 text-sm font-bold text-[#0f3d26] focus:border-[#1f7a4a] focus:ring-1 focus:ring-[#1f7a4a] outline-none cursor-pointer opacity-0 absolute inset-0 z-10" style="border: 1px solid #d9e6dd;" required>
                                    <div id="tanggal-display" class="w-full h-11 rounded-xl bg-white px-3.5 pr-10 text-sm font-bold text-[#0f3d26] flex items-center pointer-events-none" style="border: 1px solid #d9e6dd;">
                                        {{ $form->tanggal->translatedFormat('j F Y') }}
                                    </div>
                                    <span class="absolute right-3.5 top-1/2 -translate-y-1/2 material-symbols-outlined text-[18px] pointer-events-none z-20" style="color: #1f7a4a;">calendar_today</span>
                                </div>
                            </div>

                            {{-- Field 2: Pakan Terpakai --}}
                            <div>
                                <div class="flex items-center justify-between mb-1.5 min-h-[20px]">
                                    <label class="block text-xs font-bold text-[#0f3d26]" for="pakan_kg">Pakan Terpakai (kg)</label>
                                </div>
                                <div class="relative">
                                    <input id="pakan_kg" name="pakan_kg" type="number" step="0.1" min="0" class="w-full h-11 rounded-xl bg-white px-3.5 pr-10 text-sm font-bold text-[#0f3d26] placeholder:text-[#889f92] placeholder:font-medium focus:border-[#1f7a4a] focus:ring-1 focus:ring-[#1f7a4a] outline-none" style="border: 1px solid #d9e6dd; color: #0f3d26;" value="{{ old('pakan_kg', $isEdit ? (float) $form->pakan_kg : '') }}" required placeholder="Contoh: 22">
                                    <span class="absolute right-3.5 top-1/2 -translate-y-1/2 text-xs font-semibold text-[#567263] pointer-events-none">kg</span>
                                </div>
                            </div>

                            {{-- Field 3: Jumlah Telur --}}
                            <div class="relative">
                                <div class="flex items-center justify-between mb-1.5 min-h-[20px]">
                                    <label class="block text-xs font-bold text-[#0f3d26]" for="telur_butir">Jumlah Telur (butir)</label>
                                    <button type="button" id="btn-toggle-rak-calc" class="inline-flex items-center gap-1 text-xs font-bold cursor-pointer" style="color: #1f7a4a;">
                                        <span class="material-symbols-outlined text-[16px]">grid_view</span>
                                        <span>Hitung per Rak (30)</span>
                                    </button>
                                </div>
                                <div class="relative">
                                    <input id="telur_butir" name="telur_butir" type="number" min="0" class="w-full h-11 rounded-xl bg-white px-3.5 pr-14 text-sm font-bold text-[#0f3d26] placeholder:text-[#889f92] placeholder:font-medium focus:border-[#1f7a4a] focus:ring-1 focus:ring-[#1f7a4a] outline-none" style="border: 1px solid #d9e6dd; color: #0f3d26;" value="{{ old('telur_butir', $isEdit ? $form->telur_butir : '') }}" required placeholder="Contoh: 180">
                                    <span class="absolute right-3.5 top-1/2 -translate-y-1/2 text-xs font-semibold text-[#567263] pointer-events-none">butir</span>
                                </div>

                                {{-- Popover Mini Kalkulator Rak Telur --}}
                                <div id="popover-calc-rak" class="hidden absolute top-full left-0 right-0 z-30 mt-1.5 p-3.5 rounded-xl bg-white border border-[#1f7a4a]/30 shadow-xl space-y-2.5">
                                    <div class="flex items-center justify-between pb-1.5 border-b border-[#eef4f0] text-xs">
                                        <span class="font-bold text-[#0f3d26] flex items-center gap-1.5">
                                            <span class="material-symbols-outlined text-[#1f7a4a] text-[18px]">calculate</span>
                                            Kalkulator Rak Telur
                                        </span>
                                        <span class="text-[10px] text-[#567263] font-semibold">1 Rak = 30 butir</span>
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

                            {{-- Field 4: Total Berat Telur --}}
                            <div>
                                <div class="flex items-center justify-between mb-1.5 min-h-[20px]">
                                    <label class="block text-xs font-bold text-[#0f3d26]" for="telur_kg">Total Berat Telur (kg)</label>
                                    <button type="button" id="btn-calc-egg-weight" class="text-xs text-[#567263] hover:text-[#1f7a4a] hover:underline font-medium cursor-pointer">
                                        Hitung ~62g/butir
                                    </button>
                                </div>
                                <div class="relative">
                                    <input id="telur_kg" name="telur_kg" type="number" step="0.1" min="0" class="w-full h-11 rounded-xl bg-white px-3.5 pr-10 text-sm font-bold text-[#0f3d26] placeholder:text-[#889f92] placeholder:font-medium focus:border-[#1f7a4a] focus:ring-1 focus:ring-[#1f7a4a] outline-none" style="border: 1px solid #d9e6dd; color: #0f3d26;" value="{{ old('telur_kg', $form->telur_kg) }}" placeholder="Opsional">
                                    <span class="absolute right-3.5 top-1/2 -translate-y-1/2 text-xs font-semibold text-[#567263] pointer-events-none">kg</span>
                                </div>
                            </div>

                            {{-- Field 5: Ayam Mati --}}
                            <div>
                                <div class="flex items-center justify-between mb-1.5 min-h-[20px]">
                                    <label class="block text-xs font-bold text-[#0f3d26]" for="ayam_mati">Ayam Mati (ekor)</label>
                                </div>
                                <div class="relative">
                                    <input id="ayam_mati" name="ayam_mati" type="number" min="0" class="w-full h-11 rounded-xl bg-white px-3.5 pr-12 text-sm font-bold text-[#0f3d26] placeholder:text-[#889f92] placeholder:font-medium focus:border-[#1f7a4a] focus:ring-1 focus:ring-[#1f7a4a] outline-none" style="border: 1px solid #d9e6dd; color: #0f3d26;" value="{{ old('ayam_mati', $isEdit ? $form->ayam_mati : '') }}" placeholder="0">
                                    <span class="absolute right-3.5 top-1/2 -translate-y-1/2 text-xs font-semibold text-[#567263] pointer-events-none">ekor</span>
                                </div>
                            </div>

                            {{-- Field 6: Biaya Tambahan Lainnya (opsional) --}}
                            <div>
                                <div class="flex items-center justify-between mb-1.5 min-h-[20px]">
                                    <label class="block text-xs font-bold text-[#0f3d26]" for="biaya_lain">Biaya Tambahan Lainnya</label>
                                </div>
                                <div class="relative">
                                    <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-xs font-semibold text-[#567263] pointer-events-none">Rp</span>
                                    <input id="biaya_lain" name="biaya_lain" type="number" min="0" step="500" class="w-full h-11 rounded-xl bg-white pl-9 pr-3.5 text-sm font-bold text-[#0f3d26] placeholder:text-[#889f92] placeholder:font-medium focus:border-[#1f7a4a] focus:ring-1 focus:ring-[#1f7a4a] outline-none" style="border: 1px solid #d9e6dd; color: #0f3d26;" value="{{ old('biaya_lain', ($isEdit && $form->biaya_lain > 0) ? $form->biaya_lain : '') }}" placeholder="0">
                                </div>
                            </div>

                            {{-- Catatan Gejala / Kondisi (Desktop only) --}}
                            <div class="hidden md:block">
                                <label class="block text-xs font-bold text-[#0f3d26] mb-1.5" for="catatan">Catatan Kondisi Kandang / Gejala Ayam</label>
                                <textarea id="catatan" name="catatan" rows="2" class="w-full rounded-xl bg-white p-3 text-xs font-medium text-[#0f3d26] focus:border-[#1f7a4a] focus:ring-1 focus:ring-[#1f7a4a] outline-none" style="border: 1px solid #d9e6dd; color: #0f3d26;" placeholder="Contoh: Cuaca panas terik, ayam minum lebih banyak dari biasanya...">{{ old('catatan', $form->catatan) }}</textarea>
                            </div>
                        </div>

                        {{-- Notice Banner (Image 1 style) --}}
                        <div class="p-3 rounded-xl flex items-center gap-3" style="background-color: #eef6f0; border: none;">
                            <span class="material-symbols-outlined text-[20px] shrink-0" style="color: #1f7a4a;">segment</span>
                            <p class="text-xs text-[#567263] leading-snug">
                                Standar pakan <strong class="font-bold text-[#0f3d26]">110–120 g/ekor/hari</strong>. Kalkulasi otomatis.
                            </p>
                        </div>
                    </div>

                    {{-- CTA Button (Image 1 style) --}}
                    <button type="submit" class="w-full py-3.5 px-4 rounded-xl text-white font-bold text-sm shadow-xs transition active:scale-[0.99] flex items-center justify-center cursor-pointer mt-2" style="background-color: #1f7a4a; color: #ffffff; border: none;">
                        <span id="btn-submit-text">{{ $isEdit ? 'Perbarui Catatan' : 'Simpan Catatan' }}</span>
                    </button>
                </form>
            </div>

            {{-- KOLOM KANAN (1/2): Live Cockpit & Pratinjau Performa Harian (Tampil di Mobile & Desktop - Sesuai Gambar 2) --}}
            <div class="flex flex-col">
                <div id="live-preview-box" class="bg-white rounded-lg sm:rounded-xl p-4 sm:p-5 shadow-xs space-y-4 flex-1 flex flex-col justify-between" style="border: none;">
                    <div class="space-y-4">
                        {{-- Header Cockpit (Sesuai Gambar 2) --}}
                        <div class="flex items-center justify-between gap-2">
                            <div class="flex items-center gap-1.5 shrink-0 whitespace-nowrap">
                                <span class="material-symbols-outlined text-[20px]" style="color: #1f7a4a;">trending_up</span>
                                <h3 class="text-xs font-extrabold text-[#0f3d26] uppercase tracking-wider whitespace-nowrap">PRATINJAU PERFORMA</h3>
                            </div>
                            <span class="text-xs text-[#567263] font-medium shrink-0 whitespace-nowrap">Populasi: <strong class="text-[#0f3d26] font-bold">{{ number_format($jumlahAyam, 0, ',', '.') }} ekor</strong></span>
                        </div>

                        {{-- 4 Kotak Metrik 2x2 --}}
                        <div class="grid grid-cols-2 gap-3 text-center">
                            {{-- 1. HEN-DAY (HDP) --}}
                            <div class="p-3 rounded-lg flex flex-col items-center justify-between" style="background-color: #f4f8f5; border: none;">
                                <span class="text-[10px] text-[#567263] uppercase tracking-wider font-bold block mb-1 whitespace-nowrap truncate">HEN-DAY (HDP)</span>
                                <strong id="preview-hdp-val" class="text-2xl sm:text-3xl text-[#0f3d26] block font-black mb-1 whitespace-nowrap">0,0%</strong>
                                <span id="preview-hdp-badge" class="text-[11px] font-medium text-[#567263] whitespace-nowrap">Menunggu data</span>
                            </div>

                            {{-- 2. PAKAN / EKOR --}}
                            <div class="p-3 rounded-lg flex flex-col items-center justify-between" style="background-color: #f4f8f5; border: none;">
                                <span class="text-[10px] text-[#567263] uppercase tracking-wider font-bold block mb-1 whitespace-nowrap truncate">PAKAN / EKOR</span>
                                <strong id="preview-feed-val" class="text-2xl sm:text-3xl text-[#0f3d26] block font-black mb-1 whitespace-nowrap">0 <span class="text-xs font-normal text-[#567263]">gr</span></strong>
                                <span id="preview-feed-badge" class="text-[11px] font-medium text-[#567263] whitespace-nowrap">Standar: 110–120g</span>
                            </div>

                            {{-- 3. FCR INSTAN --}}
                            <div class="p-3 rounded-lg flex flex-col items-center justify-between" style="background-color: #f4f8f5; border: none;">
                                <span class="text-[10px] text-[#567263] uppercase tracking-wider font-bold block mb-1 whitespace-nowrap truncate">FCR INSTAN</span>
                                <strong id="preview-fcr-val" class="text-2xl sm:text-3xl text-[#0f3d26] block font-black mb-1 whitespace-nowrap">0,00</strong>
                                <span id="preview-fcr-badge" class="text-[11px] font-medium text-[#567263] whitespace-nowrap">Efisiensi</span>
                            </div>

                            {{-- 4. ESTIMASI BIAYA PAKAN --}}
                            <div class="p-3 rounded-lg flex flex-col items-center justify-between" style="background-color: #f4f8f5; border: none;">
                                <span class="text-[10px] text-[#567263] uppercase tracking-wider font-bold block mb-1 whitespace-nowrap truncate">ESTIMASI BIAYA PAKAN</span>
                                <strong id="preview-cost-val" class="text-xl sm:text-2xl text-[#0f3d26] block font-black mb-1 whitespace-nowrap">Rp 0</strong>
                                <span id="preview-margin-sub" class="inline-block text-[11px] text-[#567263] font-medium whitespace-nowrap">@ {{ Fmt::rp($harga) }}/kg</span>
                            </div>
                        </div>

                        {{-- Alert Box Dinamis --}}
                        <div id="preview-alert-box" class="hidden p-2.5 rounded-lg text-xs font-semibold flex items-center gap-2">
                            <span id="preview-alert-icon" class="material-symbols-outlined text-[18px]"></span>
                            <span id="preview-alert-text"></span>
                        </div>

                        {{-- Ringkasan Acuan Fase & Standar Bibit --}}
                        <div class="p-3 rounded-lg flex items-center justify-between gap-2 text-xs" style="background-color: #f4f8f5; border: none;">
                            <div class="space-y-0.5 min-w-0">
                                <span class="text-[10px] text-[#567263] uppercase tracking-wider font-bold block shrink-0 whitespace-nowrap">UMUR &amp; BIBIT AYAM</span>
                                <strong class="font-extrabold text-[#0f3d26] text-xs sm:text-sm block truncate">{{ $farm->umurMinggu() }} Minggu · {{ $farm->strain_ayam ?? 'Lohmann Brown' }}</strong>
                            </div>
                            <div class="text-right space-y-0.5 shrink-0 whitespace-nowrap">
                                <span class="text-[10px] text-[#567263] uppercase tracking-wider font-bold block text-right whitespace-nowrap">TARGET PRODUKSI (HDP)</span>
                                <strong class="font-extrabold text-xs sm:text-sm block text-right whitespace-nowrap" style="color: #1f7a4a;">85% – 92%</strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Riwayat Catatan Harian (Image 2 style) --}}
        <div class="bg-white rounded-lg sm:rounded-xl p-4 sm:p-5 shadow-xs space-y-3.5" style="border: none;">
            {{-- Card Header --}}
            <div class="flex items-center gap-3 pb-3 border-b border-[#eef4f0]">
                <div class="w-10 h-10 rounded-lg flex items-center justify-center shrink-0" style="background-color: #dcefe2; color: #1f7a4a;">
                    <span class="material-symbols-outlined text-[22px]">history</span>
                </div>
                <div>
                    <h2 class="text-base font-extrabold text-[#0f3d26] tracking-tight leading-tight">Catatan Harian Terbaru</h2>
                    <p class="text-xs text-[#567263] font-medium">Total {{ $riwayat->total() }} catatan {{ ($filterPeriode !== 'semua' || $cariTanggal) ? '(terfilter)' : '' }}</p>
                </div>
            </div>

            {{-- Filter Pills Track (Pill Badge Style) --}}
            <div class="flex items-center p-1 rounded-full w-full" style="background-color: #e4eee7;">
                <a href="{{ route('catatan.index', ['tab' => 'form', 'periode' => 'semua']) }}"
                   class="flex-1 py-1.5 text-center text-xs font-bold rounded-full transition-all {{ ($filterPeriode === 'semua' && !$cariTanggal) ? 'bg-white text-[#0f3d26] shadow-xs' : 'text-[#567263] hover:text-[#0f3d26]' }}">
                    Semua
                </a>
                <a href="{{ route('catatan.index', ['tab' => 'form', 'periode' => 'bulan_ini']) }}"
                   class="flex-1 py-1.5 text-center text-xs font-bold rounded-full transition-all {{ $filterPeriode === 'bulan_ini' ? 'bg-white text-[#0f3d26] shadow-xs' : 'text-[#567263] hover:text-[#0f3d26]' }}">
                    Bulan Ini
                </a>
                <a href="{{ route('catatan.index', ['tab' => 'form', 'periode' => 'bulan_lalu']) }}"
                   class="flex-1 py-1.5 text-center text-xs font-bold rounded-full transition-all {{ $filterPeriode === 'bulan_lalu' ? 'bg-white text-[#0f3d26] shadow-xs' : 'text-[#567263] hover:text-[#0f3d26]' }}">
                    Bulan Lalu
                </a>
                <a href="{{ route('catatan.index', ['tab' => 'form', 'periode' => '30_hari']) }}"
                   class="flex-1 py-1.5 text-center text-xs font-bold rounded-full transition-all {{ $filterPeriode === '30_hari' ? 'bg-white text-[#0f3d26] shadow-xs' : 'text-[#567263] hover:text-[#0f3d26]' }}">
                    30 Hari
                </a>
            </div>

            {{-- Date Picker Filter Input (Image 2 style) --}}
            <form method="GET" action="{{ route('catatan.index') }}" class="relative">
                <input type="hidden" name="tab" value="form">
                <div class="relative flex items-center cursor-pointer" onclick="const inp = document.getElementById('cari_tanggal'); if (inp) { try { inp.showPicker(); } catch(e) { inp.focus(); } }">
                    <span class="absolute left-3.5 top-1/2 -translate-y-1/2 material-symbols-outlined text-[18px] pointer-events-none z-20" style="color: #1f7a4a;">calendar_today</span>
                    <input type="date" name="cari_tanggal" id="cari_tanggal" value="{{ $cariTanggal }}" max="{{ today()->toDateString() }}"
                           onchange="this.form.submit()"
                           onclick="try { this.showPicker(); } catch(e) {}"
                           class="mobile-date-picker-input w-full h-11 pl-10 pr-10 rounded-lg text-xs font-semibold focus:border-[#1f7a4a] focus:ring-1 focus:ring-[#1f7a4a] outline-none cursor-pointer opacity-0 absolute inset-0 z-10"
                           style="border: 1px solid #e4eee7; background-color: #f8fbf9; color: #0f3d26;">
                    <div class="w-full h-11 pl-10 pr-10 rounded-lg text-xs font-semibold {{ $cariTanggal ? 'text-[#0f3d26]' : 'text-[#567263]' }} flex items-center pointer-events-none"
                          style="border: 1px solid #e4eee7; background-color: #f8fbf9;">
                        {{ $cariTanggal ? \Carbon\Carbon::parse($cariTanggal)->translatedFormat('d M Y') : 'Pilih tanggal' }}
                    </div>
                    @if ($cariTanggal)
                        <a href="{{ route('catatan.index', ['tab' => 'form']) }}" onclick="event.stopPropagation()" class="absolute right-3.5 top-1/2 -translate-y-1/2 text-[#567263] hover:text-rose-600 z-30" title="Reset tanggal">
                            <span class="material-symbols-outlined text-[16px]">close</span>
                        </a>
                    @endif
                </div>
            </form>

            {{-- MOBILE CARDS VIEW (md:hidden) --}}
            <div class="space-y-3 md:hidden pt-1">
                @forelse ($riwayat as $r)
                    @php
                        $itemHdp = $jumlahAyam > 0 ? round(($r->telur_butir / $jumlahAyam) * 100, 1) : 0;
                        $hdpFormatted = str_replace('.', ',', (string) $itemHdp);
                        $itemKgTelur = $r->telurKgEfektif();
                        $itemFcr = $itemKgTelur > 0 ? round($r->pakan_kg / $itemKgTelur, 2) : 0;
                        $fcrFormatted = $itemFcr > 0 ? number_format($itemFcr, 2, ',', '.') : '-';
                    @endphp
                    <a href="{{ route('catatan.index', ['tanggal' => $r->tanggal->toDateString(), 'tab' => 'form']) }}" class="block rounded-lg sm:rounded-xl p-3.5 shadow-2xs hover:shadow-xs transition space-y-2" style="background-color: #f8fbf9; border: none;">
                        <div class="flex items-center justify-between gap-2">
                            <span class="font-extrabold text-sm text-[#0f3d26] shrink-0 whitespace-nowrap">{{ $r->tanggal->translatedFormat('d M Y') }}</span>
                            <span class="text-xs font-bold shrink-0 whitespace-nowrap {{ $itemHdp >= 85 ? 'text-emerald-700' : ($itemHdp >= 75 ? 'text-teal-700' : 'text-amber-700') }}">
                                HDP {{ $hdpFormatted }}%
                            </span>
                        </div>
                        <div class="grid grid-cols-3 gap-2.5 pt-0.5">
                            <div>
                                <span class="text-[11px] text-[#567263] font-medium block">Produksi Telur</span>
                                <div class="flex items-baseline gap-1 mt-0.5">
                                    <strong class="font-black text-sm text-[#0f3d26]">{{ Fmt::num($r->telur_butir, 0) }} btr</strong>
                                    <span class="text-xs text-[#567263] font-semibold">({{ str_replace('.', ',', (string) round($itemKgTelur, 1)) }} kg)</span>
                                </div>
                            </div>
                            <div>
                                <span class="text-[11px] text-[#567263] font-medium block">Pakan Terpakai</span>
                                <div class="mt-0.5">
                                    <strong class="font-black text-sm text-[#0f3d26]">{{ str_replace('.', ',', (string) round($r->pakan_kg, 1)) }} kg</strong>
                                </div>
                            </div>
                            <div>
                                <span class="text-[11px] text-[#567263] font-medium block">FCR</span>
                                <div class="mt-0.5">
                                    <strong class="font-black text-sm text-[#0f3d26]">{{ $fcrFormatted }}</strong>
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
                            <span class="w-11 h-11 rounded-xl bg-white opacity-35 flex items-center justify-center cursor-not-allowed shadow-2xs" style="border: 1px solid #d9e6dd; color: #0f3d26;">
                                <span class="material-symbols-outlined text-[20px]">chevron_left</span>
                            </span>
                        @else
                            <a href="{{ $riwayat->previousPageUrl() }}" class="w-11 h-11 rounded-xl bg-white flex items-center justify-center hover:bg-[#f4f8f5] transition shadow-2xs cursor-pointer" style="border: 1px solid #d9e6dd; color: #0f3d26;">
                                <span class="material-symbols-outlined text-[20px]">chevron_left</span>
                            </a>
                        @endif

                        {{-- Angka Halaman --}}
                        @foreach ($riwayat->getUrlRange($startPage, $endPage) as $page => $url)
                            @if ($page == $currPage)
                                <span class="w-11 h-11 rounded-xl font-bold text-sm flex items-center justify-center shadow-xs" style="background-color: #1f7a4a; color: #ffffff; border: none;">{{ $page }}</span>
                            @else
                                <a href="{{ $url }}" class="w-11 h-11 rounded-xl bg-white font-bold text-sm flex items-center justify-center hover:bg-[#f4f8f5] transition shadow-2xs cursor-pointer" style="border: 1px solid #d9e6dd; color: #0f3d26;">{{ $page }}</a>
                            @endif
                        @endforeach

                        {{-- Tombol Selanjutnya (>) --}}
                        @if ($riwayat->hasMorePages())
                            <a href="{{ $riwayat->nextPageUrl() }}" class="w-11 h-11 rounded-xl bg-white flex items-center justify-center hover:bg-[#f4f8f5] transition shadow-2xs cursor-pointer" style="border: 1px solid #d9e6dd; color: #0f3d26;">
                                <span class="material-symbols-outlined text-[20px]">chevron_right</span>
                            </a>
                        @else
                            <span class="w-11 h-11 rounded-xl bg-white opacity-35 flex items-center justify-center cursor-not-allowed shadow-2xs" style="border: 1px solid #d9e6dd; color: #0f3d26;">
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
        </div>

{{-- ======================================================== --}}
{{-- B. TAMPILAN DESKTOP CATAT HARIAN (hidden md:block)        --}}
{{-- 100% Mengembalikan UI Asli Desktop dari commit 143dc55~1  --}}
{{-- ======================================================== --}}
<div class="hidden md:block space-y-5" id="catat-desktop-container">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-5 items-stretch">
            {{-- KOLOM KIRI (1/2): Formulir Input Catatan Harian --}}
            <div class="flex flex-col">
                <form method="POST" action="{{ route('catatan.store') }}" id="form-catat-desktop" class="card space-y-4 flex-1 flex flex-col justify-between">
                    @csrf
                    <div class="space-y-4">
                        {{-- Header Form Bersih & Tegas --}}
                        <div class="flex items-center justify-between pb-2.5 border-b border-line">
                            <div class="flex items-center gap-2">
                                <span class="material-symbols-outlined text-[20px] text-brand">edit_note</span>
                                <h2 class="text-xs font-bold text-ink uppercase tracking-wider">Catat Data Hari Ini</h2>
                            </div>
                            <span class="text-tiny font-medium text-muted">Kandang: <strong class="text-ink">{{ $farm->nama }}</strong></span>
                        </div>

                        {{-- Banner Mode Edit (Muncul jika tanggal yang dipilih sudah ada catatan) --}}
                        <div id="desk-mode-status-banner" class="{{ $isEdit ? '' : 'hidden' }} py-2 px-3 rounded-lg bg-amber-50 border border-amber-300 text-xs text-amber-950 flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <span class="material-symbols-outlined text-[18px] text-amber-700">edit_calendar</span>
                                <span><strong>Mode Edit:</strong> Catatan tanggal <span id="desk-mode-status-date" class="font-bold underline">{{ $form->tanggal ? $form->tanggal->translatedFormat('d M Y') : now()->translatedFormat('d M Y') }}</span> sudah ada. Mengisi form ini akan memperbarui data.</span>
                            </div>
                            <a href="{{ route('catatan.index', ['tab' => 'form']) }}" class="text-[11px] font-bold text-amber-900 hover:text-amber-700 underline">Reset Hari Ini</a>
                        </div>

                        {{-- Quick Fill dari Catatan Kemarin --}}
                        @if ($yesterdayLog)
                        <div class="flex items-center justify-between gap-2 bg-surface/60 p-2.5 rounded-xl border border-line/70">
                            <div class="text-xs text-muted truncate">
                                <span class="font-bold text-ink">Catatan Kemarin ({{ $yesterdayLog->tanggal->translatedFormat('d M') }}):</span>
                                {{ Fmt::num($yesterdayLog->pakan_kg, 1) }} kg · {{ Fmt::num($yesterdayLog->telur_butir, 0) }} btr
                            </div>
                            <button type="button" id="desk-btn-quick-fill"
                                    data-pakan="{{ (float) $yesterdayLog->pakan_kg }}"
                                    data-telur="{{ (int) $yesterdayLog->telur_butir }}"
                                    data-berat="{{ $yesterdayLog->telur_kg !== null ? (float) $yesterdayLog->telur_kg : '' }}"
                                    class="px-2.5 py-1 rounded-lg text-xs font-bold text-brand hover:text-brand-dark bg-white hover:bg-surface border border-line transition shadow-2xs flex items-center gap-1 cursor-pointer shrink-0">
                                <span class="material-symbols-outlined text-[15px] text-amber-500 icon-fill">bolt</span>
                                Salin Angka Kemarin
                            </button>
                        </div>
                        @endif

                        {{-- Grid Input 2 Kolom Seimbang & Sejajar --}}
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                            {{-- Baris 1 Kiri: Tanggal Pencatatan --}}
                            <div>
                                <div class="flex items-center justify-between mb-1.5 min-h-[20px]">
                                    <label class="lbl text-xs font-bold text-ink !mb-0" for="desk_tanggal">Tanggal Pencatatan</label>
                                </div>
                                <input id="desk_tanggal" name="tanggal" type="date" value="{{ old('tanggal', $form->tanggal->toDateString()) }}" max="{{ today()->toDateString() }}" class="field h-11 text-sm font-bold text-ink" required>
                            </div>

                            {{-- Baris 1 Kanan: Pakan Terpakai --}}
                            <div>
                                <div class="flex items-center justify-between mb-1.5 min-h-[20px]">
                                    <label class="lbl text-xs font-bold text-ink !mb-0" for="desk_pakan_kg">Pakan Terpakai (kg)</label>
                                </div>
                                <div class="relative">
                                    <input id="desk_pakan_kg" name="pakan_kg" type="number" step="0.1" min="0" class="field h-11 text-sm font-bold text-ink placeholder:text-muted/60 placeholder:font-medium pr-10" value="{{ old('pakan_kg', $isEdit ? (float) $form->pakan_kg : '') }}" required placeholder="Contoh: 22">
                                    <span class="absolute right-3.5 top-1/2 -translate-y-1/2 text-xs font-semibold text-muted pointer-events-none">kg</span>
                                </div>
                            </div>

                            {{-- Baris 2 Kiri: Jumlah Telur --}}
                            <div class="relative">
                                <div class="flex items-center justify-between mb-1.5 min-h-[20px]">
                                    <label class="lbl text-xs font-bold text-ink !mb-0" for="desk_telur_butir">Jumlah Telur (butir)</label>
                                    <button type="button" id="desk-btn-toggle-rak-calc" class="text-[11px] text-brand hover:text-brand-dark hover:underline font-bold cursor-pointer inline-flex items-center gap-0.5" title="Bantu hitung dari jumlah rak/tray telur (1 rak = 30 butir)">
                                        <span class="material-symbols-outlined text-[13px]">grid_view</span>
                                        <span>Hitung per Rak (30)</span>
                                    </button>
                                </div>
                                <div class="relative">
                                    <input id="desk_telur_butir" name="telur_butir" type="number" min="0" class="field h-11 text-sm font-bold text-ink placeholder:text-muted/60 placeholder:font-medium pr-14" value="{{ old('telur_butir', $isEdit ? $form->telur_butir : '') }}" required placeholder="Contoh: 180">
                                    <span class="absolute right-3.5 top-1/2 -translate-y-1/2 text-xs font-semibold text-muted pointer-events-none">butir</span>
                                </div>

                                {{-- Popover Mini Kalkulator Rak Telur --}}
                                <div id="desk-popover-calc-rak" class="hidden absolute top-full left-0 right-0 z-30 mt-1.5 p-3 rounded-xl bg-white border border-brand/30 shadow-xl space-y-2.5">
                                    <div class="flex items-center justify-between pb-1.5 border-b border-line text-xs">
                                        <span class="font-bold text-ink flex items-center gap-1">
                                            <span class="material-symbols-outlined text-brand text-[16px]">calculate</span>
                                            Kalkulator Rak Telur
                                        </span>
                                        <span class="text-[10px] text-muted font-medium">1 Rak = 30 butir</span>
                                    </div>
                                    <div class="grid grid-cols-2 gap-2 text-xs">
                                        <div>
                                            <label class="block text-[10.5px] text-muted font-semibold mb-0.5" for="desk-calc-rak-input">Jumlah Rak</label>
                                            <div class="relative">
                                                <input type="number" id="desk-calc-rak-input" min="0" step="1" placeholder="0" class="w-full h-8 px-2.5 pr-8 rounded-lg bg-surface border border-line text-xs font-bold text-ink focus:border-brand focus:ring-1 focus:ring-brand/20 outline-none">
                                                <span class="absolute right-2 top-1/2 -translate-y-1/2 text-[10px] text-muted font-medium pointer-events-none">rak</span>
                                            </div>
                                        </div>
                                        <div>
                                            <label class="block text-[10.5px] text-muted font-semibold mb-0.5" for="desk-calc-sisa-input">Sisa Butir</label>
                                            <div class="relative">
                                                <input type="number" id="desk-calc-sisa-input" min="0" step="1" placeholder="0" class="w-full h-8 px-2.5 pr-8 rounded-lg bg-surface border border-line text-xs font-bold text-ink focus:border-brand focus:ring-1 focus:ring-brand/20 outline-none">
                                                <span class="absolute right-2 top-1/2 -translate-y-1/2 text-[10px] text-muted font-medium pointer-events-none">btr</span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="p-2 rounded-lg bg-emerald-50/70 border border-emerald-200/60 flex items-center justify-between text-xs">
                                        <span class="text-emerald-900 text-[11px] font-medium">Hasil Total:</span>
                                        <strong id="desk-calc-rak-total-preview" class="text-sm font-black text-emerald-800">0 butir</strong>
                                    </div>

                                    <div class="flex items-center justify-end gap-1.5 pt-0.5">
                                        <button type="button" id="desk-btn-cancel-rak-calc" class="px-2.5 py-1 rounded-lg text-xs font-semibold text-muted hover:text-ink hover:bg-surface transition cursor-pointer">
                                            Batal
                                        </button>
                                        <button type="button" id="desk-btn-apply-rak-calc" class="px-3 py-1 rounded-lg bg-brand hover:bg-brand-dark text-white text-xs font-bold shadow-2xs transition active:scale-95 cursor-pointer">
                                            Terapkan ke Form
                                        </button>
                                    </div>
                                </div>
                            </div>

                            {{-- Baris 2 Kanan: Berat Telur (Opsional) --}}
                            <div>
                                <div class="flex items-center justify-between mb-1.5 min-h-[20px]">
                                    <label class="lbl text-xs font-bold text-ink !mb-0" for="desk_telur_kg">Total Berat Telur (kg)</label>
                                    <button type="button" id="desk-btn-calc-egg-weight" class="text-[11px] text-muted hover:text-brand hover:underline font-medium cursor-pointer" title="Hitung estimasi bobot otomatis dari butir x 62g">
                                        Hitung ~62g/butir
                                    </button>
                                </div>
                                <div class="relative">
                                    <input id="desk_telur_kg" name="telur_kg" type="number" step="0.1" min="0" class="field h-11 text-sm font-bold text-ink placeholder:text-muted/60 placeholder:font-medium pr-10" value="{{ old('telur_kg', $form->telur_kg) }}" placeholder="Opsional">
                                    <span class="absolute right-3.5 top-1/2 -translate-y-1/2 text-xs font-semibold text-muted pointer-events-none">kg</span>
                                </div>
                            </div>

                            {{-- Baris 3 Kiri: Kematian Ayam --}}
                            <div>
                                <div class="flex items-center justify-between mb-1.5 min-h-[20px]">
                                    <label class="lbl text-xs font-bold text-ink !mb-0" for="desk_ayam_mati">Ayam Mati (ekor)</label>
                                </div>
                                <div class="relative">
                                    <input id="desk_ayam_mati" name="ayam_mati" type="number" min="0" class="field h-11 text-sm font-bold text-ink placeholder:text-muted/60 placeholder:font-medium pr-12" value="{{ old('ayam_mati', $isEdit ? $form->ayam_mati : '') }}" placeholder="0">
                                    <span class="absolute right-3.5 top-1/2 -translate-y-1/2 text-xs font-semibold text-muted pointer-events-none">ekor</span>
                                </div>
                            </div>

                            {{-- Baris 3 Kanan: Biaya Lain-lain (Opsional) --}}
                            <div>
                                <div class="flex items-center justify-between mb-1.5 min-h-[20px]">
                                    <label class="lbl text-xs font-bold text-ink !mb-0" for="desk_biaya_lain">Biaya Tambahan Lainnya</label>
                                </div>
                                <div class="relative">
                                    <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-xs font-semibold text-muted pointer-events-none">Rp</span>
                                    <input id="desk_biaya_lain" name="biaya_lain" type="number" min="0" step="500" class="field h-11 text-sm font-bold text-ink placeholder:text-muted/60 placeholder:font-medium pl-9" value="{{ old('biaya_lain', ($isEdit && $form->biaya_lain > 0) ? $form->biaya_lain : '') }}" placeholder="0">
                                </div>
                            </div>
                        </div>

                        {{-- Catatan Gejala / Kondisi --}}
                        <div>
                            <label class="lbl text-xs font-bold text-ink" for="desk_catatan">Catatan Kondisi Kandang / Gejala Ayam</label>
                            <textarea id="desk_catatan" name="catatan" rows="2" class="field !min-h-[52px] py-2 text-xs" placeholder="Contoh: Cuaca panas terik, ayam minum lebih banyak dari biasanya...">{{ old('catatan', $form->catatan) }}</textarea>
                        </div>
                    </div>

                    <button class="w-full inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-full bg-brand hover:bg-brand-dark text-white text-xs font-bold shadow-md hover:shadow-brand/20 transition active:scale-95 cursor-pointer mt-2">
                        <span class="material-symbols-outlined text-[18px]">save</span>
                        <span id="desk-btn-submit-text">{{ $isEdit ? 'Perbarui Catatan Harian' : 'Simpan Catatan Harian' }}</span>
                    </button>
                </form>
            </div>

            {{-- KOLOM KANAN (1/2): Live Cockpit & Pratinjau Performa Harian (Non-Sticky, Seimbang dengan Kiri) --}}
            <div class="flex flex-col">
                <div id="desk-live-preview-box" class="card space-y-4 bg-white border border-line shadow-2xs flex-1 flex flex-col justify-between">
                    <div class="space-y-4">
                        {{-- Header Cockpit --}}
                        <div class="flex items-center justify-between pb-2.5 border-b border-line">
                            <div class="flex items-center gap-1.5">
                                <span class="material-symbols-outlined text-brand text-[18px]">query_stats</span>
                                <h3 class="text-xs font-bold text-ink uppercase tracking-wider">Pratinjau Performa Harian</h3>
                            </div>
                            <span class="text-tiny font-semibold text-muted">Populasi: <strong class="text-ink">{{ number_format($jumlahAyam, 0, ',', '.') }} ekor</strong></span>
                        </div>

                        {{-- 4 Kotak Metrik 2x2 Bersih Tanpa Pill Badge --}}
                        <div class="grid grid-cols-2 gap-3 text-center">
                            {{-- 1. HDP --}}
                            <div class="p-3.5 rounded-xl bg-surface/60 border border-line/70 shadow-2xs flex flex-col justify-between">
                                <span class="text-[10px] text-muted uppercase tracking-wider font-semibold block mb-0.5">Hen-Day (HDP)</span>
                                <strong id="desk-preview-hdp-val" class="text-2xl sm:text-3xl text-ink block font-black">0,0%</strong>
                                <span id="desk-preview-hdp-badge" class="inline-block mt-1 text-[11px] font-medium text-muted">Menunggu data</span>
                            </div>

                            {{-- 2. Pakan / Ekor --}}
                            <div class="p-3.5 rounded-xl bg-surface/60 border border-line/70 shadow-2xs flex flex-col justify-between">
                                <span class="text-[10px] text-muted uppercase tracking-wider font-semibold block mb-0.5">Pakan / Ekor</span>
                                <strong id="desk-preview-feed-val" class="text-2xl sm:text-3xl text-ink block font-black">0 <span class="text-sm font-normal">gr</span></strong>
                                <span id="desk-preview-feed-badge" class="inline-block mt-1 text-[11px] font-medium text-muted">Standar: 110–120g</span>
                            </div>

                            {{-- 3. FCR Instan --}}
                            <div class="p-3.5 rounded-xl bg-surface/60 border border-line/70 shadow-2xs flex flex-col justify-between">
                                <span class="text-[10px] text-muted uppercase tracking-wider font-semibold block mb-0.5">FCR Instan</span>
                                <strong id="desk-preview-fcr-val" class="text-2xl sm:text-3xl text-ink block font-black">0,00</strong>
                                <span id="desk-preview-fcr-badge" class="inline-block mt-1 text-[11px] font-medium text-muted">Efisiensi</span>
                            </div>

                            {{-- 4. Estimasi Biaya Pakan --}}
                            <div class="p-3.5 rounded-xl bg-surface/60 border border-line/70 shadow-2xs flex flex-col justify-between">
                                <span class="text-[10px] text-muted uppercase tracking-wider font-semibold block mb-0.5">Estimasi Biaya Pakan</span>
                                <strong id="desk-preview-cost-val" class="text-xl sm:text-2xl text-ink block font-black">Rp 0</strong>
                                <span id="desk-preview-margin-sub" class="inline-block mt-1 text-[11px] text-muted font-medium">@ {{ Fmt::rp($harga) }}/kg</span>
                            </div>
                        </div>

                        {{-- Alert Box Dinamis --}}
                        <div id="desk-preview-alert-box" class="hidden p-2.5 rounded-xl text-xs font-semibold flex items-center gap-2">
                            <span id="desk-preview-alert-icon" class="material-symbols-outlined text-[18px]"></span>
                            <span id="desk-preview-alert-text"></span>
                        </div>

                        {{-- Ringkasan Acuan Umur & Standar Bibit --}}
                        <div class="p-3.5 rounded-xl bg-surface/50 border border-line/70 flex items-center justify-between text-xs">
                            <div class="space-y-0.5">
                                <span class="text-[10px] text-muted uppercase tracking-wider font-semibold block">Umur &amp; Bibit Ayam</span>
                                <span class="font-bold text-ink">{{ $farm->umurMinggu() }} Minggu · {{ $farm->strain_ayam ?? 'Lohmann Brown' }}</span>
                            </div>
                            <div class="text-right space-y-0.5">
                                <span class="text-[10px] text-muted uppercase tracking-wider font-semibold block">Target Produksi (HDP)</span>
                                <span class="font-bold text-emerald-700">85% – 92%</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Riwayat Catatan Harian (5 per halaman + Filter Cepat & Pencarian) --}}
        <div class="card space-y-3.5">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-3 pb-3 border-b border-line">
                <div class="flex items-center gap-2">
                    <span class="material-symbols-outlined text-[19px] text-brand">history</span>
                    <div>
                        <h2 class="text-xs font-bold text-ink uppercase tracking-wider">Catatan Harian Terbaru</h2>
                        <span class="text-tiny font-medium text-muted">
                            Total {{ $riwayat->total() }} catatan {{ ($filterPeriode !== 'semua' || $cariTanggal) ? '(terfilter)' : '' }}
                        </span>
                    </div>
                </div>

                {{-- Kontrol Filter Periode & Pencarian Tanggal --}}
                <form method="GET" action="{{ route('catatan.index') }}" class="flex flex-wrap items-center gap-2">
                    <input type="hidden" name="tab" value="form">

                    {{-- Cari Tanggal Spesifik (Diletakkan di sisi kiri filter agar popup kalender terbuka ke arah dalam layar dan tidak terpotong di tepi kanan) --}}
                    <div class="flex items-center gap-1">
                        <input type="date" name="cari_tanggal" id="desk_cari_tanggal" value="{{ $cariTanggal }}" max="{{ today()->toDateString() }}"
                               onchange="this.form.submit()"
                               onclick="try { this.showPicker(); } catch(e) {}"
                               class="h-7 px-2.5 rounded-lg bg-white border border-line text-xs font-semibold text-ink focus:border-brand focus:ring-1 focus:ring-brand/20 shadow-2xs cursor-pointer"
                               title="Pilih tanggal spesifik untuk memfilter">

                        @if ($cariTanggal || $filterPeriode !== 'semua')
                            <a href="{{ route('catatan.index', ['tab' => 'form']) }}" class="h-7 px-2 rounded-lg bg-surface hover:bg-surface-2 border border-line text-muted hover:text-rose-600 flex items-center justify-center transition shadow-2xs" title="Reset filter (Tampilkan semua)">
                                <span class="material-symbols-outlined text-[15px]">close</span>
                            </a>
                        @endif
                    </div>

                    {{-- Quick Pill Filter: Semua, Bulan Ini, Bulan Lalu, 30 Hari --}}
                    <div class="inline-flex p-0.5 bg-surface rounded-lg border border-line text-[11px] font-bold select-none shadow-2xs">
                        <a href="{{ route('catatan.index', ['tab' => 'form', 'periode' => 'semua']) }}"
                           class="px-2.5 py-1 rounded-md transition-all {{ ($filterPeriode === 'semua' && !$cariTanggal) ? 'bg-white text-ink shadow-2xs font-extrabold' : 'text-muted hover:text-ink' }}">
                            Semua
                        </a>
                        <a href="{{ route('catatan.index', ['tab' => 'form', 'periode' => 'bulan_ini']) }}"
                           class="px-2.5 py-1 rounded-md transition-all {{ $filterPeriode === 'bulan_ini' ? 'bg-white text-ink shadow-2xs font-extrabold' : 'text-muted hover:text-ink' }}">
                            Bulan Ini
                        </a>
                        <a href="{{ route('catatan.index', ['tab' => 'form', 'periode' => 'bulan_lalu']) }}"
                           class="px-2.5 py-1 rounded-md transition-all {{ $filterPeriode === 'bulan_lalu' ? 'bg-white text-ink shadow-2xs font-extrabold' : 'text-muted hover:text-ink' }}">
                            Bulan Lalu
                        </a>
                        <a href="{{ route('catatan.index', ['tab' => 'form', 'periode' => '30_hari']) }}"
                           class="px-2.5 py-1 rounded-md transition-all {{ $filterPeriode === '30_hari' ? 'bg-white text-ink shadow-2xs font-extrabold' : 'text-muted hover:text-ink' }}">
                            30 Hari
                        </a>
                    </div>
                </form>
            </div>

            <div class="overflow-x-auto -mx-4 sm:-mx-5 px-4 sm:px-5">
                <table class="w-full text-xs text-left border-collapse">
                    <thead>
                        <tr class="border-b border-line text-[11px] font-bold text-muted uppercase tracking-wider">
                            <th class="py-2.5 pr-3">Tanggal</th>
                            <th class="py-2.5 px-3">Produksi Telur</th>
                            <th class="py-2.5 px-3">Pakan Terpakai</th>
                            <th class="py-2.5 px-3 text-center">HDP</th>
                            <th class="py-2.5 px-3 text-center">Pakan/Ekor</th>
                            <th class="py-2.5 px-3 text-center">FCR</th>
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
                                $itemKgTelur = $r->telurKgEfektif();
                                $itemFcr = $itemKgTelur > 0 ? round($r->pakan_kg / $itemKgTelur, 2) : 0;
                            @endphp
                            <tr class="hover:bg-surface/50 transition-colors group">
                                {{-- Tanggal --}}
                                <td class="py-3 pr-3 font-bold text-ink whitespace-nowrap">
                                    {{ $r->tanggal->translatedFormat('d M Y') }}
                                </td>

                                {{-- Produksi Telur --}}
                                <td class="py-3 px-3 whitespace-nowrap">
                                    <span class="font-bold text-ink">{{ Fmt::num($r->telur_butir, 0) }} btr</span>
                                    <span class="text-muted text-[11px] ml-1">({{ Fmt::num($itemKgTelur, 1) }} kg)</span>
                                </td>

                                {{-- Pakan Terpakai --}}
                                <td class="py-3 px-3 whitespace-nowrap font-medium text-ink">
                                    {{ Fmt::num($r->pakan_kg, 1) }} kg
                                </td>

                                {{-- HDP --}}
                                <td class="py-3 px-3 text-center whitespace-nowrap">
                                    <span class="font-bold {{ $itemHdp >= 85 ? 'text-emerald-700' : ($itemHdp >= 75 ? 'text-teal-700' : 'text-amber-700') }}">
                                        {{ str_replace('.', ',', (string) $itemHdp) }}%
                                    </span>
                                </td>

                                {{-- Pakan/Ekor --}}
                                <td class="py-3 px-3 text-center whitespace-nowrap text-muted font-medium">
                                    {{ str_replace('.', ',', (string) $itemGr) }} g
                                </td>

                                {{-- FCR --}}
                                <td class="py-3 px-3 text-center whitespace-nowrap font-medium {{ $itemFcr > 0 && $itemFcr <= 2.15 ? 'text-emerald-700 font-bold' : ($itemFcr > 2.45 ? 'text-rose-600 font-bold' : 'text-ink') }}">
                                    {{ $itemFcr > 0 ? number_format($itemFcr, 2, ',', '.') : '-' }}
                                </td>

                                {{-- Mortalitas (Tanpa ikon x, jika 0 tampilkan tanda -) --}}
                                <td class="py-3 px-3 text-center whitespace-nowrap">
                                    @if ($r->ayam_mati > 0)
                                        <span class="font-bold text-rose-600">
                                            {{ $r->ayam_mati }} ekor
                                        </span>
                                    @else
                                        <span class="text-muted/40 font-mono">-</span>
                                    @endif
                                </td>

                                {{-- Catatan --}}
                                <td class="py-3 px-3 max-w-[180px] truncate text-muted text-[11px] italic" title="{{ $r->catatan }}">
                                    {{ $r->catatan ? Str::limit($r->catatan, 30) : '-' }}
                                </td>

                                {{-- Aksi --}}
                                <td class="py-3 pl-3 text-right whitespace-nowrap">
                                    <div class="inline-flex items-center gap-1.5">
                                        <a href="{{ route('catatan.index', ['tanggal' => $r->tanggal->toDateString(), 'tab' => 'form']) }}" class="px-2.5 py-1 rounded-md text-xs font-bold text-brand hover:text-brand-dark hover:bg-surface border border-line transition cursor-pointer" title="Edit catatan">
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
                                <td colspan="9" class="py-8 text-center text-muted">
                                    <div class="flex flex-col items-center justify-center gap-1.5">
                                        <span class="material-symbols-outlined text-[26px] text-muted/40">search_off</span>
                                        <p class="text-xs font-semibold text-ink">Tidak ada catatan pada filter yang dipilih</p>
                                        <p class="text-[11px] text-muted">Coba ubah filter periode atau tanggal pencarian Anda.</p>
                                        @if ($cariTanggal || $filterPeriode !== 'semua')
                                            <a href="{{ route('catatan.index', ['tab' => 'form']) }}" class="mt-1 text-xs font-bold text-brand hover:underline inline-flex items-center gap-1">
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

            {{-- Navigasi Pagination Elegan --}}
            @if ($riwayat->hasPages())
                <div class="flex flex-wrap items-center justify-between gap-2 pt-3 border-t border-line text-xs">
                    <span class="text-muted">
                        Menampilkan <strong class="text-ink font-semibold">{{ $riwayat->firstItem() }}</strong> – <strong class="text-ink font-semibold">{{ $riwayat->lastItem() }}</strong> dari <strong class="text-ink font-semibold">{{ $riwayat->total() }}</strong> catatan
                    </span>
                    <div class="flex items-center gap-1">
                        @if ($riwayat->onFirstPage())
                            <span class="px-2.5 py-1 rounded-lg border border-line/40 text-muted/40 cursor-not-allowed text-xs">« Prev</span>
                        @else
                            <a href="{{ $riwayat->previousPageUrl() }}" class="px-2.5 py-1 rounded-lg border border-line bg-white hover:bg-surface text-ink font-bold transition shadow-2xs text-xs cursor-pointer">« Prev</a>
                        @endif

                        @foreach ($riwayat->getUrlRange(max(1, $riwayat->currentPage() - 2), min($riwayat->lastPage(), $riwayat->currentPage() + 2)) as $page => $url)
                            @if ($page == $riwayat->currentPage())
                                <span class="w-7 h-7 flex items-center justify-center rounded-lg bg-brand text-white font-bold text-xs shadow-2xs">{{ $page }}</span>
                            @else
                                <a href="{{ $url }}" class="w-7 h-7 flex items-center justify-center rounded-lg border border-line bg-white hover:bg-surface text-ink font-semibold transition text-xs cursor-pointer">{{ $page }}</a>
                            @endif
                        @endforeach

                        @if ($riwayat->hasMorePages())
                            <a href="{{ $riwayat->nextPageUrl() }}" class="px-2.5 py-1 rounded-lg border border-line bg-white hover:bg-surface text-ink font-bold transition shadow-2xs text-xs cursor-pointer">Next »</a>
                        @else
                            <span class="px-2.5 py-1 rounded-lg border border-line/40 text-muted/40 cursor-not-allowed text-xs">Next »</span>
                        @endif
                    </div>
                </div>
            @endif
        </div>



</div>
    </section>

    {{-- Panel 2: Pembelian Bahan Pakan --}}
    @php
        $jumlahTransaksiBeli = count($beli);
        $farmIngredientsMap = $farm->farmIngredients()->get()->keyBy('ingredient_id');
        $bahanPrices = $bahan->mapWithKeys(function ($b) use ($farmIngredientsMap) {
            $est = (int) ($farmIngredientsMap->get($b->id)?->harga ?? $b->harga_default);
            return [$b->id => $est];
        });
        $bahanStocks = $bahan->mapWithKeys(function ($b) use ($farmIngredientsMap) {
            $stok = (float) ($farmIngredientsMap->get($b->id)?->stok_kg ?? 0);
            return [$b->id => $stok];
        });
    @endphp
    <section data-panel="beli" class="{{ $tabAktif === 'beli' ? '' : 'hidden' }} space-y-4">
        {{-- ======================================================== --}}
        {{-- A. TAMPILAN MOBILE BELANJA PAKAN (md:hidden)              --}}
        {{-- ======================================================== --}}
        <div class="md:hidden space-y-4">
            {{-- 1. Kartu Catat Pembelian Bahan --}}
            <div class="bg-white rounded-lg sm:rounded-xl p-4 sm:p-5 border border-line/70 shadow-2xs space-y-4">
                {{-- Header Card --}}
                <div class="flex items-center gap-3 pb-3 border-b border-line/60">
                    <div class="w-10 h-10 rounded-xl bg-[#e2f0e8] text-[#116a3a] flex items-center justify-center shrink-0">
                        <span class="material-symbols-outlined text-[20px]">shopping_cart</span>
                    </div>
                    <div>
                        <h2 class="text-base font-black text-ink">Catat Pembelian Bahan</h2>
                        <p class="text-xs text-muted">{{ $farm->nama }}</p>
                    </div>
                </div>

                <form method="POST" action="{{ route('pembelian.store') }}" class="space-y-4">
                    @csrf
                    <input type="hidden" name="tambah_stok" value="1">
                    <input type="hidden" name="ingredient_id" id="beli_ingredient_id" value="{{ $bahan->first()?->id }}">

                    {{-- Pilih Bahan Pakan (Chips Selector) --}}
                    <div class="space-y-2">
                        <label class="text-xs font-bold text-ink block">Pilih Bahan Pakan</label>
                        <div class="flex flex-wrap items-center gap-2" id="chips-bahan-container">
                            @foreach ($bahan as $idx => $b)
                                @php
                                    $estHarga = $bahanPrices[$b->id] ?? $b->harga_default;
                                @endphp
                                <button type="button"
                                        data-bahan-id="{{ $b->id }}"
                                        data-bahan-nama="{{ $b->nama }}"
                                        data-bahan-acuan="{{ $estHarga }}"
                                        class="chip-bahan px-4 py-1.5 rounded-full text-xs font-bold transition-all cursor-pointer select-none {{ $idx === 0 ? 'bg-[#e2f0e8] text-[#116a3a] border-2 border-[#116a3a]' : 'bg-white text-ink border border-line hover:border-line-dark' }}">
                                    {{ $b->nama }}
                                </button>
                            @endforeach
                        </div>
                        @php
                            $firstBahan = $bahan->first();
                            $firstAcuan = $firstBahan ? ($bahanPrices[$firstBahan->id] ?? $firstBahan->harga_default) : 6500;
                            $firstStok = $firstBahan ? ($bahanStocks[$firstBahan->id] ?? 0) : 0;
                        @endphp
                        <p id="beli-acuan-text" class="text-xs text-muted font-medium pt-0.5">
                            Stok gudang {{ $firstBahan?->nama ?? 'Bahan' }}: {{ Fmt::num($firstStok, 1) }} kg · Harga terakhir Rp {{ number_format($firstAcuan, 0, ',', '.') }}/kg
                        </p>
                    </div>

                    {{-- Tanggal Pembelian --}}
                    <div class="space-y-1.5">
                        <label class="text-xs font-bold text-ink block" for="beli_tanggal">Tanggal Pembelian</label>
                        <div class="relative cursor-pointer" onclick="const inp = document.getElementById('beli_tanggal'); if (inp) { try { inp.showPicker(); } catch(e) { inp.focus(); } }">
                            <input id="beli_tanggal" name="tanggal" type="date" value="{{ today()->toDateString() }}" max="{{ today()->toDateString() }}"
                                   onclick="try { this.showPicker(); } catch(e) {}"
                                   class="mobile-date-picker-input w-full h-11 rounded-xl bg-white px-4 pr-11 text-sm font-semibold text-ink focus:border-[#116a3a] focus:ring-1 focus:ring-[#116a3a] outline-none cursor-pointer opacity-0 absolute inset-0 z-10" required>
                            <div id="beli-tanggal-display" class="w-full h-11 rounded-xl bg-white px-4 pr-11 text-sm font-semibold text-ink flex items-center pointer-events-none border border-line">
                                {{ today()->translatedFormat('j F Y') }}
                            </div>
                            <span class="absolute right-4 top-1/2 -translate-y-1/2 material-symbols-outlined text-[18px] text-[#116a3a] pointer-events-none z-20">calendar_today</span>
                        </div>
                    </div>

                    {{-- Jumlah Beli (kg) --}}
                    <div class="space-y-1.5">
                        <label class="text-xs font-bold text-ink block" for="kg">Jumlah Beli</label>
                        <div class="relative">
                            <input id="kg" name="kg" type="number" step="0.5" min="0.5" class="w-full rounded-xl border border-line bg-white px-4 py-2.5 pr-12 text-sm font-bold text-ink focus:border-[#116a3a] focus:ring-1 focus:ring-[#116a3a] outline-none transition" placeholder="50" required>
                            <span class="absolute right-4 top-1/2 -translate-y-1/2 text-xs font-bold text-muted pointer-events-none select-none">kg</span>
                        </div>
                    </div>

                    {{-- Harga Satuan (per kg) --}}
                    <div class="space-y-1.5">
                        <label class="text-xs font-bold text-ink block" for="harga_nyata">Harga Satuan (per kg)</label>
                        <div class="relative">
                            <span class="absolute left-4 top-1/2 -translate-y-1/2 text-xs font-bold text-muted pointer-events-none select-none">Rp</span>
                            <input id="harga_nyata" name="harga_nyata" type="number" step="50" min="0" value="{{ $firstAcuan }}" class="w-full rounded-xl border border-line bg-white pl-10 pr-12 text-sm font-bold text-ink focus:border-[#116a3a] focus:ring-1 focus:ring-[#116a3a] outline-none transition" placeholder="{{ $firstAcuan }}" required>
                            <span class="absolute right-4 top-1/2 -translate-y-1/2 text-xs font-semibold text-muted pointer-events-none select-none">/kg</span>
                        </div>
                    </div>

                    {{-- Live Estimasi Box (Total Biaya & Stok Gudang Setelah Beli) --}}
                    <div id="beli-live-calc-box" class="p-3.5 sm:p-4 rounded-lg bg-[#f4f7f5] border border-line/60 space-y-2 transition">
                        <div class="flex items-center justify-between text-xs">
                            <span class="text-muted font-medium">Total Biaya Pembelian</span>
                            <strong id="beli-subtotal-val" class="text-lg font-black text-ink">Rp 0</strong>
                        </div>
                        <div class="flex items-center justify-between gap-2 text-xs pt-1 border-t border-line/40">
                            <span class="text-muted font-medium shrink-0 whitespace-nowrap">Stok Gudang Setelah Beli</span>
                            <span id="beli-deviasi-badge" class="text-xs font-bold text-ink whitespace-nowrap shrink-0">
                                {{ Fmt::num($firstStok, 1) }} kg
                            </span>
                        </div>
                    </div>

                    {{-- Submit Button --}}
                    <div class="pt-1 space-y-2">
                        <button type="submit" class="w-full bg-[#186b3e] hover:bg-[#125530] text-white font-bold py-3.5 px-4 rounded-xl flex items-center justify-center gap-2 text-sm shadow-xs transition active:scale-98 cursor-pointer">
                            <span class="material-symbols-outlined text-[18px]">check</span>
                            <span>Catat Belanja Bahan</span>
                        </button>
                        <p class="text-xs text-muted flex items-start gap-1.5 px-1 leading-relaxed">
                            <span class="material-symbols-outlined text-[16px] text-muted shrink-0 mt-0.5">sync</span>
                            <span>Otomatis menambah stok gudang dan memperbarui harga satuan bahan.</span>
                        </p>
                    </div>
                </form>
            </div>

            {{-- 2. Kartu Riwayat Belanja Terkini --}}
            <div class="bg-white rounded-lg sm:rounded-xl p-4 sm:p-5 border border-line/70 shadow-2xs space-y-4">
                <div class="flex items-center justify-between gap-2 pb-3 border-b border-line/60">
                    <div class="flex items-center gap-2.5 min-w-0">
                        <div class="w-10 h-10 rounded-xl bg-[#e2f0e8] text-[#116a3a] flex items-center justify-center shrink-0">
                            <span class="material-symbols-outlined text-[20px]">history</span>
                        </div>
                        <h2 class="text-sm sm:text-base font-black text-ink truncate">Riwayat Pembelian Bahan Pakan</h2>
                    </div>
                    <span class="text-xs font-semibold text-muted shrink-0 whitespace-nowrap">
                        {{ $jumlahTransaksiBeli }} tercatat
                    </span>
                </div>

                @if ($beli->isNotEmpty())
                    <div class="divide-y divide-line/60">
                        @foreach ($beli as $p)
                            @php
                                $subtotalP = $p->harga_nyata * $p->kg;
                            @endphp
                            <div class="py-3 flex items-center justify-between gap-3">
                                <div class="min-w-0">
                                    <span class="font-black text-ink text-sm block">{{ $p->ingredient->nama }}</span>
                                    <p class="text-xs text-muted mt-0.5">
                                        <span class="font-bold text-ink">{{ Fmt::num($p->kg, 1) }} kg</span> ·
                                        <span>{{ Fmt::rp($p->harga_nyata) }}/kg</span>
                                    </p>
                                    <span class="text-[11px] text-muted">{{ $p->tanggal->translatedFormat('j M Y') }}</span>
                                </div>
                                <div class="text-right shrink-0">
                                    <span class="font-black text-sm block text-ink">
                                        {{ Fmt::rp($subtotalP) }}
                                    </span>
                                    <span class="text-[11px] font-medium text-muted">
                                        Total Biaya
                                    </span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="py-6 text-center space-y-2">
                        <div class="w-14 h-14 rounded-xl bg-[#e2f0e8] text-[#116a3a] flex items-center justify-center mx-auto shadow-2xs">
                            <span class="material-symbols-outlined text-[28px]">shopping_bag</span>
                        </div>
                        <h3 class="text-sm font-black text-ink mt-3">Belum ada riwayat pembelian</h3>
                        <p class="text-xs text-muted max-w-xs mx-auto leading-relaxed">
                            Catat pembelian bahan baku pakan untuk menambah stok gudang dan memperbarui harga satuan.
                        </p>
                    </div>
                @endif
            </div>
        </div>

        {{-- ======================================================== --}}
        {{-- B. TAMPILAN DESKTOP BELANJA PAKAN (hidden md:block)      --}}
        {{-- ======================================================== --}}
        <div class="hidden md:block space-y-5" id="beli-desktop-container">
            {{-- Layout 2 Kolom Seimbang (Formulir di Kiri & Riwayat di Kanan) --}}
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-5 items-stretch">
                {{-- KOLOM KIRI (1/2): Formulir Input Pembelian Bahan --}}
                <div class="flex flex-col">
                    <form method="POST" action="{{ route('pembelian.store') }}" class="card space-y-4 flex-1 flex flex-col justify-between" id="form-beli-desktop">
                        @csrf
                        <div class="space-y-4">
                            {{-- Header Form Bersih --}}
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
                                    @include('partials.dropdown-pilih-bahan', ['bahan' => $bahan, 'id' => 'pilih-bahan-desk', 'name' => 'ingredient_id', 'bahanPrices' => $bahanPrices, 'bahanStocks' => $bahanStocks])
                                </div>
                                <div>
                                    <label class="lbl text-xs font-bold text-ink" for="beli_tanggal_desk">Tanggal Pembelian</label>
                                    <input id="beli_tanggal_desk" name="tanggal" type="date" value="{{ today()->toDateString() }}" max="{{ today()->toDateString() }}" class="field h-11 text-sm font-bold text-ink" required>
                                </div>
                            </div>

                            {{-- Grid Input: Jumlah Beli (kg) & Harga Satuan (per kg) --}}
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                                <div>
                                    <label class="lbl text-xs font-bold text-ink" for="kg_desk">Jumlah Beli</label>
                                    <div class="relative">
                                        <input id="kg_desk" name="kg" type="number" step="0.5" min="0.5" class="field h-11 pr-10 text-sm font-bold text-ink placeholder:text-muted/60 placeholder:font-medium" placeholder="Contoh: 50" required>
                                        <span class="absolute right-3.5 top-1/2 -translate-y-1/2 text-xs font-bold text-muted pointer-events-none select-none">kg</span>
                                    </div>
                                </div>
                                <div>
                                    <label class="lbl text-xs font-bold text-ink" for="harga_nyata_desk">Harga Satuan (per kg)</label>
                                    <div class="relative">
                                        <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-xs font-bold text-muted pointer-events-none select-none">Rp</span>
                                        <input id="harga_nyata_desk" name="harga_nyata" type="number" step="50" min="0" value="{{ $firstAcuan }}" class="field h-11 pl-9 pr-12 text-sm font-bold text-ink placeholder:text-muted/60 placeholder:font-medium" placeholder="{{ $firstAcuan }}" required>
                                        <span class="absolute right-3.5 top-1/2 -translate-y-1/2 text-[11px] font-semibold text-muted pointer-events-none select-none">/kg</span>
                                    </div>
                                </div>
                            </div>

                            {{-- Live Estimasi Box (Total Biaya & Stok Gudang Setelah Beli) --}}
                            <div id="beli-live-calc-box-desk" class="p-3 rounded-xl bg-surface/70 border border-line/80 space-y-1.5 transition">
                                <div class="flex items-center justify-between text-xs">
                                    <span class="text-muted font-medium">Total Biaya Pembelian:</span>
                                    <strong id="beli-subtotal-val-desk" class="font-black text-ink text-sm">Rp 0</strong>
                                </div>
                                <div class="flex items-center justify-between text-xs pt-1 border-t border-line/50">
                                    <span class="text-muted font-medium">Stok Gudang Setelah Beli:</span>
                                    <span id="beli-deviasi-badge-desk" class="text-xs font-bold text-ink">
                                        {{ Fmt::num($firstStok, 1) }} kg
                                    </span>
                                </div>
                            </div>
                        </div>

                        {{-- Input Tersembunyi: Sinkronisasi Stok Otomatis --}}
                        <input type="hidden" name="tambah_stok" value="1">

                        {{-- Tombol Submit --}}
                        <div class="pt-2 space-y-2">
                            <button type="submit" class="w-full inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-full bg-brand hover:bg-brand-dark text-white text-xs font-bold shadow-md hover:shadow-brand/20 transition active:scale-95 cursor-pointer">
                                <span class="material-symbols-outlined text-[18px]">receipt_long</span>
                                <span>Catat Belanja Bahan</span>
                            </button>
                            <p class="text-[11px] text-muted text-center flex items-center justify-center gap-1.5">
                                <span class="material-symbols-outlined text-[15px] text-brand">sync</span>
                                <span>Otomatis menambah stok gudang & memperbarui harga satuan bahan</span>
                            </p>
                        </div>
                    </form>
                </div>

                {{-- KOLOM KANAN (1/2): Riwayat Pembelian Bahan --}}
                <div class="card space-y-3.5 flex-1 flex flex-col justify-between">
                    <div class="space-y-3.5">
                        <div class="flex items-center justify-between pb-2.5 border-b border-line">
                            <div class="flex items-center gap-2">
                                <span class="material-symbols-outlined text-[20px] text-brand">history</span>
                                <h3 class="text-xs font-bold text-ink">Riwayat Pembelian Bahan Pakan</h3>
                            </div>
                            <span class="text-tiny font-medium text-muted">{{ count($beli) }} entri tercatat</span>
                        </div>

                        <div class="overflow-x-auto -mx-4 sm:-mx-5 px-4 sm:px-5">
                            <table class="w-full text-xs text-left border-collapse">
                                <thead>
                                    <tr class="border-b border-line text-[11px] font-bold text-muted uppercase tracking-wider">
                                        <th class="py-2 pr-3">Bahan Pakan</th>
                                        <th class="py-2 px-3">Tanggal</th>
                                        <th class="py-2 px-3 text-right">Jumlah</th>
                                        <th class="py-2 px-3 text-right">Harga Satuan</th>
                                        <th class="py-2 pl-3 text-right">Total Biaya</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-line/60">
                                    @forelse ($beli as $p)
                                        @php
                                            $subtotalDesk = $p->harga_nyata * $p->kg;
                                        @endphp
                                        <tr class="hover:bg-surface/50 transition-colors group">
                                            <td class="py-2.5 pr-3 font-bold text-ink">
                                                {{ $p->ingredient->nama }}
                                            </td>
                                            <td class="py-2.5 px-3 text-muted whitespace-nowrap">
                                                {{ $p->tanggal->translatedFormat('d M Y') }}
                                            </td>
                                            <td class="py-2.5 px-3 text-right whitespace-nowrap font-medium text-ink">
                                                {{ Fmt::num($p->kg, 1) }} kg
                                            </td>
                                            <td class="py-2.5 px-3 text-right whitespace-nowrap font-mono text-muted">
                                                {{ Fmt::rp($p->harga_nyata) }}/kg
                                            </td>
                                            <td class="py-2.5 pl-3 text-right whitespace-nowrap font-bold text-ink">
                                                {{ Fmt::rp($subtotalDesk) }}
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="py-8 text-center text-muted">
                                                <div class="flex flex-col items-center justify-center gap-1.5">
                                                    <span class="material-symbols-outlined text-[24px] text-muted/40">shopping_bag</span>
                                                    <p class="text-xs font-semibold text-ink">Belum ada riwayat pembelian</p>
                                                    <p class="text-[11px] text-muted">Input nota belanja bahan baku pakan di formulir sebelah kiri.</p>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="pt-3 border-t border-line/60 flex items-center justify-between text-[11px] text-muted">
                        <span>Stok dan harga satuan di gudang diperbarui otomatis</span>
                        <a href="{{ route('bahan.index') }}" class="font-bold text-brand hover:underline inline-flex items-center gap-1">
                            <span>Lihat Stok Pakan Gudang</span>
                            <span class="material-symbols-outlined text-[13px]">arrow_forward</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

{{-- Skrip Interaktivitas Smart Buku Kandang --}}
<script>
document.addEventListener('DOMContentLoaded', function () {
    const jumlahAyam = {{ $jumlahAyam }};
    const hargaPakanPerKg = {{ $harga }};
    const existingLogs = @json($existingLogs);

    function initCatatController(isDesktop) {
        const pfxDash = isDesktop ? 'desk-' : '';

        const inputTanggal = document.getElementById(isDesktop ? 'desk_tanggal' : 'tanggal');
        const inputPakan = document.getElementById(isDesktop ? 'desk_pakan_kg' : 'pakan_kg');
        const inputTelur = document.getElementById(isDesktop ? 'desk_telur_butir' : 'telur_butir');
        const inputBerat = document.getElementById(isDesktop ? 'desk_telur_kg' : 'telur_kg');
        const inputMati = document.getElementById(isDesktop ? 'desk_ayam_mati' : 'ayam_mati');
        const inputBiayaLain = document.getElementById(isDesktop ? 'desk_biaya_lain' : 'biaya_lain');
        const inputCatatan = document.getElementById(isDesktop ? 'desk_catatan' : 'catatan');

        const previewHdpVal = document.getElementById(pfxDash + 'preview-hdp-val');
        const previewHdpBadge = document.getElementById(pfxDash + 'preview-hdp-badge');
        const previewFeedVal = document.getElementById(pfxDash + 'preview-feed-val');
        const previewFeedBadge = document.getElementById(pfxDash + 'preview-feed-badge');
        const previewFcrVal = document.getElementById(pfxDash + 'preview-fcr-val');
        const previewFcrBadge = document.getElementById(pfxDash + 'preview-fcr-badge');
        const previewCostVal = document.getElementById(pfxDash + 'preview-cost-val');
        const previewMarginSub = document.getElementById(pfxDash + 'preview-margin-sub');
        const previewAlertBox = document.getElementById(pfxDash + 'preview-alert-box');
        const previewAlertIcon = document.getElementById(pfxDash + 'preview-alert-icon');
        const previewAlertText = document.getElementById(pfxDash + 'preview-alert-text');
        const modeStatusBanner = document.getElementById(pfxDash + 'mode-status-banner');
        const modeStatusDate = document.getElementById(pfxDash + 'mode-status-date');
        const btnSubmitText = document.getElementById(pfxDash + 'btn-submit-text');

        if (!inputTanggal || !inputPakan || !inputTelur) return;

        function updateLivePreview() {
            const pakan = parseFloat(inputPakan.value) || 0;
            const butir = parseInt(inputTelur.value) || 0;
            let berat = parseFloat(inputBerat ? inputBerat.value : 0);
            if (isNaN(berat) || berat <= 0) {
                berat = butir * 0.062;
            }
            const mati = parseInt(inputMati ? inputMati.value : 0) || 0;

            // 1. HDP %
            const hdp = jumlahAyam > 0 ? (butir / jumlahAyam) * 100 : 0;
            if (previewHdpVal) previewHdpVal.textContent = hdp > 0 ? hdp.toFixed(1).replace('.', ',') + '%' : '0,0%';
            if (previewHdpBadge) {
                if (butir === 0) {
                    previewHdpBadge.className = 'inline-block mt-1 text-[10px] font-medium text-muted';
                    previewHdpBadge.textContent = 'Menunggu input';
                } else if (hdp >= 85) {
                    previewHdpBadge.className = 'inline-block mt-1 text-[10px] font-bold text-emerald-700';
                    previewHdpBadge.textContent = 'Prima / Sangat Baik';
                } else if (hdp >= 75) {
                    previewHdpBadge.className = 'inline-block mt-1 text-[10px] font-bold text-teal-700';
                    previewHdpBadge.textContent = 'Normal';
                } else {
                    previewHdpBadge.className = 'inline-block mt-1 text-[10px] font-bold text-amber-700';
                    previewHdpBadge.textContent = 'Di bawah standar (<75%)';
                }
            }

            // 2. Konsumsi gr/ekor/hari
            const grPerEkor = (jumlahAyam > 0 && pakan > 0) ? (pakan * 1000) / jumlahAyam : 0;
            if (previewFeedVal) previewFeedVal.innerHTML = `${Math.round(grPerEkor)} <span class="${isDesktop ? 'text-sm font-normal' : 'text-xs font-normal'}">gr</span>`;
            if (previewFeedBadge) {
                if (pakan === 0) {
                    previewFeedBadge.className = 'inline-block mt-1 text-[10px] font-medium text-muted';
                    previewFeedBadge.textContent = 'Standar: 110–120 g/ekor';
                } else if (grPerEkor >= 105 && grPerEkor <= 125) {
                    previewFeedBadge.className = 'inline-block mt-1 text-[10px] font-bold text-emerald-700';
                    previewFeedBadge.textContent = 'Ideal (110–125g)';
                } else if (grPerEkor < 105) {
                    previewFeedBadge.className = 'inline-block mt-1 text-[10px] font-bold text-amber-700';
                    previewFeedBadge.textContent = 'Rendah (<105g)';
                } else {
                    previewFeedBadge.className = 'inline-block mt-1 text-[10px] font-bold text-rose-600';
                    previewFeedBadge.textContent = 'Boros (>125g)';
                }
            }

            // 3. FCR Instan
            const fcr = (berat > 0 && pakan > 0) ? (pakan / berat) : 0;
            if (previewFcrVal) previewFcrVal.textContent = fcr > 0 ? fcr.toFixed(2).replace('.', ',') : '0,00';
            if (previewFcrBadge) {
                if (fcr === 0) {
                    previewFcrBadge.className = 'inline-block mt-1 text-[10px] font-medium text-muted';
                    previewFcrBadge.textContent = 'Rasio pakan per kg telur';
                } else if (fcr <= 2.15) {
                    previewFcrBadge.className = 'inline-block mt-1 text-[10px] font-bold text-emerald-700';
                    previewFcrBadge.textContent = 'Sangat Efisien';
                } else if (fcr <= 2.45) {
                    previewFcrBadge.className = 'inline-block mt-1 text-[10px] font-bold text-teal-700';
                    previewFcrBadge.textContent = 'Normal';
                } else {
                    previewFcrBadge.className = 'inline-block mt-1 text-[10px] font-bold text-rose-600';
                    previewFcrBadge.textContent = 'Tinggi (>2,45)';
                }
            }

            // 4. Biaya Pakan Hari Ini
            const totalBiaya = pakan * hargaPakanPerKg;
            if (previewCostVal) previewCostVal.textContent = 'Rp ' + Math.round(totalBiaya).toLocaleString('id-ID');
            if (previewMarginSub) {
                previewMarginSub.textContent = '@ Rp ' + Number(hargaPakanPerKg).toLocaleString('id-ID') + '/kg';
                previewMarginSub.className = 'inline-block mt-1 text-[10px] text-muted font-medium';
            }

            // Alert Box Dinamis
            if (previewAlertBox && previewAlertIcon && previewAlertText) {
                const roundedClass = 'rounded-xl';
                if (mati >= 5) {
                    previewAlertBox.className = `p-2 ${roundedClass} text-xs font-semibold flex items-center gap-2 bg-rose-100 text-rose-900 border border-rose-300`;
                    previewAlertIcon.textContent = 'warning';
                    previewAlertText.textContent = `Perhatian: Ada ${mati} ekor ayam mati tercatat hari ini. Pantau sirkulasi dan gejala penyakit!`;
                    previewAlertBox.classList.remove('hidden');
                } else if (hdp > 0 && hdp < 65) {
                    previewAlertBox.className = `p-2 ${roundedClass} text-xs font-semibold flex items-center gap-2 bg-amber-100 text-amber-900 border border-amber-300`;
                    previewAlertIcon.textContent = 'info';
                    previewAlertText.textContent = `Produksi telur rendah (${hdp.toFixed(1)}%). Cek kecukupan nutrisi pakan atau potensi stres suhu.`;
                    previewAlertBox.classList.remove('hidden');
                } else if (hdp >= 88 && fcr > 0 && fcr <= 2.15) {
                    previewAlertBox.className = `p-2 ${roundedClass} text-xs font-semibold flex items-center gap-2 bg-emerald-100 text-emerald-900 border border-emerald-300`;
                    previewAlertIcon.textContent = 'verified';
                    previewAlertText.textContent = `Luar biasa! Performa kandang hari ini sangat prima (HDP ${hdp.toFixed(1)}%, FCR ${fcr.toFixed(2)}).`;
                    previewAlertBox.classList.remove('hidden');
                } else {
                    previewAlertBox.classList.add('hidden');
                }
            }
        }

        [inputPakan, inputTelur, inputBerat, inputMati].forEach(el => {
            if (el) el.addEventListener('input', updateLivePreview);
        });

        // Button: Hitung ~62g/butir
        const btnCalcEgg = document.getElementById(pfxDash + 'btn-calc-egg-weight');
        if (btnCalcEgg && inputTelur && inputBerat) {
            btnCalcEgg.addEventListener('click', function () {
                const butir = parseInt(inputTelur.value) || 0;
                if (butir > 0) {
                    const estKg = (butir * 0.062).toFixed(1);
                    inputBerat.value = estKg;
                    updateLivePreview();
                } else {
                    if (window.showToast) window.showToast('Isi jumlah butir telur terlebih dahulu.', 'warning', 'Perhatian');
                    inputTelur.focus();
                }
            });
        }

        // Kalkulator Bantu Hitung Rak Telur (1 Rak = 30 butir)
        const btnToggleRak = document.getElementById(pfxDash + 'btn-toggle-rak-calc');
        const popoverRak = document.getElementById(pfxDash + 'popover-calc-rak');
        const inputCalcRak = document.getElementById(isDesktop ? 'desk-calc-rak-input' : 'calc_rak_input');
        const inputCalcSisa = document.getElementById(isDesktop ? 'desk-calc-sisa-input' : 'calc_sisa_input');
        const previewRakTotal = document.getElementById(pfxDash + 'calc_rak_total_preview');
        const btnApplyRak = document.getElementById(pfxDash + 'btn-apply-rak-calc');
        const btnCancelRak = document.getElementById(pfxDash + 'btn-cancel-rak-calc');

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
                    window.showToast(`${total.toLocaleString('id-ID')} butir telur diterapkan.`, 'success', 'Kalkulator Rak');
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
        const btnQuickFill = document.getElementById(pfxDash + 'btn-quick-fill');
        if (btnQuickFill) {
            btnQuickFill.addEventListener('click', function () {
                const pakan = this.dataset.pakan;
                const telur = this.dataset.telur;
                const berat = this.dataset.berat;
                if (pakan && inputPakan) inputPakan.value = pakan;
                if (telur && inputTelur) inputTelur.value = telur;
                if (berat && inputBerat) inputBerat.value = berat;
                updateLivePreview();
            });
        }

        // Mobile date display helper
        const tanggalDisplay = !isDesktop ? document.getElementById('tanggal-display') : null;
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
            if (!isDesktop) {
                inputTanggal.addEventListener('input', function () {
                    updateTanggalDisplay(this.value);
                });
            }

            inputTanggal.addEventListener('change', function () {
                const tgl = this.value;
                if (!isDesktop) updateTanggalDisplay(tgl);

                if (existingLogs && existingLogs[tgl]) {
                    const log = existingLogs[tgl];
                    if (modeStatusBanner) {
                        modeStatusBanner.classList.remove('hidden');
                        if (modeStatusDate) {
                            const parts = tgl.split('-');
                            if (parts.length === 3) {
                                const months = isDesktop 
                                    ? ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des']
                                    : ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
                                const day = isDesktop ? String(parseInt(parts[2], 10)) : String(parseInt(parts[2], 10)).padStart(2, '0');
                                const month = months[parseInt(parts[1], 10) - 1];
                                const year = parts[0];
                                modeStatusDate.textContent = `${day} ${month} ${year}`;
                            } else {
                                modeStatusDate.textContent = tgl;
                            }
                        }
                    }
                    if (btnSubmitText) btnSubmitText.textContent = isDesktop ? 'Perbarui Catatan Harian' : 'Perbarui Catatan';
                    if (inputPakan) inputPakan.value = log.pakan_kg;
                    if (inputTelur) inputTelur.value = log.telur_butir;
                    if (inputBerat) inputBerat.value = log.telur_kg !== null ? log.telur_kg : '';
                    if (inputMati) inputMati.value = log.ayam_mati;
                    if (inputBiayaLain) inputBiayaLain.value = log.biaya_lain;
                    if (inputCatatan) inputCatatan.value = log.catatan;
                } else {
                    if (modeStatusBanner) modeStatusBanner.classList.add('hidden');
                    if (btnSubmitText) btnSubmitText.textContent = isDesktop ? 'Simpan Catatan Harian' : 'Simpan Catatan';
                }
                updateLivePreview();
            });
        }

        // Tombol Batal Mode Edit (Mobile)
        if (!isDesktop) {
            const btnBatalEdit = document.getElementById('btn-batal-edit');
            if (btnBatalEdit) {
                btnBatalEdit.addEventListener('click', function (e) {
                    const urlParams = new URLSearchParams(window.location.search);
                    if (!urlParams.has('tanggal') && inputTanggal) {
                        e.preventDefault();
                        const todayStr = '{{ today()->toDateString() }}';
                        inputTanggal.value = todayStr;
                        updateTanggalDisplay(todayStr);
                        if (modeStatusBanner) modeStatusBanner.classList.add('hidden');
                        if (btnSubmitText) btnSubmitText.textContent = 'Simpan Catatan';
                        if (existingLogs && existingLogs[todayStr]) {
                            const log = existingLogs[todayStr];
                            inputPakan.value = log.pakan_kg;
                            inputTelur.value = log.telur_butir;
                            inputBerat.value = log.telur_kg !== null ? log.telur_kg : '';
                            if (inputMati) inputMati.value = log.ayam_mati;
                            if (inputBiayaLain) inputBiayaLain.value = log.biaya_lain;
                            if (inputCatatan) inputCatatan.value = log.catatan;
                        } else {
                            inputPakan.value = '';
                            inputTelur.value = '';
                            inputBerat.value = '';
                            if (inputMati) inputMati.value = 0;
                            if (inputBiayaLain) inputBiayaLain.value = '';
                            if (inputCatatan) inputCatatan.value = '';
                        }
                        updateLivePreview();
                    }
                });
            }
        }

        updateLivePreview();
    }

    // Inisialisasi formulir Mobile & Desktop secara independen
    initCatatController(false);
    initCatatController(true);

    // Live Kalkulator Belanja Pakan (Mobile & Desktop)
    const bahanPrices = @json($bahanPrices);
    const bahanStocks = @json($bahanStocks);

    // Desktop
    const inputBeliKgDesk = document.getElementById('kg_desk');
    const inputBeliHargaDesk = document.getElementById('harga_nyata_desk');
    const inputIngredientIdDesk = document.querySelector('#form-beli-desktop input[name="ingredient_id"]');
    const beliSubtotalValDesk = document.getElementById('beli-subtotal-val-desk');
    const beliDeviasiBadgeDesk = document.getElementById('beli-deviasi-badge-desk');

    function updateBeliPreviewDesk() {
        if (!inputBeliKgDesk || !inputBeliHargaDesk || !beliSubtotalValDesk || !beliDeviasiBadgeDesk) return;
        const kg = parseFloat(inputBeliKgDesk.value) || 0;
        const harga = parseFloat(inputBeliHargaDesk.value) || 0;
        const ingId = inputIngredientIdDesk ? inputIngredientIdDesk.value : null;
        const stokAwal = (ingId && bahanStocks[ingId] !== undefined) ? parseFloat(bahanStocks[ingId]) : 0;

        const subtotal = kg * harga;
        beliSubtotalValDesk.textContent = 'Rp ' + Math.round(subtotal).toLocaleString('id-ID');

        if (kg <= 0) {
            beliDeviasiBadgeDesk.className = 'text-xs font-bold text-ink';
            beliDeviasiBadgeDesk.textContent = `${stokAwal.toLocaleString('id-ID', { maximumFractionDigits: 1 })} kg`;
            return;
        }

        const stokAkhir = stokAwal + kg;
        beliDeviasiBadgeDesk.className = 'text-xs font-bold text-emerald-700';
        beliDeviasiBadgeDesk.textContent = `${stokAwal.toLocaleString('id-ID', { maximumFractionDigits: 1 })} kg → ${stokAkhir.toLocaleString('id-ID', { maximumFractionDigits: 1 })} kg`;
    }

    if (inputBeliKgDesk) inputBeliKgDesk.addEventListener('input', updateBeliPreviewDesk);
    if (inputBeliHargaDesk) inputBeliHargaDesk.addEventListener('input', updateBeliPreviewDesk);
    if (inputIngredientIdDesk) {
        inputIngredientIdDesk.addEventListener('change', function () {
            const ingId = this.value;
            if (ingId && bahanPrices[ingId] !== undefined && inputBeliHargaDesk) {
                inputBeliHargaDesk.value = bahanPrices[ingId];
                inputBeliHargaDesk.placeholder = bahanPrices[ingId];
            }
            updateBeliPreviewDesk();
        });
    }

    // Mobile
    const inputBeliKg = document.getElementById('kg');
    const inputBeliHarga = document.getElementById('harga_nyata');
    const inputIngredientId = document.getElementById('beli_ingredient_id');
    const beliSubtotalVal = document.getElementById('beli-subtotal-val');
    const beliDeviasiBadge = document.getElementById('beli-deviasi-badge');

    // Chip Bahan Pakan Selector
    const chipsBahan = document.querySelectorAll('.chip-bahan');
    chipsBahan.forEach(btn => {
        btn.addEventListener('click', function () {
            chipsBahan.forEach(b => {
                b.className = 'chip-bahan px-4 py-1.5 rounded-full text-xs font-bold transition-all cursor-pointer select-none bg-white text-ink border border-line hover:border-line-dark';
            });
            this.className = 'chip-bahan px-4 py-1.5 rounded-full text-xs font-bold transition-all cursor-pointer select-none bg-[#e2f0e8] text-[#116a3a] border-2 border-[#116a3a]';
            const ingId = this.dataset.bahanId;
            const ingNama = this.dataset.bahanNama;
            const ingAcuan = parseFloat(this.dataset.bahanAcuan) || 0;
            const ingStok = (ingId && bahanStocks[ingId] !== undefined) ? parseFloat(bahanStocks[ingId]) : 0;
            if (inputIngredientId) {
                inputIngredientId.value = ingId;
            }
            if (inputBeliHarga && ingAcuan > 0) {
                inputBeliHarga.value = ingAcuan;
                inputBeliHarga.placeholder = ingAcuan;
            }
            const acuanText = document.getElementById('beli-acuan-text');
            if (acuanText) {
                acuanText.textContent = `Stok gudang ${ingNama}: ${ingStok.toLocaleString('id-ID', { maximumFractionDigits: 1 })} kg · Harga terakhir Rp ${ingAcuan.toLocaleString('id-ID')}/kg`;
            }
            updateBeliPreview();
        });
    });

    function updateBeliPreview() {
        if (!inputBeliKg || !inputBeliHarga || !beliSubtotalVal || !beliDeviasiBadge) return;
        const kg = parseFloat(inputBeliKg.value) || 0;
        const harga = parseFloat(inputBeliHarga.value) || 0;
        const ingId = inputIngredientId ? inputIngredientId.value : null;
        const stokAwal = (ingId && bahanStocks[ingId] !== undefined) ? parseFloat(bahanStocks[ingId]) : 0;

        const subtotal = kg * harga;
        beliSubtotalVal.textContent = 'Rp ' + Math.round(subtotal).toLocaleString('id-ID');

        if (kg <= 0) {
            beliDeviasiBadge.className = 'text-xs font-bold text-ink whitespace-nowrap';
            beliDeviasiBadge.textContent = `${stokAwal.toLocaleString('id-ID', { maximumFractionDigits: 1 })} kg`;
            return;
        }

        const stokAkhir = stokAwal + kg;
        beliDeviasiBadge.className = 'text-xs font-bold text-emerald-700 whitespace-nowrap';
        beliDeviasiBadge.textContent = `${stokAwal.toLocaleString('id-ID', { maximumFractionDigits: 1 })} kg → ${stokAkhir.toLocaleString('id-ID', { maximumFractionDigits: 1 })} kg`;
    }

    if (inputBeliKg) inputBeliKg.addEventListener('input', updateBeliPreview);
    if (inputBeliHarga) inputBeliHarga.addEventListener('input', updateBeliPreview);
    if (inputIngredientId) inputIngredientId.addEventListener('change', updateBeliPreview);

    // Tanggal Pembelian Display Sync
    const beliTanggal = document.getElementById('beli_tanggal');
    const beliTanggalDisplay = document.getElementById('beli-tanggal-display');
    if (beliTanggal && beliTanggalDisplay) {
        beliTanggal.addEventListener('change', function () {
            if (this.value) {
                const parts = this.value.split('-');
                if (parts.length === 3) {
                    const dateObj = new Date(parseInt(parts[0]), parseInt(parts[1]) - 1, parseInt(parts[2]));
                    beliTanggalDisplay.textContent = dateObj.toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' });
                }
            }
        });
    }

    // Jalankan kalkulasi awal saat halaman dimuat
    if (typeof updateBeliPreview === "function") updateBeliPreview();
    if (typeof updateBeliPreviewDesk === "function") updateBeliPreviewDesk();
});
</script>
@endsection
