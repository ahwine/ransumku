@extends('layouts.app')
@section('judul', 'Smart Controller')
@section('isi')
@use('App\Support\Fmt')

<div class="space-y-5">
    {{-- 1. Header Banner Halaman: Smart Controller & Status Gateway IoT --}}
    <div class="bg-white rounded-2xl border border-line p-5 md:p-6 shadow-2xs">
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
            <div class="flex items-start gap-3.5">
                <div class="w-12 h-12 rounded-2xl bg-brand/10 text-brand grid place-items-center shrink-0 shadow-2xs">
                    <span class="material-symbols-outlined text-[28px]">tune</span>
                </div>
                <div>
                    <div class="flex items-center gap-2 flex-wrap">
                        <h1 class="text-xl md:text-2xl font-black text-ink tracking-tight">Pusat Kendali Aktuator Kandang</h1>
                        <span class="px-2.5 py-0.5 rounded-full text-tiny font-extrabold bg-brand/10 text-brand border border-brand/20 uppercase tracking-wider">
                            Smart Closed-Loop Hub
                        </span>
                    </div>
                    <p class="text-xs md:text-sm text-muted mt-1 leading-relaxed max-w-2xl">
                        Pengendali mikroklimat kandang tertutup (<em>closed-loop system</em>) <em>hardware-agnostic</em> berbasis telemetri mikrokontroler IoT (ESP32) dan otomatisasi biometeorologi THI ayam petelur tropis.
                    </p>
                </div>
            </div>

            {{-- Status Badges Konteks Kandang --}}
            <div class="flex items-center gap-2 flex-wrap lg:justify-end">
                {{-- Badge Status Sensor Online --}}
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold border {{ $isSensorOnline ? 'bg-emerald-50 text-emerald-800 border-emerald-200' : 'bg-surface text-muted border-line' }}" title="{{ $isSensorOnline ? 'ESP32 aktif mengirim data telemetri' : 'Sensor fisik offline, sistem menggunakan acuan BMKG' }}">
                    <span class="w-2 h-2 rounded-full {{ $isSensorOnline ? 'bg-emerald-500 animate-pulse' : 'bg-blue-400' }}"></span>
                    <span>{{ $isSensorOnline ? 'ESP32 Online (Live)' : 'Acuan BMKG Live' }}</span>
                </span>

                {{-- Lokasi Kandang --}}
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold bg-surface border border-line text-ink">
                    <span class="material-symbols-outlined text-[16px] text-brand">location_on</span>
                    <span>{{ $farm->lokasi_kota ?: 'Sidoarjo' }}</span>
                </span>

                {{-- Tombol Kembali ke Dashboard --}}
                <a href="{{ route('beranda') }}" class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full text-xs font-bold text-muted hover:text-ink bg-surface hover:bg-surface-2 border border-line transition shadow-2xs">
                    <span class="material-symbols-outlined text-[16px]">arrow_back</span>
                    <span>Dashboard</span>
                </a>
            </div>
        </div>

        {{-- Baris Mini Telemetri Acuan Saat Ini --}}
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 pt-4 mt-4 border-t border-line/70">
            <div class="p-3 rounded-xl bg-surface/50 border border-line/60">
                <span class="text-[11px] font-bold text-muted block">Suhu Kandang</span>
                <div class="flex items-baseline gap-1 mt-0.5">
                    <span class="text-xl font-black text-ink">{{ $weather['suhu'] }}</span>
                    <span class="text-xs font-bold text-muted">°C</span>
                </div>
            </div>
            <div class="p-3 rounded-xl bg-surface/50 border border-line/60">
                <span class="text-[11px] font-bold text-muted block">Kelembaban Relatif</span>
                <div class="flex items-baseline gap-1 mt-0.5">
                    <span class="text-xl font-black text-ink">{{ $weather['kelembaban'] }}</span>
                    <span class="text-xs font-bold text-muted">% RH</span>
                </div>
            </div>
            <div class="p-3 rounded-xl bg-surface/50 border border-line/60">
                <span class="text-[11px] font-bold text-muted block">Indeks THI</span>
                <div class="flex items-baseline gap-1 mt-0.5">
                    <span class="text-xl font-black text-ink">{{ $weather['thi'] }}</span>
                    <span class="text-xs font-bold {{ $weather['badge_class'] ?? 'text-emerald-700' }} ml-1">
                        ({{ $weather['thi_kategori_singkat'] ?? 'Aman' }})
                    </span>
                </div>
            </div>
            <div class="p-3 rounded-xl bg-surface/50 border border-line/60">
                <span class="text-[11px] font-bold text-muted block">Kondisi Cuaca</span>
                <span class="text-xs font-black text-ink mt-1.5 block truncate">{{ $weather['cuaca_teks'] }}</span>
            </div>
        </div>
    </div>

    {{-- 2. Card Utama: Pusat Kendali Aktuator Interaktif (Mode Otomatis & Manual) --}}
    <div class="bg-white rounded-2xl border border-line p-5 md:p-6 shadow-2xs">
        @include('partials.controller-widget')
    </div>

    {{-- 3. Panduan Teknis & Skematik Hardware-Agnostic (Spesifikasi untuk Juri Lomba & Peternak) --}}
    <div class="bg-white rounded-2xl border border-line p-5 md:p-6 shadow-2xs space-y-4">
        <div class="flex items-center gap-2.5 pb-3 border-b border-line">
            <div class="w-8 h-8 rounded-xl bg-teal-50 text-teal-700 grid place-items-center shrink-0">
                <span class="material-symbols-outlined text-[19px]">developer_board</span>
            </div>
            <div>
                <h3 class="text-xs font-black uppercase tracking-wider text-ink">Spesifikasi Arsitektur Hardware-Agnostic</h3>
                <p class="text-[11px] text-muted">Integrasi terbuka untuk modul relay mikro, kontaktor industri, atau PLC kandang</p>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
            {{-- Tabel Pinout ESP32 --}}
            <div class="p-4 rounded-xl bg-surface/40 border border-line space-y-2.5">
                <h4 class="font-bold text-ink flex items-center gap-1.5">
                    <span class="material-symbols-outlined text-[16px] text-brand">memory</span>
                    <span>Pemetaan Pin GPIO ESP32</span>
                </h4>
                <div class="divide-y divide-line/60 text-[11px]">
                    <div class="py-1.5 flex items-center justify-between">
                        <span class="font-mono font-bold text-blue-700">GPIO 2 (Built-in LED)</span>
                        <span class="text-muted">Indikator Sistem Siap (Status BIRU)</span>
                    </div>
                    <div class="py-1.5 flex items-center justify-between">
                        <span class="font-mono font-bold text-ink">GPIO 12</span>
                        <span class="text-muted">Relay Kipas Sirkulasi Blower (Heat Stress)</span>
                    </div>
                    <div class="py-1.5 flex items-center justify-between">
                        <span class="font-mono font-bold text-cyan-700">GPIO 14</span>
                        <span class="text-muted">Relay Pompa Kabut Misting Atap (Severe THI)</span>
                    </div>
                    <div class="py-1.5 flex items-center justify-between">
                        <span class="font-mono font-bold text-amber-700">GPIO 27</span>
                        <span class="text-muted">Relay Lampu Fotoperiode (Jadwal 16 Jam)</span>
                    </div>
                </div>
            </div>

            {{-- Protokol Komunikasi Webhook / REST API --}}
            <div class="p-4 rounded-xl bg-surface/40 border border-line space-y-2.5">
                <h4 class="font-bold text-ink flex items-center gap-1.5">
                    <span class="material-symbols-outlined text-[16px] text-emerald-600">sync_alt</span>
                    <span>Protokol Komunikasi REST API & Proteksi Keamanan</span>
                </h4>
                <ul class="space-y-1.5 text-[11px] text-muted leading-relaxed">
                    <li>
                        <strong class="text-ink">Endpoint Telemetri:</strong>
                        <code class="font-mono text-brand bg-white px-1.5 py-0.5 rounded border border-line">POST /api/kandang/{id}/sensor</code>
                    </li>
                    <li>
                        <strong class="text-ink">Payload Real-Time:</strong>
                        ESP32 mengirimkan suhu & kelembaban, server merespons instruksi aktuator: <code class="font-mono text-ink bg-white px-1 py-0.5 rounded border">{"siap": true, "relay_kipas": true, ...}</code>.
                    </li>
                    <li>
                        <strong class="text-ink">Safety Interlock:</strong>
                        Jika kelembaban udara $\ge 75\%$, sistem secara otomatis menahan penyalaan pompa misting agar sekam tidak basah (*wet litter*) dan mencegah amonia berlebih.
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection

