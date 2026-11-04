@extends('layouts.app')
@section('judul', 'Peracik Ransum')
@section('isi')
@use('App\Support\Fmt')

@php
    $totalResep = $rations->count();
    $resepLayakCount = $rations->filter(fn ($r) => $r->layak())->count();
    $persenLayak = $totalResep > 0 ? round(($resepLayakCount / $totalResep) * 100) : 0;
    $latestRation = $rations->first();
    $latestHarga = $latestRation?->biaya_per_kg ?? 0;
    $warnaPalet = ['#d99b45', '#a16207', '#186b3e', '#64748b', '#0284c7', '#7c3aed'];
@endphp

<div class="space-y-4">
    {{-- Header Halaman (Image 3 style) --}}
    <div>
        <h1 class="text-xl sm:text-2xl font-black text-ink tracking-tight">Koleksi Resep Ransum</h1>
        <p class="text-xs text-muted mt-0.5">Resep ransum presisi (Linear Simplex) berbasis SNI dan stok bahan peternakan.</p>
    </div>

    {{-- Tombol Utama: Racik Ransum Baru Full Width --}}
    <a href="{{ route('ransum.create') }}" class="w-full bg-[#186b3e] hover:bg-[#125530] text-white font-black py-3.5 px-4 rounded-2xl flex items-center justify-center gap-2 text-sm shadow-xs transition active:scale-98 cursor-pointer">
        <span class="material-symbols-outlined text-[19px]">add</span>
        <span>Racik Ransum Baru</span>
    </a>

    {{-- Baris Ringkasan KPI Koleksi Resep (3 Kartu Berdampingan Sesuai Gambar 3) --}}
    <div class="grid grid-cols-3 gap-2.5">
        {{-- Kartu 1: Total Resep --}}
        <div class="bg-white rounded-2xl border border-line/70 p-3 shadow-2xs flex flex-col justify-between">
            <span class="text-[10px] text-muted uppercase font-bold tracking-wider block">RESEP</span>
            <div class="mt-1">
                <span class="text-lg sm:text-xl font-black text-ink">{{ $totalResep }}</span>
                <span class="text-xs font-normal text-muted ml-0.5">formulasi</span>
            </div>
        </div>

        {{-- Kartu 2: Biaya Formulasi Terakhir --}}
        <div class="bg-white rounded-2xl border border-line/70 p-3 shadow-2xs flex flex-col justify-between">
            <span class="text-[10px] text-muted uppercase font-bold tracking-wider block">BIAYA TERAKHIR</span>
            <div class="mt-1">
                <span class="text-lg sm:text-xl font-black text-ink">{{ $latestHarga > 0 ? Fmt::rp($latestHarga) : 'Rp 0' }}</span>
                <span class="text-xs font-normal text-muted ml-0.5">/kg</span>
            </div>
        </div>

        {{-- Kartu 3: Tingkat Kelayakan Nutrisi --}}
        <div class="bg-white rounded-2xl border border-line/70 p-3 shadow-2xs flex flex-col justify-between">
            <span class="text-[10px] text-muted uppercase font-bold tracking-wider block">NUTRISI</span>
            <div class="mt-1">
                <span class="text-lg sm:text-xl font-black text-ink">{{ $resepLayakCount }}/{{ max(1, $totalResep) }}</span>
                <span class="text-xs font-normal text-muted ml-0.5">lolos</span>
            </div>
        </div>
    </div>

    {{-- Daftar Kartu Resep Ransum (Sesuai Gambar 3) --}}
    <div class="space-y-4">
        @forelse ($rations as $r)
            <div class="bg-white rounded-[24px] sm:rounded-3xl p-4 sm:p-5 border border-line/70 shadow-2xs space-y-3.5">
                {{-- Header Kartu Resep --}}
                <div class="space-y-1">
                    <h2 class="text-base font-black text-ink tracking-tight">
                        Formulasi {{ $r->created_at->translatedFormat('j M Y, H:i') }} WIB
                    </h2>
                    <p class="text-xs text-muted">
                        Acuan: {{ $r->namaStandar() }}
                    </p>
                </div>

                {{-- Status Badge --}}
                <div>
                    @if ($r->layak())
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-[#d8ece0] text-[#116a3a]">
                            <span class="material-symbols-outlined text-[15px]">check</span>
                            <span>Layak & memenuhi target</span>
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-rose-50 text-rose-700 border border-rose-200">
                            <span class="material-symbols-outlined text-[15px]">warning</span>
                            <span>Perlu penyesuaian</span>
                        </span>
                    @endif
                </div>

                {{-- 2 Kotak Metrik: Biaya per Kg & Total Racikan --}}
                <div class="grid grid-cols-2 gap-3">
                    <div class="p-3.5 rounded-2xl bg-[#fbfdfc] border border-line/70">
                        <span class="text-[10px] text-muted uppercase font-bold tracking-wider block mb-1">BIAYA PER KG</span>
                        <div class="text-lg sm:text-xl font-black text-ink flex items-center gap-1.5">
                            <span>{{ $r->layak() ? Fmt::rp($r->biaya_per_kg) : 'Rp -' }}</span>
                            <span class="px-1.5 py-0.2 rounded text-[9px] font-extrabold uppercase tracking-wider bg-slate-100 text-slate-500 border border-slate-200/80">EST</span>
                        </div>
                    </div>

                    <div class="p-3.5 rounded-2xl bg-[#fbfdfc] border border-line/70">
                        <span class="text-[10px] text-muted uppercase font-bold tracking-wider block mb-1">TOTAL RACIKAN</span>
                        <div class="text-lg sm:text-xl font-black text-ink">
                            {{ Fmt::num($r->total_kg, 0) }} kg
                        </div>
                    </div>
                </div>

                @if ($r->layak() && $r->items->isNotEmpty())
                    {{-- Komposisi Bahan & Segmented Bar --}}
                    <div class="space-y-2.5 pt-1">
                        <span class="text-xs font-bold text-ink block">Komposisi bahan</span>

                        {{-- Multi-color Continuous Progress Bar --}}
                        <div class="flex h-3 rounded-full overflow-hidden bg-slate-100 gap-0.5" aria-hidden="true">
                            @foreach ($r->items as $i => $it)
                                <span style="width:{{ max(2, $it->persen) }}%; background-color: {{ $warnaPalet[$i % count($warnaPalet)] }}" 
                                      class="h-full first:rounded-l-full last:rounded-r-full" 
                                      title="{{ $it->nama }}: {{ Fmt::num($it->persen, 1) }}%"></span>
                            @endforeach
                        </div>

                        {{-- Rincian Bahan Vertikal Sesuai Gambar 3 --}}
                        <div class="space-y-2 pt-1 text-xs">
                            @foreach ($r->items as $i => $it)
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-2">
                                        <span class="w-2.5 h-2.5 rounded-xs shrink-0" style="background-color: {{ $warnaPalet[$i % count($warnaPalet)] }}"></span>
                                        <span class="font-bold text-ink">{{ $it->nama }}</span>
                                    </div>
                                    <div class="flex items-center gap-4 text-right">
                                        <span class="text-muted font-medium">{{ Fmt::num($it->kg, 1) }} kg</span>
                                        <span class="font-black text-ink w-12 text-right">{{ Fmt::num($it->persen, 1) }}%</span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Action Buttons Row: Buka Resep & Delete --}}
                <div class="flex items-center gap-2.5 pt-2 border-t border-line/60">
                    <a href="{{ route('ransum.show', $r) }}" class="flex-1 bg-white hover:bg-surface text-ink font-bold py-2.5 px-4 rounded-2xl border border-line/80 flex items-center justify-center gap-2 text-sm shadow-2xs transition active:scale-98 cursor-pointer">
                        <span class="material-symbols-outlined text-[18px]">visibility</span>
                        <span>Buka resep</span>
                    </a>

                    <form method="POST" action="{{ route('ransum.destroy', $r) }}" data-confirm="Hapus riwayat resep formulasi ransum ini?" data-confirm-title="Hapus Resep Ransum" data-confirm-btn="Ya, Hapus Resep">
                        @csrf 
                        @method('DELETE')
                        <button type="submit" class="p-2.5 rounded-2xl bg-white border border-rose-200 text-rose-600 hover:bg-rose-50 transition active:scale-95 cursor-pointer flex items-center justify-center shadow-2xs" title="Hapus riwayat resep ini">
                            <span class="material-symbols-outlined text-[19px]">delete</span>
                        </button>
                    </form>
                </div>
            </div>
        @empty
            {{-- Empty State jika belum ada resep --}}
            <div class="bg-white rounded-3xl p-12 text-center space-y-4 shadow-2xs border border-line/70 flex flex-col items-center justify-center">
                <div class="w-16 h-16 rounded-2xl bg-[#e2f0e8] text-[#116a3a] flex items-center justify-center shadow-2xs">
                    <span class="material-symbols-outlined text-[32px]">calculate</span>
                </div>
                <div class="space-y-1 max-w-sm">
                    <h3 class="text-base font-black text-ink">Belum Ada Resep Ransum</h3>
                    <p class="text-xs text-muted leading-relaxed">
                        Mulai hitung formulasi campuran pakan termurah dengan target nutrisi presisi SNI menggunakan algoritma Linear Simplex.
                    </p>
                </div>
                <a href="{{ route('ransum.create') }}" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-2xl bg-[#186b3e] hover:bg-[#125530] text-white text-xs font-bold shadow-xs transition active:scale-98 cursor-pointer">
                    <span class="material-symbols-outlined text-[18px]">add</span>
                    <span>Racik Ransum Baru Sekarang</span>
                </a>
            </div>
        @endforelse
    </div>
</div>
@endsection
