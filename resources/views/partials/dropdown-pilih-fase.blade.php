@php
    $idDropdown = $id ?? ('pilih-fase-' . uniqid());
    $fieldName = $name ?? 'fase';
    $val = old($fieldName, $value ?? 'produksi');
    
    $faseList = [
        'produksi' => [
            'label' => 'Produksi',
            'subtitle' => 'Ayam Bertelur / Layer Aktif',
            'tag' => 'Fase Utama',
        ],
        'grower' => [
            'label' => 'Grower',
            'subtitle' => 'Pullet / Remaja Persiapan Telur',
            'tag' => 'Remaja',
        ],
        'starter' => [
            'label' => 'Starter',
            'subtitle' => 'DOC / Fase Awal 0–6 Minggu',
            'tag' => 'DOC',
        ],
    ];

    $faseAktif = $faseList[$val] ?? $faseList['produksi'];
    $triggerClass = $btnClass ?? 'w-full min-h-10 px-3.5 py-2 bg-white border border-line rounded-lg text-xs font-medium text-ink shadow-2xs hover:border-brand/40 focus:border-brand focus:ring-2 focus:ring-brand/15 transition cursor-pointer text-left';
@endphp

<div class="relative inline-block w-full text-left custom-dropdown-container z-30" id="{{ $idDropdown }}">
    <input type="hidden" name="{{ $fieldName }}" value="{{ $val }}" class="dropdown-hidden-input">

    {{-- Trigger Button (Clean, Tanpa Ikon, Label Jelas & Tidak Terpotong) --}}
    <button type="button" 
            onclick="toggleCustomDropdown('{{ $idDropdown }}', event)" 
            class="dropdown-trigger flex items-center justify-between {{ $triggerClass }}" 
            title="Pilih fase pemeliharaan ayam"
            aria-haspopup="true"
            aria-expanded="false">
        <div class="min-w-0 pr-1">
            <span class="font-medium text-xs text-ink truncate block dropdown-selected-label">{{ $faseAktif['label'] }}</span>
            <span class="dropdown-selected-sublabel hidden">({{ $faseAktif['subtitle'] }})</span>
        </div>
        <span class="material-symbols-outlined text-[16px] text-muted transition-transform duration-200 dropdown-caret shrink-0">expand_more</span>
    </button>

    {{-- Custom Dropdown Card (Posisi right-0 agar tidak terpotong tepi kanan modal) --}}
    <div class="dropdown-menu hidden absolute right-0 top-full mt-1.5 w-72 sm:w-80 max-w-[calc(100vw-2.5rem)] rounded-xl bg-white border border-line shadow-2xl p-2 z-50 select-none custom-dropdown-menu">
        {{-- Header Popup (Clean, Tanpa Ikon & Tanpa Pill Badge) --}}
        <div class="px-2.5 py-2 border-b border-line/60 flex items-center justify-between">
            <span class="text-xs font-bold text-ink">Fase Pemeliharaan</span>
            <span class="text-[11px] text-muted">3 Fase Ternak</span>
        </div>

        {{-- Options List (Tanpa Ikon & Tanpa Pill Badge) --}}
        <div class="space-y-1 pt-1.5" data-dropdown-list>
            @foreach ($faseList as $k => $f)
                @php $isSelected = ($val === $k); @endphp
                <button type="button" 
                        data-dropdown-item
                        data-value="{{ $k }}"
                        onclick="selectCustomDropdownOption('{{ $idDropdown }}', '{{ $k }}', '{{ $f['label'] }}', '({{ $f['subtitle'] }})', '')" 
                        class="w-full flex items-center justify-between px-3 py-2 rounded-lg text-left text-xs transition-all cursor-pointer group {{ $isSelected ? 'bg-surface font-bold text-brand' : 'hover:bg-surface/80 text-ink font-medium' }}">
                    <div class="min-w-0 pr-2">
                        <div class="text-xs leading-snug {{ $isSelected ? 'text-brand font-bold' : 'text-ink font-semibold group-hover:text-brand' }}">
                            {{ $f['label'] }}
                        </div>
                        <div class="text-[11px] text-muted font-normal mt-0.5">
                            {{ $f['subtitle'] }}
                        </div>
                    </div>
                    <span data-active-badge class="{{ $isSelected ? 'inline-block' : 'hidden' }} text-xs font-bold text-brand shrink-0">
                        Aktif
                    </span>
                    <span data-choose-hint class="{{ $isSelected ? 'hidden' : 'inline-block' }} text-xs text-muted opacity-0 group-hover:opacity-100 transition-opacity shrink-0">
                        Pilih
                    </span>
                </button>
            @endforeach
        </div>
    </div>
</div>
