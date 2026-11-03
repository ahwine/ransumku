<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="description" content="RansumKu: racik pakan hemat, kandang sehat. Alat bantu hitung ransum ayam petelur untuk peternak kecil.">
    <title>Daftar · RansumKu</title>
    <link rel="icon" type="image/png" href="{{ asset('images/logo.png') }}">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0&display=swap">
    @vite(['resources/css/app.css'])
    @include('partials.auth-styles')
</head>
@php
    $passwordError = $errors->first('password');
    $confirmError = $passwordError && str_contains($passwordError, 'Konfirmasi') ? $passwordError : null;
    $passwordError = $confirmError ? null : $passwordError;
@endphp
<body class="au-body">
    <div class="au-decor" aria-hidden="true"></div>

    <main class="au-shell">
        <section class="au-brand">
            <x-auth.logo class="au-rise" style="--d: .02s" />
            <p class="au-tagline au-rise" style="--d: .08s">Smart Poultry Farm &amp; Optimasi Pakan Hemat</p>
            <h1 class="au-headline au-desktop-only au-rise" style="--d: .14s">Satu akun untuk <span>semua kebutuhan kandang Anda.</span></h1>
            <ul class="au-benefits au-rise" style="--d: .24s">
                <li><span class="material-symbols-outlined au-ico-chip" aria-hidden="true">calculate</span>Racik ransum paling hemat sesuai kebutuhan nutrisi ayam petelur.</li>
                <li><span class="material-symbols-outlined au-ico-chip" aria-hidden="true">sensors</span>Pantau suhu dan kelembapan kandang secara langsung.</li>
                <li><span class="material-symbols-outlined au-ico-chip" aria-hidden="true">psychology</span>Tanya Asisten AI kapan saja saat butuh saran.</li>
            </ul>
        </section>

        <div class="au-card-col">
            <section class="au-card au-rise" style="--d: .12s" aria-labelledby="df-title">
                <h2 id="df-title">Buat akun baru</h2>
                <p class="au-sub">Mulai kelola kandang Anda dalam 1 menit.</p>

                <form id="form-daftar" method="POST" action="{{ url('/daftar') }}" data-auth-form novalidate>
                    @csrf
                    <x-auth.input
                        id="name"
                        name="name"
                        label="Nama lengkap"
                        :value="old('name')"
                        placeholder="Nama Anda"
                        autocomplete="name"
                        :error="$errors->first('name')"
                        autofocus
                    />
                    <x-auth.input
                        id="email"
                        name="email"
                        type="email"
                        label="Email"
                        :value="old('email')"
                        placeholder="nama@email.com"
                        autocomplete="email"
                        inputmode="email"
                        :error="$errors->first('email')"
                    />
                    <x-auth.password-input
                        id="password"
                        name="password"
                        label="Kata sandi"
                        placeholder="Buat kata sandi"
                        hint="Minimal 8 karakter."
                        autocomplete="new-password"
                        minlength="8"
                        :error="$passwordError"
                    >
                        <div class="au-strength" data-strength data-level="0">
                            <div class="au-strength-bars" aria-hidden="true"><i></i><i></i><i></i><i></i></div>
                            <p class="au-strength-label" data-strength-label aria-live="polite"></p>
                        </div>
                    </x-auth.password-input>
                    <x-auth.password-input
                        id="password_confirmation"
                        name="password_confirmation"
                        label="Konfirmasi kata sandi"
                        placeholder="Ulangi kata sandi"
                        autocomplete="new-password"
                        :error="$confirmError"
                    >
                        <p class="au-match" data-match aria-live="polite" hidden>
                            <span class="material-symbols-outlined au-ico icon-fill" aria-hidden="true">check_circle</span>
                            <span data-match-text></span>
                        </p>
                    </x-auth.password-input>

                    {{-- Persetujuan hanya divalidasi di sisi klien; logika pendaftaran server tidak diubah. --}}
                    <x-auth.checkbox id="setuju" name="setuju" value="1" :checked="(bool) old('setuju')" required required-message="Centang persetujuan untuk membuat akun.">
                        Saya setuju dengan <a class="au-link" href="#" style="font-size:inherit">Syarat &amp; Ketentuan</a> dan <a class="au-link" href="#" style="font-size:inherit">Kebijakan Privasi</a>.
                    </x-auth.checkbox>

                    <x-auth.button id="btn-daftar" label="Buat Akun" />
                    <p class="au-foot">Sudah punya akun? <a href="{{ route('masuk') }}" data-auth-switch>Masuk</a></p>
                </form>
            </section>
        </div>
    </main>
    @include('partials.auth-scripts')
</body>
</html>
