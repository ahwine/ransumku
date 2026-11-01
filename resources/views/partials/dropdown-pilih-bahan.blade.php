@php
    $idDropdown = $id ?? ('pilih-bahan-' . uniqid());
    $fieldName = $name ?? 'ingredient_id';
    $val = old($fieldName, $value ?? ($bahan->first()->id ?? ''));
    $bahanAktif = $bahan->firstWhere('id', $val) ?? $bahan->first();
    $namaBahanAktif = $bahanAktif->nama ?? 'Pilih Bahan Pakan';
    $triggerClass = $btnClass ?? 'w-full min-h-[46px] px-3.5 py-2.5 bg-white border border-line rounded-lg text-sm font-semibold text-ink shadow-2xs hover:border-brand/40 focus:border-brand focus:ring-2 focus:ring-brand/15 transition cursor-pointer text-left';
@endphp

<div class="relative inline-block w-full text-left custom-dropdown-container z-30" id="{{ $idDropdown }}">
    <input type="hidden" name="{{ $fieldName }}" id="{{ $fieldName }}" value="{{ $val }}" class="dropdown-hidden-input" required>

    {{-- Trigger Button --}}
    <button type="button" 
            onclick="toggleCustomDropdown('{{ $idDropdown }}', event)" 
            class="dropdown-trigger flex items-center justify-between {{ $triggerClass }}" 
            title="Pilih bahan pakan yang dibeli"
            aria-haspopup="true"
            aria-expanded="false">
        <div class="flex items-center gap-2 min-w-0 pr-1">
            <span class="material-symbols-outlined text-[18px] text-brand shrink-0 dropdown-selected-icon">inventory_2</span>
            <span class="font-bold text-sm truncate dropdown-selected-label">{{ $namaBahanAktif }}</span>
        </div>
        <span class="material-symbols-outlined text-[18px] text-muted transition-transform duration-200 dropdown-caret shrink-0">expand_more</span>
    </button>

    {{-- Custom Dropdown Card --}}
    <div class="dropdown-menu hidden absolute left-0 top-full mt-1.5 w-80 sm:w-96 max-w-[calc(100vw-2.5rem)] rounded-2xl bg-white border border-line shadow-2xl p-2 z-50 select-none custom-dropdown-menu">
        {{-- Header Popup --}}
        <div class="px-2.5 py-2 border-b border-line/60 flex items-center justify-between">
            <div class="flex items-center gap-1.5 text-xs font-bold text-ink">
                <span class="material-symbols-outlined text-[17px] text-brand">inventory_2</span>
                <span>Pilih Bahan Pakan</span>
            </div>
            <span class="text-[10px] font-bold text-brand bg-brand-soft/80 border border-brand/20 px-2.5 py-0.5 rounded-full">
                {{ count($bahan) }} Bahan Terdaftar
            </span>
        </div>

        {{-- Input Pencarian Real-Time --}}
        <div class="p-2 border-b border-line/60 bg-surface/30 rounded-xl my-1.5">
            <div class="relative flex items-center">
                <span class="material-symbols-outlined absolute left-2.5 text-[17px] text-muted pointer-events-none">search</span>
                <input type="text"
                       data-dropdown-search
                       oninput="filterCustomDropdown(this, '{{ $idDropdown }}')"
                       placeholder="Cari bahan pakan..."
                       class="w-full pl-8 pr-7 py-1.5 bg-white border border-line rounded-xl text-xs text-ink placeholder:text-muted focus:outline-none focus:ring-1 focus:ring-brand focus:border-brand shadow-2xs transition"
                       autocomplete="off">
                <button type="button" 
                        data-dropdown-clear
                        onclick="clearCustomDropdownSearch(this, '{{ $idDropdown }}')" 
                        class="hidden absolute right-2 text-muted hover:text-ink cursor-pointer p-0.5 rounded-md hover:bg-surface" 
                        title="Hapus pencarian">
                    <span class="material-symbols-outlined text-[15px]">close</span>
                </button>
            </div>
        </div>

        {{-- List Bahan Pakan --}}
        <div class="max-h-60 overflow-y-auto custom-dropdown-scroll space-y-1 pr-1.5" data-dropdown-list>
            @foreach ($bahan as $b)
                @php 
                    $isSelected = ((int)$val === (int)$b->id); 
                    $searchKeyword = strtolower($b->nama);
                @endphp
                <button type="button" 
                        data-dropdown-item
                        data-value="{{ $b->id }}"
                        data-search-text="{{ $searchKeyword }}"
                        onclick="selectCustomDropdownOption('{{ $idDropdown }}', '{{ $b->id }}', '{{ addslashes($b->nama) }}', '', 'inventory_2')" 
                        class="w-full flex items-center justify-between px-2.5 py-2 rounded-xl text-left text-xs transition-all cursor-pointer group {{ $isSelected ? 'bg-surface border border-brand/30 shadow-2xs font-bold' : 'hover:bg-surface/80 text-ink/80 font-medium' }}">
                    <div class="flex items-center gap-2.5 min-w-0 pr-2">
                        <div data-item-icon-box class="w-7 h-7 rounded-lg flex items-center justify-center shrink-0 {{ $isSelected ? 'bg-brand text-white' : 'bg-surface-2 text-muted group-hover:bg-brand/10 group-hover:text-brand' }} transition-colors">
                            <span class="material-symbols-outlined text-[15px]">inventory_2</span>
                        </div>
                        <div class="min-w-0">
                            <div class="font-bold text-xs truncate leading-snug {{ $isSelected ? 'text-brand-dark' : 'text-ink group-hover:text-brand' }}">
                                {{ $b->nama }}
                            </div>
                            <div class="text-[10px] text-muted truncate">
                                Bahan Baku Pakan
                            </div>
                        </div>
                    </div>
                    <span data-active-badge class="{{ $isSelected ? 'inline-flex' : 'hidden' }} items-center gap-1 text-[10px] font-bold text-brand bg-brand-soft/80 border border-brand/20 px-2 py-0.5 rounded-full shrink-0">
                        <span class="material-symbols-outlined text-[13px]">check</span>
                        <span>Aktif</span>
                    </span>
                    <span data-choose-hint class="{{ $isSelected ? 'hidden' : 'inline-block' }} text-[10px] text-muted font-semibold opacity-0 group-hover:opacity-100 transition-opacity shrink-0">
                        Pilih
                    </span>
                </button>
            @endforeach

            {{-- Empty State jika pencarian tidak ditemukan --}}
            <div data-dropdown-empty class="hidden p-6 text-center text-xs text-muted space-y-1">
                <span class="material-symbols-outlined text-[28px] text-muted/50 block">search_off</span>
                <p class="font-bold text-ink">Bahan Tidak Ditemukan</p>
                <p class="text-tiny text-muted">Coba ketik kata kunci nama bahan pakan lainnya.</p>
            </div>
        </div>
    </div>
</div>
