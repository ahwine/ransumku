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
    <style>
        :root {
            --lg-warm: #F7F8F3;
            --lg-emerald: #087B50;
            --lg-forest: #082F24;
            --lg-lime: #C6F36A;
            --lg-gray: #E7ECE6;
            --lg-charcoal: #17231D;
        }
        .lg-body {
            margin: 0;
            min-height: 100vh;
            background: var(--lg-warm);
            color: var(--lg-charcoal);
            font-family: 'Plus Jakarta Sans', Inter, ui-sans-serif, system-ui, sans-serif;
            overflow-x: hidden;
            -webkit-font-smoothing: antialiased;
        }
        .lg-decor {
            position: fixed;
            inset: 0;
            z-index: 0;
            pointer-events: none;
            background:
                radial-gradient(900px 520px at 8% -10%, rgba(8, 123, 80, .10), transparent 60%),
                radial-gradient(760px 480px at 100% 105%, rgba(198, 243, 106, .28), transparent 60%);
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
        html.lg-intro:not(.lg-done) .lg-skip { display: inline-flex; }

        .lg-shell {
            position: relative;
            z-index: 3;
            min-height: 100vh;
            max-width: 1180px;
            margin: 0 auto;
            padding: 40px 32px;
            display: grid;
            grid-template-columns: 1.05fr .95fr;
            gap: 56px;
            align-items: center;
            box-sizing: border-box;
        }
        html.lg-intro:not(.lg-done) .lg-shell {
            visibility: hidden;
            opacity: 0;
            animation: lg-failsafe .01s 10s forwards;
        }
        @keyframes lg-failsafe { to { visibility: visible; opacity: 1; } }
        @keyframes lg-failsafe-item { to { opacity: 1; transform: none; } }

        .lg-item { transition: opacity .8s cubic-bezier(.2, .7, .2, 1), transform .8s cubic-bezier(.2, .7, .2, 1); transition-delay: var(--d, 0s); }
        html.lg-intro .lg-item { opacity: 0; transform: translateY(24px); }
        html.lg-intro:not(.lg-done) .lg-item { animation: lg-failsafe-item .01s 10s forwards; }
        html.lg-intro.lg-done .lg-item { opacity: 1; transform: none; }
        html.lg-intro .lg-logo-item { transform: translateY(12px) scale(.86); }
        html.lg-intro.lg-done .lg-logo-item { transform: none; }
        html.lg-intro .lg-card-item { transform: translateY(40px); }
        html.lg-intro.lg-done .lg-card-item { transform: none; }
        html.lg-fast .lg-item { transition-duration: .25s; transition-delay: 0s; }

        .lg-brand-row { display: flex; align-items: center; gap: 14px; }
        .lg-logo { width: 56px; height: 56px; border-radius: 999px; object-fit: contain; box-shadow: 0 8px 20px -8px rgba(8, 47, 36, .35); background: #fff; }
        .lg-wordmark { font-size: 24px; font-weight: 800; letter-spacing: -.02em; color: var(--lg-forest); }
        .lg-headline { margin: 28px 0 0; font-size: clamp(2rem, 4.2vw, 3.3rem); line-height: 1.08; font-weight: 800; letter-spacing: -.03em; color: var(--lg-forest); }
        .lg-headline span { display: block; color: var(--lg-emerald); }
        .lg-desc { margin: 18px 0 0; max-width: 34rem; font-size: 16px; line-height: 1.7; color: #4b5d54; }
        .lg-chips { display: flex; flex-wrap: wrap; gap: 10px; margin: 26px 0 0; padding: 0; list-style: none; }
        .lg-chips li { padding: 8px 14px; border-radius: 999px; background: #fff; border: 1px solid var(--lg-gray); font-size: 13px; font-weight: 600; color: var(--lg-forest); }
        .lg-chips li::before { content: ''; display: inline-block; width: 7px; height: 7px; margin-right: 8px; border-radius: 99px; background: var(--lg-lime); box-shadow: 0 0 0 2px rgba(8, 123, 80, .25); }

        .lg-card {
            width: 100%;
            max-width: 440px;
            justify-self: center;
            padding: 36px 32px;
            border-radius: 28px;
            background: #fff;
            border: 1px solid var(--lg-gray);
            box-shadow: 0 30px 70px -30px rgba(8, 47, 36, .28), 0 2px 6px rgba(8, 47, 36, .04);
            box-sizing: border-box;
        }
        .lg-card h2 { margin: 0; font-size: 24px; font-weight: 800; letter-spacing: -.02em; color: var(--lg-forest); }
        .lg-card .lg-sub { margin: 6px 0 22px; font-size: 14px; color: #5f7168; }
        .lg-error { display: flex; gap: 10px; margin-bottom: 18px; padding: 12px 14px; border-radius: 14px; background: #fdeceb; border: 1px solid #f5c4bf; color: #8a1c14; font-size: 14px; }
        .lg-error ul { margin: 0; padding-left: 16px; }
        .lg-label { display: block; margin-bottom: 7px; font-size: 13px; font-weight: 700; color: var(--lg-charcoal); }
        .lg-field-wrap { position: relative; }
        .lg-input {
            width: 100%;
            height: 50px;
            padding: 0 16px;
            border-radius: 14px;
            border: 1.5px solid var(--lg-gray);
            background: var(--lg-warm);
            color: var(--lg-charcoal);
            font-size: 15px;
            font-weight: 500;
            font-family: inherit;
            box-sizing: border-box;
            transition: border-color .2s, box-shadow .2s, background .2s;
        }
        .lg-input::placeholder { color: #9aa8a0; }
        .lg-input:hover { border-color: #cfd8d0; }
        .lg-input:focus { outline: none; border-color: var(--lg-emerald); background: #fff; box-shadow: 0 0 0 4px rgba(8, 123, 80, .16); }
        .lg-input.has-toggle { padding-right: 52px; }
        .lg-eye {
            position: absolute;
            top: 50%;
            right: 6px;
            width: 40px;
            height: 40px;
            transform: translateY(-50%);
            display: grid;
            place-items: center;
            border: 0;
            border-radius: 10px;
            background: transparent;
            color: #6b7d73;
            cursor: pointer;
        }
        .lg-eye:hover { background: var(--lg-gray); color: var(--lg-forest); }
        .lg-eye:focus-visible { outline: 3px solid rgba(8, 123, 80, .35); }
        .lg-eye.is-on { color: var(--lg-emerald); }
        .lg-row { display: flex; align-items: center; justify-content: space-between; margin: 16px 0 22px; font-size: 14px; }
        .lg-remember { display: inline-flex; align-items: center; gap: 9px; color: #4b5d54; cursor: pointer; }
        .lg-remember input { width: 18px; height: 18px; accent-color: var(--lg-emerald); }
        .lg-submit {
            position: relative;
            width: 100%;
            height: 52px;
            border: 0;
            border-radius: 14px;
            background: linear-gradient(135deg, #0a8d5c, var(--lg-emerald));
            color: #fff;
            font-size: 15px;
            font-weight: 700;
            font-family: inherit;
            cursor: pointer;
            box-shadow: 0 12px 24px -12px rgba(8, 123, 80, .7);
            transition: transform .15s, box-shadow .2s, filter .2s;
        }
        .lg-submit:hover { filter: brightness(1.06); box-shadow: 0 16px 28px -12px rgba(8, 123, 80, .75); }
        .lg-submit:active { transform: translateY(1px) scale(.995); }
        .lg-submit:focus-visible { outline: 3px solid rgba(8, 123, 80, .4); outline-offset: 3px; }
        .lg-submit:disabled { cursor: progress; filter: saturate(.8) brightness(.95); }
        .lg-submit .lg-spin { display: none; width: 18px; height: 18px; margin-right: 8px; vertical-align: -3px; border-radius: 99px; border: 2.5px solid rgba(255, 255, 255, .4); border-top-color: #fff; animation: lg-rot .7s linear infinite; }
        .lg-submit.is-loading .lg-spin { display: inline-block; }
        @keyframes lg-rot { to { transform: rotate(360deg); } }
        .lg-foot { margin: 20px 0 0; text-align: center; font-size: 14px; color: #5f7168; }
        .lg-foot a { color: var(--lg-emerald); font-weight: 700; text-decoration: none; }
        .lg-foot a:hover { text-decoration: underline; }
        .lg-foot a:focus-visible { outline: 3px solid rgba(8, 123, 80, .35); border-radius: 4px; }

        @media (max-width: 900px) {
            .lg-shell { grid-template-columns: 1fr; gap: 28px; padding: 72px 18px 32px; align-content: center; }
            .lg-brand { text-align: center; }
            .lg-brand-row { justify-content: center; }
            .lg-headline { margin-top: 18px; font-size: clamp(1.55rem, 6.4vw, 2.1rem); }
            .lg-desc, .lg-chips { display: none; }
            .lg-card { padding: 28px 22px; border-radius: 24px; }
        }
        @media (prefers-reduced-motion: reduce) {
            .lg-item, #lg-stage { transition: none !important; }
        }
    </style>
    <noscript><style>.lg-shell { visibility: visible !important; opacity: 1 !important; } .lg-item { opacity: 1 !important; transform: none !important; }</style></noscript>
</head>
<body class="lg-body">
    <div class="lg-decor" aria-hidden="true"></div>
    <div id="lg-stage" data-model-url="{{ asset('models/chicken.glb') }}" data-egg-url="{{ asset('models/egg.glb') }}" aria-hidden="true"></div>
    <div id="lg-flash" aria-hidden="true"></div>
    <button type="button" id="lg-skip" class="lg-skip">Lewati animasi</button>

    <main class="lg-shell">
        <section class="lg-brand">
            <div class="lg-brand-row lg-item lg-logo-item" style="--d: .05s">
                <img src="{{ asset('images/logo.png') }}" alt="Logo RansumKu" class="lg-logo" width="56" height="56">
                <span class="lg-wordmark">RansumKu</span>
            </div>
            <h1 class="lg-headline lg-item" style="--d: .2s">Racik Pakan Lebih Cerdas. <span>Kelola Kandang Lebih Terarah.</span></h1>
            <p class="lg-desc lg-item" style="--d: .32s">RansumKu membantu peternak ayam petelur meracik ransum dengan biaya paling hemat sekaligus memantau suhu, kelembapan, dan produksi kandang dalam satu tempat.</p>
            <ul class="lg-chips lg-item" style="--d: .42s">
                <li>Optimasi ransum</li>
                <li>Pemantauan kandang IoT</li>
                <li>Asisten AI</li>
            </ul>
        </section>

        <section class="lg-card lg-item lg-card-item" style="--d: .3s" aria-labelledby="lg-title">
            <h2 id="lg-title">Masuk ke akun</h2>
            <p class="lg-sub">Lanjutkan mengelola kandang Anda.</p>

            @if ($errors->any())
                <div class="lg-error" role="alert">
                    <span class="material-symbols-outlined icon-fill" style="font-size:20px" aria-hidden="true">error</span>
                    <ul>@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
                </div>
            @endif

            <form id="form-masuk" method="POST" action="{{ url('/masuk') }}">
                @csrf
                <div style="margin-bottom:16px">
                    <label class="lg-label" for="email">Email</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" class="lg-input" placeholder="nama@email.com" autocomplete="email" required autofocus>
                </div>
                <div>
                    <label class="lg-label" for="password">Kata sandi</label>
                    <div class="lg-field-wrap">
                        <input id="password" name="password" type="password" class="lg-input has-toggle" placeholder="Masukkan kata sandi" autocomplete="current-password" required>
                        <button type="button" id="toggle-password" class="lg-eye" aria-label="Tampilkan kata sandi" aria-pressed="false">
                            <span class="material-symbols-outlined" aria-hidden="true">visibility</span>
                        </button>
                    </div>
                </div>
                <div class="lg-row">
                    <label class="lg-remember"><input type="checkbox" name="ingat" value="1"> Ingat saya</label>
                </div>
                <button id="btn-masuk" type="submit" class="lg-submit"><span class="lg-spin" aria-hidden="true"></span><span>Masuk</span></button>
                <p class="lg-foot">Belum punya akun? <a href="{{ route('daftar') }}">Daftar</a></p>
            </form>
        </section>
    </main>
</body>
</html>
