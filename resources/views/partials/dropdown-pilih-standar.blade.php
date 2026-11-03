@php
    $idDropdown = $id ?? ('pilih-standar-' . uniqid());
    $fieldName = $name ?? 'standard_id';
    $val = (int) old($fieldName, $value ?? ($recommendedStandardId ?? ($standards->first()->id ?? 0)));
    $stdAktif = $standards->firstWhere('id', $val) ?? $standards->first();
    $namaStdAktif = $stdAktif ? $stdAktif->nama : 'Pilih Acuan Standar Gizi';
    $triggerClass = $btnClass ?? 'w-full min-h-[46px] px-3.5 py-2.5 bg-white border border-line rounded-xl text-sm font-semibold text-ink shadow-2xs hover:border-line-dark focus:border-brand focus:ring-1 focus:ring-brand/20 transition cursor-pointer text-left';
@endphp

<div class="relative inline-block w-full text-left custom-dropdown-container z-30" id="{{ $idDropdown }}">
    <input type="hidden" name="{{ $fieldName }}" id="{{ $fieldName }}" value="{{ $val }}" class="dropdown-hidden-input" required>

    {{-- Trigger Button --}}
    <button type="button"
            onclick="toggleCustomDropdown('{{ $idDropdown }}', event)"
            class="dropdown-trigger flex items-center justify-between {{ $triggerClass }}"
            title="Pilih acuan standar gizi"
            aria-haspopup="true"
            aria-expanded="false">
        <div class="flex items-center gap-2 min-w-0 pr-1">
            <span class="font-bold text-sm text-ink truncate dropdown-selected-label">{{ $namaStdAktif }}</span>
            @if ($stdAktif && ! $stdAktif->verified)
                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-800 border border-amber-300 shrink-0 hidden sm:inline-block">Belum terverifikasi</span>
            @endif
        </div>
        <span class="material-symbols-outlined text-[18px] text-muted transition-transform duration-200 dropdown-caret shrink-0">expand_more</span>
    </button>

    {{-- Custom Dropdown Card --}}
    <div class="dropdown-menu hidden absolute left-0 top-full mt-1.5 w-full sm:w-[440px] max-w-[calc(100vw-2.5rem)] rounded-2xl bg-white border border-line shadow-xl p-1.5 z-50 select-none custom-dropdown-menu">
        <div class="max-h-64 overflow-y-auto custom-dropdown-scroll space-y-1" data-dropdown-list>
            @foreach ($standards as $s)
                @php
                    $isSelected = ($val === (int) $s->id);
                    $isRecommended = (! empty($recommendedStandardId) && (int) $s->id === (int) $recommendedStandardId);
                    $labelLengkap = $s->nama;
                    $subSpek = 'PK ' . $s->formatProtein() . ' • Ca ' . $s->formatKalsium() . ' • P ' . $s->formatFosfor();
                @endphp
                <button type="button"
                        data-dropdown-item
                        data-value="{{ $s->id }}"
                        onclick="selectCustomDropdownOption('{{ $idDropdown }}', '{{ $s->id }}', '{{ addslashes($labelLengkap) }}', '', '')"
                        class="w-full flex items-center justify-between px-3 py-2.5 rounded-xl text-left text-xs transition-all cursor-pointer {{ $isSelected ? 'bg-surface font-bold text-ink ring-1 ring-brand/30' : 'hover:bg-surface/70 text-ink/80 font-medium' }}">
                    <div class="min-w-0 pr-2">
                        <div class="flex items-center gap-1.5 flex-wrap">
                            <span class="font-bold text-xs text-ink truncate leading-snug">
                                {{ $labelLengkap }}
                            </span>
                            @if ($isRecommended && $s->verified)
                                <span class="px-1.5 py-0.2 rounded text-[9px] font-extrabold bg-emerald-100 text-emerald-800 uppercase tracking-tight">Sesuai Fase</span>
                            @elseif ($s->verified)
                                <span class="px-1.5 py-0.2 rounded text-[9px] font-bold bg-emerald-50 text-emerald-800 border border-emerald-200 uppercase tracking-tight">Terverifikasi</span>
                            @else
                                <span class="px-1.5 py-0.2 rounded text-[9px] font-bold bg-amber-50 text-amber-800 border border-amber-300">Belum terverifikasi</span>
                                @if ($s->isSumberSekunder())
                                    <span class="px-1.5 py-0.2 rounded text-[9px] font-bold bg-sky-50 text-sky-800 border border-sky-200">Sumber sekunder</span>
                                @endif
                            @endif
                        </div>
                        <div class="text-[10px] text-muted truncate mt-0.5">
                            {{ $subSpek }}
                        </div>
                    </div>
                    <span data-active-badge class="{{ $isSelected ? 'inline-block' : 'hidden' }} material-symbols-outlined text-[18px] text-brand shrink-0">
                        check_circle
                    </span>
                </button>
            @endforeach
        </div>
    </div>
</div>
