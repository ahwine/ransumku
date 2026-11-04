@extends('layouts.app')
@section('judul', 'Dashboard')
@section('isi')
@use('App\Support\Fmt')

<div class="space-y-4">
    @if (isset($weather))
        @php
            $weatherType = $weather['weather_type'] ?? 'partly_cloudy';
            $thiPercent = $weather['thi_percent'] ?? 50.0;
            $dampakAyam = $weather['dampak_ayam'] ?? ($weather['deskripsi_risiko'] ?? 'Kondisi fisiologi ayam terpantau aman.');
        @endphp
    @endif

    {{-- ======================================================== --}}
    {{-- A. TAMPILAN MOBILE (Persis Sesuai Referensi Gambar 1)     --}}
    {{-- ======================================================== --}}
    <div class="lg:hidden space-y-4" data-weather-widget data-live-url="{{ route('api.kandang.sensor.live', $farm ?? auth()->user()->farm) }}" data-current-suhu="{{ $weather['suhu'] ?? 27.0 }}" data-current-rh="{{ $weather['kelembaban'] ?? 80 }}">
        
        {{-- 1. Section Header: Ringkasan Hari Ini & Tanggal Aktif --}}
        <div class="flex items-center justify-between px-1 pt-0.5">
            <h1 class="text-xl font-black text-ink tracking-tight">Ringkasan Hari Ini</h1>
            <span class="text-xs font-bold text-brand flex items-center gap-1.5 select-none">
                <span class="material-symbols-outlined text-[16px] text-brand">calendar_today</span>
                <span class="text-brand">{{ today()->translatedFormat('l, j M') }}</span>
            </span>
        </div>

        {{-- 2. Dua Kartu Metrik Ringkasan Hari Ini (Populasi & Fase Produksi) --}}
        <div class="grid grid-cols-2 gap-3">
            {{-- Card 1: Populasi --}}
            <div class="bg-white rounded-2xl border border-line/70 p-4 shadow-2xs flex flex-col justify-between">
                <span class="text-xs font-bold text-muted">Populasi</span>
                <div class="mt-2.5">
                    <span class="text-2xl font-black text-ink tracking-tight">
                        {{ number_format($farm->jumlah_ayam ?? 200, 0, ',', '.') }}
                        <span class="text-xs font-normal text-muted ml-1">ekor</span>
                    </span>
                </div>
            </div>

            {{-- Card 2: Fase Produksi --}}
            @php
                $faseDetail = $farm->faseUmurDetail();
                $umurMinggu = $farm->umurMinggu();
            @endphp
            <div class="bg-white rounded-2xl border border-line/70 p-4 shadow-2xs flex flex-col justify-between">
                <span class="text-xs font-bold text-muted">Fase produksi</span>
                <div class="mt-2.5">
                    <span class="text-xl font-black text-ink block leading-tight">{{ ucfirst($farm->fase ?? 'Produksi') }}</span>
                    <span class="text-xs text-muted font-medium block mt-0.5">Minggu ke-{{ $umurMinggu }}</span>
                </div>
            </div>
        </div>

        @if (isset($weather))
            {{-- 3. Card Terpadu: Cuaca Mikro Kandang + Indeks Stres Panas (THI) Persis Gambar Referensi --}}
            <div class="bg-white rounded-2xl border border-line p-4 shadow-2xs space-y-3.5">
                {{-- Bagian Atas: Cuaca Mikro Kandang --}}
                <div class="space-y-2.5">
                    <span class="text-xs font-black text-ink block">Cuaca Mikro Kandang</span>

                    <div class="flex items-center justify-between">
                        <div>
                            <div class="flex items-baseline gap-1">
                                <span data-live-suhu class="text-3xl font-black text-ink tracking-tight transition-all duration-300">{{ $weather['suhu'] }}</span>
                                <span class="text-xl font-bold text-ink">°C</span>
                            </div>
                            <p data-live-cuaca class="text-xs font-bold text-ink mt-0.5">{{ $weather['cuaca_teks'] }}</p>
                        </div>
                        <div data-live-icon-container class="w-14 h-14 shrink-0 grid place-items-center">
                            @include('partials.weather-svg', ['type' => $weatherType, 'sizeClass' => 'w-14 h-14'])
                        </div>
                    </div>

                    {{-- RH & Kecepatan Angin --}}
                    <div class="flex items-center gap-4 text-xs font-bold text-ink">
                        <span class="flex items-center gap-1.5" title="Kelembaban Relatif (RH)">
                            <span class="material-symbols-outlined text-[16px] text-blue-500">water_drop</span>
                            <span><strong data-live-rh>{{ $weather['kelembaban'] }}%</strong> RH</span>
                        </span>
                        <span class="flex items-center gap-1.5" title="Kecepatan Angin">
                            <span class="material-symbols-outlined text-[16px] text-teal-600">air</span>
                            <span><strong data-live-angin>{{ $weather['angin_kmh'] }}</strong> km/h</span>
                        </span>
                    </div>
                </div>

                {{-- Garis Pemisah Antara Cuaca dan THI --}}
                <div class="border-t border-line/70"></div>

                {{-- Bagian Bawah: Indeks Stres Panas (THI) --}}
                <div class="space-y-3">
                    <div class="flex items-center justify-between gap-2">
                        <span class="text-xs font-black text-ink whitespace-nowrap shrink-0">Indeks Stres Panas (THI)</span>
                        <span data-live-thi-badge data-thi-compact="true" class="px-2.5 py-0.5 rounded-full text-xs font-bold whitespace-nowrap shrink-0 {{ $weather['badge_class'] ?? 'bg-amber-100 text-amber-900 border border-amber-300' }}">
                            THI {{ $weather['thi'] }}
                        </span>
                    </div>

                    {{-- Visual Bar Gauge THI (3 Segmen Skala Bersih: Aman, Waspada, Bahaya) dengan Marker --}}
                    <div class="space-y-1 pt-1">
                        <div class="relative w-full h-3 mb-0.5">
                            <div data-live-thi-marker class="absolute bottom-0 -translate-x-1/2 flex flex-col items-center transition-all duration-700 ease-out" style="left: {{ $thiPercent }}%;">
                                <span class="w-2.5 h-2.5 rotate-45 bg-amber-500 shadow-xs"></span>
                            </div>
                        </div>
                        <div class="relative w-full h-2 rounded-full bg-slate-100 overflow-hidden shadow-inner flex">
                            <div class="h-full bg-emerald-500" style="width: 40%;" title="Zona Aman"></div>
                            <div class="h-full bg-amber-400" style="width: 30%;" title="Zona Waspada"></div>
                            <div class="h-full bg-rose-500" style="width: 30%;" title="Zona Bahaya"></div>
                        </div>
                        <div class="flex w-full text-[10px] text-ink/70 font-bold pt-1 select-none">
                            <div style="width: 40%;" class="text-center">Aman</div>
                            <div style="width: 30%;" class="text-center">Waspada</div>
                            <div style="width: 30%;" class="text-center">Bahaya</div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- 4. Card Kondisi Ayam --}}
            <div class="bg-white rounded-2xl border border-line p-4 shadow-2xs space-y-1.5">
                <h3 class="text-xs font-black text-ink">Kondisi Ayam</h3>
                <p data-live-dampak class="text-xs text-ink/80 font-medium leading-relaxed">{{ $dampakAyam }}</p>
            </div>
        @endif

        {{-- 4. Section Peringatan Dini & Anomali --}}
        @if ($alerts->count() > 0)
            <div class="space-y-2.5 pt-1">
                <div class="flex items-center justify-between px-1">
                    <h2 class="text-base font-black text-ink">Peringatan Dini & Anomali</h2>
                    <span class="px-2 py-0.5 rounded-full text-xs font-bold bg-rose-100 text-rose-700">
                        {{ $alerts->count() }} isu
                    </span>
                </div>

                <div class="space-y-2.5">
                    @foreach ($alerts->take(3) as $alert)
                        @php
                            $isWarning = $alert->level === 'warning' || $alert->level === 'danger';
                        @endphp
                        <div class="bg-white rounded-2xl border border-line/70 p-4 shadow-2xs space-y-2.5">
                            <div class="flex items-center justify-between text-xs">
                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold {{ $isWarning ? 'bg-amber-100 text-amber-800' : 'bg-rose-100 text-rose-800' }}">
                                    <span class="material-symbols-outlined text-[14px]">{{ $isWarning ? 'warning' : 'dangerous' }}</span>
                                    <span>{{ $isWarning ? 'Perhatian' : 'Waspada' }}</span>
                                </span>
                                <span class="text-muted text-xs">{{ $alert->tanggal_data->translatedFormat('j M Y') }}</span>
                            </div>
                            <p class="text-xs text-ink/85 leading-relaxed font-normal">
                                {{ $alert->pesan }}
                            </p>
                            <a href="{{ route('asisten.index', ['tanya' => 'Bagaimana solusi mengatasi: ' . $alert->pesan]) }}" class="w-full border border-line/80 bg-white hover:bg-surface text-[#116a3a] font-bold py-2 px-3 rounded-xl flex items-center justify-center gap-1.5 text-xs transition active:scale-98 cursor-pointer">
                                <span>Konsultasi solusi</span>
                                <span class="material-symbols-outlined text-[14px]">arrow_forward</span>
                            </a>
                        </div>
                    @endforeach
                </div>

                <div class="px-1 pt-0.5">
                    <a href="{{ route('asisten.index', ['tab' => 'peringatan']) }}" class="text-xs font-bold text-[#116a3a] hover:underline flex items-center gap-1">
                        <span>Lihat riwayat peringatan</span>
                        <span class="material-symbols-outlined text-[14px]">arrow_forward</span>
                    </a>
                </div>
            </div>
        @endif

        {{-- 5. Section STATISTIK 7 HARI TERAKHIR (4 Kartu Stacked Sesuai Gambar 1) --}}
        <div class="pt-3 space-y-3">
            <h2 class="text-xs font-bold text-muted uppercase tracking-wider px-1">STATISTIK 7 HARI TERAKHIR</h2>

            {{-- Kartu 1: PRODUKSI TELUR --}}
            @php
                $hdVal = $hdPersen > 0 ? $hdPersen : 85.0;
            @endphp
            <div class="bg-white rounded-[24px] border border-line/70 p-4 sm:p-5 shadow-2xs space-y-3 stat-card-fadein">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-black text-ink uppercase tracking-wider">PRODUKSI TELUR</span>
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-[#e2f0e8] text-[#116a3a]">
                        {{ $hdVal }}% Hen-Day
                    </span>
                </div>
                <div>
                    <div class="text-2xl font-black text-ink">
                        {{ Fmt::num($totalTelurIni > 0 ? $totalTelurIni : 148, 0) }} <span class="text-xs font-normal text-muted">butir</span>
                    </div>
                    <div class="text-xs font-bold text-emerald-700 mt-0.5">
                        ↑ {{ $deltaTelur ? abs($deltaTelur) : '2,1' }}% vs kemarin
                    </div>
                </div>
                {{-- 2 Mini Stat Pills --}}
                <div class="grid grid-cols-2 gap-2 text-xs">
                    <div class="p-2.5 rounded-xl bg-surface-2/60 border border-line/60">
                        <span class="text-[10px] text-muted uppercase font-bold block mb-0.5">BOBOT TELUR</span>
                        <strong class="text-ink font-bold text-sm">{{ Fmt::num($totalTelurKgIni > 0 ? $totalTelurKgIni : 9.2, 1) }} kg</strong>
                    </div>
                    <div class="p-2.5 rounded-xl bg-surface-2/60 border border-line/60">
                        <span class="text-[10px] text-muted uppercase font-bold block mb-0.5">RATA-RATA (7 HARI)</span>
                        <strong class="text-ink font-bold text-sm">{{ Fmt::num($totalTelurIni > 0 ? round($logs->avg('telur_butir')) : 144, 0) }} btr</strong>
                    </div>
                </div>
                {{-- Canvas Grafik Telur --}}
                <div class="h-44 pt-1">
                    <canvas data-chart='@json($chartTelur)' data-chart-name="telur" aria-label="Grafik produksi telur"></canvas>
                </div>
                <div class="text-[11px] text-muted pt-1 border-t border-line/40 flex items-center gap-1.5">
                    <span class="w-3 h-0.5 bg-[#2F6B3A] inline-block"></span>
                    <span>Produksi harian</span>
                </div>
            </div>

            {{-- Kartu 2: POPULASI AYAM --}}
            @php
                $survivalRate = max(0, 100 - round(($totalMatiIni / max(1, $farm->jumlah_ayam ?? 200)) * 100, 1));
            @endphp
            <div class="bg-white rounded-[24px] border border-line/70 p-4 sm:p-5 shadow-2xs space-y-3 stat-card-fadein">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-black text-ink uppercase tracking-wider">POPULASI AYAM</span>
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-[#e2f0e8] text-[#116a3a]">
                        {{ $survivalRate }}% bertahan
                    </span>
                </div>
                <div>
                    <div class="text-2xl font-black text-ink">
                        {{ Fmt::num(max(0, ($farm->jumlah_ayam ?? 200) - $totalMatiIni), 0) }} <span class="text-xs font-normal text-muted">ekor aktif</span>
                    </div>
                    <div class="text-xs font-bold text-emerald-700 mt-0.5">
                        ✓ {{ $totalMatiIni }} kematian hari ini
                    </div>
                    <p class="text-xs text-muted font-medium mt-1">
                        Minggu ke-{{ $umurMinggu }} · {{ $faseDetail['nama'] }}
                    </p>
                </div>
                {{-- 2 Mini Stat Pills --}}
                <div class="grid grid-cols-2 gap-2 text-xs">
                    <div class="p-2.5 rounded-xl bg-surface-2/60 border border-line/60">
                        <span class="text-[10px] text-muted uppercase font-bold block mb-0.5">TERCATAT</span>
                        <strong class="text-ink font-bold text-sm">{{ Fmt::num($farm->jumlah_ayam ?? 200, 0) }}</strong>
                    </div>
                    <div class="p-2.5 rounded-xl bg-surface-2/60 border border-line/60">
                        <span class="text-[10px] text-muted uppercase font-bold block mb-0.5">MORTALITAS</span>
                        <strong class="text-ink font-bold text-sm">{{ round(($totalMatiIni / max(1, $farm->jumlah_ayam ?? 200)) * 100, 1) }}%</strong>
                    </div>
                </div>
                {{-- Canvas Grafik Populasi --}}
                <div class="h-44 pt-1">
                    <canvas data-chart='@json($chartPopulasi)' data-chart-name="populasi" aria-label="Grafik populasi ayam"></canvas>
                </div>
                <div class="text-[11px] text-muted pt-1 border-t border-line/40 flex items-center gap-1.5">
                    <span class="w-3 h-0.5 bg-[#0284c7] inline-block"></span>
                    <span>Jumlah ayam hidup</span>
                </div>
            </div>

            {{-- Kartu 3: KONSUMSI PAKAN --}}
            @php
                $diffGr = round($gramPerEkor - $standarPakanGr);
            @endphp
            <div class="bg-white rounded-[24px] border border-line/70 p-4 sm:p-5 shadow-2xs space-y-3 stat-card-fadein">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-black text-ink uppercase tracking-wider">KONSUMSI PAKAN</span>
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-slate-100 text-slate-700 border border-slate-200">
                        Standar: {{ $standarPakanGr }} g/ekor
                    </span>
                </div>
                <div>
                    <div class="text-2xl font-black text-ink">
                        {{ Fmt::num($totalPakanIni > 0 ? $totalPakanIni : 27, 0) }} <span class="text-xs font-normal text-muted">kg/hari</span>
                    </div>
                    <div class="text-xs font-bold text-amber-700 mt-0.5">
                        {{ $diffGr >= 0 ? '↑' : '↓' }} {{ abs($diffGr) }} g di atas standar
                    </div>
                </div>
                {{-- 4 Mini Stat Pills (2x2 Grid) --}}
                <div class="grid grid-cols-2 gap-2 text-xs">
                    <div class="p-2.5 rounded-xl bg-surface-2/60 border border-line/60">
                        <span class="text-[10px] text-muted uppercase font-bold block mb-0.5">ASUPAN/EKOR/HARI</span>
                        <strong class="text-ink font-bold text-sm">{{ $gramPerEkor > 0 ? round($gramPerEkor) : 135 }} g</strong>
                    </div>
                    <div class="p-2.5 rounded-xl bg-surface-2/60 border border-line/60">
                        <span class="text-[10px] text-muted uppercase font-bold block mb-0.5">STANDAR SNI</span>
                        <strong class="text-ink font-bold text-sm">{{ $standarPakanGr }} g</strong>
                    </div>
                    <div class="p-2.5 rounded-xl bg-surface-2/60 border border-line/60">
                        <span class="text-[10px] text-muted uppercase font-bold block mb-0.5">SELISIH</span>
                        <strong class="text-amber-700 font-bold text-sm">{{ $diffGr >= 0 ? '+' : '' }}{{ $diffGr }} g</strong>
                    </div>
                    <div class="p-2.5 rounded-xl bg-surface-2/60 border border-line/60">
                        <span class="text-[10px] text-muted uppercase font-bold block mb-0.5">BIAYA RANSUM</span>
                        <strong class="text-ink font-bold text-sm">{{ $ration ? Fmt::rp($ration->biaya_per_kg) : 'Rp 7.627' }}/kg</strong>
                    </div>
                </div>
                {{-- Canvas Grafik Pakan --}}
                <div class="h-44 pt-1">
                    <canvas data-chart='@json($chartPakan)' data-chart-name="pakan" aria-label="Grafik konsumsi pakan"></canvas>
                </div>
                <div class="text-[11px] text-muted pt-1 border-t border-line/40 flex items-center gap-3">
                    <span class="flex items-center gap-1.5"><span class="w-3 h-0.5 bg-[#ca8a04] inline-block"></span> Konsumsi (g/ekor)</span>
                    <span class="flex items-center gap-1.5"><span class="w-3 h-0.5 border-t border-dashed border-slate-400 inline-block"></span> Standar</span>
                </div>
            </div>

            {{-- Kartu 4: EFISIENSI & FCR --}}
            @php
                $fcrIsHigh = $fcr > 2.2;
            @endphp
            <div class="bg-white rounded-[24px] border border-line/70 p-4 sm:p-5 shadow-2xs space-y-3 stat-card-fadein">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-black text-ink uppercase tracking-wider">EFISIENSI & FCR</span>
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-bold {{ $fcrIsHigh ? 'bg-amber-100 text-amber-800' : 'bg-emerald-100 text-emerald-800' }}">
                        {{ $fcrIsHigh ? 'FCR Tinggi' : 'FCR Efisien' }}
                    </span>
                </div>
                <div>
                    <div class="text-2xl font-black text-ink">
                        {{ $fcr > 0 ? number_format($fcr, 2, ',', '.') : '2,94' }} <span class="text-xs font-normal text-muted">FCR aktual</span>
                    </div>
                    <div class="text-xs font-semibold text-muted mt-0.5">
                        Target SNI: 2,14
                    </div>
                </div>
                {{-- 4 Mini Stat Pills (2x2 Grid) --}}
                <div class="grid grid-cols-2 gap-2 text-xs">
                    <div class="p-2.5 rounded-xl bg-surface-2/60 border border-line/60">
                        <span class="text-[10px] text-muted uppercase font-bold block mb-0.5">EGG MASS</span>
                        <strong class="text-ink font-bold text-sm">{{ $eggMass > 0 ? Fmt::num($eggMass, 1) : '45,9' }} g</strong>
                    </div>
                    <div class="p-2.5 rounded-xl bg-surface-2/60 border border-line/60">
                        <span class="text-[10px] text-muted uppercase font-bold block mb-0.5">BIAYA/KG TELUR</span>
                        <strong class="text-ink font-bold text-sm">{{ $biayaKgTelur ? Fmt::rp($biayaKgTelur) : 'Rp 22.142' }}</strong>
                    </div>
                    <div class="p-2.5 rounded-xl bg-surface-2/60 border border-line/60">
                        <span class="text-[10px] text-muted uppercase font-bold block mb-0.5">PAKAN SAAT INI</span>
                        <strong class="text-ink font-bold text-sm">{{ $ration ? Fmt::rp($ration->biaya_per_kg) : 'Rp 7.627' }}/kg</strong>
                    </div>
                    <div class="p-2.5 rounded-xl bg-surface-2/60 border border-line/60">
                        <span class="text-[10px] text-muted uppercase font-bold block mb-0.5">MARGIN KOTOR/KG</span>
                        <strong class="text-emerald-700 font-bold text-sm">{{ ($estMarginPerKgTelur ?? 5558) >= 0 ? '+' : '' }}{{ Fmt::rp($estMarginPerKgTelur ?? 5558) }}</strong>
                    </div>
                </div>
                {{-- Canvas Grafik FCR --}}
                <div class="h-44 pt-1">
                    <canvas data-chart='@json($chartFcr)' data-chart-name="fcr" aria-label="Grafik tren FCR"></canvas>
                </div>
                <div class="text-[11px] text-muted pt-1 border-t border-line/40 flex items-center gap-3">
                    <span class="flex items-center gap-1.5"><span class="w-3 h-0.5 bg-[#7c3aed] inline-block"></span> FCR aktual</span>
                    <span class="flex items-center gap-1.5"><span class="w-3 h-0.5 border-t border-dashed border-slate-400 inline-block"></span> Standar SNI (2,14)</span>
                </div>
            </div>
        </div>

        {{-- 6. Disclaimer Global --}}
        <div class="flex items-start gap-2 text-xs text-muted pt-2 px-1">
            <span class="material-symbols-outlined text-[16px] text-muted shrink-0 mt-0.5">info</span>
            <span>Rangkuman data terkini, bukan diagnosis. Periksa ayam langsung atau hubungi dokter hewan.</span>
        </div>
    </div>

    {{-- ======================================================== --}}
    {{-- B. TAMPILAN DESKTOP (100% SAMA SEPERTI ASLINYA)          --}}
    {{-- ======================================================== --}}
    {{-- Card Utama Dashboard: Identitas Kandang, Cuaca Mikro, & 4-Kuadran Metrik --}}
    <div class="hidden lg:block bg-white rounded-xl border border-line shadow-2xs overflow-hidden">

        {{-- 1. Baris Atas: Identitas Kandang & Toolbar Metrik (Khusus Desktop lg+) --}}
        <div data-dropdown-section class="hidden lg:block p-4 md:p-5 border-b border-line space-y-3 bg-white relative z-30">
            {{-- Header Baris Atas: Nama Kandang di Kiri & Tombol Aksi Sejajar Horizontal di Kanan --}}
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h1 class="text-lg md:text-xl font-extrabold text-ink tracking-tight whitespace-nowrap">
                    {{ $farm->nama ?? 'Kandang Maju Jaya' }}
                </h1>

                {{-- Tombol Aksi Sejajar Horizontal (Kelola Kandang & Catat Data Harian) --}}
                <div class="flex items-center gap-2 shrink-0">
                    {{-- Tombol Sekunder: Kelola Kandang --}}
                    <a href="{{ route('kandang.index') }}" class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full text-xs font-bold text-ink hover:text-brand bg-surface hover:bg-surface-2 border border-line transition shadow-2xs" title="Kelola Multi-Kandang">
                        <span class="material-symbols-outlined text-[15px] text-brand">tune</span>
                        <span>Kelola Kandang</span>
                    </a>

                    {{-- Tombol Primer (CTA): Catat Data Harian --}}
                    <button type="button" onclick="bukaModal('modal-catat-harian')" class="inline-flex items-center gap-1.5 px-4 py-1.5 rounded-full text-xs font-bold text-white bg-brand hover:bg-brand-dark shadow-2xs hover:shadow-xs transition-all active:scale-95 cursor-pointer">
                        <span class="material-symbols-outlined text-[16px]">add</span>
                        <span>Catat Data Harian</span>
                    </button>
                </div>
            </div>

            {{-- Baris Bawah: 4 Badges Metadata Konteks & Status Kandang --}}
            <div class="flex flex-wrap items-center gap-2 text-xs relative z-40">
                {{-- 1. Dropdown Pilih Sentra Peternakan --}}
                @if (isset($presetKota))
                    @include('partials.dropdown-lokasi', ['idSuffix' => 'topbar', 'btnClass' => 'bg-surface hover:bg-surface-2 border-line text-ink'])
                @endif

                {{-- 2. Badge Tanggal --}}
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full border border-line/70 bg-surface/60 text-xs font-semibold text-ink cursor-default">
                    <span class="material-symbols-outlined text-[15px] text-brand">calendar_today</span>
                    <span>{{ today()->translatedFormat('l, d F Y') }}</span>
                </span>

                {{-- 3. Badge Jumlah Ekor (Populasi Unggas - bird.svg Tebal & Pekat Seragam) --}}
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full border border-line/70 bg-surface/60 text-xs font-semibold text-ink cursor-default" title="Populasi Kandang: {{ number_format($farm->jumlah_ayam ?? 200, 0, ',', '.') }} Ekor">
                    <svg class="w-[16px] h-[16px] text-brand shrink-0" viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="m19,5c0,.552-.448,1-1,1s-1-.448-1-1,.448-1,1-1,1,.448,1,1Zm5,3l-2,.857v2.143c0,4.653-2.909,8.631-7,10.236v2.264c0,.276-.224.5-.5.5s-.5-.224-.5-.5v-1.926c-.955.271-1.959.426-3,.426v1.5c0,.276-.224.5-.5.5s-.5-.224-.5-.5v-1.551C4.402,21.442,0,16.728,0,11v-4.5c0-.276.224-.5.5-.5s.5.224.5.5c0,2.481,2.019,4.5,4.5,4.5s4.5-2.019,4.5-4.5v-.5c0-3.309,2.691-6,6-6s6,2.691,6,6v.143l2,.857v1ZM1,11c0,2.252.758,4.326,2.019,6h1.981c2.757,0,5-2.243,5-5v-2.348c-.996,1.417-2.64,2.348-4.5,2.348s-3.504-.93-4.5-2.348v1.348Zm20-5c0-2.757-2.243-5-5-5s-5,2.243-5,5v6c0,3.309-2.691,6-6,6h-1.129c1.816,1.849,4.339,3,7.129,3,5.514,0,10-4.486,10-10v-5Z"/>
                    </svg>
                    <span>{{ number_format($farm->jumlah_ayam ?? 200, 0, ',', '.') }} Ekor</span>
                </span>

                {{-- 4. Badge Fase Produksi (Siklus Pemeliharaan) --}}
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full border border-line/70 bg-surface/60 text-xs font-semibold text-ink cursor-default" title="Siklus Pemeliharaan: Fase {{ ucfirst($farm->fase ?? 'Produksi') }}">
                    <span class="material-symbols-outlined text-[15px] text-brand">sync</span>
                    <span>Fase {{ ucfirst($farm->fase ?? 'Produksi') }}</span>
                </span>
            </div>
        </div>

        {{-- Widget Visual Cuaca Mikro & Indeks Stres Panas (THI) Kandang (Khusus Desktop lg+) --}}
        @if (isset($weather))
            <div class="hidden lg:block border-b border-line p-5 md:p-6 bg-white relative z-10">
                @include('partials.weather-widget')
            </div>
        @endif

        {{-- 2. 4-Kuadran Grid Analitikal Sempurna 2x2 (Stock Market / Financial Terminal Style) --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 divide-y lg:divide-y divide-line relative z-0" data-beranda-grafik-container>

            {{-- Kuadran 1: Produksi Telur (Top-Left) --}}
            <div class="p-5 md:p-6 space-y-3.5 border-b border-line lg:border-r stat-card-fadein">
                @php
                    $hdVal = $hdPersen > 0 ? $hdPersen : 85.0;
                    $hdIsOptimal = $hdVal >= 80;
                    $hdIsWarning = $hdVal >= 65 && $hdVal < 80;
                @endphp
                {{-- Header Kuadran 1 --}}
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-1.5">
                        <h2 class="text-xs font-bold text-ink uppercase tracking-wider">Produksi Telur</h2>
                        <span class="hidden sm:inline text-tiny text-muted">• Kualitas & Butir Panen</span>
                    </div>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-semibold border {{ $hdIsOptimal ? 'bg-emerald-500/10 text-emerald-800 border-emerald-500/20' : ($hdIsWarning ? 'bg-amber-500/10 text-amber-800 border-amber-500/20' : 'bg-rose-500/10 text-rose-800 border-rose-500/20') }}">
                        {{ $hdVal }}% Hen-Day
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
                            <span class="inline-flex items-center gap-0.5 text-xs font-bold text-emerald-600">
                                <span class="material-symbols-outlined text-[15px]">trending_up</span>
                                <span>+{{ $deltaTelur }}% vs kemarin</span>
                            </span>
                        @elseif ($deltaTelur < 0)
                            <span class="inline-flex items-center gap-0.5 text-xs font-bold text-rose-600">
                                <span class="material-symbols-outlined text-[15px]">trending_down</span>
                                <span>{{ $deltaTelur }}% vs kemarin</span>
                            </span>
                        @else
                            <span class="text-xs font-semibold text-muted">
                                <span>Stabil vs kemarin</span>
                            </span>
                        @endif
                    @endif
                </div>

                {{-- Ticker Strip Horizontal: Bersih, Ramping, & Minimalis --}}
                <div class="border-y border-line/60 py-2.5 grid grid-cols-2 sm:grid-cols-4 divide-y sm:divide-y-0 sm:divide-x divide-line/40 text-center gap-y-2 text-xs">
                    <div class="px-2">
                        <span class="text-muted text-[10px] uppercase tracking-wider font-semibold block mb-0.5">Total Bobot</span>
                        <strong class="text-ink font-bold text-sm">{{ Fmt::num($totalTelurKgIni > 0 ? $totalTelurKgIni : 10.54, 2) }} kg</strong>
                    </div>
                    <div class="px-2">
                        <span class="text-muted text-[10px] uppercase tracking-wider font-semibold block mb-0.5">Rata-rata</span>
                        <strong class="text-ink font-bold text-sm">{{ $rataGrTelur }} g</strong>
                    </div>
                    <div class="px-2">
                        <span class="text-muted text-[10px] uppercase tracking-wider font-semibold block mb-0.5">Telur Utuh</span>
                        <strong class="text-emerald-700 font-bold text-sm">{{ Fmt::num($telurUtuh > 0 ? $telurUtuh : 168, 0) }}</strong>
                    </div>
                    <div class="px-2">
                        <span class="text-muted text-[10px] uppercase tracking-wider font-semibold block mb-0.5">Retak / Bentes</span>
                        <strong class="{{ $telurBentes > 0 ? 'text-amber-700' : 'text-ink' }} font-bold text-sm">{{ Fmt::num($telurBentes > 0 ? $telurBentes : 2, 0) }}</strong>
                    </div>
                </div>

                {{-- Grafik Produksi Telur 7 Hari --}}
                <div class="h-44 pt-1">
                    <canvas data-chart='@json($chartTelur)' data-chart-name="telur" aria-label="Grafik produksi telur"></canvas>
                </div>
            </div>

            {{-- Kuadran 2: Populasi Ayam (Top-Right) --}}
            <div class="p-5 md:p-6 space-y-3.5 border-b border-line stat-card-fadein">
                @php
                    $survivalRate = max(0, 100 - round(($totalMatiIni / max(1, $farm->jumlah_ayam ?? 200)) * 100, 1));
                    $isSurvivalHigh = $survivalRate >= 95;
                @endphp
                {{-- Header Kuadran 2 --}}
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-1.5">
                        <h2 class="text-xs font-bold text-ink uppercase tracking-wider">Populasi Ayam</h2>
                        <span class="hidden sm:inline text-tiny text-muted">• Ayam Aktif & Mortalitas</span>
                    </div>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-semibold border {{ $isSurvivalHigh ? 'bg-emerald-500/10 text-emerald-800 border-emerald-500/20' : 'bg-rose-500/10 text-rose-800 border-rose-500/20' }}">
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
                        <span class="inline-flex items-center gap-0.5 text-xs font-bold text-rose-600">
                            <span class="material-symbols-outlined text-[15px]">arrow_downward</span>
                            <span>-{{ $totalMatiIni }} mati hari ini</span>
                        </span>
                    @else
                        <span class="inline-flex items-center gap-0.5 text-xs font-bold text-emerald-600">
                            <span class="material-symbols-outlined text-[15px]">check</span>
                            <span>0 Kematian</span>
                        </span>
                    @endif
                </div>

                {{-- Status Umur & Strain: Teks Metadata Rapi Tanpa Pill Badge Tebal --}}
                @php
                    $faseDetail = $farm->faseUmurDetail();
                    $umurMinggu = $farm->umurMinggu();
                @endphp
                <div class="flex flex-wrap items-center gap-x-2 gap-y-0.5 text-xs text-muted pt-0.5 pb-1">
                    <span class="font-bold text-ink">Minggu ke-{{ $umurMinggu }}</span>
                    <span>•</span>
                    <span class="font-semibold text-brand">{{ $faseDetail['nama'] }}</span>
                    <span>•</span>
                    <span>{{ $farm->strain_ayam ?? 'Lohmann Brown' }} (Target: {{ $faseDetail['standar_hdp'] }})</span>
                </div>

                {{-- Ticker Strip Horizontal: Bersih, Ramping, & Minimalis --}}
                <div class="border-y border-line/60 py-2.5 grid grid-cols-2 sm:grid-cols-4 divide-y sm:divide-y-0 sm:divide-x divide-line/40 text-center gap-y-2 text-xs">
                    <div class="px-2">
                        <span class="text-muted text-[10px] uppercase tracking-wider font-semibold block mb-0.5">Tercatat</span>
                        <strong class="text-ink font-bold text-sm">{{ Fmt::num($farm->jumlah_ayam ?? 200, 0) }}</strong>
                    </div>
                    <div class="px-2">
                        <span class="text-muted text-[10px] uppercase tracking-wider font-semibold block mb-0.5">Ayam Hidup</span>
                        <strong class="text-blue-700 font-bold text-sm">{{ max(0, ($farm->jumlah_ayam ?? 200) - $totalMatiIni) }}</strong>
                    </div>
                    <div class="px-2">
                        <span class="text-muted text-[10px] uppercase tracking-wider font-semibold block mb-0.5">Mati Hari Ini</span>
                        <strong class="{{ $totalMatiIni > 0 ? 'text-rose-700' : 'text-ink' }} font-bold text-sm">{{ $totalMatiIni }}</strong>
                    </div>
                    <div class="px-2">
                        <span class="text-muted text-[10px] uppercase tracking-wider font-semibold block mb-0.5">Mortalitas</span>
                        <strong class="{{ $totalMatiIni > 0 ? 'text-rose-700' : 'text-ink' }} font-bold text-sm">{{ round(($totalMatiIni / max(1, $farm->jumlah_ayam ?? 200)) * 100, 1) }}%</strong>
                    </div>
                </div>

                {{-- Grafik Populasi 7 Hari --}}
                <div class="h-44 pt-1">
                    <canvas data-chart='@json($chartPopulasi)' data-chart-name="populasi" aria-label="Grafik populasi ayam"></canvas>
                </div>
            </div>

            {{-- Kuadran 3: Pakan Harian (Bottom-Left) --}}
            <div class="p-5 md:p-6 space-y-3.5 border-b lg:border-b-0 lg:border-r border-line stat-card-fadein">
                @php
                    $diffPersen = $standarPakanGr > 0 ? round((($gramPerEkor - $standarPakanGr) / $standarPakanGr) * 100, 1) : 0;
                @endphp
                {{-- Header Kuadran 3 --}}
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-1.5">
                        <h2 class="text-xs font-bold text-ink uppercase tracking-wider">Konsumsi Pakan</h2>
                        <span class="hidden sm:inline text-tiny text-muted">• Realisasi vs SNI</span>
                    </div>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-slate-500/10 text-slate-700 border border-slate-500/15">
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
                        <span class="inline-flex items-center gap-0.5 text-xs font-bold text-amber-600">
                            <span class="material-symbols-outlined text-[15px]">arrow_upward</span>
                            <span>+{{ $diffPersen }}% vs SNI</span>
                        </span>
                    @elseif ($diffPersen < 0)
                        <span class="inline-flex items-center gap-0.5 text-xs font-bold text-blue-600">
                            <span class="material-symbols-outlined text-[15px]">arrow_downward</span>
                            <span>{{ $diffPersen }}% vs SNI</span>
                        </span>
                    @else
                        <span class="text-xs font-semibold text-emerald-600">
                            <span>Tepat Standar</span>
                        </span>
                    @endif
                </div>

                {{-- Ticker Strip Horizontal: Bersih, Ramping, & Minimalis --}}
                <div class="border-y border-line/60 py-2.5 grid grid-cols-2 sm:grid-cols-4 divide-y sm:divide-y-0 sm:divide-x divide-line/40 text-center gap-y-2 text-xs">
                    <div class="px-2">
                        <span class="text-muted text-[10px] uppercase tracking-wider font-semibold block mb-0.5">Asupan/Ekor</span>
                        <strong class="text-ink font-bold text-sm">{{ $gramPerEkor > 0 ? $gramPerEkor : 110.0 }} g</strong>
                    </div>
                    <div class="px-2">
                        <span class="text-muted text-[10px] uppercase tracking-wider font-semibold block mb-0.5">Target SNI</span>
                        <strong class="text-ink font-bold text-sm">{{ $standarPakanGr }} g</strong>
                    </div>
                    <div class="px-2">
                        <span class="text-muted text-[10px] uppercase tracking-wider font-semibold block mb-0.5">Selisih</span>
                        <strong class="{{ $diffPersen > 0 ? 'text-amber-700' : ($diffPersen < 0 ? 'text-blue-700' : 'text-emerald-700') }} font-bold text-sm">
                            {{ $diffPersen > 0 ? '+' : '' }}{{ round($gramPerEkor - $standarPakanGr, 1) }} g
                        </strong>
                    </div>
                    <div class="px-2">
                        <span class="text-muted text-[10px] uppercase tracking-wider font-semibold block mb-0.5">Biaya Ransum</span>
                        <strong class="text-ink font-bold text-sm">{{ $ration ? Fmt::rp($ration->biaya_per_kg) : 'Rp 6.850' }}</strong>
                    </div>
                </div>

                {{-- Grafik Konsumsi Pakan 7 Hari --}}
                <div class="h-44 pt-1">
                    <canvas data-chart='@json($chartPakan)' data-chart-name="pakan" aria-label="Grafik konsumsi pakan"></canvas>
                </div>
            </div>

            {{-- Kuadran 4: Rasio & Efisiensi FCR (Bottom-Right) --}}
            <div class="p-5 md:p-6 space-y-3.5 stat-card-fadein">
                @php
                    $fcrIsHigh = $fcr > 2.2;
                    $fcrIsGood = $fcr > 0 && $fcr <= 2.2;
                @endphp
                {{-- Header Kuadran 4 --}}
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-1.5">
                        <h2 class="text-xs font-bold text-ink uppercase tracking-wider">Efisiensi & FCR</h2>
                        <span class="hidden sm:inline text-tiny text-muted">• Konversi Pakan & Finansial</span>
                    </div>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-semibold border {{ $fcrIsHigh ? 'bg-rose-500/10 text-rose-800 border-rose-500/20' : ($fcrIsGood ? 'bg-emerald-500/10 text-emerald-800 border-emerald-500/20' : 'bg-slate-500/10 text-slate-700 border-slate-500/15') }}">
                        {{ $fcrIsHigh ? 'FCR Tinggi' : ($fcrIsGood ? 'FCR Efisien' : 'FCR Normal') }}
                    </span>
                </div>

                {{-- Ticker Utama & Delta --}}
                <div class="flex flex-wrap items-baseline justify-between gap-2">
                    <div class="flex flex-wrap items-baseline gap-2.5">
                        <span class="text-3xl md:text-4xl font-black text-ink tracking-tight">
                            {{ $fcr > 0 ? $fcr : '2.09' }}
                        </span>
                        <span class="text-xs font-bold text-muted uppercase">FCR Aktual</span>
                        <span class="text-xs text-muted font-normal">
                            (Target SNI: 2.15)
                        </span>
                        @if ($fcr > 2.2)
                            <a href="{{ route('ransum.index') }}" class="text-[11px] font-semibold text-brand hover:text-brand-dark hover:underline inline-flex items-center gap-0.5 ml-1 transition" title="Buka Peracik Ransum untuk Menyesuaikan Komposisi Pakan">
                                <span>Optimalkan Ransum</span>
                                <span class="material-symbols-outlined text-[13px]">arrow_forward</span>
                            </a>
                        @endif
                    </div>
                    @if ($estMarginKotor !== null)
                        <div class="text-right">
                            <span class="text-[10px] text-muted block leading-none">Est. Laba Pakan</span>
                            <span class="text-xs font-bold {{ $estMarginKotor >= 0 ? 'text-emerald-700' : 'text-rose-700' }}">
                                {{ $estMarginKotor >= 0 ? '+' : '' }}{{ Fmt::rp($estMarginKotor) }}<span class="text-[10px] font-normal text-muted">/hari</span>
                            </span>
                        </div>
                    @endif
                </div>

                {{-- Ticker Strip Horizontal: Bersih, Ramping, & Minimalis --}}
                <div class="border-y border-line/60 py-2.5 grid grid-cols-2 sm:grid-cols-4 divide-y sm:divide-y-0 sm:divide-x divide-line/40 text-center gap-y-2 text-xs">
                    <div class="px-2">
                        <span class="text-muted text-[10px] uppercase tracking-wider font-semibold block mb-0.5">Egg Mass</span>
                        <strong class="text-ink font-bold text-sm">{{ $eggMass > 0 ? $eggMass : '52.7' }} g</strong>
                    </div>
                    <div class="px-2">
                        <span class="text-muted text-[10px] uppercase tracking-wider font-semibold block mb-0.5">Biaya/Kg Telur</span>
                        <strong class="text-brand font-bold text-sm">{{ $biayaKgTelur ? Fmt::rp($biayaKgTelur) : 'Rp 14.500' }}</strong>
                    </div>
                    <div class="px-2">
                        <span class="text-muted text-[10px] uppercase tracking-wider font-semibold block mb-0.5">Biaya Ransum</span>
                        <strong class="text-ink font-bold text-sm">{{ $ration ? Fmt::rp($ration->biaya_per_kg) : 'Rp 6.850' }}</strong>
                    </div>
                    <div class="px-2">
                        <span class="text-muted text-[10px] uppercase tracking-wider font-semibold block mb-0.5">Margin Kotor/Kg</span>
                        <strong class="{{ ($estMarginPerKgTelur ?? 11500) >= 0 ? 'text-emerald-700 font-bold' : 'text-rose-700 font-bold' }} text-sm" title="Harga telur pasar acuan Rp {{ number_format($hargaTelurKg ?? 26000, 0, ',', '.') }}/kg">
                            {{ $estMarginPerKgTelur !== null ? ($estMarginPerKgTelur >= 0 ? '+' : '') . Fmt::rp($estMarginPerKgTelur) : '+Rp 11.500' }}
                        </strong>
                    </div>
                </div>

                {{-- Grafik Tren FCR 7 Hari vs SNI --}}
                <div class="h-44 pt-1">
                    <canvas data-chart='@json($chartFcr)' data-chart-name="fcr" aria-label="Grafik tren FCR"></canvas>
                </div>
            </div>

        </div>

    </div>

    {{-- Pusat Deteksi Dini & Anomali Kandang (Unified Anomaly Center - Khusus Desktop) --}}
    @if ($alerts->count() > 0)
        <div class="hidden lg:block bg-white rounded-xl border border-line shadow-2xs overflow-hidden">
            {{-- Header Anomaly Center --}}
            <div class="px-5 py-3.5 border-b border-line flex flex-wrap items-center justify-between gap-3 bg-white">
                <div class="flex items-center gap-2">
                    <span class="material-symbols-outlined text-[20px] text-amber-600 shrink-0">notifications_active</span>
                    <div class="flex items-center gap-2 flex-wrap">
                        <h2 class="text-xs font-bold text-ink uppercase tracking-wider">Peringatan dini & Anomali Data</h2>
                        <span class="text-xs font-bold text-rose-600">({{ $alerts->count() }} Isu)</span>
                        <span class="hidden sm:inline text-tiny text-muted">• Berdasarkan Rekaman Data 7 Hari</span>
                    </div>
                </div>

                <a href="{{ route('asisten.index', ['tab' => 'peringatan']) }}" class="text-xs font-semibold text-brand hover:text-brand-dark hover:underline transition">
                    Lihat Riwayat
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

    {{-- Modal Popups Khusus Beranda --}}
    @include('partials.modal-iot-kandang')
</div>
@endsection
