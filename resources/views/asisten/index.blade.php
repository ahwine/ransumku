@extends('layouts.app')
@section('judul', 'Asisten Cerdas')
@section('isi')
@use('App\Support\Fmt')


@php
    $tabAktif = request()->query('tab', 'tanya');
    $farm = auth()->user()->farm ?? null;
    $chatSessions = $chatSessions ?? ($farm ? $farm->chatSessions()->withCount('messages')->orderByDesc('pinned')->orderByDesc('updated_at')->get() : collect());
    $sessionParam = request()->query('session');
    
    // Default selalu sesi baru ketika dimuat atau di-refresh
    if ($sessionParam && is_numeric($sessionParam) && (int) $sessionParam > 0) {
        $activeSession = $activeSession ?? $chatSessions->firstWhere('id', (int) $sessionParam);
        $chats = $chats ?? ($activeSession ? $activeSession->messages()->orderBy('id')->get() : collect());
    } else {
        $activeSession = null;
        $chats = collect();
    }
@endphp

<div data-tabs class="lg:flex-1 lg:min-h-0 lg:flex lg:flex-col space-y-2">
    <div class="flex items-center justify-start shrink-0">
        <div data-tab-nav class="tab-segmented-track relative inline-flex items-center gap-1 p-1 rounded-full text-center text-xs sm:text-sm font-bold select-none bg-surface-2 border border-line/60 shadow-2xs">
            <div data-tab-slider class="tab-slider" aria-hidden="true"></div>
            <button type="button" data-tab="tanya" class="relative z-10 py-2 px-4 rounded-full transition-colors duration-200 cursor-pointer outline-none focus:outline-none focus:ring-0 select-none {{ $tabAktif === 'tanya' ? 'active-tab bg-white shadow-xs text-ink font-bold' : 'text-muted hover:text-ink font-semibold' }}">
                Tanya Asisten AI
            </button>
            @php
                $isPeringatanActive = ($tabAktif === 'peringatan');
                $unreadCountTab = $isPeringatanActive ? 0 : $alerts->where('dibaca', false)->count();
            @endphp
            <button type="button" data-tab="peringatan" onclick="bukaTabPeringatan()" class="relative z-10 py-2 px-4 rounded-full transition-colors duration-200 cursor-pointer outline-none focus:outline-none focus:ring-0 select-none {{ $tabAktif === 'peringatan' ? 'active-tab bg-white shadow-xs text-ink font-bold' : 'text-muted hover:text-ink font-semibold' }} flex items-center gap-1.5">
                <span>Peringatan Dini</span>
                @if ($unreadCountTab > 0)
                    <span id="tab-unread-badge" class="px-2 py-0.5 rounded-full text-[10px] font-black bg-rose-600 text-white shadow-2xs">
                        {{ $unreadCountTab > 9 ? '9+' : $unreadCountTab }}
                    </span>
                @endif
            </button>
        </div>
    </div>

    {{-- Tab 1: Tanya Jawab AI (ChatGPT Style & Real-Time) --}}
    <section data-panel="tanya" class="{{ $tabAktif === 'tanya' ? '' : 'hidden' }} space-y-2">
        {{-- Status API Key --}}
        @if (!$aiAktif)
            <div class="p-3 rounded-2xl bg-amber-soft/60 border border-amber/30 text-amber-ink flex items-start gap-2.5 shrink-0 mb-2">
                <span class="material-symbols-outlined icon-fill text-[20px] text-amber-600 shrink-0 mt-0.5">info</span>
                <div class="text-xs leading-relaxed">
                    <strong class="font-bold">Kunci Gemini API belum dipasang di .env.</strong>
                    <p class="text-muted mt-0.5">Asisten berjalan dalam mode jawaban bawaan (fallback otomatis tanpa crash).</p>
                </div>
            </div>
        @endif

        {{-- ChatGPT Interface Window (Light Mode) --}}
        <div class="card !p-0 overflow-hidden border border-line/80 shadow-2xs flex flex-col md:flex-row rounded-xl bg-white text-ink chatgpt-layout-container lg:flex-1 lg:min-h-0">
            
            {{-- Left Sidebar: ChatGPT Navigation (Pinned & Recents & Akun) --}}
            <aside class="chatgpt-sidebar w-full md:w-72 lg:w-80 shrink-0 bg-[#f9faf9] border-b md:border-b-0 md:border-r border-line/70 flex flex-col justify-between select-none">
                <div class="p-3.5 space-y-3.5 flex-1 flex flex-col min-h-0">
                    {{-- Tombol + Obrolan Baru --}}
                    <button type="button" onclick="mulaiObrolanBaru()" class="w-full py-2 px-3 rounded-full bg-white hover:bg-surface-2 border border-line text-ink text-xs font-semibold flex items-center justify-center gap-1.5 transition-all shadow-2xs hover:shadow-xs cursor-pointer group active:scale-[0.99]">
                        <span class="material-symbols-outlined text-[17px] text-brand group-hover:rotate-90 transition-transform">add</span>
                        <span>Obrolan Baru</span>
                    </button>

                    @php
                        $pinnedSessions = $chatSessions->where('pinned', true);
                        $recentSessions = $chatSessions->where('pinned', false);
                    @endphp

                    <div class="flex-1 min-h-0 space-y-4 overflow-y-auto pr-1 custom-scrollbar" id="sidebar-sessions">
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
                                    <div id="session-item-{{ $s->id }}" class="session-item group relative flex items-center justify-between rounded-xl px-2.5 py-2 text-xs transition-colors duration-150 {{ $isActive ? 'bg-surface text-ink font-bold border border-line/80 shadow-2xs' : 'text-ink/75 hover:bg-surface/70 hover:text-ink' }}">
                                        <button type="button" onclick="pilihSession({{ $s->id }})" class="flex items-center gap-2 flex-1 min-w-0 pr-2 text-left cursor-pointer">
                                            <span class="material-symbols-outlined text-[15px] session-icon {{ $isActive ? 'text-brand' : 'text-muted' }}">chat_bubble</span>
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
                                    <div id="session-item-{{ $s->id }}" class="session-item group relative flex items-center justify-between rounded-xl px-2.5 py-2 text-xs transition-colors duration-150 {{ $isActive ? 'bg-surface text-ink font-bold border border-line/80 shadow-2xs' : 'text-ink/75 hover:bg-surface/70 hover:text-ink' }}">
                                        <button type="button" onclick="pilihSession({{ $s->id }})" class="flex items-center gap-2 flex-1 min-w-0 pr-2 text-left cursor-pointer">
                                            <span class="material-symbols-outlined text-[15px] session-icon {{ $isActive ? 'text-brand' : 'text-muted' }}">chat_bubble_outline</span>
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

                {{-- Footer Sidebar: Profil Pengguna --}}
                <div class="p-3 border-t border-line/60 bg-surface/40 flex items-center justify-between text-xs shrink-0 h-16 min-h-[64px]">
                    <div class="flex items-center gap-2.5 truncate min-w-0 pr-2">
                        <div class="w-8 h-8 rounded-full bg-brand/15 text-brand font-bold text-xs shrink-0 shadow-2xs flex items-center justify-center">
                            {{ strtoupper(substr(auth()->user()->name ?? 'A', 0, 1)) }}
                        </div>
                        <div class="truncate leading-tight">
                            <span class="block truncate font-semibold text-ink text-xs">{{ auth()->user()->name ?? 'Andre Abdilillah Ahwien' }}</span>
                            <span class="block text-[10px] text-muted">{{ $farm->nama ?? 'Kandang Maju Jaya' }}</span>
                        </div>
                    </div>
                    <button type="button" onclick="hapusSemua()" class="p-1.5 text-muted hover:text-danger hover:bg-white rounded-lg cursor-pointer transition-colors shadow-2xs" title="Bersihkan Semua Obrolan">
                        <span class="material-symbols-outlined text-[18px]">delete_sweep</span>
                    </button>
                </div>
            </aside>

            {{-- Right Main Chat Screen --}}
            <main class="chatgpt-main flex-1 flex flex-col justify-between bg-white min-w-0">
                
                {{-- Top Chat Header --}}
                <div class="flex items-center justify-between px-4 py-3.5 border-b border-line/70 shrink-0">
                    <div class="truncate min-w-0">
                        <h3 id="current-session-title" class="font-bold text-sm text-ink truncate">
                            {{ $activeSession ? $activeSession->judul : 'Percakapan Baru' }}
                        </h3>
                        <p id="current-session-subtitle" class="text-[11px] text-muted">
                            {{ $activeSession ? 'Sesi ID #'.$activeSession->id.' • '.$chats->count().' pesan' : 'Konsultasi Nutrisi & Manajemen Unggas' }}
                        </p>
                    </div>
                </div>

                {{-- Chat Box Window --}}
                <div id="chat-box" class="flex-1 min-h-0 overflow-y-auto space-y-4 px-4 py-3 flex flex-col custom-scrollbar">
                    
                    {{-- Welcome Screen (when session has no messages) --}}
                    <div id="welcome-card" class="{{ $chats->isEmpty() ? '' : 'hidden' }} my-auto py-6 text-center space-y-4 max-w-3xl mx-auto">
                        <div class="w-14 h-14 rounded-2xl bg-brand/10 border border-brand/20 text-brand grid place-items-center mx-auto shadow-2xs">
                            <span class="material-symbols-outlined text-[30px] icon-fill">auto_awesome</span>
                        </div>
                        <div>
                            <h3 class="font-bold text-base sm:text-lg text-ink">Halo, Peternak Cerdas!</h3>
                            <p class="text-xs text-muted mt-1 max-w-xl mx-auto leading-relaxed">
                                Saya Asisten Cerdas RansumKu didukung Google Gemini AI. Saya siap menganalisis data performa kandang Anda, memberikan saran pakan alternatif, dan rekomendasi solusi ayam petelur.
                            </p>
                        </div>

                        {{-- Suggestion Prompts --}}
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 text-left pt-2">
                            <button type="button" onclick="isiDanKirim('apakah kamu bisa cek konsumsi pakan naik kenapa kok telurnya turun?')" class="p-3.5 rounded-2xl border border-line bg-surface/30 hover:bg-white hover:border-brand/40 hover:shadow-2xs transition-all text-xs text-ink space-y-1 text-left cursor-pointer group active:scale-[0.99]">
                                <div class="font-bold text-brand flex items-center justify-between">
                                    <span class="flex items-center gap-1.5">
                                        <span class="material-symbols-outlined text-[15px] text-amber-600">warning</span>
                                        <span>Pakan Naik, Telur Turun?</span>
                                    </span>
                                    <span class="material-symbols-outlined text-[14px] text-muted opacity-0 group-hover:opacity-100 group-hover:translate-x-0.5 transition-all">arrow_forward</span>
                                </div>
                                <p class="text-[11px] text-muted group-hover:text-ink line-clamp-2">Analisis data 7 hari terakhir mengapa pakan naik namun produksi menurun.</p>
                            </button>

                            <button type="button" onclick="isiDanKirim('Bagaimana analisis performa kandang saya berdasarkan data 7 hari terakhir?')" class="p-3.5 rounded-2xl border border-line bg-surface/30 hover:bg-white hover:border-brand/40 hover:shadow-2xs transition-all text-xs text-ink space-y-1 text-left cursor-pointer group active:scale-[0.99]">
                                <div class="font-bold text-brand flex items-center justify-between">
                                    <span class="flex items-center gap-1.5">
                                        <span class="material-symbols-outlined text-[15px]">query_stats</span>
                                        <span>Evaluasi Performa Kandang</span>
                                    </span>
                                    <span class="material-symbols-outlined text-[14px] text-muted opacity-0 group-hover:opacity-100 group-hover:translate-x-0.5 transition-all">arrow_forward</span>
                                </div>
                                <p class="text-[11px] text-muted group-hover:text-ink line-clamp-2">Cek Hen-Day %, konsumsi gram/ekor/hari, dan angka mortalitas.</p>
                            </button>

                            <button type="button" onclick="isiDanKirim('Apa bahan pakan pengganti jagung yang murah dan aman?')" class="p-3.5 rounded-2xl border border-line bg-surface/30 hover:bg-white hover:border-brand/40 hover:shadow-2xs transition-all text-xs text-ink space-y-1 text-left cursor-pointer group active:scale-[0.99]">
                                <div class="font-bold text-brand flex items-center justify-between">
                                    <span class="flex items-center gap-1.5">
                                        <span class="material-symbols-outlined text-[15px]">grass</span>
                                        <span>Bahan Pengganti Jagung</span>
                                    </span>
                                    <span class="material-symbols-outlined text-[14px] text-muted opacity-0 group-hover:opacity-100 group-hover:translate-x-0.5 transition-all">arrow_forward</span>
                                </div>
                                <p class="text-[11px] text-muted group-hover:text-ink line-clamp-2">Sorgum, menir, atau dedak beserta takaran aman dalam ransum.</p>
                            </button>

                            <button type="button" onclick="isiDanKirim('Bagaimana cara menjaga kualitas pakan saat musim hujan agar tidak berjamur?')" class="p-3.5 rounded-2xl border border-line bg-surface/30 hover:bg-white hover:border-brand/40 hover:shadow-2xs transition-all text-xs text-ink space-y-1 text-left cursor-pointer group active:scale-[0.99]">
                                <div class="font-bold text-brand flex items-center justify-between">
                                    <span class="flex items-center gap-1.5">
                                        <span class="material-symbols-outlined text-[15px]">rainy</span>
                                        <span>Tips Pakan Musim Hujan</span>
                                    </span>
                                    <span class="material-symbols-outlined text-[14px] text-muted opacity-0 group-hover:opacity-100 group-hover:translate-x-0.5 transition-all">arrow_forward</span>
                                </div>
                                <p class="text-[11px] text-muted group-hover:text-ink line-clamp-2">Mencegah kelembapan, kutu, mikotoksin, dan penurunan nafsu makan.</p>
                            </button>
                        </div>
                    </div>

                    {{-- Messages Stream Container --}}
                    <div id="messages-container" class="w-full space-y-4 flex flex-col">
                        @foreach ($chats as $c)
                            @if ($c->role === 'user')
                                <div class="flex gap-2 items-start justify-end max-w-[90%] sm:max-w-[85%] self-end">
                                    <div class="p-3 rounded-2xl rounded-tr-xs bg-brand text-white text-xs sm:text-sm leading-relaxed shadow-xs">
                                        {{ $c->isi }}
                                    </div>
                                </div>
                            @else
                                <div class="flex gap-2.5 items-start w-full">
                                    <div class="w-8 h-8 rounded-full bg-brand/10 border border-brand/20 text-brand grid place-items-center shrink-0 mt-0.5">
                                        <span class="material-symbols-outlined text-[16px]">smart_toy</span>
                                    </div>
                                    <div class="p-3.5 rounded-2xl rounded-tl-xs bg-[#f4f7f5] border border-[#e2ece6] text-xs sm:text-sm text-ink leading-relaxed shadow-xs ai-content-box flex-1 min-w-0" data-raw="{{ $c->isi }}">
                                        {!! App\Support\Fmt::markdownAi($c->isi) !!}
                                    </div>
                                </div>
                            @endif
                        @endforeach
                    </div>

                    <div id="chat-bawah"></div>
                </div>

                {{-- Real-Time Input Area (Sejajar Sempurna dengan Akun Sidebar, Tanpa Border Sendiri) --}}
                <div class="p-3 bg-white flex items-center shrink-0 h-16 min-h-[64px]">
                    <form id="form-chat" action="javascript:void(0);" onsubmit="return handleFormSubmit(event);" class="relative flex items-center w-full">
                        @csrf
                        <input id="input-pesan" name="pesan" type="text" class="w-full bg-[#f8faf9] hover:bg-white focus:bg-white text-ink placeholder:text-muted border border-line focus:border-brand focus:ring-2 focus:ring-brand/20 rounded-full pl-5 pr-14 py-2.5 sm:py-3 text-xs sm:text-sm outline-none transition-all shadow-2xs" placeholder="Ketik pertanyaan atau keluhan tentang kandang Anda..." required autocomplete="off">
                        <button id="btn-kirim" type="button" onclick="handleFormSubmit(event)" class="absolute right-1.5 w-8 h-8 sm:w-9 sm:h-9 rounded-full bg-brand hover:bg-brand-dark text-white flex items-center justify-center transition-all shadow-2xs hover:shadow-xs active:scale-95 disabled:opacity-40 disabled:cursor-not-allowed cursor-pointer" aria-label="Kirim" title="Kirim pesan">
                            <span class="material-symbols-outlined text-[18px]">arrow_upward</span>
                        </button>
                    </form>
                </div>
            </main>
        </div>

        {{-- Disclaimer AI diletakkan di luar chat box, posisi vertikal seimbang dan tidak terpotong --}}
        <div class="flex items-center justify-center py-2 shrink-0">
            <p class="text-[11px] text-muted text-center flex items-center justify-center gap-1.5 leading-none">
                <span class="material-symbols-outlined text-[13px] text-muted">info</span>
                <span>{{ config('ransumku.disclaimer_ai') }}</span>
            </p>
        </div>
    </section>

    {{-- Tab 2: Peringatan Dini Kandang --}}
    <section data-panel="peringatan" class="{{ $tabAktif === 'peringatan' ? '' : 'hidden' }} space-y-4 pr-1 custom-scrollbar">
        @forelse ($alerts as $alert)
            @include('partials.alert-card', ['alert' => $alert])
        @empty
            <div class="card text-center py-12 px-6 space-y-3 rounded-2xl border border-line shadow-2xs bg-white">
                <div class="w-14 h-14 rounded-full bg-brand-soft text-brand flex items-center justify-center mx-auto shadow-2xs">
                    <span class="material-symbols-outlined text-[32px] icon-fill">verified</span>
                </div>
                <h2 class="text-base sm:text-lg font-bold text-ink">Semua Pola Terlihat Normal</h2>
                <p class="text-xs text-muted max-w-sm mx-auto leading-relaxed">
                    Tidak ditemukan anomali pada konsumsi pakan, produksi telur, maupun mortalitas ayam berdasarkan data harian yang tercatat.
                </p>
            </div>
        @endforelse

        {{-- Panduan Aturan Deteksi Anomali (Tanpa Card agar Bersih & Tidak Menyatu/Berebut dengan Isu) --}}
        <div class="pt-2 px-1 space-y-2">
            <div class="font-bold text-xs uppercase tracking-wider text-muted flex items-center gap-2">
                <span class="material-symbols-outlined text-[18px] text-brand">rule</span>
                <span class="text-ink">Aturan Deteksi Anomali Kandang</span>
            </div>
            <ul class="text-xs text-muted space-y-1.5 list-disc ml-5 leading-relaxed">
                <li>Pakan naik &ge; 10% selama 3 hari berturut-turut sementara produksi telur turun.</li>
                <li>Lonjakan kematian harian &ge; 3 ekor melebihi variasi rata-rata bergerak.</li>
                <li>Biaya pakan per kg telur melonjak signifikan (z-score &ge; 2).</li>
            </ul>
        </div>

        <p class="text-[11px] text-muted flex items-center gap-1.5 pt-1">
            <span class="material-symbols-outlined text-[14px]">info</span>
            <span>{{ config('ransumku.disclaimer_ai') }}</span>
        </p>
    </section>
</div>

<script>
let currentSessionId = {{ $activeSession ? $activeSession->id : 'null' }};
// Selalu gunakan path relatif agar tidak pernah ada masalah CORS lintas localhost dan 127.0.0.1
const endpointChat = '/asisten/chat';

function getCsrfToken() {
    return document.querySelector('input[name="_token"]')?.value || '{{ csrf_token() }}';
}

function bukaTabPeringatan() {
    // 1. Hilangkan badge titik merah pada tombol tab "Peringatan Dini"
    const badge = document.getElementById('tab-unread-badge');
    if (badge) {
        badge.remove();
    }

    // 2. Hilangkan badge merah notifikasi di sidebar dan topbar/bottombar layout
    document.querySelectorAll('[data-notif-count]').forEach(el => {
        el.classList.add('hidden');
        el.classList.remove('flex');
    });
    document.querySelectorAll('[data-notif-dot]').forEach(el => {
        el.classList.add('hidden');
        el.classList.remove('inline-block');
    });

    // 3. Update status dropdown notifikasi jika fungsi global tersedia
    if (typeof window.tandaiSemuaNotifikasiDibaca === 'function') {
        window.tandaiSemuaNotifikasiDibaca();
    } else {
        fetch('/asisten/baca', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': getCsrfToken()
            },
            body: JSON.stringify({})
        }).catch(err => console.warn('Gagal menandai peringatan terbaca:', err));
    }
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
        el.classList.remove('bg-surface', 'border', 'border-line/80', 'font-bold', 'shadow-2xs', 'bg-slate-200', 'text-slate-900', 'border-slate-300');
        el.classList.add('text-ink/75');
        const icon = el.querySelector('.session-icon');
        if (icon) {
            icon.classList.remove('text-brand', 'text-slate-700');
            icon.classList.add('text-muted');
        }
    });

    window.history.replaceState(null, '', '{{ url('/asisten?tab=tanya') }}');
    const input = document.getElementById('input-pesan');
    if (input) input.focus();
}

async function pilihSession(id) {
    currentSessionId = id;
    
    // Highlight di sidebar
    document.querySelectorAll('.session-item').forEach(el => {
        el.classList.remove('bg-surface', 'border', 'border-line/80', 'font-bold', 'shadow-2xs', 'bg-slate-200', 'text-slate-900', 'border-slate-300');
        el.classList.add('text-ink/75');
        const icon = el.querySelector('.session-icon');
        if (icon) {
            icon.classList.remove('text-brand', 'text-slate-700');
            icon.classList.add('text-muted');
        }
    });
    const elActive = document.getElementById(`session-item-${id}`);
    if (elActive) {
        elActive.classList.add('bg-surface', 'text-ink', 'font-bold', 'border', 'border-line/80', 'shadow-2xs');
        elActive.classList.remove('text-ink/75', 'text-ink/80');
        const activeIcon = elActive.querySelector('.session-icon');
        if (activeIcon) {
            activeIcon.classList.remove('text-muted');
            activeIcon.classList.add('text-brand');
        }
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
            window.history.replaceState(null, '', '{{ url('/asisten?tab=tanya') }}');

            data.messages.forEach(m => {
                if (m.role === 'user') {
                    const ub = document.createElement('div');
                    ub.className = 'flex gap-2 items-start justify-end max-w-[85%] self-end';
                    ub.innerHTML = `<div class="p-3 rounded-2xl rounded-tr-xs bg-brand text-white text-xs sm:text-sm leading-relaxed shadow-xs">${escapeHtml(m.isi)}</div>`;
                    container.appendChild(ub);
                } else {
                    const ab = document.createElement('div');
                    ab.className = 'flex gap-2.5 items-start w-full';
                    ab.innerHTML = `
                        <div class="w-8 h-8 rounded-full bg-brand/10 border border-brand/20 text-brand grid place-items-center shrink-0 mt-0.5">
                            <span class="material-symbols-outlined text-[16px]">smart_toy</span>
                        </div>
                        <div class="p-3.5 rounded-2xl rounded-tl-xs bg-[#f4f7f5] border border-[#e2ece6] text-xs sm:text-sm text-ink leading-relaxed shadow-xs ai-content-box flex-1 min-w-0" data-raw="${escapeHtml(m.isi)}">
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
    aiBubble.className = 'flex gap-2.5 items-start w-full';
    
    const contentBox = document.createElement('div');
    contentBox.className = 'p-3.5 rounded-2xl rounded-tl-xs bg-[#f4f7f5] border border-[#e2ece6] text-xs sm:text-sm text-ink leading-relaxed shadow-xs ai-content-box flex-1 min-w-0';
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

                window.history.replaceState(null, '', '{{ url('/asisten?tab=tanya') }}');
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
        el.classList.remove('bg-surface', 'border', 'border-line/80', 'font-bold', 'shadow-2xs', 'bg-slate-200', 'text-slate-900', 'border-slate-300');
        el.classList.add('text-ink/75');
        const icon = el.querySelector('.session-icon');
        if (icon) {
            icon.classList.remove('text-brand', 'text-slate-700');
            icon.classList.add('text-muted');
        }
    });

    const item = document.createElement('div');
    item.id = `session-item-${id}`;
    item.className = 'session-item group relative flex items-center justify-between rounded-xl px-2.5 py-2 text-xs transition-colors duration-150 bg-surface text-ink font-bold border border-line/80 shadow-2xs';
    item.innerHTML = `
        <button type="button" onclick="pilihSession(${id})" class="flex items-center gap-2 flex-1 min-w-0 pr-2 text-left cursor-pointer">
            <span class="material-symbols-outlined text-[15px] session-icon text-brand">chat_bubble_outline</span>
            <span class="truncate session-title text-ink font-bold">${escapeHtml(judul)}</span>
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

    // Hubungkan event click tab peringatan agar titik merah otomatis hilang
    const btnTabPeringatan = document.querySelector('button[data-tab="peringatan"]');
    if (btnTabPeringatan) {
        btnTabPeringatan.addEventListener('click', bukaTabPeringatan);
    }
});
</script>

<style>
[data-tabs] [data-panel].hidden {
    display: none !important;
}

@media (min-width: 1024px) {
    [data-panel="tanya"]:not(.hidden) {
        display: flex !important;
        flex-direction: column !important;
        justify-content: space-between !important;
        flex: 1 1 0% !important;
        min-height: 0 !important;
    }
    [data-panel="peringatan"]:not(.hidden) {
        display: block !important;
        flex: 1 1 0% !important;
        min-height: 0 !important;
        overflow-y: auto !important;
    }
    .chatgpt-layout-container {
        display: flex !important;
        flex-direction: row !important;
        flex: 1 1 0% !important;
        min-height: 0 !important;
        height: 100% !important;
    }
}

@media (min-width: 768px) and (max-width: 1023px) {
    .chatgpt-layout-container {
        display: flex !important;
        flex-direction: row !important;
        height: calc(100vh - 16rem) !important;
        min-height: 360px !important;
    }
}

@media (min-width: 768px) {
    .chatgpt-sidebar {
        width: 300px !important;
        min-width: 300px !important;
        max-width: 300px !important;
        background-color: #f9faf9 !important;
        border-right: 1px solid #e2ebe5 !important;
        border-bottom: none !important;
        flex-shrink: 0 !important;
        height: 100% !important;
        overflow: hidden !important;
    }
    .chatgpt-main {
        flex: 1 1 0% !important;
        min-width: 0 !important;
        background-color: #ffffff !important;
        height: 100% !important;
        overflow: hidden !important;
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

@media (min-width: 1024px) {
    body:has([data-panel="tanya"]:not(.hidden)) {
        overflow-y: hidden !important;
    }
    body:has([data-panel="peringatan"]:not(.hidden)) {
        overflow-y: auto !important;
    }
}
</style>
@endsection
