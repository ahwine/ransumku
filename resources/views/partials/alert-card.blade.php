@php
    // Kartu peringatan dini (indikasi berbasis pola data, bukan diagnosis)
    $danger = $alert->level === 'waspada';
    $tanyaPrompt = 'Tolong berikan analisis mendalam dan rekomendasi solusi cepat untuk kendala di kandang saya: ' . $alert->pesan;
    $isList = $is_list ?? false;
@endphp

<div class="{{ $isList ? 'p-4 md:p-5 hover:bg-surface/30 transition-colors' : 'rounded-xl border border-line bg-white p-4 md:p-5 transition-all shadow-2xs hover:shadow-xs' }} flex flex-col md:flex-row md:items-center justify-between gap-4">
    <div class="flex items-start gap-3 min-w-0">
        {{-- Ikon Peringatan Murni (Tanpa Kotak/Badge) --}}
        <span class="material-symbols-outlined text-[20px] {{ $danger ? 'text-rose-600' : 'text-amber-600' }} shrink-0 mt-0.5">
            {{ $danger ? 'error' : 'warning' }}
        </span>

        <div class="space-y-1 min-w-0">
            <div class="flex flex-wrap items-center gap-2">
                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider shadow-2xs {{ $danger ? 'bg-rose-50 text-rose-700 border border-rose-200' : 'bg-amber-50 text-amber-800 border border-amber-200' }}">
                    {{ $danger ? 'Waspada' : 'Perhatian' }}
                </span>
                <span class="text-tiny font-medium text-muted">
                    {{ optional($alert->tanggal_data)->translatedFormat('d F Y') }}
                </span>
            </div>

            <p class="text-xs md:text-sm font-semibold text-ink leading-relaxed">
                {{ $alert->pesan }}
            </p>

            @if (!$isList)
                <div class="pt-2 flex items-center gap-1.5 text-tiny text-muted">
                    <span class="material-symbols-outlined text-[13px] shrink-0">info</span>
                    <span>{{ config('ransumku.disclaimer_ai') }}</span>
                </div>
            @endif
        </div>
    </div>

    {{-- Tombol Aksi Minimalis & Profesional (Tanpa AI Sparkles yang Terkesan Generik) --}}
    <div class="flex items-center shrink-0 self-end md:self-center pl-8 md:pl-0">
        <a href="{{ route('asisten.index', ['tab' => 'tanya', 'tanya' => $tanyaPrompt]) }}"
           class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full text-xs font-semibold text-ink bg-surface hover:bg-surface-2 border border-line transition-all shadow-2xs active:scale-95 cursor-pointer">
            <span>Konsultasi Solusi</span>
            <span class="material-symbols-outlined text-[15px] text-muted">arrow_forward</span>
        </a>
    </div>
</div>
