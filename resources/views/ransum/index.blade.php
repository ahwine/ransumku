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
    $warnaPalet = ['#2F6B3A', '#F2B134', '#145224', '#D64545', '#7e5700', '#96d69a', '#0284c7'];
    $warnaPaletMobile = ['#d99b45', '#a16207', '#186b3e', '#64748b', '#0284c7', '#7c3aed'];
@endphp

{{-- ======================================================== --}}
{{-- A. TAMPILAN MOBILE (block md:hidden)                      --}}
{{-- ======================================================== --}}
<div class="block md:hidden space-y-4">
    {{-- Header Halaman Mobile --}}
    <div>
        <h1 class="text-xl sm:text-2xl font-black text-ink tracking-tight">Koleksi Resep Ransum</h1>
        <p class="text-xs text-muted mt-0.5">Resep ransum presisi (Linear Simplex) berbasis SNI dan stok bahan peternakan.</p>
    </div>

    {{-- Tombol Utama: Racik Ransum Baru Full Width --}}
    <a href="{{ route('ransum.create') }}" class="w-full bg-[#186b3e] hover:bg-[#125530] text-white font-black py-3.5 px-4 rounded-2xl flex items-center justify-center gap-2 text-sm shadow-xs transition active:scale-98 cursor-pointer">
        <span class="material-symbols-outlined text-[19px]">add</span>
        <span>Racik Ransum Baru</span>
    </a>

    {{-- Baris Ringkasan KPI Koleksi Resep Mobile (3 Kartu Berdampingan) --}}
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

    {{-- Daftar Kartu Resep Ransum Mobile --}}
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
                                <span style="width:{{ max(2, $it->persen) }}%; background-color: {{ $warnaPaletMobile[$i % count($warnaPaletMobile)] }}" 
                                      class="h-full first:rounded-l-full last:rounded-r-full" 
                                      title="{{ $it->nama }}: {{ Fmt::num($it->persen, 1) }}%"></span>
                            @endforeach
                        </div>

                        {{-- Rincian Bahan Vertikal Sesuai Mobile Reference --}}
                        <div class="space-y-2 pt-1 text-xs">
                            @foreach ($r->items as $i => $it)
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-2">
                                        <span class="w-2.5 h-2.5 rounded-xs shrink-0" style="background-color: {{ $warnaPaletMobile[$i % count($warnaPaletMobile)] }}"></span>
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

{{-- ======================================================== --}}
{{-- B. TAMPILAN DESKTOP (hidden md:block - 100% ASLI e7c0074) --}}
{{-- ======================================================== --}}
<div class="hidden md:block space-y-5">
    {{-- Header Halaman Bersih & Humanis --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-lg sm:text-xl font-black text-ink">Koleksi Resep Ransum</h1>
            <p class="text-xs text-muted mt-0.5">Koleksi resep formulasi ransum presisi Linear Simplex berbasis SNI dan stok bahan peternakan</p>
        </div>
        <a href="{{ route('ransum.create') }}" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-full bg-brand hover:bg-brand-dark text-white text-xs font-bold shadow-md hover:shadow-brand/20 transition active:scale-95 cursor-pointer">
            <span class="material-symbols-outlined text-[17px]">add</span>
            <span>Racik Ransum Baru</span>
        </a>
    </div>

    {{-- Baris Ringkasan KPI Koleksi Resep (Bersih & Minimalis) --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3.5">
        {{-- Kartu 1: Total Resep Tersimpan --}}
        <div class="card p-4 flex flex-col justify-between space-y-2.5 shadow-2xs hover:shadow-xs transition">
            <span class="text-xs font-bold text-muted uppercase tracking-wider">Koleksi Resep</span>
            <div>
                <div class="text-2xl font-black text-ink">
                    {{ $totalResep }} <span class="text-xs font-normal text-muted">Formulasi</span>
                </div>
                <p class="text-[11px] text-muted mt-0.5">Resep ransum tersimpan untuk kandang ini</p>
            </div>
        </div>

        {{-- Kartu 2: Biaya Formulasi Terakhir --}}
        <div class="card p-4 flex flex-col justify-between space-y-2.5 shadow-2xs hover:shadow-xs transition">
            <span class="text-xs font-bold text-muted uppercase tracking-wider">Biaya Formulasi Terakhir</span>
            <div>
                <div class="text-2xl font-black text-ink">
                    {{ $latestHarga > 0 ? Fmt::rp($latestHarga) : 'Rp 0' }} <span class="text-xs font-normal text-muted">/kg</span>
                </div>
                <p class="text-[11px] text-muted mt-0.5">Estimasi biaya bahan baku per kg ransum</p>
            </div>
        </div>

        {{-- Kartu 3: Tingkat Kelayakan Nutrisi --}}
        <div class="card p-4 flex flex-col justify-between space-y-2.5 shadow-2xs hover:shadow-xs transition">
            <div class="flex items-center justify-between gap-2">
                <span class="text-xs font-bold text-muted uppercase tracking-wider">Tingkat Kelayakan Nutrisi</span>
                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold {{ $persenLayak >= 100 ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-amber-50 text-amber-700 border border-amber-200' }}">
                    {{ $persenLayak }}% Memenuhi
                </span>
            </div>
            <div>
                <div class="text-2xl font-black text-ink">
                    {{ $resepLayakCount }} / {{ $totalResep }} <span class="text-xs font-normal text-muted">Lolos Target</span>
                </div>
                <p class="text-[11px] text-muted mt-0.5">Standar kecukupan nutrisi ayam petelur</p>
            </div>
        </div>
    </div>

    {{-- Daftar Kartu Resep Ransum --}}
    <div class="space-y-3.5">
        @forelse ($rations as $r)
            <div class="card p-5 space-y-3.5 shadow-2xs hover:shadow-xs border border-line transition">
                {{-- Header Kartu Resep --}}
                <div class="flex flex-wrap items-center justify-between gap-2 pb-2.5 border-b border-line">
                    <div class="flex items-center gap-2 flex-wrap">
                        <h3 class="text-sm font-bold text-ink">
                            Formulasi {{ $r->created_at->translatedFormat('d F Y, H:i') }} WIB
                        </h3>
                        <span class="text-muted/40">•</span>
                        <span class="text-xs text-muted font-medium">Acuan: {{ $r->namaStandar() }}</span>
                    </div>
                    <div class="flex items-center gap-2">
                        @if ($r->layak())
                            <span class="inline-flex items-center px-3 py-0.5 rounded-full text-[11px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200/80 shadow-2xs">
                                Layak & Memenuhi Target
                            </span>
                        @else
                            <span class="inline-flex items-center px-3 py-0.5 rounded-full text-[11px] font-bold bg-rose-50 text-rose-700 border border-rose-200/80 shadow-2xs">
                                Perlu Penyesuaian
                            </span>
                        @endif
                    </div>
                </div>

                {{-- Ringkasan Biaya & Bobot Total --}}
                <div class="flex items-center justify-between py-1 border-b border-line/60">
                    <div>
                        <span class="text-xs text-muted block uppercase tracking-wider font-semibold">Biaya per Kg</span>
                        <div class="text-xl font-black text-ink flex items-baseline gap-1 mt-0.5">
                            <span>{{ $r->layak() ? Fmt::rp($r->biaya_per_kg) : 'Rp -' }}</span>
                            <span class="text-xs font-semibold text-muted">/kg</span>
                            <span class="tag-est ml-1 font-normal lowercase">est</span>
                        </div>
                    </div>
                    <div class="text-right">
                        <span class="text-xs text-muted block uppercase tracking-wider font-semibold">Volume Racikan</span>
                        <span class="text-sm font-bold text-ink block mt-0.5">
                            {{ Fmt::num($r->total_kg, 1) }} kg {{ $r->jumlah_hari ? '('.$r->jumlah_hari.' Hari)' : '' }}
                        </span>
                    </div>
                </div>

                @if ($r->layak() && $r->items->isNotEmpty())
                    {{-- Komposisi Bahan & Visual Bar sebagai Pill Badge --}}
                    <div class="space-y-2.5 pt-1">
                        <div class="flex h-4 rounded-full overflow-hidden bg-surface-2 p-0.5 border border-line shadow-2xs" aria-hidden="true">
                            @foreach ($r->items as $i => $it)
                                <span style="width:{{ max(2, $it->persen) }}%;background:{{ $warnaPalet[$i % count($warnaPalet)] }}" 
                                      class="h-full first:rounded-l-full last:rounded-r-full transition-all" 
                                      title="{{ $it->nama }}: {{ Fmt::num($it->persen, 1) }}%"></span>
                            @endforeach
                        </div>

                        {{-- Legend Daftar Persentase Bahan (Pill Badges) --}}
                        <div class="flex flex-wrap items-center gap-2 text-xs text-muted pt-0.5">
                            @foreach ($r->items as $i => $it)
                                <div class="inline-flex items-center gap-2 bg-surface/80 hover:bg-white px-3 py-1 rounded-full border border-line shadow-2xs transition-all">
                                    <span class="w-2 h-2 rounded-full shrink-0 shadow-2xs" style="background:{{ $warnaPalet[$i % count($warnaPalet)] }}"></span>
                                    <span class="text-ink font-bold">{{ $it->nama }}</span>
                                    <span class="text-brand tabular-nums font-bold">{{ Fmt::num($it->persen, 1) }}%</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Action Buttons --}}
                <div class="flex items-center justify-between gap-2 pt-2 border-t border-line/60">
                    <a href="{{ route('ransum.show', $r) }}" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-full bg-surface hover:bg-white text-ink hover:text-brand-dark border border-line hover:border-brand/40 text-xs font-bold transition active:scale-95 cursor-pointer shadow-2xs">
                        <span class="material-symbols-outlined text-[17px] text-brand">visibility</span>
                        <span>Buka Resep & Komposisi Timbangan</span>
                    </a>

                    <form method="POST" action="{{ route('ransum.destroy', $r) }}" data-confirm="Hapus riwayat resep formulasi ransum ini?" data-confirm-title="Hapus Resep Ransum" data-confirm-btn="Ya, Hapus Resep">
                        @csrf 
                        @method('DELETE')
                        <button type="submit" class="p-2 rounded-full text-muted hover:text-rose-600 hover:bg-rose-50 transition cursor-pointer" title="Hapus riwayat resep ini">
                            <span class="material-symbols-outlined text-[19px]">delete</span>
                        </button>
                    </form>
                </div>
            </div>
        @empty
            {{-- Empty State jika belum ada resep --}}
            <div class="card p-12 text-center space-y-4 shadow-2xs border border-line flex flex-col items-center justify-center">
                <div class="w-16 h-16 rounded-2xl bg-surface border border-line flex items-center justify-center text-muted shadow-2xs">
                    <span class="material-symbols-outlined text-[32px] text-brand">calculate</span>
                </div>
                <div class="space-y-1 max-w-sm">
                    <h3 class="text-sm sm:text-base font-bold text-ink">Belum Ada Resep Ransum</h3>
                    <p class="text-xs text-muted leading-relaxed">
                        Mulai hitung formulasi campuran pakan termurah dengan target nutrisi presisi SNI menggunakan algoritma Linear Simplex.
                    </p>
                </div>
                <a href="{{ route('ransum.create') }}" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-full bg-brand hover:bg-brand-dark text-white text-xs font-bold shadow-md hover:shadow-brand/20 transition active:scale-95 cursor-pointer">
                    <span class="material-symbols-outlined text-[18px]">add</span>
                    <span>Racik Ransum Baru Sekarang</span>
                </a>
            </div>
        @endforelse
    </div>
</div>
@endsection
