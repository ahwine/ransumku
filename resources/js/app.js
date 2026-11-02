import { Chart, registerables } from 'chart.js';

Chart.register(...registerables);
Chart.defaults.font.family = "'Poppins', 'Plus Jakarta Sans', sans-serif";
Chart.defaults.color = '#5C7A6D';

// =========================================================================
// UNIVERSAL PROGRESSIVE ANIMATION ENGINE UNTUK GRAFIK & STATISTIK
// Menjamin konsistensi startup animasi di seluruh dashboard (Beranda & Buku Kandang)
// =========================================================================
const bukuSweepClipPlugin = {
    id: 'bukuSweepClip',
    beforeInit(chart) {
        // Tahan kondisi awal agar tidak ada blip / flash sebelum animasi dimulai
        if (chart.config.type === 'line') {
            if (!window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
                chart._isSweepActive = true;
                chart._sweepProgress = 0;
            }
        }
    },
    beforeDatasetDraw(chart, args) {
        if (chart.config.type !== 'line') return;
        if (!chart._isSweepActive) return;

        const progress = chart._sweepProgress !== undefined ? chart._sweepProgress : 1;
        if (progress >= 1) return;

        const { ctx, chartArea } = chart;
        if (!chartArea) return;

        const clipWidth = (chartArea.right - chartArea.left) * Math.max(0, Math.min(1, progress));

        ctx.save();
        ctx.beginPath();
        ctx.rect(chartArea.left - 4, chartArea.top - 14, Math.max(0, clipWidth + 4), chartArea.height + 28);
        ctx.clip();
        chart._isClipped = true;

        // FASE 1: Selama garis bergerak, bola-bola disembunyikan (radius = 0)
        // Bola-bola akan muncul bertahap satu per satu setelah garis selesai
        if (args.meta && args.meta.data) {
            args.meta.data.forEach((pt) => {
                pt.options = { ...pt.options, radius: 0, borderWidth: 0 };
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

// Register plugin globally
Chart.register(bukuSweepClipPlugin);

// Helper: Animasi titik/bola-bola muncul bertahap satu per satu dari kiri ke kanan (Sequential Dot Bloom)
function animateSequentialDots(chart, onComplete) {
    if (!chart || !chart.ctx || !chart.data?.datasets) return;

    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        chart.data.datasets.forEach((ds, idx) => {
            const meta = chart.getDatasetMeta(idx);
            if (meta && meta.data) {
                const baseRadius = ds.pointRadius ?? 4;
                meta.data.forEach(pt => {
                    pt.options.radius = baseRadius;
                    pt.options.borderWidth = ds.pointBorderWidth ?? 2;
                });
            }
        });
        chart.draw();
        if (onComplete) onComplete();
        return;
    }

    if (chart._dotsRaf) {
        cancelAnimationFrame(chart._dotsRaf);
        chart._dotsRaf = null;
    }

    let maxPoints = 0;
    chart.data.datasets.forEach((ds, idx) => {
        const meta = chart.getDatasetMeta(idx);
        if (meta && meta.data) {
            maxPoints = Math.max(maxPoints, meta.data.length);
        }
    });

    if (maxPoints === 0) {
        if (onComplete) onComplete();
        return;
    }

    // Kecepatan jelas bertahap (~95-105ms antar-bola) agar bola terlihat jelas mekar satu per satu
    const staggerMs = Math.max(80, Math.min(115, 700 / maxPoints));
    const bloomDurationMs = 260;
    const totalDuration = (maxPoints - 1) * staggerMs + bloomDurationMs;

    let startTime = null;

    // Kurva mekar elastis (ease-out back): 0 -> mekar 109% -> kembali pas ke ukuran aslinya
    function easeOutBack(t) {
        const s = 1.6;
        const p = t - 1;
        return 1 + (s + 1) * Math.pow(p, 3) + s * Math.pow(p, 2);
    }

    // KRUSIAL: Putus sharedOptions reference di Chart.js agar radius tiap titik independen
    chart.data.datasets.forEach((ds, idx) => {
        const meta = chart.getDatasetMeta(idx);
        if (meta && meta.data) {
            meta.data.forEach(pt => {
                pt.options = { ...pt.options, radius: 0, borderWidth: 0 };
            });
        }
    });
    chart.draw();

    function step(now) {
        if (!startTime) startTime = now;
        const elapsed = now - startTime;

        chart.data.datasets.forEach((ds, idx) => {
            const meta = chart.getDatasetMeta(idx);
            if (!meta || !meta.data) return;

            const baseRadius = ds.pointRadius ?? 4;
            const baseBorder = ds.pointBorderWidth ?? 2;
            if (baseRadius <= 0) return;

            meta.data.forEach((pt, i) => {
                const pointStart = i * staggerMs;
                const pointElapsed = elapsed - pointStart;

                if (pointElapsed <= 0) {
                    pt.options.radius = 0;
                    pt.options.borderWidth = 0;
                } else if (pointElapsed < bloomDurationMs) {
                    const norm = pointElapsed / bloomDurationMs;
                    const scale = easeOutBack(norm);
                    pt.options.radius = Math.max(0, baseRadius * scale);
                    pt.options.borderWidth = Math.max(0.5, baseBorder * Math.min(1, scale));
                } else {
                    pt.options.radius = baseRadius;
                    pt.options.borderWidth = baseBorder;
                }
            });
        });

        chart.draw();

        if (elapsed < totalDuration) {
            chart._dotsRaf = requestAnimationFrame(step);
        } else {
            // Selesai seluruh tahapan: kembalikan ke baseRadius dan baseBorder normal
            chart.data.datasets.forEach((ds, idx) => {
                const meta = chart.getDatasetMeta(idx);
                if (meta && meta.data) {
                    const baseRadius = ds.pointRadius ?? 4;
                    const baseBorder = ds.pointBorderWidth ?? 2;
                    meta.data.forEach(pt => {
                        pt.options.radius = baseRadius;
                        pt.options.borderWidth = baseBorder;
                    });
                }
            });
            chart.draw();
            chart._dotsRaf = null;
            if (onComplete) onComplete();
        }
    }

    chart._dotsRaf = requestAnimationFrame(step);
}

// Helper: Animasi garis bergerak dari kiri ke kanan (Progressive Line Drawing)
function animateBukuLine(chart, delayMs = 0, durationMs = 1000) {
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
    if (chart._dotsRaf) {
        cancelAnimationFrame(chart._dotsRaf);
        chart._dotsRaf = null;
    }

    // Tahan titik bola di radius 0 selama garis belum selesai dan putus sharedOptions
    if (chart.data?.datasets) {
        chart.data.datasets.forEach((ds, idx) => {
            const meta = chart.getDatasetMeta(idx);
            if (meta && meta.data) {
                meta.data.forEach((pt) => {
                    pt.options = { ...pt.options, radius: 0, borderWidth: 0 };
                });
            }
        });
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

        // Ease-out cubic: sapuan garis mulus dari kiri ke kanan
        const easeT = 1 - Math.pow(1 - rawT, 3);

        chart._sweepProgress = easeT;
        chart.draw();

        if (rawT < 1) {
            chart._sweepRaf = requestAnimationFrame(step);
        } else {
            // Selesai Fase 1: Garis selesai menyapu
            chart._sweepProgress = 1;
            chart._isSweepActive = false;
            chart._sweepRaf = null;

            // Beri jeda mikro 40ms agar sapuan garis terasa tuntas sebelum bola pertama mekar
            setTimeout(() => {
                animateSequentialDots(chart);
            }, 40);
        }
    }

    chart._sweepRaf = requestAnimationFrame(step);
}

// Helper: Animasi batang muncul satu per satu dari baseline kiri ke kanan
function animateBukuBar(chart, delayMs = 280, durationMs = 650) {
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
            duration: durationMs,
            easing: 'easeOutQuart',
        };
        chart.options.animations = {
            y: {
                type: 'number',
                duration: durationMs,
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

// Factory konfigurasi & inisialisasi Chart seragam
function createStandardChart(el, cfg) {
    if (cfg.type === 'line' && Array.isArray(cfg.datasets)) {
        cfg.datasets.forEach((ds) => {
            if (!ds.borderDash) {
                ds.borderWidth = ds.borderWidth ?? 2.5;
                ds.pointRadius = ds.pointRadius ?? 4;
                ds.pointHoverRadius = ds.pointHoverRadius ?? 6;
                ds.pointBackgroundColor = ds.pointBackgroundColor ?? '#FFFFFF';
                ds.pointBorderColor = ds.pointBorderColor ?? ds.borderColor;
                ds.pointBorderWidth = ds.pointBorderWidth ?? 2;
            } else {
                ds.pointRadius = ds.pointRadius ?? 0;
                ds.pointHoverRadius = ds.pointHoverRadius ?? 0;
            }
        });
    }

    if (cfg.type === 'bar' && Array.isArray(cfg.datasets)) {
        cfg.datasets.forEach((ds) => {
            ds.borderRadius = ds.borderRadius ?? 0;
            delete ds.barThickness;
            ds.maxBarThickness = ds.maxBarThickness ?? 38;
        });
    }

    return new Chart(el, {
        type: cfg.type,
        data: { labels: cfg.labels, datasets: cfg.datasets },
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
                    titleFont: { family: "'Plus Jakarta Sans', sans-serif", weight: 'bold', size: 12 },
                    bodyFont: { family: "'Plus Jakarta Sans', sans-serif", size: 11 },
                    cornerRadius: 8,
                    padding: 10,
                    boxPadding: 4,
                    callbacks: {
                        label(ctx) {
                            const val = ctx.parsed.y ?? ctx.raw;
                            const label = ctx.dataset.label || '';
                            if (val === undefined || val === null) return label;
                            const isIntegerChart = cfg.precision === 0 || el.dataset?.chartName === 'populasi';
                            const formatted = typeof val === 'number'
                                ? (isIntegerChart ? Math.round(val).toLocaleString('id-ID') : val.toLocaleString('id-ID', { maximumFractionDigits: 2 }))
                                : val;
                            return ` ${label}: ${formatted}`;
                        }
                    }
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
                        precision: cfg.precision ?? (el.dataset?.chartName === 'populasi' ? 0 : undefined),
                        stepSize: cfg.stepSize ?? (el.dataset?.chartName === 'populasi' ? 1 : undefined),
                        font: { size: 10, family: "'Plus Jakarta Sans', 'Poppins', sans-serif" },
                        color: '#9CA3AF',
                        padding: 6,
                        callback(val) {
                            // Untuk grafik populasi atau integer, jangan tampilkan nilai pecahan
                            const isIntegerChart = cfg.precision === 0 || el.dataset?.chartName === 'populasi';
                            if (isIntegerChart && !Number.isInteger(val)) {
                                return null;
                            }
                            if (val >= 1000000) return (val / 1000000).toLocaleString('id-ID', { maximumFractionDigits: 1 }) + ' jt';
                            if (val >= 10000) return (val / 1000).toLocaleString('id-ID') + ' rb';
                            return val.toLocaleString('id-ID');
                        }
                    }
                }
            },
        },
    });

    // Isolasi objek options tiap titik agar tidak berbagi referensi (sharedOptions) di Chart.js
    if (cfg.type === 'line' && chart.data?.datasets) {
        chart.data.datasets.forEach((ds, idx) => {
            const meta = chart.getDatasetMeta(idx);
            if (meta && meta.data) {
                meta.data.forEach((pt) => {
                    pt.options = { ...pt.options };
                });
            }
        });
    }

    return chart;
}

// Inisialisasi peta instans grafik
const bukuChartsMap = {};
const berandaChartsMap = {};

// 1. Inisialisasi Canvas Buku Kandang
document.querySelectorAll('canvas[data-buku-chart]').forEach((el) => {
    const cfg = JSON.parse(el.dataset.bukuChart);
    const chartName = el.dataset.chartName;
    const chart = createStandardChart(el, cfg);
    if (chartName) {
        bukuChartsMap[chartName] = chart;
    }
    el._bukuChartInstance = chart;
});

// 2. Inisialisasi Canvas Beranda & Standalone
document.querySelectorAll('canvas[data-chart]').forEach((el) => {
    const cfg = JSON.parse(el.dataset.chart);
    const chartName = el.dataset.chartName;
    const chart = createStandardChart(el, cfg);

    const isBerandaGrid = el.closest('[data-beranda-grafik-container]');
    if (isBerandaGrid && chartName) {
        berandaChartsMap[chartName] = chart;
    } else if (!isBerandaGrid) {
        // Standalone chart di luar beranda: picu animasi langsung
        if (cfg.type === 'line') {
            animateBukuLine(chart, 80, 1200);
        } else if (cfg.type === 'bar') {
            animateBukuBar(chart, 80);
        }
    }
    el._chartInstance = chart;
});

// Orkestrasi urutan animasi 4 grafik Buku Kandang
function triggerBukuKandangOrchestration() {
    const container = document.querySelector('[data-buku-grafik-container]');
    if (!container) return;

    // Periksa apakah tab dashboard sedang aktif
    const panel = container.closest('[data-panel]');
    if (panel && panel.classList.contains('hidden')) return;

    // 1. Fade-in halus pada kartu grafik
    const cards = container.querySelectorAll('.buku-card-fadein, .stat-card-fadein');
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

// Orkestrasi urutan animasi 4 kuadran grafik Beranda Dashboard
function triggerBerandaOrchestration() {
    const container = document.querySelector('[data-beranda-grafik-container]');
    if (!container) return;

    // Resize instans grafik jika diperlukan
    Object.values(berandaChartsMap).forEach((ci) => {
        if (ci) ci.resize();
    });

    // 2. Kuadran 1: Produksi Telur (batang berurutan kiri ke kanan, delay: 60ms)
    if (berandaChartsMap.telur) {
        animateBukuBar(berandaChartsMap.telur, 60);
    }

    // 3. Kuadran 2: Populasi Ayam (garis menyapu kiri ke kanan, delay: 180ms, durasi: 1000ms)
    if (berandaChartsMap.populasi) {
        animateBukuLine(berandaChartsMap.populasi, 180, 1000);
    }

    // 4. Kuadran 3: Konsumsi Pakan vs SNI (garis menyapu kiri ke kanan, delay: 320ms, durasi: 1000ms)
    if (berandaChartsMap.pakan) {
        animateBukuLine(berandaChartsMap.pakan, 320, 1000);
    }

    // 5. Kuadran 4: Efisiensi & FCR vs SNI (garis menyapu kiri ke kanan, delay: 460ms, durasi: 1000ms)
    if (berandaChartsMap.fcr) {
        animateBukuLine(berandaChartsMap.fcr, 460, 1000);
    }
}

window.__triggerBukuKandangAnimation = triggerBukuKandangOrchestration;
window.__triggerBerandaAnimation = triggerBerandaOrchestration;

// IntersectionObserver untuk Buku Kandang
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

// IntersectionObserver & Auto-trigger untuk Beranda Dashboard
let hasAnimatedBerandaOnView = false;
const berandaContainerEl = document.querySelector('[data-beranda-grafik-container]');
if (berandaContainerEl) {
    // Pemicu langsung jika container sudah ada di dalam viewport layar saat boot
    const rect = berandaContainerEl.getBoundingClientRect();
    if (rect.top < window.innerHeight && rect.bottom > 0) {
        hasAnimatedBerandaOnView = true;
        setTimeout(triggerBerandaOrchestration, 50);
    }

    if ('IntersectionObserver' in window) {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    if (!hasAnimatedBerandaOnView) {
                        hasAnimatedBerandaOnView = true;
                        triggerBerandaOrchestration();
                    }
                }
            });
        }, { threshold: 0.15 });

        observer.observe(berandaContainerEl);
    } else if (!hasAnimatedBerandaOnView) {
        setTimeout(triggerBerandaOrchestration, 150);
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

            if (typeof window.__triggerBerandaAnimation === 'function') {
                const berandaInPanel = group.querySelector(`[data-panel="${tabId}"] [data-beranda-grafik-container]`);
                if (berandaInPanel) {
                    setTimeout(() => {
                        window.__triggerBerandaAnimation();
                    }, 75);
                }
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
        const hdr = c.closest('header');
        if (hdr) hdr.style.zIndex = '';
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
        const hdr = container.closest('header');
        if (hdr) hdr.style.zIndex = '999';
        if (caret) caret.classList.add('rotate-180');
        if (trigger) {
            trigger.setAttribute('aria-expanded', 'true');
            trigger.classList.add('ring-2', 'ring-brand/20', 'border-brand');
        }
        const searchInput = container.querySelector('[data-dropdown-search]');
        if (searchInput) {
            setTimeout(() => {
                searchInput.focus();
                container.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }, 80);
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

// Form Dropdown Selection Handler for City Selector (No direct submit)
window.selectCustomDropdownKotaInput = function(dropdownId, value, nama, info) {
    const container = document.getElementById(dropdownId);
    if (!container) return;

    const hidden = container.querySelector('.dropdown-hidden-input') || container.querySelector('input[type="hidden"]');
    if (hidden) {
        hidden.value = value;
        hidden.dispatchEvent(new Event('change', { bubbles: true }));
    }

    const labelEl = container.querySelector('.dropdown-selected-label');
    if (labelEl) {
        labelEl.textContent = nama;
    }
    const infoEl = container.querySelector('.dropdown-selected-info');
    if (infoEl && info) {
        infoEl.textContent = info;
    }

    const items = container.querySelectorAll('[data-city-item]');
    items.forEach((item) => {
        const itemVal = item.getAttribute('data-city-key');
        const isSelected = itemVal === value;

        item.classList.toggle('bg-surface', isSelected);
        item.classList.toggle('border-brand/30', isSelected);
        item.classList.toggle('shadow-2xs', isSelected);
        item.classList.toggle('font-bold', isSelected);
        item.classList.toggle('hover:bg-surface/80', !isSelected);
        item.classList.toggle('text-ink/80', !isSelected);
        item.classList.toggle('font-medium', !isSelected);

        const iconEl = item.querySelector('[data-item-icon]');
        if (iconEl) {
            iconEl.className = isSelected
                ? 'material-symbols-outlined text-[18px] shrink-0 text-brand transition-colors'
                : 'material-symbols-outlined text-[18px] shrink-0 text-muted group-hover:text-brand transition-colors';
        }

        const badge = item.querySelector('[data-active-badge]');
        const chooseHint = item.querySelector('[data-choose-hint]');
        if (badge) badge.classList.toggle('hidden', !isSelected);
        if (chooseHint) chooseHint.classList.toggle('hidden', isSelected);
    });

    window.tutupCustomDropdown(dropdownId);
};

// Universal Custom Form Dropdown Option Selector
window.selectCustomDropdownOption = function(dropdownId, value, label, sublabel, icon) {
    const container = document.getElementById(dropdownId);
    if (!container) return;

    const hidden = container.querySelector('.dropdown-hidden-input') || container.querySelector('input[type="hidden"]');
    if (hidden) {
        hidden.value = value;
        hidden.dispatchEvent(new Event('change', { bubbles: true }));
    }

    const labelEl = container.querySelector('.dropdown-selected-label');
    if (labelEl && label !== undefined) {
        labelEl.textContent = label;
    }
    const sublabelEl = container.querySelector('.dropdown-selected-sublabel');
    if (sublabelEl && sublabel !== undefined) {
        sublabelEl.textContent = sublabel;
    }
    const iconEl = container.querySelector('.dropdown-selected-icon');
    if (iconEl && icon) {
        iconEl.textContent = icon;
    }

    const items = container.querySelectorAll('[data-dropdown-item]');
    items.forEach((item) => {
        const itemVal = item.getAttribute('data-value');
        const isSelected = String(itemVal) === String(value);

        item.classList.toggle('bg-surface', isSelected);
        item.classList.toggle('border-brand/30', isSelected);
        item.classList.toggle('shadow-2xs', isSelected);
        item.classList.toggle('font-bold', isSelected);
        item.classList.toggle('hover:bg-surface/80', !isSelected);
        item.classList.toggle('text-ink/80', !isSelected);
        item.classList.toggle('font-medium', !isSelected);

        const iconBox = item.querySelector('[data-item-icon-box]');
        if (iconBox) {
            iconBox.className = isSelected
                ? 'w-7 h-7 rounded-lg flex items-center justify-center shrink-0 bg-brand text-white transition-colors'
                : 'w-7 h-7 rounded-lg flex items-center justify-center shrink-0 bg-surface-2 text-muted group-hover:bg-brand/10 group-hover:text-brand transition-colors';
        }

        const badge = item.querySelector('[data-active-badge]');
        const chooseHint = item.querySelector('[data-choose-hint]');
        if (badge) badge.classList.toggle('hidden', !isSelected);
        if (chooseHint) chooseHint.classList.toggle('hidden', isSelected);
    });

    window.tutupCustomDropdown(dropdownId);
};

window.tutupCustomDropdown = function(dropdownId) {
    const container = document.getElementById(dropdownId);
    if (!container) return;
    const menu = container.querySelector('.dropdown-menu');
    const caret = container.querySelector('.dropdown-caret');
    const trigger = container.querySelector('.dropdown-trigger');

    if (menu) menu.classList.add('hidden');
    container.classList.remove('z-50');
    const sec = container.closest('[data-dropdown-section]');
    if (sec) sec.style.zIndex = '';
    if (caret) caret.classList.remove('rotate-180');
    if (trigger) {
        trigger.setAttribute('aria-expanded', 'false');
        trigger.classList.remove('ring-2', 'ring-brand/20', 'border-brand');
    }
};

window.filterCustomDropdown = function(input, dropdownId) {
    const container = document.getElementById(dropdownId);
    if (!container) return;

    const query = (input.value || '').trim().toLowerCase();
    const clearBtn = container.querySelector('[data-dropdown-clear]');
    if (clearBtn) {
        clearBtn.classList.toggle('hidden', query.length === 0);
    }

    const items = container.querySelectorAll('[data-dropdown-item]');
    let matchCount = 0;

    items.forEach((item) => {
        const searchText = (item.getAttribute('data-search-text') || item.textContent || '').toLowerCase();
        const match = query.length === 0 || searchText.includes(query);
        item.style.display = match ? '' : 'none';
        if (match) matchCount++;
    });

    const emptyState = container.querySelector('[data-dropdown-empty]');
    if (emptyState) {
        emptyState.classList.toggle('hidden', matchCount > 0);
    }
};

window.clearCustomDropdownSearch = function(btn, dropdownId) {
    const container = document.getElementById(dropdownId);
    if (!container) return;
    const input = container.querySelector('[data-dropdown-search]');
    if (input) {
        input.value = '';
        input.focus();
        window.filterCustomDropdown(input, dropdownId);
    }
};

// Filter Pencarian Real-Time Kota / Kabupaten Se-Indonesia
window.filterDropdownKota = function(input, dropdownId) {
    const container = document.getElementById(dropdownId);
    if (!container) return;

    const query = (input.value || '').trim().toLowerCase();
    const clearBtn = container.querySelector('[data-dropdown-clear]');
    if (clearBtn) {
        clearBtn.classList.toggle('hidden', query.length === 0);
    }

    const activeWilayahBtn = container.querySelector('[data-wilayah-btn].bg-brand');
    const selectedWilayah = activeWilayahBtn ? activeWilayahBtn.getAttribute('data-wilayah-btn') : 'Semua';

    const items = container.querySelectorAll('[data-city-item]');
    let matchCount = 0;

    items.forEach((item) => {
        const searchText = (item.getAttribute('data-search-text') || '').toLowerCase();
        const itemWilayah = item.getAttribute('data-city-wilayah') || '';
        const matchQuery = query.length === 0 || searchText.includes(query);
        const matchWilayah = selectedWilayah === 'Semua' || itemWilayah === selectedWilayah;

        if (matchQuery && matchWilayah) {
            item.style.display = '';
            matchCount++;
        } else {
            item.style.display = 'none';
        }
    });

    const emptyState = container.querySelector('[data-dropdown-empty]');
    if (emptyState) {
        emptyState.classList.toggle('hidden', matchCount > 0);
    }
};

window.clearDropdownKota = function(btn, dropdownId) {
    const container = document.getElementById(dropdownId);
    if (!container) return;
    const input = container.querySelector('[data-dropdown-search]');
    if (input) {
        input.value = '';
        input.focus();
        window.filterDropdownKota(input, dropdownId);
    }
};

window.filterDropdownWilayah = function(wilayah, dropdownId, targetBtn) {
    const container = document.getElementById(dropdownId);
    if (!container) return;

    // Update active button state
    container.querySelectorAll('[data-wilayah-btn]').forEach((b) => {
        const isActive = b === targetBtn;
        b.className = isActive
            ? 'px-2.5 py-0.5 rounded-full whitespace-nowrap transition-all font-bold cursor-pointer bg-brand text-white'
            : 'px-2.5 py-0.5 rounded-full whitespace-nowrap transition-all font-semibold cursor-pointer bg-surface text-muted hover:text-ink hover:bg-surface-2 border border-line/60';
    });

    const searchInput = container.querySelector('[data-dropdown-search]');
    if (searchInput) {
        window.filterDropdownKota(searchInput, dropdownId);
    }
};

// Filter Pencarian Cepat Katalog Bahan Pakan (/bahan)
window.filterBahanKatalog = function(input) {
    const query = (input.value || '').trim().toLowerCase();
    const clearBtn = document.getElementById('clear-search-bahan');
    if (clearBtn) {
        clearBtn.classList.toggle('hidden', query.length === 0);
    }

    const cards = document.querySelectorAll('[data-bahan-card]');
    let count = 0;
    cards.forEach((card) => {
        const nama = (card.getAttribute('data-bahan-nama') || '').toLowerCase();
        const match = query.length === 0 || nama.includes(query);
        card.style.display = match ? '' : 'none';
        if (match) count++;
    });

    const empty = document.getElementById('bahan-empty-filter');
    if (empty) {
        empty.classList.toggle('hidden', count > 0);
    }
};

window.clearFilterBahanKatalog = function() {
    const input = document.getElementById('search-bahan-live');
    if (input) {
        input.value = '';
        input.focus();
        window.filterBahanKatalog(input);
    }
};

// Filter Pencarian Cepat Bahan Racik Ransum (/ransum/baru)
window.filterBahanRacikan = function(input) {
    const query = (input.value || '').trim().toLowerCase();
    const clearBtn = document.getElementById('clear-search-bahan-racik');
    if (clearBtn) {
        clearBtn.classList.toggle('hidden', query.length === 0);
    }

    const cards = document.querySelectorAll('[data-racik-item]');
    let count = 0;
    cards.forEach((card) => {
        const nama = (card.getAttribute('data-bahan-nama') || '').toLowerCase();
        const match = query.length === 0 || nama.includes(query);
        card.style.display = match ? '' : 'none';
        if (match) count++;
    });

    const empty = document.getElementById('racik-empty-filter');
    if (empty) {
        empty.classList.toggle('hidden', count > 0);
    }
};

window.clearFilterBahanRacikan = function() {
    const input = document.getElementById('search-bahan-racik');
    if (input) {
        input.value = '';
        input.focus();
        window.filterBahanRacikan(input);
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
        if (typeof window.setMobileHeaderCapsule === 'function') {
            const menuModal = document.getElementById('modal-menu-mobile');
            const isMenuOpen = menuModal && !menuModal.classList.contains('hidden');
            if (!isMenuOpen) {
                window.setMobileHeaderCapsule(null);
            }
        }
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
        if (typeof window.setMobileHeaderCapsule === 'function') {
            const menuModal = document.getElementById('modal-menu-mobile');
            const isMenuOpen = menuModal && !menuModal.classList.contains('hidden');
            if (!isMenuOpen) {
                window.setMobileHeaderCapsule(null);
            }
        }
    }
});

// ==========================================
// Mobile Header Dynamic Island Capsule Buttons
// ==========================================
window.setMobileHeaderCapsule = function(activeName) {
    const notifBtn = document.getElementById('btn-capsule-notif');
    const menuBtn = document.getElementById('btn-capsule-menu');
    const akunBtn = document.getElementById('btn-capsule-akun');
    const kandangBtn = document.getElementById('btn-nav-kandang-mobile');

    const updateBtn = (btn, isActive) => {
        if (!btn) return;
        const labelWrap = btn.querySelector('.capsule-label-wrap') || btn.querySelector('.capsule-label');
        if (isActive) {
            btn.classList.add('is-active');
            if (labelWrap) {
                labelWrap.style.maxWidth = 'none';
                const targetW = labelWrap.scrollWidth;
                labelWrap.style.maxWidth = '0px';
                void labelWrap.offsetWidth;
                labelWrap.style.maxWidth = targetW + 'px';
            }
        } else {
            btn.classList.remove('is-active');
            if (labelWrap) {
                const currentW = labelWrap.offsetWidth;
                labelWrap.style.maxWidth = currentW + 'px';
                void labelWrap.offsetWidth;
                labelWrap.style.maxWidth = '0px';
            }
        }
    };

    updateBtn(notifBtn, activeName === 'notif');
    updateBtn(menuBtn, activeName === 'menu');
    updateBtn(akunBtn, activeName === 'akun');

    // Fade out tab kandang when any capsule expands to avoid collision on small screens
    if (kandangBtn) {
        if (activeName !== null) {
            kandangBtn.classList.remove('opacity-100', 'pointer-events-auto', 'translate-x-0', 'scale-100');
            kandangBtn.classList.add('opacity-0', 'pointer-events-none', '-translate-x-2', 'scale-95');
        } else {
            kandangBtn.classList.remove('opacity-0', 'pointer-events-none', '-translate-x-2', 'scale-95');
            kandangBtn.classList.add('opacity-100', 'pointer-events-auto', 'translate-x-0', 'scale-100');
        }
    }
};

window.toggleNotifikasiMobile = function(event) {
    if (event) {
        event.stopPropagation();
        event.preventDefault();
    }
    const dropdown = document.getElementById('notifikasi-popover-mobile');
    if (!dropdown) return;
    const menu = dropdown.querySelector('.dropdown-menu');
    const isOpen = menu && !menu.classList.contains('hidden');

    // Close menu modal if open
    const menuModal = document.getElementById('modal-menu-mobile');
    if (menuModal && !menuModal.classList.contains('hidden')) {
        window.tutupModal('modal-menu-mobile');
    }

    // Close akun dropdown if open
    const akunDropdown = document.getElementById('akun-popover-mobile');
    if (akunDropdown) {
        const m = akunDropdown.querySelector('.dropdown-menu');
        if (m) m.classList.add('hidden');
    }

    if (isOpen) {
        if (menu) menu.classList.add('hidden');
        window.setMobileHeaderCapsule(null);
    } else {
        // Close any other custom dropdowns
        document.querySelectorAll('.custom-dropdown-container').forEach((c) => {
            const m = c.querySelector('.dropdown-menu');
            if (m) m.classList.add('hidden');
        });
        if (menu) menu.classList.remove('hidden');
        window.setMobileHeaderCapsule('notif');
    }
};

window.toggleMenuMobile = function(event) {
    if (event) {
        event.stopPropagation();
        event.preventDefault();
    }
    const menuModal = document.getElementById('modal-menu-mobile');
    const isOpen = menuModal && !menuModal.classList.contains('hidden');

    // Close notif dropdown if open
    const notifDropdown = document.getElementById('notifikasi-popover-mobile');
    if (notifDropdown) {
        const m = notifDropdown.querySelector('.dropdown-menu');
        if (m) m.classList.add('hidden');
    }

    // Close akun dropdown if open
    const akunDropdown = document.getElementById('akun-popover-mobile');
    if (akunDropdown) {
        const m = akunDropdown.querySelector('.dropdown-menu');
        if (m) m.classList.add('hidden');
    }

    if (isOpen) {
        window.tutupModal('modal-menu-mobile');
        window.setMobileHeaderCapsule(null);
    } else {
        window.bukaModal('modal-menu-mobile');
        window.setMobileHeaderCapsule('menu');
    }
};

window.toggleAkunMobile = function(event) {
    if (event) {
        event.stopPropagation();
        event.preventDefault();
    }
    const dropdown = document.getElementById('akun-popover-mobile');
    if (!dropdown) return;
    const menu = dropdown.querySelector('.dropdown-menu');
    const isOpen = menu && !menu.classList.contains('hidden');

    // Close menu modal if open
    const menuModal = document.getElementById('modal-menu-mobile');
    if (menuModal && !menuModal.classList.contains('hidden')) {
        window.tutupModal('modal-menu-mobile');
    }

    // Close notif dropdown if open
    const notifDropdown = document.getElementById('notifikasi-popover-mobile');
    if (notifDropdown) {
        const m = notifDropdown.querySelector('.dropdown-menu');
        if (m) m.classList.add('hidden');
    }

    if (isOpen) {
        if (menu) menu.classList.add('hidden');
        window.setMobileHeaderCapsule(null);
    } else {
        // Close any other custom dropdowns
        document.querySelectorAll('.custom-dropdown-container').forEach((c) => {
            const m = c.querySelector('.dropdown-menu');
            if (m) m.classList.add('hidden');
        });
        if (menu) menu.classList.remove('hidden');
        window.setMobileHeaderCapsule('akun');
    }
};

// ==========================================
// RansumKu Universal Modal & Dialog System
// ==========================================

window.bukaModal = function(id) {
    if (id === 'modal-menu-mobile' && typeof window.setMobileHeaderCapsule === 'function') {
        window.setMobileHeaderCapsule('menu');
    }
    if (id === 'modal-pilih-kandang-mobile' || id === 'modal-tambah-kandang' || id.startsWith('modal-edit-kandang-')) {
        if (typeof window.setMobileHeaderCapsule === 'function') {
            window.setMobileHeaderCapsule(null);
        }
        const notifDropdown = document.getElementById('notifikasi-popover-mobile');
        if (notifDropdown) {
            const m = notifDropdown.querySelector('.dropdown-menu');
            if (m) m.classList.add('hidden');
        }
        const akunDropdown = document.getElementById('akun-popover-mobile');
        if (akunDropdown) {
            const m = akunDropdown.querySelector('.dropdown-menu');
            if (m) m.classList.add('hidden');
        }
    }
    const el = document.getElementById(id);
    if (!el) return;
    el.classList.remove('hidden');
    requestAnimationFrame(() => {
        el.classList.remove('opacity-0', 'pointer-events-none');
        const card = el.querySelector('[data-modal-card]');
        if (card) {
            card.classList.remove('scale-95', 'opacity-0');
            card.classList.add('scale-100', 'opacity-100');
        }
    });
    document.body.classList.add('overflow-hidden');

    if (id === 'modal-catat-harian') {
        const centerDot = document.getElementById('nav-center-dot');
        if (centerDot) {
            centerDot.classList.remove('bg-transparent', 'scale-0');
            centerDot.classList.add('bg-brand', 'scale-100');
        }
    }
};

window.tutupModal = function(id) {
    if (id === 'modal-menu-mobile' && typeof window.setMobileHeaderCapsule === 'function') {
        window.setMobileHeaderCapsule(null);
    }
    const el = document.getElementById(id);
    if (!el) return;
    const card = el.querySelector('[data-modal-card]');
    if (card) {
        card.classList.add('scale-95', 'opacity-0');
        card.classList.remove('scale-100', 'opacity-100');
    }
    el.classList.add('opacity-0', 'pointer-events-none');
    setTimeout(() => {
        el.classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
    }, 200);

    if (id === 'modal-catat-harian') {
        const centerDot = document.getElementById('nav-center-dot');
        if (centerDot) {
            centerDot.classList.add('bg-transparent', 'scale-0');
            centerDot.classList.remove('bg-brand', 'scale-100');
        }
    }
};

// Global escape key to close active modal
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        const visibleModal = document.querySelector('[role="dialog"]:not(.hidden)');
        if (visibleModal) {
            window.tutupModal(visibleModal.id);
        }
    }
});

// Universal Confirm Callback Handler
let activeConfirmCallback = null;

window.bukaKonfirmasi = function({ judul, pesan, subpesan = '', tombolTeks = 'Ya, Hapus', tombolTipe = 'danger', onConfirm }) {
    const modal = document.getElementById('modal-konfirmasi-universal');
    if (!modal) {
        if (confirm(pesan || 'Lanjutkan aksi ini?')) {
            if (onConfirm) onConfirm();
        }
        return;
    }

    const elJudul = document.getElementById('konfirmasi-judul');
    const elPesan = document.getElementById('konfirmasi-pesan');
    const elSubpesan = document.getElementById('konfirmasi-subpesan');
    const elBtn = document.getElementById('konfirmasi-btn-submit');
    const elIcon = document.getElementById('konfirmasi-icon');

    if (elJudul) elJudul.textContent = judul || 'Konfirmasi Tindakan';
    if (elPesan) elPesan.textContent = pesan || 'Apakah Anda yakin ingin melanjutkan tindakan ini?';
    if (elSubpesan) {
        elSubpesan.textContent = subpesan;
        elSubpesan.classList.toggle('hidden', !subpesan);
    }
    if (elBtn) {
        elBtn.textContent = tombolTeks;
        if (tombolTipe === 'danger') {
            elBtn.className = 'btn-danger btn-sm flex-1 min-h-11 shadow-sm';
            if (elIcon) elIcon.className = 'w-14 h-14 mx-auto rounded-full bg-rose-100 text-danger flex items-center justify-center mb-3.5';
        } else {
            elBtn.className = 'btn-primary btn-sm flex-1 min-h-11 shadow-sm';
            if (elIcon) elIcon.className = 'w-14 h-14 mx-auto rounded-full bg-brand/10 text-brand flex items-center justify-center mb-3.5';
        }
    }

    activeConfirmCallback = onConfirm;
    window.bukaModal('modal-konfirmasi-universal');
};

window.eksekusiKonfirmasi = function() {
    if (typeof activeConfirmCallback === 'function') {
        activeConfirmCallback();
    }
    window.tutupModal('modal-konfirmasi-universal');
    activeConfirmCallback = null;
};

// Automatic Form Submit Interceptor for [data-confirm]
document.addEventListener('submit', function(e) {
    const form = e.target;
    if (!form || !form.hasAttribute('data-confirm')) return;

    if (form.dataset.confirmed === 'true') {
        return; // Allow submission
    }

    e.preventDefault();
    const pesan = form.getAttribute('data-confirm') || 'Apakah Anda yakin ingin melanjutkan tindakan ini?';
    const judul = form.getAttribute('data-confirm-title') || 'Konfirmasi Tindakan';
    const subpesan = form.getAttribute('data-confirm-sub') || 'Tindakan ini tidak dapat dibatalkan.';
    const tombolTeks = form.getAttribute('data-confirm-btn') || 'Ya, Lanjutkan';
    const tombolTipe = form.getAttribute('data-confirm-type') || 'danger';

    window.bukaKonfirmasi({
        judul,
        pesan,
        subpesan,
        tombolTeks,
        tombolTipe,
        onConfirm: () => {
            form.dataset.confirmed = 'true';
            form.submit();
        }
    });
});

// Horizontal drag & wheel scroll handler tanpa scrollbar visual
document.querySelectorAll('[data-horizontal-scroll]').forEach((el) => {
    el.addEventListener('wheel', (evt) => {
        if (evt.deltaY !== 0) {
            evt.preventDefault();
            el.scrollLeft += evt.deltaY;
        }
    }, { passive: false });

    let isDown = false;
    let startX = 0;
    let scrollLeft = 0;

    el.addEventListener('mousedown', (e) => {
        isDown = true;
        startX = e.pageX - el.offsetLeft;
        scrollLeft = el.scrollLeft;
    });
    el.addEventListener('mouseleave', () => { isDown = false; });
    el.addEventListener('mouseup', () => { isDown = false; });
    el.addEventListener('mousemove', (e) => {
        if (!isDown) return;
        e.preventDefault();
        const x = e.pageX - el.offsetLeft;
        const walk = (x - startX) * 1.5;
        el.scrollLeft = scrollLeft - walk;
    });
});

// Auto-dismiss floating toast notifications after 4 seconds
setTimeout(() => {
    const toasts = document.querySelectorAll('[data-toast], #toast-notification, #toast-error');
    toasts.forEach((toast) => {
        toast.style.transition = 'opacity 0.4s ease, transform 0.4s ease';
        toast.style.opacity = '0';
        toast.style.transform = 'translateY(-12px)';
        setTimeout(() => toast.remove(), 400);
    });
}, 4000);

// Universal Floating Toast Notification Dispatcher
window.showToast = function (pesan, tipe = 'success', judul = null) {
    let container = document.getElementById('toast-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toast-container';
        container.className = 'fixed top-20 right-4 lg:right-8 z-50 flex flex-col gap-2.5 max-w-sm w-full pointer-events-none';
        document.body.appendChild(container);
    }

    const toast = document.createElement('div');
    toast.setAttribute('data-toast', '');
    toast.className = 'pointer-events-auto bg-white/95 backdrop-blur-md rounded-2xl border shadow-2xl p-4 flex items-start gap-3 transform transition-all duration-300 translate-y-2 opacity-0';

    let icon = 'check_circle';
    let iconClass = 'bg-emerald-100 text-emerald-700';
    let borderClass = 'border-emerald-200/90';
    let titleText = judul || 'Berhasil';
    let titleClass = 'text-ink';

    if (tipe === 'error') {
        icon = 'error';
        iconClass = 'bg-rose-100 text-danger';
        borderClass = 'border-rose-200/90';
        titleText = judul || 'Terdapat Kendala';
        titleClass = 'text-danger';
    } else if (tipe === 'warning') {
        icon = 'warning';
        iconClass = 'bg-amber-100 text-amber-700';
        borderClass = 'border-amber-200/90';
        titleText = judul || 'Perhatian';
        titleClass = 'text-amber-800';
    } else if (tipe === 'info') {
        icon = 'info';
        iconClass = 'bg-blue-100 text-blue-700';
        borderClass = 'border-blue-200/90';
        titleText = judul || 'Informasi';
        titleClass = 'text-blue-800';
    }

    toast.classList.add(borderClass);
    toast.innerHTML = `
        <div class="w-9 h-9 rounded-full ${iconClass} flex items-center justify-center shrink-0">
            <span class="material-symbols-outlined icon-fill text-[20px]">${icon}</span>
        </div>
        <div class="flex-1 pr-1">
            <p class="text-xs font-bold ${titleClass}">${titleText}</p>
            <p class="text-xs text-muted mt-0.5 leading-relaxed">${pesan}</p>
        </div>
        <button type="button" class="text-muted hover:text-ink cursor-pointer p-1 rounded-lg hover:bg-surface transition" aria-label="Tutup">
            <span class="material-symbols-outlined text-[16px]">close</span>
        </button>
    `;

    toast.querySelector('button').addEventListener('click', () => {
        toast.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
        toast.style.opacity = '0';
        toast.style.transform = 'translateY(-12px)';
        setTimeout(() => toast.remove(), 300);
    });

    container.appendChild(toast);

    requestAnimationFrame(() => {
        toast.classList.remove('translate-y-2', 'opacity-0');
        toast.classList.add('translate-y-0', 'opacity-100');
    });

    setTimeout(() => {
        if (toast.parentElement) {
            toast.style.transition = 'opacity 0.4s ease, transform 0.4s ease';
            toast.style.opacity = '0';
            toast.style.transform = 'translateY(-12px)';
            setTimeout(() => toast.remove(), 400);
        }
    }, 4500);
};

// Polyfill window.alert agar selalu menampilkan custom Toast UI elegan daripada dialog bawaan browser
window.alert = function (msg) {
    if (typeof msg === 'string') {
        const lower = msg.toLowerCase();
        const tipe = lower.includes('gagal') || lower.includes('error') ? 'error' : (lower.includes('perhatian') || lower.includes('silakan') || lower.includes('wajib') ? 'warning' : 'success');
        window.showToast(msg, tipe);
    } else {
        window.showToast(String(msg), 'info');
    }
};

// ==========================================
// RansumKu Notification & Dynamic Tab Bar System
// ==========================================

let baseDocumentTitle = document.title.replace(/^\(\d+\)\s*/, '');

window.updateNotificationBadge = function (count) {
    const unread = Math.max(0, parseInt(count, 10) || 0);

    // 1. Update judul tab bar browser (seperti WhatsApp Web: "(1) Dashboard · RansumKu")
    if (unread > 0) {
        document.title = `(${unread}) ${baseDocumentTitle}`;
    } else {
        document.title = baseDocumentTitle;
    }

    // 2. Update dot merah di navbar lonceng (desktop & mobile)
    document.querySelectorAll('[data-notif-dot]').forEach((dot) => {
        dot.classList.toggle('hidden', unread === 0);
        dot.classList.toggle('inline-block', unread > 0);
    });

    // 3. Update counter badge di sidebar & bottom nav
    document.querySelectorAll('[data-notif-count]').forEach((badge) => {
        badge.textContent = unread > 9 ? '9+' : unread;
        badge.classList.toggle('hidden', unread === 0);
        badge.classList.toggle('flex', unread > 0);
    });

    // 4. Update badge status di modal notifikasi
    const modalBadge = document.querySelector('[data-notif-modal-badge]');
    if (modalBadge) {
        modalBadge.textContent = unread > 0 ? `${unread} Baru` : 'Semua Terbaca';
        modalBadge.className = unread > 0
            ? 'text-[10px] font-bold px-2 py-0.5 rounded-full bg-rose-100 text-rose-700 border border-rose-200'
            : 'text-[10px] font-bold px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-700 border border-emerald-200';
    }

    // Update counter di dalam tab filter
    document.querySelectorAll('[data-tab-unread-count]').forEach((el) => {
        el.textContent = `(${unread})`;
    });

    // 5. Update tombol "Tandai Semua Dibaca"
    const markAllBtn = document.getElementById('btn-mark-all-read');
    if (markAllBtn) {
        markAllBtn.classList.toggle('hidden', unread === 0);
        markAllBtn.classList.toggle('flex', unread > 0);
    }
};

window.filterNotifTab = function(tab, containerId, btn) {
    const container = document.getElementById(containerId);
    if (!container) return;

    container.querySelectorAll('[data-notif-tab-btn]').forEach((b) => {
        const isActive = b === btn;
        b.className = isActive
            ? 'px-2.5 py-1 rounded-full font-bold cursor-pointer transition bg-brand text-white shadow-2xs'
            : 'px-2.5 py-1 rounded-full font-semibold cursor-pointer transition bg-surface text-muted hover:text-ink hover:bg-surface-2 border border-line/60';
    });

    const items = container.querySelectorAll('[data-notif-item]');
    let count = 0;
    items.forEach((item) => {
        const isUnread = item.dataset.isUnread === 'true';
        const level = item.dataset.level || '';
        let show = true;
        if (tab === 'unread') {
            show = isUnread;
        } else if (tab === 'waspada') {
            show = level === 'waspada';
        }
        item.style.display = show ? '' : 'none';
        if (show) count++;
    });

    const emptyFilter = container.querySelector('[data-notif-empty-filter]');
    if (emptyFilter) {
        emptyFilter.classList.toggle('hidden', count > 0 || items.length === 0);
    }
};

window.tandaiSatuNotifikasiDibaca = function (alertId, element) {
    if (!element || element.dataset.isUnread !== 'true') return;

    // Hapus dot merah pada item ini dan ubah styling ke read state
    element.dataset.isUnread = 'false';
    const dot = element.querySelector('[data-notif-item-dot]');
    if (dot) {
        dot.classList.add('hidden');
        dot.classList.remove('inline-block');
    }

    element.classList.remove('bg-surface/80', 'border-brand/25', 'shadow-2xs');
    element.classList.add('bg-white', 'border-line/60', 'opacity-80');

    // Hitung sisa unread di dalam modal
    const remainingUnread = document.querySelectorAll('[data-notif-item][data-is-unread="true"]').length;
    window.updateNotificationBadge(remainingUnread);

    // Kirim request ke server untuk update database
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    fetch('/asisten/baca', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
        },
        body: JSON.stringify({ alert_id: alertId }),
    }).then(res => res.json()).then(data => {
        if (data && typeof data.unread_count === 'number') {
            window.updateNotificationBadge(data.unread_count);
        }
    }).catch(err => {
        console.warn('Gagal update status baca notifikasi:', err);
    });
};

window.tandaiSemuaNotifikasiDibaca = function () {
    // Hapus semua dot merah pada item notifikasi
    document.querySelectorAll('[data-notif-item]').forEach((element) => {
        element.dataset.isUnread = 'false';
        const dot = element.querySelector('[data-notif-item-dot]');
        if (dot) {
            dot.classList.add('hidden');
            dot.classList.remove('inline-block');
        }
        element.classList.remove('bg-surface/80', 'border-brand/25', 'shadow-2xs');
        element.classList.add('bg-white', 'border-line/60', 'opacity-80');
    });

    window.updateNotificationBadge(0);

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    fetch('/asisten/baca', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
        },
        body: JSON.stringify({}),
    }).catch(err => {
        console.warn('Gagal update status baca semua notifikasi:', err);
    });
};

// ==========================================
// Real-time Live Sensor & Microclimate Poller
// ==========================================
function initLiveSensorPoller() {
    const widgets = document.querySelectorAll('[data-weather-widget]');
    if (!widgets.length) return;

    const firstWidget = widgets[0];
    const liveUrl = firstWidget.getAttribute('data-live-url');
    if (!liveUrl) return;

    let currentSuhu = firstWidget.getAttribute('data-current-suhu');
    let currentRh = firstWidget.getAttribute('data-current-rh');
    let lastSensorOnline = null;

    const iconSVGs = {
        sunny: '<svg class="w-14 h-14 md:w-16 md:h-16 animate-[spin_20s_linear_infinite]" viewBox="0 0 64 64" fill="none"><circle cx="32" cy="32" r="14" fill="#F59E0B"/><g stroke="#F59E0B" stroke-width="3" stroke-linecap="round"><line x1="32" y1="4" x2="32" y2="10"/><line x1="32" y1="54" x2="32" y2="60"/><line x1="4" y1="32" x2="10" y2="32"/><line x1="54" y1="32" x2="60" y2="32"/></g></svg>',
        partly_cloudy: '<svg class="w-14 h-14 md:w-16 md:h-16" viewBox="0 0 64 64" fill="none"><circle cx="38" cy="22" r="11" fill="#EA580C"/><path d="M20 48H44C47.866 48 51 44.866 51 41C51 37.134 47.866 34 44 34C43.7 34 43.4 34.02 43.1 34.06C42.06 28.32 37.07 24 31 24C24.37 24 19 29.37 19 36C19 36.35 19.01 36.69 19.05 37.03C15.65 37.56 13 40.48 13 44C13 46.21 14.79 48 17 48H20Z" fill="#CBD5E1" stroke="#94A3B8" stroke-width="1.5"/></svg>',
        rainy: '<svg class="w-14 h-14 md:w-16 md:h-16" viewBox="0 0 64 64" fill="none"><path d="M19 40H45C48.3 40 51 37.3 51 34C51 30.7 48.3 28 45 28C44.7 28 44.4 28.02 44.1 28.06C43.1 23.32 38.9 20 34 20C28.5 20 24 24.5 24 30C24 30.3 24.01 30.6 24.05 30.9C21.1 31.4 19 33.9 19 36.8C19 38.6 20.4 40 22.2 40H19Z" fill="#CBD5E1" stroke="#94A3B8" stroke-width="1.5"/><line x1="24" y1="44" x2="21" y2="52" stroke="#3B82F6" stroke-width="2.5" stroke-linecap="round"/><line x1="33" y1="44" x2="30" y2="52" stroke="#3B82F6" stroke-width="2.5" stroke-linecap="round"/><line x1="42" y1="44" x2="39" y2="52" stroke="#3B82F6" stroke-width="2.5" stroke-linecap="round"/></svg>',
        thunderstorm: '<svg class="w-14 h-14 md:w-16 md:h-16" viewBox="0 0 64 64" fill="none"><path d="M19 36H45C48.3 36 51 33.3 51 30C51 26.7 48.3 24 45 24C44.7 24 44.4 24.02 44.1 24.06C43.1 19.32 38.9 16 34 16C28.5 16 24 20.5 24 26C24 26.3 24.01 26.6 24.05 26.9C21.1 27.4 19 29.9 19 32.8C19 34.6 20.4 36 22.2 36H19Z" fill="#475569"/><polygon points="34,34 26,46 33,46 29,58 41,44 34,44" fill="#FACC15" stroke="#EAB308" stroke-width="1"/></svg>',
    };

    async function pollSensor() {
        try {
            const res = await fetch(liveUrl, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });
            if (!res.ok) return;
            const data = await res.json();
            if (!data || !data.ok) return;

            const isSensorOnline = Boolean(data.sensor_online || (data.sumber === 'sensor' && data.sensor_aktif));

            // Deteksi otomatis saat sensor ESP32 dipasang atau dilepas
            if (lastSensorOnline !== null) {
                if (!lastSensorOnline && isSensorOnline) {
                    if (typeof window.showToast === 'function') {
                        window.showToast(
                            `Sensor IoT ESP32 terdeteksi terpasang! Suhu fisik ${data.suhu}°C (${data.kelembaban}% RH) kini terbaca real-time.`,
                            'success',
                            'Sensor IoT Terhubung'
                        );
                    }
                } else if (lastSensorOnline && !isSensorOnline) {
                    if (typeof window.showToast === 'function') {
                        window.showToast(
                            `Sensor IoT ESP32 dilepas atau terputus. Sistem beralih otomatis ke prakiraan cuaca mikro ${data.kota || 'BMKG'}.`,
                            'warning',
                            'Sensor IoT Terlepas'
                        );
                    }
                }
            }
            lastSensorOnline = isSensorOnline;

            const newSuhuStr = String(data.suhu);
            const newRhStr = String(data.kelembaban);

            widgets.forEach((widget) => {
                const suhuEl = widget.querySelector('[data-live-suhu]');
                const rhEl = widget.querySelector('[data-live-rh]');
                const apparentEl = widget.querySelector('[data-live-apparent]');
                const anginEl = widget.querySelector('[data-live-angin]');
                const cuacaEl = widget.querySelector('[data-live-cuaca]');
                const updatedEl = widget.querySelector('[data-live-updated]');
                const sumberBadge = widget.querySelector('[data-live-sumber-badge]');
                const pulseDot = widget.querySelector('[data-live-pulse-dot]');
                const thiBadge = widget.querySelector('[data-live-thi-badge]');
                const thiMarker = widget.querySelector('[data-live-thi-marker]');
                const thiPinText = widget.querySelector('[data-live-thi-pin-text]');
                const dampakEl = widget.querySelector('[data-live-dampak]');
                const liveBadge = widget.querySelector('[data-live-indicator-badge]');
                const iconContainer = widget.querySelector('[data-live-icon-container]');

                // Jika suhu berubah
                if (currentSuhu !== newSuhuStr) {
                    if (suhuEl) {
                        suhuEl.textContent = data.suhu;
                        suhuEl.classList.add('text-brand', 'scale-110');
                        setTimeout(() => {
                            suhuEl.classList.remove('text-brand', 'scale-110');
                        }, 800);
                    }
                    if (liveBadge) {
                        liveBadge.classList.remove('hidden');
                        setTimeout(() => liveBadge.classList.add('hidden'), 4000);
                    }
                    widget.setAttribute('data-current-suhu', newSuhuStr);
                }

                // Jika kelembaban berubah
                if (currentRh !== newRhStr) {
                    if (rhEl) rhEl.textContent = data.kelembaban + '%';
                    widget.setAttribute('data-current-rh', newRhStr);
                }

                if (apparentEl && data.apparent_temp !== undefined) {
                    apparentEl.textContent = data.apparent_temp + '°C';
                }
                if (anginEl && data.angin_kmh !== undefined) {
                    anginEl.textContent = data.angin_kmh;
                }
                if (cuacaEl && data.cuaca_teks) {
                    cuacaEl.textContent = data.cuaca_teks;
                }
                if (updatedEl && data.updated_at) {
                    updatedEl.textContent = 'Pembaruan: ' + data.updated_at;
                }

                // Update Ikon Cuaca
                if (iconContainer && data.weather_type && iconSVGs[data.weather_type]) {
                    if (iconContainer.getAttribute('data-current-type') !== data.weather_type) {
                        iconContainer.innerHTML = iconSVGs[data.weather_type];
                        iconContainer.setAttribute('data-current-type', data.weather_type);
                    }
                }

                // Update THI Badge & Kategori
                if (thiBadge && data.thi !== undefined) {
                    thiBadge.textContent = 'THI ' + data.thi + (data.thi_kategori_singkat ? ' · ' + data.thi_kategori_singkat : '');
                    if (data.badge_class) {
                        thiBadge.className = 'px-2.5 py-0.5 rounded-full text-xs font-bold shadow-2xs transition-all duration-300 ' + data.badge_class;
                    }
                }

                // Update THI Pin Marker di atas bar gauge
                if (thiMarker && data.thi_percent !== undefined) {
                    thiMarker.style.left = data.thi_percent + '%';
                }
                if (thiPinText && data.thi !== undefined) {
                    thiPinText.textContent = 'THI ' + data.thi;
                }

                // Update Dampak Ayam
                if (dampakEl && data.dampak_ayam) {
                    dampakEl.textContent = data.dampak_ayam;
                }

                // Update Indikator Status Sensor
                const isSensor = isSensorOnline;
                if (sumberBadge) {
                    sumberBadge.textContent = isSensor ? 'Sensor IoT Live' : 'Prakiraan BMKG';
                    sumberBadge.className = isSensor
                        ? 'text-tiny px-2.5 py-0.5 rounded-full font-bold transition-all bg-emerald-50 text-emerald-800 border border-emerald-200'
                        : 'text-tiny px-2.5 py-0.5 rounded-full font-bold transition-all bg-surface text-muted border border-line';
                }
                if (pulseDot) {
                    pulseDot.className = isSensor
                        ? 'w-2 h-2 rounded-full transition-colors bg-emerald-500 animate-pulse'
                        : 'w-2 h-2 rounded-full transition-colors bg-blue-400';
                    pulseDot.title = isSensor ? 'Sensor IoT Fisik Terhubung' : 'Prakiraan Cuaca Terbuka';
                }
            });

            currentSuhu = newSuhuStr;
            currentRh = newRhStr;
        } catch (e) {
            // Ignore temporary network errors
        }
    }

    window.__refreshLiveSensor = pollSensor;

    // Polling setiap 3 detik secara background saat tab aktif
    setInterval(() => {
        if (!document.hidden) {
            pollSensor();
        }
    }, 3000);

    // Langsung cek ketika peternak kembali ke tab browser
    document.addEventListener('visibilitychange', () => {
        if (!document.hidden) {
            pollSensor();
        }
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initLiveSensorPoller);
} else {
    initLiveSensorPoller();
}

