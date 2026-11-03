@extends('layouts.app')
@section('judul', 'Ubah Acuan Gizi')
@section('isi')

<div class="space-y-5">
    <div class="flex items-center justify-between flex-wrap gap-3">
        <div class="flex items-center gap-3">
            <a href="{{ route('acuan.index') }}" class="w-8 h-8 rounded-full bg-white hover:bg-surface border border-line text-muted hover:text-ink grid place-items-center transition shadow-2xs shrink-0" aria-label="Kembali ke Daftar Acuan Gizi">
                <span class="material-symbols-outlined text-[18px]">arrow_back</span>
            </a>
            <div>
                <h1 class="text-lg sm:text-xl font-bold text-ink tracking-tight">
                    Lengkapi / Ubah Acuan Gizi
                </h1>
                <p class="text-xs text-muted mt-0.5">
                    Hanya masukkan angka yang memiliki sumber dokumen jelas. Kosongkan kolom apabila angkanya belum tersedia di sumber.
                </p>
            </div>
        </div>

        <span class="px-2.5 py-1 rounded-lg text-[11px] font-bold border bg-amber-50 text-amber-800 border-amber-300">
            Belum terverifikasi
        </span>
    </div>

    <form id="form-acuan" method="POST" action="{{ route('acuan.update', $acuan) }}" class="w-full bg-white rounded-2xl border border-line shadow-2xs divide-y divide-line overflow-hidden">
        @csrf
        @method('PUT')

        {{-- SEKSI 1: IDENTITAS, TAHUN EDISI & SUMBER --}}
        <div class="p-5 md:p-7 grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
            <div class="lg:col-span-4 space-y-1">
                <div class="flex items-center gap-2.5">
                    <span class="w-6 h-6 rounded-full bg-brand/10 text-brand text-xs font-bold flex items-center justify-center shrink-0">1</span>
                    <h2 class="text-sm font-bold text-ink">Identitas & Sumber Rujukan</h2>
                </div>
                <p class="text-xs text-muted leading-relaxed pl-8.5">
                    Cantumkan nama acuan, tahun/edisi dokumen, dan rincian sumber primer atau catatan sumber sekunder.
                </p>
            </div>

            <div class="lg:col-span-8 space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div class="sm:col-span-2">
                        <label class="lbl" for="nama">Nama Acuan <span class="text-danger">*</span></label>
                        <input id="nama" name="nama" type="text" class="field !min-h-10 text-xs font-medium" value="{{ old('nama', $acuan->nama) }}" required autocomplete="off">
                    </div>

                    <div>
                        <label class="lbl" for="edition_year">Tahun / Edisi <span class="text-danger">*</span></label>
                        <input id="edition_year" name="edition_year" type="text" class="field !min-h-10 text-xs font-medium" value="{{ old('edition_year', $acuan->edition_year) }}" placeholder="Contoh: 2024" required>
                    </div>
                </div>

                <div>
                    <label class="lbl" for="source">Sumber Dokumen <span class="text-danger">*</span></label>
                    <input id="source" name="source" type="text" class="field !min-h-10 text-xs font-medium" value="{{ old('source', $acuan->source ?? $acuan->sumber) }}" required>
                </div>
            </div>
        </div>

        {{-- SEKSI 2: MAKRONUTRISI & ENERGI --}}
        <div class="p-5 md:p-7 grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
            <div class="lg:col-span-4 space-y-1">
                <div class="flex items-center gap-2.5">
                    <span class="w-6 h-6 rounded-full bg-brand/10 text-brand text-xs font-bold flex items-center justify-center shrink-0">2</span>
                    <h2 class="text-sm font-bold text-ink">Protein & Energi Metabolis</h2>
                </div>
                <p class="text-xs text-muted leading-relaxed pl-8.5">
                    Biarkan kosong bila nilai energi metabolis atau protein belum tercantum di dokumen sumber.
                </p>
            </div>

            <div class="lg:col-span-8 space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="lbl" for="protein_min">Protein Kasar Minimal (%)</label>
                        <input id="protein_min" name="protein_min" type="number" step="0.01" min="0" max="100" class="field !min-h-10 text-xs font-medium" value="{{ old('protein_min', $acuan->protein_min) }}" placeholder="Kosongkan jika belum ada sumber">
                    </div>

                    <div>
                        <label class="lbl" for="energi_min">Energi Metabolis Minimal (kkal/kg)</label>
                        <input id="energi_min" name="energi_min" type="number" min="0" max="10000" class="field !min-h-10 text-xs font-medium" value="{{ old('energi_min', $acuan->energi_min) }}" placeholder="Kosongkan jika belum ada sumber">
                    </div>
                </div>
            </div>
        </div>

        {{-- SEKSI 3: MINERAL & SERAT KASAR --}}
        <div class="p-5 md:p-7 grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
            <div class="lg:col-span-4 space-y-1">
                <div class="flex items-center gap-2.5">
                    <span class="w-6 h-6 rounded-full bg-brand/10 text-brand text-xs font-bold flex items-center justify-center shrink-0">3</span>
                    <h2 class="text-sm font-bold text-ink">Kalsium, Fosfor & Serat</h2>
                </div>
                <p class="text-xs text-muted leading-relaxed pl-8.5">
                    Isi hanya batas yang tertulis di sumber (misalnya hanya batas minimal tanpa batas maksimal).
                </p>
            </div>

            <div class="lg:col-span-8 space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="lbl" for="kalsium_min">Kalsium Minimal (%)</label>
                        <input id="kalsium_min" name="kalsium_min" type="number" step="0.01" min="0" max="100" class="field !min-h-10 text-xs font-medium" value="{{ old('kalsium_min', $acuan->kalsium_min) }}" placeholder="Kosongkan jika belum ada sumber">
                    </div>

                    <div>
                        <label class="lbl" for="kalsium_max">Kalsium Maksimal (%)</label>
                        <input id="kalsium_max" name="kalsium_max" type="number" step="0.01" min="0" max="100" class="field !min-h-10 text-xs font-medium" value="{{ old('kalsium_max', $acuan->kalsium_max) }}" placeholder="Kosongkan jika tidak ada batas atas">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="lbl" for="fosfor_min">Fosfor Total Minimal (%)</label>
                        <input id="fosfor_min" name="fosfor_min" type="number" step="0.01" min="0" max="100" class="field !min-h-10 text-xs font-medium" value="{{ old('fosfor_min', $acuan->fosfor_min) }}" placeholder="Kosongkan jika belum terverifikasi">
                    </div>

                    <div>
                        <label class="lbl" for="fosfor_max">Fosfor Total Maksimal (%)</label>
                        <input id="fosfor_max" name="fosfor_max" type="number" step="0.01" min="0" max="100" class="field !min-h-10 text-xs font-medium" value="{{ old('fosfor_max', $acuan->fosfor_max) }}" placeholder="Kosongkan jika tidak ada batas atas">
                    </div>
                </div>

                <div class="pt-1">
                    <label class="lbl" for="serat_max">Serat Kasar Maksimal (%)</label>
                    <input id="serat_max" name="serat_max" type="number" step="0.01" min="0" max="100" class="field !min-h-10 text-xs font-medium" value="{{ old('serat_max', $acuan->serat_max) }}" placeholder="Kosongkan jika tidak dibatasi di sumber">
                </div>

                <div class="pt-2 border-t border-line/60">
                    <label class="inline-flex items-start gap-2.5 cursor-pointer">
                        <input type="checkbox" name="verified" value="1" class="mt-0.5 w-4 h-4 rounded text-brand accent-brand" @checked(old('verified', $acuan->verified))>
                        <span class="text-xs text-ink">
                            <strong class="font-bold">Tandai sudah terverifikasi ke dokumen primer</strong>
                            <span class="block text-[11px] text-muted mt-0.5">Jika dicentang dan disimpan, acuan ini akan terkunci dan dapat dipakai oleh fitur Auto-Match.</span>
                        </span>
                    </label>
                </div>
            </div>
        </div>

        <div class="px-5 md:px-7 py-4 bg-surface/40 flex items-center justify-between flex-wrap gap-2">
            <span class="text-xs text-muted">
                Pastikan seluruh angka yang diisi sesuai dokumen rujukan.
            </span>

            <div class="flex items-center gap-2">
                <a href="{{ route('acuan.index') }}" class="inline-flex items-center justify-center px-4 py-2 rounded-full text-xs font-semibold text-ink bg-white hover:bg-surface border border-line transition shadow-2xs cursor-pointer">
                    Batal
                </a>
                <button type="submit" class="inline-flex items-center justify-center px-5 py-2 rounded-full text-xs font-bold text-white bg-brand hover:bg-brand-dark transition shadow-xs hover:shadow-sm cursor-pointer">
                    Simpan Perubahan
                </button>
            </div>
        </div>
    </form>
</div>
@endsection
