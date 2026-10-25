@php
    // Kartu peringatan dini (indikasi berbasis pola data, bukan diagnosis)
    $danger = $alert->level === 'waspada';
@endphp
<div class="rounded-2xl p-4 {{ $danger ? 'bg-danger-soft/50' : 'bg-amber-soft/50' }}">
    <div class="flex items-start gap-3">
        <span class="w-9 h-9 rounded-full {{ $danger ? 'bg-danger/15 text-danger' : 'bg-amber/30 text-amber-ink' }} grid place-items-center shrink-0">
            <span class="material-symbols-outlined icon-fill">warning</span>
        </span>
        <div class="flex-1 min-w-0">
            <div class="flex items-center justify-between gap-2 mb-1">
                <span class="text-h-sm {{ $danger ? 'text-danger-ink' : 'text-amber-ink' }}">Peringatan dini</span>
                <span class="chip text-white {{ $danger ? 'bg-danger' : 'bg-amber !text-ink' }}">{{ $danger ? 'Waspada' : 'Perhatian' }}</span>
            </div>
            <p class="leading-snug">{{ $alert->pesan }}</p>
            <p class="text-xs text-muted mt-1">{{ optional($alert->tanggal_data)->translatedFormat('d M Y') }}</p>
        </div>
    </div>
    <p class="mt-3 pt-2 border-t border-black/5 text-xs italic text-muted flex gap-1.5">
        <span class="material-symbols-outlined text-[15px] shrink-0">info</span>{{ config('ransumku.disclaimer_ai') }}
    </p>
</div>
