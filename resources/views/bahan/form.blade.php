@extends('layouts.app')
@section('judul', $bahan->exists ? 'Ubah Bahan' : 'Tambah Bahan')
@section('isi')

<div class="space-y-5">
    {{-- Header Halaman Bersih & Lebar Penuh (W-Full) --}}
    <div class="flex items-center gap-3">
        <a href="{{ route('bahan.index') }}" class="w-8 h-8 rounded-full bg-white hover:bg-surface border border-line text-muted hover:text-ink grid place-items-center transition shadow-2xs shrink-0" aria-label="Kembali ke Daftar Bahan">
            <span class="material-symbols-outlined text-[18px]">arrow_back</span>
        </a>
        <div>
            <h1 class="text-lg sm:text-xl font-bold text-ink tracking-tight">
                {{ $bahan->exists ? 'Ubah Data Bahan Pakan' : 'Tambah Bahan Pakan Baru' }}
            </h1>
            <p class="text-xs text-muted mt-0.5">
                Atur komposisi nutrisi makro, ambang batas keamanan ransum, dan harga acuan pasar.
            </p>
        </div>
    </div>

    {{-- Form Card Lebar Penuh (Two-Column Split Sectional Layout) --}}
    <form id="form-bahan" method="POST" action="{{ $bahan->exists ? route('bahan.update', $bahan) : route('bahan.store') }}" class="w-full bg-white rounded-2xl border border-line shadow-2xs divide-y divide-line overflow-hidden">
        @csrf
        @if ($bahan->exists) 
            @method('PUT') 
        @endif

        {{-- SEKSI 1: IDENTITAS & HARGA ACUAN --}}
        <div class="p-5 md:p-7 grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
            <div class="lg:col-span-4 space-y-1">
                <div class="flex items-center gap-2.5">
                    <span class="w-6 h-6 rounded-full bg-brand/10 text-brand text-xs font-bold flex items-center justify-center shrink-0">1</span>
                    <h2 class="text-sm font-bold text-ink">Identitas & Harga Acuan</h2>
                </div>
                <p class="text-xs text-muted leading-relaxed pl-8.5">
                    Nama bahan pakan, estimasi harga default pasar per kilogram, dan asal informasi atau uji laboratorium resmi.
                </p>
            </div>

            <div class="lg:col-span-8 space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="lbl" for="nama">Nama Bahan Pakan <span class="text-danger">*</span></label>
                        <input id="nama" name="nama" type="text" class="field !min-h-10 text-xs font-medium" value="{{ old('nama', $bahan->nama) }}" placeholder="Contoh: Jagung Kuning" required autocomplete="off">
                    </div>

                    <div>
                        <label class="lbl" for="harga_default">Harga Acuan (Rp/kg) <span class="text-danger">*</span></label>
                        <input id="harga_default" name="harga_default" type="number" min="0" step="50" class="field !min-h-10 text-xs font-medium" value="{{ old('harga_default', $bahan->exists ? $bahan->harga_default : '') }}" placeholder="Contoh: 6500" required>
                    </div>
                </div>

                <div>
                    <label class="lbl" for="sumber">Sumber Data / Laboratorium</label>
                    <input id="sumber" name="sumber" type="text" class="field !min-h-10 text-xs font-medium" value="{{ old('sumber', $bahan->sumber) }}" placeholder="Contoh: SNI 2016 / Label Kemasan Pabrik">
                </div>
            </div>
        </div>

        {{-- SEKSI 2: KANDUNGAN NUTRISI MAKRO --}}
        <div class="p-5 md:p-7 grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
            <div class="lg:col-span-4 space-y-1">
                <div class="flex items-center gap-2.5">
                    <span class="w-6 h-6 rounded-full bg-brand/10 text-brand text-xs font-bold flex items-center justify-center shrink-0">2</span>
                    <h2 class="text-sm font-bold text-ink">Kandungan Nutrisi Makro</h2>
                </div>
                <p class="text-xs text-muted leading-relaxed pl-8.5">
                    Nilai nutrisi utama yang menjadi acuan algoritma Linear Simplex untuk memenuhi standar gizi SNI ayam petelur.
                </p>
            </div>

            <div class="lg:col-span-8 space-y-4">
                {{-- Dua Pilar Utama: Protein & Energi --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="lbl" for="protein">Protein Kasar (%) <span class="text-danger">*</span></label>
                        <input id="protein" name="protein" type="number" step="0.01" min="0" max="100" class="field !min-h-10 text-xs font-medium" value="{{ old('protein', $bahan->exists ? $bahan->protein : '') }}" placeholder="Misal: 8.5" required>
                    </div>

                    <div>
                        <label class="lbl" for="energi">Energi Metabolis (kkal/kg) <span class="text-danger">*</span></label>
                        <input id="energi" name="energi" type="number" min="0" max="10000" class="field !min-h-10 text-xs font-medium" value="{{ old('energi', $bahan->exists ? $bahan->energi : '') }}" placeholder="Misal: 3300" required>
                    </div>
                </div>

                {{-- Trio Mineral & Serat: Kalsium, Fosfor, Serat Kasar --}}
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label class="lbl" for="kalsium">Kalsium / Ca (%) <span class="text-danger">*</span></label>
                        <input id="kalsium" name="kalsium" type="number" step="0.01" min="0" max="100" class="field !min-h-10 text-xs font-medium" value="{{ old('kalsium', $bahan->exists ? $bahan->kalsium : '') }}" placeholder="Misal: 0.03" required>
                    </div>

                    <div>
                        <label class="lbl" for="fosfor">Fosfor / P (%) <span class="text-danger">*</span></label>
                        <input id="fosfor" name="fosfor" type="number" step="0.01" min="0" max="100" class="field !min-h-10 text-xs font-medium" value="{{ old('fosfor', $bahan->exists ? $bahan->fosfor : '') }}" placeholder="Misal: 0.28" required>
                    </div>

                    <div>
                        <label class="lbl" for="serat">Serat Kasar / SK (%) <span class="text-danger">*</span></label>
                        <input id="serat" name="serat" type="number" step="0.01" min="0" max="100" class="field !min-h-10 text-xs font-medium" value="{{ old('serat', $bahan->exists ? $bahan->serat : '') }}" placeholder="Misal: 2.2" required>
                    </div>
                </div>
            </div>
        </div>

        {{-- SEKSI 3: AMBANG BATAS INKLUSI & KEAMANAN PAKAN --}}
        <div class="p-5 md:p-7 grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
            <div class="lg:col-span-4 space-y-1">
                <div class="flex items-center gap-2.5">
                    <span class="w-6 h-6 rounded-full bg-brand/10 text-brand text-xs font-bold flex items-center justify-center shrink-0">3</span>
                    <h2 class="text-sm font-bold text-ink">Batas Keamanan & Inklusi</h2>
                </div>
                <p class="text-xs text-muted leading-relaxed pl-8.5">
                    Batas persentase penggunaan agar formulasi ransum tidak mengganggu metabolisme ayam atau menyebabkan kelebihan zat antinutrisi.
                </p>
            </div>

            <div class="lg:col-span-8 space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label class="lbl" for="bk">Bahan Kering / BK (%) <span class="text-danger">*</span></label>
                        <input id="bk" name="bk" type="number" step="0.1" min="1" max="100" class="field !min-h-10 text-xs font-medium" value="{{ old('bk', $bahan->bk ?? 88.0) }}" required>
                        <span class="text-[10px] text-muted block mt-1">Standar: Jagung ~86%, Dedak ~88%</span>
                    </div>

                    <div>
                        <label class="lbl" for="min_persen">Min Penggunaan (%)</label>
                        <input id="min_persen" name="min_persen" type="number" step="0.1" min="0" max="100" class="field !min-h-10 text-xs font-medium" value="{{ old('min_persen', $bahan->min_persen ?? 0.0) }}">
                        <span class="text-[10px] text-muted block mt-1">Batas minimum dalam ransum</span>
                    </div>

                    <div>
                        <label class="lbl" for="max_persen">Maks Penggunaan (%) <span class="text-danger">*</span></label>
                        <input id="max_persen" name="max_persen" type="number" step="0.1" min="0" max="100" class="field !min-h-10 text-xs font-medium" value="{{ old('max_persen', $bahan->max_persen ?? 100.0) }}" required>
                        <span class="text-[10px] text-muted block mt-1">Contoh: Dedak maks 30%, Kapur 10%</span>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-1">
                    <div>
                        <label class="lbl" for="catatan_keamanan">Peringatan Keamanan / Antinutrisi (Opsional)</label>
                        <input id="catatan_keamanan" name="catatan_keamanan" type="text" class="field !min-h-10 text-xs font-medium" value="{{ old('catatan_keamanan', $bahan->catatan_keamanan) }}" placeholder="Contoh: Dibatasi 25% untuk mencegah asam fitat berlebih">
                    </div>

                    <div>
                        <label class="lbl" for="catatan">Catatan Tambahan (Opsional)</label>
                        <input id="catatan" name="catatan" type="text" class="field !min-h-10 text-xs font-medium" value="{{ old('catatan', $bahan->catatan) }}" placeholder="Contoh: Simpan di tempat kering dan sejuk">
                    </div>
                </div>
            </div>
        </div>

        {{-- CARD FOOTER: AKSI FORMULIR (KIRI: KETERANGAN, KANAN: TOMBOL AKSI PILL) --}}
        <div class="px-5 md:px-7 py-4 bg-surface/40 flex items-center justify-between">
            <span class="text-xs text-muted">
                Tanda <span class="text-danger font-bold">*</span> wajib diisi dengan nilai yang valid
            </span>

            <div class="flex items-center gap-2">
                <a href="{{ route('bahan.index') }}" class="inline-flex items-center justify-center px-4 py-2 rounded-full text-xs font-semibold text-ink bg-white hover:bg-surface border border-line transition shadow-2xs cursor-pointer">
                    Batal
                </a>
                <button type="submit" class="inline-flex items-center justify-center px-5 py-2 rounded-full text-xs font-bold text-white bg-brand hover:bg-brand-dark transition shadow-xs hover:shadow-sm cursor-pointer">
                    {{ $bahan->exists ? 'Simpan Perubahan' : 'Tambahkan Bahan' }}
                </button>
            </div>
        </div>
    </form>
</div>
@endsection

