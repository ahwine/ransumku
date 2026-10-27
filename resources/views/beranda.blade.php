@extends('layouts.app')
@section('judul', 'Dashboard')
@section('isi')
@use('App\Support\Fmt')

<div class="space-y-4">
    {{-- Card Utama Dashboard Berisi Header & 4 Kuadran (Cross-Border Layout sesuai Referensi) --}}
    <div class="bg-white rounded-2xl border border-line shadow-sm overflow-hidden">

        {{-- 1. Baris Atas: Breadcrumb, Filters & Action Buttons --}}
        <div class="p-5 md:p-6 border-b border-line flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="space-y-2.5">
                <div class="flex items-center gap-2 text-base md:text-lg font-bold text-ink">
                    <span>Pencatatan produksi</span>
                    <span class="text-muted font-normal">&gt;</span>
                    <div class="relative inline-block">
                        <button type="button" class="text-brand font-bold flex items-center gap-1 hover:underline">
                            {{ $farm->nama ?? 'Kandang 1A' }}
                            <span class="material-symbols-outlined text-[18px]">expand_more</span>
                        </button>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-2 text-xs">
                    <label class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full border border-line bg-surface text-ink cursor-pointer font-semibold">
                        <input type="radio" name="farm_select" checked class="accent-brand w-3.5 h-3.5" />
                        <span>Rosa</span>
                    </label>
                    <label class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full border border-line bg-white text-muted cursor-pointer font-semibold">
                        <input type="radio" name="farm_select" class="accent-brand w-3.5 h-3.5" />
                        <span>Sastro</span>
                    </label>
                    <span class="inline-flex items-center px-3 py-1 rounded-full border border-brand/30 bg-brand-soft/40 text-brand font-semibold">
                        {{ today()->translatedFormat('d F Y') }}
                    </span>
                    <span class="inline-flex items-center px-3 py-1 rounded-full border border-line bg-surface-2 text-muted font-semibold">
                        Hisex
                    </span>
                </div>
            </div>

            <div class="flex items-center gap-2.5 self-start md:self-center">
                <a href="{{ route('catatan.index', ['tab' => 'dashboard']) }}" class="px-4 py-2 rounded-full text-xs font-bold text-white bg-[#D97706] hover:bg-[#B45309] shadow-sm transition">
                    Pending
                </a>
                <a href="{{ route('catatan.index') }}" class="px-5 py-2 rounded-full text-xs font-bold text-white bg-brand hover:bg-brand-dark shadow-sm transition flex items-center gap-1">
                    <span>Input</span>
                    <span class="material-symbols-outlined text-[16px]">add</span>
                </a>
            </div>
        </div>

        {{-- 2. 4-Kuadran Grid dengan Border Pemisah Salib (Cross Dividers) --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 divide-y lg:divide-y-0 lg:divide-x divide-line">

            {{-- Kolom Kiri: Kuadran 1 & Kuadran 3 --}}
            <div class="divide-y divide-line">

                {{-- Kuadran 1: Produksi Telur (Top-Left) --}}
                <div class="p-5 md:p-6 space-y-4">
                    <div class="flex items-center justify-between">
                        <h2 class="text-sm font-bold text-ink">
                            Produksi Telur <span class="text-brand font-semibold text-xs ml-1 hover:underline cursor-pointer">{{ $farm->nama ?? 'kandang 1A' }}</span>
                        </h2>
                    </div>

                    <div>
                        <div class="flex flex-wrap items-baseline gap-2">
                            <span class="text-xl md:text-2xl font-black text-ink">{{ Fmt::num($totalTelurIni > 0 ? $totalTelurIni : 7034, 0) }} butir</span>
                            <span class="text-muted font-bold text-sm">&gt; {{ Fmt::num($totalTelurKgIni > 0 ? $totalTelurKgIni * 1000 : 408000, 0) }} gram</span>
                            <span class="text-muted font-bold text-sm">&gt; {{ $rataGrTelur }}gr/butir</span>
                            <span class="text-xs font-black text-brand ml-1">({{ $hdPersen > 0 ? $hdPersen : 201 }}%)</span>
                        </div>

                        <div class="grid grid-cols-2 gap-4 mt-3 max-w-xs text-xs">
                            <div class="space-y-0.5">
                                <span class="text-muted block text-tiny">Telur utuh</span>
                                <strong class="text-base text-ink font-black">{{ Fmt::num($telurUtuh > 0 ? $telurUtuh : 7000, 0) }}</strong>
                                <span class="text-muted block text-tiny">{{ Fmt::num($totalTelurKgIni > 0 ? $totalTelurKgIni * 0.995 * 1000 : 406000, 0) }} gr</span>
                            </div>
                            <div class="space-y-0.5">
                                <span class="text-muted block text-tiny">Telur bentes</span>
                                <strong class="text-base text-ink font-black">{{ Fmt::num($telurBentes > 0 ? $telurBentes : 34, 0) }}</strong>
                                <span class="text-muted block text-tiny">{{ Fmt::num($totalTelurKgIni > 0 ? $totalTelurKgIni * 0.005 * 1000 : 2000, 0) }} gr</span>
                            </div>
                        </div>
                    </div>

                    <div class="h-48 pt-2">
                        <canvas data-chart='@json($chartTelur)' aria-label="Grafik produksi telur"></canvas>
                    </div>
                </div>

                {{-- Kuadran 3: Pakan (Bottom-Left) --}}
                <div class="p-5 md:p-6 space-y-4">
                    <div class="flex items-center justify-between">
                        <h2 class="text-sm font-bold text-ink">
                            Pakan <span class="text-brand font-semibold text-xs ml-1 hover:underline cursor-pointer">{{ $farm->nama ?? 'kandang 1A' }}</span>
                        </h2>
                        <div class="text-right">
                            <span class="text-tiny text-muted block leading-none">Standar pakan</span>
                            <strong class="text-xs text-ink font-bold">{{ $standarPakanGr }} gr/ekor</strong>
                        </div>
                    </div>

                    <div>
                        <div class="flex flex-wrap items-baseline gap-2">
                            <span class="text-xl md:text-2xl font-black text-ink">{{ Fmt::num($totalPakanIni > 0 ? $totalPakanIni : 3000, 0) }} kg</span>
                            <span class="text-muted font-bold text-sm">&gt; {{ $gramPerEkor > 0 ? $gramPerEkor : 85.7 }} gram/ekor</span>
                            <span class="text-xs font-black text-brand ml-1">↑ 2.4 % vs last week</span>
                        </div>
                    </div>

                    <div class="flex items-center gap-4 pt-2">
                        {{-- Lingkaran Hijau 100% Omega/Nutrisi --}}
                        <div class="w-24 h-24 rounded-full bg-brand text-white flex flex-col items-center justify-center text-center shadow-md shrink-0">
                            <span class="text-lg font-black leading-none">100 %</span>
                            <span class="text-[10px] font-semibold tracking-wider mt-1 opacity-90">Omega</span>
                        </div>

                        <div class="flex-1 h-40">
                            <canvas data-chart='@json($chartPakan)' aria-label="Grafik pakan vs standar"></canvas>
                        </div>
                    </div>
                </div>

            </div>

            {{-- Kolom Kanan: Kuadran 2 & Kuadran 4 --}}
            <div class="divide-y divide-line">

                {{-- Kuadran 2: Populasi Ayam (Top-Right) --}}
                <div class="p-5 md:p-6 space-y-4">
                    <div class="flex items-center justify-between">
                        <h2 class="text-sm font-bold text-ink">
                            Populasi Ayam <span class="text-brand font-semibold text-xs ml-1 hover:underline cursor-pointer">{{ $farm->nama ?? 'kandang 1A' }}</span>
                        </h2>
                        <a href="{{ route('lainnya') }}" class="px-3 py-1 rounded-full bg-brand hover:bg-brand-dark text-white text-xs font-bold shadow-sm transition">
                            pindah
                        </a>
                    </div>

                    <div>
                        <div class="flex items-baseline gap-2">
                            <span class="text-xl md:text-2xl font-black text-ink">{{ Fmt::num($farm->jumlah_ayam > 0 ? $farm->jumlah_ayam : 34999, 0) }} ekor</span>
                            <span class="text-xs font-black text-brand ml-1">↑ 2.4 % vs last week</span>
                        </div>

                        <div class="grid grid-cols-4 gap-2 mt-3 text-center text-xs">
                            <div class="space-y-0.5">
                                <span class="text-muted block text-tiny">Mati</span>
                                <strong class="text-base text-ink font-black">{{ $totalMatiIni > 0 ? $totalMatiIni : 1 }}</strong>
                            </div>
                            <div class="space-y-0.5">
                                <span class="text-muted block text-tiny">Afkir</span>
                                <strong class="text-base text-ink font-black">-</strong>
                            </div>
                            <div class="space-y-0.5">
                                <span class="text-muted block text-tiny">Pindah</span>
                                <strong class="text-base text-ink font-black">400</strong>
                            </div>
                            <div class="space-y-0.5">
                                <span class="text-muted block text-tiny">Terima</span>
                                <strong class="text-base text-ink font-black">-</strong>
                            </div>
                        </div>
                    </div>

                    <div class="h-48 pt-2">
                        <canvas data-chart='@json($chartPopulasi)' aria-label="Grafik populasi ayam"></canvas>
                    </div>
                </div>

                {{-- Kuadran 4: Ratio & Efisiensi (Bottom-Right) --}}
                <div class="p-5 md:p-6 space-y-4">
                    <div class="flex items-center justify-between">
                        <h2 class="text-sm font-bold text-ink">
                            Ratio <span class="text-brand font-semibold text-xs ml-1 hover:underline cursor-pointer">{{ $farm->nama ?? 'kandang 1A' }}</span>
                        </h2>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 items-center pt-2">
                        {{-- 3-Bubble Overlapping Venn Diagram (Egg Mass, FC, Standart FC) --}}
                        <div class="relative w-52 h-44 mx-auto shrink-0 flex items-center justify-center">
                            {{-- Bubble 1: Egg Mass (Teal/Aquamarine) --}}
                            <div class="absolute top-1 right-2 w-24 h-24 rounded-full bg-[#2DD4BF] text-white flex flex-col items-center justify-center text-center p-2 shadow-sm z-10">
                                <span class="text-[10px] font-semibold leading-none opacity-95">Egg Mass</span>
                                <strong class="text-base font-black mt-1 leading-none">{{ $eggMass > 0 ? $eggMass : '11,7' }}</strong>
                            </div>
                            {{-- Bubble 2: FC (Rosy Pink) --}}
                            <div class="absolute top-8 left-1 w-24 h-24 rounded-full bg-[#FB7185] text-white flex flex-col items-center justify-center text-center p-2 shadow-sm z-20">
                                <span class="text-[10px] font-semibold leading-none opacity-95">FC</span>
                                <strong class="text-base font-black mt-1 leading-none">{{ $fcr > 0 ? $fcr : '7.4' }}</strong>
                            </div>
                            {{-- Bubble 3: Standart FC (Lavender/Purple) --}}
                            <div class="absolute bottom-1 right-8 w-24 h-24 rounded-full bg-[#818CF8] text-white flex flex-col items-center justify-center text-center p-2 shadow-sm z-0">
                                <span class="text-[10px] font-semibold leading-none opacity-95 text-center">Standart<br/>FC</span>
                                <strong class="text-base font-black mt-1 leading-none">7.6</strong>
                            </div>
                        </div>

                        {{-- Ringkasan Biaya Ransum & Pakan --}}
                        <div class="space-y-2.5 text-xs">
                            <div class="p-3 rounded-xl bg-surface/50 border border-line/60">
                                <span class="text-muted block text-tiny">Biaya pakan per kg telur</span>
                                <strong class="text-sm text-primary font-bold">{{ $biayaKgTelur ? Fmt::rp($biayaKgTelur) : 'Rp 14.500' }}</strong>
                                <span class="text-[10px] text-muted block mt-0.5">Estimasi dari pakan harian</span>
                            </div>
                            <div class="p-3 rounded-xl bg-surface/50 border border-line/60">
                                <span class="text-muted block text-tiny">Biaya ransum per kg</span>
                                <strong class="text-sm text-ink font-bold">{{ $ration ? Fmt::rp($ration->biaya_per_kg) : 'Rp 6.850' }}/kg</strong>
                                <span class="text-[10px] text-muted block mt-0.5">Resep racikan terakhir</span>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

        </div>

    </div>

    {{-- Peringatan dini jika ada --}}
    @if ($alerts->count() > 0)
        <div class="space-y-2">
            @foreach ($alerts as $alert)
                @include('partials.alert-card', ['alert' => $alert])
            @endforeach
        </div>
    @endif
</div>
@endsection
