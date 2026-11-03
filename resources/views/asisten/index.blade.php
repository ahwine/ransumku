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

        {{-- ChatGPT Interface Window (Light Mode) - KHUSUS DESKTOP (md:flex) --}}
        <div class="hidden md:flex card !p-0 overflow-hidden border border-line/80 shadow-2xs flex-col md:flex-row rounded-xl bg-white text-ink chatgpt-layout-container lg:flex-1 lg:min-h-0">
            
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

                    {{-- Messages Stream Container (Desktop) --}}
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

                {{-- Real-Time Input Area (Desktop) --}}
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

        {{-- ========================================================================= --}}
        {{-- MOBILE ASISTEN CERDAS (Sesuai Desain Gambar 1 & Gambar 3)                 --}}
        {{-- ========================================================================= --}}
        <div class="block md:hidden">
            {{-- SUB-VIEW 1: TAMPILAN CHAT MOBILE (Persis Gambar Desain Mobile) --}}
            <div id="mobile-chat-view" class="flex flex-col h-[calc(100dvh-13.5rem)] min-h-[500px]">
                
                {{-- Bagian Riwayat Chat (Horizontal Capsule Pills) --}}
                <div class="shrink-0 mb-3 space-y-2">
                    <div class="flex items-center justify-between px-0.5">
                        <h4 class="font-extrabold text-sm text-ink tracking-tight">Riwayat Chat</h4>
                        <button type="button" onclick="bukaRiwayatMobile()" class="text-xs font-bold text-brand hover:underline cursor-pointer">
                            Lihat semua
                        </button>
                    </div>

                    {{-- Horizontal Capsule Pills Row --}}
                    <div id="mobile-recent-cards" class="flex items-center gap-2 overflow-x-auto pb-1 -mx-4 px-4 no-scrollbar select-none">
                        {{-- Tombol + Baru --}}
                        <button type="button" 
                                onclick="mulaiObrolanBaru()" 
                                class="h-9 rounded-full border-2 border-dashed border-brand/50 hover:border-brand bg-white/40 hover:bg-white text-brand px-3.5 flex items-center gap-1.5 shrink-0 transition-all cursor-pointer shadow-2xs group active:scale-95" 
                                title="Mulai Obrolan Baru">
                            <span class="material-symbols-outlined text-[16px] text-brand">add</span>
                            <span class="text-xs font-bold">Baru</span>
                        </button>

                        @foreach ($chatSessions->take(10) as $s)
                            @php
                                $isActive = ($activeSession && $activeSession->id === $s->id);
                            @endphp
                            <button type="button" 
                                    data-session-card-id="{{ $s->id }}"
                                    onclick="pilihSession({{ $s->id }})" 
                                    class="mobile-session-card h-9 rounded-full px-4 flex items-center justify-center shrink-0 text-left transition-all cursor-pointer active:scale-95 whitespace-nowrap {{ $isActive ? 'bg-brand text-white shadow-xs' : 'bg-white text-ink border border-line/80 shadow-2xs hover:border-brand/40' }}"
                                    style="{{ $isActive ? 'background-color: #116a3a; color: #ffffff;' : 'background-color: #ffffff; color: #053b26;' }}">
                                <span class="font-semibold text-xs truncate max-w-[150px] mobile-card-title">{{ $s->judul }}</span>
                            </button>
                        @endforeach
                    </div>
                </div>

                {{-- Messages Stream Container (Mobile) --}}
                <div id="mobile-chat-box" class="flex-1 min-h-0 overflow-y-auto space-y-3 pr-1 py-1 custom-scrollbar">
                    {{-- Welcome Screen Mobile (ketika sesi kosong - Persis Desain) --}}
                    <div id="mobile-welcome-card" class="{{ $chats->isEmpty() ? '' : 'hidden' }} my-auto py-2 text-center max-w-sm mx-auto">
                        {{-- Judul & Deskripsi --}}
                        <h3 class="font-extrabold text-xl text-ink tracking-tight">Halo, Peternak Cerdas!</h3>
                        <p class="text-xs text-muted mt-1.5 max-w-[280px] mx-auto leading-relaxed">
                            Tanyakan kendala performa kandang, analisa pakan, atau konsultasi ayam petelur kepada Asisten AI.
                        </p>

                        {{-- Bagian Coba Tanyakan --}}
                        <div class="text-left pt-6">
                            <h5 class="text-[11px] font-extrabold uppercase tracking-wider text-muted px-0.5 mb-2.5">
                                COBA TANYAKAN
                            </h5>
                            <div class="space-y-2.5">
                                {{-- Card 1: Pakan naik, kenapa telur turun? --}}
                                <button type="button" 
                                        onclick="isiDanKirim('apakah kamu bisa cek konsumsi pakan naik kenapa kok telurnya turun?')" 
                                        class="w-full p-3 rounded-2xl bg-white border border-line/80 hover:border-brand/40 text-left flex items-center justify-between gap-3 shadow-2xs hover:shadow-xs active:scale-98 transition group cursor-pointer">
                                    <div class="flex items-center gap-3 min-w-0">
                                        <div class="w-10 h-10 rounded-xl bg-[#e2f4ea] text-brand flex items-center justify-center shrink-0" style="background-color: #e2f4ea; color: #1b6b46;">
                                            <span class="material-symbols-outlined text-[20px]">trending_down</span>
                                        </div>
                                        <span class="font-bold text-xs text-ink truncate">Pakan naik, kenapa telur turun?</span>
                                    </div>
                                    <span class="material-symbols-outlined text-[20px] text-muted shrink-0 group-hover:translate-x-0.5 transition-transform">chevron_right</span>
                                </button>

                                {{-- Card 2: THI tinggi & penanganan heat stress? --}}
                                <button type="button" 
                                        onclick="isiDanKirim('THI hari ini tinggi, apa yang harus saya lakukan untuk mengurangi heat stress pada ayam?')" 
                                        class="w-full p-3 rounded-2xl bg-white border border-line/80 hover:border-brand/40 text-left flex items-center justify-between gap-3 shadow-2xs hover:shadow-xs active:scale-98 transition group cursor-pointer">
                                    <div class="flex items-center gap-3 min-w-0">
                                        <div class="w-10 h-10 rounded-xl bg-[#e2f4ea] text-brand flex items-center justify-center shrink-0" style="background-color: #e2f4ea; color: #1b6b46;">
                                            <span class="material-symbols-outlined text-[20px]">device_thermostat</span>
                                        </div>
                                        <span class="font-bold text-xs text-ink truncate">THI tinggi & penanganan heat stress?</span>
                                    </div>
                                    <span class="material-symbols-outlined text-[20px] text-muted shrink-0 group-hover:translate-x-0.5 transition-transform">chevron_right</span>
                                </button>

                                {{-- Card 3: Bahan pakan alternatif murah & aman? --}}
                                <button type="button" 
                                        onclick="isiDanKirim('Apa bahan pakan pengganti jagung yang murah dan aman?')" 
                                        class="w-full p-3 rounded-2xl bg-white border border-line/80 hover:border-brand/40 text-left flex items-center justify-between gap-3 shadow-2xs hover:shadow-xs active:scale-98 transition group cursor-pointer">
                                    <div class="flex items-center gap-3 min-w-0">
                                        <div class="w-10 h-10 rounded-xl bg-[#e2f4ea] text-brand flex items-center justify-center shrink-0" style="background-color: #e2f4ea; color: #1b6b46;">
                                            <span class="material-symbols-outlined text-[20px]">eco</span>
                                        </div>
                                        <span class="font-bold text-xs text-ink truncate">Bahan pakan alternatif murah & aman?</span>
                                    </div>
                                    <span class="material-symbols-outlined text-[20px] text-muted shrink-0 group-hover:translate-x-0.5 transition-transform">chevron_right</span>
                                </button>
                            </div>
                        </div>
                    </div>

                    {{-- Message Feed Mobile --}}
                    <div id="mobile-messages-container" class="space-y-3 flex flex-col {{ $chats->isEmpty() ? 'hidden' : '' }}">
                        <div class="shrink-0 flex items-center justify-center my-1 select-none">
                            <span class="text-[11px] font-medium text-muted/70 bg-surface-2 px-3 py-0.5 rounded-full">Hari ini</span>
                        </div>
                        @foreach ($chats as $c)
                            @if ($c->role === 'user')
                                <div class="flex flex-col items-end max-w-[85%] self-end">
                                    <div class="bg-brand text-white rounded-2xl rounded-tr-xs px-3.5 py-2.5 text-xs leading-relaxed shadow-xs" style="background-color: #116a3a; color: #ffffff;">
                                        {{ $c->isi }}
                                        <div class="flex items-center justify-end gap-1 mt-1 text-[10px] text-white/70">
                                            <span>{{ $c->created_at->format('H:i') }}</span>
                                            <span class="material-symbols-outlined text-[13px] leading-none">done_all</span>
                                        </div>
                                    </div>
                                </div>
                            @else
                                <div class="flex items-start gap-2 max-w-[90%] self-start">
                                    <div class="w-8 h-8 rounded-full bg-[#EBF5EE] border border-[#CDE5D6] text-brand grid place-items-center shrink-0 mt-0.5 shadow-2xs" style="background-color: #EBF5EE; border-color: #CDE5D6; color: #116a3a;">
                                        <span class="material-symbols-outlined text-[16px]">smart_toy</span>
                                    </div>
                                    <div class="bg-[#F8FAF9] border border-line/70 rounded-2xl rounded-tl-xs px-3.5 py-2.5 text-xs text-ink leading-relaxed shadow-2xs flex-1 min-w-0" style="background-color: #f8faf9;">
                                        {!! App\Support\Fmt::markdownAi($c->isi) !!}
                                        <div class="text-[10px] text-muted text-right mt-1 font-medium">
                                            {{ $c->created_at->format('H:i') }}
                                        </div>
                                    </div>
                                </div>
                            @endif
                        @endforeach
                    </div>
                </div>

                {{-- Bottom Pinned Input Bar & Disclaimer (Persis Gambar Desain Mobile) --}}
                <div class="shrink-0 pt-2 pb-1 bg-canvas">
                    <form id="mobile-form-chat" action="javascript:void(0);" onsubmit="return handleMobileFormSubmit(event);" class="relative flex items-center w-full bg-white border border-line/90 rounded-full p-1.5 pl-4 shadow-sm focus-within:border-brand focus-within:ring-2 focus-within:ring-brand/20 transition-all">
                        <input id="mobile-input-pesan" 
                               type="text" 
                               class="w-full bg-transparent text-xs text-ink placeholder:text-[#94a89e] border-none outline-none pr-2" 
                               placeholder="Ketik pertanyaan..." 
                               required 
                               autocomplete="off">
                        <button id="mobile-btn-kirim" 
                                type="button" 
                                onclick="handleMobileFormSubmit(event)" 
                                class="w-10 h-10 rounded-full bg-brand hover:bg-brand-dark text-white flex items-center justify-center shrink-0 shadow-xs active:scale-95 transition cursor-pointer disabled:opacity-40" 
                                style="background-color: #1a6644; color: #ffffff;"
                                aria-label="Kirim" 
                                title="Kirim pesan">
                            <span class="material-symbols-outlined text-[20px]">arrow_upward</span>
                        </button>
                    </form>
                </div>
            </div>

            {{-- SUB-VIEW 2: HALAMAN RIWAYAT CHAT KHUSUS MOBILE (Gambar 3) --}}
            <div id="mobile-history-view" class="hidden space-y-4 pb-4">
                {{-- Top Bar: Back Button & Total Sessions --}}
                <div class="flex items-center justify-between gap-2 pb-1 border-b border-line/60">
                    <button type="button" onclick="tutupRiwayatMobile()" class="flex items-center gap-1 text-xs font-bold text-brand hover:text-brand-dark py-1.5 px-2.5 -ml-2 rounded-xl hover:bg-surface transition cursor-pointer">
                        <span class="material-symbols-outlined text-[18px]">arrow_back</span>
                        <span>Kembali ke Chat</span>
                    </button>
                    <div class="text-[11px] font-semibold text-muted">
                        {{ $chatSessions->count() }} Sesi
                    </div>
                </div>

                {{-- Filter Pills (Gambar 3) --}}
                <div class="flex items-center gap-2 select-none">
                    <button type="button" 
                            data-history-filter="semua" 
                            onclick="filterRiwayatMobile('semua')" 
                            class="history-filter-pill px-4 py-1.5 rounded-full text-xs font-bold transition-all bg-brand text-white shadow-2xs cursor-pointer"
                            style="background-color: #116a3a; color: #ffffff;">
                        Semua
                    </button>
                    <button type="button" 
                            data-history-filter="disematkan" 
                            onclick="filterRiwayatMobile('disematkan')" 
                            class="history-filter-pill px-4 py-1.5 rounded-full text-xs font-semibold transition-all bg-surface-2 text-muted hover:text-ink border border-line/60 cursor-pointer"
                            style="background-color: #eaefec; color: #5c7a6d;">
                        Disematkan
                    </button>
                    <button type="button" 
                            data-history-filter="hari_ini" 
                            onclick="filterRiwayatMobile('hari_ini')" 
                            class="history-filter-pill px-4 py-1.5 rounded-full text-xs font-semibold transition-all bg-surface-2 text-muted hover:text-ink border border-line/60 cursor-pointer"
                            style="background-color: #eaefec; color: #5c7a6d;">
                        Hari Ini
                    </button>
                </div>

                {{-- Section Disematkan (Pinned) --}}
                @php
                    $pinnedMobile = $chatSessions->where('pinned', true);
                    $unpinnedMobile = $chatSessions->where('pinned', false);
                @endphp
                <div id="history-section-pinned" class="space-y-2 {{ $pinnedMobile->isEmpty() ? 'hidden' : '' }}">
                    <div class="flex items-center justify-between text-xs font-bold text-ink uppercase tracking-wider px-1">
                        <span class="flex items-center gap-1.5">
                            <span class="material-symbols-outlined text-[16px] text-brand">push_pin</span>
                            <span>Disematkan</span>
                        </span>
                        <button type="button" onclick="filterRiwayatMobile('disematkan')" class="text-xs font-bold text-brand hover:underline cursor-pointer lowercase first-letter:uppercase">Kelola</button>
                    </div>

                    <div class="space-y-1.5" id="history-pinned-list">
                        @foreach ($pinnedMobile as $s)
                            @php
                                $lastMsg = $s->messages->first()?->isi ?? 'Belum ada pesan.';
                                $tglStr = $s->updated_at->translatedFormat('d M');
                                $isToday = $s->updated_at->isToday();
                            @endphp
                            <div data-history-item 
                                 data-session-id="{{ $s->id }}" 
                                 data-is-pinned="1" 
                                 data-is-today="{{ $isToday ? '1' : '0' }}"
                                 class="flex items-center justify-between gap-3 p-3 rounded-2xl bg-white hover:bg-surface/60 border border-line/80 hover:border-brand/40 transition-all shadow-2xs cursor-pointer group"
                                 onclick="pilihSessionDanTutupHistory({{ $s->id }})">
                                <div class="flex items-center gap-3 min-w-0 flex-1">
                                    <div class="w-10 h-10 rounded-full bg-[#EBF5EE] text-brand grid place-items-center shrink-0 shadow-2xs" style="background-color: #EBF5EE; color: #116a3a;">
                                        <span class="material-symbols-outlined text-[18px]">chat</span>
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <h5 class="font-bold text-xs text-ink truncate">{{ $s->judul }}</h5>
                                        <p class="text-[11px] text-muted truncate mt-0.5">{{ Str::limit(strip_tags($lastMsg), 65) }}</p>
                                    </div>
                                </div>
                                <div class="flex flex-col items-end gap-1 shrink-0 select-none">
                                    <span class="text-[10px] text-muted font-medium">{{ $tglStr }}</span>
                                    <div class="flex items-center gap-1" onclick="event.stopPropagation()">
                                        <button type="button" onclick="togglePinSession({{ $s->id }}, event)" class="p-1 text-brand hover:text-brand-dark rounded cursor-pointer transition" title="Lepas Sematan">
                                            <span class="material-symbols-outlined text-[16px]">push_pin</span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Section Semua Percakapan --}}
                <div id="history-section-unpinned" class="space-y-2">
                    <div class="flex items-center justify-between text-xs font-bold text-ink uppercase tracking-wider px-1">
                        <span>Semua Percakapan</span>
                        <span class="text-[11px] text-muted font-semibold">{{ $unpinnedMobile->count() }}</span>
                    </div>

                    <div class="space-y-1.5" id="history-unpinned-list">
                        @forelse ($unpinnedMobile as $s)
                            @php
                                $lastMsg = $s->messages->first()?->isi ?? 'Belum ada pesan.';
                                $tglStr = $s->updated_at->translatedFormat('d M');
                                $isToday = $s->updated_at->isToday();
                            @endphp
                            <div data-history-item 
                                 data-session-id="{{ $s->id }}" 
                                 data-is-pinned="0" 
                                 data-is-today="{{ $isToday ? '1' : '0' }}"
                                 class="flex items-center justify-between gap-3 p-3 rounded-2xl bg-white hover:bg-surface/60 border border-line/80 hover:border-brand/40 transition-all shadow-2xs cursor-pointer group"
                                 onclick="pilihSessionDanTutupHistory({{ $s->id }})">
                                <div class="flex items-center gap-3 min-w-0 flex-1">
                                    <div class="w-10 h-10 rounded-full bg-[#F4F7F5] text-muted grid place-items-center shrink-0 shadow-2xs" style="background-color: #F4F7F5;">
                                        <span class="material-symbols-outlined text-[18px]">chat</span>
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <h5 class="font-bold text-xs text-ink truncate">{{ $s->judul }}</h5>
                                        <p class="text-[11px] text-muted truncate mt-0.5">{{ Str::limit(strip_tags($lastMsg), 65) }}</p>
                                    </div>
                                </div>
                                <div class="flex flex-col items-end gap-1 shrink-0 select-none">
                                    <span class="text-[10px] text-muted font-medium">{{ $tglStr }}</span>
                                    <div class="relative" onclick="event.stopPropagation()">
                                        <button type="button" onclick="toggleHistoryDropdown({{ $s->id }}, event)" class="p-1 text-muted hover:text-ink rounded-lg hover:bg-surface-2 transition cursor-pointer" title="Menu Sesi">
                                            <span class="material-symbols-outlined text-[18px]">more_vert</span>
                                        </button>
                                        <div id="history-dropdown-{{ $s->id }}" class="history-dropdown-menu hidden absolute right-0 bottom-full mb-1 w-36 bg-white rounded-xl shadow-lg border border-line py-1 z-30">
                                            <button type="button" onclick="togglePinSession({{ $s->id }}, event); tutupSemuaHistoryDropdown();" class="w-full px-3 py-2 text-left text-xs text-ink hover:bg-surface flex items-center gap-2 cursor-pointer">
                                                <span class="material-symbols-outlined text-[16px] text-brand">push_pin</span>
                                                <span>Sematkan</span>
                                            </button>
                                            <button type="button" onclick="hapusSession({{ $s->id }}, event); tutupSemuaHistoryDropdown();" class="w-full px-3 py-2 text-left text-xs text-danger hover:bg-danger-soft/30 flex items-center gap-2 cursor-pointer">
                                                <span class="material-symbols-outlined text-[16px] text-danger">delete</span>
                                                <span>Hapus Sesi</span>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @empty
                            @if ($pinnedMobile->isEmpty())
                                <div class="p-6 text-center text-xs text-muted italic bg-surface/30 rounded-2xl border border-line/60">
                                    Belum ada riwayat obrolan tersimpan.
                                </div>
                            @endif
                        @endforelse
                    </div>
                </div>

                {{-- Bottom + Obrolan Baru button in History view --}}
                <div class="pt-2">
                    <button type="button" onclick="mulaiObrolanBaru(); tutupRiwayatMobile();" class="w-full py-3 px-4 rounded-full bg-brand hover:bg-brand-dark text-white font-bold text-xs flex items-center justify-center gap-2 shadow-sm transition active:scale-98 cursor-pointer" style="background-color: #116a3a; color: #ffffff;">
                        <span class="material-symbols-outlined text-[18px]">add</span>
                        <span>+ Obrolan Baru</span>
                    </button>
                </div>
            </div>
        </div>
    </section>

    {{-- Tab 2: Peringatan Dini Kandang (Sesuai Gambar 2, Berlaku Desktop & Mobile) --}}
    <section data-panel="peringatan" class="{{ $tabAktif === 'peringatan' ? '' : 'hidden' }} space-y-5 pr-1 custom-scrollbar">
        @php
            $groupedAlerts = $alerts->groupBy(function($alert) {
                $tgl = $alert->tanggal_data ?? $alert->created_at;
                if ($tgl->isToday()) {
                    return 'Hari Ini';
                }
                if ($tgl->isYesterday()) {
                    return 'Kemarin';
                }
                return 'Sebelumnya';
            });
        @endphp

        @if ($alerts->isEmpty())
            <div class="card text-center py-12 px-6 space-y-3 rounded-2xl border border-line shadow-2xs bg-white">
                <div class="w-14 h-14 rounded-full bg-brand-soft text-brand flex items-center justify-center mx-auto shadow-2xs">
                    <span class="material-symbols-outlined text-[32px] icon-fill">verified</span>
                </div>
                <h2 class="text-base sm:text-lg font-bold text-ink">Semua Pola Terlihat Normal</h2>
                <p class="text-xs text-muted max-w-sm mx-auto leading-relaxed">
                    Tidak ditemukan anomali pada konsumsi pakan, produksi telur, maupun mortalitas ayam berdasarkan data harian yang tercatat.
                </p>
            </div>
        @else
            @foreach (['Hari Ini', 'Kemarin', 'Sebelumnya'] as $grupNama)
                @if (isset($groupedAlerts[$grupNama]) && $groupedAlerts[$grupNama]->isNotEmpty())
                    <div class="space-y-3">
                        <h3 class="text-xs sm:text-sm font-extrabold text-ink tracking-tight px-1">
                            {{ $grupNama }}
                        </h3>

                        <div class="space-y-3">
                            @foreach ($groupedAlerts[$grupNama] as $alert)
                                @php
                                    // Tipe kartu sesuai desain Gambar 2:
                                    // - "BIAYA PAKAN NAIK TAJAM" -> Waspada (Kuning / Krem)
                                    // - "PRODUKSI TELUR TURUN" -> Bahaya (Pink / Merah)
                                    // - "KONSUMSI PAKAN TINGGI" -> Waspada (Kuning / Krem)
                                    // - "LONJAKAN MORTALITAS AYAM" -> Bahaya (Pink / Merah)
                                    // - "KONDISI CUACA EKSTREM" -> Info (Mint / Hijau)
                                    if ($alert->jenis === 'pakan_naik_telur_turun' || $alert->jenis === 'lonjakan_kematian' || $alert->level === 'bahaya') {
                                        $cardType = 'bahaya';
                                    } elseif ($alert->jenis === 'biaya_per_kg_telur_naik' || $alert->jenis === 'konsumsi_pakan_tinggi' || $alert->level === 'waspada' || $alert->level === 'perhatian') {
                                        $cardType = 'waspada';
                                    } else {
                                        $cardType = 'info';
                                    }

                                    // Mapping Judul Huruf Besar (Gambar 2)
                                    $titles = [
                                        'biaya_per_kg_telur_naik' => 'BIAYA PAKAN NAIK TAJAM',
                                        'pakan_naik_telur_turun' => 'PRODUKSI TELUR TURUN',
                                        'lonjakan_kematian' => 'LONJAKAN MORTALITAS AYAM',
                                        'konsumsi_pakan_tinggi' => 'KONSUMSI PAKAN TINGGI',
                                        'cuaca_ekstrem' => 'KONDISI CUACA EKSTREM',
                                    ];
                                    $judulPeringatan = $titles[$alert->jenis] ?? strtoupper(str_replace('_', ' ', $alert->jenis));

                                    // Mapping Waktu
                                    $tgl = $alert->tanggal_data ?? $alert->created_at;
                                    if ($tgl->isToday()) {
                                        $waktuTeks = $alert->created_at ? $alert->created_at->format('H:i') : 'Hari ini';
                                    } elseif ($tgl->isYesterday()) {
                                        $waktuTeks = 'Kemarin';
                                    } else {
                                        $diffHari = (int) $tgl->diffInDays(now());
                                        $waktuTeks = $diffHari > 0 ? $diffHari . ' hari lalu' : $tgl->translatedFormat('d M');
                                    }

                                    // Styling per tipe kartu dengan Inline Style Presisi (Anti-Bentrok Tailwind)
                                    if ($cardType === 'bahaya') {
                                        $cardStyle = 'background-color: #FFF1F2; border: 1px solid #FECDD3;';
                                        $badgeStyle = 'background-color: #FFE4E6; color: #BE123C;';
                                        $badgeText = 'Bahaya';
                                        $badgeIcon = 'warning';
                                        $dotStyle = 'background-color: #E11D48;';
                                    } elseif ($cardType === 'waspada') {
                                        $cardStyle = 'background-color: #FFFBEB; border: 1px solid #FDE68A;';
                                        $badgeStyle = 'background-color: #FEF3C7; color: #B45309;';
                                        $badgeText = 'Waspada';
                                        $badgeIcon = 'warning';
                                        $dotStyle = 'background-color: #D97706;';
                                    } else {
                                        $cardStyle = 'background-color: #F0FDF4; border: 1px solid #BBF7D0;';
                                        $badgeStyle = 'background-color: #DCFCE7; color: #15803D;';
                                        $badgeText = 'Info';
                                        $badgeIcon = 'description';
                                        $dotStyle = 'background-color: #16A34A;';
                                    }

                                    $tanyaPrompt = 'Tolong berikan analisis mendalam dan rekomendasi solusi cepat untuk kendala di kandang saya: ' . $alert->pesan;
                                @endphp

                                <div onclick="konsultasiPeringatan('{{ addslashes($tanyaPrompt) }}')"
                                     style="{{ $cardStyle }}"
                                     class="rounded-2xl p-4 sm:p-5 shadow-2xs hover:shadow-xs transition-all cursor-pointer relative group flex items-start justify-between gap-3">
                                    <div class="space-y-1.5 flex-1 min-w-0 pr-2">
                                        {{-- Badge & Waktu --}}
                                        <div class="flex items-center justify-between">
                                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold" style="{{ $badgeStyle }}">
                                                <span class="material-symbols-outlined text-[15px]">{{ $badgeIcon }}</span>
                                                <span>{{ $badgeText }}</span>
                                            </span>
                                            <span class="text-xs text-muted font-medium">{{ $waktuTeks }}</span>
                                        </div>

                                        {{-- Judul Huruf Besar --}}
                                        <h4 class="font-extrabold text-xs sm:text-sm text-ink tracking-tight pt-1">
                                            {{ $judulPeringatan }}
                                        </h4>

                                        {{-- Pesan Deskripsi --}}
                                        <p class="text-xs sm:text-sm text-ink/85 leading-relaxed">
                                            {{ $alert->pesan }}
                                        </p>
                                    </div>

                                    {{-- Titik Status di Kanan --}}
                                    <div class="pt-2 shrink-0 self-center">
                                        <span class="block w-2.5 h-2.5 rounded-full" style="{{ $dotStyle }}"></span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            @endforeach
        @endif

        {{-- Panduan Aturan Deteksi Anomali --}}
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

function scrollToBottom() {
    setTimeout(() => {
        const chatBox = document.getElementById('chat-box');
        if (chatBox) {
            chatBox.scrollTop = chatBox.scrollHeight;
        }
        const mobileChatBox = document.getElementById('mobile-chat-box');
        if (mobileChatBox) {
            mobileChatBox.scrollTop = mobileChatBox.scrollHeight;
        }
    }, 30);
}

function isiDanKirim(teks) {
    const inputDesktop = document.getElementById('input-pesan');
    const inputMobile = document.getElementById('mobile-input-pesan');
    if (inputDesktop) inputDesktop.value = teks;
    if (inputMobile) inputMobile.value = teks;
    kirimPesanRealtime();
}

function bukaRiwayatMobile() {
    const chatView = document.getElementById('mobile-chat-view');
    const histView = document.getElementById('mobile-history-view');
    if (chatView && histView) {
        chatView.classList.add('hidden');
        histView.classList.remove('hidden');
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
}

function tutupRiwayatMobile() {
    const chatView = document.getElementById('mobile-chat-view');
    const histView = document.getElementById('mobile-history-view');
    if (chatView && histView) {
        histView.classList.add('hidden');
        chatView.classList.remove('hidden');
        scrollToBottom();
    }
}

function filterRiwayatMobile(filterType) {
    document.querySelectorAll('.history-filter-pill').forEach(btn => {
        if (btn.getAttribute('data-history-filter') === filterType) {
            btn.className = 'history-filter-pill px-4 py-1.5 rounded-full text-xs font-bold transition-all bg-brand text-white shadow-2xs cursor-pointer';
            btn.style.backgroundColor = '#116a3a';
            btn.style.color = '#ffffff';
        } else {
            btn.className = 'history-filter-pill px-4 py-1.5 rounded-full text-xs font-semibold transition-all bg-surface-2 text-muted hover:text-ink border border-line/60 cursor-pointer';
            btn.style.backgroundColor = '#eaefec';
            btn.style.color = '#5c7a6d';
        }
    });

    const secPinned = document.getElementById('history-section-pinned');
    const secUnpinned = document.getElementById('history-section-unpinned');
    let visiblePinned = 0;
    let visibleUnpinned = 0;

    document.querySelectorAll('#mobile-history-view [data-history-item]').forEach(item => {
        const isPinned = item.getAttribute('data-is-pinned') === '1';
        const isToday = item.getAttribute('data-is-today') === '1';

        let show = false;
        if (filterType === 'semua') {
            show = true;
        } else if (filterType === 'disematkan') {
            show = isPinned;
        } else if (filterType === 'hari_ini') {
            show = isToday;
        }

        item.style.display = show ? 'flex' : 'none';
        if (show) {
            if (isPinned) visiblePinned++;
            else visibleUnpinned++;
        }
    });

    if (secPinned) {
        if (filterType === 'hari_ini') {
            secPinned.style.display = (visiblePinned > 0) ? 'block' : 'none';
        } else {
            secPinned.style.display = (visiblePinned > 0) ? 'block' : 'none';
        }
    }
    if (secUnpinned) {
        secUnpinned.style.display = (visibleUnpinned > 0 || filterType === 'semua') ? 'block' : 'none';
    }
}

function toggleHistoryDropdown(id, event) {
    if (event) event.stopPropagation();
    const menu = document.getElementById(`history-dropdown-${id}`);
    const isHidden = menu ? menu.classList.contains('hidden') : true;
    tutupSemuaHistoryDropdown();
    if (menu && isHidden) {
        menu.classList.remove('hidden');
    }
}

function tutupSemuaHistoryDropdown() {
    document.querySelectorAll('.history-dropdown-menu').forEach(m => m.classList.add('hidden'));
}

document.addEventListener('click', () => {
    tutupSemuaHistoryDropdown();
});

function pilihSessionDanTutupHistory(id) {
    pilihSession(id);
    tutupRiwayatMobile();
}

function konsultasiPeringatan(promptText) {
    // 1. Pindah tab ke 'tanya'
    const btnTabTanya = document.querySelector('button[data-tab="tanya"]');
    if (btnTabTanya) {
        btnTabTanya.click();
    }
    // 2. Tutup riwayat mobile jika terbuka
    tutupRiwayatMobile();

    // 3. Auto fill dan kirim
    setTimeout(() => {
        isiDanKirim(promptText);
    }, 250);
}

function updateMobileCardHighlight(activeId) {
    document.querySelectorAll('.mobile-session-card').forEach(card => {
        const cardId = parseInt(card.getAttribute('data-session-card-id'));
        const isThisActive = (activeId && cardId === activeId);

        if (isThisActive) {
            card.className = 'mobile-session-card h-9 rounded-full px-4 flex items-center justify-center shrink-0 text-left transition-all cursor-pointer active:scale-95 whitespace-nowrap bg-brand text-white shadow-xs';
            card.style.backgroundColor = '#116a3a';
            card.style.color = '#ffffff';
        } else {
            card.className = 'mobile-session-card h-9 rounded-full px-4 flex items-center justify-center shrink-0 text-left transition-all cursor-pointer active:scale-95 whitespace-nowrap bg-white text-ink border border-line/80 shadow-2xs hover:border-brand/40';
            card.style.backgroundColor = '#ffffff';
            card.style.color = '#053b26';
        }
    });
}

function mulaiObrolanBaru() {
    currentSessionId = null;
    
    // Reset desktop
    const deskContainer = document.getElementById('messages-container');
    if (deskContainer) deskContainer.innerHTML = '';
    const deskWelcome = document.getElementById('welcome-card');
    if (deskWelcome) deskWelcome.classList.remove('hidden');
    const deskTitle = document.getElementById('current-session-title');
    if (deskTitle) deskTitle.textContent = 'Percakapan Baru';
    const deskSubtitle = document.getElementById('current-session-subtitle');
    if (deskSubtitle) deskSubtitle.textContent = 'Konsultasi Nutrisi & Manajemen Unggas';

    // Hapus highlight aktif di desktop sidebar
    document.querySelectorAll('.session-item').forEach(el => {
        el.classList.remove('bg-surface', 'border', 'border-line/80', 'font-bold', 'shadow-2xs', 'bg-slate-200', 'text-slate-900', 'border-slate-300');
        el.classList.add('text-ink/75');
        const icon = el.querySelector('.session-icon');
        if (icon) {
            icon.classList.remove('text-brand', 'text-slate-700');
            icon.classList.add('text-muted');
        }
    });

    // Reset mobile
    const mobContainer = document.getElementById('mobile-messages-container');
    if (mobContainer) {
        mobContainer.innerHTML = '';
        mobContainer.classList.add('hidden');
    }
    const mobWelcome = document.getElementById('mobile-welcome-card');
    if (mobWelcome) mobWelcome.classList.remove('hidden');
    updateMobileCardHighlight(null);
    tutupRiwayatMobile();

    window.history.replaceState(null, '', '{{ url('/asisten?tab=tanya') }}');
    const input = window.innerWidth >= 768 ? document.getElementById('input-pesan') : document.getElementById('mobile-input-pesan');
    if (input) input.focus();
}

async function pilihSession(id) {
    currentSessionId = id;
    
    // 1. Highlight di desktop sidebar
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

    // 2. Highlight di mobile horizontal cards
    updateMobileCardHighlight(id);

    const welcomeCard = document.getElementById('welcome-card');
    const container = document.getElementById('messages-container');
    const mobWelcomeCard = document.getElementById('mobile-welcome-card');
    const mobContainer = document.getElementById('mobile-messages-container');

    if (welcomeCard) welcomeCard.classList.add('hidden');
    if (mobWelcomeCard) mobWelcomeCard.classList.add('hidden');
    if (mobContainer) mobContainer.classList.remove('hidden');

    const loaderHtml = `
        <div class="flex gap-2.5 items-start max-w-[85%] py-2 text-xs text-muted">
            <span class="material-symbols-outlined text-[18px] animate-spin text-brand">sync</span>
            <span>Memuat pesan percakapan...</span>
        </div>
    `;
    if (container) container.innerHTML = loaderHtml;
    if (mobContainer) mobContainer.innerHTML = loaderHtml;

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
        if (container) container.innerHTML = '';
        if (mobContainer) mobContainer.innerHTML = '';

        if (data.status === 'success') {
            const titleEl = document.getElementById('current-session-title');
            if (titleEl) titleEl.textContent = data.session_title;
            const subtitleEl = document.getElementById('current-session-subtitle');
            if (subtitleEl) subtitleEl.textContent = `Sesi ID #${id} • ${data.messages.length} pesan`;
            
            window.history.replaceState(null, '', '{{ url('/asisten?tab=tanya') }}');

            data.messages.forEach(m => {
                const timeText = escapeHtml(m.time || '');

                // A. Render Desktop
                if (container) {
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
                }

                // B. Render Mobile (Gambar 1)
                if (mobContainer) {
                    if (m.role === 'user') {
                        const mobU = document.createElement('div');
                        mobU.className = 'flex flex-col items-end max-w-[85%] self-end';
                        mobU.innerHTML = `
                            <div class="bg-brand text-white rounded-2xl rounded-tr-xs px-3.5 py-2.5 text-xs leading-relaxed shadow-xs" style="background-color: #116a3a; color: #ffffff;">
                                ${escapeHtml(m.isi)}
                                <div class="flex items-center justify-end gap-1 mt-1 text-[10px] text-white/70">
                                    <span>${timeText}</span>
                                    <span class="material-symbols-outlined text-[13px] leading-none">done_all</span>
                                </div>
                            </div>
                        `;
                        mobContainer.appendChild(mobU);
                    } else {
                        const mobA = document.createElement('div');
                        mobA.className = 'flex items-start gap-2 max-w-[90%] self-start';
                        mobA.innerHTML = `
                            <div class="w-8 h-8 rounded-full bg-[#EBF5EE] border border-[#CDE5D6] text-brand grid place-items-center shrink-0 mt-0.5 shadow-2xs" style="background-color: #EBF5EE; border-color: #CDE5D6; color: #116a3a;">
                                <span class="material-symbols-outlined text-[16px]">smart_toy</span>
                            </div>
                            <div class="bg-[#F8FAF9] border border-line/70 rounded-2xl rounded-tl-xs px-3.5 py-2.5 text-xs text-ink leading-relaxed shadow-2xs flex-1 min-w-0" style="background-color: #f8faf9;" data-raw="${escapeHtml(m.isi)}">
                                ${formatAiMarkdown(m.isi)}
                                <div class="text-[10px] text-muted text-right mt-1 font-medium">
                                    ${timeText}
                                </div>
                            </div>
                        `;
                        mobContainer.appendChild(mobA);
                    }
                }
            });
            scrollToBottom();
        }
    } catch (e) {
        if (container) container.innerHTML = '';
        if (mobContainer) mobContainer.innerHTML = '';
        tampilkanErrorChat('Gagal memuat pesan sesi.');
    }
}

/**
 * Streaming perkata ala ChatGPT: Mendukung elemen tunggal maupun array elemen
 */
function streamWords(elements, fullText, onDone) {
    if (!Array.isArray(elements)) {
        elements = [elements].filter(Boolean);
    } else {
        elements = elements.filter(Boolean);
    }
    const tokens = fullText.split(/(\s+)/);
    let current = '';
    let idx = 0;
    const speed = 20;

    elements.forEach(el => {
        el.innerHTML = '<span class="inline-block w-1.5 h-3.5 bg-brand ml-1 animate-pulse align-middle"></span>';
    });

    function tick() {
        if (idx < tokens.length) {
            current += tokens[idx];
            idx++;
            if (idx < tokens.length && /^\s+$/.test(tokens[idx])) {
                current += tokens[idx];
                idx++;
            }
            const formatted = formatAiMarkdown(current) + '<span class="inline-block w-1.5 h-3.5 bg-brand ml-1 animate-pulse align-middle"></span>';
            elements.forEach(el => el.innerHTML = formatted);
            scrollToBottom();
            setTimeout(tick, speed);
        } else {
            const formattedFinal = formatAiMarkdown(fullText);
            elements.forEach(el => el.innerHTML = formattedFinal);
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

function handleMobileFormSubmit(e) {
    if (e) {
        e.preventDefault();
        e.stopPropagation();
    }
    const mobInput = document.getElementById('mobile-input-pesan');
    const deskInput = document.getElementById('input-pesan');
    if (mobInput && deskInput) {
        deskInput.value = mobInput.value;
    }
    kirimPesanRealtime();
    if (mobInput) {
        mobInput.value = '';
    }
    return false;
}

async function kirimPesanRealtime() {
    const inputDesk = document.getElementById('input-pesan');
    const inputMob = document.getElementById('mobile-input-pesan');
    const btnDesk = document.getElementById('btn-kirim');
    const btnMob = document.getElementById('mobile-btn-kirim');
    const deskContainer = document.getElementById('messages-container');
    const mobContainer = document.getElementById('mobile-messages-container');
    const deskWelcome = document.getElementById('welcome-card');
    const mobWelcome = document.getElementById('mobile-welcome-card');

    const pesan = (inputMob && inputMob.value.trim() ? inputMob.value.trim() : (inputDesk ? inputDesk.value.trim() : ''));
    if (!pesan) return;

    if (deskWelcome) deskWelcome.classList.add('hidden');
    if (mobWelcome) mobWelcome.classList.add('hidden');
    if (mobContainer) mobContainer.classList.remove('hidden');

    // 1. Tampilkan bubble pesan user di Desktop & Mobile
    if (deskContainer) {
        const ubDesk = document.createElement('div');
        ubDesk.className = 'flex gap-2 items-start justify-end max-w-[85%] self-end';
        ubDesk.innerHTML = `<div class="p-3 rounded-2xl rounded-tr-xs bg-brand text-white text-xs sm:text-sm leading-relaxed shadow-xs">${escapeHtml(pesan)}</div>`;
        deskContainer.appendChild(ubDesk);
    }
    if (mobContainer) {
        const ubMob = document.createElement('div');
        ubMob.className = 'flex flex-col items-end max-w-[85%] self-end';
        ubMob.innerHTML = `
            <div class="bg-brand text-white rounded-2xl rounded-tr-xs px-3.5 py-2.5 text-xs leading-relaxed shadow-xs" style="background-color: #116a3a; color: #ffffff;">
                ${escapeHtml(pesan)}
                <div class="flex items-center justify-end gap-1 mt-1 text-[10px] text-white/70">
                    <span>Baru saja</span>
                    <span class="material-symbols-outlined text-[13px] leading-none">done_all</span>
                </div>
            </div>
        `;
        mobContainer.appendChild(ubMob);
    }

    if (inputDesk) {
        inputDesk.value = '';
        inputDesk.disabled = true;
    }
    if (inputMob) {
        inputMob.value = '';
        inputMob.disabled = true;
    }
    if (btnDesk) btnDesk.disabled = true;
    if (btnMob) btnMob.disabled = true;

    // 2. Buat gelembung Asisten AI di Desktop & Mobile
    let deskContentBox = null;
    let mobContentBox = null;

    if (deskContainer) {
        const aiBubbleDesk = document.createElement('div');
        aiBubbleDesk.className = 'flex gap-2.5 items-start w-full';
        deskContentBox = document.createElement('div');
        deskContentBox.className = 'p-3.5 rounded-2xl rounded-tl-xs bg-[#f4f7f5] border border-[#e2ece6] text-xs sm:text-sm text-ink leading-relaxed shadow-xs ai-content-box flex-1 min-w-0';
        deskContentBox.innerHTML = `
            <div class="flex items-center gap-2 text-brand text-xs py-0.5 font-medium">
                <span class="w-2 h-2 rounded-full bg-brand animate-ping"></span>
                <span class="animate-pulse text-muted">Asisten sedang menganalisis & menyusun jawaban...</span>
            </div>
        `;
        aiBubbleDesk.innerHTML = `
            <div class="w-8 h-8 rounded-full bg-brand/10 border border-brand/20 text-brand grid place-items-center shrink-0 mt-0.5">
                <span class="material-symbols-outlined text-[16px]">smart_toy</span>
            </div>
        `;
        aiBubbleDesk.appendChild(deskContentBox);
        deskContainer.appendChild(aiBubbleDesk);
    }

    if (mobContainer) {
        const aiBubbleMob = document.createElement('div');
        aiBubbleMob.className = 'flex items-start gap-2 max-w-[90%] self-start';
        mobContentBox = document.createElement('div');
        mobContentBox.className = 'bg-[#F8FAF9] border border-line/70 rounded-2xl rounded-tl-xs px-3.5 py-2.5 text-xs text-ink leading-relaxed shadow-2xs flex-1 min-w-0';
        mobContentBox.style.backgroundColor = '#f8faf9';
        mobContentBox.innerHTML = `
            <div class="flex items-center gap-2 text-brand text-xs py-0.5 font-medium" style="color: #116a3a;">
                <span class="w-2 h-2 rounded-full bg-brand animate-ping" style="background-color: #116a3a;"></span>
                <span class="animate-pulse text-muted">Sedang menganalisis...</span>
            </div>
        `;
        aiBubbleMob.innerHTML = `
            <div class="w-8 h-8 rounded-full bg-[#EBF5EE] border border-[#CDE5D6] text-brand grid place-items-center shrink-0 mt-0.5 shadow-2xs" style="background-color: #EBF5EE; border-color: #CDE5D6; color: #116a3a;">
                <span class="material-symbols-outlined text-[16px]">smart_toy</span>
            </div>
        `;
        aiBubbleMob.appendChild(mobContentBox);
        mobContainer.appendChild(aiBubbleMob);
    }

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
            const boxes = [deskContentBox, mobContentBox].filter(Boolean);
            streamWords(boxes, data.jawaban, () => {
                if (mobContentBox) {
                    const timeEl = document.createElement('div');
                    timeEl.className = 'text-[10px] text-muted text-right mt-1 font-medium';
                    timeEl.textContent = data.created_at || 'Baru saja';
                    mobContentBox.appendChild(timeEl);
                }
            });

            if (data.is_new_session && data.session_id) {
                currentSessionId = data.session_id;
                const titleEl = document.getElementById('current-session-title');
                if (titleEl) titleEl.textContent = data.session_title;
                const subtitleEl = document.getElementById('current-session-subtitle');
                if (subtitleEl) subtitleEl.textContent = `Sesi ID #${data.session_id} • 2 pesan`;

                window.history.replaceState(null, '', '{{ url('/asisten?tab=tanya') }}');
                tambahSesiKeSidebar(data.session_id, data.session_title);
            }
        } else {
            const errHtml = `
                <div class="flex items-center gap-2 text-xs text-danger-ink bg-danger-soft border border-danger/30 rounded-xl p-3">
                    <span class="material-symbols-outlined text-[16px]">error</span>
                    <span>${escapeHtml(data.message || 'Gagal memproses jawaban.')}</span>
                </div>
            `;
            if (deskContentBox) deskContentBox.innerHTML = errHtml;
            if (mobContentBox) mobContentBox.innerHTML = errHtml;
        }
    } catch (err) {
        const errHtml = `
            <div class="flex items-center gap-2 text-xs text-danger-ink bg-danger-soft border border-danger/30 rounded-xl p-3">
                <span class="material-symbols-outlined text-[16px]">error</span>
                <span>Gagal menghubungi server AI (${escapeHtml(err.message || 'Koneksi terputus')}). Pastikan server web aktif.</span>
            </div>
        `;
        if (deskContentBox) deskContentBox.innerHTML = errHtml;
        if (mobContentBox) mobContentBox.innerHTML = errHtml;
    } finally {
        if (inputDesk) {
            inputDesk.disabled = false;
            if (window.innerWidth >= 768) inputDesk.focus();
        }
        if (inputMob) {
            inputMob.disabled = false;
            if (window.innerWidth < 768) inputMob.focus();
        }
        if (btnDesk) btnDesk.disabled = false;
        if (btnMob) btnMob.disabled = false;
        scrollToBottom();
    }
}

function tampilkanErrorChat(teks) {
    const container = document.getElementById('messages-container');
    const mobContainer = document.getElementById('mobile-messages-container');
    const errEl = document.createElement('div');
    errEl.className = 'flex items-center gap-2 text-xs text-danger-ink bg-danger-soft border border-danger/30 rounded-xl p-3 max-w-[85%]';
    errEl.innerHTML = `<span class="material-symbols-outlined text-[16px]">error</span><span>${escapeHtml(teks)}</span>`;
    if (container) container.appendChild(errEl);
    if (mobContainer) mobContainer.appendChild(errEl.cloneNode(true));
    scrollToBottom();
}

function tambahSesiKeSidebar(id, judul) {
    // 1. Tambah ke desktop sidebar
    const recentsList = document.getElementById('recents-list');
    const emptyNotice = document.getElementById('empty-history-notice');
    if (emptyNotice) emptyNotice.remove();

    document.querySelectorAll('.session-item').forEach(el => {
        el.classList.remove('bg-surface', 'border', 'border-line/80', 'font-bold', 'shadow-2xs', 'bg-slate-200', 'text-slate-900', 'border-slate-300');
        el.classList.add('text-ink/75');
        const icon = el.querySelector('.session-icon');
        if (icon) {
            icon.classList.remove('text-brand', 'text-slate-700');
            icon.classList.add('text-muted');
        }
    });

    if (recentsList) {
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

    // 2. Tambah ke mobile horizontal recent cards (Gambar 1)
    const mobileCardsContainer = document.getElementById('mobile-recent-cards');
    if (mobileCardsContainer) {
        updateMobileCardHighlight(null);

        const newCard = document.createElement('button');
        newCard.type = 'button';
        newCard.setAttribute('data-session-card-id', id);
        newCard.onclick = () => pilihSession(id);
        newCard.className = 'mobile-session-card h-9 rounded-full px-4 flex items-center justify-center shrink-0 text-left transition-all cursor-pointer active:scale-95 whitespace-nowrap bg-brand text-white shadow-xs';
        newCard.style.backgroundColor = '#116a3a';
        newCard.style.color = '#ffffff';
        newCard.innerHTML = `
            <span class="font-semibold text-xs truncate max-w-[150px] mobile-card-title">${escapeHtml(judul)}</span>
        `;
        const btnBaru = mobileCardsContainer.firstElementChild;
        if (btnBaru && btnBaru.nextSibling) {
            mobileCardsContainer.insertBefore(newCard, btnBaru.nextSibling);
        } else {
            mobileCardsContainer.appendChild(newCard);
        }
    }

    // 3. Tambah ke mobile history list (Gambar 3)
    const histUnpinnedList = document.getElementById('history-unpinned-list');
    if (histUnpinnedList) {
        const histItem = document.createElement('div');
        histItem.setAttribute('data-history-item', '');
        histItem.setAttribute('data-session-id', id);
        histItem.setAttribute('data-is-pinned', '0');
        histItem.setAttribute('data-is-today', '1');
        histItem.className = 'flex items-center justify-between gap-3 p-3 rounded-2xl bg-white hover:bg-surface/60 border border-line/80 hover:border-brand/40 transition-all shadow-2xs cursor-pointer group';
        histItem.onclick = () => pilihSessionDanTutupHistory(id);
        histItem.innerHTML = `
            <div class="flex items-center gap-3 min-w-0 flex-1">
                <div class="w-10 h-10 rounded-full bg-[#F4F7F5] text-muted grid place-items-center shrink-0 shadow-2xs" style="background-color: #F4F7F5;">
                    <span class="material-symbols-outlined text-[18px]">chat</span>
                </div>
                <div class="min-w-0 flex-1">
                    <h5 class="font-bold text-xs text-ink truncate">${escapeHtml(judul)}</h5>
                    <p class="text-[11px] text-muted truncate mt-0.5">Sesi baru dibuat.</p>
                </div>
            </div>
            <div class="flex flex-col items-end gap-1 shrink-0 select-none">
                <span class="text-[10px] text-muted font-medium">Hari ini</span>
                <div class="relative" onclick="event.stopPropagation()">
                    <button type="button" onclick="toggleHistoryDropdown(${id}, event)" class="p-1 text-muted hover:text-ink rounded-lg hover:bg-surface-2 transition cursor-pointer" title="Menu Sesi">
                        <span class="material-symbols-outlined text-[18px]">more_vert</span>
                    </button>
                    <div id="history-dropdown-${id}" class="history-dropdown-menu hidden absolute right-0 bottom-full mb-1 w-36 bg-white rounded-xl shadow-lg border border-line py-1 z-30">
                        <button type="button" onclick="togglePinSession(${id}, event); tutupSemuaHistoryDropdown();" class="w-full px-3 py-2 text-left text-xs text-ink hover:bg-surface flex items-center gap-2 cursor-pointer">
                            <span class="material-symbols-outlined text-[16px] text-brand">push_pin</span>
                            <span>Sematkan</span>
                        </button>
                        <button type="button" onclick="hapusSession(${id}, event); tutupSemuaHistoryDropdown();" class="w-full px-3 py-2 text-left text-xs text-danger hover:bg-danger-soft/30 flex items-center gap-2 cursor-pointer">
                            <span class="material-symbols-outlined text-[16px] text-danger">delete</span>
                            <span>Hapus Sesi</span>
                        </button>
                    </div>
                </div>
            </div>
        `;
        histUnpinnedList.insertBefore(histItem, histUnpinnedList.firstChild);
    }
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
                    // Hapus dari desktop
                    const elDesk = document.getElementById(`session-item-${id}`);
                    if (elDesk) elDesk.remove();
                    // Hapus dari mobile card
                    const elCard = document.querySelector(`.mobile-session-card[data-session-card-id="${id}"]`);
                    if (elCard) elCard.remove();
                    // Hapus dari mobile history
                    const elHist = document.querySelector(`#mobile-history-view [data-session-id="${id}"]`);
                    if (elHist) elHist.remove();

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
    // Cegah native submit pada input teks desktop dengan tombol Enter
    const inputPesanDesk = document.getElementById('input-pesan');
    if (inputPesanDesk) {
        inputPesanDesk.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                handleFormSubmit(e);
            }
        });
    }

    // Cegah native submit pada input teks mobile dengan tombol Enter
    const inputPesanMob = document.getElementById('mobile-input-pesan');
    if (inputPesanMob) {
        inputPesanMob.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                handleMobileFormSubmit(e);
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
