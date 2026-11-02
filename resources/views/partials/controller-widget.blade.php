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

    {{-- Baris 1: Header Bersih Tanpa Sakelar Global (Fokus Mandiri Per-Controller) --}}
    <div class="flex items-center justify-between gap-3.5 pb-4 border-b border-line">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-brand/10 text-brand grid place-items-center shrink-0 shadow-2xs">
                <span class="material-symbols-outlined text-[22px]">tune</span>
            </div>
            <div>
                <div class="flex items-center gap-2">
                    <h3 class="text-sm font-black uppercase tracking-wider text-ink">Pusat Kendali Aktuator Kandang</h3>
                    <span class="text-tiny font-bold text-muted">• Independent Controllers</span>
                </div>
                <p class="text-[11px] text-muted">Setiap aktuator dikendalikan mandiri: tombol sakelar manual & pengaturan kondisi nyala/berhenti otomatis</p>
            </div>
        </div>
    </div>

    {{-- Baris 2: 4 Kartu Aktuator Mandiri (Kipas, Misting, Lampu, Kereta Pakan) --}}
    <form id="form-settings-kontroler" onsubmit="simpanSemuaSetting(event)">
        @csrf
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            
            {{-- ================================================================ --}}
            {{-- CONTROLLER 1: KIPAS SIRKULASI / BLOWER (GPIO 12)                 --}}
            {{-- ================================================================ --}}
            <div class="p-4 md:p-5 rounded-2xl border transition-all duration-200 flex flex-col justify-between space-y-4 {{ $relayKipas ? 'bg-white border-blue-300 shadow-sm shadow-blue-500/10 ring-1 ring-blue-300/60' : 'bg-surface/30 border-line' }}" id="card-relay-kipas">
                <div class="space-y-3.5">
                    {{-- Header Kartu --}}
                    <div class="flex items-start justify-between gap-2">
                        <div class="flex items-center gap-2.5">
                            <div class="w-10 h-10 rounded-xl flex items-center justify-center transition-colors {{ $relayKipas ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-400' }}" id="icon-bg-kipas">
                                <span class="material-symbols-outlined text-[22px] {{ $relayKipas ? 'animate-spin' : '' }}" id="icon-relay-kipas">mode_fan</span>
                            </div>
                            <div>
                                <h4 class="text-sm font-bold text-ink">Kipas Sirkulasi</h4>
                                <span class="text-[10px] text-muted font-medium">Blower Hawa Panas · GPIO 12</span>
                            </div>
                        </div>
                        <span class="text-[10px] font-extrabold px-2.5 py-1 rounded-full uppercase tracking-wider {{ $relayKipas ? 'bg-emerald-100 text-emerald-800' : 'bg-gray-100 text-gray-500' }}" id="badge-relay-kipas">
                            {{ $relayKipas ? 'Aktif (ON)' : 'Mati (OFF)' }}
                        </span>
                    </div>

                    {{-- Tombol Kendali Manual (Klik untuk Langsung Nyalakan / Matikan) --}}
                    <div class="p-3 rounded-xl bg-white border border-line/80 space-y-2 shadow-2xs">
                        <div class="flex items-center justify-between">
                            <span class="text-tiny font-bold text-ink uppercase tracking-wider">Kendali Manual:</span>
                            <span class="text-[10px] text-muted">Sakelar Langsung</span>
                        </div>
                        <button type="button" onclick="ubahSakelar('kipas', {{ $relayKipas ? 'false' : 'true' }})" id="btn-sakelar-kipas" class="w-full py-2 px-3 rounded-xl font-bold text-xs flex items-center justify-center gap-2 transition-all shadow-2xs active:scale-95 cursor-pointer {{ $relayKipas ? 'bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200' : 'bg-brand hover:bg-brand-dark text-white shadow-brand/20' }}">
                            <span class="material-symbols-outlined text-[16px]">{{ $relayKipas ? 'power_settings_new' : 'bolt' }}</span>
                            <span>{{ $relayKipas ? 'Matikan Kipas Sekarang (OFF)' : 'Nyalakan Kipas Sekarang (ON)' }}</span>
                        </button>
                    </div>

                    {{-- Pengaturan Otomatis (Kapan Nyala Otomatis & Kapan Berhenti) --}}
                    <div class="p-3.5 rounded-xl bg-surface/50 border border-line/70 space-y-2.5">
                        <div class="flex items-center justify-between">
                            <label class="flex items-center gap-2 cursor-pointer select-none">
                                <input type="checkbox" name="kipas[otomatis]" value="1" {{ !empty($settings['kipas']['otomatis']) ? 'checked' : '' }} class="w-4 h-4 rounded text-brand focus:ring-brand border-line" id="toggle-auto-kipas">
                                <span class="text-xs font-bold text-ink">Otomatisasi Cerdas</span>
                            </label>
                            <span class="text-[10px] text-brand font-semibold">Sensor & THI</span>
                        </div>

                        <div class="space-y-2 pt-1 text-xs">
                            <div>
                                <label class="text-[11px] text-muted font-medium flex items-center justify-between">
                                    <span>Nyala Otomatis jika Suhu ≥</span>
                                    <span class="font-bold text-ink font-mono"><span id="val-suhu-kipas">{{ $settings['kipas']['suhu_nyala'] }}</span> °C</span>
                                </label>
                                <input type="number" step="0.5" min="25" max="42" name="kipas[suhu_nyala]" value="{{ $settings['kipas']['suhu_nyala'] }}" oninput="document.getElementById('val-suhu-kipas').textContent = this.value" class="mt-1 w-full px-2.5 py-1.5 rounded-lg border border-line bg-white text-xs font-bold text-ink focus:border-brand focus:ring-1 focus:ring-brand">
                            </div>

                            <div>
                                <label class="text-[11px] text-muted font-medium flex items-center justify-between">
                                    <span>Atau jika Indeks THI ≥</span>
                                    <span class="font-bold text-ink font-mono"><span id="val-thi-kipas">{{ $settings['kipas']['thi_nyala'] }}</span></span>
                                </label>
                                <input type="number" step="0.5" min="70" max="90" name="kipas[thi_nyala]" value="{{ $settings['kipas']['thi_nyala'] }}" oninput="document.getElementById('val-thi-kipas').textContent = this.value" class="mt-1 w-full px-2.5 py-1.5 rounded-lg border border-line bg-white text-xs font-bold text-ink focus:border-brand focus:ring-1 focus:ring-brand">
                            </div>

                            <p class="text-[10px] text-muted pt-1 leading-relaxed border-t border-line/60">
                                <strong>Kapan Berhenti:</strong> Kipas otomatis berhenti ketika suhu & THI turun kembali di bawah batas aman.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ================================================================ --}}
            {{-- CONTROLLER 2: POMPA MISTING / KABUT AIR ATAP (GPIO 14)           --}}
            {{-- ================================================================ --}}
            <div class="p-4 md:p-5 rounded-2xl border transition-all duration-200 flex flex-col justify-between space-y-4 {{ $relayMisting ? 'bg-white border-cyan-300 shadow-sm shadow-cyan-500/10 ring-1 ring-cyan-300/60' : 'bg-surface/30 border-line' }}" id="card-relay-misting">
                <div class="space-y-3.5">
                    {{-- Header Kartu --}}
                    <div class="flex items-start justify-between gap-2">
                        <div class="flex items-center gap-2.5">
                            <div class="w-10 h-10 rounded-xl flex items-center justify-center transition-colors {{ $relayMisting ? 'bg-cyan-100 text-cyan-700' : 'bg-gray-100 text-gray-400' }}" id="icon-bg-misting">
                                <span class="material-symbols-outlined text-[22px]" id="icon-relay-misting">water_drop</span>
                            </div>
                            <div>
                                <h4 class="text-sm font-bold text-ink">Pompa Misting</h4>
                                <span class="text-[10px] text-muted font-medium">Kabut Pendingin Atap · GPIO 14</span>
                            </div>
                        </div>
                        <span class="text-[10px] font-extrabold px-2.5 py-1 rounded-full uppercase tracking-wider {{ $relayMisting ? 'bg-emerald-100 text-emerald-800' : 'bg-gray-100 text-gray-500' }}" id="badge-relay-misting">
                            {{ $relayMisting ? 'Aktif (ON)' : 'Mati (OFF)' }}
                        </span>
                    </div>

                    {{-- Tombol Kendali Manual --}}
                    <div class="p-3 rounded-xl bg-white border border-line/80 space-y-2 shadow-2xs">
                        <div class="flex items-center justify-between">
                            <span class="text-tiny font-bold text-ink uppercase tracking-wider">Kendali Manual:</span>
                            <span class="text-[10px] text-muted">Sakelar Langsung</span>
                        </div>
                        <button type="button" onclick="ubahSakelar('misting', {{ $relayMisting ? 'false' : 'true' }})" id="btn-sakelar-misting" class="w-full py-2 px-3 rounded-xl font-bold text-xs flex items-center justify-center gap-2 transition-all shadow-2xs active:scale-95 cursor-pointer {{ $relayMisting ? 'bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200' : 'bg-cyan-700 hover:bg-cyan-800 text-white shadow-cyan-700/20' }}">
                            <span class="material-symbols-outlined text-[16px]">{{ $relayMisting ? 'power_settings_new' : 'water_drop' }}</span>
                            <span>{{ $relayMisting ? 'Matikan Misting Sekarang (OFF)' : 'Nyalakan Misting Sekarang (ON)' }}</span>
                        </button>
                    </div>

                    {{-- Pengaturan Otomatis (Kapan Nyala Otomatis & Kapan Berhenti) --}}
                    <div class="p-3.5 rounded-xl bg-surface/50 border border-line/70 space-y-2.5">
                        <div class="flex items-center justify-between">
                            <label class="flex items-center gap-2 cursor-pointer select-none">
                                <input type="checkbox" name="misting[otomatis]" value="1" {{ !empty($settings['misting']['otomatis']) ? 'checked' : '' }} class="w-4 h-4 rounded text-brand focus:ring-brand border-line" id="toggle-auto-misting">
                                <span class="text-xs font-bold text-ink">Otomatisasi Cerdas</span>
                            </label>
                            <span class="text-[10px] text-cyan-700 font-semibold">Evaporasi Aman</span>
                        </div>

                        <div class="space-y-2 pt-1 text-xs">
                            <div>
                                <label class="text-[11px] text-muted font-medium flex items-center justify-between">
                                    <span>Nyala Otomatis jika THI ≥</span>
                                    <span class="font-bold text-ink font-mono"><span id="val-thi-misting">{{ $settings['misting']['thi_nyala'] }}</span></span>
                                </label>
                                <input type="number" step="0.5" min="75" max="92" name="misting[thi_nyala]" value="{{ $settings['misting']['thi_nyala'] }}" oninput="document.getElementById('val-thi-misting').textContent = this.value" class="mt-1 w-full px-2.5 py-1.5 rounded-lg border border-line bg-white text-xs font-bold text-ink focus:border-brand focus:ring-1 focus:ring-brand">
                            </div>

                            <div>
                                <label class="text-[11px] text-muted font-medium flex items-center justify-between">
                                    <span>Berhenti jika Kelembaban (RH) ≥</span>
                                    <span class="font-bold text-ink font-mono"><span id="val-rh-misting">{{ $settings['misting']['rh_maksimal'] }}</span> %</span>
                                </label>
                                <input type="number" step="1" min="60" max="85" name="misting[rh_maksimal]" value="{{ $settings['misting']['rh_maksimal'] }}" oninput="document.getElementById('val-rh-misting').textContent = this.value" class="mt-1 w-full px-2.5 py-1.5 rounded-lg border border-line bg-white text-xs font-bold text-ink focus:border-brand focus:ring-1 focus:ring-brand">
                            </div>

                            <p class="text-[10px] text-muted pt-1 leading-relaxed border-t border-line/60">
                                <strong>Proteksi Sekam Becek:</strong> Pompa misting otomatis berhenti saat kelembaban tinggi agar sekam tetap kering dan amonia tidak naik.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ================================================================ --}}
            {{-- CONTROLLER 3: LAMPU FOTOPERIODE (GPIO 27)                        --}}
            {{-- ================================================================ --}}
            <div class="p-4 md:p-5 rounded-2xl border transition-all duration-200 flex flex-col justify-between space-y-4 {{ $relayLampu ? 'bg-white border-amber-300 shadow-sm shadow-amber-500/10 ring-1 ring-amber-300/60' : 'bg-surface/30 border-line' }}" id="card-relay-lampu">
                <div class="space-y-3.5">
                    {{-- Header Kartu --}}
                    <div class="flex items-start justify-between gap-2">
                        <div class="flex items-center gap-2.5">
                            <div class="w-10 h-10 rounded-xl flex items-center justify-center transition-colors {{ $relayLampu ? 'bg-amber-100 text-amber-700' : 'bg-gray-100 text-gray-400' }}" id="icon-bg-lampu">
                                <span class="material-symbols-outlined text-[22px]" id="icon-relay-lampu">lightbulb</span>
                            </div>
                            <div>
                                <h4 class="text-sm font-bold text-ink">Lampu Fotoperiode</h4>
                                <span class="text-[10px] text-muted font-medium">Stimulasi Hormon Telur · GPIO 27</span>
                            </div>
                        </div>
                        <span class="text-[10px] font-extrabold px-2.5 py-1 rounded-full uppercase tracking-wider {{ $relayLampu ? 'bg-emerald-100 text-emerald-800' : 'bg-gray-100 text-gray-500' }}" id="badge-relay-lampu">
                            {{ $relayLampu ? 'Aktif (ON)' : 'Mati (OFF)' }}
                        </span>
                    </div>

                    {{-- Tombol Kendali Manual --}}
                    <div class="p-3 rounded-xl bg-white border border-line/80 space-y-2 shadow-2xs">
                        <div class="flex items-center justify-between">
                            <span class="text-tiny font-bold text-ink uppercase tracking-wider">Kendali Manual:</span>
                            <span class="text-[10px] text-muted">Sakelar Langsung</span>
                        </div>
                        <button type="button" onclick="ubahSakelar('lampu', {{ $relayLampu ? 'false' : 'true' }})" id="btn-sakelar-lampu" class="w-full py-2 px-3 rounded-xl font-bold text-xs flex items-center justify-center gap-2 transition-all shadow-2xs active:scale-95 cursor-pointer {{ $relayLampu ? 'bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200' : 'bg-amber-600 hover:bg-amber-700 text-white shadow-amber-600/20' }}">
                            <span class="material-symbols-outlined text-[16px]">{{ $relayLampu ? 'power_settings_new' : 'lightbulb' }}</span>
                            <span>{{ $relayLampu ? 'Matikan Lampu Sekarang (OFF)' : 'Nyalakan Lampu Sekarang (ON)' }}</span>
                        </button>
                    </div>

                    {{-- Pengaturan Otomatis (Kapan Nyala Otomatis & Kapan Berhenti) --}}
                    <div class="p-3.5 rounded-xl bg-surface/50 border border-line/70 space-y-2.5">
                        <div class="flex items-center justify-between">
                            <label class="flex items-center gap-2 cursor-pointer select-none">
                                <input type="checkbox" name="lampu[otomatis]" value="1" {{ !empty($settings['lampu']['otomatis']) ? 'checked' : '' }} class="w-4 h-4 rounded text-brand focus:ring-brand border-line" id="toggle-auto-lampu">
                                <span class="text-xs font-bold text-ink">Otomatisasi Cerdas</span>
                            </label>
                            <span class="text-[10px] text-amber-700 font-semibold">16 Jam Cahaya</span>
                        </div>

                        <div class="space-y-2 pt-1 text-xs">
                            <div class="grid grid-cols-2 gap-2">
                                <div>
                                    <label class="text-[10px] text-muted font-medium block">Pagi: Nyala Jam</label>
                                    <input type="time" name="lampu[jam_pagi_mulai]" value="{{ $settings['lampu']['jam_pagi_mulai'] }}" class="mt-0.5 w-full px-2 py-1.5 rounded-lg border border-line bg-white text-xs font-bold text-ink focus:border-brand">
                                </div>
                                <div>
                                    <label class="text-[10px] text-muted font-medium block">Pagi: Mati Jam</label>
                                    <input type="time" name="lampu[jam_pagi_selesai]" value="{{ $settings['lampu']['jam_pagi_selesai'] }}" class="mt-0.5 w-full px-2 py-1.5 rounded-lg border border-line bg-white text-xs font-bold text-ink focus:border-brand">
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-2">
                                <div>
                                    <label class="text-[10px] text-muted font-medium block">Sore: Nyala Jam</label>
                                    <input type="time" name="lampu[jam_sore_mulai]" value="{{ $settings['lampu']['jam_sore_mulai'] }}" class="mt-0.5 w-full px-2 py-1.5 rounded-lg border border-line bg-white text-xs font-bold text-ink focus:border-brand">
                                </div>
                                <div>
                                    <label class="text-[10px] text-muted font-medium block">Sore: Mati Jam</label>
                                    <input type="time" name="lampu[jam_sore_selesai]" value="{{ $settings['lampu']['jam_sore_selesai'] }}" class="mt-0.5 w-full px-2 py-1.5 rounded-lg border border-line bg-white text-xs font-bold text-ink focus:border-brand">
                                </div>
                            </div>

                            <p class="text-[10px] text-muted pt-1 leading-relaxed border-t border-line/60">
                                <strong>Kapan Berhenti:</strong> Lampu mati di luar jam subuh & malam untuk siklus istirahat 8 jam gelap yang tenang.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ================================================================ --}}
            {{-- CONTROLLER 4: KERETA PAKAN BERJALAN / KATROL REL (L298N)         --}}
            {{-- ================================================================ --}}
            <div class="p-4 md:p-5 rounded-2xl border transition-all duration-200 flex flex-col justify-between space-y-4 {{ $relayPakan ? 'bg-white border-emerald-300 shadow-sm shadow-emerald-500/10 ring-1 ring-emerald-300/60' : 'bg-surface/30 border-line' }}" id="card-relay-pakan">
                <div class="space-y-3.5">
                    {{-- Header Kartu --}}
                    <div class="flex items-start justify-between gap-2">
                        <div class="flex items-center gap-2.5">
                            <div class="w-10 h-10 rounded-xl flex items-center justify-center transition-colors {{ $relayPakan ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-400' }}" id="icon-bg-pakan">
                                <span class="material-symbols-outlined text-[22px] {{ $relayPakan ? 'animate-bounce' : '' }}" id="icon-relay-pakan">agriculture</span>
                            </div>
                            <div>
                                <h4 class="text-sm font-bold text-ink">Kereta Pakan Rel</h4>
                                <span class="text-[10px] text-muted font-medium">Katrol Tali Sling · L298N</span>
                            </div>
                        </div>
                        <span class="text-[10px] font-extrabold px-2.5 py-1 rounded-full uppercase tracking-wider {{ $relayPakan ? 'bg-emerald-100 text-emerald-800' : 'bg-gray-100 text-gray-500' }}" id="badge-relay-pakan">
                            {{ $relayPakan ? 'Meluncur (ON)' : 'Parkir (OFF)' }}
                        </span>
                    </div>

                    {{-- Tombol Kendali Manual (Klik untuk Langsung Nyalakan / Matikan) --}}
                    <div class="p-3 rounded-xl bg-white border border-line/80 space-y-2 shadow-2xs">
                        <div class="flex items-center justify-between">
                            <span class="text-tiny font-bold text-ink uppercase tracking-wider">Kendali Manual:</span>
                            <span class="text-[10px] text-muted">Jarak Jauh</span>
                        </div>
                        <button type="button" onclick="ubahSakelar('pakan', {{ $relayPakan ? 'false' : 'true' }})" id="btn-sakelar-pakan" class="w-full py-2 px-3 rounded-xl font-bold text-xs flex items-center justify-center gap-2 transition-all shadow-2xs active:scale-95 cursor-pointer {{ $relayPakan ? 'bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200' : 'bg-emerald-700 hover:bg-emerald-800 text-white shadow-emerald-700/20' }}">
                            <span class="material-symbols-outlined text-[16px]">{{ $relayPakan ? 'stop_circle' : 'play_arrow' }}</span>
                            <span>{{ $relayPakan ? 'Hentikan Kereta Sekarang (OFF)' : 'Jalankan Pakan Sekarang (ON)' }}</span>
                        </button>
                    </div>

                    {{-- Pengaturan Otomatis (Kapan Nyala Otomatis & Kapan Berhenti) --}}
                    <div class="p-3.5 rounded-xl bg-surface/50 border border-line/70 space-y-2.5">
                        <div class="flex items-center justify-between">
                            <label class="flex items-center gap-2 cursor-pointer select-none">
                                <input type="checkbox" name="pakan[otomatis]" value="1" {{ !empty($settings['pakan']['otomatis']) ? 'checked' : '' }} class="w-4 h-4 rounded text-brand focus:ring-brand border-line" id="toggle-auto-pakan">
                                <span class="text-xs font-bold text-ink">Jadwal Otomatis</span>
                            </label>
                            <span class="text-[10px] text-emerald-700 font-semibold">2 Sesi / Hari</span>
                        </div>

                        <div class="space-y-2 pt-1 text-xs">
                            <div>
                                <label class="text-[10px] text-muted font-medium block">Pilihan Mode Operasi:</label>
                                <select name="pakan[mode]" class="mt-0.5 w-full px-2 py-1.5 rounded-lg border border-line bg-white text-xs font-bold text-ink focus:border-brand">
                                    <option value="shuttle" {{ ($settings['pakan']['mode'] ?? 'shuttle') === 'shuttle' ? 'selected' : '' }}>Mode Shuttle (Ujung-ke-Ujung)</option>
                                    <option value="bolak_balik" {{ ($settings['pakan']['mode'] ?? '') === 'bolak_balik' ? 'selected' : '' }}>Mode Bolak-Balik (Round-Trip)</option>
                                </select>
                            </div>

                            <div class="grid grid-cols-2 gap-2">
                                <div>
                                    <label class="text-[10px] text-muted font-medium block">Jam Sesi Pagi</label>
                                    <input type="time" name="pakan[jam_pagi]" value="{{ $settings['pakan']['jam_pagi'] ?? '07:00' }}" class="mt-0.5 w-full px-2 py-1.5 rounded-lg border border-line bg-white text-xs font-bold text-ink focus:border-brand">
                                </div>
                                <div>
                                    <label class="text-[10px] text-muted font-medium block">Jam Sesi Sore</label>
                                    <input type="time" name="pakan[jam_sore]" value="{{ $settings['pakan']['jam_sore'] ?? '15:30' }}" class="mt-0.5 w-full px-2 py-1.5 rounded-lg border border-line bg-white text-xs font-bold text-ink focus:border-brand">
                                </div>
                            </div>

                            <div>
                                <label class="text-[10px] text-muted font-medium flex items-center justify-between">
                                    <span>Durasi Tempuh Rel:</span>
                                    <span class="font-bold text-ink" id="label-durasi-pakan">{{ $settings['pakan']['durasi_detik'] ?? 4 }} Detik</span>
                                </label>
                                <input type="range" name="pakan[durasi_detik]" min="1" max="15" step="1" value="{{ $settings['pakan']['durasi_detik'] ?? 4 }}" oninput="document.getElementById('label-durasi-pakan').textContent = this.value + ' Detik'" class="w-full mt-1 accent-emerald-600">
                            </div>

                            <p class="text-[10px] text-muted pt-1 leading-relaxed border-t border-line/60">
                                <strong>Kapan Berhenti:</strong> Otomatis berhenti setelah mencapai ujung rel sesuai durasi atau saat menyentuh limit switch.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        {{-- Baris Bawah: Tombol Simpan Pengaturan Otomatisasi --}}
        <div class="mt-4 pt-4 border-t border-line flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <span class="text-xs text-muted">
                <span class="font-bold text-ink">Catatan:</span> Tombol manual di atas dapat ditekan kapan saja untuk menyalakan/mematikan alat seketika tanpa menunggu jadwal otomatis.
            </span>
            <button type="submit" id="btn-simpan-settings" class="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl text-xs font-bold text-white bg-brand hover:bg-brand-dark transition-all shadow-sm shadow-brand/20 active:scale-95 cursor-pointer shrink-0">
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
    const iconBg = document.getElementById(`icon-bg-${alat}`);
    const icon = document.getElementById(`icon-relay-${alat}`);
    const btn = document.getElementById(`btn-sakelar-${alat}`);

    if (badge) {
        if (alat === 'pakan') {
            badge.textContent = isAktif ? 'Meluncur (ON)' : 'Parkir (OFF)';
        } else {
            badge.textContent = isAktif ? 'Aktif (ON)' : 'Mati (OFF)';
        }
        badge.className = isAktif
            ? 'text-[10px] font-extrabold px-2.5 py-1 rounded-full uppercase tracking-wider bg-emerald-100 text-emerald-800'
            : 'text-[10px] font-extrabold px-2.5 py-1 rounded-full uppercase tracking-wider bg-gray-100 text-gray-500';
    }

    if (btn) {
        const textSpan = btn.querySelector('span:last-child');
        const iconSpan = btn.querySelector('.material-symbols-outlined');
        
        if (alat === 'kipas') {
            btn.className = isAktif 
                ? 'w-full py-2 px-3 rounded-xl font-bold text-xs flex items-center justify-center gap-2 transition-all shadow-2xs active:scale-95 cursor-pointer bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200'
                : 'w-full py-2 px-3 rounded-xl font-bold text-xs flex items-center justify-center gap-2 transition-all shadow-2xs active:scale-95 cursor-pointer bg-brand hover:bg-brand-dark text-white shadow-brand/20';
            if (iconSpan) iconSpan.textContent = isAktif ? 'power_settings_new' : 'bolt';
            if (textSpan) textSpan.textContent = isAktif ? 'Matikan Kipas Sekarang (OFF)' : 'Nyalakan Kipas Sekarang (ON)';
        } else if (alat === 'misting') {
            btn.className = isAktif
                ? 'w-full py-2 px-3 rounded-xl font-bold text-xs flex items-center justify-center gap-2 transition-all shadow-2xs active:scale-95 cursor-pointer bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200'
                : 'w-full py-2 px-3 rounded-xl font-bold text-xs flex items-center justify-center gap-2 transition-all shadow-2xs active:scale-95 cursor-pointer bg-cyan-700 hover:bg-cyan-800 text-white shadow-cyan-700/20';
            if (iconSpan) iconSpan.textContent = isAktif ? 'power_settings_new' : 'water_drop';
            if (textSpan) textSpan.textContent = isAktif ? 'Matikan Misting Sekarang (OFF)' : 'Nyalakan Misting Sekarang (ON)';
        } else if (alat === 'lampu') {
            btn.className = isAktif
                ? 'w-full py-2 px-3 rounded-xl font-bold text-xs flex items-center justify-center gap-2 transition-all shadow-2xs active:scale-95 cursor-pointer bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200'
                : 'w-full py-2 px-3 rounded-xl font-bold text-xs flex items-center justify-center gap-2 transition-all shadow-2xs active:scale-95 cursor-pointer bg-amber-600 hover:bg-amber-700 text-white shadow-amber-600/20';
            if (iconSpan) iconSpan.textContent = isAktif ? 'power_settings_new' : 'lightbulb';
            if (textSpan) textSpan.textContent = isAktif ? 'Matikan Lampu Sekarang (OFF)' : 'Nyalakan Lampu Sekarang (ON)';
        } else if (alat === 'pakan') {
            btn.className = isAktif
                ? 'w-full py-2 px-3 rounded-xl font-bold text-xs flex items-center justify-center gap-2 transition-all shadow-2xs active:scale-95 cursor-pointer bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200'
                : 'w-full py-2 px-3 rounded-xl font-bold text-xs flex items-center justify-center gap-2 transition-all shadow-2xs active:scale-95 cursor-pointer bg-emerald-700 hover:bg-emerald-800 text-white shadow-emerald-700/20';
            if (iconSpan) iconSpan.textContent = isAktif ? 'stop_circle' : 'play_arrow';
            if (textSpan) textSpan.textContent = isAktif ? 'Hentikan Kereta Sekarang (OFF)' : 'Jalankan Pakan Sekarang (ON)';
        }

        btn.setAttribute('onclick', `ubahSakelar('${alat}', ${isAktif ? 'false' : 'true'})`);
    }

    if (alat === 'kipas') {
        if (card) card.className = isAktif ? 'p-4 md:p-5 rounded-2xl border transition-all duration-200 flex flex-col justify-between space-y-4 bg-white border-blue-300 shadow-sm shadow-blue-500/10 ring-1 ring-blue-300/60' : 'p-4 md:p-5 rounded-2xl border transition-all duration-200 flex flex-col justify-between space-y-4 bg-surface/30 border-line';
        if (iconBg) iconBg.className = isAktif ? 'w-10 h-10 rounded-xl flex items-center justify-center transition-colors bg-blue-100 text-blue-700' : 'w-10 h-10 rounded-xl flex items-center justify-center transition-colors bg-gray-100 text-gray-400';
        if (icon) icon.className = isAktif ? 'material-symbols-outlined text-[22px] animate-spin' : 'material-symbols-outlined text-[22px]';
    } else if (alat === 'misting') {
        if (card) card.className = isAktif ? 'p-4 md:p-5 rounded-2xl border transition-all duration-200 flex flex-col justify-between space-y-4 bg-white border-cyan-300 shadow-sm shadow-cyan-500/10 ring-1 ring-cyan-300/60' : 'p-4 md:p-5 rounded-2xl border transition-all duration-200 flex flex-col justify-between space-y-4 bg-surface/30 border-line';
        if (iconBg) iconBg.className = isAktif ? 'w-10 h-10 rounded-xl flex items-center justify-center transition-colors bg-cyan-100 text-cyan-700' : 'w-10 h-10 rounded-xl flex items-center justify-center transition-colors bg-gray-100 text-gray-400';
    } else if (alat === 'lampu') {
        if (card) card.className = isAktif ? 'p-4 md:p-5 rounded-2xl border transition-all duration-200 flex flex-col justify-between space-y-4 bg-white border-amber-300 shadow-sm shadow-amber-500/10 ring-1 ring-amber-300/60' : 'p-4 md:p-5 rounded-2xl border transition-all duration-200 flex flex-col justify-between space-y-4 bg-surface/30 border-line';
        if (iconBg) iconBg.className = isAktif ? 'w-10 h-10 rounded-xl flex items-center justify-center transition-colors bg-amber-100 text-amber-700' : 'w-10 h-10 rounded-xl flex items-center justify-center transition-colors bg-gray-100 text-gray-400';
    } else if (alat === 'pakan') {
        if (card) card.className = isAktif ? 'p-4 md:p-5 rounded-2xl border transition-all duration-200 flex flex-col justify-between space-y-4 bg-white border-emerald-300 shadow-sm shadow-emerald-500/10 ring-1 ring-emerald-300/60' : 'p-4 md:p-5 rounded-2xl border transition-all duration-200 flex flex-col justify-between space-y-4 bg-surface/30 border-line';
        if (iconBg) iconBg.className = isAktif ? 'w-10 h-10 rounded-xl flex items-center justify-center transition-colors bg-emerald-100 text-emerald-700' : 'w-10 h-10 rounded-xl flex items-center justify-center transition-colors bg-gray-100 text-gray-400';
        if (icon) icon.className = isAktif ? 'material-symbols-outlined text-[22px] animate-bounce' : 'material-symbols-outlined text-[22px]';
    }
}

window.updateControllerUI = updateControllerUI;
</script>
