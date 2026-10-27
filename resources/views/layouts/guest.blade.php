<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="description" content="RansumKu: racik pakan hemat, kandang sehat. Alat bantu hitung ransum ayam petelur untuk peternak kecil.">
    <title>@yield('judul', 'Masuk') · RansumKu</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0&display=swap">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-canvas">
<main class="min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-md space-y-5">
        <div class="text-center">
            <span class="inline-grid place-items-center w-16 h-16 rounded-2xl bg-brand text-white mb-3"><span class="material-symbols-outlined icon-fill text-[34px]">egg_alt</span></span>
            <h1 class="text-h-xl text-brand">RansumKu</h1>
            <p class="text-muted">Racik pakan hemat, kandang sehat</p>
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
