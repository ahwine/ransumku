{{-- Komponen Visual Cuaca Mikro & Indeks Stres Panas (THI) Kandang --}}
@php
    $weatherType = $weather['weather_type'] ?? 'partly_cloudy';
    $thiPercent = $weather['thi_percent'] ?? 50.0;
    $dampakAyam = $weather['dampak_ayam'] ?? ($weather['deskripsi_risiko'] ?? 'Kondisi fisiologi ayam terpantau aman.');
@endphp
<div class="space-y-4">
    {{-- Baris 1: Header Cuaca Ramping (Judul & Timestamp Bersih) --}}
    <div class="flex items-center justify-between gap-2 pb-3 border-b border-line">
        <span class="text-xs font-bold text-ink uppercase tracking-wider flex items-center gap-1.5">
            <span class="material-symbols-outlined text-[16px] text-brand">thermostat</span>
            <span>Cuaca Mikro Kandang</span>
        </span>
        <span class="text-tiny font-medium text-muted">Pembaruan: {{ $weather['updated_at'] }}</span>
    </div>

    {{-- Baris 2: Visual Suhu & Gauge Indeks Stres Panas (THI) --}}
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-center py-2">
        {{-- Sisi Kiri (Kolom 1-5): Ikon Cuaca Murni (Tanpa Kotak Badge) & Angka Suhu --}}
        <div class="lg:col-span-5 flex items-center gap-4 md:gap-5">
            {{-- Ilustrasi Cuaca SVG Langsung (Murni Tanpa Border/Badge Box) --}}
            <div class="w-16 h-16 md:w-20 md:h-20 shrink-0 grid place-items-center">
                @if ($weatherType === 'sunny')
                    {{-- Sun SVG --}}
                    <svg class="w-14 h-14 md:w-16 md:h-16 animate-[spin_20s_linear_infinite]" viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <circle cx="32" cy="32" r="14" fill="url(#sunGrad)" />
                        <g stroke="#F59E0B" stroke-width="3" stroke-linecap="round">
                            <line x1="32" y1="4" x2="32" y2="10" />
                            <line x1="32" y1="54" x2="32" y2="60" />
                            <line x1="4" y1="32" x2="10" y2="32" />
                            <line x1="54" y1="32" x2="60" y2="32" />
                            <line x1="12.2" y1="12.2" x2="16.5" y2="16.5" />
                            <line x1="47.5" y1="47.5" x2="51.8" y2="51.8" />
                            <line x1="12.2" y1="51.8" x2="16.5" y2="47.5" />
                            <line x1="47.5" y1="16.5" x2="51.8" y2="12.2" />
                        </g>
                        <defs>
                            <radialGradient id="sunGrad" cx="0" cy="0" r="1" gradientUnits="userSpaceOnUse" gradientTransform="translate(32 32) rotate(90) scale(14)">
                                <stop stop-color="#FCD34D" />
                                <stop offset="1" stop-color="#F59E0B" />
                            </radialGradient>
                        </defs>
                    </svg>
                @elseif ($weatherType === 'partly_cloudy')
                    {{-- Sun + Cloud SVG --}}
                    <svg class="w-14 h-14 md:w-16 md:h-16" viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <circle cx="38" cy="22" r="11" fill="url(#sunPartly)" />
                        <g stroke="#F59E0B" stroke-width="2.5" stroke-linecap="round">
                            <line x1="38" y1="6" x2="38" y2="9" />
                            <line x1="52" y1="14" x2="50" y2="16" />
                            <line x1="54" y1="22" x2="51" y2="22" />
                            <line x1="26" y1="14" x2="28" y2="16" />
                        </g>
                        <path d="M20 48H44C47.866 48 51 44.866 51 41C51 37.134 47.866 34 44 34C43.7 34 43.4 34.02 43.1 34.06C42.06 28.32 37.07 24 31 24C24.37 24 19 29.37 19 36C19 36.35 19.01 36.69 19.05 37.03C15.65 37.56 13 40.48 13 44C13 46.21 14.79 48 17 48H20Z" fill="url(#cloudGrad)" stroke="#CBD5E1" stroke-width="1.5" />
                        <defs>
                            <linearGradient id="sunPartly" x1="27" y1="11" x2="49" y2="33" gradientUnits="userSpaceOnUse">
                                <stop stop-color="#FDE047" />
                                <stop offset="1" stop-color="#EA580C" />
                            </linearGradient>
                            <linearGradient id="cloudGrad" x1="13" y1="24" x2="51" y2="48" gradientUnits="userSpaceOnUse">
                                <stop stop-color="#FFFFFF" />
                                <stop offset="1" stop-color="#E2E8F0" />
                            </linearGradient>
                        </defs>
                    </svg>
                @elseif ($weatherType === 'rainy')
                    {{-- Rain SVG --}}
                    <svg class="w-14 h-14 md:w-16 md:h-16" viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M19 40H45C48.3 40 51 37.3 51 34C51 30.7 48.3 28 45 28C44.7 28 44.4 28.02 44.1 28.06C43.1 23.32 38.9 20 34 20C28.5 20 24 24.5 24 30C24 30.3 24.01 30.6 24.05 30.9C21.1 31.4 19 33.9 19 36.8C19 38.6 20.4 40 22.2 40H19Z" fill="#CBD5E1" stroke="#94A3B8" stroke-width="1.5" />
                        <line x1="24" y1="44" x2="21" y2="52" stroke="#3B82F6" stroke-width="2.5" stroke-linecap="round" />
                        <line x1="33" y1="44" x2="30" y2="52" stroke="#3B82F6" stroke-width="2.5" stroke-linecap="round" />
                        <line x1="42" y1="44" x2="39" y2="52" stroke="#3B82F6" stroke-width="2.5" stroke-linecap="round" />
                    </svg>
                @elseif ($weatherType === 'thunderstorm')
                    {{-- Storm SVG --}}
                    <svg class="w-14 h-14 md:w-16 md:h-16" viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M19 36H45C48.3 36 51 33.3 51 30C51 26.7 48.3 24 45 24C44.7 24 44.4 24.02 44.1 24.06C43.1 19.32 38.9 16 34 16C28.5 16 24 20.5 24 26C24 26.3 24.01 26.6 24.05 26.9C21.1 27.4 19 29.9 19 32.8C19 34.6 20.4 36 22.2 36H19Z" fill="#475569" />
                        <polygon points="34,34 26,46 33,46 29,58 41,44 34,44" fill="#FACC15" stroke="#EAB308" stroke-width="1" />
                    </svg>
                @else
                    {{-- Overcast Cloud SVG --}}
                    <svg class="w-14 h-14 md:w-16 md:h-16" viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M18 46H46C50 46 53 43 53 39C53 35.2 50 32 46.2 32C45.9 32 45.5 32.02 45.2 32.06C44.1 26.3 39 22 33 22C26.4 22 21 27.4 21 34C21 34.3 21.01 34.7 21.05 35C17.6 35.6 15 38.5 15 42C15 44.2 16.8 46 19 46H18Z" fill="#E2E8F0" stroke="#94A3B8" stroke-width="1.5" />
                    </svg>
                @endif
            </div>

            {{-- Angka Suhu & Ringkasan Cuaca --}}
            <div>
                <span class="sr-only">{{ $weather['suhu'] }}°C</span>
                <div class="flex items-baseline gap-1">
                    <span class="text-3xl md:text-4xl lg:text-5xl font-black text-ink tracking-tight">{{ $weather['suhu'] }}</span>
                    <span class="text-xl md:text-2xl font-bold text-muted">°C</span>
                </div>
                <p class="text-xs md:text-sm font-bold text-ink mt-0.5">{{ $weather['cuaca_teks'] }}</p>
                <div class="flex items-center gap-3 text-tiny font-medium text-muted mt-1.5">
                    <span class="flex items-center gap-1" title="Kelembaban Relatif (RH)">
                        <span class="material-symbols-outlined text-[15px] text-blue-500">water_drop</span>
                        <strong>{{ $weather['kelembaban'] }}%</strong> RH
                    </span>
                    <span class="flex items-center gap-1" title="Kecepatan Angin">
                        <span class="material-symbols-outlined text-[15px] text-teal-600">air</span>
                        <strong>{{ $weather['angin_kmh'] }}</strong> km/h
                    </span>
                    <span class="hidden sm:inline" title="Suhu Terasa (Heat Index)">
                        Terasa ~<strong>{{ $weather['apparent_temp'] }}°C</strong>
                    </span>
                </div>
            </div>
        </div>

        {{-- Sisi Kanan (Kolom 6-12): Visual Gauge Indeks Stres Panas (THI) Bersih & Modern --}}
        <div class="lg:col-span-7 bg-white rounded-xl p-4 border border-line space-y-3 shadow-2xs">
            {{-- Header THI Status dengan Pill Badge Melengkung Halus --}}
            <div class="flex items-center justify-between flex-wrap gap-2">
                <span class="text-xs font-bold uppercase tracking-wider text-ink flex items-center gap-1.5">
                    <span class="material-symbols-outlined text-[16px] text-amber-600">thermostat_auto</span>
                    <span>Indeks Stres Panas (THI)</span>
                </span>
                <span class="px-3.5 py-1 rounded-full text-xs font-bold shadow-2xs {{ $weather['badge_class'] }}">
                    THI {{ $weather['thi'] }} · {{ $weather['thi_kategori_singkat'] }}
                </span>
            </div>

            {{-- Visual Bar Gauge THI Unggas (Rentang 60 - 90+) --}}
            <div class="space-y-1.5 pt-1">
                {{-- Pin Marker Posisi THI Saat Ini (Di Atas Bar, Menunjuk ke Titik Nilai) --}}
                <div class="relative w-full h-6 mb-1.5">
                    <div class="absolute bottom-0 -translate-x-1/2 flex flex-col items-center transition-all duration-500" style="left: {{ $thiPercent }}%;">
                        <span class="text-[11px] font-bold text-ink whitespace-nowrap leading-none bg-surface border border-line px-2 py-1 rounded-md shadow-2xs">
                            THI {{ $weather['thi'] }}
                        </span>
                        <span class="w-1.5 h-1.5 rotate-45 bg-surface border-r border-b border-line -mt-1 shadow-2xs"></span>
                    </div>
                </div>

                {{-- Bar Warna THI: 40% Nyaman, 20% Waspada, 20% Kritis, 20% Bahaya --}}
                <div class="relative w-full h-2 rounded-full bg-slate-100 overflow-hidden shadow-inner flex">
                    <div class="h-full bg-emerald-500" style="width: 40%;" title="Zona Nyaman: THI < 72"></div>
                    <div class="h-full bg-amber-400" style="width: 20%;" title="Waspada Panas: THI 72 - 78"></div>
                    <div class="h-full bg-orange-500" style="width: 20%;" title="Kritis Heat Stress: THI 78 - 84"></div>
                    <div class="h-full bg-rose-600" style="width: 20%;" title="Bahaya Ekstrem: THI ≥ 84"></div>
                </div>

                {{-- Label Skala THI: Masing-masing Tepat Sejajar di Bawah Segmen Warnanya --}}
                <div class="flex w-full text-[8px] sm:text-[9px] text-muted font-medium pt-1 select-none">
                    <div style="width: 40%;" class="text-center truncate">
                        <span>&lt; 72 (Nyaman)</span>
                    </div>
                    <div style="width: 20%;" class="text-center truncate">
                        <span>72-78 (Waspada)</span>
                    </div>
                    <div style="width: 20%;" class="text-center truncate">
                        <span>78-84 (Kritis)</span>
                    </div>
                    <div style="width: 20%;" class="text-center truncate">
                        <span>&gt; 84 (Bahaya)</span>
                    </div>
                </div>
            </div>

            {{-- Dampak Fisiologi Ayam Petelur --}}
            <div class="flex items-start gap-2 pt-1 border-t border-line/60 text-xs">
                <span class="material-symbols-outlined text-[17px] text-muted shrink-0 mt-0.5" aria-hidden="true">info</span>
                <p class="text-ink font-medium leading-relaxed">
                    <strong class="font-bold">Kondisi Ayam:</strong> {{ $dampakAyam }}
                </p>
            </div>
        </div>
    </div>

    {{-- Baris 3: Rekomendasi Manajemen Pakan Cuaca Terkini (Collapsible Accordion Opsi 1) --}}
    <details class="group pt-3 border-t border-line">
        <summary class="flex items-center justify-between cursor-pointer list-none select-none py-0.5 text-xs transition-colors hover:text-brand [&::-webkit-details-marker]:hidden">
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full border border-line bg-surface hover:bg-surface-2 transition text-xs font-semibold text-ink shadow-2xs">
                    <span class="material-symbols-outlined text-[15px] text-amber-600">lightbulb</span>
                    <span>{{ count($weather['saran_manajemen']) }} Langkah Mitigasi Pakan Cuaca Terkini</span>
                </span>
                <span class="hidden sm:inline text-tiny text-muted">Berdasarkan Standar Fisiologi Unggas Tropis</span>
            </div>
            <div class="flex items-center gap-1 text-tiny font-semibold text-muted group-hover:text-brand transition-colors">
                <span class="group-open:hidden">Buka Panduan</span>
                <span class="hidden group-open:inline">Tutup Panduan</span>
                <span class="material-symbols-outlined text-[16px] transition-transform duration-200 group-open:rotate-180">expand_more</span>
            </div>
        </summary>

        {{-- Grid Kartu Rekomendasi (Muncul Saat Dibuka) --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-2.5 pt-3 mt-2.5 border-t border-dashed border-line">
            @foreach ($weather['saran_manajemen'] as $index => $saran)
                <div class="p-3 rounded-lg bg-surface/30 border border-line text-xs flex items-start gap-2.5 shadow-2xs">
                    <span class="w-5 h-5 rounded-full bg-brand/10 text-brand font-bold text-tiny flex items-center justify-center shrink-0 mt-0.5">
                        {{ $index + 1 }}
                    </span>
                    <span class="text-ink font-medium leading-relaxed">{{ $saran }}</span>
                </div>
            @endforeach
        </div>
    </details>
</div>
