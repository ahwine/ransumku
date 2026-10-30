@php
    $idDropdown = $id ?? ('pilih-standar-' . uniqid());
    $fieldName = $name ?? 'standard_id';
    $val = (int) old($fieldName, $value ?? ($standards->first()->id ?? 0));
    $stdAktif = $standards->firstWhere('id', $val) ?? $standards->first();
    $namaStdAktif = $stdAktif ? ($stdAktif->nama . ($stdAktif->tahun ? ' (' . $stdAktif->tahun . ')' : '')) : 'Pilih Acuan Standar Gizi';
    $triggerClass = $btnClass ?? 'w-full min-h-[46px] px-3.5 py-2.5 bg-white border border-line rounded-lg text-sm font-semibold text-ink shadow-2xs hover:border-brand/40 focus:border-brand focus:ring-2 focus:ring-brand/15 transition cursor-pointer text-left';
@endphp

<div class="relative inline-block w-full text-left custom-dropdown-container z-30" id="{{ $idDropdown }}">
    <input type="hidden" name="{{ $fieldName }}" id="{{ $fieldName }}" value="{{ $val }}" class="dropdown-hidden-input" required>

    {{-- Trigger Button --}}
    <button type="button" 
            onclick="toggleCustomDropdown('{{ $idDropdown }}', event)" 
            class="dropdown-trigger flex items-center justify-between {{ $triggerClass }}" 
            title="Pilih acuan standar gizi untuk optimasi formula pakan"
            aria-haspopup="true"
            aria-expanded="false">
        <div class="flex items-center gap-2 min-w-0 pr-1">
            <span class="material-symbols-outlined text-[18px] text-brand shrink-0 dropdown-selected-icon">fact_check</span>
            <span class="font-bold text-sm truncate dropdown-selected-label">{{ $namaStdAktif }}</span>
        </div>
        <span class="material-symbols-outlined text-[18px] text-muted transition-transform duration-200 dropdown-caret shrink-0">expand_more</span>
    </button>

    {{-- Custom Dropdown Card --}}
    <div class="dropdown-menu hidden absolute left-0 top-full mt-1.5 w-80 sm:w-96 max-w-[calc(100vw-2.5rem)] rounded-2xl bg-white border border-line shadow-2xl p-2 z-50 select-none custom-dropdown-menu">
        {{-- Header Popup --}}
        <div class="px-2.5 py-2 border-b border-line/60 flex items-center justify-between">
            <div class="flex items-center gap-1.5 text-xs font-bold text-ink">
                <span class="material-symbols-outlined text-[17px] text-brand">fact_check</span>
                <span>Acuan Standar Gizi</span>
            </div>
            <span class="text-[10px] font-mono font-bold text-brand bg-brand-soft/80 border border-brand/20 px-2 py-0.5 rounded-full">
                {{ count($standards) }} Standar Terdaftar
            </span>
        </div>

        {{-- List Acuan Standar --}}
        <div class="max-h-60 overflow-y-auto custom-dropdown-scroll space-y-1 pt-1.5 pr-1.5" data-dropdown-list>
            @foreach ($standards as $s)
                @php 
                    $isSelected = ($val === (int)$s->id); 
                    $labelLengkap = $s->nama . ($s->tahun ? ' (' . $s->tahun . ')' : '');
                    $subSpek = 'PK min ' . \App\Support\Fmt::num($s->protein_min, 2) . '% • Ca ' . \App\Support\Fmt::num($s->kalsium_min, 2) . '–' . \App\Support\Fmt::num($s->kalsium_max, 2) . '% • ME ' . \App\Support\Fmt::num($s->energi_min, 0) . ' kkal';
                @endphp
                <button type="button" 
                        data-dropdown-item
                        data-value="{{ $s->id }}"
                        onclick="selectCustomDropdownOption('{{ $idDropdown }}', '{{ $s->id }}', '{{ addslashes($labelLengkap) }}', '', 'fact_check')" 
                        class="w-full flex items-center justify-between px-2.5 py-2 rounded-xl text-left text-xs transition-all cursor-pointer group {{ $isSelected ? 'bg-surface border border-brand/30 shadow-2xs font-bold' : 'hover:bg-surface/80 text-ink/80 font-medium' }}">
                    <div class="flex items-center gap-2.5 min-w-0 pr-2">
                        <div data-item-icon-box class="w-7 h-7 rounded-lg flex items-center justify-center shrink-0 {{ $isSelected ? 'bg-brand text-white' : 'bg-surface-2 text-muted group-hover:bg-brand/10 group-hover:text-brand' }} transition-colors">
                            <span class="material-symbols-outlined text-[15px]">fact_check</span>
                        </div>
                        <div class="min-w-0">
                            <div class="font-bold text-xs truncate leading-snug {{ $isSelected ? 'text-brand-dark' : 'text-ink group-hover:text-brand' }}">
                                {{ $labelLengkap }}
                            </div>
                            <div class="text-[10px] text-muted truncate">
                                {{ $subSpek }}
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
