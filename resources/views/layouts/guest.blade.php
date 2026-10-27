<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="description" content="RansumKu: racik pakan hemat, kandang sehat. Alat bantu hitung ransum ayam petelur untuk peternak kecil.">
    <title>@yield('judul', 'Masuk') · RansumKu</title>
    <link rel="icon" type="image/png" href="{{ asset('images/logo.png') }}">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0&display=swap">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-canvas">
<main class="min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-md space-y-5">
        <div class="text-center">
            <img src="{{ asset('images/logo.png') }}" alt="Logo RansumKu" class="w-20 h-20 mx-auto object-contain mb-3 drop-shadow-md rounded-full" />
            <h1 class="text-h-xl text-brand font-black tracking-tight">RansumKu</h1>
            <p class="text-muted text-sm mt-0.5">Smart Poultry Farm & Optimasi Pakan Hemat</p>
        </div>
        @if ($errors->any())
            <div class="notice bg-danger-soft text-danger-ink" role="alert">
                <span class="material-symbols-outlined icon-fill text-[20px]">error</span>
                <ul class="list-disc ml-4">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            </div>
        @endif
        @yield('isi')
    </div>
</main>
</body>
</html>
