<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="description" content="RansumKu: alat bantu hitung ransum ayam petelur, buku kandang digital, dan asisten cerdas untuk peternak kecil.">
    <meta name="theme-color" content="#002415">
    <title>@yield('judul', 'Dashboard') · RansumKu</title>
    <link rel="icon" type="image/png" href="{{ asset('images/logo.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0&display=swap">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-[#EBF1EB] text-ink antialiased">
@php
    $farmNav = auth()->user()?->activeFarm() ?? auth()->user()?->farm;
    $unreadAlertsCount = 0;
    if ($farmNav) {
        $unreadAlertsCount = $farmNav->alerts()->where('dibaca', false)->count();
    }
@endphp

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
            <a href="{{ route('catatan.index') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-lg text-sm font-semibold transition {{ request()->routeIs('catatan.*') ? 'bg-surface text-ink font-bold' : 'text-muted hover:bg-surface hover:text-ink' }}">
                <span class="material-symbols-outlined text-[20px] {{ request()->routeIs('catatan.*') ? 'text-brand' : '' }}">description</span>
                <span>Buku Kandang</span>
            </a>
            <a href="{{ route('ransum.index') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-lg text-sm font-semibold transition {{ request()->routeIs('ransum.*') ? 'bg-surface text-ink font-bold' : 'text-muted hover:bg-surface hover:text-ink' }}">
                <span class="material-symbols-outlined text-[20px] {{ request()->routeIs('ransum.*') ? 'text-brand' : '' }}">calculate</span>
                <span>Peracik Ransum</span>
            </a>
            <a href="{{ route('asisten.index') }}" class="flex items-center justify-between px-3.5 py-2.5 rounded-lg text-sm font-semibold transition {{ request()->routeIs('asisten.*') ? 'bg-surface text-ink font-bold' : 'text-muted hover:bg-surface hover:text-ink' }}">
                <div class="flex items-center gap-3">
                    <span class="material-symbols-outlined text-[20px] {{ request()->routeIs('asisten.*') ? 'text-brand' : '' }}">smart_toy</span>
                    <span>Asisten Cerdas</span>
                </div>
                @if ($unreadAlertsCount > 0)
                    <span class="w-5 h-5 rounded-full bg-rose-600 text-white text-[10px] font-black flex items-center justify-center shrink-0 shadow-xs ring-2 ring-white">{{ $unreadAlertsCount > 9 ? '9+' : $unreadAlertsCount }}</span>
                @endif
            </a>
        </nav>
    </div>

    {{-- Menu Group 2: PENGATURAN & MASTER --}}
    <div class="mb-5">
        <span class="px-3 text-[11px] font-black uppercase tracking-wider text-brand block mb-2">PENGATURAN</span>
        <nav class="space-y-1">
            <a href="{{ route('kandang.index') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-lg text-sm font-semibold transition {{ request()->routeIs('kandang.*') ? 'bg-surface text-ink font-bold' : 'text-muted hover:bg-surface hover:text-ink' }}">
                <span class="material-symbols-outlined text-[20px] {{ request()->routeIs('kandang.*') ? 'text-brand' : '' }}">cottage</span>
                <span>Kelola Kandang</span>
            </a>
            <a href="{{ route('bahan.index') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-lg text-sm font-semibold transition {{ request()->routeIs('bahan.*') ? 'bg-surface text-ink font-bold' : 'text-muted hover:bg-surface hover:text-ink' }}">
                <span class="material-symbols-outlined text-[20px] {{ request()->routeIs('bahan.*') ? 'text-brand' : '' }}">inventory_2</span>
                <span>Bahan Pakan</span>
            </a>
            <a href="{{ route('acuan.index') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-lg text-sm font-semibold transition {{ request()->routeIs('acuan.*') ? 'bg-surface text-ink font-bold' : 'text-muted hover:bg-surface hover:text-ink' }}">
                <span class="material-symbols-outlined text-[20px] {{ request()->routeIs('acuan.*') ? 'text-brand' : '' }}">fact_check</span>
                <span>Acuan Gizi (SNI)</span>
            </a>
            <a href="{{ route('tentang') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-lg text-sm font-semibold transition {{ request()->routeIs('tentang') ? 'bg-surface text-ink font-bold' : 'text-muted hover:bg-surface hover:text-ink' }}">
                <span class="material-symbols-outlined text-[20px] {{ request()->routeIs('tentang') ? 'text-brand' : '' }}">help_outline</span>
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
<header class="hidden lg:flex fixed top-0 left-64 right-0 h-16 bg-white border-b border-line shadow-2xs z-30 px-8 items-center justify-between">
    {{-- Sisi Kiri: Breadcrumb / Judul Halaman Aktif (Menggantikan searchbar) --}}
    <div class="flex items-center gap-2.5">
        <span class="text-sm font-extrabold text-ink tracking-tight">@yield('judul', 'Dashboard')</span>
        <span class="text-muted/60 text-xs">•</span>
        <span class="text-xs font-medium text-muted">{{ $farmNav->nama ?? 'Kandang Maju Jaya' }}</span>
    </div>

    {{-- Sisi Kanan: Lonceng Notifikasi & Akun Pill Badge --}}
    <div class="flex items-center gap-2.5">

        {{-- Ikon Lonceng Notifikasi (Opsi A: Direct Link ke Pusat Peringatan dengan Unread Counter) --}}
        <a href="{{ route('asisten.index', ['tab' => 'peringatan']) }}"
           class="relative w-9 h-9 rounded-full bg-surface hover:bg-surface-2 border border-line flex items-center justify-center text-ink hover:text-brand transition shadow-2xs group cursor-pointer"
           title="Pusat Peringatan & Notifikasi Kandang ({{ $unreadAlertsCount }} belum dibaca)">
            <span class="material-symbols-outlined text-[19px] transition-transform group-hover:scale-110">notifications</span>
            @if ($unreadAlertsCount > 0)
                <span class="absolute -top-1 -right-1 flex h-4 min-w-4 px-1 items-center justify-center rounded-full bg-rose-600 text-white text-[9px] font-black shadow-sm ring-2 ring-white">
                    {{ $unreadAlertsCount > 9 ? '9+' : $unreadAlertsCount }}
                </span>
            @endif
        </a>

        {{-- Akun Pill Badge --}}
        <a href="{{ route('kandang.index') }}" class="flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-surface hover:bg-surface-2 border border-line text-xs font-semibold text-ink shadow-2xs transition cursor-pointer" title="Kelola Multi-Kandang">
            <span class="material-symbols-outlined text-[18px] text-brand">person</span>
            <span>{{ auth()->user()->name ?? 'Peternak' }}</span>
        </a>
    </div>
</header>

{{-- 3. Header Mobile --}}
<header class="lg:hidden fixed top-0 inset-x-0 z-40 bg-white/95 backdrop-blur-xl border-b border-line">
    <div class="h-16 px-4 flex items-center justify-between max-w-lg mx-auto">
        <a href="{{ route('beranda') }}" class="flex items-center gap-2.5">
            <img src="{{ asset('images/logo.png') }}" alt="Logo RansumKu" class="w-8 h-8 object-contain rounded-lg shadow-2xs shrink-0" />
            <div class="flex items-baseline gap-1.5">
                <span class="text-xl font-black text-brand">RansumKu</span>
                <span class="text-xs text-muted font-bold">· @yield('judul', 'Dashboard')</span>
            </div>
        </a>
        <div class="flex items-center gap-2">
            <a href="{{ route('asisten.index', ['tab' => 'peringatan']) }}" class="relative w-8 h-8 rounded-full bg-surface border border-line text-ink grid place-items-center cursor-pointer" aria-label="Notifikasi" title="Pusat Peringatan & Notifikasi Kandang ({{ $unreadAlertsCount }} belum dibaca)">
                <span class="material-symbols-outlined text-[18px]">notifications</span>
                @if ($unreadAlertsCount > 0)
                    <span class="absolute -top-1 -right-1 flex h-4 min-w-4 px-1 items-center justify-center rounded-full bg-rose-600 text-white text-[9px] font-black shadow-xs ring-2 ring-white">
                        {{ $unreadAlertsCount > 9 ? '9+' : $unreadAlertsCount }}
                    </span>
                @endif
            </a>
            <a href="{{ route('kandang.index') }}" class="w-8 h-8 rounded-full bg-surface border border-line text-ink grid place-items-center cursor-pointer" aria-label="Kelola Kandang" title="Kelola Kandang">
                <span class="material-symbols-outlined text-[18px]">cottage</span>
            </a>
        </div>
    </div>
</header>

{{-- 4. Main Content Area --}}
<main class="lg:ml-64 pt-20 lg:pt-20 pb-28 lg:pb-12 px-4 lg:px-8">
    <div class="w-full space-y-5">
        @yield('isi')
    </div>
</main>

{{-- 5. Navbar Bawah (Mobile) --}}
<nav class="lg:hidden fixed bottom-0 inset-x-0 z-40 bg-white/95 backdrop-blur-xl border-t border-line shadow-[0_-4px_20px_rgba(0,36,21,0.04)]">
    <div class="flex items-center justify-around h-16 max-w-lg mx-auto">
        <a href="{{ route('beranda') }}" class="flex flex-col items-center justify-center min-w-14 min-h-12 py-1 transition-colors {{ request()->routeIs('beranda') ? 'text-brand font-bold' : 'text-muted' }}">
            <span class="material-symbols-outlined text-[22px] {{ request()->routeIs('beranda') ? 'icon-fill' : '' }}">dashboard</span>
            <span class="text-[10px] mt-0.5">Dashboard</span>
        </a>
        <a href="{{ route('catatan.index') }}" class="flex flex-col items-center justify-center min-w-14 min-h-12 py-1 transition-colors {{ request()->routeIs('catatan.*') ? 'text-brand font-bold' : 'text-muted' }}">
            <span class="material-symbols-outlined text-[22px] {{ request()->routeIs('catatan.*') ? 'icon-fill' : '' }}">description</span>
            <span class="text-[10px] mt-0.5">Produksi</span>
        </a>
        <a href="{{ route('ransum.index') }}" class="flex flex-col items-center justify-center min-w-14 min-h-12 py-1 transition-colors {{ request()->routeIs('ransum.*') ? 'text-brand font-bold' : 'text-muted' }}">
            <span class="material-symbols-outlined text-[22px] {{ request()->routeIs('ransum.*') ? 'icon-fill' : '' }}">calculate</span>
            <span class="text-[10px] mt-0.5">Ransum</span>
        </a>
        <a href="{{ route('asisten.index') }}" class="relative flex flex-col items-center justify-center min-w-14 min-h-12 py-1 transition-colors {{ request()->routeIs('asisten.*') ? 'text-brand font-bold' : 'text-muted' }}">
            <div class="relative inline-flex items-center justify-center">
                <span class="material-symbols-outlined text-[22px] {{ request()->routeIs('asisten.*') ? 'icon-fill' : '' }}">smart_toy</span>
                @if ($unreadAlertsCount > 0)
                    <span class="absolute -top-1 -right-2 flex h-3.5 min-w-3.5 px-0.5 items-center justify-center rounded-full bg-rose-600 text-white text-[8px] font-black shadow-xs ring-1 ring-white">
                        {{ $unreadAlertsCount > 9 ? '9+' : $unreadAlertsCount }}
                    </span>
                @endif
            </div>
            <span class="text-[10px] mt-0.5">Asisten</span>
        </a>
        <a href="{{ route('bahan.index') }}" class="flex flex-col items-center justify-center min-w-14 min-h-12 py-1 transition-colors {{ request()->routeIs('bahan.*|acuan.*|tentang') ? 'text-brand font-bold' : 'text-muted' }}">
            <span class="material-symbols-outlined text-[22px] {{ request()->routeIs('bahan.*|acuan.*|tentang') ? 'icon-fill' : '' }}">inventory_2</span>
            <span class="text-[10px] mt-0.5">Bahan</span>
        </a>
    </div>
</nav>

{{-- Modal Konfirmasi Universal & Toast Notifikasi Mengambang --}}
@include('partials.confirm-modal')
@include('partials.toast-popup')

</body>
</html>
