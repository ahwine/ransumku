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
        /* Putar intro 3D di desktop; lewati jika baru mengirim form, reduced-motion aktif, atau layar mobile HP hemat kuota. */
        (function () {
            var skip = {{ $errors->any() || old('email') ? 'true' : 'false' }};
            try {
                if (sessionStorage.getItem('rk_login_submitted') === '1') {
                    skip = true;
                    sessionStorage.removeItem('rk_login_submitted');
                }
                sessionStorage.removeItem('rk_login_intro');
                skip = skip || window.matchMedia('(prefers-reduced-motion: reduce)').matches;
                if (window.innerWidth < 768) {
                    skip = true;
                }
            } catch (e) {}
            if (!skip) {
                document.documentElement.classList.add('lg-intro');
            }
        })();
    </script>
    @vite(['resources/css/app.css', 'resources/js/login.js'])
    @include('partials.auth-styles')
    <style>
        /* ====== INTRO 3D (Three.js WebGL di resources/js/login.js) ====== */
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

        html:not(.lg-intro) .lg-item { animation: au-rise .5s cubic-bezier(.2, .7, .2, 1) both; animation-delay: var(--d, 0s); }

        /* ====== MASKOT AYAM SVG INTERAKTIF (Sensor Mouse & Respons Form) ====== */
        .mascot-stage-area {
            position: relative;
            width: 100%;
            max-width: 360px;
            margin: 16px 0 20px;
        }
        .chicken-wrap {
            position: relative;
            width: 100%;
            max-width: 340px;
            margin: 0 auto;
            padding-bottom: 6px;
        }
        .chicken-wrap::before {
            content: '';
            position: absolute;
            left: 50%;
            top: 50%;
            width: 86%;
            aspect-ratio: 1;
            transform: translate(-50%, -50%);
            border-radius: 50%;
            background: #e9f2eb;
            z-index: 0;
        }
        #chicken-svg {
            position: relative;
            display: block;
            width: 100%;
            height: auto;
            overflow: visible;
            cursor: pointer;
            z-index: 1;
            -webkit-tap-highlight-color: transparent;
        }
        .bubble {
            position: absolute;
            top: 4%;
            right: 8%;
            padding: 6px 14px;
            border-radius: 14px;
            background: #fff;
            border: 1px solid #d9dfdb;
            box-shadow: 0 4px 14px rgba(8, 47, 36, .1);
            font-size: 14px;
            font-weight: 700;
            color: #14201a;
            opacity: 0;
            transform: translateY(6px);
            transition: opacity .15s ease, transform .15s ease;
            pointer-events: none;
            z-index: 3;
        }
        .bubble.show { opacity: 1; transform: translateY(0); }
        .caption {
            margin: 18px 0 0;
            min-height: 24px;
            font-size: 14.5px;
            font-weight: 600;
            text-align: center;
            color: var(--au-forest, #082F24);
            transition: opacity .18s ease;
            position: relative;
            z-index: 2;
        }
        .caption.fading { opacity: 0; }

        #breath {
            transform-origin: 220px 230px;
            animation: breath 3.8s ease-in-out infinite;
        }
        @keyframes breath {
            0%, 100% { transform: scale(1, 1); }
            50% { transform: scale(1.018, .985) translateY(2px); }
        }
        .tilt {
            transform-origin: 220px 384px;
            transform: rotate(var(--tilt, 0deg));
            transition: transform .2s ease-out;
        }
        .face {
            transform: translate(var(--fx, 0px), var(--fy, 0px));
            transition: transform .2s ease-out;
        }
        .comb { transform: translate(calc(var(--fx, 0px) * .6), 0); transition: transform .2s ease-out; }
        .pupil {
            transform: translate(var(--px, 0px), var(--py, 0px));
            transition: transform .12s ease-out;
        }
        .eye {
            transform-box: fill-box;
            transform-origin: center;
            transition: transform .08s ease-out;
        }
        #chicken-svg.blink .eye { transform: scaleY(.1); }
        .wing {
            transform-origin: 104px 232px;
            transition: transform .5s cubic-bezier(.34, 1.56, .64, 1);
        }
        [data-state="shy"] .wing,
        [data-state="peek"] .wing-l { transform: rotate(-124deg); }
        .jump { transform-origin: 220px 384px; }
        .jump.do-jump { animation: hop .5s ease-out; }
        @keyframes hop {
            0% { transform: translateY(0) scale(1, 1); }
            15% { transform: translateY(0) scale(1.04, .95); }
            45% { transform: translateY(-28px) scale(.98, 1.03); }
            75% { transform: translateY(0) scale(1.03, .96); }
            100% { transform: translateY(0) scale(1, 1); }
        }
        .headshake { transform-origin: 220px 210px; }
        .nope .headshake { animation: nope .6s ease-in-out; }
        @keyframes nope {
            0%, 100% { transform: translateX(0) rotate(0); }
            15% { transform: translateX(-9px) rotate(-5deg); }
            35% { transform: translateX(9px) rotate(5deg); }
            55% { transform: translateX(-7px) rotate(-4deg); }
            75% { transform: translateX(6px) rotate(3deg); }
        }
        .squat { transform-origin: 220px 384px; }
        .is-laying .squat { animation: squat 1.1s ease-in-out both; }
        @keyframes squat {
            0% { transform: scale(1, 1); }
            20%, 70% { transform: scale(1.05, .86); }
            100% { transform: scale(1, 1); }
        }
        .egg { opacity: 0; }
        .is-laying .egg { animation: lay-egg 1s ease-in .3s both; }
        @keyframes lay-egg {
            0% { opacity: 0; transform: translateY(-34px); }
            12% { opacity: 1; transform: translateY(-34px); }
            45% { opacity: 1; transform: translateY(0); }
            62% { transform: translateY(-14px); }
            78% { transform: translateY(0); }
            88% { transform: translateY(-4px); }
            100% { opacity: 1; transform: translateY(0); }
        }

        .au-headline-compact {
            margin-top: 16px;
            font-size: clamp(1.6rem, 2.8vw, 2.2rem);
        }

        @media (max-width: 1023px) {
            .au-shell {
                grid-template-columns: 1fr;
                gap: 0;
                padding: clamp(12px, 3vw, 20px) 16px 20px;
                min-height: 100vh;
                min-height: 100svh;
                display: flex;
                flex-direction: column;
                justify-content: flex-start;
            }
            .au-brand {
                display: flex;
                flex-direction: column;
                align-items: center;
                text-align: center;
                margin-bottom: -16px;
                position: relative;
                z-index: 2;
            }
            .au-brand .lg-logo-item {
                transform: scale(.85);
                margin-bottom: 2px;
            }
            .au-headline-compact,
            .au-desc {
                display: none !important;
            }
            .mascot-stage-area {
                max-width: 240px;
                margin: 0 auto;
                position: relative;
                display: flex;
                flex-direction: column;
                align-items: center;
            }
            .chicken-wrap {
                max-width: 220px;
                padding-bottom: 0;
                display: flex;
                flex-direction: column;
                align-items: center;
            }
            .chicken-wrap::before {
                width: 90%;
                background: #e9f2eb;
            }
            #chicken-svg {
                margin-bottom: -16px; /* Maskot mengintip manis di atas kartu */
            }
            .bubble {
                font-size: 11px;
                padding: 3px 8px;
                top: 24px;
                right: -8px;
            }
            .caption {
                order: -1; /* Tampil di atas kepala ayam seperti pill balon kata di HP */
                font-size: 12px;
                font-weight: 700;
                color: var(--au-forest, #082F24);
                background: rgba(255, 255, 255, .82);
                border: 1px solid rgba(8, 47, 36, .1);
                border-radius: 999px;
                padding: 4px 14px;
                margin: 2px 0 6px;
                box-shadow: 0 4px 12px rgba(8, 47, 36, .05);
                backdrop-filter: blur(4px);
                max-width: 90%;
            }
            .au-card-col {
                position: relative;
                z-index: 3;
                width: 100%;
                max-width: 420px;
                margin: 0 auto;
            }
            .au-card {
                padding: 22px 18px 24px;
                border-radius: 24px;
                background: #ffffff;
                box-shadow: 0 12px 32px -10px rgba(8, 47, 36, .14);
            }
            .au-card h2 {
                font-size: 22px;
                margin-bottom: 4px;
            }
            .au-sub {
                font-size: 13px;
                margin-bottom: 16px;
            }
            .au-field {
                margin-bottom: 14px;
            }
            .au-row {
                margin: 8px 0 18px;
            }
        }

        /* Penyesuaian khusus layar HP pendek (< 720px tinggi) agar pas 1 layar */
        @media (max-width: 1023px) and (max-height: 720px) {
            .au-shell {
                padding: 10px 14px 16px;
            }
            .au-brand .lg-logo-item {
                transform: scale(.75);
                margin-bottom: -4px;
            }
            .mascot-stage-area {
                max-width: 180px;
            }
            .chicken-wrap {
                max-width: 170px;
            }
            .caption {
                font-size: 11px;
                padding: 2px 10px;
                margin: 0 0 4px;
            }
            .au-card {
                padding: 16px 16px 18px;
                border-radius: 20px;
            }
            .au-card h2 {
                font-size: 20px;
                margin-bottom: 2px;
            }
            .au-sub {
                font-size: 12px;
                margin-bottom: 12px;
            }
            .au-field {
                margin-bottom: 10px;
            }
            .au-row {
                margin: 4px 0 12px;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            .lg-item, #lg-stage { transition: none !important; }
            html:not(.lg-intro) .lg-item { animation: none !important; }
            #breath, .jump.do-jump, .nope .headshake, .is-laying .squat, .is-laying .egg { animation: none; }
            .wing { transition-duration: .01s; }
            .pupil, .face, .comb, .tilt, .eye, .caption, .bubble { transition-duration: .01s; }
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

            <!-- Maskot Ayam Interaktif RansumKu (Pelacak Mouse & Penjaga Sandi) -->
            <div class="mascot-stage-area lg-item" style="--d: .15s">
                <div class="chicken-wrap" id="mascot-stage" data-state="idle">
                    <div class="bubble" id="bubble" aria-hidden="true">Kok kok!</div>

                    <svg id="chicken-svg" viewBox="0 0 440 348" aria-hidden="true" focusable="false">
                        <defs>
                            <g id="wing-shape">
                                <path d="M104 230 C70 246 58 300 88 352 C124 338 144 288 128 244 C123 234 114 230 104 230 Z" fill="#f3eedf" stroke="#ddd5bf" stroke-width="2.5" stroke-linejoin="round"/>
                                <path d="M90 282 Q106 296 124 288" fill="none" stroke="#ddd5bf" stroke-width="2.5" stroke-linecap="round"/>
                                <path d="M88 310 Q106 324 128 314" fill="none" stroke="#ddd5bf" stroke-width="2.5" stroke-linecap="round"/>
                                <path d="M90 334 Q104 344 116 338" fill="none" stroke="#ddd5bf" stroke-width="2.5" stroke-linecap="round"/>
                            </g>
                        </defs>

                        <g transform="translate(0 -56)">
                            <!-- tanah -->
                            <ellipse cx="220" cy="384" rx="130" ry="11" fill="#c6d9cc"/>

                            <g class="jump" id="chicken">
                                <g class="squat">
                                    <!-- kaki -->
                                    <g>
                                        <rect x="188" y="352" width="8" height="30" rx="4" fill="#e9962b"/>
                                        <rect x="244" y="352" width="8" height="30" rx="4" fill="#e9962b"/>
                                        <ellipse cx="192" cy="382" rx="15" ry="5" fill="#e9962b"/>
                                        <ellipse cx="248" cy="382" rx="15" ry="5" fill="#e9962b"/>
                                    </g>

                                    <g class="tilt">
                                        <g id="breath">
                                            <!-- jengger (tiga lingkaran) -->
                                            <g class="comb" fill="#d8402f">
                                                <circle cx="198" cy="94" r="17"/>
                                                <circle cx="220" cy="82" r="22"/>
                                                <circle cx="242" cy="94" r="17"/>
                                            </g>

                                            <!-- badan -->
                                            <circle cx="220" cy="230" r="140" fill="#ffffff" stroke="#d3e0d7" stroke-width="2"/>
                                            <ellipse cx="220" cy="302" rx="100" ry="60" fill="#f5f1e6"/>

                                            <!-- wajah -->
                                            <g class="face">
                                                <g class="headshake">
                                                    <circle cx="146" cy="238" r="13" fill="#f4b8a8" opacity=".55"/>
                                                    <circle cx="294" cy="238" r="13" fill="#f4b8a8" opacity=".55"/>

                                                    <g class="eye">
                                                        <circle cx="178" cy="200" r="22" fill="#ffffff" stroke="#cfd8d3" stroke-width="2"/>
                                                        <g class="pupil">
                                                            <circle cx="178" cy="200" r="10" fill="#14201a"/>
                                                            <circle cx="182" cy="196" r="3" fill="#ffffff"/>
                                                        </g>
                                                    </g>
                                                    <g class="eye">
                                                        <circle cx="262" cy="200" r="22" fill="#ffffff" stroke="#cfd8d3" stroke-width="2"/>
                                                        <g class="pupil">
                                                            <circle cx="262" cy="200" r="10" fill="#14201a"/>
                                                            <circle cx="266" cy="196" r="3" fill="#ffffff"/>
                                                        </g>
                                                    </g>

                                                    <!-- pial -->
                                                    <ellipse cx="220" cy="262" rx="8" ry="14" fill="#d8402f"/>
                                                    <!-- paruh -->
                                                    <path d="M203 224 Q220 214 237 224 Q233 246 220 253 Q207 246 203 224 Z" fill="#f0a22b"/>
                                                </g>
                                            </g>

                                            <!-- sayap kiri -->
                                            <g class="wing wing-l"><use href="#wing-shape"/></g>
                                            <!-- sayap kanan (cermin) -->
                                            <g transform="translate(440 0) scale(-1 1)">
                                                <g class="wing wing-r"><use href="#wing-shape"/></g>
                                            </g>
                                        </g>
                                    </g>

                                    <!-- telur emas -->
                                    <g class="egg">
                                        <ellipse cx="220" cy="374" rx="17" ry="21" fill="#f0c04a"/>
                                        <ellipse cx="214" cy="365" rx="4" ry="7" fill="#f7d77a" opacity=".8"/>
                                    </g>
                                </g>
                            </g>
                        </g>
                    </svg>

                    <p class="caption" id="caption" aria-live="polite">Halo, selamat datang kembali.</p>
                </div>
            </div>

            <h1 class="au-headline au-headline-compact lg-item" style="--d: .25s">Racik Pakan Lebih Cerdas. <span>Kelola Kandang Lebih Terarah.</span></h1>
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

    <!-- Skrip Interaktif Maskot Ayam: Melacak Mouse, Reaksi Input Email & Sandi Tutup Mata -->
    <script>
    (function () {
        'use strict';

        var VB_W = 440;
        var ART_OFFSET_Y = 56;
        var MAX_PUPIL = 7, MAX_FX = 6, MAX_FY = 4, MAX_TILT = 4;
        var DEFAULT_CAPTION = 'Halo, selamat datang kembali.';

        var stage = document.getElementById('mascot-stage');
        var svg = document.getElementById('chicken-svg');
        var bubble = document.getElementById('bubble');
        var caption = document.getElementById('caption');
        var chicken = document.getElementById('chicken');
        var email = document.getElementById('email');
        var pw = document.getElementById('password');
        var toggle = document.getElementById('toggle-password');
        var form = document.getElementById('form-masuk');

        if (!stage || !svg || !email || !pw) return;

        var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
        var state = { zone: null, pwVisible: false, busy: false, mouse: null };

        function clamp(v, a, b) { return Math.min(b, Math.max(a, v)); }

        /* Arah Pandang Mata & Kepala */
        function setGaze(px, py, fx, fy, tilt) {
            svg.style.setProperty('--px', px.toFixed(2) + 'px');
            svg.style.setProperty('--py', py.toFixed(2) + 'px');
            svg.style.setProperty('--fx', fx.toFixed(2) + 'px');
            svg.style.setProperty('--fy', fy.toFixed(2) + 'px');
            svg.style.setProperty('--tilt', tilt.toFixed(2) + 'deg');
        }

        function eyeCenter() {
            var r = svg.getBoundingClientRect();
            var s = r.width / VB_W;
            return { x: r.left + 220 * s, y: r.top + (200 - ART_OFFSET_Y) * s };
        }

        function gazeAt(cx, cy) {
            var c = eyeCenter();
            var dx = cx - c.x, dy = cy - c.y;
            var dist = Math.hypot(dx, dy) || 1;
            var m = clamp(dist / 140, 0, 1);
            var nx = clamp(dx / (window.innerWidth / 2), -1, 1);
            var ny = clamp(dy / (window.innerHeight / 2), -1, 1);
            setGaze(dx / dist * MAX_PUPIL * m, dy / dist * MAX_PUPIL * m, nx * MAX_FX, ny * MAX_FY, nx * MAX_TILT);
        }

        function gazeAtElement(el) {
            var r = el.getBoundingClientRect();
            gazeAt(r.left + r.width / 2, r.top + r.height / 2);
        }

        function gazeEmail() {
            var r = email.getBoundingClientRect();
            var c = eyeCenter();
            var dx = r.left + r.width / 2 - c.x;
            var dy = r.top + r.height / 2 - c.y;
            var dist = Math.hypot(dx, dy) || 1;
            var frac = clamp(email.value.length / 26, 0, 1);
            var wide = window.innerWidth >= 1024;
            var px = wide ? 1.5 + frac * 5.5 : -4 + frac * 8;
            var py = clamp(dy / dist * 5, -2, 5);
            var nx = clamp(dx / (window.innerWidth / 2), -1, 1);
            var ny = clamp(dy / (window.innerHeight / 2), -1, 1);
            setGaze(px, py, nx * MAX_FX, ny * MAX_FY, nx * MAX_TILT);
        }

        function refreshGaze() {
            if (state.busy) return;
            if (state.zone === 'email') { gazeEmail(); }
            else if (state.zone === 'pw') { gazeAtElement(pw); }
            else if (state.mouse) { gazeAt(state.mouse.x, state.mouse.y); }
            else { setGaze(0, 0, 0, 0, 0); }
        }

        var captionTimer = 0;
        function setCaption(text) {
            if (!caption || caption.textContent === text) return;
            window.clearTimeout(captionTimer);
            caption.classList.add('fading');
            captionTimer = window.setTimeout(function () {
                caption.textContent = text;
                caption.classList.remove('fading');
            }, reduceMotion.matches ? 0 : 160);
        }

        function applyState() {
            var s = 'idle';
            if (state.busy) { s = 'laying'; }
            else if (state.zone === 'pw') { s = state.pwVisible ? 'peek' : 'shy'; }
            else if (state.zone === 'email') { s = 'email'; }
            stage.dataset.state = s;
            if (state.busy) return;

            var text = DEFAULT_CAPTION;
            if (s === 'shy') { text = 'Tenang, saya tidak mengintip sandi Anda.'; }
            else if (s === 'peek') { text = 'Ups, saya agak mengintip sedikit!'; }
            else if (s === 'email') { text = 'Masukkan email akun peternakan Anda.'; }
            setCaption(text);
            refreshGaze();
        }

        function zoneOf(el) {
            if (!el) return null;
            if (el === email || el.id === 'email') return 'email';
            if (el === pw || el.id === 'password' || (toggle && toggle.contains(el))) return 'pw';
            return null;
        }

        email.addEventListener('focus', function () {
            state.zone = 'email';
            applyState();
        });
        email.addEventListener('blur', function () {
            if (state.zone === 'email') {
                state.zone = null;
                applyState();
            }
        });

        pw.addEventListener('focus', function () {
            state.zone = 'pw';
            applyState();
        });
        pw.addEventListener('blur', function (e) {
            if (state.zone === 'pw' && (!toggle || !toggle.contains(e.relatedTarget))) {
                state.zone = null;
                applyState();
            }
        });

        document.addEventListener('focusin', function (e) {
            var z = zoneOf(e.target);
            if (z) {
                state.zone = z;
                applyState();
            }
        });

        document.addEventListener('focusout', function (e) {
            var next = zoneOf(e.relatedTarget);
            if (!next) {
                state.zone = null;
                applyState();
            }
        });

        email.addEventListener('input', function () {
            if (state.zone === 'email') gazeEmail();
        });

        if (toggle) {
            toggle.addEventListener('click', function () {
                state.zone = 'pw';
                window.setTimeout(function () {
                    state.pwVisible = (pw.type === 'text');
                    applyState();
                }, 40);
            });
        }

        /* Sensor Gerakan Mouse & Sentuhan Mobile (Real-time Gaze Tracking) */
        var rafId = 0;
        function trackPoint(x, y) {
            state.mouse = { x: x, y: y };
            if (state.zone || state.busy || rafId) return;
            rafId = window.requestAnimationFrame(function () {
                rafId = 0;
                if (!state.zone && !state.busy && state.mouse) {
                    gazeAt(state.mouse.x, state.mouse.y);
                }
            });
        }

        window.addEventListener('pointermove', function (e) {
            trackPoint(e.clientX, e.clientY);
        }, { passive: true });

        window.addEventListener('pointerdown', function (e) {
            trackPoint(e.clientX, e.clientY);
        }, { passive: true });

        document.documentElement.addEventListener('mouseleave', function () {
            state.mouse = null;
            if (!state.zone && !state.busy) setGaze(0, 0, 0, 0, 0);
        });

        window.addEventListener('resize', refreshGaze);

        /* Berkedip Otomatis */
        function scheduleBlink() {
            window.setTimeout(function () {
                svg.classList.add('blink');
                window.setTimeout(function () { svg.classList.remove('blink'); }, 140);
                scheduleBlink();
            }, 2400 + Math.random() * 3200);
        }
        if (!reduceMotion.matches) scheduleBlink();

        /* Reaksi Klik pada Maskot */
        var bubbleTimer = 0;
        svg.addEventListener('click', function () {
            if (state.busy) return;
            if (!reduceMotion.matches && chicken) {
                chicken.classList.remove('do-jump');
                void chicken.getBoundingClientRect();
                chicken.classList.add('do-jump');
            }
            if (bubble) {
                bubble.classList.add('show');
                window.clearTimeout(bubbleTimer);
                bubbleTimer = window.setTimeout(function () { bubble.classList.remove('show'); }, 1100);
            }
        });

        if (chicken) {
            chicken.addEventListener('animationend', function (e) {
                if (e.animationName === 'hop') chicken.classList.remove('do-jump');
            });
        }

        /* Reaksi Saat Form Dikirim */
        if (form) {
            form.addEventListener('submit', function () {
                if (!form.checkValidity()) return;
                state.busy = true;
                setGaze(0, 2, 0, 0, 0);
                applyState();
                stage.classList.add('is-laying');
            });
        }

        {{-- Jika ada error server dari redirect sebelumnya, ayam menggelengkan kepala --}}
        @if ($errors->any())
        window.addEventListener('DOMContentLoaded', function () {
            if (!reduceMotion.matches && svg) {
                svg.classList.add('nope');
                setCaption('Email atau kata sandi belum sesuai.');
                window.setTimeout(function () { svg.classList.remove('nope'); }, 650);
            }
        });
        @endif
    })();
    </script>
</body>
</html>