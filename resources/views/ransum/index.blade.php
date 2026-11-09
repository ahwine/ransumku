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
    <div class="flex items-center justify-between gap-3">
        <div>
            <h1 class="text-xl sm:text-2xl font-black text-ink tracking-tight">Koleksi Resep Ransum</h1>
            <p class="text-xs text-muted mt-0.5">Formulasi ransum presisi berbasis SNI 8290 dan ketersediaan bahan pakan.</p>
        </div>
        @if ($totalResep > 0)
            <a href="{{ route('ransum.create') }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-brand hover:bg-brand-dark text-white text-xs font-bold transition shadow-xs shrink-0 cursor-pointer">
                <span class="material-symbols-outlined text-[17px]">add</span>
                <span>Racik Baru</span>
            </a>
        @endif
    </div>

    {{-- Daftar Kartu Resep Ransum Mobile --}}
    <div class="space-y-4">
        @forelse ($rations as $r)
            <div class="bg-white rounded-lg sm:rounded-3xl p-4 sm:p-5 border border-line/70 shadow-2xs space-y-3.5">
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
            <div class="bg-white rounded-lg sm:rounded-3xl p-8 sm:p-12 text-center space-y-4 shadow-2xs border border-line/70 flex flex-col items-center justify-center">
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
{{-- B. TAMPILAN DESKTOP                                       --}}
{{-- ======================================================== --}}
<div class="hidden md:block space-y-5">
    {{-- Header Halaman Bersih & Humanis --}}
    <div class="flex items-center justify-between gap-3">
        <div>
            <h1 class="text-xl sm:text-2xl font-black text-ink tracking-tight">Koleksi Resep Ransum</h1>
            <p class="text-xs text-muted mt-0.5">Formulasi ransum presisi berbasis standar baku mutu SNI 8290 dan ketersediaan stok kandang.</p>
        </div>
        @if ($totalResep > 0)
            <a href="{{ route('ransum.create') }}" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-full bg-brand hover:bg-brand-dark text-white text-xs font-bold shadow-xs hover:shadow-brand/20 transition active:scale-95 cursor-pointer">
                <span class="material-symbols-outlined text-[18px]">add</span>
                <span>Racik Ransum Baru</span>
            </a>
        @endif
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
                        @if (!empty($r->hasil['stok_dipotong']))
                            <span class="text-xs font-bold text-emerald-600">
                                Stok Telah Dipotong
                            </span>
                        @else
                            <span class="text-xs font-semibold text-muted">
                                Simulasi (Belum Dipotong)
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
                        </div>
                    </div>
                    <div class="text-right">
                        <span class="text-xs text-muted block uppercase tracking-wider font-semibold">Volume Racikan</span>
                        <span class="text-sm font-bold text-ink block mt-0.5">
                            {{ Fmt::num($r->total_kg, 0) }} kg {{ $r->jumlah_hari ? '('.$r->jumlah_hari.' Hari)' : '' }}
                        </span>
                    </div>
                </div>

                @if ($r->layak() && $r->items->isNotEmpty())
                    {{-- Komposisi Bahan & Visual Bar --}}
                    <div class="space-y-2.5 pt-1">
                        <div class="flex h-3.5 rounded-full overflow-hidden bg-surface-2 p-0.5 border border-line shadow-2xs" aria-hidden="true">
                            @foreach ($r->items as $i => $it)
                                @php $pTampil = (float) ($it->persen_as_fed ?: $it->persen); @endphp
                                <span style="width:{{ max(2, $pTampil) }}%;background:{{ $warnaPalet[$i % count($warnaPalet)] }}" 
                                      class="h-full first:rounded-l-full last:rounded-r-full transition-all" 
                                      title="{{ $it->nama }}: {{ Fmt::num($pTampil, 1) }}%"></span>
                            @endforeach
                        </div>

                        {{-- Legend Daftar Persentase Bahan (Bersih Tanpa Pill Badge) --}}
                        <div class="flex flex-wrap items-center gap-x-4 gap-y-1.5 text-xs text-muted pt-0.5">
                            @foreach ($r->items as $i => $it)
                                @php $pTampil = (float) ($it->persen_as_fed ?: $it->persen); @endphp
                                <div class="inline-flex items-center gap-1.5">
                                    <span class="w-2 h-2 rounded-full shrink-0" style="background:{{ $warnaPalet[$i % count($warnaPalet)] }}"></span>
                                    <span class="text-ink font-semibold">{{ $it->nama }}</span>
                                    <span class="text-brand tabular-nums font-bold">{{ Fmt::num($pTampil, 1) }}%</span>
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
            {{-- Empty State jika belum ada resep (Tengah Layar Elegan) --}}
            <div class="card p-12 text-center space-y-4 shadow-2xs border border-line flex flex-col items-center justify-center my-6">
                <div class="w-16 h-16 rounded-2xl bg-brand/10 border border-brand/20 flex items-center justify-center text-brand shadow-2xs">
                    <span class="material-symbols-outlined text-[32px]">soup_kitchen</span>
                </div>
                <div class="space-y-1.5 max-w-md">
                    <h3 class="text-base font-black text-ink">Belum Ada Koleksi Resep Ransum</h3>
                    <p class="text-xs text-muted leading-relaxed">
                        Anda belum pernah membuat formula pakan untuk kandang ini. Mulai racik ransum pertama Anda yang hemat biaya dan memenuhi standar baku SNI 8290.
                    </p>
                </div>
                <a href="{{ route('ransum.create') }}" class="inline-flex items-center gap-2 px-6 py-3 rounded-full bg-brand hover:bg-brand-dark text-white text-xs font-bold shadow-md hover:shadow-brand/20 transition active:scale-95 cursor-pointer">
                    <span class="material-symbols-outlined text-[18px]">add</span>
                    <span>Mulai Racik Pakan Pertama</span>
                </a>
            </div>
        @endforelse
    </div>
</div>
@endsection
