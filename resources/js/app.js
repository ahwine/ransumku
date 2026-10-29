import { Chart, registerables } from 'chart.js';

Chart.register(...registerables);
Chart.defaults.font.family = "'Poppins', 'Plus Jakarta Sans', sans-serif";
Chart.defaults.color = '#5C7A6D';

// Grafik dibuat dari <canvas data-chart='{...}'> agar Blade cukup mengirim JSON.
document.querySelectorAll('canvas[data-chart]').forEach((el) => {
    const cfg = JSON.parse(el.dataset.chart);
    new Chart(el, {
        type: cfg.type,
        data: { labels: cfg.labels, datasets: cfg.datasets },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: cfg.legend ?? false,
                    position: 'bottom',
                    labels: {
                        boxWidth: 8,
                        boxHeight: 8,
                        usePointStyle: true,
                        pointStyle: 'circle',
                        padding: 14,
                        font: { size: 11, family: "'Plus Jakarta Sans', 'Poppins', sans-serif" },
                        color: '#5C7A6D'
                    }
                },
                tooltip: {
                    backgroundColor: '#002415',
                    titleFont: { family: "'Plus Jakarta Sans', sans-serif" },
                    bodyFont: { family: "'Plus Jakarta Sans', sans-serif" },
                    cornerRadius: 8,
                    padding: 8
                }
            },
            scales: {
                x: {
                    grid: { display: false },
                    ticks: {
                        font: { size: 10, family: "'Plus Jakarta Sans', 'Poppins', sans-serif" },
                        color: '#5C7A6D'
                    }
                },
                y: {
                    beginAtZero: cfg.zero ?? true,
                    border: { dash: [3, 3] },
                    grid: {
                        color: '#E0EBE4',
                        drawTicks: false
                    },
                    ticks: {
                        font: { size: 10, family: "'Plus Jakarta Sans', 'Poppins', sans-serif" },
                        color: '#9CA3AF'
                    }
                }
            },
        },
    });
});

// =========================================================================
// KHUSUS HALAMAN BUKU KANDANG: Animasi Progresif Kiri ke Kanan (4 Grafik)
// Terisolasi 100% menggunakan data-buku-chart, tidak menyentuh halaman lain
// =========================================================================
const bukuSweepClipPlugin = {
    id: 'bukuSweepClip',
    beforeDatasetDraw(chart, args) {
        if (chart.config.type !== 'line') return;
        if (!chart._isSweepActive) return;

        const progress = chart._sweepProgress !== undefined ? chart._sweepProgress : 1;
        if (progress >= 1) return;

        const { ctx, chartArea } = chart;
        if (!chartArea) return;

        const clipWidth = (chartArea.right - chartArea.left) * Math.max(0, Math.min(1, progress));
        const currentRight = chartArea.left + clipWidth;

        ctx.save();
        ctx.beginPath();
        ctx.rect(chartArea.left - 4, chartArea.top - 14, Math.max(0, clipWidth + 4), chartArea.height + 28);
        ctx.clip();
        chart._isClipped = true;

        // Titik data muncul saat sapuan garis mencapai titik tersebut
        if (args.meta && args.meta.data) {
            const baseRadius = chart.data.datasets[args.index]?.pointRadius ?? 4;
            args.meta.data.forEach((pt) => {
                const dist = currentRight - pt.x;
                if (dist < 0) {
                    pt.options.radius = 0;
                } else if (dist < 22) {
                    const bloom = Math.sin((dist / 22) * (Math.PI / 2));
                    pt.options.radius = baseRadius * bloom;
                } else {
                    pt.options.radius = baseRadius;
                }
            });
        }
    },
    afterDatasetDraw(chart) {
        if (chart._isClipped) {
            chart.ctx.restore();
            chart._isClipped = false;
        }
    }
};

// Helper: Animasi garis bergerak dari kiri ke kanan (Progressive Line Drawing)
function animateBukuLine(chart, delayMs = 0, durationMs = 1200) {
    if (!chart || !chart.ctx) return;

    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        chart._sweepProgress = 1;
        chart._isSweepActive = false;
        chart.update('none');
        return;
    }

    if (chart._sweepRaf) {
        cancelAnimationFrame(chart._sweepRaf);
        chart._sweepRaf = null;
    }

    chart._sweepProgress = 0;
    chart._isSweepActive = true;
    chart.draw();

    let start = null;

    function step(now) {
        if (!start) start = now;
        const elapsed = now - start;

        if (elapsed < delayMs) {
            chart._sweepProgress = 0;
            chart.draw();
            chart._sweepRaf = requestAnimationFrame(step);
            return;
        }

        const animElapsed = elapsed - delayMs;
        const rawT = Math.min(1, animElapsed / durationMs);

        // Ease-out cubic: halus dan bertahap
        const easeT = 1 - Math.pow(1 - rawT, 3);

        chart._sweepProgress = easeT;
        chart.draw();

        if (rawT < 1) {
            chart._sweepRaf = requestAnimationFrame(step);
        } else {
            chart._sweepProgress = 1;
            chart._isSweepActive = false;
            if (chart.data?.datasets?.[0] && chart.getDatasetMeta(0)?.data) {
                const baseRadius = chart.data.datasets[0].pointRadius ?? 4;
                chart.getDatasetMeta(0).data.forEach((pt) => {
                    pt.options.radius = baseRadius;
                });
            }
            chart.draw();
            chart._sweepRaf = null;
        }
    }

    chart._sweepRaf = requestAnimationFrame(step);
}

// Helper: Animasi batang muncul satu per satu dari baseline kiri ke kanan
function animateBukuBar(chart, delayMs = 280) {
    if (!chart || !chart.ctx) return;

    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        chart.update('none');
        return;
    }

    if (chart._barTimer) {
        clearTimeout(chart._barTimer);
        chart._barTimer = null;
    }

    chart.reset(); // Batang ditahan di baseline 0

    chart._barTimer = setTimeout(() => {
        chart.options.animation = {
            duration: 650,
            easing: 'easeOutQuart',
        };
        chart.options.animations = {
            y: {
                type: 'number',
                duration: 650,
                easing: 'easeOutQuart',
                delay(ctx) {
                    if (ctx.type !== 'data' || ctx.mode !== 'default') return 0;
                    return ctx.index * 75; // Jeda 75ms antar-batang dari kiri ke kanan
                }
            }
        };
        chart.update();
    }, delayMs);
}

// Inisialisasi 4 grafik Buku Kandang
const bukuChartsMap = {};

document.querySelectorAll('canvas[data-buku-chart]').forEach((el) => {
    const cfg = JSON.parse(el.dataset.bukuChart);
    const chartName = el.dataset.chartName;

    const chart = new Chart(el, {
        type: cfg.type,
        data: { labels: cfg.labels, datasets: cfg.datasets },
        plugins: cfg.type === 'line' ? [bukuSweepClipPlugin] : [],
        options: {
            responsive: true,
            maintainAspectRatio: false,
            animation: false, // Animasi dikendalikan oleh orkestrator terkoordinasi
            plugins: {
                legend: {
                    display: cfg.legend ?? false,
                    position: 'bottom',
                    labels: {
                        boxWidth: 8,
                        boxHeight: 8,
                        usePointStyle: true,
                        pointStyle: 'circle',
                        padding: 14,
                        font: { size: 11, family: "'Plus Jakarta Sans', 'Poppins', sans-serif" },
                        color: '#5C7A6D'
                    }
                },
                tooltip: {
                    backgroundColor: '#002415',
                    titleFont: { family: "'Plus Jakarta Sans', sans-serif" },
                    bodyFont: { family: "'Plus Jakarta Sans', sans-serif" },
                    cornerRadius: 8,
                    padding: 8
                }
            },
            scales: {
                x: {
                    grid: { display: false },
                    ticks: {
                        font: { size: 10, family: "'Plus Jakarta Sans', 'Poppins', sans-serif" },
                        color: '#5C7A6D'
                    }
                },
                y: {
                    beginAtZero: cfg.zero ?? true,
                    border: { dash: [3, 3] },
                    grid: {
                        color: '#E0EBE4',
                        drawTicks: false
                    },
                    ticks: {
                        font: { size: 10, family: "'Plus Jakarta Sans', 'Poppins', sans-serif" },
                        color: '#9CA3AF',
                        padding: 6,
                        callback(val) {
                            if (val >= 1000000) return (val / 1000000).toLocaleString('id-ID', { maximumFractionDigits: 1 }) + ' jt';
                            if (val >= 10000) return (val / 1000).toLocaleString('id-ID') + ' rb';
                            return val.toLocaleString('id-ID');
                        }
                    }
                }
            },
        },
    });

    if (chartName) {
        bukuChartsMap[chartName] = chart;
    }
    el._bukuChartInstance = chart;
});

// Orkestrasi urutan animasi 4 grafik Buku Kandang
function triggerBukuKandangOrchestration() {
    const container = document.querySelector('[data-buku-grafik-container]');
    if (!container) return;

    // Periksa apakah tab dashboard sedang aktif
    const panel = container.closest('[data-panel]');
    if (panel && panel.classList.contains('hidden')) return;

    // 1. Fade-in halus pada kartu grafik
    const cards = container.querySelectorAll('.buku-card-fadein');
    cards.forEach((c) => {
        c.style.animation = 'none';
        void c.offsetHeight; // trigger reflow
        c.style.animation = '';
    });

    // Resize instans grafik jika diperlukan
    Object.values(bukuChartsMap).forEach((ci) => {
        if (ci) ci.resize();
    });

    // 2. Grafik Konsumsi Pakan (delay: 80ms, durasi: 1200ms)
    if (bukuChartsMap.pakan) {
        animateBukuLine(bukuChartsMap.pakan, 80, 1200);
    }

    // 3. Grafik Produksi Telur (batang muncul berurutan dari kiri ke kanan, delay: 280ms)
    if (bukuChartsMap.telur) {
        animateBukuBar(bukuChartsMap.telur, 280);
    }

    // 4. Grafik Biaya Pakan Harian (delay: 480ms, durasi: 1200ms)
    if (bukuChartsMap.biaya) {
        animateBukuLine(bukuChartsMap.biaya, 480, 1200);
    }

    // 5. Grafik Efisiensi: Biaya per Kg Telur (delay: 680ms, durasi: 1200ms)
    if (bukuChartsMap.efisiensi) {
        animateBukuLine(bukuChartsMap.efisiensi, 680, 1200);
    }
}

window.__triggerBukuKandangAnimation = triggerBukuKandangOrchestration;

// IntersectionObserver untuk memicu animasi satu kali saat pertama kali terlihat
let hasAnimatedBukuOnView = false;
const bukuContainerEl = document.querySelector('[data-buku-grafik-container]');
if (bukuContainerEl) {
    if ('IntersectionObserver' in window) {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    const panel = bukuContainerEl.closest('[data-panel]');
                    if (!panel || !panel.classList.contains('hidden')) {
                        if (!hasAnimatedBukuOnView) {
                            hasAnimatedBukuOnView = true;
                            triggerBukuKandangOrchestration();
                        }
                    }
                }
            });
        }, { threshold: 0.15 });

        observer.observe(bukuContainerEl);
    } else {
        setTimeout(triggerBukuKandangOrchestration, 200);
    }
}

// Apple-style Sliding Segmented Tabs ([data-tabs])
function initSlidingTabs() {
    const tabGroups = document.querySelectorAll('[data-tabs]');

    tabGroups.forEach((group) => {
        const nav = group.querySelector('[data-tab-nav]') || group.querySelector('.tab-segmented-container') || group.querySelector('.grid.rounded-full');
        if (!nav) return;

        nav.classList.add('relative');

        let slider = nav.querySelector('[data-tab-slider]');
        if (!slider) {
            slider = document.createElement('div');
            slider.className = 'tab-slider';
            slider.setAttribute('data-tab-slider', '');
            slider.setAttribute('aria-hidden', 'true');
            nav.prepend(slider);
        }

        const buttons = Array.from(group.querySelectorAll('[data-tab]'));
        if (buttons.length === 0) return;

        const updateSlider = (targetBtn, animate = true) => {
            if (!slider || !targetBtn) return;

            if (!animate) {
                slider.style.transition = 'none';
            } else {
                slider.style.transition = 'transform 340ms cubic-bezier(0.25, 1, 0.5, 1), width 340ms cubic-bezier(0.25, 1, 0.5, 1), height 340ms cubic-bezier(0.25, 1, 0.5, 1)';
            }

            const x = targetBtn.offsetLeft;
            const y = targetBtn.offsetTop;
            const w = targetBtn.offsetWidth;
            const h = targetBtn.offsetHeight;

            slider.style.width = `${w}px`;
            slider.style.height = `${h}px`;
            slider.style.transform = `translate3d(${x}px, ${y}px, 0)`;

            if (!animate) {
                void slider.offsetHeight; // Force reflow
                requestAnimationFrame(() => {
                    slider.style.transition = 'transform 340ms cubic-bezier(0.25, 1, 0.5, 1), width 340ms cubic-bezier(0.25, 1, 0.5, 1), height 340ms cubic-bezier(0.25, 1, 0.5, 1)';
                });
            }
        };

        const activeBtn = buttons.find((b) => b.classList.contains('active-tab')) ||
            buttons.find((b) => b.classList.contains('bg-white')) ||
            buttons.find((b) => b.classList.contains('bg-brand')) ||
            buttons[0];

        let currentIndex = buttons.indexOf(activeBtn);
        if (currentIndex === -1) currentIndex = 0;

        const switchTab = (targetBtn, animate = true) => {
            if (!targetBtn) return;
            const tabId = targetBtn.dataset.tab;
            const targetIndex = buttons.indexOf(targetBtn);
            const direction = targetIndex >= currentIndex ? 'right' : 'left';
            currentIndex = targetIndex;

            buttons.forEach((b) => {
                const isActive = b === targetBtn;
                b.classList.toggle('active-tab', isActive);
                b.classList.remove('bg-brand', 'bg-white', 'shadow-xs', 'shadow-sm', 'text-white');

                if (isActive) {
                    b.classList.remove('text-muted');
                    b.classList.add('text-ink', 'font-bold');
                } else {
                    b.classList.remove('text-ink');
                    b.classList.add('text-muted', 'hover:text-ink', 'font-semibold');
                }
            });

            updateSlider(targetBtn, animate);

            const panels = group.querySelectorAll('[data-panel]');
            panels.forEach((p) => {
                const isMatch = p.dataset.panel === tabId;
                if (isMatch) {
                    p.classList.remove('hidden', 'tab-slide-right', 'tab-slide-left', 'tab-panel-enter');
                    if (animate) {
                        void p.offsetWidth; // Force reflow
                        p.classList.add(direction === 'right' ? 'tab-slide-right' : 'tab-slide-left');
                    }
                } else {
                    p.classList.add('hidden');
                    p.classList.remove('tab-slide-right', 'tab-slide-left', 'tab-panel-enter');
                }
            });

            try {
                const url = new URL(window.location.href);
                if (url.searchParams.get('tab') !== tabId) {
                    url.searchParams.set('tab', tabId);
                    window.history.replaceState({ tab: tabId }, '', url.toString());
                }
            } catch (e) {
                // Ignore url parse errors
            }

            // Resize Chart.js instances cleanly if visible
            setTimeout(() => {
                if (window.Chart && window.Chart.instances) {
                    Object.values(window.Chart.instances).forEach((ci) => ci.resize());
                }
            }, 60);

            if (tabId === 'dashboard' && typeof window.__triggerBukuKandangAnimation === 'function') {
                setTimeout(() => {
                    window.__triggerBukuKandangAnimation();
                }, 75);
            }
        };

        buttons.forEach((btn) => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                switchTab(btn, true);
            });
        });

        // Set initial state without animation
        switchTab(activeBtn, false);
        slider.classList.add('is-ready');

        // Double check position on next tick to handle any font/layout shifts
        requestAnimationFrame(() => updateSlider(activeBtn, false));
        setTimeout(() => updateSlider(activeBtn, false), 50);

        let prevW = window.innerWidth;
        let prevH = window.innerHeight;
        window.addEventListener('resize', () => {
            if (window.innerWidth !== prevW || window.innerHeight !== prevH) {
                prevW = window.innerWidth;
                prevH = window.innerHeight;
                const currentActive = buttons.find((b) => b.classList.contains('active-tab')) || buttons[0];
                updateSlider(currentActive, false);
            }
        });

        if (document.fonts?.ready) {
            document.fonts.ready.then(() => {
                const currentActive = buttons.find((b) => b.classList.contains('active-tab')) || buttons[0];
                updateSlider(currentActive, false);
            });
        }
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initSlidingTabs);
} else {
    initSlidingTabs();
}

// Mode jumlah campuran: kg langsung / berdasarkan hari
document.querySelectorAll('input[name="mode"]').forEach((r) => {
    const sync = () => {
        const mode = document.querySelector('input[name="mode"]:checked')?.value;
        document.querySelectorAll('[data-mode]').forEach((p) => p.classList.toggle('hidden', p.dataset.mode !== mode));
    };
    r.addEventListener('change', sync);
    sync();
});

// Hitung total kg otomatis pada mode hari
const hitungHari = () => {
    const h = parseFloat(document.getElementById('jumlah_hari')?.value || 0);
    const a = parseFloat(document.getElementById('jumlah_ayam')?.value || 0);
    const k = parseFloat((document.getElementById('konsumsi')?.value || '0').replace(',', '.'));
    const out = document.getElementById('total_hari');
    if (out) out.textContent = (h * a * k).toLocaleString('id-ID', { maximumFractionDigits: 1 });
};
['jumlah_hari', 'jumlah_ayam', 'konsumsi'].forEach((id) => document.getElementById(id)?.addEventListener('input', hitungHari));
hitungHari();

// Custom Floating Dropdown (Sentra Peternakan & City Selector)
window.toggleCustomDropdown = function (dropdownId, event) {
    if (event) {
        event.stopPropagation();
    }
    const container = document.getElementById(dropdownId);
    if (!container) return;

    const menu = container.querySelector('.dropdown-menu');
    const caret = container.querySelector('.dropdown-caret');
    const trigger = container.querySelector('.dropdown-trigger');
    const isOpen = menu && !menu.classList.contains('hidden');

    // Close all custom dropdowns first
    document.querySelectorAll('.custom-dropdown-container').forEach((c) => {
        const m = c.querySelector('.dropdown-menu');
        const crt = c.querySelector('.dropdown-caret');
        const trg = c.querySelector('.dropdown-trigger');
        if (m) m.classList.add('hidden');
        c.classList.remove('z-50');
        const sec = c.closest('[data-dropdown-section]');
        if (sec) sec.style.zIndex = '';
        if (crt) crt.classList.remove('rotate-180');
        if (trg) {
            trg.setAttribute('aria-expanded', 'false');
            trg.classList.remove('ring-2', 'ring-brand/20', 'border-brand');
        }
    });

    if (!isOpen && menu) {
        menu.classList.remove('hidden');
        container.classList.add('z-50');
        const sec = container.closest('[data-dropdown-section]');
        if (sec) sec.style.zIndex = '999';
        if (caret) caret.classList.add('rotate-180');
        if (trigger) {
            trigger.setAttribute('aria-expanded', 'true');
            trigger.classList.add('ring-2', 'ring-brand/20', 'border-brand');
        }
    }
};

window.selectCustomDropdownKota = function (dropdownId, value) {
    const container = document.getElementById(dropdownId);
    if (!container) return;

    const form = container.querySelector('form');
    const hiddenInput = container.querySelector('input[name="kota"]');
    if (hiddenInput && form) {
        hiddenInput.value = value;
        form.submit();
    }
};

// Global event: Click outside closes any active dropdown
document.addEventListener('click', (e) => {
    if (!e.target.closest('.custom-dropdown-container')) {
        document.querySelectorAll('.custom-dropdown-container').forEach((c) => {
            const m = c.querySelector('.dropdown-menu');
            const crt = c.querySelector('.dropdown-caret');
            const trg = c.querySelector('.dropdown-trigger');
            if (m) m.classList.add('hidden');
            c.classList.remove('z-50');
            const sec = c.closest('[data-dropdown-section]');
            if (sec) sec.style.zIndex = '';
            if (crt) crt.classList.remove('rotate-180');
            if (trg) {
                trg.setAttribute('aria-expanded', 'false');
                trg.classList.remove('ring-2', 'ring-brand/20', 'border-brand');
            }
        });
    }
});

// Global event: Escape key closes any active dropdown
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        document.querySelectorAll('.custom-dropdown-container').forEach((c) => {
            const m = c.querySelector('.dropdown-menu');
            const crt = c.querySelector('.dropdown-caret');
            const trg = c.querySelector('.dropdown-trigger');
            if (m) m.classList.add('hidden');
            c.classList.remove('z-50');
            const sec = c.closest('[data-dropdown-section]');
            if (sec) sec.style.zIndex = '';
            if (crt) crt.classList.remove('rotate-180');
            if (trg) {
                trg.setAttribute('aria-expanded', 'false');
                trg.classList.remove('ring-2', 'ring-brand/20', 'border-brand');
            }
        });
    }
});

