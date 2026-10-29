@php
    $idDropdown = 'dropdown-' . ($idSuffix ?? uniqid());
    $kotaAktif = $farm->lokasi_kota ?? 'Surabaya';
    $namaKotaAktif = $presetKota[$kotaAktif]['nama'] ?? $kotaAktif;
    $customBtnClass = $btnClass ?? 'bg-surface hover:bg-surface-2 border-line text-ink';
    $menuAlignClass = ($align ?? 'left') === 'right' ? 'right-0' : 'left-0';
@endphp

<div class="relative inline-block text-left custom-dropdown-container" id="{{ $idDropdown }}">
    <form method="POST" action="{{ route('kandang.lokasi') }}">
        @csrf
        <input type="hidden" name="kota" value="{{ $kotaAktif }}" class="dropdown-hidden-input">

        {{-- Mother Button (Desain Pill Bersih & Konsisten) --}}
        <button type="button" 
                onclick="toggleCustomDropdown('{{ $idDropdown }}', event)" 
                class="dropdown-trigger inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full border transition-all text-xs font-semibold shadow-2xs cursor-pointer select-none group focus:outline-none {{ $customBtnClass }}" 
                title="Pilih sentra peternakan prakiraan cuaca mikro"
                aria-haspopup="true"
                aria-expanded="false">
            <span class="material-symbols-outlined text-[16px] text-brand">location_on</span>
            <span class="font-bold text-xs dropdown-selected-label">{{ $namaKotaAktif }}</span>
            <span class="material-symbols-outlined text-[16px] text-muted transition-transform duration-200 dropdown-caret">expand_more</span>
        </button>

        {{-- Custom Dropdown Card (Desain Senada dengan Mother Pill) --}}
        <div class="dropdown-menu hidden absolute {{ $menuAlignClass }} top-full mt-2 w-72 max-w-[calc(100vw-2rem)] rounded-2xl bg-white border border-line shadow-2xl p-1.5 z-50 select-none custom-dropdown-menu">
            {{-- Header Popup --}}
            <div class="px-3 py-2 border-b border-line/60 flex items-center justify-between mb-1">
                <div class="flex items-center gap-1.5 text-xs font-bold text-ink">
                    <span class="material-symbols-outlined text-[16px] text-brand">location_on</span>
                    <span>Sentra Peternakan</span>
                </div>
                <span class="text-[10px] font-mono font-bold text-brand bg-brand-soft/80 border border-brand/20 px-2 py-0.5 rounded-full">
                    {{ count($presetKota) }} Sentra
                </span>
            </div>

            {{-- List Sentra Kota --}}
            <div class="max-h-64 overflow-y-auto custom-dropdown-scroll space-y-1 pr-0.5">
                @foreach ($presetKota as $k => $p)
                    @php $isSelected = ($kotaAktif === $k); @endphp
                    <button type="button" 
                            onclick="selectCustomDropdownKota('{{ $idDropdown }}', '{{ $k }}')" 
                            class="w-full flex items-center justify-between px-2.5 py-2 rounded-xl text-left text-xs transition-all cursor-pointer group {{ $isSelected ? 'bg-surface border border-brand/30 shadow-2xs font-bold' : 'hover:bg-surface/80 text-ink/80 font-medium' }}">
                        <div class="flex items-center gap-2.5 min-w-0 pr-2">
                            <div class="w-6 h-6 rounded-lg flex items-center justify-center shrink-0 {{ $isSelected ? 'bg-brand text-white' : 'bg-surface-2 text-muted group-hover:bg-brand/10 group-hover:text-brand' }} transition-colors">
                                <span class="material-symbols-outlined text-[14px]">location_on</span>
                            </div>
                            <div class="min-w-0">
                                <div class="font-bold text-xs truncate leading-snug {{ $isSelected ? 'text-brand-dark' : 'text-ink group-hover:text-brand' }}">
                                    {{ $p['nama'] }}
                                </div>
                                <div class="text-[10px] text-muted truncate">
                                    {{ $p['provinsi'] }}
                                </div>
                            </div>
                        </div>
                        @if ($isSelected)
                            <span class="inline-flex items-center gap-1 text-[10px] font-bold text-brand bg-brand-soft/80 border border-brand/20 px-2 py-0.5 rounded-full shrink-0">
                                <span class="material-symbols-outlined text-[13px]">check</span>
                                <span>Aktif</span>
                            </span>
                        @else
                            <span class="text-[10px] text-muted font-mono opacity-0 group-hover:opacity-100 transition-opacity shrink-0">Pilih</span>
                        @endif
                    </button>
                @endforeach
            </div>
        </div>
    </form>
</div>
