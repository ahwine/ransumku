<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="description" content="RansumKu: racik pakan hemat, kandang sehat. Alat bantu hitung ransum ayam petelur untuk peternak kecil.">
    <title>Masuk · RansumKu</title>
    <link rel="icon" type="image/png" href="{{ asset('images/logo.png') }}">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0&display=swap">
    <script>
        /* Putar intro kecuali jika baru saja mengirim form login (ada galat/input lama) atau reduced-motion aktif. */
        (function () {
            var skip = {{ $errors->any() || old('email') ? 'true' : 'false' }};
            try {
                if (sessionStorage.getItem('rk_login_submitted') === '1') {
                    skip = true;
                    sessionStorage.removeItem('rk_login_submitted');
                }
                sessionStorage.removeItem('rk_login_intro');
                skip = skip || window.matchMedia('(prefers-reduced-motion: reduce)').matches;
            } catch (e) {}
            if (!skip) {
                document.documentElement.classList.add('lg-intro');
            }
        })();
    </script>
    @vite(['resources/css/app.css', 'resources/js/login.js'])
    @include('partials.auth-styles')
    <style>
        /* ====== INTRO 3D (jangan diubah: dipakai resources/js/login.js) ====== */
        :root {
            --lg-warm: #F7F8F3;
            --lg-emerald: #087B50;
            --lg-forest: #082F24;
            --lg-lime: #C6F36A;
            --lg-gray: #E7ECE6;
            --lg-charcoal: #17231D;
        }
        #lg-stage {
            position: fixed;
            inset: 0;
            z-index: 1;
            background: var(--lg-warm);
            pointer-events: none;
            transition: opacity .7s ease;
        }
        html:not(.lg-intro) #lg-stage { display: none; }
        #lg-stage::after {
            content: '';
            position: absolute;
            inset: 0;
            pointer-events: none;
            background: radial-gradient(ellipse at 50% 55%, transparent 55%, rgba(8, 47, 36, .08) 100%);
        }
        html.lg-done #lg-stage { opacity: 0; }
        #lg-flash {
            --fx: 50%; --fy: 60%; --r: 0px;
            position: fixed;
            inset: 0;
            z-index: 2;
            opacity: 0;
            pointer-events: none;
            background: radial-gradient(circle at var(--fx) var(--fy),
                rgba(214, 247, 150, .95) 0,
                rgba(198, 243, 106, .8) calc(var(--r) * .18),
                rgba(8, 123, 80, .72) calc(var(--r) * .5),
                rgba(8, 123, 80, 0) var(--r));
        }

        .lg-skip {
            position: fixed;
            top: max(16px, env(safe-area-inset-top));
            right: 16px;
            z-index: 6;
            display: none;
            align-items: center;
            gap: 6px;
            padding: 10px 16px;
            border-radius: 999px;
            border: 1px solid var(--lg-gray);
            background: rgba(255, 255, 255, .85);
            backdrop-filter: blur(8px);
            color: var(--lg-forest);
            font-size: 13px;
            font-weight: 600;
            line-height: 1;
            font-family: inherit;
            cursor: pointer;
            transition: background .2s, border-color .2s;
        }
        .lg-skip:hover { background: #fff; border-color: var(--lg-emerald); }
        .lg-skip:focus-visible { outline: 3px solid rgba(8, 123, 80, .35); outline-offset: 2px; }
        html.lg-intro:not(.lg-done) .lg-skip { display: inline-flex; animation: lg-skip-failsafe .01s 16s forwards; }

        html.lg-intro:not(.lg-done) #lg-stage {
            animation: lg-stage-failsafe .01s 16s forwards;
        }
        html.lg-intro:not(.lg-done) .lg-shell {
            visibility: hidden;
            opacity: 0;
            animation: lg-failsafe .01s 16s forwards;
        }
        @keyframes lg-failsafe { to { visibility: visible; opacity: 1; } }
        @keyframes lg-failsafe-item { to { opacity: 1; transform: none; } }
        @keyframes lg-stage-failsafe { to { opacity: 0; visibility: hidden; pointer-events: none; } }
        @keyframes lg-skip-failsafe { to { opacity: 0; visibility: hidden; pointer-events: none; display: none; } }

        .lg-item { transition: opacity .8s cubic-bezier(.2, .7, .2, 1), transform .8s cubic-bezier(.2, .7, .2, 1); transition-delay: var(--d, 0s); }
        html.lg-intro .lg-item { opacity: 0; transform: translateY(24px); }
        html.lg-intro:not(.lg-done) .lg-item { animation: lg-failsafe-item .01s 16s forwards; }
        html.lg-intro.lg-done .lg-item { opacity: 1; transform: none; }
        html.lg-intro .lg-logo-item { transform: translateY(12px) scale(.86); }
        html.lg-intro.lg-done .lg-logo-item { transform: none; }
        html.lg-intro .lg-card-item { transform: translateY(40px); }
        html.lg-intro.lg-done .lg-card-item { transform: none; }
        html.lg-fast .lg-item { transition-duration: .25s; transition-delay: 0s; }
        /* ====== AKHIR BLOK INTRO ====== */

        /* Tanpa intro: elemen masuk dengan fade-rise halus (konsisten dengan halaman daftar) */
        html:not(.lg-intro) .lg-item { animation: au-rise .5s cubic-bezier(.2, .7, .2, 1) both; animation-delay: var(--d, 0s); }
        @media (prefers-reduced-motion: reduce) {
            .lg-item, #lg-stage { transition: none !important; }
            html:not(.lg-intro) .lg-item { animation: none !important; }
        }
    </style>
    <noscript><style>.lg-shell { visibility: visible !important; opacity: 1 !important; } .lg-item { opacity: 1 !important; transform: none !important; }</style></noscript>
</head>
<body class="au-body">
    <div class="au-decor" aria-hidden="true"></div>
    <div id="lg-stage" data-model-url="{{ asset('models/chicken.glb') }}" data-egg-url="{{ asset('models/egg.glb') }}" aria-hidden="true"></div>
    <div id="lg-flash" aria-hidden="true"></div>
    <button type="button" id="lg-skip" class="lg-skip">Lewati animasi</button>

    <main class="au-shell lg-shell">
        <section class="au-brand">
            <x-auth.logo class="lg-item lg-logo-item" style="--d: .05s" />
            <h1 class="au-headline lg-item" style="--d: .2s">Racik Pakan Lebih Cerdas. <span>Kelola Kandang Lebih Terarah.</span></h1>
            <p class="au-desc lg-item" style="--d: .32s">Sistem manajemen formulasi pakan presisi dan pemantauan peternakan ayam petelur modern.</p>
        </section>

        <div class="au-card-col">
            <section class="au-card lg-item lg-card-item" style="--d: .3s" aria-labelledby="lg-title">
                <h2 id="lg-title">Masuk ke akun</h2>
                <p class="au-sub">Lanjutkan mengelola kandang Anda.</p>

                <form id="form-masuk" method="POST" action="{{ url('/masuk') }}" data-auth-form novalidate>
                    @csrf
                    <x-auth.input
                        id="email"
                        name="email"
                        type="email"
                        label="Email"
                        :value="old('email')"
                        placeholder="nama@email.com"
                        autocomplete="email"
                        inputmode="email"
                        autofocus
                    />
                    <x-auth.password-input
                        id="password"
                        name="password"
                        label="Kata sandi"
                        placeholder="Masukkan kata sandi"
                        autocomplete="current-password"
                        toggle-id="toggle-password"
                    />

                    <div class="au-row">
                        <x-auth.checkbox id="ingat" name="ingat" value="1">Ingat saya</x-auth.checkbox>
                        {{-- Titik integrasi: ganti dengan tautan rute reset kata sandi bila fitur tersedia. --}}
                        <button type="button" class="au-link" data-toggle-target="#lupa-sandi-info" aria-expanded="false" aria-controls="lupa-sandi-info">Lupa kata sandi?</button>
                    </div>
                    <p class="au-note" id="lupa-sandi-info" hidden>Reset kata sandi mandiri belum tersedia. Silakan hubungi admin RansumKu untuk dibantu mengatur ulang kata sandi Anda.</p>

                    <div class="au-banner is-error" role="alert" aria-live="polite" @unless ($errors->any()) hidden @endunless>
                        <span class="material-symbols-outlined icon-fill" style="font-size:20px" aria-hidden="true">error</span>
                        <ul>@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
                    </div>

                    <x-auth.button id="btn-masuk" label="Masuk" />
                    <p class="au-foot">Belum punya akun? <a href="{{ route('daftar') }}" data-auth-switch>Daftar</a></p>
                </form>
            </section>
        </div>
    </main>
    @include('partials.auth-scripts')
</body>
</html>
