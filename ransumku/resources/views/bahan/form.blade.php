@extends('layouts.app')
@section('judul', $bahan->exists ? 'Ubah Bahan' : 'Tambah Bahan')
@section('isi')
<div class="flex items-center gap-2">
    <a href="{{ route('bahan.index') }}" class="w-10 h-10 grid place-items-center rounded-full bg-white border border-line" aria-label="Kembali">
        <span class="material-symbols-outlined">arrow_back</span>
    </a>
    <h1 class="text-h-lg">{{ $bahan->exists ? 'Ubah Bahan' : 'Tambah Bahan Baru' }}</h1>
</div>

<form method="POST" action="{{ $bahan->exists ? route('bahan.update', $bahan) : route('bahan.store') }}" class="card space-y-4">
    @csrf
    @if ($bahan->exists) @method('PUT') @endif

    <div>
        <label class="lbl" for="nama">Nama Bahan</label>
        <input id="nama" name="nama" type="text" class="field" value="{{ old('nama', $bahan->nama) }}" placeholder="Contoh: Jagung Kuning" required>
    </div>

    <div class="grid grid-cols-2 gap-3">
        <div>
            <label class="lbl" for="protein">Protein Kasar (%)</label>
            <input id="protein" name="protein" type="number" step="0.01" min="0" max="100" class="field" value="{{ old('protein', $bahan->protein ?? 0) }}" required>
        </div>
        <div>
            <label class="lbl" for="energi">Energi Metabolis (kkal/kg)</label>
            <input id="energi" name="energi" type="number" min="0" max="10000" class="field" value="{{ old('energi', $bahan->energi ?? 0) }}" required>
        </div>
    </div>

    <div class="grid grid-cols-3 gap-2">
        <div>
            <label class="lbl" for="kalsium">Kalsium (%)</label>
            <input id="kalsium" name="kalsium" type="number" step="0.01" min="0" max="100" class="field" value="{{ old('kalsium', $bahan->kalsium ?? 0) }}" required>
        </div>
        <div>
            <label class="lbl" for="fosfor">Fosfor (%)</label>
            <input id="fosfor" name="fosfor" type="number" step="0.01" min="0" max="100" class="field" value="{{ old('fosfor', $bahan->fosfor ?? 0) }}" required>
        </div>
        <div>
            <label class="lbl" for="serat">Serat Kasar (%)</label>
            <input id="serat" name="serat" type="number" step="0.01" min="0" max="100" class="field" value="{{ old('serat', $bahan->serat ?? 0) }}" required>
        </div>
    </div>

    <div>
        <label class="lbl" for="harga_default">Harga Acuan / Default (Rp/kg)</label>
        <input id="harga_default" name="harga_default" type="number" min="0" step="50" class="field" value="{{ old('harga_default', $bahan->harga_default ?? 0) }}" required>
        <p class="text-xs text-muted mt-1">Harga saat meracik ransum tetap bisa diubah di formulir perhitungan.</p>
    </div>

    <div>
        <label class="lbl" for="sumber">Sumber Informasi / Laboratorium</label>
        <input id="sumber" name="sumber" type="text" class="field" value="{{ old('sumber', $bahan->sumber) }}" placeholder="Contoh: Label Kemasan Pabrik / SNI 2016">
    </div>

    <div>
        <label class="lbl" for="catatan">Catatan Tambahan (Opsional)</label>
        <textarea id="catatan" name="catatan" rows="2" class="field !min-h-[70px] py-2" placeholder="Contoh: Batasi maksimal 15% agar tidak diare">{{ old('catatan', $bahan->catatan) }}</textarea>
    </div>

    <div class="pt-2">
        <button class="btn-primary w-full">
            {{ $bahan->exists ? 'Simpan Perubahan' : 'Tambahkan Bahan' }}
        </button>
    </div>
</form>
@endsection
