@extends('layouts.app')
@section('judul', 'Asisten Cerdas')
@section('isi')
@use('App\Support\Fmt')

<div class="flex items-center justify-between mb-4">
    <div>
        <h1 class="text-h-xl flex items-center gap-2">
            <span>Asisten Cerdas</span>
            <span class="material-symbols-outlined icon-fill text-brand text-[24px]">auto_awesome</span>
        </h1>
        <p class="text-muted text-sm">Deteksi dini anomali kandang & konsultasi pakan berbasis AI</p>
    </div>
</div>

@php
    $tabAktif = request()->query('tab', 'tanya');
    $farm = auth()->user()->farm ?? null;
    $chatSessions = $chatSessions ?? ($farm ? $farm->chatSessions()->withCount('messages')->orderByDesc('pinned')->orderByDesc('updated_at')->get() : collect());
    $sessionParam = request()->query('session');
    if ($sessionParam === 'baru') {
        $activeSession = null;
        $chats = collect();
    } elseif ($sessionParam) {
        $activeSession = $chatSessions->firstWhere('id', (int) $sessionParam);
        $chats = $activeSession ? $activeSession->messages()->orderBy('id')->get() : collect();
    } else {
        $activeSession = $chatSessions->first();
        $chats = $activeSession ? $activeSession->messages()->orderBy('id')->get() : collect();
    }
@endphp

<div data-tabs class="space-y-4">
    <div data-tab-nav class="tab-segmented-track relative grid grid-cols-2 gap-1 p-1.5 rounded-full text-center text-xs sm:text-sm font-bold select-none w-full">
        <div data-tab-slider class="tab-slider" aria-hidden="true"></div>
        <button type="button" data-tab="tanya" class="relative z-10 py-2.5 px-4 rounded-full transition-colors duration-200 cursor-pointer outline-none focus:outline-none focus:ring-0 select-none {{ $tabAktif === 'tanya' ? 'active-tab bg-white shadow-xs text-ink font-bold' : 'text-muted hover:text-ink font-semibold' }}">
            Tanya Asisten AI
        </button>
        <button type="button" data-tab="peringatan" class="relative z-10 py-2.5 px-4 rounded-full transition-colors duration-200 cursor-pointer outline-none focus:outline-none focus:ring-0 select-none {{ $tabAktif === 'peringatan' ? 'active-tab bg-white shadow-xs text-ink font-bold' : 'text-muted hover:text-ink font-semibold' }} flex items-center justify-center gap-1.5">
            <span>Peringatan Dini</span>
            @php $unreadCountTab = $alerts->where('dibaca', false)->count(); @endphp
            @if ($unreadCountTab > 0)
                <span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-rose-600 text-white shadow-2xs">
                    {{ $unreadCountTab > 9 ? '9+' : $unreadCountTab }}
                </span>
            @elseif ($alerts->count() > 0)
                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-surface-2 text-muted">
                    {{ $alerts->count() }}
                </span>
            @endif
        </button>
    </div>

    {{-- Tab 1: Tanya Jawab AI (ChatGPT Style & Real-Time) --}}
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

        {{-- ChatGPT Interface Window (Light Mode) --}}
        <div class="card !p-0 overflow-hidden border border-line shadow-sm flex flex-col md:flex-row min-h-[660px] rounded-2xl bg-white text-ink chatgpt-layout-container">
            
            {{-- Left Sidebar: ChatGPT Navigation (Pinned & Recents & Akun) --}}
            <aside class="chatgpt-sidebar w-full md:w-72 lg:w-80 shrink-0 bg-[#f7faf8] border-b md:border-b-0 md:border-r border-line flex flex-col justify-between select-none">
                <div class="p-3.5 space-y-3.5 flex-1 flex flex-col min-h-0">
                    {{-- Tombol + Obrolan Baru --}}
                    <button type="button" onclick="mulaiObrolanBaru()" class="w-full py-2.5 px-3.5 rounded-xl bg-white hover:bg-surface border border-line text-ink text-xs font-semibold flex items-center justify-between transition-all duration-150 shadow-xs cursor-pointer group">
                        <div class="flex items-center gap-2">
                            <span class="material-symbols-outlined text-[18px] text-brand group-hover:rotate-90 transition-transform">add</span>
                            <span>Obrolan Baru</span>
                        </div>
                        <span class="text-[10px] text-muted border border-line px-1.5 py-0.5 rounded font-mono bg-surface">Baru</span>
                    </button>

                    @php
                        $pinnedSessions = $chatSessions->where('pinned', true);
                        $recentSessions = $chatSessions->where('pinned', false);
                    @endphp

                    <div class="space-y-4 overflow-y-auto max-h-[460px] pr-1 custom-scrollbar" id="sidebar-sessions">
                        {{-- Bagian Pinned (Disematkan) --}}
                        <div id="pinned-container" class="space-y-1 {{ $pinnedSessions->isEmpty() ? 'hidden' : '' }}">
                            <div class="flex items-center justify-between px-2.5 py-1 text-[11px] font-bold uppercase tracking-wider text-muted">
                                <div class="flex items-center gap-1.5">
                                    <span class="material-symbols-outlined text-[14px]">push_pin</span>
                                    <span>Pinned</span>
                                </div>
                                <span class="material-symbols-outlined text-[16px] text-muted">expand_more</span>
                            </div>
                            <div id="pinned-list" class="space-y-0.5">
                                @foreach ($pinnedSessions as $s)
                                    @php $isActive = ($activeSession && $activeSession->id === $s->id); @endphp
                                    <div id="session-item-{{ $s->id }}" class="session-item group relative flex items-center justify-between rounded-xl px-2.5 py-2 text-xs transition-colors duration-150 {{ $isActive ? 'bg-brand-soft/80 text-brand-dark font-bold border border-brand/20 shadow-xs' : 'text-ink/80 hover:bg-surface hover:text-ink' }}">
                                        <button type="button" onclick="pilihSession({{ $s->id }})" class="flex items-center gap-2 flex-1 min-w-0 pr-2 text-left cursor-pointer">
                                            <span class="material-symbols-outlined text-[15px] {{ $isActive ? 'text-brand' : 'text-muted' }}">chat_bubble</span>
                                            <span class="truncate session-title">{{ $s->judul }}</span>
                                        </button>
                                        <div class="flex items-center gap-1 shrink-0">
                                            <span class="material-symbols-outlined text-[14px] text-muted group-hover:hidden rotate-45">push_pin</span>
                                            <div class="hidden group-hover:flex items-center gap-0.5">
                                                <button type="button" onclick="togglePinSession({{ $s->id }}, event)" class="p-1 hover:text-brand text-muted cursor-pointer rounded" title="Lepas Sematan">
                                                    <span class="material-symbols-outlined text-[14px]">keep_off</span>
                                                </button>
                                                <button type="button" onclick="hapusSession({{ $s->id }}, event)" class="p-1 hover:text-danger text-muted cursor-pointer rounded" title="Hapus Sesi">
                                                    <span class="material-symbols-outlined text-[14px]">delete</span>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        {{-- Bagian Recents (Terkini) --}}
                        <div class="space-y-1">
                            <div class="px-2.5 py-1 text-[11px] font-bold uppercase tracking-wider text-muted flex items-center gap-1.5">
                                <span class="material-symbols-outlined text-[14px]">schedule</span>
                                <span>Recents</span>
                            </div>

                            <div id="recents-list" class="space-y-0.5">
                                @forelse ($recentSessions as $s)
                                    @php $isActive = ($activeSession && $activeSession->id === $s->id); @endphp
                                    <div id="session-item-{{ $s->id }}" class="session-item group relative flex items-center justify-between rounded-xl px-2.5 py-2 text-xs transition-colors duration-150 {{ $isActive ? 'bg-brand-soft/80 text-brand-dark font-bold border border-brand/20 shadow-xs' : 'text-ink/80 hover:bg-surface hover:text-ink' }}">
                                        <button type="button" onclick="pilihSession({{ $s->id }})" class="flex items-center gap-2 flex-1 min-w-0 pr-2 text-left cursor-pointer">
                                            <span class="material-symbols-outlined text-[15px] {{ $isActive ? 'text-brand' : 'text-muted' }}">chat_bubble_outline</span>
                                            <span class="truncate session-title">{{ $s->judul }}</span>
                                        </button>
                                        <div class="flex items-center gap-0.5 shrink-0 opacity-0 group-hover:opacity-100 transition-opacity">
                                            <button type="button" onclick="togglePinSession({{ $s->id }}, event)" class="p-1 hover:text-brand text-muted cursor-pointer rounded" title="Sematkan">
                                                <span class="material-symbols-outlined text-[14px]">push_pin</span>
                                            </button>
                                            <button type="button" onclick="hapusSession({{ $s->id }}, event)" class="p-1 hover:text-danger text-muted cursor-pointer rounded" title="Hapus Sesi">
                                                <span class="material-symbols-outlined text-[14px]">delete</span>
                                            </button>
                                        </div>
                                    </div>
                                @empty
                                    @if ($pinnedSessions->isEmpty())
                                        <div id="empty-history-notice" class="p-4 text-center text-xs text-muted italic">
                                            Belum ada riwayat percakapan. Mulai pertanyaan pertama Anda!
                                        </div>
                                    @endif
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Footer Sidebar: Profil Pengguna (Sesuai Screenshot ChatGPT) --}}
                <div class="p-3 border-t border-line bg-surface/60 flex items-center justify-between text-xs shrink-0">
                    <div class="flex items-center gap-2.5 truncate min-w-0 pr-2">
                        <div class="w-8 h-8 rounded-full bg-brand/15 text-brand font-bold text-xs shrink-0 shadow-xs flex items-center justify-center">
                            {{ strtoupper(substr(auth()->user()->name ?? 'A', 0, 1)) }}
                        </div>
                        <div class="truncate leading-tight">
                            <span class="block truncate font-semibold text-ink text-xs">{{ auth()->user()->name ?? 'Andre Abdilillah Ahwien' }}</span>
                            <span class="block text-[10px] text-muted">{{ $farm->nama ?? 'Kandang Maju Jaya' }}</span>
                        </div>
                    </div>
                    <button type="button" onclick="hapusSemua()" class="p-1.5 text-muted hover:text-danger hover:bg-white rounded-lg cursor-pointer transition-colors shadow-xs" title="Bersihkan Semua Obrolan">
                        <span class="material-symbols-outlined text-[18px]">delete_sweep</span>
                    </button>
                </div>
            </aside>

            {{-- Right Main Chat Screen --}}
            <main class="chatgpt-main flex-1 flex flex-col justify-between bg-white p-3 md:p-5 min-w-0">
                
                {{-- Top Chat Header --}}
                <div class="flex items-center justify-between pb-3 border-b border-line mb-2 shrink-0">
                    <div class="flex items-center gap-2.5 min-w-0">
                        <div class="w-8 h-8 rounded-lg bg-brand/10 border border-brand/20 text-brand grid place-items-center shrink-0">
                            <span class="material-symbols-outlined text-[18px]">smart_toy</span>
                        </div>
                        <div class="truncate">
                            <h3 id="current-session-title" class="font-bold text-sm text-ink truncate">
                                {{ $activeSession ? $activeSession->judul : 'Percakapan Baru' }}
                            </h3>
                            <p id="current-session-subtitle" class="text-[11px] text-muted">
                                {{ $activeSession ? 'Sesi ID #'.$activeSession->id.' • '.$chats->count().' pesan' : 'Konsultasi Nutrisi & Manajemen Unggas' }}
                            </p>
                        </div>
                    </div>
                    
                    <div class="flex items-center gap-2 shrink-0">
                        <span class="hidden sm:inline-flex items-center gap-1.5 text-[11px] font-mono text-brand bg-brand-soft/60 border border-brand/20 px-2.5 py-0.5 rounded-full font-bold">
                            <span class="w-1.5 h-1.5 rounded-full bg-brand animate-pulse"></span>
                            <span>Gemini AI</span>
                        </span>
                        <button type="button" onclick="mulaiObrolanBaru()" class="py-1.5 px-3 rounded-lg bg-surface hover:bg-surface-2 border border-line text-xs font-semibold text-ink flex items-center gap-1 cursor-pointer transition-colors shadow-xs">
                            <span class="material-symbols-outlined text-[15px] text-brand">add</span>
                            <span>Baru</span>
                        </button>
                    </div>
                </div>

                {{-- Chat Box Window --}}
                <div id="chat-box" class="flex-1 min-h-[380px] max-h-[490px] overflow-y-auto space-y-4 px-2 py-2 flex flex-col custom-scrollbar">
                    
                    {{-- Welcome Screen (when session has no messages) --}}
                    <div id="welcome-card" class="{{ $chats->isEmpty() ? '' : 'hidden' }} my-auto py-6 text-center space-y-4 max-w-lg mx-auto">
                        <div class="w-14 h-14 rounded-2xl bg-brand/10 border border-brand/20 text-brand grid place-items-center mx-auto shadow-xs">
                            <span class="material-symbols-outlined text-[32px] icon-fill">auto_awesome</span>
                        </div>
                        <div>
                            <h3 class="font-bold text-lg text-ink">Halo, Peternak Cerdas!</h3>
                            <p class="text-xs text-muted mt-1 max-w-md mx-auto leading-relaxed">
                                Saya Asisten Cerdas RansumKu (didukung Google Gemini AI). Saya siap menganalisis data performa kandang Anda, memberikan saran pakan alternatif, dan solusi masalah ayam petelur secara real-time.
                            </p>
                        </div>

                        {{-- Suggestion Prompts --}}
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-left pt-2">
                            <button type="button" onclick="isiDanKirim('apakah kamu bisa cek konsumsi pakan naik kenapa kok telurnya turun?')" class="p-3 rounded-xl border border-line bg-white hover:bg-surface/70 hover:border-brand/40 transition-all text-xs text-ink space-y-1 text-left cursor-pointer group shadow-xs">
                                <div class="font-bold text-brand flex items-center gap-1">
                                    <span class="material-symbols-outlined text-[15px] text-amber-ink">warning</span>
                                    <span>Pakan Naik, Telur Turun?</span>
                                </div>
                                <p class="text-[11px] text-muted group-hover:text-ink line-clamp-2">Analisis data 7 hari terakhir mengapa pakan naik namun produksi menurun.</p>
                            </button>

                            <button type="button" onclick="isiDanKirim('Bagaimana analisis performa kandang saya berdasarkan data 7 hari terakhir?')" class="p-3 rounded-xl border border-line bg-white hover:bg-surface/70 hover:border-brand/40 transition-all text-xs text-ink space-y-1 text-left cursor-pointer group shadow-xs">
                                <div class="font-bold text-brand flex items-center gap-1">
                                    <span class="material-symbols-outlined text-[15px]">query_stats</span>
                                    <span>Evaluasi Performa Kandang</span>
                                </div>
                                <p class="text-[11px] text-muted group-hover:text-ink line-clamp-2">Cek Hen-Day %, konsumsi gram/ekor/hari, dan angka mortalitas.</p>
                            </button>

                            <button type="button" onclick="isiDanKirim('Apa bahan pakan pengganti jagung yang murah dan aman?')" class="p-3 rounded-xl border border-line bg-white hover:bg-surface/70 hover:border-brand/40 transition-all text-xs text-ink space-y-1 text-left cursor-pointer group shadow-xs">
                                <div class="font-bold text-brand flex items-center gap-1">
                                    <span class="material-symbols-outlined text-[15px]">grass</span>
                                    <span>Bahan Pengganti Jagung</span>
                                </div>
                                <p class="text-[11px] text-muted group-hover:text-ink line-clamp-2">Sorgum, menir, atau dedak beserta takaran aman dalam ransum.</p>
                            </button>

                            <button type="button" onclick="isiDanKirim('Bagaimana cara menjaga kualitas pakan saat musim hujan agar tidak berjamur?')" class="p-3 rounded-xl border border-line bg-white hover:bg-surface/70 hover:border-brand/40 transition-all text-xs text-ink space-y-1 text-left cursor-pointer group shadow-xs">
                                <div class="font-bold text-brand flex items-center gap-1">
                                    <span class="material-symbols-outlined text-[15px]">rainy</span>
                                    <span>Tips Pakan Musim Hujan</span>
                                </div>
                                <p class="text-[11px] text-muted group-hover:text-ink line-clamp-2">Mencegah kelembapan, kutu, mikotoksin, dan penurunan nafsu makan.</p>
                            </button>
                        </div>
                    </div>

                    {{-- Messages Stream Container --}}
                    <div id="messages-container" class="space-y-4 flex flex-col">
                        @foreach ($chats as $c)
                            @if ($c->role === 'user')
                                <div class="flex gap-2 items-start justify-end max-w-[85%] self-end">
                                    <div class="p-3 rounded-2xl rounded-tr-xs bg-brand text-white text-xs sm:text-sm leading-relaxed shadow-xs">
                                        {{ $c->isi }}
                                    </div>
                                </div>
                            @else
                                <div class="flex gap-2.5 items-start max-w-[92%] sm:max-w-[88%]">
                                    <div class="w-8 h-8 rounded-full bg-brand/10 border border-brand/20 text-brand grid place-items-center shrink-0 mt-0.5">
                                        <span class="material-symbols-outlined text-[16px]">smart_toy</span>
                                    </div>
                                    <div class="p-3.5 rounded-2xl rounded-tl-xs bg-[#f4f7f5] border border-[#e2ece6] text-xs sm:text-sm text-ink leading-relaxed shadow-xs ai-content-box" data-raw="{{ $c->isi }}">
                                        {!! App\Support\Fmt::markdownAi($c->isi) !!}
                                    </div>
                                </div>
                            @endif
                        @endforeach
                    </div>

                    <div id="chat-bawah"></div>
                </div>

                {{-- Real-Time Input Area --}}
                <div class="pt-3 border-t border-line space-y-2 shrink-0">
                    <form id="form-chat" action="javascript:void(0);" onsubmit="return handleFormSubmit(event);" class="flex gap-2">
                        @csrf
                        <div class="relative flex-1">
                            <input id="input-pesan" name="pesan" type="text" class="w-full bg-[#f8faf9] hover:bg-white focus:bg-white text-ink placeholder:text-muted border border-line focus:border-brand focus:ring-2 focus:ring-brand/20 rounded-xl px-4 py-3 text-xs sm:text-sm outline-none transition-all shadow-xs" placeholder="Ketik pertanyaan atau keluhan tentang kandang Anda..." required autocomplete="off">
                        </div>
                        <button id="btn-kirim" type="button" onclick="handleFormSubmit(event)" class="bg-brand hover:bg-brand-dark text-white font-bold px-4 sm:px-5 rounded-xl text-xs sm:text-sm flex items-center gap-1.5 cursor-pointer transition-all shadow-sm shrink-0 disabled:opacity-50 disabled:cursor-not-allowed" aria-label="Kirim">
                            <span>Kirim</span>
                            <span class="material-symbols-outlined text-[17px]">send</span>
                        </button>
                    </form>

                    <p class="text-[11px] text-muted text-center">
                        {{ config('ransumku.disclaimer_ai') }}
                    </p>
                </div>
            </main>
        </div>
    </section>

    {{-- Tab 2: Peringatan Dini Kandang --}}
    <section data-panel="peringatan" class="{{ $tabAktif === 'peringatan' ? '' : 'hidden' }} space-y-3">
        @if ($alerts->where('dibaca', false)->count() > 0)
            <div class="flex justify-end">
                <form method="POST" action="{{ url('/asisten/baca') }}">
                    @csrf
                    <button class="text-xs text-brand font-bold hover:underline flex items-center gap-1 cursor-pointer">
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
</div>

<script>
let currentSessionId = {{ $activeSession ? $activeSession->id : 'null' }};
// Selalu gunakan path relatif agar tidak pernah ada masalah CORS lintas localhost dan 127.0.0.1
const endpointChat = '/asisten/chat';

function getCsrfToken() {
    return document.querySelector('input[name="_token"]')?.value || '{{ csrf_token() }}';
}

function escapeHtml(text) {
    if (!text) return '';
    return text.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

/**
 * Format markdown teks AI agar memiliki jeda 1 baris saja (mb-2) dan merender bold/list secara rapi di Light Mode.
 */
function formatAiMarkdown(text) {
    if (!text) return '';
    let s = escapeHtml(text.trim());

    // 1. Ubah **teks** menjadi <strong>
    s = s.replace(/\*\*(.*?)\*\*/gs, '<strong class="font-bold text-ink">$1</strong>');

    // 2. Normalisasi 3 atau lebih newline menjadi 2 newline
    s = s.replace(/\n{3,}/g, '\n\n');

    // 3. Pisahkan paragraf berdasarkan 2 atau lebih newline
    const paragraphs = s.split(/\n\s*\n/);
    const htmlBlocks = [];

    for (let p of paragraphs) {
        p = p.trim();
        if (!p) continue;

        const lines = p.split('\n');
        let paraHtml = '';

        for (let line of lines) {
            line = line.trim();
            if (!line) continue;

            // Header ###
            if (line.startsWith('### ')) {
                paraHtml += `<div class="font-bold text-ink text-sm mt-2 mb-0.5">${line.substring(4)}</div>`;
            } else if (line.startsWith('## ')) {
                paraHtml += `<div class="font-bold text-ink text-base mt-2.5 mb-0.5">${line.substring(3)}</div>`;
            }
            // Poin bullet (* atau -)
            else if (/^[\*\-]\s+(.*)$/.test(line)) {
                const match = line.match(/^[\*\-]\s+(.*)$/);
                const item = match[1].replace(/(?<!\w)\*(.*?)\*(?!\w)/g, '<em class="italic text-muted">$1</em>');
                paraHtml += `<div class="flex items-start gap-2 my-0.5 text-ink"><span class="text-brand font-bold text-xs mt-0.5">•</span><span>${item}</span></div>`;
            }
            // Poin berangka (1. atau 2.)
            else if (/^(\d+)\.\s+(.*)$/.test(line)) {
                const match = line.match(/^(\d+)\.\s+(.*)$/);
                const item = match[2].replace(/(?<!\w)\*(.*?)\*(?!\w)/g, '<em class="italic text-muted">$1</em>');
                paraHtml += `<div class="flex items-start gap-2 my-0.5 text-ink"><span class="text-brand font-bold text-xs mt-0.5">${match[1]}.</span><span>${item}</span></div>`;
            }
            // Pemisah horizontal ---
            else if (line === '---') {
                paraHtml += '<hr class="border-line my-2">';
            }
            // Teks biasa (break 1 baris standar)
            else {
                line = line.replace(/(?<!\w)\*(.*?)\*(?!\w)/g, '<em class="italic text-muted">$1</em>');
                paraHtml += (paraHtml !== '' && !paraHtml.endsWith('</div>') && !paraHtml.endsWith('<hr class="border-line my-2">') ? '<br>' : '') + line;
            }
        }

        htmlBlocks.push(`<div class="mb-2 last:mb-0 leading-relaxed text-ink">${paraHtml}</div>`);
    }

    return htmlBlocks.join('');
}

function scrollToBottom() {
    setTimeout(() => {
        const chatBox = document.getElementById('chat-box');
        if (chatBox) {
            chatBox.scrollTop = chatBox.scrollHeight;
        }
    }, 30);
}

function isiDanKirim(teks) {
    const input = document.getElementById('input-pesan');
    if (input) {
        input.value = teks;
        handleFormSubmit();
    }
}

function mulaiObrolanBaru() {
    currentSessionId = null;
    document.getElementById('messages-container').innerHTML = '';
    document.getElementById('welcome-card').classList.remove('hidden');
    document.getElementById('current-session-title').textContent = 'Percakapan Baru';
    document.getElementById('current-session-subtitle').textContent = 'Konsultasi Nutrisi & Manajemen Unggas';
    
    // Hapus highlight aktif di sidebar
    document.querySelectorAll('.session-item').forEach(el => {
        el.classList.remove('bg-brand-soft/80', 'text-brand-dark', 'font-bold', 'border', 'border-brand/20', 'shadow-xs');
        el.classList.add('text-ink/80');
    });

    window.history.pushState(null, '', '{{ url('/asisten?tab=tanya&session=baru') }}');
    const input = document.getElementById('input-pesan');
    if (input) input.focus();
}

async function pilihSession(id) {
    currentSessionId = id;
    
    // Highlight di sidebar
    document.querySelectorAll('.session-item').forEach(el => {
        el.classList.remove('bg-brand-soft/80', 'text-brand-dark', 'font-bold', 'border', 'border-brand/20', 'shadow-xs');
        el.classList.add('text-ink/80');
    });
    const elActive = document.getElementById(`session-item-${id}`);
    if (elActive) {
        elActive.classList.add('bg-brand-soft/80', 'text-brand-dark', 'font-bold', 'border', 'border-brand/20', 'shadow-xs');
        elActive.classList.remove('text-ink/80');
    }

    const welcomeCard = document.getElementById('welcome-card');
    const container = document.getElementById('messages-container');

    if (welcomeCard) welcomeCard.classList.add('hidden');
    container.innerHTML = `
        <div class="flex gap-2.5 items-start max-w-[85%] py-2 text-xs text-muted">
            <span class="material-symbols-outlined text-[18px] animate-spin text-brand">sync</span>
            <span>Memuat pesan percakapan...</span>
        </div>
    `;

    try {
        const res = await fetch(endpointChat, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': getCsrfToken()
            },
            body: JSON.stringify({ aksi: 'ambil_session', session_id: id })
        });
        const data = await res.json();
        container.innerHTML = '';

        if (data.status === 'success') {
            document.getElementById('current-session-title').textContent = data.session_title;
            document.getElementById('current-session-subtitle').textContent = `Sesi ID #${id} • ${data.messages.length} pesan`;
            window.history.pushState(null, '', `{{ url('/asisten?tab=tanya') }}&session=${id}`);

            data.messages.forEach(m => {
                if (m.role === 'user') {
                    const ub = document.createElement('div');
                    ub.className = 'flex gap-2 items-start justify-end max-w-[85%] self-end';
                    ub.innerHTML = `<div class="p-3 rounded-2xl rounded-tr-xs bg-brand text-white text-xs sm:text-sm leading-relaxed shadow-xs">${escapeHtml(m.isi)}</div>`;
                    container.appendChild(ub);
                } else {
                    const ab = document.createElement('div');
                    ab.className = 'flex gap-2.5 items-start max-w-[92%] sm:max-w-[88%]';
                    ab.innerHTML = `
                        <div class="w-8 h-8 rounded-full bg-brand/10 border border-brand/20 text-brand grid place-items-center shrink-0 mt-0.5">
                            <span class="material-symbols-outlined text-[16px]">smart_toy</span>
                        </div>
                        <div class="p-3.5 rounded-2xl rounded-tl-xs bg-[#f4f7f5] border border-[#e2ece6] text-xs sm:text-sm text-ink leading-relaxed shadow-xs ai-content-box" data-raw="${escapeHtml(m.isi)}">
                            ${formatAiMarkdown(m.isi)}
                        </div>
                    `;
                    container.appendChild(ab);
                }
            });
            scrollToBottom();
        }
    } catch (e) {
        container.innerHTML = '';
        tampilkanErrorChat('Gagal memuat pesan sesi.');
    }
}

/**
 * Streaming perkata ala ChatGPT: Menghasilkan kata demi kata secara bertahap
 * dengan kursor berkedip di ujung teks dan autoscroll mulus.
 */
function streamWords(element, fullText, onDone) {
    const tokens = fullText.split(/(\s+)/);
    let current = '';
    let idx = 0;
    const speed = 20; // 20ms per token

    element.innerHTML = '<span class="inline-block w-1.5 h-3.5 bg-brand ml-1 animate-pulse align-middle"></span>';

    function tick() {
        if (idx < tokens.length) {
            current += tokens[idx];
            idx++;
            if (idx < tokens.length && /^\s+$/.test(tokens[idx])) {
                current += tokens[idx];
                idx++;
            }
            element.innerHTML = formatAiMarkdown(current) + '<span class="inline-block w-1.5 h-3.5 bg-brand ml-1 animate-pulse align-middle"></span>';
            scrollToBottom();
            setTimeout(tick, speed);
        } else {
            element.innerHTML = formatAiMarkdown(fullText);
            scrollToBottom();
            if (onDone) onDone();
        }
    }
    tick();
}

function handleFormSubmit(e) {
    if (e) {
        e.preventDefault();
        e.stopPropagation();
    }
    kirimPesanRealtime();
    return false;
}

async function kirimPesanRealtime() {
    const input = document.getElementById('input-pesan');
    const btn = document.getElementById('btn-kirim');
    const container = document.getElementById('messages-container');
    const welcomeCard = document.getElementById('welcome-card');

    const pesan = input.value.trim();
    if (!pesan) return;

    if (welcomeCard) welcomeCard.classList.add('hidden');

    // 1. Tampilkan gelembung pesan Pengguna langsung di chat feed
    const userBubble = document.createElement('div');
    userBubble.className = 'flex gap-2 items-start justify-end max-w-[85%] self-end';
    userBubble.innerHTML = `<div class="p-3 rounded-2xl rounded-tr-xs bg-brand text-white text-xs sm:text-sm leading-relaxed shadow-xs">${escapeHtml(pesan)}</div>`;
    container.appendChild(userBubble);

    input.value = '';
    input.disabled = true;
    btn.disabled = true;

    // 2. Buat gelembung Asisten AI LANGSUNG di bawahnya dengan indikator berdenyut ala ChatGPT
    const aiBubble = document.createElement('div');
    aiBubble.className = 'flex gap-2.5 items-start max-w-[92%] sm:max-w-[88%]';
    
    const contentBox = document.createElement('div');
    contentBox.className = 'p-3.5 rounded-2xl rounded-tl-xs bg-[#f4f7f5] border border-[#e2ece6] text-xs sm:text-sm text-ink leading-relaxed shadow-xs ai-content-box';
    contentBox.innerHTML = `
        <div class="flex items-center gap-2 text-brand text-xs py-0.5 font-medium">
            <span class="w-2 h-2 rounded-full bg-brand animate-ping"></span>
            <span class="animate-pulse text-muted">Asisten sedang menganalisis & menyusun jawaban...</span>
        </div>
    `;

    aiBubble.innerHTML = `
        <div class="w-8 h-8 rounded-full bg-brand/10 border border-brand/20 text-brand grid place-items-center shrink-0 mt-0.5">
            <span class="material-symbols-outlined text-[16px]">smart_toy</span>
        </div>
    `;
    aiBubble.appendChild(contentBox);
    container.appendChild(aiBubble);
    scrollToBottom();

    try {
        const response = await fetch(endpointChat, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': getCsrfToken()
            },
            body: JSON.stringify({
                pesan: pesan,
                session_id: currentSessionId
            })
        });

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}`);
        }

        const data = await response.json();

        if (data.status === 'success') {
            // Jalankan penulisan kata-demi-kata (Streaming / Typewriter Effect)
            streamWords(contentBox, data.jawaban);

            // Update sesi jika sesi baru
            if (data.is_new_session && data.session_id) {
                currentSessionId = data.session_id;
                document.getElementById('current-session-title').textContent = data.session_title;
                document.getElementById('current-session-subtitle').textContent = `Sesi ID #${data.session_id} • 2 pesan`;

                window.history.pushState(null, '', `{{ url('/asisten?tab=tanya') }}&session=${data.session_id}`);
                tambahSesiKeSidebar(data.session_id, data.session_title);
            }
        } else {
            contentBox.innerHTML = `
                <div class="flex items-center gap-2 text-xs text-danger-ink bg-danger-soft border border-danger/30 rounded-xl p-3">
                    <span class="material-symbols-outlined text-[16px]">error</span>
                    <span>${escapeHtml(data.message || 'Gagal memproses jawaban.')}</span>
                </div>
            `;
        }
    } catch (err) {
        contentBox.innerHTML = `
            <div class="flex items-center gap-2 text-xs text-danger-ink bg-danger-soft border border-danger/30 rounded-xl p-3">
                <span class="material-symbols-outlined text-[16px]">error</span>
                <span>Gagal menghubungi server AI (${escapeHtml(err.message || 'Koneksi terputus')}). Pastikan server web aktif.</span>
            </div>
        `;
    } finally {
        input.disabled = false;
        btn.disabled = false;
        input.focus();
        scrollToBottom();
    }
}

function tampilkanErrorChat(teks) {
    const container = document.getElementById('messages-container');
    const errEl = document.createElement('div');
    errEl.className = 'flex items-center gap-2 text-xs text-danger-ink bg-danger-soft border border-danger/30 rounded-xl p-3 max-w-[85%]';
    errEl.innerHTML = `<span class="material-symbols-outlined text-[16px]">error</span><span>${escapeHtml(teks)}</span>`;
    container.appendChild(errEl);
    scrollToBottom();
}

function tambahSesiKeSidebar(id, judul) {
    const recentsList = document.getElementById('recents-list');
    const emptyNotice = document.getElementById('empty-history-notice');
    if (emptyNotice) emptyNotice.remove();

    // Hapus highlight sebelumnya
    document.querySelectorAll('.session-item').forEach(el => {
        el.classList.remove('bg-brand-soft/80', 'text-brand-dark', 'font-bold', 'border', 'border-brand/20', 'shadow-xs');
        el.classList.add('text-ink/80');
    });

    const item = document.createElement('div');
    item.id = `session-item-${id}`;
    item.className = 'session-item group relative flex items-center justify-between rounded-xl px-2.5 py-2 text-xs transition-colors duration-150 bg-brand-soft/80 text-brand-dark font-bold border border-brand/20 shadow-xs';
    item.innerHTML = `
        <button type="button" onclick="pilihSession(${id})" class="flex items-center gap-2 flex-1 min-w-0 pr-2 text-left cursor-pointer">
            <span class="material-symbols-outlined text-[15px] text-brand">chat_bubble</span>
            <span class="truncate session-title text-brand-dark">${escapeHtml(judul)}</span>
        </button>
        <div class="flex items-center gap-0.5 shrink-0 opacity-0 group-hover:opacity-100 transition-opacity">
            <button type="button" onclick="togglePinSession(${id}, event)" class="p-1 hover:text-brand text-muted cursor-pointer rounded" title="Sematkan">
                <span class="material-symbols-outlined text-[14px]">push_pin</span>
            </button>
            <button type="button" onclick="hapusSession(${id}, event)" class="p-1 hover:text-danger text-muted cursor-pointer rounded" title="Hapus Sesi">
                <span class="material-symbols-outlined text-[14px]">delete</span>
            </button>
        </div>
    `;
    recentsList.insertBefore(item, recentsList.firstChild);
}

function hapusSession(id, e) {
    if (e) e.stopPropagation();
    bukaKonfirmasi({
        judul: 'Hapus Sesi Obrolan',
        pesan: 'Apakah Anda yakin ingin menghapus sesi obrolan ini?',
        tombolTeks: 'Ya, Hapus Sesi',
        onConfirm: async () => {
            try {
                const res = await fetch(endpointChat, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': getCsrfToken()
                    },
                    body: JSON.stringify({ aksi: 'hapus_session', session_id: id })
                });
                const d = await res.json();
                if (d.status === 'success') {
                    const el = document.getElementById(`session-item-${id}`);
                    if (el) el.remove();
                    if (currentSessionId === id) {
                        mulaiObrolanBaru();
                    }
                }
            } catch (err) {
                console.error(err);
            }
        }
    });
}

async function togglePinSession(id, e) {
    if (e) e.stopPropagation();

    try {
        const res = await fetch(endpointChat, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': getCsrfToken()
            },
            body: JSON.stringify({ aksi: 'pin_session', session_id: id })
        });
        const d = await res.json();
        if (d.status === 'success') {
            window.location.reload();
        }
    } catch (err) {
        window.showToast('Gagal mengubah sematan sesi.', 'error', 'Gagal Memperbarui');
    }
}

function hapusSemua() {
    bukaKonfirmasi({
        judul: 'Hapus Seluruh Riwayat Chat',
        pesan: 'Apakah Anda yakin ingin menghapus SELURUH riwayat obrolan kandang? Semua pesan konsultasi AI akan hilang permanen.',
        tombolTeks: 'Ya, Hapus Semua',
        onConfirm: async () => {
            try {
                const res = await fetch(endpointChat, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': getCsrfToken()
                    },
                    body: JSON.stringify({ aksi: 'hapus_semua' })
                });
                const d = await res.json();
                if (d.status === 'success') {
                    window.location.href = '{{ url('/asisten?tab=tanya&session=baru') }}';
                }
            } catch (err) {
                console.error('Gagal membersihkan seluruh riwayat:', err);
            }
        }
    });
}

document.addEventListener('DOMContentLoaded', () => {
    // Cegah native submit pada input teks dengan tombol Enter
    const inputPesan = document.getElementById('input-pesan');
    if (inputPesan) {
        inputPesan.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                handleFormSubmit(e);
            }
        });
    }

    scrollToBottom();

    // Auto-fill & auto-submit jika diarahkan dari kartu peringatan dashboard (?tanya=...)
    const urlParams = new URLSearchParams(window.location.search);
    const tanyaParam = urlParams.get('tanya');
    if (tanyaParam) {
        setTimeout(() => {
            isiDanKirim(tanyaParam);
        }, 350);
    }
});
</script>

<style>
@media (min-width: 768px) {
    .chatgpt-layout-container {
        display: flex !important;
        flex-direction: row !important;
        min-height: 660px !important;
    }
    .chatgpt-sidebar {
        width: 300px !important;
        min-width: 300px !important;
        max-width: 300px !important;
        background-color: #f7faf8 !important;
        border-right: 1px solid #e0ebe4 !important;
        border-bottom: none !important;
        flex-shrink: 0 !important;
    }
    .chatgpt-main {
        flex: 1 1 0% !important;
        min-width: 0 !important;
        background-color: #ffffff !important;
    }
}
.custom-scrollbar::-webkit-scrollbar {
    width: 5px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #cbdad1;
    border-radius: 9999px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: #a8bfb2;
}
</style>
@endsection
