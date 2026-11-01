@extends('layouts.app')
@section('judul', $acuan->exists ? 'Ubah Acuan Gizi' : 'Tambah Acuan Gizi')
@section('isi')

<div class="space-y-5">
    {{-- Header Halaman Bersih & Lebar Penuh (W-Full) --}}
    <div class="flex items-center gap-3">
        <a href="{{ route('acuan.index') }}" class="w-8 h-8 rounded-full bg-white hover:bg-surface border border-line text-muted hover:text-ink grid place-items-center transition shadow-2xs shrink-0" aria-label="Kembali ke Daftar Acuan Gizi">
            <span class="material-symbols-outlined text-[18px]">arrow_back</span>
        </a>
        <div>
            <h1 class="text-lg sm:text-xl font-bold text-ink tracking-tight">
                {{ $acuan->exists ? 'Ubah Data Acuan Gizi' : 'Tambah Acuan Gizi Baru' }}
            </h1>
            <p class="text-xs text-muted mt-0.5">
                Konfigurasi standar batas minimal dan maksimal nutrisi kebutuhan ayam petelur.
            </p>
        </div>
    </div>

    {{-- Form Card Lebar Penuh (Two-Column Split Sectional Layout) --}}
    <form id="form-acuan" method="POST" action="{{ $acuan->exists ? route('acuan.update', $acuan) : route('acuan.store') }}" class="w-full bg-white rounded-2xl border border-line shadow-2xs divide-y divide-line overflow-hidden">
        @csrf
        @if ($acuan->exists) 
            @method('PUT') 
        @endif

        {{-- SEKSI 1: IDENTITAS ACUAN & RUJUKAN --}}
        <div class="p-5 md:p-7 grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
            <div class="lg:col-span-4 space-y-1">
                <div class="flex items-center gap-2.5">
                    <span class="w-6 h-6 rounded-full bg-brand/10 text-brand text-xs font-bold flex items-center justify-center shrink-0">1</span>
                    <h2 class="text-sm font-bold text-ink">Identitas & Rujukan</h2>
                </div>
                <p class="text-xs text-muted leading-relaxed pl-8.5">
                    Nama pedoman standar, tahun publikasi resmi, dan dokumen rujukan atau jurnal ilmiah yang digunakan.
                </p>
            </div>

            <div class="lg:col-span-8 space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div class="sm:col-span-2">
                        <label class="lbl" for="nama">Nama Acuan <span class="text-danger">*</span></label>
                        <input id="nama" name="nama" type="text" class="field !min-h-10 text-xs font-medium" value="{{ old('nama', $acuan->nama) }}" placeholder="Contoh: SNI 2016 (Ayam Petelur Fase Produksi)" required autocomplete="off">
                    </div>

                    <div>
                        <label class="lbl" for="tahun">Tahun Rujukan</label>
                        <input id="tahun" name="tahun" type="number" min="1980" max="2050" class="field !min-h-10 text-xs font-medium" value="{{ old('tahun', $acuan->exists ? $acuan->tahun : 2016) }}" placeholder="Contoh: 2016">
                    </div>
                </div>

                <div>
                    <label class="lbl" for="sumber">Keterangan Sumber / Jurnal / Rujukan</label>
                    <input id="sumber" name="sumber" type="text" class="field !min-h-10 text-xs font-medium" value="{{ old('sumber', $acuan->sumber) }}" placeholder="Contoh: SNI Pakan Ayam Petelur No. 01-3929-2006 / Balitnak">
                </div>
            </div>
        </div>

        {{-- SEKSI 2: KEBUTUHAN MAKRONUTRISI UTAMA --}}
        <div class="p-5 md:p-7 grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
            <div class="lg:col-span-4 space-y-1">
                <div class="flex items-center gap-2.5">
                    <span class="w-6 h-6 rounded-full bg-brand/10 text-brand text-xs font-bold flex items-center justify-center shrink-0">2</span>
                    <h2 class="text-sm font-bold text-ink">Makronutrisi & Energi</h2>
                </div>
                <p class="text-xs text-muted leading-relaxed pl-8.5">
                    Target minimal protein kasar dan energi metabolis untuk menjaga stamina serta kestabilan produksi telur harian.
                </p>
            </div>

            <div class="lg:col-span-8 space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="lbl" for="protein_min">Protein Kasar Minimal (%) <span class="text-danger">*</span></label>
                        <input id="protein_min" name="protein_min" type="number" step="0.01" min="0" max="100" class="field !min-h-10 text-xs font-medium" value="{{ old('protein_min', $acuan->exists ? $acuan->protein_min : '16.5') }}" placeholder="Misal: 16.5" required>
                    </div>

                    <div>
                        <label class="lbl" for="energi_min">Energi Metabolis Minimal (kkal/kg) <span class="text-danger">*</span></label>
                        <input id="energi_min" name="energi_min" type="number" min="0" max="10000" class="field !min-h-10 text-xs font-medium" value="{{ old('energi_min', $acuan->exists ? $acuan->energi_min : '2700') }}" placeholder="Misal: 2700" required>
                    </div>
                </div>
            </div>
        </div>

        {{-- SEKSI 3: MINERAL & AMBANG SERAT KASAR --}}
        <div class="p-5 md:p-7 grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
            <div class="lg:col-span-4 space-y-1">
                <div class="flex items-center gap-2.5">
                    <span class="w-6 h-6 rounded-full bg-brand/10 text-brand text-xs font-bold flex items-center justify-center shrink-0">3</span>
                    <h2 class="text-sm font-bold text-ink">Mineral & Ambang Serat</h2>
                </div>
                <p class="text-xs text-muted leading-relaxed pl-8.5">
                    Rentang kebutuhan kalsium untuk kerabang telur, fosfor tersedia untuk metabolisme tulang, dan batas toleransi serat kasar.
                </p>
            </div>

            <div class="lg:col-span-8 space-y-4">
                {{-- Rentang Kalsium --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="lbl" for="kalsium_min">Kalsium Minimal (%) <span class="text-danger">*</span></label>
                        <input id="kalsium_min" name="kalsium_min" type="number" step="0.01" min="0" max="100" class="field !min-h-10 text-xs font-medium" value="{{ old('kalsium_min', $acuan->exists ? $acuan->kalsium_min : '3.25') }}" placeholder="Misal: 3.25" required>
                    </div>

                    <div>
                        <label class="lbl" for="kalsium_max">Kalsium Maksimal (%) <span class="text-danger">*</span></label>
                        <input id="kalsium_max" name="kalsium_max" type="number" step="0.01" min="0" max="100" class="field !min-h-10 text-xs font-medium" value="{{ old('kalsium_max', $acuan->exists ? $acuan->kalsium_max : '4.25') }}" placeholder="Misal: 4.25" required>
                    </div>
                </div>

                {{-- Rentang Fosfor --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="lbl" for="fosfor_min">Fosfor Minimal (%) <span class="text-danger">*</span></label>
                        <input id="fosfor_min" name="fosfor_min" type="number" step="0.01" min="0" max="100" class="field !min-h-10 text-xs font-medium" value="{{ old('fosfor_min', $acuan->exists ? $acuan->fosfor_min : '0.60') }}" placeholder="Misal: 0.60" required>
                    </div>

                    <div>
                        <label class="lbl" for="fosfor_max">Fosfor Maksimal (%) <span class="text-danger">*</span></label>
                        <input id="fosfor_max" name="fosfor_max" type="number" step="0.01" min="0" max="100" class="field !min-h-10 text-xs font-medium" value="{{ old('fosfor_max', $acuan->exists ? $acuan->fosfor_max : '0.90') }}" placeholder="Misal: 0.90" required>
                    </div>
                </div>

                {{-- Batas Serat Kasar --}}
                <div class="pt-1">
                    <label class="lbl" for="serat_max">Serat Kasar Maksimal (%) (Opsional)</label>
                    <input id="serat_max" name="serat_max" type="number" step="0.01" min="0" max="100" class="field !min-h-10 text-xs font-medium" value="{{ old('serat_max', $acuan->serat_max) }}" placeholder="Kosongkan jika tidak dibatasi (contoh: 7.0)">
                    <span class="text-[10px] text-muted block mt-1">Kosongkan jika formula ransum tidak membatasi serat kasar secara ketat</span>
                </div>
            </div>
        </div>

        {{-- CARD FOOTER: AKSI FORMULIR (KIRI: KETERANGAN, KANAN: TOMBOL AKSI PILL) --}}
        <div class="px-5 md:px-7 py-4 bg-surface/40 flex items-center justify-between">
            <span class="text-xs text-muted">
                Tanda <span class="text-danger font-bold">*</span> wajib diisi dengan nilai yang valid
            </span>

            <div class="flex items-center gap-2">
                <a href="{{ route('acuan.index') }}" class="inline-flex items-center justify-center px-4 py-2 rounded-full text-xs font-semibold text-ink bg-white hover:bg-surface border border-line transition shadow-2xs cursor-pointer">
                    Batal
                </a>
                <button type="submit" class="inline-flex items-center justify-center px-5 py-2 rounded-full text-xs font-bold text-white bg-brand hover:bg-brand-dark transition shadow-xs hover:shadow-sm cursor-pointer">
                    {{ $acuan->exists ? 'Simpan Perubahan' : 'Tambahkan Acuan' }}
                </button>
            </div>
        </div>
    </form>
</div>
@endsection

