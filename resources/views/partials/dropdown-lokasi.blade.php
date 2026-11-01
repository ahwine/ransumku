@php
    $idDropdown = 'dropdown-' . ($idSuffix ?? uniqid());
    $kotaAktif = $farm->lokasi_kota ?? 'Surabaya';
    $namaKotaAktif = $presetKota[$kotaAktif]['nama'] ?? $kotaAktif;
    $customBtnClass = $btnClass ?? 'bg-surface hover:bg-surface-2 border-line text-ink';
    $menuAlignClass = ($align ?? 'left') === 'right' ? 'right-0' : 'left-0';
    $daftarWilayah = ['Semua', 'Jawa', 'Sumatera', 'Bali & Nusa Tenggara', 'Kalimantan', 'Sulawesi', 'Maluku & Papua'];
@endphp

<div class="relative inline-block text-left custom-dropdown-container z-30" id="{{ $idDropdown }}">
    <form method="POST" action="{{ route('kandang.lokasi') }}">
        @csrf
        <input type="hidden" name="kota" value="{{ $kotaAktif }}" class="dropdown-hidden-input">

        {{-- Mother Button (Desain Pill Bersih & Konsisten) --}}
        <button type="button" 
                onclick="toggleCustomDropdown('{{ $idDropdown }}', event)" 
                class="dropdown-trigger inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full border transition-all text-xs font-semibold shadow-2xs cursor-pointer select-none group focus:outline-none {{ $customBtnClass }}" 
                title="Pilih lokasi atau sentra peternakan di seluruh Indonesia untuk prakiraan cuaca mikro"
                aria-haspopup="true"
                aria-expanded="false">
            <span class="material-symbols-outlined text-[16px] text-brand">location_on</span>
            <span class="font-bold text-xs dropdown-selected-label">{{ $namaKotaAktif }}</span>
            <span class="material-symbols-outlined text-[16px] text-muted transition-transform duration-200 dropdown-caret">expand_more</span>
        </button>

        {{-- Custom Dropdown Card dengan Fitur Pencarian Real-Time Seluruh Indonesia --}}
        <div class="dropdown-menu hidden absolute {{ $menuAlignClass }} top-full mt-2 w-80 sm:w-96 max-w-[calc(100vw-2rem)] rounded-2xl bg-white border border-line shadow-2xl p-2 z-50 select-none custom-dropdown-menu">
            {{-- Header Popup --}}
            <div class="px-2.5 py-2 border-b border-line/60 flex items-center justify-between">
                <div class="flex items-center gap-1.5 text-xs font-bold text-ink">
                    <span class="material-symbols-outlined text-[17px] text-brand">location_on</span>
                    <span>Wilayah & Sentra Kandang</span>
                </div>
                <span class="text-[10px] font-semibold text-muted bg-surface-2 border border-line px-2 py-0.5 rounded-full shrink-0">
                    {{ count($presetKota) }} Wilayah
                </span>
            </div>

            {{-- Input Pencarian Real-Time (Cari Kota, Kab, atau Provinsi) --}}
            <div class="p-2 border-b border-line/60 bg-surface/30 rounded-xl my-1.5">
                <div class="relative flex items-center">
                    <span class="material-symbols-outlined absolute left-2.5 text-[17px] text-muted pointer-events-none">search</span>
                    <input type="text"
                           data-dropdown-search
                           oninput="filterDropdownKota(this, '{{ $idDropdown }}')"
                           placeholder="Cari kota, kabupaten, atau provinsi..."
                           class="w-full pl-8 pr-7 py-1.5 bg-white border border-line rounded-xl text-xs text-ink placeholder:text-muted focus:outline-none focus:ring-1 focus:ring-brand focus:border-brand shadow-2xs transition"
                           autocomplete="off">
                    <button type="button" 
                            data-dropdown-clear
                            onclick="clearDropdownKota(this, '{{ $idDropdown }}')" 
                            class="hidden absolute right-2 text-muted hover:text-ink cursor-pointer p-0.5 rounded-md hover:bg-surface" 
                            title="Hapus pencarian">
                        <span class="material-symbols-outlined text-[15px]">close</span>
                    </button>
                </div>

                {{-- Filter Cepat Pulau / Wilayah --}}
                <div class="flex items-center gap-1 overflow-x-auto pt-2 pb-0.5 slim-scrollbar text-[10px]">
                    @foreach ($daftarWilayah as $idx => $wilayah)
                        <button type="button"
                                data-wilayah-btn="{{ $wilayah }}"
                                onclick="filterDropdownWilayah('{{ $wilayah }}', '{{ $idDropdown }}', this)"
                                class="px-2.5 py-0.5 rounded-full whitespace-nowrap transition-all font-semibold cursor-pointer {{ $idx === 0 ? 'bg-brand text-white font-bold' : 'bg-surface text-muted hover:text-ink hover:bg-surface-2 border border-line/60' }}">
                            {{ $wilayah }}
                        </button>
                    @endforeach
                </div>
            </div>

            {{-- List Sentra & Kota Seluruh Indonesia --}}
            <div class="max-h-64 sm:max-h-72 overflow-y-auto custom-dropdown-scroll space-y-1 pr-1.5" data-dropdown-list>
                @foreach ($presetKota as $k => $p)
                    @php 
                        $isSelected = ($kotaAktif === $k); 
                        $searchKeyword = strtolower($k . ' ' . $p['nama'] . ' ' . $p['provinsi'] . ' ' . ($p['wilayah'] ?? ''));
                    @endphp
                    <button type="button" 
                            data-city-item
                            data-search-text="{{ $searchKeyword }}"
                            data-city-wilayah="{{ $p['wilayah'] ?? '' }}"
                            onclick="selectCustomDropdownKota('{{ $idDropdown }}', '{{ $k }}')" 
                            class="w-full flex items-center justify-between px-2.5 py-2 rounded-xl text-left text-xs transition-all cursor-pointer group {{ $isSelected ? 'bg-surface border border-brand/30 shadow-2xs font-bold' : 'hover:bg-surface/80 text-ink/80 font-medium' }}">
                        <div class="flex items-center gap-2.5 min-w-0 pr-2">
                            <span class="material-symbols-outlined text-[18px] shrink-0 {{ $isSelected ? 'text-brand' : 'text-muted group-hover:text-brand' }} transition-colors">location_on</span>
                            <div class="min-w-0">
                                <div class="font-bold text-xs truncate leading-snug {{ $isSelected ? 'text-brand-dark' : 'text-ink group-hover:text-brand' }}">
                                    {{ $p['nama'] }}
                                </div>
                                <div class="text-[10px] text-muted truncate flex items-center gap-1.5">
                                    <span>{{ $p['provinsi'] }}</span>
                                    <span class="text-muted/40">•</span>
                                    <span class="text-brand/80 font-medium">{{ $p['wilayah'] ?? '' }}</span>
                                </div>
                            </div>
                        </div>
                        @if ($isSelected)
                            <span class="inline-flex items-center gap-1 text-xs font-bold text-brand shrink-0">
                                <span class="material-symbols-outlined text-[15px]">check</span>
                                <span>Dipilih</span>
                            </span>
                        @else
                            <span class="text-[11px] text-muted font-medium opacity-0 group-hover:opacity-100 transition-opacity shrink-0">Pilih</span>
                        @endif
                    </button>
                @endforeach

                {{-- Empty State jika pencarian tidak ditemukan --}}
                <div data-dropdown-empty class="hidden p-6 text-center text-xs text-muted space-y-1">
                    <span class="material-symbols-outlined text-[28px] text-muted/50 block">location_off</span>
                    <p class="font-bold text-ink">Wilayah Tidak Ditemukan</p>
                    <p class="text-tiny text-muted">Coba ketik nama kota, kabupaten, atau provinsi lain di Indonesia.</p>
                </div>
            </div>
        </div>
    </form>
</div>
