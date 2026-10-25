@extends('layouts.app')
@section('judul', 'Asisten Cerdas')
@section('isi')
@use('App\Support\Fmt')

<div class="flex items-center justify-between">
    <div>
        <h1 class="text-h-xl flex items-center gap-2">
            <span>Asisten Cerdas</span>
            <span class="material-symbols-outlined icon-fill text-brand text-[24px]">auto_awesome</span>
        </h1>
        <p class="text-muted text-sm">Deteksi dini anomali kandang & konsultasi pakan</p>
    </div>
</div>

@php
    $tabAktif = request()->query('tab', 'peringatan');
@endphp

<div data-tabs class="space-y-4">
    <div class="grid grid-cols-2 gap-1 p-1 rounded-full bg-surface-2 text-center text-sm font-bold">
        <button type="button" data-tab="peringatan" class="py-2.5 rounded-full transition {{ $tabAktif === 'peringatan' ? 'bg-brand text-white' : 'text-muted' }}">
            Peringatan Dini ({{ $alerts->where('dibaca', false)->count() }})
        </button>
        <button type="button" data-tab="tanya" class="py-2.5 rounded-full transition {{ $tabAktif === 'tanya' ? 'bg-brand text-white' : 'text-muted' }}">
            Tanya Asisten AI
        </button>
    </div>

    {{-- Tab 1: Peringatan Dini --}}
    <section data-panel="peringatan" class="{{ $tabAktif === 'peringatan' ? '' : 'hidden' }} space-y-3">
        @if ($alerts->where('dibaca', false)->count() > 0)
            <div class="flex justify-end">
                <form method="POST" action="{{ route('asisten.baca') }}">
                    @csrf
                    <button class="text-xs text-brand font-bold hover:underline flex items-center gap-1">
                        <span class="material-symbols-outlined text-[16px]">done_all</span>
                        Tandai semua sudah dibaca
                    </button>
                </form>
            </div>
        @endif

        @forelse ($alerts as $alert)
            @include('partials.alert-card', ['alert' => $alert])
        @empty
            <div class="card text-center py-10 space-y-2">
                <span class="material-symbols-outlined text-[48px] text-brand icon-fill">verified</span>
                <h2 class="text-h-sm text-ink">Semua Pola Terlihat Normal</h2>
                <p class="text-xs text-muted max-w-sm mx-auto">
                    Tidak ditemukan anomali pada konsumsi pakan, produksi telur, maupun mortalitas ayam berdasarkan data harian yang tercatat.
                </p>
            </div>
        @endforelse

        <div class="card space-y-2 bg-surface">
            <h3 class="font-bold text-xs uppercase text-muted tracking-wider">Aturan Deteksi Anomali</h3>
            <ul class="text-xs text-muted space-y-1 list-disc ml-4">
                <li>Pakan naik &ge; 10% selama 3 hari berturut-turut sementara produksi telur turun.</li>
                <li>Lonjakan kematian harian &ge; 3 ekor melebihi variasi rata-rata bergerak.</li>
                <li>Biaya pakan per kg telur melonjak signifikan (z-score &ge; 2).</li>
            </ul>
        </div>
    </section>

    {{-- Tab 2: Tanya Jawab AI --}}
    <section data-panel="tanya" class="{{ $tabAktif === 'tanya' ? '' : 'hidden' }} space-y-3">
        {{-- Status API Key --}}
        @if (!$aiAktif)
            <div class="notice bg-amber-soft/50 text-amber-ink">
                <span class="material-symbols-outlined icon-fill">info</span>
                <div>
                    <strong>Kunci Gemini API belum dipasang di .env.</strong>
                    <p class="text-xs mt-0.5">Asisten berjalan dalam mode jawaban bawaan (fallback otomatis tanpa crash).</p>
                </div>
            </div>
        @endif

        {{-- Chat Box Window --}}
        <div class="card !p-3 space-y-3 min-h-[300px] max-h-[420px] overflow-y-auto flex flex-col">
            {{-- Pesan sambutan awal --}}
            <div class="flex gap-2 items-start max-w-[85%]">
                <div class="w-8 h-8 rounded-full bg-brand text-white grid place-items-center shrink-0">
                    <span class="material-symbols-outlined text-[16px]">smart_toy</span>
                </div>
                <div class="p-3 rounded-2xl rounded-tl-sm bg-surface-2 text-sm text-ink space-y-1">
                    <p>Halo peternak! Saya asisten cerdas RansumKu. Anda bisa bertanya seputar bahan pakan pengganti, tips pemeliharaan pakan, atau meminta ulasan ransum.</p>
                    <p class="text-[11px] text-muted italic">Catatan: Saya tidak mendiagnosis penyakit dan perhitungan nutrisi dikerjakan oleh program.</p>
                </div>
            </div>

            @foreach ($chats as $c)
                @if ($c->role === 'user')
                    <div class="flex gap-2 items-start justify-end max-w-[85%] self-end">
                        <div class="p-3 rounded-2xl rounded-tr-sm bg-brand text-white text-sm">
                            {{ $c->isi }}
                        </div>
                    </div>
                @else
                    <div class="flex gap-2 items-start max-w-[85%]">
                        <div class="w-8 h-8 rounded-full bg-brand text-white grid place-items-center shrink-0">
                            <span class="material-symbols-outlined text-[16px]">smart_toy</span>
                        </div>
                        <div class="p-3 rounded-2xl rounded-tl-sm bg-surface-2 text-sm text-ink whitespace-pre-line">
                            {{ $c->isi }}
                        </div>
                    </div>
                @endif
            @endforeach
            <div id="chat-bawah"></div>
        </div>

        {{-- Suggestion Chips --}}
        <div class="flex flex-wrap gap-1.5 pt-1">
            <span class="text-xs text-muted self-center mr-1">Pertanyaan cepat:</span>
            <button type="button" onclick="isiPesan('Apa bahan pakan pengganti jagung yang murah?')" class="chip bg-surface hover:bg-surface-2 border border-line text-ink">
                Pengganti jagung?
            </button>
            <button type="button" onclick="isiPesan('Bagaimana cara menjaga kualitas pakan saat musim hujan?')" class="chip bg-surface hover:bg-surface-2 border border-line text-ink">
                Pakan musim hujan?
            </button>
            <button type="button" onclick="isiPesan('Kenapa ayam makan banyak tapi telur berkurang?')" class="chip bg-surface hover:bg-surface-2 border border-line text-ink">
                Pakan naik telur turun?
            </button>
        </div>

        {{-- Input Form --}}
        <form method="POST" action="{{ route('asisten.chat') }}" class="flex gap-2 pt-1">
            @csrf
            <input id="input-pesan" name="pesan" type="text" class="field !min-h-12" placeholder="Ketik pertanyaan untuk asisten..." required autocomplete="off">
            <button class="btn-primary !px-5 shrink-0" aria-label="Kirim">
                <span class="material-symbols-outlined">send</span>
            </button>
        </form>

        <p class="text-[11px] text-muted text-center">
            {{ config('ransumku.disclaimer_ai') }}
        </p>
    </section>
</div>

<script>
function isiPesan(teks) {
    const input = document.getElementById('input-pesan');
    if (input) {
        input.value = teks;
        input.focus();
    }
}
</script>
@endsection
