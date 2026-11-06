@php
    $user = auth()->user();
    $farmNav = $user?->activeFarm() ?? $user?->farm;
    $userFarms = $user ? $user->farms()->orderBy('id')->get() : collect();
    $presetKota = \App\Services\WeatherService::PRESET_KOTA;
    $unreadAlertsCount = 0;
    $modalAlerts = collect();
    $stokGudangNav = ['total_kg' => 0, 'kritis_count' => 0, 'kritis_items' => []];
    $kritisStokCount = 0;
    if ($farmNav) {
        $unreadAlertsCount = $farmNav->alerts()->where('dibaca', false)->count();
        $modalAlerts = $farmNav->alerts()->latest('id')->limit(8)->get();
        $stokGudangNav = $farmNav->ringkasanStokGudang();
        $kritisStokCount = $stokGudangNav['kritis_count'] ?? 0;
    }
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover, interactive-widget=resizes-content">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="RansumKu: alat bantu hitung ransum ayam petelur, buku kandang digital, dan asisten cerdas untuk peternak kecil.">
    <meta name="theme-color" content="#002415">
    <title>{{ $unreadAlertsCount > 0 ? "({$unreadAlertsCount}) " : '' }}@yield('judul', 'Dashboard') · RansumKu</title>
    <link rel="icon" type="image/png" href="{{ asset('images/logo.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0&display=swap">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-[#f2f7f3] text-ink antialiased">

{{-- 1. Sidebar Desktop --}}
<aside class="hidden lg:flex fixed inset-y-0 left-0 w-64 flex-col bg-white border-r border-line p-5 z-40">
    {{-- Logo RansumKu --}}
    <a href="{{ route('beranda') }}" class="flex items-center gap-3 px-2 mb-6 group">
        <img src="{{ asset('images/logo.png') }}" alt="Logo RansumKu" class="w-11 h-11 object-contain rounded-full shadow-sm group-hover:scale-105 transition-transform shrink-0" />
        <div>
            <div class="flex items-center gap-1.5">
               <span class="text-2xl font-black tracking-tight text-brand">RansumKu</span>
               <span class="w-2 h-2 rounded-full bg-lime"></span>
            </div>
            <span class="block text-[9px] font-bold tracking-widest text-muted uppercase">SMART POULTRY FARM</span>
        </div>
    </a>

    {{-- Menu Group 1: MENU UTAMA --}}
    <div class="mb-5">
        <span class="px-3 text-[11px] font-black uppercase tracking-wider text-brand block mb-2">MENU UTAMA</span>
        <nav class="space-y-1">
            <a href="{{ route('beranda') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-lg text-sm font-semibold transition {{ request()->routeIs('beranda') ? 'bg-surface text-ink font-bold' : 'text-muted hover:bg-surface hover:text-ink' }}">
                <span class="material-symbols-outlined text-[20px] {{ request()->routeIs('beranda') ? 'text-brand' : '' }}">dashboard</span>
                <span>Dashboard</span>
            </a>
            <a href="{{ route('kontroler.index') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-lg text-sm font-semibold transition {{ request()->routeIs('kontroler.*') ? 'bg-surface text-ink font-bold' : 'text-muted hover:bg-surface hover:text-ink' }}">
                <span class="material-symbols-outlined text-[20px] {{ request()->routeIs('kontroler.*') ? 'text-brand' : '' }}">tune</span>
                <span>Smart Controller</span>
            </a>
            <a href="{{ route('catatan.index') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-lg text-sm font-semibold transition {{ request()->routeIs('catatan.*') ? 'bg-surface text-ink font-bold' : 'text-muted hover:bg-surface hover:text-ink' }}">
                <span class="material-symbols-outlined text-[20px] {{ request()->routeIs('catatan.*') ? 'text-brand' : '' }}">menu_book</span>
                <span>Buku Kandang</span>
            </a>
            <a href="{{ route('ransum.index') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-lg text-sm font-semibold transition {{ request()->routeIs('ransum.*') ? 'bg-surface text-ink font-bold' : 'text-muted hover:bg-surface hover:text-ink' }}">
                <span class="material-symbols-outlined text-[20px] {{ request()->routeIs('ransum.*') ? 'text-brand' : '' }}">calculate</span>
                <span>Peracik Ransum</span>
            </a>
            <a href="{{ route('laporan.index') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-lg text-sm font-semibold transition {{ request()->routeIs('laporan.*') ? 'bg-surface text-ink font-bold' : 'text-muted hover:bg-surface hover:text-ink' }}">
                <span class="material-symbols-outlined text-[20px] {{ request()->routeIs('laporan.*') ? 'text-brand' : '' }}">assessment</span>
                <span>Laporan Sistem</span>
            </a>
            <a href="{{ route('asisten.index') }}" class="flex items-center justify-between px-3.5 py-2.5 rounded-lg text-sm font-semibold transition {{ request()->routeIs('asisten.*') ? 'bg-surface text-ink font-bold' : 'text-muted hover:bg-surface hover:text-ink' }}">
                <div class="flex items-center gap-3">
                    <span class="material-symbols-outlined text-[20px] {{ request()->routeIs('asisten.*') ? 'text-brand' : '' }}">psychology</span>
                    <span>Asisten Cerdas</span>
                </div>
                <span data-notif-count class="{{ $unreadAlertsCount > 0 ? 'flex' : 'hidden' }} w-5 h-5 rounded-full bg-rose-600 text-white text-[10px] font-black items-center justify-center shrink-0 shadow-xs ring-2 ring-white">{{ $unreadAlertsCount > 9 ? '9+' : $unreadAlertsCount }}</span>
            </a>
        </nav>
    </div>

    {{-- Menu Group 2: PENGATURAN & MASTER --}}
    <div class="mb-5">
        <span class="px-3 text-[11px] font-black uppercase tracking-wider text-brand block mb-2">PENGATURAN</span>
        <nav class="space-y-1">
            <a href="{{ route('kandang.index') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-lg text-sm font-semibold transition {{ request()->routeIs('kandang.*') ? 'bg-surface text-ink font-bold' : 'text-muted hover:bg-surface hover:text-ink' }}">
                <span class="material-symbols-outlined text-[20px] {{ request()->routeIs('kandang.*') ? 'text-brand' : '' }}">warehouse</span>
                <span>Kelola Kandang</span>
            </a>
            <a href="{{ route('bahan.index') }}" class="flex items-center justify-between px-3.5 py-2.5 rounded-lg text-sm font-semibold transition {{ request()->routeIs('bahan.*') ? 'bg-surface text-ink font-bold' : 'text-muted hover:bg-surface hover:text-ink' }}">
                <div class="flex items-center gap-3">
                    <span class="material-symbols-outlined text-[20px] {{ request()->routeIs('bahan.*') ? 'text-brand' : '' }}">inventory_2</span>
                    <span>Stok Pakan Gudang</span>
                </div>
                @if ($kritisStokCount > 0)
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-amber-500 text-white leading-tight shadow-2xs" title="{{ $kritisStokCount }} bahan di bawah safety stock">
                        {{ $kritisStokCount }}
                    </span>
                @endif
            </a>
            <a href="{{ route('tentang') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-lg text-sm font-semibold transition {{ request()->routeIs('tentang') ? 'bg-surface text-ink font-bold' : 'text-muted hover:bg-surface hover:text-ink' }}">
                <span class="material-symbols-outlined text-[20px] {{ request()->routeIs('tentang') ? 'text-brand' : '' }}">info</span>
                <span>Tentang RansumKu</span>
            </a>
        </nav>
    </div>

    {{-- Tombol Logout / Keluar di Bagian Bawah Sidebar (Merah Transparan Modern Tanpa Info Farm Redundan) --}}
    <div class="mt-auto pt-4 border-t border-line/70">
        <form method="POST" action="{{ route('keluar') }}" data-confirm="Apakah Anda yakin ingin keluar dari akun RansumKu?" data-confirm-title="Keluar dari Akun" data-confirm-btn="Ya, Keluar">
            @csrf
            <button type="submit" class="w-full flex items-center justify-center gap-2 px-3.5 py-2.5 rounded-xl bg-rose-500/10 hover:bg-rose-500/20 text-rose-600 hover:text-rose-700 border border-rose-500/20 font-bold text-xs transition-all duration-200 cursor-pointer shadow-2xs hover:shadow-xs active:scale-[0.98] group" title="Keluar dari sesi akun">
                <span class="material-symbols-outlined text-[18px] transition-transform duration-200 group-hover:-translate-x-0.5">logout</span>
                <span>Keluar</span>
            </button>
        </form>
    </div>
</aside>

{{-- 2. Top Bar Desktop / Navbar (Solid White Navbar dengan Breadcrumb & Pill Badges) --}}
<header class="hidden lg:flex fixed top-0 left-64 right-0 h-16 bg-white border-b border-line shadow-2xs z-50 px-8 items-center justify-between">
    {{-- Sisi Kiri: Breadcrumb / Judul Halaman Aktif (Menggantikan searchbar) --}}
    <div class="flex items-center">
        <span class="text-sm font-extrabold text-ink tracking-tight">@yield('judul', 'Dashboard')</span>
    </div>

    {{-- Sisi Kanan: Lonceng Notifikasi & Akun Pill Badge --}}
    <div class="flex items-center gap-2.5">

        {{-- Ikon Lonceng Notifikasi Navbar (Dropdown Popover Tepat di Bawah Ikon, Tanpa Blur) --}}
        @include('partials.dropdown-notifikasi', ['idDropdown' => 'notifikasi-popover-desktop', 'isMobile' => false])

        {{-- Akun Pill Badge --}}
        <a href="{{ route('kandang.index') }}" class="flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-surface hover:bg-surface-2 border border-line text-xs font-semibold text-ink shadow-2xs transition cursor-pointer" title="Kelola Multi-Kandang">
            <span class="material-symbols-outlined text-[18px] text-brand">person</span>
            <span>{{ auth()->user()->name ?? 'Peternak' }}</span>
        </a>
    </div>
</header>

{{-- 3. Header Mobile --}}
<header class="lg:hidden fixed top-0 inset-x-0 z-50 bg-white/95 backdrop-blur-xl border-b border-line">
    <div class="h-16 px-2.5 sm:px-4 flex items-center justify-between max-w-lg mx-auto">
        {{-- Sisi Kiri: Logo Ayam & Tab Kandang Aktif (Sesuai Gambar Referensi 2) --}}
        <div class="flex items-center gap-2 min-w-0 pr-1">
            <a href="{{ route('beranda') }}" class="flex items-center shrink-0 select-none group" aria-label="Beranda RansumKu">
                <img src="{{ asset('images/logo.png') }}" alt="Logo RansumKu" class="w-9 h-9 object-contain rounded-xl shadow-2xs group-hover:scale-105 transition-transform" />
            </a>

            @if(isset($farmNav) && $farmNav)
                @php
                    $presetHeader = $presetKota[$farmNav->lokasi_kota ?? 'Blitar'] ?? null;
                    $provinsiHeader = $presetHeader['provinsi'] ?? 'Jawa Timur';
                    $kotaHeader = $presetHeader['nama'] ?? ($farmNav->lokasi_kota ?? 'Blitar');
                @endphp
                <button type="button" 
                        id="btn-nav-kandang-mobile"
                        onclick="bukaModal('modal-pilih-kandang-mobile')" 
                        class="flex items-center gap-1 text-left focus:outline-none transition-all duration-300 ease-in-out min-w-0 select-none cursor-pointer group py-0.5 px-1 rounded-xl hover:bg-surface/60 active:scale-95 opacity-100 pointer-events-auto translate-x-0 scale-100"
                        title="Pilih Kandang Aktif">
                    <div class="min-w-0">
                        <div class="flex items-center gap-0.5">
                            <span class="text-xs sm:text-sm font-extrabold text-ink truncate group-hover:text-brand transition-colors leading-tight max-w-[125px] sm:max-w-[180px]">
                                {{ $farmNav->nama }}
                            </span>
                            <span class="material-symbols-outlined text-[17px] text-ink/70 group-hover:text-brand transition-transform shrink-0">expand_more</span>
                        </div>
                        <span class="text-[10px] text-muted truncate block font-medium leading-none mt-0.5 max-w-[135px] sm:max-w-[190px]">
                            {{ $kotaHeader }}, {{ $provinsiHeader }}
                        </span>
                    </div>
                </button>
            @endif
        </div>

        {{-- Sisi Kanan: Kapsul Dinamis Notifikasi, Menu & Akun (Dynamic Island Segmented Style) --}}
        <div class="flex items-center gap-2 shrink-0">
            {{-- 1. Kapsul Notifikasi --}}
            @include('partials.dropdown-notifikasi', ['idDropdown' => 'notifikasi-popover-mobile', 'isMobile' => true])

            {{-- 2. Kapsul Menu --}}
            <button type="button" 
                    id="btn-capsule-menu"
                    onclick="toggleMenuMobile(event)" 
                    class="header-capsule-btn" 
                    aria-label="Buka Menu Lengkap" 
                    title="Menu Lengkap">
                {{-- Icon Wrapper: Always exact 36px x 36px --}}
                <span class="capsule-icon-wrap">
                    <span class="material-symbols-outlined text-[19px] shrink-0 leading-none select-none">widgets</span>
                </span>

                {{-- Expanding Label Wrapper --}}
                <span class="capsule-label-wrap">
                    <span class="capsule-label-text">Menu</span>
                </span>
            </button>

            {{-- 3. Kapsul Akun / Logout Popover --}}
            <div class="relative inline-block text-left custom-dropdown-container z-50" id="akun-popover-mobile">
                <button type="button" 
                        id="btn-capsule-akun"
                        onclick="toggleAkunMobile(event)" 
                        class="dropdown-trigger header-capsule-btn" 
                        title="Profil & Keluar Akun"
                        aria-haspopup="true"
                        aria-expanded="false"
                        aria-label="Menu Akun">
                    <span class="capsule-icon-wrap">
                        <span class="material-symbols-outlined text-[19px] shrink-0 leading-none select-none">person</span>
                    </span>
                    <span class="capsule-label-wrap">
                        <span class="capsule-label-text">Akun</span>
                    </span>
                </button>

                {{-- Popover Dropdown Opsi Logout --}}
                <div class="dropdown-menu hidden absolute right-0 top-full mt-2 w-56 max-w-[calc(100vw-2rem)] rounded-2xl bg-white border border-line shadow-2xl p-2.5 z-50 select-none custom-dropdown-menu">
                    <div class="px-2.5 py-2 mb-2 border-b border-line flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-full bg-brand/10 text-brand grid place-items-center font-bold text-xs shrink-0">
                            {{ strtoupper(substr(auth()->user()->name ?? 'P', 0, 1)) }}
                        </div>
                        <div class="min-w-0 flex-1">
                            <span class="text-xs font-extrabold text-ink block truncate">{{ auth()->user()->name ?? 'Peternak' }}</span>
                            <span class="text-[10px] text-muted truncate block">{{ auth()->user()->email ?? 'Akun Peternak' }}</span>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('keluar') }}" data-confirm="Apakah Anda yakin ingin keluar dari akun RansumKu?" data-confirm-title="Keluar dari Akun" data-confirm-btn="Ya, Keluar">
                        @csrf
                        <button type="submit" class="w-full flex items-center justify-center gap-2 px-3 py-2 rounded-xl bg-rose-50 hover:bg-rose-100 text-rose-600 font-bold text-xs transition cursor-pointer active:scale-95">
                            <span class="material-symbols-outlined text-[17px]">logout</span>
                            <span>Keluar dari Akun</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</header>

{{-- 4. Main Content Area --}}
<main class="lg:ml-64 pt-20 lg:pt-20 {{ request()->routeIs('asisten.*') ? 'pb-24 lg:pb-2.5 lg:h-screen lg:max-h-screen lg:flex lg:flex-col lg:overflow-hidden box-border' : 'pb-28 lg:pb-12' }} px-2.5 sm:px-4 lg:px-8 @yield('main_class')">
    <div class="w-full {{ request()->routeIs('asisten.*') ? 'lg:flex-1 lg:min-h-0 lg:flex lg:flex-col' : 'space-y-5' }} @yield('container_class')">
        @yield('isi')
    </div>
</main>

{{-- 5. Floating Curved Bottom Navigation Bar (Mobile - Gojek Style with Symmetrical 3x3) --}}
<nav id="mobile-bottom-nav" class="lg:hidden fixed bottom-2.5 inset-x-2.5 sm:inset-x-6 max-w-lg mx-auto z-40 select-none pointer-events-none transition-all duration-200 transform transform-gpu will-change-transform" style="bottom: calc(0.6rem + env(safe-area-inset-bottom, 0px));" aria-label="Navigasi Utama Mobile">
    <div class="relative w-full h-[64px] pointer-events-auto filter drop-shadow-[0_6px_16px_rgba(0,36,21,0.08)]">
        
        {{-- Background Layer with Seamless Center Notch --}}
        <div class="absolute inset-0 flex items-stretch pointer-events-none" aria-hidden="true">
            {{-- Sayap Kiri --}}
            <div class="flex-1 bg-white rounded-l-[32px] border-y border-l border-line -mr-[1px]"></div>
            
            {{-- Bridge Lekukan Tengah (Concave Scoop Mengikuti Bentuk Lingkaran Ikon Ayam) --}}
            <div class="w-[88px] h-[64px] shrink-0 relative">
                <svg class="w-[88px] h-[64px] block" viewBox="0 0 88 64" fill="none">
                    {{-- White Bar Fill with Deep Circular Cutout Scoop --}}
                    <path d="M 0,0 C 8,0 12,1 15,5 C 18,10 22,21 30,28 C 34,31.5 39,32 44,32 C 49,32 54,31.5 58,28 C 66,21 70,10 73,5 C 76,1 80,0 88,0 L 88,64 L 0,64 Z" fill="#FFFFFF" />
                    {{-- Top Border Stroke that Follows the Concave Contour --}}
                    <path d="M 0,0.5 C 8,0.5 12,1.5 15,5.5 C 18,10.5 22,21.5 30,28.5 C 34,31.5 39,32.5 44,32.5 C 49,32.5 54,31.5 58,28.5 C 66,21.5 70,10.5 73,5.5 C 76,1.5 80,0.5 88,0.5" stroke="#E0EBE4" stroke-width="1.2" />
                    {{-- Bottom Border Line --}}
                    <line x1="0" y1="63.5" x2="88" y2="63.5" stroke="#E0EBE4" stroke-width="1" />
                </svg>
            </div>
            
            {{-- Sayap Kanan --}}
            <div class="flex-1 bg-white rounded-r-[32px] border-y border-r border-line -ml-[1px]"></div>
        </div>

        {{-- Elevated Center Green Action Button (Catat Data Harian) --}}
        <button type="button" 
                onclick="bukaModal('modal-catat-harian')" 
                class="absolute left-1/2 -translate-x-1/2 -top-6 w-12 h-12 rounded-full bg-brand hover:bg-brand-dark text-white flex items-center justify-center shadow-[0_6px_18px_rgba(17,106,58,0.38)] active:scale-90 transition-all duration-150 cursor-pointer pointer-events-auto z-30 group" 
                aria-label="Catat Data Harian" 
                title="Catat Data Harian">
            <svg class="w-6 h-6 text-white group-hover:scale-110 transition-transform" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                <path d="m19,5c0,.552-.448,1-1,1s-1-.448-1-1,.448-1,1-1,1,.448,1,1Zm5,3l-2,.857v2.143c0,4.653-2.909,8.631-7,10.236v2.264c0,.276-.224.5-.5.5s-.5-.224-.5-.5v-1.926c-.955.271-1.959.426-3,.426v1.5c0,.276-.224.5-.5.5s-.5-.224-.5-.5v-1.551C4.402,21.442,0,16.728,0,11v-4.5c0-.276.224-.5.5-.5s.5.224.5.5c0,2.481,2.019,4.5,4.5,4.5s4.5-2.019,4.5-4.5v-.5c0-3.309,2.691-6,6-6s6,2.691,6,6v.143l2,.857v1ZM1,11c0,2.252.758,4.326,2.019,6h1.981c2.757,0,5-2.243,5-5v-2.348c-.996,1.417-2.64,2.348-4.5,2.348s-3.504-.93-4.5-2.348v1.348Zm20-5c0-2.757-2.243-5-5-5s-5,2.243-5,5v6c0,3.309-2.691,6-6,6h-1.129c1.816,1.849,4.339,3,7.129,3,5.514,0,10-4.486,10-10v-5Z"/>
            </svg>
        </button>

        {{-- Interactive Navigation Items Layer (Simetris 5 Kolom Persis Seperti Gojek) --}}
        <div id="mobile-nav-bar" class="relative z-20 w-full h-[64px] grid grid-cols-5 items-stretch px-1 sm:px-2">
            {{-- 1. Dashboard --}}
            <a href="{{ route('beranda') }}" class="group flex flex-col items-center justify-center h-full transition-all" aria-label="Dashboard" title="Dashboard">
                <div class="w-12 h-7 rounded-full flex items-center justify-center transition-all duration-200 {{ request()->routeIs('beranda') ? 'bg-brand/12 text-brand font-bold' : 'bg-transparent text-[#88998F] group-hover:text-ink' }}">
                    <span class="material-symbols-outlined text-[20px] leading-none transition-colors {{ request()->routeIs('beranda') ? 'icon-fill' : '' }}">dashboard</span>
                </div>
                <span class="text-[10px] leading-none tracking-tight mt-[3px] transition-colors {{ request()->routeIs('beranda') ? 'text-brand font-bold' : 'text-[#88998F] font-semibold group-hover:text-ink' }}">Dashboard</span>
            </a>

            {{-- 2. Kandang --}}
            <a href="{{ route('catatan.index') }}" class="group flex flex-col items-center justify-center h-full transition-all" aria-label="Kandang" title="Kandang">
                <div class="w-12 h-7 rounded-full flex items-center justify-center transition-all duration-200 {{ request()->routeIs('catatan.*') ? 'bg-brand/12 text-brand font-bold' : 'bg-transparent text-[#88998F] group-hover:text-ink' }}">
                    <span class="material-symbols-outlined text-[20px] leading-none transition-colors {{ request()->routeIs('catatan.*') ? 'icon-fill' : '' }}">menu_book</span>
                </div>
                <span class="text-[10px] leading-none tracking-tight mt-[3px] transition-colors {{ request()->routeIs('catatan.*') ? 'text-brand font-bold' : 'text-[#88998F] font-semibold group-hover:text-ink' }}">Kandang</span>
            </a>

            {{-- 3. Ruang Tengah dengan Label 'Catat' di bawah tombol ayam --}}
            <div class="h-full flex flex-col items-center justify-end pb-[11.5px] pointer-events-none z-20">
                <span class="text-[10px] leading-none font-bold text-brand tracking-tight">Catat</span>
            </div>

            {{-- 4. Ransum --}}
            <a href="{{ route('ransum.index') }}" class="group flex flex-col items-center justify-center h-full transition-all" aria-label="Peracik Ransum" title="Peracik Ransum">
                <div class="w-12 h-7 rounded-full flex items-center justify-center transition-all duration-200 {{ request()->routeIs('ransum.*') ? 'bg-brand/12 text-brand font-bold' : 'bg-transparent text-[#88998F] group-hover:text-ink' }}">
                    <span class="material-symbols-outlined text-[20px] leading-none transition-colors {{ request()->routeIs('ransum.*') ? 'icon-fill' : '' }}">calculate</span>
                </div>
                <span class="text-[10px] leading-none tracking-tight mt-[3px] transition-colors {{ request()->routeIs('ransum.*') ? 'text-brand font-bold' : 'text-[#88998F] font-semibold group-hover:text-ink' }}">Ransum</span>
            </a>

            {{-- 5. Asisten --}}
            <a href="{{ route('asisten.index') }}" class="group flex flex-col items-center justify-center h-full transition-all" aria-label="Asisten Cerdas AI" title="Asisten Cerdas AI">
                <div class="relative w-12 h-7 rounded-full flex items-center justify-center transition-all duration-200 {{ request()->routeIs('asisten.*') ? 'bg-brand/12 text-brand font-bold' : 'bg-transparent text-[#88998F] group-hover:text-ink' }}">
                    <span class="material-symbols-outlined text-[20px] leading-none transition-colors {{ request()->routeIs('asisten.*') ? 'icon-fill' : '' }}">psychology</span>
                    <span data-notif-count class="{{ $unreadAlertsCount > 0 ? 'flex' : 'hidden' }} absolute -top-1 -right-1 h-3.5 min-w-3.5 px-0.5 items-center justify-center rounded-full bg-rose-600 text-white text-[8px] font-black shadow-xs ring-1 ring-white">
                        {{ $unreadAlertsCount > 9 ? '9+' : $unreadAlertsCount }}
                    </span>
                </div>
                <span class="text-[10px] leading-none tracking-tight mt-[3px] transition-colors {{ request()->routeIs('asisten.*') ? 'text-brand font-bold' : 'text-[#88998F] font-semibold group-hover:text-ink' }}">Asisten</span>
            </a>
        </div>
    </div>
</nav>

{{-- Modal Lengkap Mobile, Modal Catat Harian, Modal Multi-Kandang, Konfirmasi Universal, & Toast Notifikasi --}}
@include('partials.modal-pilih-kandang', ['farmNav' => $farmNav, 'userFarms' => $userFarms, 'presetKota' => $presetKota])
@include('partials.modal-menu-mobile', ['farmNav' => $farmNav])
@include('partials.modal-catat-harian', ['farm' => $farmNav])
@include('partials.confirm-modal')
@include('partials.toast-popup')

</body>
</html>
