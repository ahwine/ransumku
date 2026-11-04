{{-- Komponen Pusat Kendali Aktuator Kandang Per-Controller (Smart Independent Actuators) --}}
@php
    $relayKipas = (bool) ($farm->relay_kipas ?? false);
    $relayMisting = (bool) ($farm->relay_misting ?? false);
    $relayLampu = (bool) ($farm->relay_lampu ?? false);
    $relayPakan = (bool) ($farm->relay_pakan ?? false);
    $settings = $farm->getSettingsKontroler();
@endphp

<div class="space-y-5" data-controller-widget 
    data-live-url="{{ route('api.kandang.sensor.live', $farm ?? auth()->user()->farm) }}" 
    data-sakelar-url="{{ route('api.kandang.kontrol.sakelar', $farm ?? auth()->user()->farm) }}"
    data-settings-url="{{ route('api.kandang.kontrol.settings', $farm ?? auth()->user()->farm) }}">

    {{-- Baris 1: Header Bersih Tanpa Ikon Samping (Clean Typography) --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-4 border-b border-line">
        <div>
            <div class="flex items-center gap-2 flex-wrap">
                <h3 class="text-sm font-black uppercase tracking-wider text-ink">Pusat Kendali Aktuator Kandang</h3>
                <span class="text-tiny font-bold text-muted">• Independent Controllers</span>
            </div>
            <p class="text-[11px] text-muted">Setiap aktuator dikendalikan mandiri: tombol sakelar manual & pengaturan kondisi nyala/berhenti otomatis</p>
        </div>
        <div class="flex items-center gap-1.5 self-start sm:self-auto">
            <span class="px-2.5 py-1 rounded-full text-[10px] font-extrabold bg-[#f8fbf9] text-brand border border-brand/20 uppercase tracking-wider inline-flex items-center gap-1.5">
                <span class="w-1.5 h-1.5 rounded-full bg-brand animate-pulse"></span>
                <span>4 Saluran IoT Siap</span>
            </span>
        </div>
    </div>

    {{-- Baris 2: 4 Kartu Aktuator Mandiri (Kipas, Misting, Lampu, Kereta Pakan) --}}
    <form id="form-settings-kontroler" onsubmit="simpanSemuaSetting(event)">
        @csrf
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            
            {{-- ================================================================ --}}
            {{-- CONTROLLER 1: KIPAS SIRKULASI / BLOWER (GPIO 12)                 --}}
            {{-- ================================================================ --}}
            <div class="bg-white rounded-xl border transition-all duration-200 flex flex-col justify-between p-4 sm:p-4.5 space-y-4 shadow-2xs hover:shadow-sm {{ $relayKipas ? 'border-blue-400 ring-1 ring-blue-300/50 shadow-blue-500/10' : 'border-line' }}" id="card-relay-kipas">
                <div class="space-y-3.5">
                    {{-- Header Kartu (Bare Icon Tanpa Badge Box) --}}
                    <div class="flex items-start justify-between gap-2">
                        <div class="flex items-center gap-2.5">
                            <span class="material-symbols-outlined text-[26px] shrink-0 transition-colors {{ $relayKipas ? 'text-blue-600 animate-spin' : 'text-slate-400' }}" id="icon-relay-kipas">mode_fan</span>
                            <div>
                                <h4 class="text-sm font-bold text-ink leading-tight">Kipas Sirkulasi</h4>
                                <div class="flex items-center gap-1 mt-0.5">
                                    <span class="font-mono text-[9px] font-bold px-1.5 py-0.5 rounded bg-surface border border-line/80 text-muted">GPIO 12</span>
                                    <span class="text-[10px] text-muted truncate">Blower Panas</span>
                                </div>
                            </div>
                        </div>
                        <span class="inline-flex items-center gap-1 text-[10px] font-extrabold px-2.5 py-1 rounded-full uppercase tracking-wider {{ $relayKipas ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-100 text-slate-500 border border-slate-200' }}" id="badge-relay-kipas">
                            @if($relayKipas)
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                            @endif
                            <span>{{ $relayKipas ? 'Aktif (ON)' : 'Mati (OFF)' }}</span>
                        </span>
                    </div>

                    {{-- Tombol Kendali Manual (Sleek High-Contrast Action) --}}
                    <div class="space-y-1">
                        <div class="flex items-center justify-between text-[10px] font-bold uppercase tracking-wider text-muted px-0.5">
                            <span>Sakelar Langsung</span>
                            <span class="text-tiny font-normal text-muted lowercase">override manual</span>
                        </div>
                        <button type="button" onclick="ubahSakelar('kipas', {{ $relayKipas ? 'false' : 'true' }})" id="btn-sakelar-kipas" class="w-full h-10 px-3 rounded-lg font-bold text-xs flex items-center justify-center gap-2 transition-all shadow-2xs active:scale-[0.98] cursor-pointer {{ $relayKipas ? 'bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200' : 'bg-brand hover:bg-brand-dark text-white shadow-brand/20' }}">
                            <span class="material-symbols-outlined text-[17px]">{{ $relayKipas ? 'power_settings_new' : 'bolt' }}</span>
                            <span>{{ $relayKipas ? 'Matikan Kipas Sekarang (OFF)' : 'Nyalakan Kipas Sekarang (ON)' }}</span>
                        </button>
                    </div>

                    {{-- Pengaturan Otomatis (Kapan Nyala Otomatis & Kapan Berhenti) --}}
                    <div class="p-3.5 rounded-xl bg-[#f8fbf9] border border-line/80 space-y-3">
                        <div class="flex items-center justify-between">
                            <label class="flex items-center gap-2 cursor-pointer select-none">
                                <input type="checkbox" name="kipas[otomatis]" value="1" {{ !empty($settings['kipas']['otomatis']) ? 'checked' : '' }} class="w-4 h-4 rounded text-brand focus:ring-brand border-line accent-[#116a3a]" id="toggle-auto-kipas">
                                <span class="text-xs font-bold text-ink">Otomatisasi Cerdas</span>
                            </label>
                            <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-brand/10 text-brand border border-brand/20">Sensor & THI</span>
                        </div>

                        <div class="space-y-2.5 pt-0.5 text-xs">
                            <div>
                                <div class="flex items-center justify-between text-[11px] mb-1">
                                    <span class="text-muted font-medium">Nyala Otomatis jika Suhu ≥</span>
                                    <span class="font-bold text-ink font-mono"><span id="val-suhu-kipas">{{ $settings['kipas']['suhu_nyala'] }}</span> °C</span>
                                </div>
                                <div class="relative">
                                    <input type="number" step="0.5" min="25" max="42" name="kipas[suhu_nyala]" value="{{ $settings['kipas']['suhu_nyala'] }}" oninput="document.getElementById('val-suhu-kipas').textContent = this.value" class="w-full h-9 pl-3 pr-8 rounded-lg border border-line bg-white text-xs font-bold text-ink focus:border-brand focus:ring-1 focus:ring-brand shadow-2xs">
                                    <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs font-bold text-muted pointer-events-none">°C</span>
                                </div>
                            </div>

                            <div>
                                <div class="flex items-center justify-between text-[11px] mb-1">
                                    <span class="text-muted font-medium">Atau jika Indeks THI ≥</span>
                                    <span class="font-bold text-ink font-mono"><span id="val-thi-kipas">{{ $settings['kipas']['thi_nyala'] }}</span></span>
                                </div>
                                <input type="number" step="0.5" min="70" max="90" name="kipas[thi_nyala]" value="{{ $settings['kipas']['thi_nyala'] }}" oninput="document.getElementById('val-thi-kipas').textContent = this.value" class="w-full h-9 px-3 rounded-lg border border-line bg-white text-xs font-bold text-ink focus:border-brand focus:ring-1 focus:ring-brand shadow-2xs">
                            </div>

                            <div class="flex items-start gap-1.5 text-[10px] text-muted pt-2 border-t border-line/70 leading-relaxed">
                                <span class="material-symbols-outlined text-[14px] text-brand shrink-0 mt-0.5">info</span>
                                <span><strong>Kapan Berhenti:</strong> Kipas otomatis mati ketika suhu & THI turun kembali di bawah batas aman.</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ================================================================ --}}
            {{-- CONTROLLER 2: POMPA MISTING / KABUT AIR ATAP (GPIO 14)           --}}
            {{-- ================================================================ --}}
            <div class="bg-white rounded-xl border transition-all duration-200 flex flex-col justify-between p-4 sm:p-4.5 space-y-4 shadow-2xs hover:shadow-sm {{ $relayMisting ? 'border-cyan-400 ring-1 ring-cyan-300/50 shadow-cyan-500/10' : 'border-line' }}" id="card-relay-misting">
                <div class="space-y-3.5">
                    {{-- Header Kartu (Bare Icon Tanpa Badge Box) --}}
                    <div class="flex items-start justify-between gap-2">
                        <div class="flex items-center gap-2.5">
                            <span class="material-symbols-outlined text-[26px] shrink-0 transition-colors {{ $relayMisting ? 'text-cyan-600' : 'text-slate-400' }}" id="icon-relay-misting">water_drop</span>
                            <div>
                                <h4 class="text-sm font-bold text-ink leading-tight">Pompa Misting</h4>
                                <div class="flex items-center gap-1 mt-0.5">
                                    <span class="font-mono text-[9px] font-bold px-1.5 py-0.5 rounded bg-surface border border-line/80 text-muted">GPIO 14</span>
                                    <span class="text-[10px] text-muted truncate">Kabut Pendingin</span>
                                </div>
                            </div>
                        </div>
                        <span class="inline-flex items-center gap-1 text-[10px] font-extrabold px-2.5 py-1 rounded-full uppercase tracking-wider {{ $relayMisting ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-100 text-slate-500 border border-slate-200' }}" id="badge-relay-misting">
                            @if($relayMisting)
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                            @endif
                            <span>{{ $relayMisting ? 'Aktif (ON)' : 'Mati (OFF)' }}</span>
                        </span>
                    </div>

                    {{-- Tombol Kendali Manual --}}
                    <div class="space-y-1">
                        <div class="flex items-center justify-between text-[10px] font-bold uppercase tracking-wider text-muted px-0.5">
                            <span>Sakelar Langsung</span>
                            <span class="text-tiny font-normal text-muted lowercase">override manual</span>
                        </div>
                        <button type="button" onclick="ubahSakelar('misting', {{ $relayMisting ? 'false' : 'true' }})" id="btn-sakelar-misting" class="w-full h-10 px-3 rounded-lg font-bold text-xs flex items-center justify-center gap-2 transition-all shadow-2xs active:scale-[0.98] cursor-pointer {{ $relayMisting ? 'bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200' : 'bg-cyan-700 hover:bg-cyan-800 text-white shadow-cyan-700/20' }}">
                            <span class="material-symbols-outlined text-[17px]">{{ $relayMisting ? 'power_settings_new' : 'water_drop' }}</span>
                            <span>{{ $relayMisting ? 'Matikan Misting Sekarang (OFF)' : 'Nyalakan Misting Sekarang (ON)' }}</span>
                        </button>
                    </div>

                    {{-- Pengaturan Otomatis (Kapan Nyala Otomatis & Kapan Berhenti) --}}
                    <div class="p-3.5 rounded-xl bg-[#f8fbf9] border border-line/80 space-y-3">
                        <div class="flex items-center justify-between">
                            <label class="flex items-center gap-2 cursor-pointer select-none">
                                <input type="checkbox" name="misting[otomatis]" value="1" {{ !empty($settings['misting']['otomatis']) ? 'checked' : '' }} class="w-4 h-4 rounded text-brand focus:ring-brand border-line accent-[#0e7490]" id="toggle-auto-misting">
                                <span class="text-xs font-bold text-ink">Otomatisasi Cerdas</span>
                            </label>
                            <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-cyan-50 text-cyan-700 border border-cyan-200">Evaporasi Aman</span>
                        </div>

                        <div class="space-y-2.5 pt-0.5 text-xs">
                            <div>
                                <div class="flex items-center justify-between text-[11px] mb-1">
                                    <span class="text-muted font-medium">Nyala Otomatis jika THI ≥</span>
                                    <span class="font-bold text-ink font-mono"><span id="val-thi-misting">{{ $settings['misting']['thi_nyala'] }}</span></span>
                                </div>
                                <input type="number" step="0.5" min="75" max="92" name="misting[thi_nyala]" value="{{ $settings['misting']['thi_nyala'] }}" oninput="document.getElementById('val-thi-misting').textContent = this.value" class="w-full h-9 px-3 rounded-lg border border-line bg-white text-xs font-bold text-ink focus:border-cyan-600 focus:ring-1 focus:ring-cyan-600 shadow-2xs">
                            </div>

                            <div>
                                <div class="flex items-center justify-between text-[11px] mb-1">
                                    <span class="text-muted font-medium">Berhenti jika Kelembaban (RH) ≥</span>
                                    <span class="font-bold text-ink font-mono"><span id="val-rh-misting">{{ $settings['misting']['rh_maksimal'] }}</span> %</span>
                                </div>
                                <div class="relative">
                                    <input type="number" step="1" min="60" max="85" name="misting[rh_maksimal]" value="{{ $settings['misting']['rh_maksimal'] }}" oninput="document.getElementById('val-rh-misting').textContent = this.value" class="w-full h-9 pl-3 pr-8 rounded-lg border border-line bg-white text-xs font-bold text-ink focus:border-cyan-600 focus:ring-1 focus:ring-cyan-600 shadow-2xs">
                                    <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs font-bold text-muted pointer-events-none">%</span>
                                </div>
                            </div>

                            <div class="flex items-start gap-1.5 text-[10px] text-muted pt-2 border-t border-line/70 leading-relaxed">
                                <span class="material-symbols-outlined text-[14px] text-cyan-700 shrink-0 mt-0.5">shield</span>
                                <span><strong>Proteksi Sekam:</strong> Otomatis berhenti saat kelembaban tinggi agar lantai tetap kering & amonia tidak naik.</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ================================================================ --}}
            {{-- CONTROLLER 3: LAMPU FOTOPERIODE (GPIO 27)                        --}}
            {{-- ================================================================ --}}
            <div class="bg-white rounded-xl border transition-all duration-200 flex flex-col justify-between p-4 sm:p-4.5 space-y-4 shadow-2xs hover:shadow-sm {{ $relayLampu ? 'border-amber-400 ring-1 ring-amber-300/50 shadow-amber-500/10' : 'border-line' }}" id="card-relay-lampu">
                <div class="space-y-3.5">
                    {{-- Header Kartu (Bare Icon Tanpa Badge Box) --}}
                    <div class="flex items-start justify-between gap-2">
                        <div class="flex items-center gap-2.5">
                            <span class="material-symbols-outlined text-[26px] shrink-0 transition-colors {{ $relayLampu ? 'text-amber-500' : 'text-slate-400' }}" id="icon-relay-lampu">lightbulb</span>
                            <div>
                                <h4 class="text-sm font-bold text-ink leading-tight">Lampu Fotoperiode</h4>
                                <div class="flex items-center gap-1 mt-0.5">
                                    <span class="font-mono text-[9px] font-bold px-1.5 py-0.5 rounded bg-surface border border-line/80 text-muted">GPIO 27</span>
                                    <span class="text-[10px] text-muted truncate">Stimulasi Hormon</span>
                                </div>
                            </div>
                        </div>
                        <span class="inline-flex items-center gap-1 text-[10px] font-extrabold px-2.5 py-1 rounded-full uppercase tracking-wider {{ $relayLampu ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-100 text-slate-500 border border-slate-200' }}" id="badge-relay-lampu">
                            @if($relayLampu)
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                            @endif
                            <span>{{ $relayLampu ? 'Aktif (ON)' : 'Mati (OFF)' }}</span>
                        </span>
                    </div>

                    {{-- Tombol Kendali Manual --}}
                    <div class="space-y-1">
                        <div class="flex items-center justify-between text-[10px] font-bold uppercase tracking-wider text-muted px-0.5">
                            <span>Sakelar Langsung</span>
                            <span class="text-tiny font-normal text-muted lowercase">override manual</span>
                        </div>
                        <button type="button" onclick="ubahSakelar('lampu', {{ $relayLampu ? 'false' : 'true' }})" id="btn-sakelar-lampu" class="w-full h-10 px-3 rounded-lg font-bold text-xs flex items-center justify-center gap-2 transition-all shadow-2xs active:scale-[0.98] cursor-pointer {{ $relayLampu ? 'bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200' : 'bg-amber-600 hover:bg-amber-700 text-white shadow-amber-600/20' }}">
                            <span class="material-symbols-outlined text-[17px]">{{ $relayLampu ? 'power_settings_new' : 'lightbulb' }}</span>
                            <span>{{ $relayLampu ? 'Matikan Lampu Sekarang (OFF)' : 'Nyalakan Lampu Sekarang (ON)' }}</span>
                        </button>
                    </div>

                    {{-- Pengaturan Otomatis (Kapan Nyala Otomatis & Kapan Berhenti) --}}
                    <div class="p-3.5 rounded-xl bg-[#f8fbf9] border border-line/80 space-y-3">
                        <div class="flex items-center justify-between">
                            <label class="flex items-center gap-2 cursor-pointer select-none">
                                <input type="checkbox" name="lampu[otomatis]" value="1" {{ !empty($settings['lampu']['otomatis']) ? 'checked' : '' }} class="w-4 h-4 rounded text-brand focus:ring-brand border-line accent-[#d97706]" id="toggle-auto-lampu">
                                <span class="text-xs font-bold text-ink">Otomatisasi Cerdas</span>
                            </label>
                            <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-amber-50 text-amber-700 border border-amber-200">16 Jam Cahaya</span>
                        </div>

                        <div class="space-y-2 pt-0.5 text-xs">
                            <div class="grid grid-cols-2 gap-2">
                                <div>
                                    <label class="text-[10px] text-muted font-medium block">Pagi: Nyala</label>
                                    <input type="time" name="lampu[jam_pagi_mulai]" value="{{ $settings['lampu']['jam_pagi_mulai'] }}" class="mt-0.5 w-full h-9 px-2 rounded-lg border border-line bg-white text-xs font-bold text-ink focus:border-amber-600 shadow-2xs">
                                </div>
                                <div>
                                    <label class="text-[10px] text-muted font-medium block">Pagi: Mati</label>
                                    <input type="time" name="lampu[jam_pagi_selesai]" value="{{ $settings['lampu']['jam_pagi_selesai'] }}" class="mt-0.5 w-full h-9 px-2 rounded-lg border border-line bg-white text-xs font-bold text-ink focus:border-amber-600 shadow-2xs">
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-2">
                                <div>
                                    <label class="text-[10px] text-muted font-medium block">Sore: Nyala</label>
                                    <input type="time" name="lampu[jam_sore_mulai]" value="{{ $settings['lampu']['jam_sore_mulai'] }}" class="mt-0.5 w-full h-9 px-2 rounded-lg border border-line bg-white text-xs font-bold text-ink focus:border-amber-600 shadow-2xs">
                                </div>
                                <div>
                                    <label class="text-[10px] text-muted font-medium block">Sore: Mati</label>
                                    <input type="time" name="lampu[jam_sore_selesai]" value="{{ $settings['lampu']['jam_sore_selesai'] }}" class="mt-0.5 w-full h-9 px-2 rounded-lg border border-line bg-white text-xs font-bold text-ink focus:border-amber-600 shadow-2xs">
                                </div>
                            </div>

                            <div class="flex items-start gap-1.5 text-[10px] text-muted pt-2 border-t border-line/70 leading-relaxed">
                                <span class="material-symbols-outlined text-[14px] text-amber-700 shrink-0 mt-0.5">bedtime</span>
                                <span><strong>Kapan Berhenti:</strong> Lampu mati di luar jam subuh & malam untuk siklus istirahat 8 jam gelap yang tenang.</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ================================================================ --}}
            {{-- CONTROLLER 4: KERETA PAKAN BERJALAN / KATROL REL (L298N)         --}}
            {{-- ================================================================ --}}
            <div class="bg-white rounded-xl border transition-all duration-200 flex flex-col justify-between p-4 sm:p-4.5 space-y-4 shadow-2xs hover:shadow-sm {{ $relayPakan ? 'border-emerald-400 ring-1 ring-emerald-300/50 shadow-emerald-500/10' : 'border-line' }}" id="card-relay-pakan">
                <div class="space-y-3.5">
                    {{-- Header Kartu (Bare Icon Tanpa Badge Box) --}}
                    <div class="flex items-start justify-between gap-2">
                        <div class="flex items-center gap-2.5">
                            <span class="material-symbols-outlined text-[26px] shrink-0 transition-colors {{ $relayPakan ? 'text-brand animate-bounce' : 'text-slate-400' }}" id="icon-relay-pakan">agriculture</span>
                            <div>
                                <h4 class="text-sm font-bold text-ink leading-tight">Kereta Pakan Rel</h4>
                                <div class="flex items-center gap-1 mt-0.5">
                                    <span class="font-mono text-[9px] font-bold px-1.5 py-0.5 rounded bg-surface border border-line/80 text-muted">L298N</span>
                                    <span class="text-[10px] text-muted truncate">Katrol Tali Sling</span>
                                </div>
                            </div>
                        </div>
                        <span class="inline-flex items-center gap-1 text-[10px] font-extrabold px-2.5 py-1 rounded-full uppercase tracking-wider {{ $relayPakan ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-100 text-slate-500 border border-slate-200' }}" id="badge-relay-pakan">
                            @if($relayPakan)
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                            @endif
                            <span>{{ $relayPakan ? 'Meluncur (ON)' : 'Parkir (OFF)' }}</span>
                        </span>
                    </div>

                    {{-- Tombol Kendali Manual (Klik untuk Langsung Nyalakan / Matikan) --}}
                    <div class="space-y-1">
                        <div class="flex items-center justify-between text-[10px] font-bold uppercase tracking-wider text-muted px-0.5">
                            <span>Sakelar Langsung</span>
                            <span class="text-tiny font-normal text-muted lowercase">override manual</span>
                        </div>
                        <button type="button" onclick="ubahSakelar('pakan', {{ $relayPakan ? 'false' : 'true' }})" id="btn-sakelar-pakan" class="w-full h-10 px-3 rounded-lg font-bold text-xs flex items-center justify-center gap-2 transition-all shadow-2xs active:scale-[0.98] cursor-pointer {{ $relayPakan ? 'bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200' : 'bg-brand hover:bg-brand-dark text-white shadow-brand/20' }}">
                            <span class="material-symbols-outlined text-[17px]">{{ $relayPakan ? 'stop_circle' : 'play_arrow' }}</span>
                            <span>{{ $relayPakan ? 'Hentikan Kereta Sekarang (OFF)' : 'Jalankan Pakan Sekarang (ON)' }}</span>
                        </button>
                    </div>

                    {{-- Pengaturan Otomatis (Kapan Nyala Otomatis & Kapan Berhenti) --}}
                    <div class="p-3.5 rounded-xl bg-[#f8fbf9] border border-line/80 space-y-3">
                        <div class="flex items-center justify-between">
                            <label class="flex items-center gap-2 cursor-pointer select-none">
                                <input type="checkbox" name="pakan[otomatis]" value="1" {{ !empty($settings['pakan']['otomatis']) ? 'checked' : '' }} class="w-4 h-4 rounded text-brand focus:ring-brand border-line accent-[#116a3a]" id="toggle-auto-pakan">
                                <span class="text-xs font-bold text-ink">Jadwal Otomatis</span>
                            </label>
                            <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200">2 Sesi / Hari</span>
                        </div>

                        <div class="space-y-2 pt-0.5 text-xs">
                            <div>
                                <label class="text-[10px] text-muted font-medium block">Pilihan Mode Operasi</label>
                                <select name="pakan[mode]" class="mt-0.5 w-full h-9 px-2 rounded-lg border border-line bg-white text-xs font-bold text-ink focus:border-brand shadow-2xs">
                                    <option value="shuttle" {{ ($settings['pakan']['mode'] ?? 'shuttle') === 'shuttle' ? 'selected' : '' }}>Mode Shuttle (Ujung-ke-Ujung)</option>
                                    <option value="bolak_balik" {{ ($settings['pakan']['mode'] ?? '') === 'bolak_balik' ? 'selected' : '' }}>Mode Bolak-Balik (Round-Trip)</option>
                                </select>
                            </div>

                            <div class="grid grid-cols-2 gap-2">
                                <div>
                                    <label class="text-[10px] text-muted font-medium block">Jam Pagi</label>
                                    <input type="time" name="pakan[jam_pagi]" value="{{ $settings['pakan']['jam_pagi'] ?? '07:00' }}" class="mt-0.5 w-full h-9 px-2 rounded-lg border border-line bg-white text-xs font-bold text-ink focus:border-brand shadow-2xs">
                                </div>
                                <div>
                                    <label class="text-[10px] text-muted font-medium block">Jam Sore</label>
                                    <input type="time" name="pakan[jam_sore]" value="{{ $settings['pakan']['jam_sore'] ?? '15:30' }}" class="mt-0.5 w-full h-9 px-2 rounded-lg border border-line bg-white text-xs font-bold text-ink focus:border-brand shadow-2xs">
                                </div>
                            </div>

                            <div>
                                <div class="flex items-center justify-between text-[10px] text-muted font-medium">
                                    <span>Durasi Tempuh Rel</span>
                                    <span class="font-bold text-ink font-mono" id="label-durasi-pakan">{{ $settings['pakan']['durasi_detik'] ?? 4 }} Detik</span>
                                </div>
                                <input type="range" name="pakan[durasi_detik]" min="1" max="15" step="1" value="{{ $settings['pakan']['durasi_detik'] ?? 4 }}" oninput="document.getElementById('label-durasi-pakan').textContent = this.value + ' Detik'" class="w-full mt-1 accent-[#116a3a]">
                            </div>

                            <div class="flex items-start gap-1.5 text-[10px] text-muted pt-2 border-t border-line/70 leading-relaxed">
                                <span class="material-symbols-outlined text-[14px] text-emerald-700 shrink-0 mt-0.5">track_changes</span>
                                <span><strong>Kapan Berhenti:</strong> Otomatis berhenti setelah mencapai ujung rel sesuai durasi atau saat menyentuh limit switch.</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        {{-- Baris Bawah: Tombol Simpan Pengaturan Otomatisasi --}}
        <div class="mt-5 pt-4 border-t border-line flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div class="flex items-center gap-2 text-xs text-muted">
                <span class="material-symbols-outlined text-[16px] text-brand shrink-0">info</span>
                <span>
                    <strong class="text-ink">Catatan:</strong> Tombol sakelar manual di atas dapat ditekan kapan saja untuk kontrol seketika tanpa menunggu timer otomatis.
                </span>
            </div>
            <button type="submit" id="btn-simpan-settings" class="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-lg text-xs font-bold text-white bg-brand hover:bg-brand-dark transition-all shadow-sm shadow-brand/20 active:scale-95 cursor-pointer shrink-0">
                <span class="material-symbols-outlined text-[17px]">save</span>
                <span>Simpan Pengaturan Otomatisasi</span>
            </button>
        </div>
    </form>
</div>

<script>
// Fungsi mengubah sakelar manual per-alat (Override Langsung dengan Responsif Visual)
async function ubahSakelar(alat, statusBaru) {
    const widget = document.querySelector('[data-controller-widget]');
    if (!widget) return;
    const url = widget.getAttribute('data-sakelar-url');
    if (!url) return;

    const btn = document.getElementById(`btn-sakelar-${alat}`);
    let prevInnerHTML = '';
    if (btn) {
        prevInnerHTML = btn.innerHTML;
        btn.disabled = true;
        btn.classList.add('opacity-80', 'pointer-events-none');
        btn.innerHTML = `<span class="material-symbols-outlined text-[16px] animate-spin">progress_activity</span><span>Mengirim Perintah...</span>`;
    }

    try {
        const res = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ alat: alat, status: statusBaru })
        });
        const data = await res.json();
        if (data && data.ok && data.kontrol) {
            updateControllerUI(data.kontrol);
            if (typeof window.showToast === 'function') {
                const labelAlat = {
                    'kipas': 'Kipas Sirkulasi Blower',
                    'misting': 'Pompa Misting Atap',
                    'lampu': 'Lampu Fotoperiode',
                    'pakan': 'Kereta Pakan Rel'
                }[alat] || alat.toUpperCase();
                window.showToast(data.pesan || `${labelAlat} berhasil di-${statusBaru ? 'nyalakan' : 'matikan'}.`, 'success', labelAlat);
            }
        } else {
            throw new Error(data.message || 'Gagal merespons data kendali');
        }
    } catch (err) {
        console.error('Gagal ubah sakelar:', err);
        if (btn && prevInnerHTML) {
            btn.innerHTML = prevInnerHTML;
        }
        if (typeof window.showToast === 'function') {
            window.showToast('Gagal mengirim perintah sakelar. Silakan coba kembali.', 'error', 'Kendala Sakelar');
        }
    } finally {
        if (btn) {
            btn.disabled = false;
            btn.classList.remove('opacity-80', 'pointer-events-none');
        }
    }
}

// Fungsi menyimpan konfigurasi kapan alat nyala otomatis dan kapan berhenti
async function simpanSemuaSetting(e) {
    e.preventDefault();
    const form = e.target;
    const widget = document.querySelector('[data-controller-widget]');
    if (!widget) return;
    const url = widget.getAttribute('data-settings-url');
    if (!url) return;

    const btn = document.getElementById('btn-simpan-settings');
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<span class="material-symbols-outlined animate-spin text-[16px]">progress_activity</span><span>Menyimpan...</span>';
    }

    const formData = new FormData(form);
    const payload = {
        kipas: {
            otomatis: formData.get('kipas[otomatis]') ? 1 : 0,
            suhu_nyala: parseFloat(formData.get('kipas[suhu_nyala]')),
            thi_nyala: parseFloat(formData.get('kipas[thi_nyala]'))
        },
        misting: {
            otomatis: formData.get('misting[otomatis]') ? 1 : 0,
            thi_nyala: parseFloat(formData.get('misting[thi_nyala]')),
            rh_maksimal: parseFloat(formData.get('misting[rh_maksimal]'))
        },
        lampu: {
            otomatis: formData.get('lampu[otomatis]') ? 1 : 0,
            jam_pagi_mulai: formData.get('lampu[jam_pagi_mulai]'),
            jam_pagi_selesai: formData.get('lampu[jam_pagi_selesai]'),
            jam_sore_mulai: formData.get('lampu[jam_sore_mulai]'),
            jam_sore_selesai: formData.get('lampu[jam_sore_selesai]')
        }
    };

    try {
        const res = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify(payload)
        });
        const data = await res.json();
        if (data && data.ok) {
            if (data.kontrol) updateControllerUI(data.kontrol);
            if (typeof window.showToast === 'function') {
                window.showToast(data.pesan || 'Konfigurasi otomatisasi kandang berhasil diperbarui.', 'success', 'Pengaturan Tersimpan');
            }
        } else {
            throw new Error(data.message || 'Gagal menyimpan pengaturan');
        }
    } catch (err) {
        console.error('Gagal simpan setting:', err);
        if (typeof window.showToast === 'function') {
            window.showToast('Gagal menyimpan pengaturan otomatisasi. Periksa koneksi internet Anda.', 'error', 'Gagal Simpan');
        }
    } finally {
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = '<span class="material-symbols-outlined text-[17px]">save</span><span>Simpan Pengaturan Otomatisasi</span>';
        }
    }
}

// Fungsi sinkronisasi visual seluruh UI Kontroler (otomatis dipanggil saat polling background)
function updateControllerUI(kontrol) {
    if (!kontrol) return;

    // Update 4 Kartu Aktuator Detail
    updateAktuatorCardDetail('kipas', Boolean(kontrol.relay_kipas));
    updateAktuatorCardDetail('misting', Boolean(kontrol.relay_misting));
    updateAktuatorCardDetail('lampu', Boolean(kontrol.relay_lampu));
    updateAktuatorCardDetail('pakan', Boolean(kontrol.relay_pakan));

    // Sinkronkan ke Simulasi 3D Digital Twin jika tersedia
    if (typeof window.syncKandang3DState === 'function') {
        window.syncKandang3DState({
            kipas: Boolean(kontrol.relay_kipas),
            misting: Boolean(kontrol.relay_misting),
            lampu: Boolean(kontrol.relay_lampu),
            pakan: Boolean(kontrol.relay_pakan)
        });
    }
}

function updateAktuatorCardDetail(alat, isAktif) {
    const card = document.getElementById(`card-relay-${alat}`);
    const badge = document.getElementById(`badge-relay-${alat}`);
    const icon = document.getElementById(`icon-relay-${alat}`);
    const btn = document.getElementById(`btn-sakelar-${alat}`);

    if (badge) {
        const label = (alat === 'pakan')
            ? (isAktif ? 'Meluncur (ON)' : 'Parkir (OFF)')
            : (isAktif ? 'Aktif (ON)' : 'Mati (OFF)');
        
        badge.innerHTML = isAktif
            ? `<span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span><span>${label}</span>`
            : `<span>${label}</span>`;
            
        badge.className = isAktif
            ? 'inline-flex items-center gap-1 text-[10px] font-extrabold px-2.5 py-1 rounded-full uppercase tracking-wider bg-emerald-50 text-emerald-700 border border-emerald-200'
            : 'inline-flex items-center gap-1 text-[10px] font-extrabold px-2.5 py-1 rounded-full uppercase tracking-wider bg-slate-100 text-slate-500 border border-slate-200';
    }

    if (btn) {
        const textSpan = btn.querySelector('span:last-child');
        const iconSpan = btn.querySelector('.material-symbols-outlined');
        
        const baseBtnClasses = 'w-full h-10 px-3 rounded-lg font-bold text-xs flex items-center justify-center gap-2 transition-all shadow-2xs active:scale-[0.98] cursor-pointer';
        const activeBtnClasses = `${baseBtnClasses} bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200`;

        if (alat === 'kipas') {
            btn.className = isAktif ? activeBtnClasses : `${baseBtnClasses} bg-brand hover:bg-brand-dark text-white shadow-brand/20`;
            if (iconSpan) iconSpan.textContent = isAktif ? 'power_settings_new' : 'bolt';
            if (textSpan) textSpan.textContent = isAktif ? 'Matikan Kipas Sekarang (OFF)' : 'Nyalakan Kipas Sekarang (ON)';
        } else if (alat === 'misting') {
            btn.className = isAktif ? activeBtnClasses : `${baseBtnClasses} bg-cyan-700 hover:bg-cyan-800 text-white shadow-cyan-700/20`;
            if (iconSpan) iconSpan.textContent = isAktif ? 'power_settings_new' : 'water_drop';
            if (textSpan) textSpan.textContent = isAktif ? 'Matikan Misting Sekarang (OFF)' : 'Nyalakan Misting Sekarang (ON)';
        } else if (alat === 'lampu') {
            btn.className = isAktif ? activeBtnClasses : `${baseBtnClasses} bg-amber-600 hover:bg-amber-700 text-white shadow-amber-600/20`;
            if (iconSpan) iconSpan.textContent = isAktif ? 'power_settings_new' : 'lightbulb';
            if (textSpan) textSpan.textContent = isAktif ? 'Matikan Lampu Sekarang (OFF)' : 'Nyalakan Lampu Sekarang (ON)';
        } else if (alat === 'pakan') {
            btn.className = isAktif ? activeBtnClasses : `${baseBtnClasses} bg-brand hover:bg-brand-dark text-white shadow-brand/20`;
            if (iconSpan) iconSpan.textContent = isAktif ? 'stop_circle' : 'play_arrow';
            if (textSpan) textSpan.textContent = isAktif ? 'Hentikan Kereta Sekarang (OFF)' : 'Jalankan Pakan Sekarang (ON)';
        }

        btn.setAttribute('onclick', `ubahSakelar('${alat}', ${isAktif ? 'false' : 'true'})`);
    }

    const baseCardClasses = 'bg-white rounded-xl border transition-all duration-200 flex flex-col justify-between p-4 sm:p-4.5 space-y-4 shadow-2xs hover:shadow-sm';

    if (alat === 'kipas') {
        if (card) card.className = isAktif ? `${baseCardClasses} border-blue-400 ring-1 ring-blue-300/50 shadow-blue-500/10` : `${baseCardClasses} border-line`;
        if (icon) icon.className = isAktif ? 'material-symbols-outlined text-[26px] shrink-0 transition-colors text-blue-600 animate-spin' : 'material-symbols-outlined text-[26px] shrink-0 transition-colors text-slate-400';
    } else if (alat === 'misting') {
        if (card) card.className = isAktif ? `${baseCardClasses} border-cyan-400 ring-1 ring-cyan-300/50 shadow-cyan-500/10` : `${baseCardClasses} border-line`;
        if (icon) icon.className = isAktif ? 'material-symbols-outlined text-[26px] shrink-0 transition-colors text-cyan-600' : 'material-symbols-outlined text-[26px] shrink-0 transition-colors text-slate-400';
    } else if (alat === 'lampu') {
        if (card) card.className = isAktif ? `${baseCardClasses} border-amber-400 ring-1 ring-amber-300/50 shadow-amber-500/10` : `${baseCardClasses} border-line`;
        if (icon) icon.className = isAktif ? 'material-symbols-outlined text-[26px] shrink-0 transition-colors text-amber-500' : 'material-symbols-outlined text-[26px] shrink-0 transition-colors text-slate-400';
    } else if (alat === 'pakan') {
        if (card) card.className = isAktif ? `${baseCardClasses} border-emerald-400 ring-1 ring-emerald-300/50 shadow-emerald-500/10` : `${baseCardClasses} border-line`;
        if (icon) icon.className = isAktif ? 'material-symbols-outlined text-[26px] shrink-0 transition-colors text-brand animate-bounce' : 'material-symbols-outlined text-[26px] shrink-0 transition-colors text-slate-400';
    }
}

window.updateControllerUI = updateControllerUI;
</script>
