{{-- Gaya bersama halaman Masuk & Daftar RansumKu (design tokens + komponen auth). --}}
<style>
    :root {
        --au-warm: #F7F8F3;
        --au-emerald: #087B50;
        --au-emerald-hover: #0a8d5c;
        --au-forest: #082F24;
        --au-lime: #C6F36A;
        --au-gray: #E7ECE6;
        --au-ink: #17231D;
        --au-text-2: #4b5d54;
        --au-muted: #5a6b62;
        --au-placeholder: #6b7a72;
        --au-error: #D92D20;
        --au-error-soft: #fdeceb;
        --au-success: #12B76A;
        --au-success-ink: #067647;
        --au-warn: #F79009;
    }

    .au-body {
        margin: 0;
        min-height: 100vh;
        min-height: 100dvh;
        background: var(--au-warm);
        color: var(--au-ink);
        font-family: 'Plus Jakarta Sans', Inter, ui-sans-serif, system-ui, sans-serif;
        overflow-x: hidden;
        -webkit-font-smoothing: antialiased;
    }

    /* Latar: glow zamrud kiri atas, lime kanan bawah, + noise tipis agar tidak datar */
    .au-decor {
        position: fixed;
        inset: 0;
        z-index: 0;
        pointer-events: none;
        background:
            radial-gradient(900px 560px at 6% -10%, rgba(8, 123, 80, .16), transparent 62%),
            radial-gradient(780px 520px at 100% 108%, rgba(198, 243, 106, .40), transparent 62%);
    }
    .au-decor::after {
        content: '';
        position: absolute;
        inset: 0;
        opacity: .05;
        background-image: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='160' height='160'><filter id='n'><feTurbulence type='fractalNoise' baseFrequency='.9' numOctaves='2' stitchTiles='stitch'/></filter><rect width='100%25' height='100%25' filter='url(%23n)' opacity='.6'/></svg>");
    }

    /* Layout dua kolom sama lebar, keduanya center secara vertikal */
    .au-shell {
        position: relative;
        z-index: 3;
        box-sizing: border-box;
        width: 100%;
        max-width: 1200px;
        min-height: 100vh;
        min-height: 100dvh;
        margin: 0 auto;
        padding: clamp(24px, 4vw, 48px) clamp(20px, 4vw, 32px);
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: clamp(48px, 7vw, 96px);
        align-items: center;
    }
    .au-brand { min-width: 0; }
    .au-card-col { display: flex; justify-content: center; min-width: 0; }

    .au-brand-row { display: flex; align-items: center; gap: 14px; }
    .au-logo {
        width: var(--au-logo-size, 56px);
        height: var(--au-logo-size, 56px);
        border-radius: 999px;
        object-fit: contain;
        background: #fff;
        box-shadow: 0 8px 20px -8px rgba(8, 47, 36, .35);
    }
    .au-wordmark { font-size: 24px; font-weight: 800; letter-spacing: -.02em; color: var(--au-forest); }
    .au-tagline { margin: 10px 0 0; font-size: 14px; font-weight: 600; color: var(--au-muted); }

    .au-headline {
        margin: 28px 0 0;
        font-size: clamp(2.25rem, 4vw, 3.5rem);
        line-height: 1.1;
        font-weight: 800;
        letter-spacing: -.035em;
        color: var(--au-forest);
        text-wrap: balance;
    }
    .au-headline span {
        display: block;
        background: linear-gradient(100deg, #087B50, #0a8d5c 60%, #2aa56f);
        -webkit-background-clip: text;
        background-clip: text;
        -webkit-text-fill-color: transparent;
        color: var(--au-emerald);
    }
    .au-desc { margin: 20px 0 0; max-width: 46ch; font-size: 16px; line-height: 1.7; color: var(--au-text-2); }

    .au-chips { display: flex; flex-wrap: wrap; gap: 10px; margin: 28px 0 0; padding: 0; list-style: none; }
    .au-chips li {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 8px 14px 8px 10px;
        border-radius: 999px;
        background: #fff;
        border: 1px solid var(--au-gray);
        font-size: 13px;
        font-weight: 600;
        color: var(--au-forest);
        white-space: nowrap;
        transition: transform .18s, border-color .18s, box-shadow .18s;
    }
    .au-chips li:hover { transform: translateY(-1px); border-color: rgba(8, 123, 80, .45); box-shadow: 0 8px 18px -12px rgba(8, 47, 36, .45); }
    .au-chips .au-ico-chip {
        display: grid;
        place-items: center;
        width: 24px;
        height: 24px;
        border-radius: 999px;
        background: rgba(198, 243, 106, .55);
        color: var(--au-emerald);
        font-size: 15px;
    }
    .au-trust { display: flex; align-items: center; gap: 8px; margin: 22px 0 0; font-size: 13px; font-weight: 600; color: var(--au-muted); }
    .au-trust .material-symbols-outlined { font-size: 18px; color: var(--au-emerald); }

    .au-benefits { display: grid; gap: 14px; margin: 26px 0 0; padding: 0; list-style: none; max-width: 46ch; }
    .au-benefits li { display: flex; align-items: flex-start; gap: 12px; font-size: 15px; line-height: 1.55; color: var(--au-text-2); }
    .au-benefits .au-ico-chip {
        flex: none;
        display: grid;
        place-items: center;
        width: 30px;
        height: 30px;
        border-radius: 10px;
        background: #fff;
        border: 1px solid var(--au-gray);
        color: var(--au-emerald);
        font-size: 18px;
    }

    /* Kartu */
    .au-card {
        box-sizing: border-box;
        width: 100%;
        max-width: 480px;
        padding: 40px 36px;
        border-radius: 28px;
        background: #fff;
        border: 1px solid var(--au-gray);
        box-shadow:
            0 1px 2px rgba(8, 47, 36, .05),
            0 12px 24px -12px rgba(8, 47, 36, .14),
            0 40px 80px -36px rgba(8, 47, 36, .32);
    }
    .au-card h2 { margin: 0; font-size: 26px; line-height: 1.2; font-weight: 800; letter-spacing: -.02em; color: var(--au-forest); }
    .au-card .au-sub { margin: 8px 0 24px; font-size: 14.5px; line-height: 1.55; color: var(--au-muted); }

    /* Field */
    .au-field { margin-bottom: 18px; }
    .au-label { display: block; margin-bottom: 8px; font-size: 13.5px; font-weight: 600; color: var(--au-ink); }
    .au-input-wrap { position: relative; }
    .au-input {
        width: 100%;
        height: 50px;
        padding: 0 16px;
        border-radius: 14px;
        border: 1.5px solid var(--au-gray);
        background: var(--au-warm);
        color: var(--au-ink);
        font-size: 15px;
        font-weight: 500;
        font-family: inherit;
        box-sizing: border-box;
        transition: border-color .2s, box-shadow .2s, background .2s;
    }
    .au-input::placeholder { color: var(--au-placeholder); opacity: 1; }
    .au-input:hover { border-color: #cbd5cd; }
    .au-input:focus { outline: none; border-color: var(--au-emerald); background: #fff; box-shadow: 0 0 0 4px rgba(8, 123, 80, .18); }
    .au-input.is-invalid { border-color: var(--au-error); background: #fffafa; }
    .au-input.is-invalid:focus { box-shadow: 0 0 0 4px rgba(217, 45, 32, .16); }
    .au-input.has-toggle { padding-right: 54px; }
    .au-hint { margin: 7px 0 0; font-size: 12.5px; color: var(--au-muted); }
    .au-field-error { display: flex; align-items: flex-start; gap: 6px; margin: 7px 0 0; font-size: 13px; font-weight: 500; line-height: 1.4; color: var(--au-error); }
    .au-field-error[hidden], .au-banner[hidden], .au-note[hidden], .au-match[hidden] { display: none; }
    .au-ico { font-size: 17px; line-height: 1.2; flex: none; }

    .au-eye {
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
        color: #5f7168;
        cursor: pointer;
    }
    .au-eye:hover { background: var(--au-gray); color: var(--au-forest); }
    .au-eye:focus-visible { outline: 3px solid rgba(8, 123, 80, .4); }
    .au-eye.is-on { color: var(--au-emerald); }

    /* Kekuatan kata sandi & kecocokan */
    .au-strength { display: none; margin-top: 10px; }
    .au-strength[data-level]:not([data-level="0"]) { display: block; }
    .au-strength-bars { display: grid; grid-template-columns: repeat(4, 1fr); gap: 6px; }
    .au-strength-bars i { height: 5px; border-radius: 99px; background: var(--au-gray); transition: background .2s; }
    .au-strength[data-level="1"] i:nth-child(-n+1) { background: var(--au-error); }
    .au-strength[data-level="2"] i:nth-child(-n+2) { background: var(--au-warn); }
    .au-strength[data-level="3"] i:nth-child(-n+3) { background: var(--au-success); }
    .au-strength[data-level="4"] i:nth-child(-n+4) { background: var(--au-emerald); }
    .au-strength-label { margin: 6px 0 0; font-size: 12.5px; font-weight: 600; color: var(--au-muted); }
    .au-match { display: flex; align-items: center; gap: 6px; margin: 7px 0 0; font-size: 13px; font-weight: 600; }
    .au-match.is-ok { color: var(--au-success-ink); }
    .au-match.is-bad { color: var(--au-error); }

    /* Checkbox */
    .au-field-check { margin-bottom: 18px; }
    .au-check { display: flex; align-items: flex-start; gap: 10px; font-size: 14px; line-height: 1.5; color: var(--au-text-2); cursor: pointer; }
    .au-check input { flex: none; width: 18px; height: 18px; margin: 2px 0 0; accent-color: var(--au-emerald); cursor: pointer; }
    .au-check input:focus-visible { outline: 3px solid rgba(8, 123, 80, .4); outline-offset: 2px; }

    .au-row { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin: 4px 0 20px; flex-wrap: wrap; }
    .au-row .au-field-check { margin: 0; }

    /* Tautan */
    .au-link {
        padding: 0;
        border: 0;
        background: none;
        font: inherit;
        font-size: 14px;
        font-weight: 700;
        color: var(--au-emerald);
        text-decoration: none;
        cursor: pointer;
    }
    .au-link:hover { text-decoration: underline; }
    .au-link:focus-visible { outline: 3px solid rgba(8, 123, 80, .4); outline-offset: 2px; border-radius: 4px; }

    /* Banner & catatan */
    .au-banner {
        display: flex;
        gap: 10px;
        margin-bottom: 18px;
        padding: 12px 14px;
        border-radius: 14px;
        font-size: 14px;
        line-height: 1.5;
    }
    .au-banner.is-error { background: var(--au-error-soft); border: 1px solid #f5c4bf; color: #8a1c14; }
    .au-banner ul { margin: 0; padding-left: 16px; }
    .au-note {
        margin: 0 0 18px;
        padding: 12px 14px;
        border-radius: 14px;
        background: #eef6f1;
        border: 1px solid #cfe5d8;
        color: var(--au-forest);
        font-size: 13.5px;
        line-height: 1.55;
    }

    /* Tombol utama */
    .au-btn {
        position: relative;
        width: 100%;
        height: 52px;
        border: 0;
        border-radius: 999px;
        background: linear-gradient(135deg, #0a8d5c, var(--au-emerald));
        color: #fff;
        font-size: 15px;
        font-weight: 700;
        font-family: inherit;
        cursor: pointer;
        box-shadow: 0 12px 24px -12px rgba(8, 123, 80, .7);
        transition: transform .15s, box-shadow .2s, filter .2s, opacity .2s;
    }
    .au-btn:hover:not(:disabled) { transform: translateY(-2px); filter: brightness(1.05); box-shadow: 0 18px 30px -12px rgba(8, 123, 80, .75); }
    .au-btn:active:not(:disabled) { transform: translateY(0) scale(.99); box-shadow: 0 8px 16px -10px rgba(8, 123, 80, .7); }
    .au-btn:focus-visible { outline: 3px solid rgba(8, 123, 80, .45); outline-offset: 3px; }
    .au-btn:disabled { cursor: not-allowed; opacity: .55; box-shadow: none; }
    .au-btn.is-loading:disabled { cursor: progress; opacity: .85; }
    .au-btn-loading { display: none; align-items: center; justify-content: center; gap: 8px; }
    .au-btn.is-loading .au-btn-label { display: none; }
    .au-btn.is-loading .au-btn-loading { display: inline-flex; }
    .au-spin { width: 18px; height: 18px; border-radius: 99px; border: 2.5px solid rgba(255, 255, 255, .4); border-top-color: #fff; animation: au-rot .7s linear infinite; }
    @keyframes au-rot { to { transform: rotate(360deg); } }

    .au-foot { margin: 22px 0 0; text-align: center; font-size: 14px; color: var(--au-muted); }
    .au-foot a { color: var(--au-emerald); font-weight: 800; text-decoration: none; }
    .au-foot a:hover { text-decoration: underline; }
    .au-foot a:focus-visible { outline: 3px solid rgba(8, 123, 80, .4); outline-offset: 2px; border-radius: 4px; }

    /* Animasi masuk halaman & transisi keluar antarhalaman (login <-> daftar) */
    .au-rise { animation: au-rise .5s cubic-bezier(.2, .7, .2, 1) both; animation-delay: var(--d, 0s); }
    @keyframes au-rise { from { opacity: 0; transform: translateY(14px); } to { opacity: 1; transform: none; } }
    html.au-leaving .au-shell { opacity: 0; transform: translateY(8px); transition: opacity .22s ease, transform .22s ease; }

    @media (max-width: 1023px) {
        .au-shell { grid-template-columns: 1fr; gap: 28px; padding: 72px 18px 32px; align-content: center; }
        .au-brand { text-align: center; }
        .au-brand-row { justify-content: center; }
        .au-headline { margin-top: 18px; font-size: clamp(1.6rem, 6.4vw, 2.2rem); }
        .au-desc, .au-benefits, .au-trust, .au-desktop-only { display: none; }
        .au-chips {
            flex-wrap: nowrap;
            justify-content: flex-start;
            margin: 18px -18px 0;
            padding: 2px 18px 6px;
            overflow-x: auto;
            scrollbar-width: none;
        }
        .au-chips::-webkit-scrollbar { display: none; }
        .au-card { padding: 28px 22px; border-radius: 24px; }
    }
    @media (max-width: 420px) {
        .au-wordmark { font-size: 21px; }
        .au-card h2 { font-size: 23px; }
    }
    @media (prefers-reduced-motion: reduce) {
        .au-rise { animation: none !important; }
        .au-btn, .au-input, .au-chips li, .au-strength-bars i { transition: none !important; }
        .au-btn:hover:not(:disabled) { transform: none; }
        .au-spin { animation-duration: 1.6s; }
        html.au-leaving .au-shell { transition: none; }
    }
</style>

