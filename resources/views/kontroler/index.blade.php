@extends('layouts.app')
@section('judul', 'Smart Controller')
@section('isi')
@use('App\Support\Fmt')

<div class="space-y-5">
    {{-- 1. Header Banner Halaman: Smart Controller & Status Gateway IoT --}}
    <div class="bg-white rounded-xl border border-line p-5 md:p-6 shadow-2xs">
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
            <div>
                <h1 class="text-xl md:text-2xl font-black text-ink tracking-tight">Pusat Kendali Aktuator Kandang</h1>
                <p class="text-xs md:text-sm text-muted mt-1 leading-relaxed max-w-2xl">
                    Pengendali mikroklimat kandang tertutup (<em>closed-loop system</em>) <em>hardware-agnostic</em> berbasis telemetri mikrokontroler IoT (ESP32) dan otomatisasi biometeorologi THI ayam petelur tropis.
                </p>
            </div>

            {{-- Status Badges Konteks Kandang --}}
            <div class="flex items-center gap-2 flex-wrap lg:justify-end">
                {{-- Badge Status Sensor Online --}}
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold border {{ $isSensorOnline ? 'bg-emerald-50 text-emerald-800 border-emerald-200' : 'bg-surface text-muted border-line' }}" title="{{ $isSensorOnline ? 'ESP32 aktif mengirim data telemetri' : 'Sensor fisik offline, sistem menggunakan acuan BMKG' }}">
                    <span class="w-2 h-2 rounded-full {{ $isSensorOnline ? 'bg-emerald-500 animate-pulse' : 'bg-blue-400' }}"></span>
                    <span>{{ $isSensorOnline ? 'ESP32 Online (Live)' : 'Acuan BMKG Live' }}</span>
                </span>

                {{-- Lokasi Kandang --}}
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold bg-[#f8fbf9] border border-line text-ink">
                    <span class="material-symbols-outlined text-[16px] text-brand">location_on</span>
                    <span>{{ $farm->lokasi_kota ?: 'Sidoarjo' }}</span>
                </span>

                {{-- Tombol Kembali ke Dashboard --}}
                <a href="{{ route('beranda') }}" class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full text-xs font-bold text-ink hover:text-brand bg-white hover:bg-surface border border-line transition shadow-2xs">
                    <span class="material-symbols-outlined text-[16px]">arrow_back</span>
                    <span>Dashboard</span>
                </a>
            </div>
        </div>

        {{-- Baris Mini Telemetri Acuan Saat Ini (Clean Bare Icons Without Badge) --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 pt-4 mt-4 border-t border-line/70">
            {{-- Metric 1: Suhu Kandang --}}
            <div class="p-3.5 rounded-xl bg-white border border-line/80 shadow-2xs hover:border-brand/40 transition-all flex flex-col justify-between">
                <div class="flex items-center justify-between">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-muted">Suhu Kandang</span>
                    <span class="material-symbols-outlined text-[20px] text-amber-500">thermostat</span>
                </div>
                <div class="mt-1">
                    <div class="flex items-baseline gap-1">
                        <span class="text-2xl font-black text-ink tracking-tight">{{ $weather['suhu'] }}</span>
                        <span class="text-xs font-bold text-muted">°C</span>
                    </div>
                    <span class="text-[10px] text-muted font-medium mt-0.5 block">Mikroklimat Udara</span>
                </div>
            </div>

            {{-- Metric 2: Kelembaban Relatif --}}
            <div class="p-3.5 rounded-xl bg-white border border-line/80 shadow-2xs hover:border-brand/40 transition-all flex flex-col justify-between">
                <div class="flex items-center justify-between">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-muted">Kelembaban Relatif</span>
                    <span class="material-symbols-outlined text-[20px] text-sky-500">water_drop</span>
                </div>
                <div class="mt-1">
                    <div class="flex items-baseline gap-1">
                        <span class="text-2xl font-black text-ink tracking-tight">{{ $weather['kelembaban'] }}</span>
                        <span class="text-xs font-bold text-muted">% RH</span>
                    </div>
                    <span class="text-[10px] text-muted font-medium mt-0.5 block">Kadar Uap Udara</span>
                </div>
            </div>

            {{-- Metric 3: Indeks THI --}}
            <div class="p-3.5 rounded-xl bg-white border border-line/80 shadow-2xs hover:border-brand/40 transition-all flex flex-col justify-between">
                <div class="flex items-center justify-between">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-muted">Indeks THI</span>
                    <span class="material-symbols-outlined text-[20px] {{ ($weather['thi_level'] ?? '') === 'safe' ? 'text-emerald-500' : (($weather['thi_level'] ?? '') === 'warning' ? 'text-amber-500' : 'text-rose-500') }}">speed</span>
                </div>
                <div class="mt-1">
                    <div class="flex items-baseline gap-1.5 flex-wrap">
                        <span class="text-2xl font-black text-ink tracking-tight">{{ $weather['thi'] }}</span>
                        <span class="text-xs font-bold {{ ($weather['thi_level'] ?? '') === 'safe' ? 'text-emerald-600' : (($weather['thi_level'] ?? '') === 'warning' ? 'text-amber-600' : 'text-rose-600') }}">
                            {{ $weather['thi_kategori_singkat'] ?? 'Aman' }}
                        </span>
                    </div>
                    <span class="text-[10px] text-muted font-medium mt-0.5 block">Biometeorologi Ayam</span>
                </div>
            </div>

            {{-- Metric 4: Kondisi Cuaca --}}
            <div class="p-3.5 rounded-xl bg-white border border-line/80 shadow-2xs hover:border-brand/40 transition-all flex flex-col justify-between">
                <div class="flex items-center justify-between">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-muted">Kondisi Cuaca</span>
                    <span class="material-symbols-outlined text-[20px] text-teal-600">{{ $weather['icon'] ?? 'cloud' }}</span>
                </div>
                <div class="mt-1">
                    <span class="text-base font-black text-ink tracking-tight block truncate">{{ $weather['cuaca_teks'] }}</span>
                    <span class="text-[10px] text-muted font-medium mt-0.5 block truncate">{{ $isSensorOnline ? 'Sensor Fisik Kandang' : 'Stasiun Cuaca BMKG' }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- 2. Digital Twin 3D: Simulasi Fisik Interaktif Kandang Ayam & Kereta Pakan Katrol (WebGL Three.js) --}}
    @include('partials.kandang-3d-viewer')

    {{-- 3. Card Utama: Pusat Kendali Aktuator Interaktif (Mode Otomatis & Manual) --}}
    <div class="bg-white rounded-xl border border-line p-5 md:p-6 shadow-2xs">
        @include('partials.controller-widget')
    </div>

    {{-- 4. Panduan Teknis & Skematik Hardware-Agnostic (Spesifikasi untuk Juri Lomba & Peternak) --}}
    <div class="bg-white rounded-xl border border-line p-5 md:p-6 shadow-2xs space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 pb-3.5 border-b border-line">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-brand/10 text-brand grid place-items-center shrink-0 border border-brand/15">
                    <span class="material-symbols-outlined text-[20px]">developer_board</span>
                </div>
                <div>
                    <h3 class="text-xs font-black uppercase tracking-wider text-ink">Spesifikasi Arsitektur Hardware-Agnostic</h3>
                    <p class="text-[11px] text-muted">Integrasi terbuka untuk modul relay mikro, kontaktor industri, atau PLC kandang</p>
                </div>
            </div>
            <span class="px-2.5 py-1 rounded-full text-[10px] font-extrabold bg-[#f8fbf9] text-muted border border-line uppercase tracking-wider self-start sm:self-auto">
                ESP32 Architecture · 3.3V Logic
            </span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
            {{-- Tabel Pinout ESP32 --}}
            <div class="p-4 sm:p-5 rounded-xl bg-[#f8fbf9] border border-line/80 space-y-3">
                <div class="flex items-center justify-between">
                    <h4 class="font-bold text-ink flex items-center gap-1.5 text-xs">
                        <span class="material-symbols-outlined text-[17px] text-brand">memory</span>
                        <span>Pemetaan Pin GPIO ESP32</span>
                    </h4>
                    <span class="text-[10px] font-bold text-brand bg-brand/10 px-2 py-0.5 rounded-full border border-brand/20">Microcontroller Bus</span>
                </div>

                <div class="divide-y divide-line/60 text-[11px]">
                    <div class="py-2 flex items-center justify-between gap-2">
                        <div class="flex items-center gap-2">
                            <span class="font-mono font-bold text-[11px] px-2 py-0.5 rounded bg-blue-50 text-blue-700 border border-blue-200">GPIO 2</span>
                            <span class="font-bold text-ink">Built-in Status LED</span>
                        </div>
                        <span class="text-muted text-right text-[10px] sm:text-[11px]">Indikator Sistem Siap (Status BIRU)</span>
                    </div>
                    <div class="py-2 flex items-center justify-between gap-2">
                        <div class="flex items-center gap-2">
                            <span class="font-mono font-bold text-[11px] px-2 py-0.5 rounded bg-indigo-50 text-indigo-700 border border-indigo-200">GPIO 26</span>
                            <span class="font-bold text-ink">Relay K1 Kipas Sirkulasi</span>
                        </div>
                        <span class="text-muted text-right text-[10px] sm:text-[11px]">Kipas DC 12V Plafon (Via IN1 / NO1 CW-021)</span>
                    </div>
                    <div class="py-2 flex items-center justify-between gap-2">
                        <div class="flex items-center gap-2">
                            <span class="font-mono font-bold text-[11px] px-2 py-0.5 rounded bg-cyan-50 text-cyan-700 border border-cyan-200">GPIO 14</span>
                            <span class="font-bold text-ink">Relay K2 Pompa Sprinkler</span>
                        </div>
                        <span class="text-muted text-right text-[10px] sm:text-[11px]">Pompa Celup Putih 5V (Via IN2 / NO2 CW-021)</span>
                    </div>
                    <div class="py-2 flex items-center justify-between gap-2">
                        <div class="flex items-center gap-2">
                            <span class="font-mono font-bold text-[11px] px-2 py-0.5 rounded bg-emerald-50 text-emerald-700 border border-emerald-200">GPIO 15</span>
                            <span class="font-bold text-ink">Sensor DHT22</span>
                        </div>
                        <span class="text-muted text-right text-[10px] sm:text-[11px]">Mikroklimat Suhu (°C) & RH (%)</span>
                    </div>
                    <div class="py-2 flex items-center justify-between gap-2">
                        <div class="flex items-center gap-2">
                            <span class="font-mono font-bold text-[11px] px-2 py-0.5 rounded bg-purple-50 text-purple-700 border border-purple-200">GPIO 18, 19</span>
                            <span class="font-bold text-ink">Driver Motor L298N (IN1/IN2)</span>
                        </div>
                        <span class="text-muted text-right text-[10px] sm:text-[11px]">Motor Gearbox Kuning OUT1/OUT2 (Kereta Pakan)</span>
                    </div>
                    <div class="py-2 flex items-center justify-between gap-2">
                        <div class="flex items-center gap-2">
                            <span class="font-mono font-bold text-[11px] px-2 py-0.5 rounded bg-sky-50 text-sky-700 border border-sky-200">GPIO 21, 22</span>
                            <span class="font-bold text-ink">OLED 0.96" SSD1306</span>
                        </div>
                        <span class="text-muted text-right text-[10px] sm:text-[11px]">I2C Bus (SDA D21 / SCL D22)</span>
                    </div>
                </div>
            </div>

            {{-- Protokol Komunikasi Webhook / REST API --}}
            <div class="p-4 sm:p-5 rounded-xl bg-[#f8fbf9] border border-line/80 space-y-3">
                <div class="flex items-center justify-between">
                    <h4 class="font-bold text-ink flex items-center gap-1.5 text-xs">
                        <span class="material-symbols-outlined text-[17px] text-emerald-600">sync_alt</span>
                        <span>REST API & Proteksi Keamanan</span>
                    </h4>
                    <span class="text-[10px] font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-full border border-emerald-200">JSON Protocol</span>
                </div>

                <div class="space-y-2 text-[11px] text-muted leading-relaxed">
                    {{-- Endpoint row --}}
                    <div class="p-2.5 rounded-lg bg-white border border-line flex items-center justify-between gap-2">
                        <div class="flex items-center gap-2 min-w-0">
                            <span class="font-mono font-extrabold text-[10px] px-2 py-0.5 rounded bg-emerald-100 text-emerald-800">POST</span>
                            <code class="font-mono text-ink text-[11px] truncate">/api/kandang/{id}/sensor</code>
                        </div>
                        <span class="text-[10px] text-muted font-bold shrink-0">Telemetri Realtime</span>
                    </div>

                    {{-- Payload info --}}
                    <div class="text-[11px] text-muted">
                        <strong class="text-ink">Payload Real-Time:</strong> ESP32 mem-post suhu & kelembaban, server merespons instruksi:
                        <code class="font-mono text-[10px] text-brand bg-white px-1.5 py-0.5 rounded border border-line block mt-1 overflow-x-auto">{"siap": true, "relay_kipas": true, "relay_misting": false, "relay_lampu": true, "relay_pakan": false}</code>
                    </div>

                    {{-- Safety interlock alert --}}
                    <div class="p-2.5 rounded-lg bg-amber-50/80 border border-amber-200/80 flex items-start gap-2 text-[11px] text-amber-950">
                        <span class="material-symbols-outlined text-[16px] text-amber-700 shrink-0 mt-0.5">shield</span>
                        <div>
                            <strong class="font-bold text-amber-900">Safety Interlock Anti-Wet Litter:</strong>
                            Jika kelembaban udara ≥ 75%, sistem secara otomatis menahan penyalaan misting agar sekam tidak basah dan amonia tidak naik.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
