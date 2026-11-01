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
@endphp

<div class="space-y-5">
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

        {{-- Kartu 3: Tingkat Kelayakan Nutrisi SNI --}}
        <div class="card p-4 flex flex-col justify-between space-y-2.5 shadow-2xs hover:shadow-xs transition">
            <div class="flex items-center justify-between gap-2">
                <span class="text-xs font-bold text-muted uppercase tracking-wider">Tingkat Kelayakan SNI</span>
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
                        <span class="text-xs text-muted font-medium">Acuan: {{ $r->standard?->nama ?? 'Standar SNI' }}</span>
                    </div>
                    <div class="flex items-center gap-2">
                        @if ($r->layak())
                            <span class="inline-flex items-center px-3 py-0.5 rounded-full text-[11px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200/80 shadow-2xs">
                                Layak & Sesuai SNI
                            </span>
                        @else
                            <span class="inline-flex items-center px-3 py-0.5 rounded-full text-[11px] font-bold bg-rose-50 text-rose-700 border border-rose-200/80 shadow-2xs">
                                Perlu Penyesuaian
                            </span>
                        @endif
                    </div>
                </div>

                {{-- Ringkasan Biaya & Kapasitas Campuran --}}
                <div class="flex flex-wrap items-baseline justify-between gap-3 pt-0.5">
                    <div>
                        <span class="text-xs text-muted block uppercase tracking-wider font-semibold">Estimasi Biaya Pakan</span>
                        <div class="flex items-baseline gap-2 mt-0.5">
                            <strong class="text-xl sm:text-2xl font-black text-ink">
                                {{ $r->layak() ? Fmt::rp($r->biaya_per_kg) : 'Rp -' }}
                            </strong>
                            <span class="text-xs text-muted font-semibold">/ kg ransum</span>
                            @if ($r->total_biaya > 0)
                                <span class="text-xs text-muted">· Total: <strong class="text-ink font-bold">{{ Fmt::rp($r->total_biaya) }}</strong></span>
                            @endif
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
