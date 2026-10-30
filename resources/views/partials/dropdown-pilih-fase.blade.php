@php
    $idDropdown = $id ?? ('pilih-fase-' . uniqid());
    $fieldName = $name ?? 'fase';
    $val = old($fieldName, $value ?? 'produksi');
    
    $faseList = [
        'produksi' => [
            'label' => 'Produksi',
            'subtitle' => 'Ayam Bertelur / Layer Aktif',
            'icon' => 'egg',
            'tag' => 'Fase Utama',
        ],
        'grower' => [
            'label' => 'Grower',
            'subtitle' => 'Pullet / Remaja Persiapan Telur',
            'icon' => 'layers',
            'tag' => 'Remaja',
        ],
        'starter' => [
            'label' => 'Starter',
            'subtitle' => 'DOC / Fase Awal 0–6 Minggu',
            'icon' => 'nest_cam_wired_stand',
            'tag' => 'DOC',
        ],
    ];

    $faseAktif = $faseList[$val] ?? $faseList['produksi'];
    $triggerClass = $btnClass ?? 'w-full min-h-[42px] px-3 py-2 bg-white border border-line rounded-xl text-xs font-semibold text-ink shadow-2xs hover:border-brand/40 focus:border-brand focus:ring-2 focus:ring-brand/15 transition cursor-pointer text-left';
@endphp

<div class="relative inline-block w-full text-left custom-dropdown-container z-30" id="{{ $idDropdown }}">
    <input type="hidden" name="{{ $fieldName }}" value="{{ $val }}" class="dropdown-hidden-input">

    {{-- Trigger Button --}}
    <button type="button" 
            onclick="toggleCustomDropdown('{{ $idDropdown }}', event)" 
            class="dropdown-trigger flex items-center justify-between {{ $triggerClass }}" 
            title="Pilih fase pemeliharaan ayam"
            aria-haspopup="true"
            aria-expanded="false">
        <div class="flex items-center gap-2 min-w-0 pr-1">
            <span class="material-symbols-outlined text-[16px] text-brand shrink-0 dropdown-selected-icon">{{ $faseAktif['icon'] }}</span>
            <span class="font-bold text-xs truncate dropdown-selected-label">{{ $faseAktif['label'] }}</span>
            <span class="text-[10px] text-muted font-normal truncate hidden sm:inline dropdown-selected-sublabel">({{ $faseAktif['subtitle'] }})</span>
        </div>
        <span class="material-symbols-outlined text-[16px] text-muted transition-transform duration-200 dropdown-caret shrink-0">expand_more</span>
    </button>

    {{-- Custom Dropdown Card --}}
    <div class="dropdown-menu hidden absolute left-0 top-full mt-1.5 w-72 sm:w-80 max-w-[calc(100vw-2.5rem)] rounded-2xl bg-white border border-line shadow-2xl p-2 z-50 select-none custom-dropdown-menu">
        {{-- Header Popup --}}
        <div class="px-2.5 py-2 border-b border-line/60 flex items-center justify-between">
            <div class="flex items-center gap-1.5 text-xs font-bold text-ink">
                <span class="material-symbols-outlined text-[17px] text-brand">layers</span>
                <span>Fase Pemeliharaan</span>
            </div>
            <span class="text-[10px] font-mono font-bold text-brand bg-brand-soft/80 border border-brand/20 px-2 py-0.5 rounded-full">
                3 Fase Ternak
            </span>
        </div>

        {{-- Options List --}}
        <div class="space-y-1 pt-1.5" data-dropdown-list>
            @foreach ($faseList as $k => $f)
                @php $isSelected = ($val === $k); @endphp
                <button type="button" 
                        data-dropdown-item
                        data-value="{{ $k }}"
                        onclick="selectCustomDropdownOption('{{ $idDropdown }}', '{{ $k }}', '{{ $f['label'] }}', '({{ $f['subtitle'] }})', '{{ $f['icon'] }}')" 
                        class="w-full flex items-center justify-between px-2.5 py-2 rounded-xl text-left text-xs transition-all cursor-pointer group {{ $isSelected ? 'bg-surface border border-brand/30 shadow-2xs font-bold' : 'hover:bg-surface/80 text-ink/80 font-medium' }}">
                    <div class="flex items-center gap-2.5 min-w-0 pr-2">
                        <div data-item-icon-box class="w-7 h-7 rounded-lg flex items-center justify-center shrink-0 {{ $isSelected ? 'bg-brand text-white' : 'bg-surface-2 text-muted group-hover:bg-brand/10 group-hover:text-brand' }} transition-colors">
                            <span class="material-symbols-outlined text-[15px]">{{ $f['icon'] }}</span>
                        </div>
                        <div class="min-w-0">
                            <div class="font-bold text-xs truncate leading-snug {{ $isSelected ? 'text-brand-dark' : 'text-ink group-hover:text-brand' }}">
                                {{ $f['label'] }}
                            </div>
                            <div class="text-[10px] text-muted truncate">
                                {{ $f['subtitle'] }}
                            </div>
                        </div>
                    </div>
                    <span data-active-badge class="{{ $isSelected ? 'inline-flex' : 'hidden' }} items-center gap-1 text-[10px] font-bold text-brand bg-brand-soft/80 border border-brand/20 px-2 py-0.5 rounded-full shrink-0">
                        <span class="material-symbols-outlined text-[13px]">check</span>
                        <span>Aktif</span>
                    </span>
                    <span data-choose-hint class="{{ $isSelected ? 'hidden' : 'inline-block' }} text-[10px] text-muted font-mono opacity-0 group-hover:opacity-100 transition-opacity shrink-0">
                        Pilih
                    </span>
                </button>
            @endforeach
        </div>
    </div>
</div>
