{{-- Skrip bersama form Masuk & Daftar: validasi inline, tombol mata (halaman daftar), indikator sandi, transisi antarhalaman.
     Tidak menyentuh logika autentikasi (form tetap POST biasa) maupun animasi intro 3D (resources/js/login.js). --}}
<script>
(function () {
    'use strict';

    var reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    /* Tombol yang membuka/menutup catatan (mis. "Lupa kata sandi?") */
    document.querySelectorAll('[data-toggle-target]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var target = document.querySelector(btn.getAttribute('data-toggle-target'));
            if (!target) { return; }
            var willOpen = target.hidden;
            target.hidden = !willOpen;
            btn.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
        });
    });

    /* Tombol mata. Halaman masuk memakai #toggle-password yang ditangani login.js, jadi tidak diikat di sini. */
    document.querySelectorAll('[data-toggle-password]').forEach(function (btn) {
        var input = document.getElementById(btn.getAttribute('data-toggle-password'));
        if (!input) { return; }
        btn.addEventListener('click', function () {
            var show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            btn.setAttribute('aria-pressed', show ? 'true' : 'false');
            btn.setAttribute('aria-label', show ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi');
            btn.classList.toggle('is-on', show);
            var icon = btn.querySelector('.material-symbols-outlined');
            if (icon) { icon.textContent = show ? 'visibility_off' : 'visibility'; }
        });
    });

    /* Transisi halus antara halaman masuk dan daftar */
    document.querySelectorAll('a[data-auth-switch]').forEach(function (link) {
        link.addEventListener('click', function (e) {
            if (reduceMotion || e.defaultPrevented || e.metaKey || e.ctrlKey || e.shiftKey || e.button !== 0) { return; }
            e.preventDefault();
            document.documentElement.classList.add('au-leaving');
            window.setTimeout(function () { window.location.href = link.href; }, 220);
        });
    });
    window.addEventListener('pageshow', function () {
        document.documentElement.classList.remove('au-leaving');
    });

    var form = document.querySelector('form[data-auth-form]');
    if (!form) { return; }

    var submit = form.querySelector('[data-auth-submit]');
    var controls = Array.prototype.slice.call(form.querySelectorAll('input[name]')).filter(function (el) {
        return el.name !== '_token';
    });

    function fieldOf(el) { return el.closest('[data-field]'); }

    function labelOf(el) {
        var field = fieldOf(el);
        var label = field && field.querySelector('.au-label');
        return label ? label.textContent.trim() : 'Kolom ini';
    }

    function messageFor(el) {
        var v = el.validity;
        if (v.valueMissing) {
            return el.type === 'checkbox'
                ? (el.getAttribute('data-required-message') || 'Centang kotak ini untuk melanjutkan.')
                : labelOf(el) + ' wajib diisi.';
        }
        if (v.typeMismatch) { return 'Format email belum benar. Contoh: nama@email.com'; }
        if (v.tooShort) { return labelOf(el) + ' minimal ' + el.getAttribute('minlength') + ' karakter.'; }
        if (v.customError) { return el.validationMessage; }
        return 'Isian belum valid.';
    }

    function setError(el, message) {
        el.classList.add('is-invalid');
        el.setAttribute('aria-invalid', 'true');
        var field = fieldOf(el);
        var box = field && field.querySelector('.au-field-error');
        if (!box) { return; }
        box.querySelector('[data-error-text]').textContent = message;
        box.hidden = false;
    }

    function clearError(el) {
        el.classList.remove('is-invalid');
        el.setAttribute('aria-invalid', 'false');
        var field = fieldOf(el);
        var box = field && field.querySelector('.au-field-error');
        if (box) { box.hidden = true; }
    }

    function check(el, show) {
        if (el.checkValidity()) {
            clearError(el);
            return true;
        }
        if (show) {
            if (el.hasAttribute('data-match-input') && el.validity.customError) {
                /* Umpan balik ketidakcocokan sudah ditampilkan oleh indikator "tidak cocok". */
                el.classList.add('is-invalid');
                el.setAttribute('aria-invalid', 'true');
            } else {
                setError(el, messageFor(el));
            }
        }
        return false;
    }

    function refreshSubmit() {
        if (!submit || submit.classList.contains('is-loading')) { return; }
        submit.disabled = !form.checkValidity();
    }

    controls.forEach(function (el) {
        el.addEventListener('input', function () {
            el.dataset.dirty = '1';
            if (el.classList.contains('is-invalid')) { check(el, true); }
            refreshSubmit();
        });
        el.addEventListener('change', refreshSubmit);
        el.addEventListener('blur', function (e) {
            if (el.dataset.dirty !== '1') { return; }
            if (e.relatedTarget && e.relatedTarget.closest && e.relatedTarget.closest('a')) { return; }
            check(el, true);
        });
    });

    form.addEventListener('submit', function (e) {
        var firstInvalid = null;
        controls.forEach(function (el) {
            if (!check(el, true) && !firstInvalid) { firstInvalid = el; }
        });
        if (firstInvalid) {
            e.preventDefault();
            firstInvalid.focus();
            return;
        }
        if (submit && !submit.classList.contains('is-loading')) {
            submit.classList.add('is-loading');
            submit.setAttribute('aria-busy', 'true');
            window.setTimeout(function () { submit.disabled = true; }, 0);
        }
    });

    /* Pengisian otomatis browser tidak selalu memicu event input, jadi status tombol dicek berkala. */
    window.setInterval(refreshSubmit, 400);
    refreshSubmit();

    /* Indikator kekuatan kata sandi & kecocokan konfirmasi (hanya halaman daftar) */
    var password = document.getElementById('password');
    var confirmInput = document.getElementById('password_confirmation');
    var meter = form.querySelector('[data-strength]');
    var meterLabel = form.querySelector('[data-strength-label]');
    var matchBox = form.querySelector('[data-match]');
    var levels = ['', 'Lemah', 'Sedang', 'Kuat', 'Sangat kuat'];

    function scorePassword(value) {
        if (!value) { return 0; }
        var score = 0;
        if (value.length >= 8) { score++; }
        if (/[a-z]/.test(value) && /[A-Z]/.test(value)) { score++; }
        if (/\d/.test(value)) { score++; }
        if (/[^A-Za-z0-9]/.test(value) || value.length >= 12) { score++; }
        return Math.max(score, 1);
    }

    function updateStrength() {
        if (!meter || !password) { return; }
        var level = scorePassword(password.value);
        meter.setAttribute('data-level', String(level));
        if (meterLabel) { meterLabel.textContent = level ? 'Kekuatan kata sandi: ' + levels[level] : ''; }
    }

    function updateMatch() {
        if (!confirmInput || !password) { return; }
        if (!confirmInput.value) {
            confirmInput.setCustomValidity('');
            if (matchBox) { matchBox.hidden = true; }
            return;
        }
        var same = confirmInput.value === password.value;
        confirmInput.setCustomValidity(same ? '' : 'Konfirmasi kata sandi tidak sama.');
        if (matchBox) {
            matchBox.hidden = false;
            matchBox.className = 'au-match ' + (same ? 'is-ok' : 'is-bad');
            matchBox.querySelector('.material-symbols-outlined').textContent = same ? 'check_circle' : 'cancel';
            matchBox.querySelector('[data-match-text]').textContent = same ? 'Kata sandi cocok' : 'Kata sandi tidak cocok';
        }
        if (same) { clearError(confirmInput); }
        refreshSubmit();
    }

    if (password) {
        password.addEventListener('input', function () { updateStrength(); updateMatch(); });
    }
    if (confirmInput) {
        confirmInput.setAttribute('data-match-input', '1');
        confirmInput.addEventListener('input', updateMatch);
    }
    updateStrength();
    updateMatch();
})();
</script>

