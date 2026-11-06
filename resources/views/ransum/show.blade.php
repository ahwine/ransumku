@extends('layouts.app')
@section('judul', 'Hasil Ransum')
@section('isi')
@use('App\Support\Fmt')
@php
    $warna = ['#2F6B3A', '#F2B134', '#145224', '#D64545', '#7e5700', '#96d69a', '#b22b2e'];
@endphp

{{-- Header Halaman Bersih --}}
<div class="flex items-center gap-3">
    <a href="{{ route('ransum.index') }}" 
       class="w-10 h-10 rounded-full bg-white border border-line shadow-2xs hover:bg-surface-2 flex items-center justify-center text-ink transition active:scale-95 shrink-0" 
       title="Kembali ke Koleksi Resep">
        <span class="material-symbols-outlined text-[20px]">arrow_back</span>
    </a>
    <div class="space-y-0.5 min-w-0">
        <h1 class="text-h-lg font-black tracking-tight text-ink truncate">Hasil Formulasi Ransum</h1>
        <p class="text-xs text-muted">{{ $ration->created_at->translatedFormat('d F Y, H:i') }} WIB · Acuan: <span class="font-semibold text-ink">{{ $ration->namaStandar() }}</span></p>
    </div>
</div>

@if (! $ration->standard)
    <div class="mt-4 p-3.5 rounded-2xl bg-surface border border-line text-ink flex items-start gap-2.5 text-xs">
        <span class="material-symbols-outlined text-[20px] text-muted shrink-0 mt-0.5">inventory_2</span>
        <div class="leading-relaxed">
            <span>Acuan asal resep ini (<strong>{{ $ration->namaStandar() }}</strong>) sudah dihapus dari katalog aktif. Takaran bahan dan hasil gizi tersimpan tetap utuh; jika menekan <strong>Hitung Ulang</strong>, sistem akan memakai acuan aktif yang tersedia.</span>
        </div>
    </div>
@endif

@if (!empty($unconstrainedParams))
    <div class="mt-4 p-3.5 rounded-2xl bg-amber-50 border border-amber-300 text-amber-900 flex items-start gap-2.5 text-xs">
        <span class="material-symbols-outlined text-[20px] text-amber-700 shrink-0 mt-0.5">warning</span>
        <div class="space-y-1 leading-relaxed">
            <strong class="font-bold block">Peringatan Batasan Acuan Gizi</strong>
            <ul class="list-disc list-inside space-y-0.5">
                @foreach ($unconstrainedParams as $paramName)
                    <li>Parameter {{ $paramName }} tidak dibatasi oleh standar ini.</li>
                @endforeach
            </ul>
        </div>
    </div>
@endif

{{-- Peringatan Ambang Batas Aman & Gizi --}}
@if (!empty($keamanan['peringatan']))
    <div class="mt-4 space-y-2">
        @foreach ($keamanan['peringatan'] as $pk)
            <div class="p-3.5 rounded-2xl border flex items-start gap-2.5 text-xs {{ $pk['level'] === 'danger' ? 'bg-danger-soft/80 text-danger-ink border-danger/40' : ($pk['level'] === 'warning' ? 'bg-amber-soft/70 text-amber-ink border-amber/30' : 'bg-brand/10 text-brand-dark border-brand/20') }}">
                <span class="material-symbols-outlined text-[20px] shrink-0 mt-0.5">{{ $pk['level'] === 'danger' ? 'gpp_bad' : ($pk['level'] === 'warning' ? 'warning' : 'verified_user') }}</span>
                <div class="leading-relaxed">
                    <strong class="font-bold">{{ $pk['judul'] }}:</strong> {{ $pk['pesan'] }}
                </div>
            </div>
        @endforeach
    </div>
@endif

@if ($ration->layak())
    {{-- 1. Hero Action Banner: Status Penerapan & Potong Stok Gudang --}}
    @php
        $stokDipakai = !empty($ration->hasil['stok_dipotong']);
        $tglDiterapkan = $stokDipakai && !empty($ration->hasil['applied_at']) 
            ? \Carbon\Carbon::parse($ration->hasil['applied_at'])->translatedFormat('d F Y, H:i') 
            : null;
        $totalSegarKg = $asFed['total_as_fed_kg'] ?? $ration->total_kg;
    @endphp

    @if (! $stokDipakai)
        <div class="mt-4 p-4 sm:p-5 rounded-2xl bg-gradient-to-r from-amber-500/10 via-amber-500/5 to-white border-2 border-amber-300 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="flex items-start gap-3.5">
                <div class="w-11 h-11 rounded-2xl bg-amber-500 text-white flex items-center justify-center shrink-0 shadow-xs">
                    <span class="material-symbols-outlined text-[24px]">blender</span>
                </div>
                <div class="space-y-1">
                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-amber-100 text-amber-900 border border-amber-300">
                            📋 Status: Simulasi Racikan
                        </span>
                        <span class="text-xs font-bold text-amber-900">Belum Mengurangi Stok Gudang</span>
                    </div>
                    <p class="text-xs text-muted leading-relaxed">
                        Formulasi ini siap dipakai. Jika Anda sudah menimbang & mengaduk pakan sebesar <strong class="text-ink font-bold">{{ Fmt::num($totalSegarKg, 1) }} kg</strong> di mixer kandang, terapkan ke gudang agar stok berkurang otomatis.
                    </p>
                </div>
            </div>

            <div class="shrink-0 pt-2 md:pt-0">
                <button type="button" 
                        onclick="document.getElementById('modalKonfirmasiTerapkan').showModal()"
                        class="w-full md:w-auto inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-full text-xs sm:text-sm font-black text-white bg-brand hover:bg-brand-dark transition shadow-md hover:shadow-lg active:scale-95 cursor-pointer">
                    <span class="material-symbols-outlined text-[18px]">inventory_2</span>
                    <span>Campur Pakan & Potong Stok</span>
                </button>
            </div>
        </div>
    @else
        <div class="mt-4 p-4 sm:p-5 rounded-2xl bg-gradient-to-r from-emerald-500/15 via-emerald-500/5 to-white border-2 border-emerald-300 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="flex items-start gap-3.5">
                <div class="w-11 h-11 rounded-2xl bg-emerald-600 text-white flex items-center justify-center shrink-0 shadow-xs">
                    <span class="material-symbols-outlined text-[24px]">check_circle</span>
                </div>
                <div class="space-y-1">
                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-emerald-100 text-emerald-900 border border-emerald-300">
                            ✅ Telah Diterapkan ke Gudang
                        </span>
                        <span class="text-xs font-bold text-emerald-900">{{ $tglDiterapkan ? "Tercatat pada {$tglDiterapkan} WIB" : 'Stok Terpotong' }}</span>
                    </div>
                    <p class="text-xs text-muted leading-relaxed">
                        Stok bahan baku di gudang telah otomatis dipotong sebesar <strong class="text-ink font-bold">{{ Fmt::num($totalSegarKg, 1) }} kg segar</strong> sesuai takaran timbangan nyata.
                    </p>
                </div>
            </div>

            <div class="shrink-0 pt-2 md:pt-0">
                <form method="POST" action="{{ route('ransum.batal-terapkan', $ration) }}" 
                      data-confirm="Batalkan pemotongan stok untuk racikan ini? Jumlah kilogram bahan akan dikembalikan utuh ke stok gudang pakan Anda." 
                      data-confirm-title="Batalkan Penerapan Racikan" 
                      data-confirm-btn="Ya, Kembalikan Stok">
                    @csrf
                    <button type="submit" class="w-full md:w-auto inline-flex items-center justify-center gap-1.5 px-4 py-2 rounded-full text-xs font-bold text-rose-700 hover:text-rose-800 bg-white hover:bg-rose-50 border border-rose-300 transition shadow-2xs active:scale-95 cursor-pointer">
                        <span class="material-symbols-outlined text-[16px]">undo</span>
                        <span>Batalkan Pemotongan Stok</span>
                    </button>
                </form>
            </div>
        </div>
    @endif

    {{-- 2. Baris 3 KPI Minimalis Sejajar (Total Biaya, Harga/kg, Kelayakan) --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-3.5 mt-4">
        {{-- KPI 1: Total Biaya Campuran --}}
        <div class="card p-4 sm:p-5 space-y-1 border border-line shadow-2xs">
            <span class="text-xs uppercase tracking-wider font-semibold text-muted block">Total Biaya Campuran</span>
            <div class="text-2xl sm:text-3xl font-bold text-ink tabular-nums tracking-tight">
                {{ Fmt::rp($ration->total_biaya) }}
            </div>
            <p class="text-xs text-muted pt-0.5">
                Volume: <strong class="text-ink font-semibold">{{ Fmt::num($ration->total_kg, 1) }} kg</strong>
                {{ $ration->jumlah_hari ? ' • '.$ration->jumlah_hari.' hari' : '' }}
            </p>
        </div>

        {{-- KPI 2: Harga per Kg Pakan --}}
        <div class="card p-4 sm:p-5 space-y-1 border border-line shadow-2xs">
            <span class="text-xs uppercase tracking-wider font-semibold text-muted block">Harga per Kg Pakan</span>
            <div class="text-2xl sm:text-3xl font-bold text-brand tabular-nums tracking-tight">
                {{ Fmt::rp($ration->biaya_per_kg) }} <span class="text-xs font-semibold text-muted font-sans">/ kg</span>
            </div>
            <p class="text-xs text-muted pt-0.5">
                Estimasi biaya bahan baku pakan riil
            </p>
        </div>

        {{-- KPI 3: Standar Nutrisi --}}
        <div class="card p-4 sm:p-5 space-y-1 border border-line shadow-2xs">
            <span class="text-xs uppercase tracking-wider font-semibold text-muted block">Kelayakan Nutrisi</span>
            <div class="flex items-baseline gap-2">
                <span class="text-2xl sm:text-3xl font-bold text-ink tabular-nums tracking-tight">100%</span>
                <span class="text-base sm:text-lg font-bold text-brand">{{ empty($unconstrainedParams) ? 'Sesuai Target' : 'Sesuai Batasan Aktif' }}</span>
            </div>
            <p class="text-xs text-muted pt-0.5 truncate">
                Acuan: <span class="font-medium text-ink">{{ $ration->namaStandar() }}</span>
            </p>
        </div>
    </div>

    {{-- 2. Konten Utama: 2 Kolom Bersih --}}
    <div class="mt-4 lg:grid lg:grid-cols-12 lg:gap-6 space-y-4 lg:space-y-0 items-start">
        {{-- KOLOM KIRI: Lembar Takaran Racikan (Tanpa nested boxes) --}}
        <div class="lg:col-span-7 xl:col-span-8 space-y-4">
            <section class="card space-y-4 p-4 sm:p-6 border border-line shadow-2xs">
                {{-- Header & Switcher --}}
                <div class="flex items-center justify-between flex-wrap gap-2">
                    <div>
                        <h2 class="text-h-sm font-bold text-ink">Komposisi Resep Pakan</h2>
                        <span class="text-xs text-muted">Bahan Kering (BK) & Segar (As-Fed)</span>
                    </div>

                    {{-- Switcher Pill Tabs --}}
                    <div class="inline-flex rounded-2xl md:rounded-full bg-[#e6eeea] md:bg-surface-2 p-1 text-xs font-bold border border-line/60 shadow-2xs" id="tabGroupResep">
                        <button type="button" 
                                id="btnTabAsFed" 
                                class="px-3.5 py-1.5 rounded-xl md:rounded-full transition bg-white shadow-2xs text-[#0f3d26] md:text-brand font-black md:font-bold select-none cursor-pointer">
                            <span class="sm:hidden">Segar (As-Fed)</span>
                            <span class="hidden sm:inline">Timbangan Segar (As-Fed)</span>
                        </button>
                        <button type="button" 
                                id="btnTabBk" 
                                class="px-3.5 py-1.5 rounded-xl md:rounded-full transition text-ink/75 md:text-muted hover:text-ink font-bold md:font-semibold select-none cursor-pointer">
                            <span class="sm:hidden">Kering (BK)</span>
                            <span class="hidden sm:inline">Formulasi Kering (BK)</span>
                        </button>
                    </div>
                </div>

                {{-- Visual Stacked Recipe Distribution Bar --}}
                <div class="space-y-2">
                    <div class="flex h-3 rounded-full overflow-hidden bg-surface-2 p-0.5 border border-line/60 shadow-2xs" aria-hidden="true">
                        @foreach ($baris as $i => $b)
                            <span style="width:{{ $b['persen'] }}%;background:{{ $warna[$i % count($warna)] }}" 
                                  class="h-full first:rounded-l-full last:rounded-r-full transition-all"
                                  title="{{ $b['nama'] }}: {{ Fmt::num($b['persen'], 1) }}%"></span>
                        @endforeach
                    </div>

                    {{-- Legend Micro-Pills --}}
                    <div class="flex flex-wrap items-center gap-2 text-xs text-muted pt-0.5">
                        @foreach ($baris as $i => $b)
                            <div class="inline-flex items-center gap-1.5 bg-surface/80 hover:bg-white px-2.5 py-0.5 rounded-full border border-line shadow-2xs transition-all text-[11px]">
                                <span class="w-2 h-2 rounded-full shrink-0" style="background:{{ $warna[$i % count($warna)] }}"></span>
                                <span class="text-ink font-semibold">{{ $b['nama'] }}</span>
                                <span class="text-brand tabular-nums font-bold">{{ Fmt::num($b['persen'], 1) }}%</span>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Tab 1: Timbangan Segar (As-Fed) --}}
                <div id="tabAsFedContent" class="space-y-3 pt-1">
                    <div class="overflow-x-auto rounded-xl border border-line">
                        <table class="w-full table-fixed text-xs">
                            <colgroup>
                                <col class="w-[32%]">
                                <col class="w-[14%]">
                                <col class="w-[16%]">
                                <col class="w-[18%]">
                                <col class="w-[20%]">
                            </colgroup>
                            <thead>
                                <tr class="text-muted bg-surface/40 border-b border-line text-[11px] uppercase tracking-wider font-semibold">
                                    <th class="py-2.5 px-3 text-left">Bahan Pakan</th>
                                    <th class="py-2.5 px-2 text-center">BK</th>
                                    <th class="py-2.5 px-2 text-center">% Segar</th>
                                    <th class="py-2.5 px-2 text-center font-bold text-brand">Kg Segar</th>
                                    <th class="py-2.5 px-3 text-center">Biaya (Rp)</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-line/40">
                            @foreach ($asFed['items'] as $i => $af)
                                <tr class="hover:bg-surface/20 transition-colors">
                                    <td class="py-2.5 px-3 font-semibold text-ink text-left truncate">
                                        <div class="flex items-center gap-2">
                                            <span class="w-2 h-2 rounded-full shrink-0" style="background:{{ $warna[$i % count($warna)] }}"></span>
                                            <span class="truncate">{{ $af['nama'] }}</span>
                                        </div>
                                    </td>
                                    <td class="py-2.5 px-2 text-center tabular-nums text-muted">{{ Fmt::num($af['bk'], 0) }}%</td>
                                    <td class="py-2.5 px-2 text-center tabular-nums text-ink font-semibold">{{ Fmt::num($af['persen_as_fed'], 1) }}%</td>
                                    <td class="py-2.5 px-2 text-center tabular-nums font-bold text-brand text-xs sm:text-sm">{{ Fmt::num($af['kg_as_fed'], 2) }} kg</td>
                                    <td class="py-2.5 px-3 text-center tabular-nums text-ink font-medium">{{ Fmt::rp($af['biaya']) }}</td>
                                </tr>
                            @endforeach
                            <tr class="bg-surface-2/60 border-t-2 border-line font-bold text-ink">
                                <td class="py-2.5 px-3 text-left" colspan="2">Total Timbangan Segar</td>
                                <td class="py-2.5 px-2 text-center tabular-nums font-bold">100%</td>
                                <td class="py-2.5 px-2 text-center tabular-nums font-bold text-brand text-xs sm:text-sm">{{ Fmt::num($asFed['total_as_fed_kg'], 2) }} kg</td>
                                <td class="py-2.5 px-3 text-center tabular-nums font-bold">{{ Fmt::rp($asFed['total_biaya']) }}</td>
                            </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="flex items-center justify-between flex-wrap gap-2 text-[11px] text-muted pt-0.5 min-h-[26px]">
                        <span>*Timbangan Segar (As-Fed) memperhitungkan kadar air alami bahan untuk penakaran riil di gudang.</span>
                        <span>Biaya riil: <strong class="text-ink tabular-nums font-bold">{{ Fmt::rp($asFed['biaya_per_kg_as_fed']) }} / kg</strong></span>
                    </div>
                </div>

                {{-- Tab 2: Formulasi Kering (BK) --}}
                <div id="tabBkContent" class="hidden space-y-3 pt-1">
                    <div class="overflow-x-auto rounded-xl border border-line">
                        <table class="w-full table-fixed text-xs">
                            <colgroup>
                                <col class="w-[32%]">
                                <col class="w-[14%]">
                                <col class="w-[16%]">
                                <col class="w-[18%]">
                                <col class="w-[20%]">
                            </colgroup>
                            <thead>
                                <tr class="text-muted bg-surface/40 border-b border-line text-[11px] uppercase tracking-wider font-semibold">
                                    <th class="py-2.5 px-3 text-left">Bahan Pakan</th>
                                    <th class="py-2.5 px-2 text-center">BK</th>
                                    <th class="py-2.5 px-2 text-center">% BK</th>
                                    <th class="py-2.5 px-2 text-center font-bold text-brand">Kg BK</th>
                                    <th class="py-2.5 px-3 text-center">Biaya (Rp)</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-line/40">
                            @foreach ($baris as $i => $b)
                                <tr class="hover:bg-surface/20 transition-colors">
                                    <td class="py-2.5 px-3 font-semibold text-ink text-left truncate">
                                        <div class="flex items-center gap-2">
                                            <span class="w-2 h-2 rounded-full shrink-0" style="background:{{ $warna[$i % count($warna)] }}"></span>
                                            <span class="truncate">{{ $b['nama'] }}</span>
                                        </div>
                                    </td>
                                    <td class="py-2.5 px-2 text-center tabular-nums text-muted">{{ Fmt::num($b['bk'], 0) }}%</td>
                                    <td class="py-2.5 px-2 text-center tabular-nums text-ink font-semibold">{{ Fmt::num($b['persen'], 1) }}%</td>
                                    <td class="py-2.5 px-2 text-center tabular-nums font-bold text-brand text-xs sm:text-sm">{{ Fmt::num($b['kg'], 2) }} kg</td>
                                    <td class="py-2.5 px-3 text-center tabular-nums text-ink font-medium">{{ Fmt::rp($b['biaya']) }}</td>
                                </tr>
                            @endforeach
                            <tr class="bg-surface-2/60 border-t-2 border-line font-bold text-ink">
                                <td class="py-2.5 px-3 text-left" colspan="2">Total Formulasi</td>
                                <td class="py-2.5 px-2 text-center tabular-nums font-bold">100%</td>
                                <td class="py-2.5 px-2 text-center tabular-nums font-bold text-brand text-xs sm:text-sm">{{ Fmt::num($skala, 2) }} kg</td>
                                <td class="py-2.5 px-3 text-center tabular-nums font-bold">{{ Fmt::rp($baris->sum('biaya')) }}</td>
                            </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="flex items-center justify-between flex-wrap gap-2 text-[11px] text-muted pt-0.5 min-h-[26px]">
                        <span>*Formulasi Kering (BK) digunakan untuk standardisasi formula laboratorium sebelum dikonversi ke pakan segar.</span>
                    </div>
                </div>
            </section>
        </div>

        {{-- KOLOM KANAN: Panel Terpadu Spesifikasi Nutrisi & Ketersediaan Gudang --}}
        <div class="lg:col-span-5 xl:col-span-4 space-y-4">
            <section class="card p-4 sm:p-5 space-y-5 border border-line shadow-2xs">
                {{-- Bagian 1: Spesifikasi Nutrisi --}}
                <div class="space-y-3">
                    <div class="flex items-center justify-between gap-2 pb-1.5 border-b border-line/60">
                        <h2 class="text-xs font-bold text-ink uppercase tracking-wider shrink-0">Spesifikasi Nutrisi</h2>
                        <span class="text-xs font-medium text-muted truncate">
                            {{ $ration->standard?->labelOtoritas() ?? 'Acuan Tersimpan' }}
                        </span>
                    </div>

                    <div class="space-y-2.5">
                        @foreach ($ration->hasil['gizi'] ?? [] as $g)
                            <div class="flex items-center justify-between text-xs">
                                <div class="min-w-0 pr-2">
                                    <span class="font-bold text-ink block truncate">{{ $g['label'] }}</span>
                                    <span class="text-[10px] text-muted">Target: {{ $g['target'] }}</span>
                                </div>
                                <span class="tabular-nums font-bold shrink-0 {{ $g['ok'] ? 'text-ink' : 'text-danger' }}">
                                    {{ Fmt::num($g['nilai'], 2) }}{{ $g['satuan'] }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Bagian 2: Kesiapan Stok Gudang --}}
                <div class="space-y-3">
                    <div class="py-2.5 border-y border-line/60 flex items-center justify-between">
                        <h2 class="text-xs font-bold text-ink uppercase tracking-wider">Sisa Stok di Gudang</h2>
                        <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200">
                            Cukup
                        </span>
                    </div>

                    <div class="space-y-1 text-xs">
                        @foreach ($ration->hasil['sisa_stok'] ?? [] as $s)
                            <div class="flex justify-between items-center py-2 border-b border-line/20 last:border-0">
                                <span class="text-muted font-medium">{{ $s['nama'] }}</span>
                                <span class="tabular-nums font-medium text-ink">sisa {{ Fmt::num($s['sisa_kg'], 1) }} kg</span>
                            </div>
                        @endforeach
                    </div>

                    @include('ransum._belanja', ['belanja' => $ration->hasil['belanja'] ?? []])
                </div>
            </section>
        </div>
    </div>
@else
    {{-- Tidak layak: pesan ramah, bukan error mentah --}}
    <section class="mt-4 card space-y-4 border-danger/30 p-5 sm:p-6">
        <div class="flex items-center gap-3">
            <span class="material-symbols-outlined icon-fill text-danger text-[32px]">sentiment_neutral</span>
            <div>
                <h2 class="text-h-md font-bold text-ink">Ransum Belum Bisa Disusun</h2>
                <p class="text-xs text-muted">Kombinasi bahan pakan dan batasan nutrisi saat ini belum menemukan titik optimal.</p>
            </div>
        </div>

        <ul class="space-y-2">
            @foreach ($ration->pesan ?? [] as $p)
                <li class="p-3 rounded-2xl bg-danger-soft/50 border border-danger/20 text-danger-ink flex items-start gap-2 text-xs">
                    <span class="material-symbols-outlined text-[18px] shrink-0 mt-0.5">priority_high</span>
                    <span>{{ $p }}</span>
                </li>
            @endforeach
        </ul>

        <div class="p-3.5 rounded-2xl bg-brand/5 border border-brand/20 text-primary flex items-start gap-2.5 text-xs">
            <span class="material-symbols-outlined text-[20px] text-brand shrink-0 mt-0.5">lightbulb</span>
            <div class="leading-relaxed">
                <strong>Saran Solusi:</strong> Tambah variasi bahan pakan (misalnya sumber protein konsentrat atau kalsium tepung kulit kerang), tambah stok gudang, atau kurangi target volume adukan. Lalu tekan Hitung Ulang.
            </div>
        </div>

        @if (!empty($ration->hasil['ideal']))
            <div class="p-4 rounded-2xl bg-surface border border-line space-y-2.5">
                <h3 class="font-bold text-xs text-ink">Jika Anda menambah stok/bahan, resep yang mungkin:</h3>
                <ul class="text-xs space-y-1.5 divide-y divide-line/60">
                    @foreach ($ration->hasil['ideal']['items'] as $it)
                        <li class="flex justify-between items-center pt-1.5 first:pt-0">
                            <span class="font-medium text-ink">{{ $it['nama'] }} ({{ Fmt::num($it['persen'], 1) }}%)</span>
                            <span class="tabular-nums font-bold text-ink">{{ Fmt::num($it['kg'], 2) }} kg</span>
                        </li>
                    @endforeach
                </ul>
                <p class="text-[11px] text-muted pt-1">Estimasi biaya <strong class="text-ink tabular-nums font-bold">{{ Fmt::rp($ration->hasil['ideal']['total_biaya']) }}</strong> bila semua bahan tersedia.</p>
            </div>
            @include('ransum._belanja', ['belanja' => $ration->hasil['belanja'] ?? []])
        @endif

        <div class="grid grid-cols-2 gap-3 pt-2">
            <a href="{{ route('ransum.create') }}" class="btn-outline btn-sm rounded-full flex items-center justify-center gap-1.5 py-2.5">
                <span class="material-symbols-outlined text-[16px]">tune</span>
                <span>Ubah Bahan & Stok</span>
            </a>
            <a href="{{ route('acuan.index') }}" class="btn-outline btn-sm rounded-full flex items-center justify-center gap-1.5 py-2.5">
                <span class="material-symbols-outlined text-[16px]">fact_check</span>
                <span>Ubah Acuan Gizi</span>
            </a>
        </div>
    </section>
@endif

<p class="text-[11px] text-muted flex items-center gap-1.5 mt-4">
    <span class="material-symbols-outlined text-[16px]">info</span>
    <span>Potensi hemat bergantung pada harga dan stok riil Anda. Biaya di atas merupakan estimasi linear programming.</span>
</p>

{{-- Penjelasan AI --}}
@if ($ration->penjelasan)
    <section id="penjelasan" class="mt-4 card p-4 sm:p-5 space-y-3 bg-white border border-line shadow-2xs rounded-2xl relative transition-all">
        <div class="flex items-center justify-between pb-2 border-b border-line/50">
            <div class="flex items-center gap-2">
                <span class="w-6 h-6 rounded-full bg-amber-50 text-amber-600 flex items-center justify-center shrink-0">
                    <span class="material-symbols-outlined text-[15px]">auto_awesome</span>
                </span>
                <h2 class="text-xs font-bold text-ink uppercase tracking-wider">Analisis & Penjelasan Ahli Ransum</h2>
            </div>
            <button type="button" 
                    onclick="document.getElementById('penjelasan').style.display='none'" 
                    class="w-6 h-6 rounded-full hover:bg-surface-2 text-muted hover:text-ink flex items-center justify-center transition cursor-pointer" 
                    title="Tutup penjelasan">
                <span class="material-symbols-outlined text-[16px]">close</span>
            </button>
        </div>
        <p class="whitespace-pre-line text-xs text-ink/85 leading-relaxed">{{ $ration->penjelasan }}</p>
        <p class="text-[11px] text-muted border-t border-line/40 pt-2 flex items-center gap-1.5">
            <span class="material-symbols-outlined text-[14px]">info</span>
            <span>Penjelasan memakai angka riil dari optimasi linear programming. Angka tidak dihitung oleh AI.</span>
        </p>
    </section>
@endif

{{-- Bottom Action Buttons --}}
<div class="mt-6 grid grid-cols-1 sm:grid-cols-3 gap-3 pb-8">
    <form method="POST" action="{{ route('ransum.jelaskan', $ration) }}" class="w-full">
        @csrf
        <button id="btn-jelaskan" 
                class="btn-amber w-full rounded-full py-3 flex items-center justify-center gap-1.5 text-xs font-bold shadow-2xs hover:shadow-md transition active:scale-95 cursor-pointer">
            <span class="material-symbols-outlined text-[18px]">auto_awesome</span>
            <span>Jelaskan hasil ini</span>
        </button>
    </form>
    <form method="POST" action="{{ route('ransum.hitung-ulang', $ration) }}" class="w-full">
        @csrf
        <button id="btn-hitung-ulang" 
                class="btn-primary w-full rounded-full py-3 flex items-center justify-center gap-1.5 text-xs font-bold shadow-2xs hover:shadow-brand/20 transition active:scale-95 cursor-pointer">
            <span class="material-symbols-outlined text-[18px]">refresh</span>
            <span>Hitung Ulang</span>
        </button>
    </form>
    <a href="{{ route('ransum.create') }}" 
       class="btn-outline w-full rounded-full py-3 flex items-center justify-center gap-1.5 text-xs font-bold shadow-2xs hover:bg-surface-2 transition active:scale-95 text-center">
        <span class="material-symbols-outlined text-[18px]">edit</span>
        <span>Ubah harga/stok</span>
    </a>
</div>

{{-- MODAL KONFIRMASI CAMPUR PAKAN & POTONG STOK GUDANG --}}
<dialog id="modalKonfirmasiTerapkan" class="rounded-2xl p-0 backdrop:bg-black/50 backdrop:backdrop-blur-xs shadow-2xl border border-line w-full max-w-lg overflow-hidden">
    <div class="bg-white p-5 sm:p-6 space-y-4">
        <div class="flex items-center justify-between pb-3 border-b border-line">
            <div class="flex items-center gap-2.5">
                <div class="w-10 h-10 rounded-xl bg-brand/10 text-brand grid place-items-center shrink-0">
                    <span class="material-symbols-outlined text-[22px]">blender</span>
                </div>
                <div>
                    <h3 class="text-sm sm:text-base font-bold text-ink">Konfirmasi Campur Pakan</h3>
                    <p class="text-[11px] text-muted">Pemotongan stok fisik di gudang pakan.</p>
                </div>
            </div>
            <button type="button" onclick="document.getElementById('modalKonfirmasiTerapkan').close()" class="p-1 rounded-lg text-muted hover:text-ink hover:bg-surface cursor-pointer">
                <span class="material-symbols-outlined text-[18px]">close</span>
            </button>
        </div>

        <div class="space-y-3 text-xs">
            <div class="p-3.5 rounded-xl bg-amber-50 border border-amber-200 text-amber-900 leading-relaxed space-y-1">
                <strong class="font-bold block flex items-center gap-1.5">
                    <span class="material-symbols-outlined text-[16px] text-amber-700">info</span>
                    <span>Apakah pakan ini sudah selesai diaduk di mixer?</span>
                </strong>
                <p class="text-[11px] text-amber-800">
                    Stok bahan baku di gudang Anda akan otomatis dipotong sesuai timbangan riil pakan segar di bawah ini:
                </p>
            </div>

            {{-- Tabel Rincian Potongan Stok --}}
            <div class="rounded-xl border border-line overflow-hidden">
                <table class="w-full text-left text-xs divide-y divide-line">
                    <thead class="bg-surface font-bold text-muted">
                        <tr>
                            <th class="py-2.5 px-3">Bahan Pakan</th>
                            <th class="py-2.5 px-3 text-right">Takaran Potong</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line bg-white">
                        @foreach ($asFed['items'] ?? $baris as $b)
                            <tr>
                                <td class="py-2 px-3 font-semibold text-ink">{{ $b['nama'] ?? '' }}</td>
                                <td class="py-2 px-3 text-right font-black text-brand tabular-nums">
                                    {{ Fmt::num($b['kg_as_fed'] ?? $b['kg'] ?? 0, 2) }} kg
                                </td>
                            </tr>
                        @endforeach
                        <tr class="bg-surface-2/60 font-black">
                            <td class="py-2.5 px-3 text-ink">Total Pakan Segar:</td>
                            <td class="py-2.5 px-3 text-right text-brand tabular-nums text-sm">
                                {{ Fmt::num($asFed['total_as_fed_kg'] ?? $ration->total_kg, 2) }} kg
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <form method="POST" action="{{ route('ransum.terapkan', $ration) }}">
            @csrf
            <div class="flex items-center justify-end gap-2 pt-3 border-t border-line">
                <button type="button" 
                        onclick="document.getElementById('modalKonfirmasiTerapkan').close()" 
                        class="px-4 py-2 rounded-full text-xs font-semibold text-ink bg-white hover:bg-surface border border-line transition shadow-2xs cursor-pointer">
                    Batal / Nanti Dulu
                </button>
                <button type="submit" 
                        class="px-5 py-2.5 rounded-full text-xs font-black text-white bg-brand hover:bg-brand-dark transition shadow-md hover:shadow-lg active:scale-95 cursor-pointer flex items-center gap-1.5">
                    <span class="material-symbols-outlined text-[16px]">check_circle</span>
                    <span>Ya, Pakan Sudah Diaduk (Potong Stok)</span>
                </button>
            </div>
        </form>
    </div>
</dialog>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const btnAsFed = document.getElementById('btnTabAsFed');
        const btnBk = document.getElementById('btnTabBk');
        const contentAsFed = document.getElementById('tabAsFedContent');
        const contentBk = document.getElementById('tabBkContent');

        if (btnAsFed && btnBk && contentAsFed && contentBk) {
            btnAsFed.addEventListener('click', () => {
                contentAsFed.classList.remove('hidden');
                contentBk.classList.add('hidden');
                btnAsFed.className = 'px-3.5 py-1.5 rounded-full transition bg-white shadow-2xs text-brand font-bold select-none cursor-pointer';
                btnBk.className = 'px-3.5 py-1.5 rounded-full transition text-muted hover:text-ink font-semibold select-none cursor-pointer';
            });

            btnBk.addEventListener('click', () => {
                contentBk.classList.remove('hidden');
                contentAsFed.classList.add('hidden');
                btnBk.className = 'px-3.5 py-1.5 rounded-full transition bg-white shadow-2xs text-brand font-bold select-none cursor-pointer';
                btnAsFed.className = 'px-3.5 py-1.5 rounded-full transition text-muted hover:text-ink font-semibold select-none cursor-pointer';
            });
        }
    });
</script>
@endsection
