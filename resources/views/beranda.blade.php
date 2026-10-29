@extends('layouts.app')
@section('judul', 'Dashboard')
@section('isi')
@use('App\Support\Fmt')

<div class="space-y-4">
    {{-- Card Utama Dashboard: Identitas Kandang, Cuaca Mikro, & 4-Kuadran Metrik --}}
    <div class="bg-white rounded-xl border border-line shadow-2xs overflow-hidden">

        {{-- 1. Baris Atas: Identitas Kandang & Toolbar Metrik --}}
        <div class="p-4 md:p-5 border-b border-line space-y-3 bg-white">
            {{-- Header Baris Atas: Nama Kandang di Kiri & Tombol Aksi Sejajar Horizontal di Kanan --}}
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h1 class="text-lg md:text-xl font-extrabold text-ink tracking-tight whitespace-nowrap flex items-center gap-2">
                    <span class="material-symbols-outlined text-brand text-[22px]">cottage</span>
                    <span>{{ $farm->nama ?? 'Kandang Maju Jaya' }}</span>
                </h1>

                {{-- Tombol Aksi Sejajar Horizontal (Kelola Kandang & Catat Data Harian) --}}
                <div class="flex items-center gap-2 shrink-0">
                    {{-- Tombol Sekunder: Kelola Kandang --}}
                    <a href="{{ route('kandang.index') }}" class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full text-xs font-semibold text-muted hover:text-ink bg-surface hover:bg-surface-2 border border-line transition shadow-2xs" title="Kelola Multi-Kandang">
                        <span class="material-symbols-outlined text-[15px] text-brand">tune</span>
                        <span>Kelola Kandang</span>
                    </a>

                    {{-- Tombol Primer (CTA): Catat Data Harian --}}
                    <a href="{{ route('catatan.index') }}" class="inline-flex items-center gap-1.5 px-4 py-1.5 rounded-full text-xs font-bold text-white bg-brand hover:bg-brand-dark shadow-2xs hover:shadow-xs transition-all active:scale-95">
                        <span class="material-symbols-outlined text-[16px]">add</span>
                        <span>Catat Data Harian</span>
                    </a>
                </div>
            </div>

            {{-- Baris Bawah: 4 Badges Metadata Konteks & Status Kandang --}}
            <div class="flex flex-wrap items-center gap-2 text-xs">
                {{-- 1. Badge Kota Sentra Peternakan --}}
                @if (isset($presetKota))
                    <form method="POST" action="{{ route('kandang.lokasi') }}" class="inline-flex items-center">
                        @csrf
                        <div class="inline-flex items-center gap-1 px-3.5 py-1.5 rounded-full border border-line bg-surface hover:bg-surface-2 transition text-xs font-semibold text-ink shadow-2xs cursor-pointer" title="Ubah lokasi sentra kandang">
                            <span class="material-symbols-outlined text-[15px] text-brand">location_on</span>
                            <select name="kota" onchange="this.form.submit()" class="bg-transparent text-xs font-bold text-ink focus:outline-none cursor-pointer pr-1">
                                @foreach ($presetKota as $k => $p)
                                    <option value="{{ $k }}" @selected(($farm->lokasi_kota ?? 'Surabaya') === $k)>{{ $p['nama'] }}</option>
                                @endforeach
                            </select>
                        </div>
                    </form>
                @endif

                {{-- 2. Badge Tanggal --}}
                <span class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full border border-line bg-surface text-xs font-semibold text-muted shadow-2xs">
                    <span class="material-symbols-outlined text-[15px] text-brand">calendar_today</span>
                    <span>{{ today()->translatedFormat('l, d F Y') }}</span>
                </span>

                {{-- 3. Badge Jumlah Ekor (Populasi Kawanan Ayam) --}}
                <span class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full border border-line bg-surface text-ink font-semibold shadow-2xs">
                    <span class="material-symbols-outlined text-[15px] text-brand">groups</span>
                    <span>{{ number_format($farm->jumlah_ayam ?? 200, 0, ',', '.') }} Ekor</span>
                </span>

                {{-- 4. Badge Fase Produksi --}}
                <span class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full border border-line bg-surface text-ink font-semibold shadow-2xs">
                    <span class="material-symbols-outlined text-[15px] text-brand">layers</span>
                    <span>Fase {{ ucfirst($farm->fase ?? 'Produksi') }}</span>
                </span>
            </div>
        </div>

        {{-- Widget Visual Cuaca Mikro & Indeks Stres Panas (THI) Kandang --}}
        @if (isset($weather))
            <div class="border-b border-line p-5 md:p-6 bg-white">
                @include('partials.weather-widget')
            </div>
        @endif

        {{-- 2. 4-Kuadran Grid Analitikal Sempurna 2x2 (Stock Market / Financial Terminal Style) --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 divide-y lg:divide-y divide-line">

            {{-- Kuadran 1: Produksi Telur (Top-Left) --}}
            <div class="p-5 md:p-6 space-y-3.5 border-b border-line lg:border-r">
                {{-- Header Kuadran 1 --}}
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-1.5">
                        <h2 class="text-xs font-bold text-ink uppercase tracking-wider">Produksi Telur</h2>
                        <span class="hidden sm:inline text-tiny text-muted">• Kualitas & Butir Panen</span>
                    </div>
                    <span class="px-3 py-1 rounded-full text-xs font-bold shadow-2xs {{ $hdPersen >= 75 ? 'bg-emerald-50 text-emerald-800 border border-emerald-200' : 'bg-amber-50 text-amber-800 border border-amber-200' }}">
                        {{ $hdPersen > 0 ? $hdPersen : 85.0 }}% Hen-Day
                    </span>
                </div>

                {{-- Ticker Utama & Delta --}}
                <div class="flex items-baseline gap-2.5">
                    <span class="text-3xl md:text-4xl font-black text-ink tracking-tight">
                        {{ Fmt::num($totalTelurIni > 0 ? $totalTelurIni : 170, 0) }}
                    </span>
                    <span class="text-xs font-bold text-muted uppercase">butir</span>
                    @if (isset($deltaTelur))
                        @if ($deltaTelur > 0)
                            <span class="inline-flex items-center gap-0.5 px-2 py-0.5 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                <span class="material-symbols-outlined text-[13px]">trending_up</span>
                                <span>+{{ $deltaTelur }}%</span>
                            </span>
                        @elseif ($deltaTelur < 0)
                            <span class="inline-flex items-center gap-0.5 px-2 py-0.5 rounded-full text-xs font-bold bg-rose-50 text-rose-700 border border-rose-200">
                                <span class="material-symbols-outlined text-[13px]">trending_down</span>
                                <span>{{ $deltaTelur }}%</span>
                            </span>
                        @else
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold bg-slate-50 text-slate-700 border border-slate-200">
                                <span>Stabil</span>
                            </span>
                        @endif
                    @endif
                </div>

                {{-- Ticker Strip Horizontal: 2-Kolom di Mobile, 4-Kolom di Desktop --}}
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 py-2 px-3 rounded-lg bg-surface/50 border border-line text-center text-xs">
                    <div>
                        <span class="text-muted text-tiny block">Total Bobot</span>
                        <strong class="text-ink font-bold">{{ Fmt::num($totalTelurKgIni > 0 ? $totalTelurKgIni : 10.54, 2) }} kg</strong>
                    </div>
                    <div>
                        <span class="text-muted text-tiny block">Rata-rata</span>
                        <strong class="text-ink font-bold">{{ $rataGrTelur }} g</strong>
                    </div>
                    <div>
                        <span class="text-muted text-tiny block">Telur Utuh</span>
                        <strong class="text-emerald-700 font-bold">{{ Fmt::num($telurUtuh > 0 ? $telurUtuh : 168, 0) }}</strong>
                    </div>
                    <div>
                        <span class="text-muted text-tiny block">Retak / Bentes</span>
                        <strong class="{{ $telurBentes > 0 ? 'text-amber-700' : 'text-ink' }} font-bold">{{ Fmt::num($telurBentes > 0 ? $telurBentes : 2, 0) }}</strong>
                    </div>
                </div>

                {{-- Grafik Produksi Telur 7 Hari --}}
                <div class="h-44 pt-1">
                    <canvas data-chart='@json($chartTelur)' aria-label="Grafik produksi telur"></canvas>
                </div>
            </div>

            {{-- Kuadran 2: Populasi Ayam (Top-Right) --}}
            <div class="p-5 md:p-6 space-y-3.5 border-b border-line">
                @php
                    $survivalRate = max(0, 100 - round(($totalMatiIni / max(1, $farm->jumlah_ayam ?? 200)) * 100, 1));
                @endphp
                {{-- Header Kuadran 2 --}}
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-1.5">
                        <h2 class="text-xs font-bold text-ink uppercase tracking-wider">Populasi Ayam</h2>
                        <span class="hidden sm:inline text-tiny text-muted">• Ayam Aktif & Mortalitas</span>
                    </div>
                    <span class="px-3 py-1 rounded-full text-xs font-bold shadow-2xs {{ $totalMatiIni > 0 ? 'bg-rose-50 text-rose-800 border border-rose-200' : 'bg-blue-50 text-blue-800 border border-blue-200' }}">
                        {{ $survivalRate }}% Survival Rate
                    </span>
                </div>

                {{-- Ticker Utama & Delta --}}
                <div class="flex items-baseline gap-2.5">
                    <span class="text-3xl md:text-4xl font-black text-ink tracking-tight">
                        {{ Fmt::num(max(0, ($farm->jumlah_ayam ?? 200) - $totalMatiIni), 0) }}
                    </span>
                    <span class="text-xs font-bold text-muted uppercase">ekor aktif</span>
                    @if ($totalMatiIni > 0)
                        <span class="inline-flex items-center gap-0.5 px-2 py-0.5 rounded-full text-xs font-bold bg-rose-50 text-rose-700 border border-rose-200">
                            <span class="material-symbols-outlined text-[13px]">arrow_downward</span>
                            <span>-{{ $totalMatiIni }} mati</span>
                        </span>
                    @else
                        <span class="inline-flex items-center gap-0.5 px-2 py-0.5 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                            <span class="material-symbols-outlined text-[13px]">check</span>
                            <span>0 Kematian</span>
                        </span>
                    @endif
                </div>

                {{-- Ticker Strip Horizontal: 2-Kolom di Mobile, 4-Kolom di Desktop --}}
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 py-2 px-3 rounded-lg bg-surface/50 border border-line text-center text-xs">
                    <div>
                        <span class="text-muted text-tiny block">Tercatat</span>
                        <strong class="text-ink font-bold">{{ Fmt::num($farm->jumlah_ayam ?? 200, 0) }}</strong>
                    </div>
                    <div>
                        <span class="text-muted text-tiny block">Ayam Hidup</span>
                        <strong class="text-blue-700 font-bold">{{ max(0, ($farm->jumlah_ayam ?? 200) - $totalMatiIni) }}</strong>
                    </div>
                    <div>
                        <span class="text-muted text-tiny block">Mati Hari Ini</span>
                        <strong class="{{ $totalMatiIni > 0 ? 'text-rose-700' : 'text-ink' }} font-bold">{{ $totalMatiIni }}</strong>
                    </div>
                    <div>
                        <span class="text-muted text-tiny block">Mortalitas</span>
                        <strong class="{{ $totalMatiIni > 0 ? 'text-rose-700' : 'text-ink' }} font-bold">{{ round(($totalMatiIni / max(1, $farm->jumlah_ayam ?? 200)) * 100, 1) }}%</strong>
                    </div>
                </div>

                {{-- Grafik Populasi 7 Hari --}}
                <div class="h-44 pt-1">
                    <canvas data-chart='@json($chartPopulasi)' aria-label="Grafik populasi ayam"></canvas>
                </div>
            </div>

            {{-- Kuadran 3: Pakan Harian (Bottom-Left) --}}
            <div class="p-5 md:p-6 space-y-3.5 border-b lg:border-b-0 lg:border-r border-line">
                @php
                    $diffPersen = $standarPakanGr > 0 ? round((($gramPerEkor - $standarPakanGr) / $standarPakanGr) * 100, 1) : 0;
                @endphp
                {{-- Header Kuadran 3 --}}
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-1.5">
                        <h2 class="text-xs font-bold text-ink uppercase tracking-wider">Konsumsi Pakan</h2>
                        <span class="hidden sm:inline text-tiny text-muted">• Realisasi vs SNI</span>
                    </div>
                    <span class="px-3 py-1 rounded-full text-xs font-bold shadow-2xs bg-surface border border-line text-ink">
                        Standar: {{ $standarPakanGr }} g/ekor
                    </span>
                </div>

                {{-- Ticker Utama & Delta --}}
                <div class="flex items-baseline gap-2.5">
                    <span class="text-3xl md:text-4xl font-black text-ink tracking-tight">
                        {{ Fmt::num($totalPakanIni > 0 ? $totalPakanIni : 22, 1) }}
                    </span>
                    <span class="text-xs font-bold text-muted uppercase">kg / hari</span>
                    @if ($diffPersen > 0)
                        <span class="inline-flex items-center gap-0.5 px-2 py-0.5 rounded-full text-xs font-bold bg-amber-50 text-amber-800 border border-amber-200">
                            <span class="material-symbols-outlined text-[13px]">arrow_upward</span>
                            <span>+{{ $diffPersen }}% vs SNI</span>
                        </span>
                    @elseif ($diffPersen < 0)
                        <span class="inline-flex items-center gap-0.5 px-2 py-0.5 rounded-full text-xs font-bold bg-blue-50 text-blue-800 border border-blue-200">
                            <span class="material-symbols-outlined text-[13px]">arrow_downward</span>
                            <span>{{ $diffPersen }}% vs SNI</span>
                        </span>
                    @else
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold bg-emerald-50 text-emerald-800 border border-emerald-200">
                            <span>Tepat Standar</span>
                        </span>
                    @endif
                </div>

                {{-- Ticker Strip Horizontal: 2-Kolom di Mobile, 4-Kolom di Desktop --}}
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 py-2 px-3 rounded-lg bg-surface/50 border border-line text-center text-xs">
                    <div>
                        <span class="text-muted text-tiny block">Asupan/Ekor</span>
                        <strong class="text-ink font-bold">{{ $gramPerEkor > 0 ? $gramPerEkor : 110.0 }} g</strong>
                    </div>
                    <div>
                        <span class="text-muted text-tiny block">Target SNI</span>
                        <strong class="text-ink font-bold">{{ $standarPakanGr }} g</strong>
                    </div>
                    <div>
                        <span class="text-muted text-tiny block">Selisih</span>
                        <strong class="{{ $diffPersen > 0 ? 'text-amber-700' : ($diffPersen < 0 ? 'text-blue-700' : 'text-emerald-700') }} font-bold">
                            {{ $diffPersen > 0 ? '+' : '' }}{{ round($gramPerEkor - $standarPakanGr, 1) }} g
                        </strong>
                    </div>
                    <div>
                        <span class="text-muted text-tiny block">Biaya Ransum</span>
                        <strong class="text-ink font-bold">{{ $ration ? Fmt::rp($ration->biaya_per_kg) : 'Rp 6.850' }}</strong>
                    </div>
                </div>

                {{-- Grafik Konsumsi Pakan 7 Hari --}}
                <div class="h-44 pt-1">
                    <canvas data-chart='@json($chartPakan)' aria-label="Grafik konsumsi pakan"></canvas>
                </div>
            </div>

            {{-- Kuadran 4: Rasio & Efisiensi FCR (Bottom-Right) --}}
            <div class="p-5 md:p-6 space-y-3.5">
                {{-- Header Kuadran 4 --}}
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-1.5">
                        <h2 class="text-xs font-bold text-ink uppercase tracking-wider">Efisiensi & FCR</h2>
                        <span class="hidden sm:inline text-tiny text-muted">• Konversi Pakan & Finansial</span>
                    </div>
                    @if ($fcr > 0 && $fcr <= 2.2)
                        <span class="px-3 py-1 rounded-full text-xs font-bold shadow-2xs bg-emerald-50 text-emerald-800 border border-emerald-200">
                            FCR Efisien
                        </span>
                    @elseif ($fcr > 2.2)
                        <span class="px-3 py-1 rounded-full text-xs font-bold shadow-2xs bg-rose-50 text-rose-800 border border-rose-200">
                            FCR Tinggi (Boros)
                        </span>
                    @else
                        <span class="px-3 py-1 rounded-full text-xs font-bold shadow-2xs bg-slate-50 text-slate-700 border border-slate-200">
                            FCR Standar
                        </span>
                    @endif
                </div>

                {{-- Ticker Utama & Delta --}}
                <div class="flex items-baseline gap-2.5">
                    <span class="text-3xl md:text-4xl font-black text-ink tracking-tight">
                        {{ $fcr > 0 ? $fcr : '2.09' }}
                    </span>
                    <span class="text-xs font-bold text-muted uppercase">FCR Aktual</span>
                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-surface border border-line text-muted">
                        <span>Standar SNI: 2.15</span>
                    </span>
                </div>

                {{-- Ticker Strip Horizontal: 2-Kolom di Mobile, 4-Kolom di Desktop --}}
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 py-2 px-3 rounded-lg bg-surface/50 border border-line text-center text-xs">
                    <div>
                        <span class="text-muted text-tiny block">Egg Mass</span>
                        <strong class="text-ink font-bold">{{ $eggMass > 0 ? $eggMass : '52.7' }} g</strong>
                    </div>
                    <div>
                        <span class="text-muted text-tiny block">Biaya/Kg Telur</span>
                        <strong class="text-brand font-bold">{{ $biayaKgTelur ? Fmt::rp($biayaKgTelur) : 'Rp 14.500' }}</strong>
                    </div>
                    <div>
                        <span class="text-muted text-tiny block">Biaya Ransum</span>
                        <strong class="text-ink font-bold">{{ $ration ? Fmt::rp($ration->biaya_per_kg) : 'Rp 6.850' }}</strong>
                    </div>
                    <div>
                        <span class="text-muted text-tiny block">Standar FCR</span>
                        <strong class="text-ink font-bold">2.15</strong>
                    </div>
                </div>

                {{-- Grafik Tren FCR 7 Hari vs SNI --}}
                <div class="h-44 pt-1">
                    <canvas data-chart='@json($chartFcr)' aria-label="Grafik tren FCR"></canvas>
                </div>
            </div>

        </div>

    </div>

    {{-- Pusat Deteksi Dini & Anomali Kandang (Unified Anomaly Center) --}}
    @if ($alerts->count() > 0)
        <div class="bg-white rounded-xl border border-line shadow-2xs overflow-hidden">
            {{-- Header Anomaly Center --}}
            <div class="px-5 py-3.5 border-b border-line flex flex-wrap items-center justify-between gap-3 bg-white">
                <div class="flex items-center gap-2">
                    <span class="material-symbols-outlined text-[20px] text-amber-600 shrink-0">notifications_active</span>
                    <div class="flex items-center gap-2 flex-wrap">
                        <h2 class="text-xs font-bold text-ink uppercase tracking-wider">Peringatan dini & Anomali Data</h2>
                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-rose-50 text-rose-700 border border-rose-200">
                            {{ $alerts->count() }} Isu Terdeteksi
                        </span>
                        <span class="hidden sm:inline text-tiny text-muted">• Berdasarkan Rekaman Data 7 Hari</span>
                    </div>
                </div>

                <a href="{{ route('asisten.index', ['tab' => 'peringatan']) }}" class="text-xs font-semibold text-brand hover:underline flex items-center gap-1">
                    <span>Lihat Riwayat</span>
                    <span class="material-symbols-outlined text-[15px]">arrow_forward</span>
                </a>
            </div>

            {{-- List Alert Items --}}
            <div class="divide-y divide-line">
                @foreach ($alerts as $alert)
                    @include('partials.alert-card', ['alert' => $alert, 'is_list' => true])
                @endforeach
            </div>

            {{-- Footer Card (1 Disclaimer Global Halus Tanpa Diulang) --}}
            <div class="px-5 py-2.5 bg-surface/30 border-t border-line flex items-center gap-2 text-tiny text-muted">
                <span class="material-symbols-outlined text-[14px] text-muted shrink-0">info</span>
                <span>{{ config('ransumku.disclaimer_ai') }}</span>
            </div>
        </div>
    @endif
</div>
@endsection
