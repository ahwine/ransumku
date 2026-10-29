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
    $farmNav = auth()->user()?->farm;
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
            <a href="{{ route('beranda') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-full text-sm font-semibold transition {{ request()->routeIs('beranda') ? 'bg-surface text-ink font-bold' : 'text-muted hover:bg-surface hover:text-ink' }}">
                <span class="material-symbols-outlined text-[20px] {{ request()->routeIs('beranda') ? 'text-brand' : '' }}">dashboard</span>
                <span>Dashboard</span>
            </a>
            <a href="{{ route('catatan.index') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-full text-sm font-semibold transition {{ request()->routeIs('catatan.*') ? 'bg-surface text-ink font-bold' : 'text-muted hover:bg-surface hover:text-ink' }}">
                <span class="material-symbols-outlined text-[20px] {{ request()->routeIs('catatan.*') ? 'text-brand' : '' }}">description</span>
                <span>Buku Kandang</span>
            </a>
            <a href="{{ route('ransum.index') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-full text-sm font-semibold transition {{ request()->routeIs('ransum.*') ? 'bg-surface text-ink font-bold' : 'text-muted hover:bg-surface hover:text-ink' }}">
                <span class="material-symbols-outlined text-[20px] {{ request()->routeIs('ransum.*') ? 'text-brand' : '' }}">calculate</span>
                <span>Peracik Ransum</span>
            </a>
            <a href="{{ route('asisten.index') }}" class="flex items-center justify-between px-3.5 py-2.5 rounded-full text-sm font-semibold transition {{ request()->routeIs('asisten.*') ? 'bg-surface text-ink font-bold' : 'text-muted hover:bg-surface hover:text-ink' }}">
                <div class="flex items-center gap-3">
                    <span class="material-symbols-outlined text-[20px] {{ request()->routeIs('asisten.*') ? 'text-brand' : '' }}">smart_toy</span>
                    <span>Asisten Cerdas</span>
                </div>
                @if (($alertCount = $farmNav?->alerts()->where('dibaca', false)->count() ?? 0) > 0)
                    <span class="px-2 py-0.5 rounded-full bg-danger text-white text-[10px] font-bold">{{ $alertCount }}</span>
                @endif
            </a>
        </nav>
    </div>

    {{-- Menu Group 2: PENGATURAN & MASTER --}}
    <div class="mb-5">
        <span class="px-3 text-[11px] font-black uppercase tracking-wider text-brand block mb-2">PENGATURAN</span>
        <nav class="space-y-1">
            <a href="{{ route('bahan.index') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-full text-sm font-semibold transition {{ request()->routeIs('bahan.*') ? 'bg-surface text-ink font-bold' : 'text-muted hover:bg-surface hover:text-ink' }}">
                <span class="material-symbols-outlined text-[20px] {{ request()->routeIs('bahan.*') ? 'text-brand' : '' }}">inventory_2</span>
                <span>Bahan Pakan</span>
            </a>
            <a href="{{ route('acuan.index') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-full text-sm font-semibold transition {{ request()->routeIs('acuan.*') ? 'bg-surface text-ink font-bold' : 'text-muted hover:bg-surface hover:text-ink' }}">
                <span class="material-symbols-outlined text-[20px] {{ request()->routeIs('acuan.*') ? 'text-brand' : '' }}">fact_check</span>
                <span>Acuan Gizi (SNI)</span>
            </a>
            <a href="{{ route('tentang') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-full text-sm font-semibold transition {{ request()->routeIs('tentang') ? 'bg-surface text-ink font-bold' : 'text-muted hover:bg-surface hover:text-ink' }}">
                <span class="material-symbols-outlined text-[20px] {{ request()->routeIs('tentang') ? 'text-brand' : '' }}">help_outline</span>
                <span>Tentang RansumKu</span>
            </a>
        </nav>
    </div>

    {{-- Info Farm & Logout di Bagian Bawah Sidebar --}}
    @if ($farmNav)
        <div class="mt-auto card !p-3 text-xs bg-surface/60 border-line">
            <div class="flex items-center gap-2 mb-1">
                <span class="w-2 h-2 rounded-full bg-lime"></span>
                <p class="font-bold text-ink truncate">{{ $farmNav->nama }}</p>
            </div>
            <p class="text-[11px] text-muted">{{ number_format($farmNav->jumlah_ayam, 0, ',', '.') }} ayam · {{ ucfirst($farmNav->fase) }}</p>
            <form method="POST" action="{{ route('keluar') }}" class="mt-2 pt-2 border-t border-line/60">@csrf
                <button class="text-danger font-bold text-tiny cursor-pointer hover:underline">Keluar</button>
            </form>
        </div>
    @endif
</aside>

{{-- 2. Top Bar Desktop (Sesuai Referensi: Search + User + Clock) --}}
<header class="hidden lg:flex fixed top-0 left-64 right-0 h-16 bg-[#EBF1EB]/90 backdrop-blur-md z-30 px-8 items-center justify-between">
    <div class="relative w-80">
        <input type="text" placeholder="Search" class="w-full bg-white border border-line rounded-full py-2 pl-4 pr-10 text-xs text-ink placeholder:text-muted focus:outline-none focus:border-brand focus:ring-1 focus:ring-brand/20 shadow-sm" />
        <span class="material-symbols-outlined absolute right-3.5 top-1/2 -translate-y-1/2 text-muted text-[18px]">search</span>
    </div>

    <div class="flex items-center gap-3">
        <div class="flex items-center gap-2 px-3 py-1.5 rounded-full bg-white/80 border border-line/60 text-xs font-semibold text-ink shadow-sm">
            <span class="w-6 h-6 rounded-full bg-brand/15 text-brand flex items-center justify-center">
                <span class="material-symbols-outlined text-[16px]">person</span>
            </span>
            <span>{{ auth()->user()->name ?? 'Peternak' }}</span>
        </div>
        <button type="button" class="w-8 h-8 rounded-full bg-white/80 border border-line/60 flex items-center justify-center text-brand hover:bg-white transition shadow-sm" title="Jam / Riwayat">
            <span class="material-symbols-outlined text-[18px]">schedule</span>
        </button>
    </div>
</header>

{{-- 3. Header Mobile --}}
<header class="lg:hidden fixed top-0 inset-x-0 z-40 bg-white/95 backdrop-blur-xl border-b border-line">
    <div class="h-16 px-4 flex items-center justify-between max-w-lg mx-auto">
        <a href="{{ route('beranda') }}" class="flex items-center gap-2.5">
            <img src="{{ asset('images/logo.png') }}" alt="Logo RansumKu" class="w-8 h-8 object-contain rounded-full shadow-sm shrink-0" />
            <div class="flex items-baseline gap-1.5">
                <span class="text-xl font-black text-brand">RansumKu</span>
                <span class="text-xs text-muted font-bold">· @yield('judul', 'Dashboard')</span>
            </div>
        </a>
        <div class="w-8 h-8 rounded-full bg-surface border border-line text-ink grid place-items-center" aria-label="Akun">
            <span class="material-symbols-outlined text-[18px]">person</span>
        </div>
    </div>
</header>

{{-- 4. Main Content Area --}}
<main class="lg:ml-64 pt-20 lg:pt-20 pb-28 lg:pb-12 px-4 lg:px-8">
    <div class="max-w-6xl mx-auto space-y-4">
        @if (session('ok'))
            <div class="notice bg-brand-soft/70 text-primary" role="status">
                <span class="material-symbols-outlined icon-fill text-[20px]">check_circle</span><span>{{ session('ok') }}</span>
            </div>
        @endif
        @if (isset($errors) && $errors->any())
            <div class="notice bg-danger-soft text-danger-ink" role="alert">
                <span class="material-symbols-outlined icon-fill text-[20px]">error</span>
                <ul class="list-disc ml-4">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            </div>
        @endif

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
        <a href="{{ route('asisten.index') }}" class="flex flex-col items-center justify-center min-w-14 min-h-12 py-1 transition-colors {{ request()->routeIs('asisten.*') ? 'text-brand font-bold' : 'text-muted' }}">
            <span class="material-symbols-outlined text-[22px] {{ request()->routeIs('asisten.*') ? 'icon-fill' : '' }}">smart_toy</span>
            <span class="text-[10px] mt-0.5">Asisten</span>
        </a>
        <a href="{{ route('bahan.index') }}" class="flex flex-col items-center justify-center min-w-14 min-h-12 py-1 transition-colors {{ request()->routeIs('bahan.*|acuan.*|tentang') ? 'text-brand font-bold' : 'text-muted' }}">
            <span class="material-symbols-outlined text-[22px] {{ request()->routeIs('bahan.*|acuan.*|tentang') ? 'icon-fill' : '' }}">inventory_2</span>
            <span class="text-[10px] mt-0.5">Bahan</span>
        </a>
    </div>
</nav>

</body>
</html>
