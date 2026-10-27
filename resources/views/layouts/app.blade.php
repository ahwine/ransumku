<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="description" content="RansumKu: alat bantu hitung ransum ayam petelur, buku kandang digital, dan asisten cerdas untuk peternak kecil.">
    <meta name="theme-color" content="#002415">
    <title>@yield('judul', 'Dashboard') · ROSSA</title>
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

{{-- 1. Sidebar Desktop (Sesuai Referensi ROSSA) --}}
<aside class="hidden lg:flex fixed inset-y-0 left-0 w-64 flex-col bg-white border-r border-line p-5 z-40">
    {{-- Logo ROSSA --}}
    <a href="{{ route('beranda') }}" class="flex items-center gap-2 px-2 mb-6">
        <div>
            <div class="flex items-center gap-1.5">
                <span class="text-2xl font-black tracking-tight text-brand font-serif">ROSSA</span>
                <span class="w-2 h-2 rounded-full bg-lime"></span>
            </div>
            <span class="block text-[9px] font-bold tracking-widest text-muted uppercase">EST 2010</span>
        </div>
    </a>

    {{-- Menu Group 1: MENU --}}
    <div class="mb-5">
        <span class="px-3 text-[11px] font-black uppercase tracking-wider text-brand block mb-2">MENU</span>
        <nav class="space-y-1">
            <a href="{{ route('beranda') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-full text-sm font-semibold transition {{ request()->routeIs('beranda') ? 'bg-surface text-ink font-bold' : 'text-muted hover:bg-surface hover:text-ink' }}">
                <span class="material-symbols-outlined text-[20px] {{ request()->routeIs('beranda') ? 'text-brand' : '' }}">dashboard</span>
                <span>Dashboard</span>
            </a>
            <a href="{{ route('catatan.index') }}" class="flex items-center justify-between px-3.5 py-2.5 rounded-full text-sm font-semibold transition {{ request()->routeIs('catatan.*') ? 'bg-brand/10 text-brand font-bold' : 'text-muted hover:bg-surface hover:text-ink' }}">
                <div class="flex items-center gap-3">
                    <span class="material-symbols-outlined text-[20px] text-brand">description</span>
                    <span>Pencatatan produksi</span>
                </div>
                <span class="material-symbols-outlined text-[16px]">expand_more</span>
            </a>
            <a href="{{ route('catatan.index', ['tab' => 'dashboard']) }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-full text-sm font-semibold text-muted hover:bg-surface hover:text-ink transition">
                <span class="material-symbols-outlined text-[20px]">bar_chart</span>
                <span>Pelaporan</span>
            </a>
            <a href="{{ route('asisten.index') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-full text-sm font-semibold text-muted hover:bg-surface hover:text-ink transition">
                <span class="material-symbols-outlined text-[20px]">calendar_month</span>
                <span>Penjadwalan</span>
            </a>
            <a href="{{ route('asisten.index') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-full text-sm font-semibold text-muted hover:bg-surface hover:text-ink transition">
                <span class="material-symbols-outlined text-[20px]">verified_user</span>
                <span>Validasi data</span>
            </a>
        </nav>
    </div>

    {{-- Menu Group 2: OTHER --}}
    <div class="mb-5">
        <span class="px-3 text-[11px] font-black uppercase tracking-wider text-brand block mb-2">OTHER</span>
        <nav class="space-y-1">
            <a href="{{ route('tentang') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-full text-sm font-semibold text-muted hover:bg-surface hover:text-ink transition">
                <span class="material-symbols-outlined text-[20px]">help_outline</span>
                <span>Help</span>
            </a>
            <a href="{{ route('bahan.index') }}" class="flex items-center justify-between px-3.5 py-2.5 rounded-full text-sm font-semibold text-muted hover:bg-surface hover:text-ink transition">
                <div class="flex items-center gap-3">
                    <span class="material-symbols-outlined text-[20px]">layers</span>
                    <span>Master data</span>
                </div>
                <span class="material-symbols-outlined text-[16px]">expand_more</span>
            </a>
            <a href="{{ route('lainnya') }}" class="flex items-center justify-between px-3.5 py-2.5 rounded-full text-sm font-semibold text-muted hover:bg-surface hover:text-ink transition">
                <div class="flex items-center gap-3">
                    <span class="material-symbols-outlined text-[20px]">group</span>
                    <span>Manajemen user</span>
                </div>
                <span class="material-symbols-outlined text-[16px]">expand_more</span>
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
        <a href="{{ route('lainnya') }}" class="flex items-center gap-2 px-3 py-1.5 rounded-full bg-white/80 hover:bg-white border border-line/60 transition text-xs font-semibold text-ink shadow-sm">
            <span class="w-6 h-6 rounded-full bg-brand/15 text-brand flex items-center justify-center">
                <span class="material-symbols-outlined text-[16px]">person</span>
            </span>
            <span>{{ auth()->user()->name ?? 'Rossa Lia' }}</span>
            <span class="material-symbols-outlined text-[16px] text-muted">expand_more</span>
        </a>
        <button type="button" class="w-8 h-8 rounded-full bg-white/80 border border-line/60 flex items-center justify-center text-brand hover:bg-white transition shadow-sm" title="Jam / Riwayat">
            <span class="material-symbols-outlined text-[18px]">schedule</span>
        </button>
    </div>
</header>

{{-- 3. Header Mobile --}}
<header class="lg:hidden fixed top-0 inset-x-0 z-40 bg-white/95 backdrop-blur-xl border-b border-line">
    <div class="h-16 px-4 flex items-center justify-between max-w-lg mx-auto">
        <a href="{{ route('beranda') }}" class="flex items-center gap-2">
            <span class="text-xl font-black text-brand font-serif">ROSSA</span>
            <span class="text-xs text-muted font-bold">· @yield('judul', 'Dashboard')</span>
        </a>
        <a href="{{ route('lainnya') }}" class="w-8 h-8 rounded-full bg-surface border border-line text-ink grid place-items-center hover:bg-surface-2 transition" aria-label="Akun">
            <span class="material-symbols-outlined text-[18px]">person</span>
        </a>
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
        <a href="{{ route('lainnya') }}" class="flex flex-col items-center justify-center min-w-14 min-h-12 py-1 transition-colors {{ request()->routeIs('lainnya|bahan.*|acuan.*|tentang') ? 'text-brand font-bold' : 'text-muted' }}">
            <span class="material-symbols-outlined text-[22px]">more_horiz</span>
            <span class="text-[10px] mt-0.5">Lainnya</span>
        </a>
    </div>
</nav>

</body>
</html>
