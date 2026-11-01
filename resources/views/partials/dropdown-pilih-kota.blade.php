@php
    $idDropdown = $id ?? ('pilih-kota-' . uniqid());
    $fieldName = $name ?? 'lokasi_kota';
    $val = old($fieldName, $value ?? 'Surabaya');
    $presetKota = $presetKota ?? \App\Services\WeatherService::PRESET_KOTA;
    $namaKotaAktif = $presetKota[$val]['nama'] ?? $val;
    $provinsiKotaAktif = $presetKota[$val]['provinsi'] ?? 'Indonesia';
    $triggerClass = $btnClass ?? 'w-full min-h-10 px-3.5 py-2 bg-white border border-line rounded-lg text-xs font-medium text-ink shadow-2xs hover:border-brand/40 focus:border-brand focus:ring-2 focus:ring-brand/15 transition cursor-pointer text-left';
@endphp

<div class="relative inline-block w-full text-left custom-dropdown-container z-30" id="{{ $idDropdown }}">
    <input type="hidden" name="{{ $fieldName }}" value="{{ $val }}" class="dropdown-hidden-input">

    {{-- Trigger Button (Clean, Teks Jelas & Lebar Penuh) --}}
    <button type="button" 
            onclick="toggleCustomDropdown('{{ $idDropdown }}', event)" 
            class="dropdown-trigger flex items-center justify-between {{ $triggerClass }}" 
            title="Pilih lokasi kota atau sentra kandang"
            aria-haspopup="true"
            aria-expanded="false">
        <div class="flex items-center gap-1.5 min-w-0 pr-1">
            <span class="font-medium text-xs text-ink truncate dropdown-selected-label">{{ $namaKotaAktif }}</span>
            <span class="text-[11px] text-muted font-normal truncate hidden sm:inline dropdown-selected-info">({{ $provinsiKotaAktif }})</span>
        </div>
        <span class="material-symbols-outlined text-[16px] text-muted transition-transform duration-200 dropdown-caret shrink-0">expand_more</span>
    </button>

    {{-- Custom Dropdown Card (Sleek, Langsung Menampilkan Pencarian & Opsi Kota di Atas) --}}
    <div class="dropdown-menu hidden absolute left-0 right-0 top-full mt-1.5 w-full rounded-xl bg-white border border-line shadow-2xl p-2 z-50 select-none custom-dropdown-menu">
        {{-- Input Pencarian Langsung di Paling Atas (Tanpa Header / Tab Berbelit) --}}
        <div class="relative flex items-center mb-1.5">
            <span class="material-symbols-outlined absolute left-2.5 text-[16px] text-muted pointer-events-none">search</span>
            <input type="text"
                   data-dropdown-search
                   oninput="filterDropdownKota(this, '{{ $idDropdown }}')"
                   placeholder="Ketik nama kota atau provinsi..."
                   class="w-full pl-8 pr-7 py-1.5 bg-surface border border-line/80 rounded-lg text-xs text-ink placeholder:text-muted focus:outline-none focus:bg-white focus:ring-1 focus:ring-brand focus:border-brand transition"
                   autocomplete="off">
            <button type="button" 
                    data-dropdown-clear
                    onclick="clearDropdownKota(this, '{{ $idDropdown }}')" 
                    class="hidden absolute right-2 text-muted hover:text-ink cursor-pointer p-0.5 rounded-md hover:bg-surface-2" 
                    title="Hapus pencarian">
                <span class="material-symbols-outlined text-[14px]">close</span>
            </button>
        </div>

        {{-- List Kota Langsung Muncul Tepat di Bawah Kolom Pencarian --}}
        <div class="max-h-52 overflow-y-auto custom-dropdown-scroll space-y-0.5 pr-1" data-dropdown-list>
            @foreach ($presetKota as $k => $p)
                @php 
                    $isSelected = ($val === $k); 
                    $searchKeyword = strtolower($k . ' ' . $p['nama'] . ' ' . $p['provinsi'] . ' ' . ($p['wilayah'] ?? ''));
                @endphp
                <button type="button" 
                        data-city-item
                        data-city-key="{{ $k }}"
                        data-search-text="{{ $searchKeyword }}"
                        data-city-wilayah="{{ $p['wilayah'] ?? '' }}"
                        onclick="selectCustomDropdownKotaInput('{{ $idDropdown }}', '{{ $k }}', '{{ addslashes($p['nama']) }}', '({{ addslashes($p['provinsi']) }})')" 
                        class="w-full flex items-center justify-between px-2.5 py-1.5 rounded-lg text-left text-xs transition-all cursor-pointer group {{ $isSelected ? 'bg-surface font-bold text-brand' : 'hover:bg-surface/80 text-ink font-medium' }}">
                    <div class="min-w-0 pr-2">
                        <span class="text-xs leading-snug {{ $isSelected ? 'text-brand font-bold' : 'text-ink font-medium group-hover:text-brand' }}">
                            {{ $p['nama'] }}
                        </span>
                        <span class="text-[11px] text-muted font-normal ml-1">
                            ({{ $p['provinsi'] }})
                        </span>
                    </div>
                    <span data-active-badge class="{{ $isSelected ? 'inline-block' : 'hidden' }} text-xs font-bold text-brand shrink-0">
                        Dipilih
                    </span>
                    <span data-choose-hint class="{{ $isSelected ? 'hidden' : 'inline-block' }} text-[11px] text-muted opacity-0 group-hover:opacity-100 transition-opacity shrink-0">
                        Pilih
                    </span>
                </button>
            @endforeach

            {{-- Empty State jika pencarian tidak ditemukan --}}
            <div data-dropdown-empty class="hidden p-4 text-center text-xs text-muted">
                <p class="font-bold text-ink">Kota Tidak Ditemukan</p>
                <p class="text-[11px] text-muted mt-0.5">Ketik nama kota atau provinsi lain di Indonesia.</p>
            </div>
        </div>
    </div>
</div>
