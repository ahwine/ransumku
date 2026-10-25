@extends('layouts.app')
@section('judul', 'Data Bahan')
@section('isi')
@use('App\Support\Fmt')

<div class="flex items-center justify-between">
    <div>
        <h1 class="text-h-xl">Data Bahan</h1>
        <p class="text-muted text-sm">Nilai gizi dan harga acuan bawaan bahan pakan</p>
    </div>
    <a href="{{ route('bahan.create') }}" class="btn-primary btn-sm">
        <span class="material-symbols-outlined">add</span>Tambah
    </a>
</div>

{{-- Peringatan Penting #4 --}}
<div class="notice bg-amber-soft/50 text-amber-ink">
    <span class="material-symbols-outlined icon-fill shrink-0">info</span>
    <p class="text-sm">
        <strong>Perhatian:</strong> Nilai gizi dapat diedit; sesuaikan dengan label kemasan atau tabel komposisi bahan pakan resmi. Nilai awal hanyalah angka ilustrasi.
    </p>
</div>

{{-- Pencarian --}}
<form method="GET" action="{{ route('bahan.index') }}" class="flex gap-2">
    <input type="text" name="q" value="{{ $q }}" placeholder="Cari nama bahan..." class="field !min-h-11">
    <button class="btn-outline btn-sm">Cari</button>
</form>

<div class="space-y-3">
    @forelse ($bahan as $b)
        <div class="card space-y-2">
            <div class="flex items-start justify-between">
                <div>
                    <h2 class="text-h-sm text-ink">{{ $b->nama }}</h2>
                    <p class="text-xs text-muted">Sumber: {{ $b->sumber ?: 'Input mandiri' }}</p>
                </div>
                <div class="flex items-center gap-2">
                    <a href="{{ route('bahan.edit', $b) }}" class="btn-outline btn-sm !min-h-9 !px-3" aria-label="Ubah">
                        <span class="material-symbols-outlined text-[18px]">edit</span>
                    </a>
                    <form method="POST" action="{{ route('bahan.destroy', $b) }}" onsubmit="return confirm('Hapus bahan {{ $b->nama }}?')">
                        @csrf @method('DELETE')
                        <button class="btn btn-sm !min-h-9 !px-3 border border-line text-danger hover:bg-danger/5" aria-label="Hapus">
                            <span class="material-symbols-outlined text-[18px]">delete</span>
                        </button>
                    </form>
                </div>
            </div>

            <div class="grid grid-cols-3 sm:grid-cols-5 gap-2 text-center text-xs pt-1">
                <div class="p-2 rounded-lg bg-surface">
                    <span class="block text-muted">Protein</span>
                    <strong class="text-sm">{{ Fmt::num($b->protein, 1) }}%</strong>
                </div>
                <div class="p-2 rounded-lg bg-surface">
                    <span class="block text-muted">Kalsium</span>
                    <strong class="text-sm">{{ Fmt::num($b->kalsium, 2) }}%</strong>
                </div>
                <div class="p-2 rounded-lg bg-surface">
                    <span class="block text-muted">Fosfor</span>
                    <strong class="text-sm">{{ Fmt::num($b->fosfor, 2) }}%</strong>
                </div>
                <div class="p-2 rounded-lg bg-surface">
                    <span class="block text-muted">Serat</span>
                    <strong class="text-sm">{{ Fmt::num($b->serat, 1) }}%</strong>
                </div>
                <div class="p-2 rounded-lg bg-surface col-span-3 sm:col-span-1">
                    <span class="block text-muted">Energi</span>
                    <strong class="text-sm">{{ Fmt::num($b->energi, 0) }} kkal</strong>
                </div>
            </div>

            <div class="flex justify-between items-center text-xs text-muted pt-1 border-t border-line/60">
                <span>Harga standar: <strong class="text-ink">{{ Fmt::rp($b->harga_default) }}/kg</strong></span>
                @if ($b->catatan)
                    <span class="truncate max-w-[200px]" title="{{ $b->catatan }}">{{ $b->catatan }}</span>
                @endif
            </div>
        </div>
    @empty
        <div class="card text-center py-8 text-muted">
            <span class="material-symbols-outlined text-[40px] mb-1">inventory_2</span>
            <p>Tidak ada bahan yang ditemukan.</p>
        </div>
    @endforelse
</div>
@endsection
