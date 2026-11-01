@extends('layouts.app')
@section('judul', 'Bahan Pakan')
@section('isi')
@use('App\Support\Fmt')

<div class="space-y-5">
    {{-- Header Halaman Bersih & Humanis --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-lg sm:text-xl font-bold text-ink tracking-tight">Bahan Pakan</h1>
            <p class="text-xs text-muted mt-0.5">Katalog nilai nutrisi, batas aman ransum, dan harga acuan bahan baku peternakan.</p>
        </div>

        <a href="{{ route('bahan.create') }}" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-full bg-brand hover:bg-brand-dark text-white text-xs font-bold shadow-2xs hover:shadow-xs transition active:scale-95 cursor-pointer">
            <span class="material-symbols-outlined text-[17px]">add</span>
            <span>Tambah Bahan</span>
        </a>
    </div>

    {{-- Baris Ringkasan KPI Bahan Pakan (Bersih & Minimalis) --}}
    @php
        $totalBahan = $bahan->count();
        $avgHarga = $totalBahan > 0 ? $bahan->avg('harga_default') : 0;
        $avgProtein = $totalBahan > 0 ? $bahan->avg('protein') : 0;
    @endphp
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3.5">
        {{-- Kartu 1: Total Bahan Tersimpan --}}
        <div class="card p-4 flex flex-col justify-between space-y-1.5 shadow-2xs hover:shadow-xs transition">
            <span class="text-xs font-bold text-muted uppercase tracking-wider">Katalog Bahan</span>
            <div>
                <div class="text-xl sm:text-2xl font-black text-ink">
                    {{ $totalBahan }} <span class="text-xs font-normal text-muted">Bahan Terdaftar</span>
                </div>
                <p class="text-[11px] text-muted mt-0.5">Koleksi bahan baku untuk racikan pakan</p>
            </div>
        </div>

        {{-- Kartu 2: Estimasi Rata-rata Harga Acuan --}}
        <div class="card p-4 flex flex-col justify-between space-y-1.5 shadow-2xs hover:shadow-xs transition">
            <span class="text-xs font-bold text-muted uppercase tracking-wider">Rata-rata Harga Acuan</span>
            <div>
                <div class="text-xl sm:text-2xl font-black text-ink">
                    {{ Fmt::rp($avgHarga) }} <span class="text-xs font-normal text-muted">/ kg</span>
                </div>
                <p class="text-[11px] text-muted mt-0.5">Estimasi rata-rata harga pasar bahan baku</p>
            </div>
        </div>

        {{-- Kartu 3: Profil Protein Rata-rata --}}
        <div class="card p-4 flex flex-col justify-between space-y-1.5 shadow-2xs hover:shadow-xs transition">
            <span class="text-xs font-bold text-muted uppercase tracking-wider">Protein Rata-rata</span>
            <div>
                <div class="text-xl sm:text-2xl font-black text-ink">
                    {{ Fmt::num($avgProtein, 1) }}% <span class="text-xs font-normal text-muted">Protein Kasar</span>
                </div>
                <p class="text-[11px] text-muted mt-0.5">Rata-rata kandungan protein kasar dari katalog</p>
            </div>
        </div>
    </div>

    {{-- Pencarian Cepat Real-Time Terintegrasi (Bentuk Pill Badge) --}}
    <div class="relative flex items-center">
        <span class="material-symbols-outlined absolute left-4 text-[18px] text-muted pointer-events-none">search</span>
        <input type="text" 
               name="q" 
               id="search-bahan-live"
               value="{{ $q }}" 
               oninput="filterBahanKatalog(this)"
               placeholder="Cari nama bahan pakan (misal: Jagung, Bekatul, Konsentrat)..." 
               class="w-full pl-11 pr-10 py-2.5 bg-white border border-line rounded-full text-xs sm:text-sm font-medium text-ink placeholder:text-muted focus:outline-none focus:ring-2 focus:ring-brand/15 focus:border-brand shadow-2xs transition"
               autocomplete="off">
        @if ($q)
            <a href="{{ route('bahan.index') }}" class="absolute right-3.5 text-muted hover:text-ink cursor-pointer p-1 rounded-full hover:bg-surface transition" title="Hapus filter pencarian">
                <span class="material-symbols-outlined text-[16px]">close</span>
            </a>
        @else
            <button type="button" 
                    id="clear-search-bahan"
                    onclick="clearFilterBahanKatalog()" 
                    class="hidden absolute right-3.5 text-muted hover:text-ink cursor-pointer p-1 rounded-full hover:bg-surface transition" 
                    title="Hapus pencarian">
                <span class="material-symbols-outlined text-[16px]">close</span>
            </button>
        @endif
    </div>

    {{-- Subheader Bersih --}}
    <div class="space-y-3.5">
        <div class="flex items-center justify-between pb-1 border-b border-line/60">
            <span class="text-xs font-bold text-muted uppercase tracking-wider">
                Katalog Bahan Pakan ({{ $totalBahan }})
            </span>
        </div>

        {{-- Grid 2 Kolom Bahan Pakan: Proporsional, Tidak Melar, & Rapi --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            @forelse ($bahan as $b)
                <div class="bg-white rounded-2xl border border-line p-4 sm:p-5 shadow-2xs hover:shadow-xs transition flex flex-col justify-between space-y-3" data-bahan-card data-bahan-nama="{{ strtolower($b->nama) }}">
                    <div class="space-y-3">
                        {{-- Baris Atas: Nama Bahan, Pill Tags & Harga Acuan --}}
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <h2 class="text-base font-bold text-ink tracking-tight truncate">
                                    {{ $b->nama }}
                                </h2>
                                <div class="flex items-center gap-1.5 flex-wrap mt-1">
                                    @if ($b->max_persen && $b->max_persen < 100)
                                        <span class="px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-amber-50 text-amber-800 border border-amber-200">
                                            Maks: {{ Fmt::num($b->max_persen, 0) }}%
                                        </span>
                                    @endif
                                    @if ($b->bk)
                                        <span class="px-2.5 py-0.5 rounded-full text-[11px] font-medium bg-surface text-muted border border-line">
                                            BK {{ Fmt::num($b->bk, 1) }}%
                                        </span>
                                    @endif
                                    @if ($b->sumber && !str_contains(strtolower($b->sumber), 'ilustrasi'))
                                        <span class="px-2.5 py-0.5 rounded-full text-[11px] font-medium bg-surface text-muted border border-line truncate max-w-[150px]">
                                            {{ $b->sumber }}
                                        </span>
                                    @endif
                                </div>
                            </div>

                            <div class="text-right shrink-0">
                                <span class="text-[10px] font-semibold text-muted uppercase tracking-wider block">Harga Acuan</span>
                                <span class="text-sm sm:text-base font-bold text-ink block">
                                    {{ Fmt::rp($b->harga_default) }} <span class="text-xs font-normal text-muted">/ kg</span>
                                </span>
                            </div>
                        </div>

                        {{-- Strip 5 Metrik Terbagi (Divided Metric Strip Bersih & Harmonis) --}}
                        <div class="grid grid-cols-5 divide-x divide-line rounded-lg border border-line/60 bg-surface/30 py-2 text-center">
                            <div class="px-1">
                                <span class="text-[10px] font-semibold text-muted uppercase tracking-wider block">Protein</span>
                                <span class="text-xs sm:text-sm font-bold text-ink mt-0.5 block">
                                    {{ Fmt::num($b->protein, 1) }}%
                                </span>
                            </div>
                            <div class="px-1">
                                <span class="text-[10px] font-semibold text-muted uppercase tracking-wider block">Energi</span>
                                <span class="text-xs sm:text-sm font-bold text-ink mt-0.5 block truncate">
                                    {{ Fmt::num($b->energi, 0) }} <span class="text-[10px] font-normal text-muted">kkal</span>
                                </span>
                            </div>
                            <div class="px-1">
                                <span class="text-[10px] font-semibold text-muted uppercase tracking-wider block">Kalsium</span>
                                <span class="text-xs sm:text-sm font-bold text-ink mt-0.5 block">
                                    {{ Fmt::num($b->kalsium, 2) }}%
                                </span>
                            </div>
                            <div class="px-1">
                                <span class="text-[10px] font-semibold text-muted uppercase tracking-wider block">Fosfor</span>
                                <span class="text-xs sm:text-sm font-bold text-ink mt-0.5 block">
                                    {{ Fmt::num($b->fosfor, 2) }}%
                                </span>
                            </div>
                            <div class="px-1">
                                <span class="text-[10px] font-semibold text-muted uppercase tracking-wider block">Serat</span>
                                <span class="text-xs sm:text-sm font-bold text-ink mt-0.5 block">
                                    {{ Fmt::num($b->serat, 1) }}%
                                </span>
                            </div>
                        </div>
                    </div>

                    {{-- Baris Bawah: Catatan Keamanan / Keterangan & Tombol Aksi Pill --}}
                    <div class="pt-2.5 border-t border-line flex items-center justify-between gap-2">
                        <div class="min-w-0 pr-2">
                            @if ($b->catatan_keamanan)
                                <span class="text-[11px] text-amber-800 font-medium truncate block" title="{{ $b->catatan_keamanan }}">
                                    {{ $b->catatan_keamanan }}
                                </span>
                            @elseif ($b->catatan && !str_contains(strtolower($b->catatan), 'sesuaikan dengan label'))
                                <span class="text-[11px] text-muted truncate block" title="{{ $b->catatan }}">
                                    {{ $b->catatan }}
                                </span>
                            @else
                                <span class="text-[11px] text-muted">Bahan baku siap digunakan</span>
                            @endif
                        </div>

                        <div class="flex items-center gap-1.5 shrink-0">
                            {{-- Tombol Edit Berbentuk Pill Badge --}}
                            <a href="{{ route('bahan.edit', $b) }}" class="inline-flex items-center gap-1 px-3.5 py-1.5 rounded-full text-xs font-semibold text-ink bg-surface hover:bg-surface-2 border border-line transition cursor-pointer shadow-2xs hover:shadow-xs" aria-label="Ubah">
                                <span class="material-symbols-outlined text-[15px] text-muted">edit</span>
                                <span>Edit</span>
                            </a>

                            {{-- Tombol Hapus Berbentuk Pill Badge --}}
                            <form method="POST" action="{{ route('bahan.destroy', $b) }}" data-confirm="Apakah Anda yakin ingin menghapus bahan pakan '{{ $b->nama }}' dari daftar?" data-confirm-title="Hapus Bahan Pakan" data-confirm-btn="Ya, Hapus Bahan">
                                @csrf @method('DELETE')
                                <button type="submit" class="inline-flex items-center gap-1 px-3 py-1.5 rounded-full text-xs font-semibold text-rose-600 hover:text-rose-700 bg-rose-50/60 hover:bg-rose-100/70 border border-rose-200/70 transition cursor-pointer shadow-2xs" aria-label="Hapus">
                                    <span class="material-symbols-outlined text-[15px]">delete</span>
                                    <span>Hapus</span>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-span-full bg-white rounded-2xl border border-line text-center py-10 text-muted space-y-2">
                    <span class="material-symbols-outlined text-[36px] text-muted/50 block">inventory_2</span>
                    <p class="font-bold text-ink text-sm">Belum Ada Bahan Pakan</p>
                    <p class="text-xs text-muted">Klik tombol "Tambah Bahan" di atas untuk menambahkan bahan pakan baru.</p>
                </div>
            @endforelse

            {{-- Live Filter Empty State --}}
            <div id="bahan-empty-filter" class="hidden col-span-full bg-white rounded-2xl border border-line text-center py-10 text-muted space-y-1">
                <span class="material-symbols-outlined text-[36px] text-muted/50 mb-1 block">search_off</span>
                <p class="font-bold text-ink text-sm">Bahan Tidak Ditemukan</p>
                <p class="text-xs text-muted">Tidak ada bahan pakan yang sesuai dengan kata kunci pencarian Anda.</p>
            </div>
        </div>
    </div>
</div>
@endsection
