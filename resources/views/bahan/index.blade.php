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

{{-- Pencarian Cepat Bahan Pakan --}}
<div class="card !p-3">
    <form method="GET" action="{{ route('bahan.index') }}" class="flex items-center gap-2" id="form-search-bahan">
        <div class="relative flex-1 flex items-center">
            <span class="material-symbols-outlined absolute left-3 text-[18px] text-muted pointer-events-none">search</span>
            <input type="text" 
                   name="q" 
                   id="search-bahan-live"
                   value="{{ $q }}" 
                   oninput="filterBahanKatalog(this)"
                   placeholder="Cari nama bahan pakan (misal: Jagung, Bekatul, Konsentrat)..." 
                   class="w-full pl-9 pr-8 py-2.5 bg-surface/50 focus:bg-white border border-line rounded-xl text-xs sm:text-sm font-semibold text-ink placeholder:text-muted focus:outline-none focus:ring-1 focus:ring-brand focus:border-brand shadow-2xs transition"
                   autocomplete="off">
            @if ($q)
                <a href="{{ route('bahan.index') }}" class="absolute right-2.5 text-muted hover:text-ink cursor-pointer p-0.5 rounded-md hover:bg-surface" title="Hapus filter pencarian">
                    <span class="material-symbols-outlined text-[16px]">close</span>
                </a>
            @else
                <button type="button" 
                        id="clear-search-bahan"
                        onclick="clearFilterBahanKatalog()" 
                        class="hidden absolute right-2.5 text-muted hover:text-ink cursor-pointer p-0.5 rounded-md hover:bg-surface" 
                        title="Hapus pencarian">
                    <span class="material-symbols-outlined text-[16px]">close</span>
                </button>
            @endif
        </div>
        <button type="submit" class="btn-primary btn-sm !min-h-10 !px-4 text-xs font-bold shadow-2xs">
            <span>Cari</span>
        </button>
    </form>
</div>

<div class="space-y-3">
    @forelse ($bahan as $b)
        <div class="card space-y-2" data-bahan-card data-bahan-nama="{{ strtolower($b->nama) }}">
            <div class="flex items-start justify-between">
                <div>
                    <h2 class="text-h-sm text-ink">{{ $b->nama }}</h2>
                    <p class="text-xs text-muted">Sumber: {{ $b->sumber ?: 'Input mandiri' }}</p>
                </div>
                <div class="flex items-center gap-2">
                    <a href="{{ route('bahan.edit', $b) }}" class="btn-outline btn-sm !min-h-9 !px-3" aria-label="Ubah">
                        <span class="material-symbols-outlined text-[18px]">edit</span>
                    </a>
                    <form method="POST" action="{{ route('bahan.destroy', $b) }}" data-confirm="Apakah Anda yakin ingin menghapus bahan pakan '{{ $b->nama }}' dari daftar?" data-confirm-title="Hapus Bahan Pakan" data-confirm-btn="Ya, Hapus Bahan">
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

    {{-- Live Filter Empty State --}}
    <div id="bahan-empty-filter" class="hidden card text-center py-8 text-muted space-y-1">
        <span class="material-symbols-outlined text-[36px] text-muted/60 mb-1 block">search_off</span>
        <p class="font-bold text-ink">Bahan Tidak Ditemukan</p>
        <p class="text-xs text-muted">Tidak ada bahan pakan yang sesuai dengan kata kunci pencarian Anda.</p>
    </div>
</div>
@endsection
