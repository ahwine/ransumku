@extends('layouts.app')
@section('judul', 'Hasil Ransum')
@section('isi')
@use('App\Support\Fmt')
@php
    $warna = ['#2F6B3A', '#F2B134', '#145224', '#D64545', '#7e5700', '#96d69a', '#b22b2e'];
@endphp

{{-- Header Halaman Bersih --}}
<div class="flex items-center justify-between gap-3 flex-wrap">
    <div class="flex items-center gap-3">
        <a href="{{ route('ransum.index') }}" 
           class="w-10 h-10 rounded-full bg-white border border-line shadow-2xs hover:bg-surface-2 flex items-center justify-center text-ink transition active:scale-95 shrink-0" 
           title="Kembali ke Koleksi Resep">
            <span class="material-symbols-outlined text-[20px]">arrow_back</span>
        </a>
        <div class="space-y-0.5 min-w-0">
            <h1 class="text-h-lg font-black tracking-tight text-ink truncate">Hasil Formulasi Ransum</h1>
            <p class="text-xs text-muted">{{ $ration->created_at->translatedFormat('d F Y, H:i') }} WIB · Standar Mutu: <span class="font-semibold text-ink">{{ $ration->namaStandar() }}</span></p>
        </div>
    </div>
    
    @if ($ration->layak())
        @php
            $stokDipakai = !empty($ration->hasil['stok_dipotong']);
        @endphp
        <div class="flex items-center gap-2">
            @if ($stokDipakai)
                <span class="text-xs font-bold text-emerald-600">
                    Stok Gudang Telah Dipotong
                </span>
            @else
                <span class="text-xs font-semibold text-muted">
                    Simulasi (Belum Dipotong)
                </span>
            @endif
        </div>
    @endif
</div>

@if (!empty($unconstrainedParams))
    <div class="sr-only">
        @foreach ($unconstrainedParams as $param)
            <span>Parameter {{ $param }} tidak dibatasi oleh standar ini.</span>
        @endforeach
    </div>
@endif

@if ($ration->layak())
    @php
        $stokDipakai = !empty($ration->hasil['stok_dipotong']);
        $tglDiterapkan = $stokDipakai && !empty($ration->hasil['applied_at']) 
            ? \Carbon\Carbon::parse($ration->hasil['applied_at'])->translatedFormat('d F Y, H:i') 
            : null;
        $totalSegarKg = $asFed['total_as_fed_kg'] ?? $ration->total_kg;
    @endphp

    {{-- 2. Baris 3 KPI Minimalis Sejajar (Total Biaya, Harga/kg, Kelayakan) --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-3.5 mt-4">
        {{-- KPI 1: Total Biaya Campuran --}}
        <div class="card p-4 sm:p-5 space-y-1 border border-line shadow-2xs">
            <span class="text-xs uppercase tracking-wider font-semibold text-muted block">Total Biaya Campuran</span>
            <div class="text-2xl sm:text-3xl font-bold text-ink tabular-nums tracking-tight">
                {{ Fmt::rp($ration->total_biaya) }}
            </div>
            <p class="text-xs text-muted pt-0.5">
                Kebutuhan: <strong class="text-ink font-semibold">{{ $ration->jumlah_hari ? $ration->jumlah_hari.' hari • ' : '' }}{{ Fmt::num($totalSegarKg, 1) }} kg segar</strong> ({{ Fmt::num($ration->total_kg, 0) }} kg BK)
            </p>
        </div>

        {{-- KPI 2: Harga per Kg Pakan --}}
        <div class="card p-4 sm:p-5 space-y-1 border border-line shadow-2xs">
            <span class="text-xs uppercase tracking-wider font-semibold text-muted block">Harga per Kg Pakan</span>
            <div class="text-2xl sm:text-3xl font-bold text-brand tabular-nums tracking-tight">
                {{ Fmt::rp($ration->biaya_per_kg) }} <span class="text-xs font-semibold text-muted font-sans">/ kg</span>
            </div>
            <p class="text-xs text-muted pt-0.5">
                Biaya riil pakan segar: <strong class="text-ink font-semibold">{{ Fmt::rp($asFed['biaya_per_kg_as_fed'] ?? $ration->biaya_per_kg) }} / kg</strong>
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

                {{-- Tab 1: Timbangan Segar (As-Fed) --}}
                <div id="tabAsFedContent" class="space-y-3 pt-1">
                    {{-- Visual Stacked Recipe Distribution Bar (As-Fed) --}}
                    <div class="space-y-2 pb-1">
                        <div class="flex h-3 rounded-full overflow-hidden bg-surface-2 p-0.5 border border-line/60 shadow-2xs" aria-hidden="true">
                            @foreach ($asFed['items'] as $i => $af)
                                <span style="width:{{ $af['persen_as_fed'] }}%;background:{{ $warna[$i % count($warna)] }}" 
                                      class="h-full first:rounded-l-full last:rounded-r-full transition-all"
                                      title="{{ $af['nama'] }}: {{ Fmt::num($af['persen_as_fed'], 1) }}%"></span>
                            @endforeach
                        </div>

                        <div class="flex flex-wrap items-center gap-x-4 gap-y-1.5 text-xs text-muted pt-0.5">
                            @foreach ($asFed['items'] as $i => $af)
                                <div class="inline-flex items-center gap-1.5 text-[11px]">
                                    <span class="w-2 h-2 rounded-full shrink-0" style="background:{{ $warna[$i % count($warna)] }}"></span>
                                    <span class="text-ink font-semibold">{{ $af['nama'] }}</span>
                                    <span class="text-brand tabular-nums font-bold">{{ Fmt::num($af['persen_as_fed'], 1) }}%</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
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
                    {{-- Visual Stacked Recipe Distribution Bar (BK) --}}
                    <div class="space-y-2 pb-1">
                        <div class="flex h-3 rounded-full overflow-hidden bg-surface-2 p-0.5 border border-line/60 shadow-2xs" aria-hidden="true">
                            @foreach ($baris as $i => $b)
                                <span style="width:{{ $b['persen'] }}%;background:{{ $warna[$i % count($warna)] }}" 
                                      class="h-full first:rounded-l-full last:rounded-r-full transition-all"
                                      title="{{ $b['nama'] }}: {{ Fmt::num($b['persen'], 1) }}%"></span>
                            @endforeach
                        </div>

                        <div class="flex flex-wrap items-center gap-x-4 gap-y-1.5 text-xs text-muted pt-0.5">
                            @foreach ($baris as $i => $b)
                                <div class="inline-flex items-center gap-1.5 text-[11px]">
                                    <span class="w-2 h-2 rounded-full shrink-0" style="background:{{ $warna[$i % count($warna)] }}"></span>
                                    <span class="text-ink font-semibold">{{ $b['nama'] }}</span>
                                    <span class="text-brand tabular-nums font-bold">{{ Fmt::num($b['persen'], 1) }}%</span>
                                </div>
                            @endforeach
                        </div>
                    </div>

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
                        <h2 class="text-xs font-bold text-ink uppercase tracking-wider">Ketersediaan Stok Gudang</h2>
                        @if ($stokDipakai)
                            <span class="text-xs font-bold text-emerald-600">
                                Stok Telah Dipotong
                            </span>
                        @elseif ($bisaDiterapkan)
                            <span class="text-xs font-bold text-emerald-600">
                                Stok Cukup
                            </span>
                        @else
                            <span class="text-xs font-bold text-rose-600">
                                Stok Kurang
                            </span>
                        @endif
                    </div>

                    @if (! $stokDipakai && ! empty($stokKurangList))
                        <div class="p-3 rounded-xl bg-rose-50/60 border border-rose-200 space-y-1.5">
                            <span class="text-[11px] font-bold text-rose-900 block flex items-center gap-1">
                                <span class="material-symbols-outlined text-[15px] text-rose-700">warning</span>
                                <span>Bahan kurang untuk adukan ini:</span>
                            </span>
                            @foreach ($stokKurangList as $sk)
                                <div class="flex justify-between items-center text-xs text-rose-800">
                                    <span>{{ $sk['nama'] }}</span>
                                    <span class="font-bold tabular-nums">kurang {{ Fmt::num($sk['kurang'], 1) }} kg</span>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    <div class="space-y-1 text-xs">
                        @foreach ($asFed['items'] ?? $baris as $b)
                            @php
                                $fi = $farm->farmIngredients()->where('ingredient_id', $b['id'] ?? null)->first();
                                $stokGudangReal = (float) ($fi?->stok_kg ?? 0);
                                $kgDibutuhkanSegar = (float) ($b['kg_as_fed'] ?? $b['kg'] ?? 0);
                                $sisaTampil = $stokDipakai ? $stokGudangReal : max(0.0, round($stokGudangReal - $kgDibutuhkanSegar, 2));
                            @endphp
                            <div class="flex justify-between items-center py-2 border-b border-line/20 last:border-0">
                                <span class="text-muted font-medium">{{ $b['nama'] }}</span>
                                <span class="tabular-nums font-medium text-ink">sisa {{ Fmt::num($sisaTampil, 1) }} kg</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>
        </div>
    </div>
@else
    {{-- Tidak layak: Tidak disimpan ke Koleksi Resep + Penyesuaian Hari Cepat + Tabel Belanja Bersih --}}
    <div class="mt-4 space-y-4">
        <section class="card p-5 sm:p-6 space-y-4 border border-line shadow-2xs">
            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 pb-4 border-b border-line/60">
                <div class="flex items-start gap-3">
                    <span class="material-symbols-outlined text-rose-600 text-[28px] shrink-0 mt-0.5">error</span>
                    <div class="space-y-0.5">
                        <div class="flex items-center gap-2.5 flex-wrap">
                            <h2 class="text-h-md font-bold text-ink">Ransum Belum Bisa Disusun</h2>
                            <span class="text-xs font-semibold text-muted">· Tidak disimpan ke Koleksi Resep</span>
                        </div>
                        <p class="text-xs text-muted">
                            Stok bahan di gudang saat ini belum mencukupi untuk menyusun kebutuhan
                            <strong class="text-ink">{{ $ration->jumlah_hari ? $ration->jumlah_hari.' hari ('.Fmt::num($ration->total_kg, 0).' kg)' : Fmt::num($ration->total_kg, 0).' kg' }}</strong>.
                        </p>
                    </div>
                </div>

                {{-- Form Cepat Ubah Jumlah Hari --}}
                <form method="POST" action="{{ route('ransum.hitung-ulang', $ration) }}" class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2 bg-surface/70 p-2.5 rounded-2xl border border-line shrink-0">
                    @csrf
                    <div class="flex items-center gap-2 px-1">
                        <label for="ubah_hari_cepat" class="text-xs font-bold text-ink whitespace-nowrap">Ubah Kebutuhan:</label>
                        <div class="relative flex items-center w-24">
                            <input id="ubah_hari_cepat"
                                   name="jumlah_hari"
                                   type="number"
                                   min="1"
                                   max="60"
                                   value="{{ $hariMaksStok ?? max(1, ($ration->jumlah_hari ?: 3) - 1) }}"
                                   class="field !min-h-9 !py-1 !pr-11 text-xs font-black text-ink bg-white">
                            <span class="absolute right-2.5 text-[11px] font-bold text-muted pointer-events-none">hari</span>
                        </div>
                    </div>
                    <button type="submit" class="px-4 py-2 rounded-xl bg-brand hover:bg-brand-dark text-white text-xs font-bold transition shadow-2xs cursor-pointer whitespace-nowrap">
                        Hitung Ulang Hari
                    </button>
                </form>
            </div>

            @if (!empty($hariMaksStok))
                <p class="text-xs font-semibold text-emerald-700 flex items-center gap-1.5">
                    <span class="material-symbols-outlined text-[16px]">check_circle</span>
                    <span>Stok gudang Anda saat ini masih mencukupi untuk adukan <strong>{{ $hariMaksStok }} hari</strong>. Klik tombol <strong>Hitung Ulang Hari</strong> di atas untuk langsung menyusunnya.</span>
                </p>
            @endif

            <ul class="space-y-2">
                @foreach ($ration->pesan ?? [] as $p)
                    <li class="p-3 rounded-xl bg-rose-50/60 border border-rose-200/80 text-rose-900 flex items-start gap-2 text-xs">
                        <span class="material-symbols-outlined text-[17px] text-rose-600 shrink-0 mt-0.5">info</span>
                        <span>{{ $p }}</span>
                    </li>
                @endforeach
            </ul>
        </section>

        @if (!empty($ration->hasil['ideal']))
            <div class="lg:grid lg:grid-cols-12 lg:gap-6 space-y-4 lg:space-y-0 items-start">
                {{-- Kolom Kiri: Tabel Rekomendasi Belanja Tambahan --}}
                <div class="lg:col-span-7">
                    <section class="card p-4 sm:p-5 border border-line shadow-2xs">
                        @include('ransum._belanja', ['belanja' => $ration->hasil['belanja'] ?? []])
                    </section>
                </div>

                {{-- Kolom Kanan: Tabel Simulasi Resep Jika Stok Terpenuhi --}}
                <div class="lg:col-span-5">
                    <section class="card p-4 sm:p-5 space-y-3 border border-line shadow-2xs">
                        <div>
                            <h3 class="text-sm font-bold text-ink">Simulasi Resep ({{ $ration->jumlah_hari ? $ration->jumlah_hari.' Hari' : Fmt::num($ration->total_kg, 0).' kg' }})</h3>
                            <p class="text-xs text-muted">Komposisi pakan segar jika seluruh stok bahan terpenuhi.</p>
                        </div>

                        <div class="overflow-x-auto rounded-xl border border-line bg-white">
                            <table class="w-full text-xs">
                                <thead>
                                    <tr class="text-muted bg-surface/40 border-b border-line text-[11px] uppercase tracking-wider font-semibold">
                                        <th class="py-2.5 px-3 text-left">Bahan Pakan</th>
                                        <th class="py-2.5 px-2 text-center">Proporsi</th>
                                        <th class="py-2.5 px-3 text-right">Takaran</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-line/50">
                                    @foreach ($ration->hasil['ideal']['items'] as $it)
                                        <tr>
                                            <td class="py-2.5 px-3 font-semibold text-ink">{{ $it['nama'] }}</td>
                                            <td class="py-2.5 px-2 text-center tabular-nums text-muted">{{ Fmt::num($it['persen'], 1) }}%</td>
                                            <td class="py-2.5 px-3 text-right tabular-nums font-bold text-ink">{{ Fmt::num($it['kg'], 2) }} kg</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr class="bg-surface-2/60 border-t-2 border-line font-bold text-ink">
                                        <td class="py-2.5 px-3 text-left" colspan="2">Estimasi Biaya Total</td>
                                        <td class="py-2.5 px-3 text-right tabular-nums text-brand font-black">
                                            {{ Fmt::rp($ration->hasil['ideal']['total_biaya']) }}
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </section>
                </div>
            </div>
        @endif
    </div>
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

{{-- Bottom Action Bar: Bersih, Tegas, Tanpa Bloat --}}
<div class="mt-8 pt-5 border-t border-line flex flex-col sm:flex-row items-center justify-between gap-4 pb-12">
    <a href="{{ route('ransum.create') }}" 
       class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-full border border-line bg-white hover:bg-surface-2 text-ink text-xs font-bold transition shadow-2xs active:scale-95 cursor-pointer">
        <span class="material-symbols-outlined text-[17px]">arrow_back</span>
        <span>Racik Resep Lain</span>
    </a>

    <div class="w-full sm:w-auto flex flex-col sm:flex-row items-center gap-3">
        @if ($ration->layak())
            @if (! $stokDipakai)
                @if ($bisaDiterapkan)
                    <button type="button" 
                            onclick="bukaModal('modalKonfirmasiTerapkan')"
                            class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-6 py-3 rounded-full text-xs sm:text-sm font-black text-white bg-brand hover:bg-brand-dark transition shadow-md hover:shadow-brand/20 active:scale-95 cursor-pointer">
                        <span class="material-symbols-outlined text-[19px]">blender</span>
                        <span>Campur Pakan & Kurangi Stok Gudang</span>
                    </button>
                @else
                    <div class="flex items-center gap-2 w-full sm:w-auto">
                        <button type="button" 
                                disabled
                                class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-6 py-3 rounded-full text-xs font-bold text-muted bg-surface-2 border border-line cursor-not-allowed opacity-80"
                                title="Stok bahan di gudang tidak mencukupi untuk adukan ini">
                            <span class="material-symbols-outlined text-[18px]">lock</span>
                            <span>Stok Gudang Kurang (Tidak Bisa Potong)</span>
                        </button>
                        <a href="{{ route('bahan.index') }}" 
                           class="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-full text-xs font-bold text-brand bg-brand/10 hover:bg-brand/20 transition shrink-0 cursor-pointer"
                           title="Tambah stok bahan di gudang pakan">
                            <span class="material-symbols-outlined text-[17px]">add_shopping_cart</span>
                            <span>Beli Bahan</span>
                        </a>
                    </div>
                @endif
            @else
                <form method="POST" action="{{ route('ransum.batal-terapkan', $ration) }}" 
                      data-confirm="Batalkan penerapan formula ini? Seluruh stok pakan akan dikembalikan utuh ke gudang Anda." 
                      data-confirm-title="Batalkan Penerapan Racikan" 
                      data-confirm-btn="Ya, Kembalikan Stok">
                    @csrf
                    <button type="submit" class="w-full sm:w-auto inline-flex items-center justify-center gap-1.5 px-5 py-2.5 rounded-full text-xs font-bold text-rose-700 hover:text-rose-800 bg-white hover:bg-rose-50 border border-rose-200 transition shadow-2xs active:scale-95 cursor-pointer">
                        <span class="material-symbols-outlined text-[17px]">undo</span>
                        <span>Batalkan Pemotongan Stok</span>
                    </button>
                </form>
            @endif
        @else
            <a href="{{ route('bahan.index') }}" 
               class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-full text-xs font-bold text-white bg-brand hover:bg-brand-dark transition shadow-2xs active:scale-95 cursor-pointer">
                <span class="material-symbols-outlined text-[17px]">inventory_2</span>
                <span>Buka Gudang & Tambah Stok</span>
            </a>
        @endif
    </div>
</div>

{{-- MODAL KONFIRMASI CAMPUR PAKAN & POTONG STOK GUDANG (CENTERED) --}}
<div id="modalKonfirmasiTerapkan" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-xs transition-opacity duration-200 hidden opacity-0 pointer-events-none" role="dialog" aria-modal="true">
    <div class="absolute inset-0" onclick="tutupModal('modalKonfirmasiTerapkan')"></div>
    <div data-modal-card class="relative z-10 w-full max-w-lg bg-white rounded-2xl p-5 sm:p-6 shadow-2xl border border-line transform transition-all duration-200 scale-95 opacity-0 space-y-4 max-h-[92vh] overflow-y-auto">
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
            <button type="button" onclick="tutupModal('modalKonfirmasiTerapkan')" class="p-1 rounded-lg text-muted hover:text-ink hover:bg-surface cursor-pointer">
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
                        onclick="tutupModal('modalKonfirmasiTerapkan')" 
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
</div>

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
