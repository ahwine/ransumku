<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="description" content="RansumKu: alat bantu hitung ransum ayam petelur, buku kandang digital, dan asisten cerdas untuk peternak kecil.">
    <meta name="theme-color" content="#2F6B3A">
    <title>@yield('judul', 'RansumKu') · RansumKu</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0&display=swap">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen">
@php
    $nav = [
        ['beranda', 'Beranda', 'home', 'beranda'],
        ['ransum.*', 'Ransum', 'calculate', 'ransum.index'],
        ['catatan.*', 'Catatan', 'assignment', 'catatan.index'],
        ['asisten.*', 'Asisten', 'smart_toy', 'asisten.index'],
        ['lainnya|bahan.*|acuan.*|tentang', 'Lainnya', 'more_horiz', 'lainnya'],
    ];
    $aktif = function ($pola) { foreach (explode('|', $pola) as $p) { if (request()->routeIs($p)) return true; } return false; };
    $farmNav = auth()->user()?->farm;
@endphp

{{-- Sidebar desktop --}}
<aside class="hidden lg:flex fixed inset-y-0 left-0 w-64 flex-col bg-white border-r border-line p-5 z-40">
    <a href="{{ route('beranda') }}" class="flex items-center gap-2 mb-8">
        <span class="w-10 h-10 rounded-xl bg-brand text-white grid place-items-center"><span class="material-symbols-outlined icon-fill">egg_alt</span></span>
        <span class="text-h-sm text-brand">RansumKu</span>
    </a>
    <nav class="flex flex-col gap-1">
        @foreach ($nav as [$pola, $label, $ikon, $rute])
            <a href="{{ route($rute) }}" class="flex items-center gap-3 px-4 py-3 rounded-xl font-semibold {{ $aktif($pola) ? 'bg-brand text-white' : 'text-muted hover:bg-surface' }}">
                <span class="material-symbols-outlined">{{ $ikon }}</span>{{ $label }}
            </a>
        @endforeach
    </nav>
    @if ($farmNav)
        <div class="mt-auto card !p-3 text-sm">
            <p class="font-bold">{{ $farmNav->nama }}</p>
            <p class="text-muted">{{ number_format($farmNav->jumlah_ayam, 0, ',', '.') }} ayam · {{ ucfirst($farmNav->fase) }}</p>
            <form method="POST" action="{{ route('keluar') }}" class="mt-2">@csrf
                <button class="text-danger font-bold text-sm cursor-pointer">Keluar</button>
            </form>
        </div>
    @endif
</aside>

{{-- Header mobile --}}
<header class="lg:hidden fixed top-0 inset-x-0 z-40 bg-canvas/90 backdrop-blur-xl border-b border-line/60">
    <div class="h-16 px-4 flex items-center justify-between max-w-lg mx-auto">
        <a href="{{ route('beranda') }}" class="flex items-center gap-2">
            <span class="w-9 h-9 rounded-xl bg-brand text-white grid place-items-center"><span class="material-symbols-outlined icon-fill text-[20px]">egg_alt</span></span>
            <span class="leading-none">
                <span class="block text-h-sm text-brand">RansumKu</span>
                <span class="block text-tiny text-muted mt-0.5">@yield('judul', 'Beranda')</span>
            </span>
        </a>
        <a href="{{ route('lainnya') }}" class="w-9 h-9 rounded-full bg-primary text-white grid place-items-center" aria-label="Akun">
            <span class="material-symbols-outlined text-[18px]">person</span>
        </a>
    </div>
</header>

<main class="lg:ml-64 pt-20 lg:pt-8 pb-28 lg:pb-10 px-4">
    <div class="max-w-lg lg:max-w-5xl mx-auto space-y-4">
        @if (session('ok'))
            <div class="notice bg-brand-soft/50 text-primary" role="status">
                <span class="material-symbols-outlined icon-fill text-[20px]">check_circle</span><span>{{ session('ok') }}</span>
            </div>
        @endif
        @if ($errors->any())
            <div class="notice bg-danger-soft text-danger-ink" role="alert">
                <span class="material-symbols-outlined icon-fill text-[20px]">error</span>
                <ul class="list-disc ml-4">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            </div>
        @endif

        @yield('isi')
    </div>
</main>

{{-- Navbar bawah (HP) --}}
<nav class="lg:hidden fixed bottom-0 inset-x-0 z-40 bg-canvas/95 backdrop-blur-xl border-t border-line" style="box-shadow:0 -4px 16px rgba(34,34,34,.04)">
    <div class="flex items-center justify-around h-16 max-w-lg mx-auto">
        @foreach ($nav as [$pola, $label, $ikon, $rute])
            <a href="{{ route($rute) }}" @if($aktif($pola)) aria-current="page" @endif
               class="flex flex-col items-center justify-center min-w-14 min-h-12 py-1 {{ $aktif($pola) ? 'text-brand' : 'text-muted' }}">
                <span class="material-symbols-outlined text-[24px] {{ $aktif($pola) ? 'icon-fill' : '' }}">{{ $ikon }}</span>
                <span class="text-tiny mt-0.5 {{ $aktif($pola) ? 'font-extrabold' : '' }}">{{ $label }}</span>
            </a>
        @endforeach
    </div>
    <div style="height:env(safe-area-inset-bottom,0px)"></div>
</nav>
</body>
</html>
