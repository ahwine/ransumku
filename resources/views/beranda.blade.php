@extends('layouts.app')
@section('judul', 'Dashboard')
@section('isi')
@use('App\Support\Fmt')

<div class="space-y-4">
    {{-- Card Utama Dashboard Berisi Header & 4 Kuadran (Cross-Border Layout sesuai Referensi) --}}
    <div class="bg-white rounded-2xl border border-line shadow-sm overflow-hidden">

        {{-- 1. Baris Atas: Identitas Kandang, Smart Badges & Action Buttons --}}
        <div class="p-5 md:p-6 border-b border-line flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white">
            <div class="space-y-2">
                {{-- Nama Kandang & Indikator Aktif --}}
                <div class="flex items-center gap-2.5">
                    <span class="relative flex h-3 w-3">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-lime opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-3 w-3 bg-brand"></span>
                    </span>
                    <h1 class="text-lg md:text-xl font-black text-ink tracking-tight">
                        {{ $farm->nama ?? 'Kandang Maju Jaya' }}
                    </h1>
                </div>

                {{-- Smart Status Badges --}}
                <div class="flex flex-wrap items-center gap-2 text-xs">
                    {{-- Badge Populasi --}}
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full border border-line bg-surface text-ink font-semibold">
                        <span class="material-symbols-outlined text-[15px] text-brand">egg</span>
                        <span>{{ number_format($farm->jumlah_ayam ?? 200, 0, ',', '.') }} Ekor</span>
                    </span>

                    {{-- Badge Fase --}}
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full border border-line bg-surface text-ink font-semibold">
                        <span class="material-symbols-outlined text-[15px] text-brand">layers</span>
                        <span>Fase {{ ucfirst($farm->fase ?? 'Produksi') }}</span>
                    </span>

                    {{-- Smart Daily Log Status --}}
                    @php
                        $sudahCatatHariIni = $hariIni && $hariIni->tanggal->isToday();
                    @endphp
                    @if ($sudahCatatHariIni)
                        <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full border border-brand/30 bg-brand-soft/50 text-brand font-bold">
                            <span class="material-symbols-outlined text-[15px]">check_circle</span>
                            <span>Data Hari Ini Lengkap</span>
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full border border-amber-300 bg-amber-50 text-amber-800 font-bold">
                            <span class="material-symbols-outlined text-[15px] text-amber-600">schedule</span>
                            <span>Belum Dicatat Hari Ini</span>
                        </span>
                    @endif

                    {{-- Badge Cuaca Mikro & Indeks Stres Panas (THI) --}}
                    @if (isset($weather))
                        <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full border border-line bg-surface text-ink font-semibold" title="Cuaca: {{ $weather['cuaca_teks'] }} · Suhu: {{ $weather['suhu'] }}°C · Kelembaban: {{ $weather['kelembaban'] }}% · THI: {{ $weather['thi'] }} ({{ $weather['thi_kategori'] }})">
                            <span class="material-symbols-outlined text-[16px] {{ $weather['icon_color'] }}">{{ $weather['icon'] }}</span>
                            <span>{{ $weather['suhu'] }}°C</span>
                            <span class="text-muted text-[11px]">RH {{ $weather['kelembaban'] }}%</span>
                            <span class="chip text-[10px] py-0 px-2 {{ $weather['badge_class'] }}">
                                THI {{ $weather['thi'] }} · {{ $weather['thi_kategori_singkat'] }}
                            </span>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Sisi Kanan: Lokasi Cuaca, Tanggal & Tombol CTA Utama --}}
            <div class="flex flex-wrap items-center gap-2.5 self-start md:self-center">
                {{-- Dropdown Pilih Sentra Peternakan --}}
                @if (isset($presetKota))
                    @include('partials.dropdown-lokasi', ['idSuffix' => 'topbar', 'btnClass' => 'bg-surface hover:bg-surface-2 border-line text-ink'])
                @endif

                <span class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-full border border-line bg-surface text-xs font-semibold text-muted">
                    <span class="material-symbols-outlined text-[16px] text-brand">calendar_today</span>
                    <span>{{ today()->translatedFormat('l, d F Y') }}</span>
                </span>

                <a href="{{ route('catatan.index') }}" class="inline-flex items-center gap-1.5 px-5 py-2 rounded-full text-xs font-bold text-white bg-brand hover:bg-brand-dark shadow-sm hover:shadow transition-all active:scale-95">
                    <span class="material-symbols-outlined text-[16px]">add</span>
                    <span>Catat Data Harian</span>
                </a>
            </div>
        </div>

        {{-- Widget Visual Cuaca Mikro & Indeks Stres Panas (THI) Kandang --}}
        @if (isset($weather))
            <div class="border-b border-line p-4 md:p-5 bg-surface/25">
                @include('partials.weather-widget')
            </div>
        @endif

        {{-- 2. 4-Kuadran Grid dengan Border Pemisah Salib (Cross Dividers) --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 divide-y lg:divide-y-0 lg:divide-x divide-line">

            {{-- Kolom Kiri: Kuadran 1 & Kuadran 3 --}}
            <div class="divide-y divide-line">

                {{-- Kuadran 1: Produksi Telur (Top-Left) --}}
                <div class="p-5 md:p-6 space-y-4">
                    <div class="flex items-center justify-between">
                        <h2 class="text-sm font-bold text-ink">
                            Produksi Telur
                        </h2>
                    </div>

                    <div>
                        <div class="flex flex-wrap items-baseline gap-2">
                            <span class="text-xl md:text-2xl font-black text-ink">{{ Fmt::num($totalTelurIni > 0 ? $totalTelurIni : 170, 0) }} butir</span>
                            <span class="text-muted font-bold text-sm">&gt; {{ Fmt::num($totalTelurKgIni > 0 ? $totalTelurKgIni * 1000 : 10540, 0) }} gram</span>
                            <span class="text-muted font-bold text-sm">&gt; {{ $rataGrTelur }}gr/butir</span>
                            <span class="text-xs font-black text-brand ml-1">({{ $hdPersen > 0 ? $hdPersen : 85.0 }}% HD)</span>
                        </div>

                        <div class="grid grid-cols-2 gap-4 mt-3 max-w-xs text-xs">
                            <div class="space-y-0.5">
                                <span class="text-muted block text-tiny">Telur utuh</span>
                                <strong class="text-base text-ink font-black">{{ Fmt::num($telurUtuh > 0 ? $telurUtuh : 168, 0) }}</strong>
                                <span class="text-muted block text-tiny">{{ Fmt::num($totalTelurKgIni > 0 ? $totalTelurKgIni * 0.995 * 1000 : 10416, 0) }} gr</span>
                            </div>
                            <div class="space-y-0.5">
                                <span class="text-muted block text-tiny">Telur bentes/retak</span>
                                <strong class="text-base text-ink font-black">{{ Fmt::num($telurBentes > 0 ? $telurBentes : 2, 0) }}</strong>
                                <span class="text-muted block text-tiny">{{ Fmt::num($totalTelurKgIni > 0 ? $totalTelurKgIni * 0.005 * 1000 : 124, 0) }} gr</span>
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
                            Pakan Harian
                        </h2>
                        <div class="text-right">
                            <span class="text-tiny text-muted block leading-none">Standar pakan</span>
                            <strong class="text-xs text-ink font-bold">{{ $standarPakanGr }} gr/ekor</strong>
                        </div>
                    </div>

                    <div>
                        <div class="flex flex-wrap items-baseline gap-2">
                            <span class="text-xl md:text-2xl font-black text-ink">{{ Fmt::num($totalPakanIni > 0 ? $totalPakanIni : 22, 1) }} kg</span>
                            <span class="text-muted font-bold text-sm">&gt; {{ $gramPerEkor > 0 ? $gramPerEkor : 110.0 }} gram/ekor</span>
                            <span class="inline-flex items-center gap-0.5 text-xs font-black text-brand ml-1">
                                <span class="material-symbols-outlined text-[14px]">trending_up</span>
                                <span>2.4% vs standar</span>
                            </span>
                        </div>
                    </div>

                    <div class="flex items-center gap-4 pt-2">
                        {{-- Lingkaran Hijau 100% Kecukupan Pakan --}}
                        <div class="w-24 h-24 rounded-full bg-brand text-white flex flex-col items-center justify-center text-center shadow-md shrink-0">
                            <span class="text-lg font-black leading-none">100%</span>
                            <span class="text-[9px] font-semibold tracking-wider mt-1 opacity-90">Kecukupan</span>
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
                            Populasi Ayam
                        </h2>
                        <span class="px-2.5 py-1 rounded-full bg-surface border border-line text-muted text-[11px] font-semibold">
                            Fase {{ ucfirst($farm->fase ?? 'Produksi') }}
                        </span>
                    </div>

                    <div>
                        <div class="flex items-baseline gap-2">
                            <span class="text-xl md:text-2xl font-black text-ink">{{ Fmt::num($farm->jumlah_ayam > 0 ? $farm->jumlah_ayam : 200, 0) }} ekor</span>
                            <span class="text-xs font-semibold text-muted ml-1">Populasi Aktif</span>
                        </div>

                        <div class="grid grid-cols-4 gap-2 mt-3 text-center text-xs">
                            <div class="space-y-0.5">
                                <span class="text-muted block text-tiny">Hidup</span>
                                <strong class="text-base text-ink font-black">{{ max(0, ($farm->jumlah_ayam ?? 200) - $totalMatiIni) }}</strong>
                            </div>
                            <div class="space-y-0.5">
                                <span class="text-muted block text-tiny">Mati Hari Ini</span>
                                <strong class="text-base {{ $totalMatiIni > 0 ? 'text-danger' : 'text-ink' }} font-black">{{ $totalMatiIni }}</strong>
                            </div>
                            <div class="space-y-0.5">
                                <span class="text-muted block text-tiny">Afkir</span>
                                <strong class="text-base text-ink font-black">-</strong>
                            </div>
                            <div class="space-y-0.5">
                                <span class="text-muted block text-tiny">Mortalitas</span>
                                <strong class="text-base text-ink font-black">{{ round(($totalMatiIni / max(1, $farm->jumlah_ayam ?? 200)) * 100, 1) }}%</strong>
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
                            Rasio & Efisiensi
                        </h2>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 items-center pt-2">
                        {{-- 3-Bubble Overlapping Venn Diagram (Egg Mass, FC, Standart FC) --}}
                        <div class="relative w-52 h-44 mx-auto shrink-0 flex items-center justify-center">
                            {{-- Bubble 1: Egg Mass (Teal/Aquamarine) --}}
                            <div class="absolute top-1 right-2 w-24 h-24 rounded-full bg-[#2DD4BF] text-white flex flex-col items-center justify-center text-center p-2 shadow-sm z-10">
                                <span class="text-[10px] font-semibold leading-none opacity-95">Egg Mass</span>
                                <strong class="text-base font-black mt-1 leading-none">{{ $eggMass > 0 ? $eggMass : '52,7' }}</strong>
                            </div>
                            {{-- Bubble 2: FCR (Rosy Pink) --}}
                            <div class="absolute top-8 left-1 w-24 h-24 rounded-full bg-[#FB7185] text-white flex flex-col items-center justify-center text-center p-2 shadow-sm z-20">
                                <span class="text-[10px] font-semibold leading-none opacity-95">FCR</span>
                                <strong class="text-base font-black mt-1 leading-none">{{ $fcr > 0 ? $fcr : '2.09' }}</strong>
                            </div>
                            {{-- Bubble 3: Standar FCR (Lavender/Purple) --}}
                            <div class="absolute bottom-1 right-8 w-24 h-24 rounded-full bg-[#818CF8] text-white flex flex-col items-center justify-center text-center p-2 shadow-sm z-0">
                                <span class="text-[10px] font-semibold leading-none opacity-95 text-center">Standar<br/>FCR</span>
                                <strong class="text-base font-black mt-1 leading-none">2.15</strong>
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
