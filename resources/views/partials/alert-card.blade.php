@php
    // Kartu peringatan dini (indikasi berbasis pola data, bukan diagnosis)
    $danger = $alert->level === 'waspada';
    $tanyaPrompt = 'Tolong berikan analisis mendalam dan rekomendasi solusi cepat untuk kendala di kandang saya: ' . $alert->pesan;
    $isList = $is_list ?? false;
@endphp

<div class="{{ $isList ? 'p-4 md:py-4 md:px-5 hover:bg-surface/30 transition-colors' : 'rounded-lg md:rounded-xl border border-line/80 bg-white p-4 sm:p-5 hover:border-line hover:shadow-xs transition-all shadow-2xs' }} flex flex-col md:flex-row md:items-center justify-between gap-4">
    <div class="space-y-2 min-w-0 flex-1">
        {{-- Status Badge & Tanggal --}}
        <div class="flex items-center gap-2 text-xs">
            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold shrink-0 whitespace-nowrap {{ $danger ? 'bg-rose-50 text-rose-700 border border-rose-200/80' : 'bg-amber-50 text-amber-800 border border-amber-200/80' }}">
                <span class="material-symbols-outlined text-[13px]">{{ $danger ? 'error' : 'warning' }}</span>
                <span>{{ $danger ? 'Waspada' : 'Perhatian' }}</span>
            </span>
            <span class="text-muted/40 shrink-0">•</span>
            <span class="text-muted font-medium text-[11px] shrink-0 whitespace-nowrap">
                {{ optional($alert->tanggal_data)->translatedFormat('d F Y') }}
            </span>
        </div>

        <p class="text-xs sm:text-sm font-semibold text-ink leading-relaxed">
            {{ $alert->pesan }}
        </p>
    </div>

    {{-- Tombol Aksi Konsultasi Solusi --}}
    <div class="flex items-center shrink-0 self-end md:self-center">
        <a href="{{ route('asisten.index', ['tab' => 'tanya', 'tanya' => $tanyaPrompt]) }}"
           class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full text-xs font-bold bg-surface hover:bg-white text-ink hover:text-brand border border-line hover:border-brand/40 transition active:scale-95 shadow-2xs cursor-pointer group">
            <span>Konsultasi Solusi</span>
            <span class="material-symbols-outlined text-[15px] text-brand group-hover:translate-x-0.5 transition-transform">arrow_forward</span>
        </a>
    </div>
</div>
