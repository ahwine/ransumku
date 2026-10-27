@extends('layouts.app')
@section('judul', $acuan->exists ? 'Ubah Acuan Gizi' : 'Tambah Acuan Gizi')
@section('isi')
<div class="flex items-center gap-2">
    <a href="{{ route('acuan.index') }}" class="w-10 h-10 grid place-items-center rounded-full bg-white border border-line" aria-label="Kembali">
        <span class="material-symbols-outlined">arrow_back</span>
    </a>
    <h1 class="text-h-lg">{{ $acuan->exists ? 'Ubah Acuan Gizi' : 'Tambah Acuan Gizi Baru' }}</h1>
</div>

<form method="POST" action="{{ $acuan->exists ? route('acuan.update', $acuan) : route('acuan.store') }}" class="card space-y-4">
    @csrf
    @if ($acuan->exists) @method('PUT') @endif

    <div class="grid grid-cols-3 gap-3">
        <div class="col-span-2">
            <label class="lbl" for="nama">Nama Acuan</label>
            <input id="nama" name="nama" type="text" class="field" value="{{ old('nama', $acuan->nama) }}" placeholder="Contoh: SNI 2016 (Ayam Petelur Fase Produksi)" required>
        </div>
        <div>
            <label class="lbl" for="tahun">Tahun</label>
            <input id="tahun" name="tahun" type="number" min="1980" max="2050" class="field" value="{{ old('tahun', $acuan->tahun ?? 2016) }}">
        </div>
    </div>

    <div class="grid grid-cols-2 gap-3">
        <div>
            <label class="lbl" for="protein_min">Protein Kasar Minimal (%)</label>
            <input id="protein_min" name="protein_min" type="number" step="0.01" min="0" max="100" class="field" value="{{ old('protein_min', $acuan->protein_min ?? 16.5) }}" required>
        </div>
        <div>
            <label class="lbl" for="energi_min">Energi Metabolis Minimal (kkal/kg)</label>
            <input id="energi_min" name="energi_min" type="number" min="0" max="10000" class="field" value="{{ old('energi_min', $acuan->energi_min ?? 2700) }}" required>
        </div>
    </div>

    <div class="grid grid-cols-2 gap-3">
        <div>
            <label class="lbl" for="kalsium_min">Kalsium Min (%)</label>
            <input id="kalsium_min" name="kalsium_min" type="number" step="0.01" min="0" max="100" class="field" value="{{ old('kalsium_min', $acuan->kalsium_min ?? 3.25) }}" required>
        </div>
        <div>
            <label class="lbl" for="kalsium_max">Kalsium Maks (%)</label>
            <input id="kalsium_max" name="kalsium_max" type="number" step="0.01" min="0" max="100" class="field" value="{{ old('kalsium_max', $acuan->kalsium_max ?? 4.25) }}" required>
        </div>
    </div>

    <div class="grid grid-cols-2 gap-3">
        <div>
            <label class="lbl" for="fosfor_min">Fosfor Min (%)</label>
            <input id="fosfor_min" name="fosfor_min" type="number" step="0.01" min="0" max="100" class="field" value="{{ old('fosfor_min', $acuan->fosfor_min ?? 0.6) }}" required>
        </div>
        <div>
            <label class="lbl" for="fosfor_max">Fosfor Maks (%)</label>
            <input id="fosfor_max" name="fosfor_max" type="number" step="0.01" min="0" max="100" class="field" value="{{ old('fosfor_max', $acuan->fosfor_max ?? 0.9) }}" required>
        </div>
    </div>

    <div>
        <label class="lbl" for="serat_max">Serat Kasar Maksimal (%) (Opsional)</label>
        <input id="serat_max" name="serat_max" type="number" step="0.01" min="0" max="100" class="field" value="{{ old('serat_max', $acuan->serat_max) }}" placeholder="Kosongkan jika tidak dibatasi">
    </div>

    <div>
        <label class="lbl" for="sumber">Keterangan Sumber / Jurnal / Rujukan</label>
        <input id="sumber" name="sumber" type="text" class="field" value="{{ old('sumber', $acuan->sumber) }}" placeholder="Contoh: SNI Pakan Ayam Petelur No. 01-3929-2006">
    </div>

    <div class="pt-2">
        <button class="btn-primary w-full">
            {{ $acuan->exists ? 'Simpan Perubahan' : 'Tambahkan Acuan' }}
        </button>
    </div>
</form>
@endsection
