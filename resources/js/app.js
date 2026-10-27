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
                slider.style.transition = 'transform 320ms cubic-bezier(0.25, 1, 0.5, 1), width 320ms cubic-bezier(0.25, 1, 0.5, 1), height 320ms cubic-bezier(0.25, 1, 0.5, 1), opacity 150ms ease';
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
                    slider.style.transition = 'transform 320ms cubic-bezier(0.25, 1, 0.5, 1), width 320ms cubic-bezier(0.25, 1, 0.5, 1), height 320ms cubic-bezier(0.25, 1, 0.5, 1), opacity 150ms ease';
                });
            }
        };

        const activeBtn = buttons.find((b) => b.classList.contains('active-tab')) ||
            buttons.find((b) => b.classList.contains('bg-brand')) ||
            buttons.find((b) => !b.classList.contains('text-muted') && b.classList.contains('text-white')) ||
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
                b.classList.remove('bg-brand');

                if (isActive) {
                    b.classList.remove('text-muted', 'hover:text-ink');
                    b.classList.add('text-white', 'font-bold');
                } else {
                    b.classList.remove('text-white');
                    b.classList.add('text-muted', 'hover:text-ink', 'font-bold');
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

            window.dispatchEvent(new Event('resize'));
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

        window.addEventListener('resize', () => {
            const currentActive = buttons.find((b) => b.classList.contains('active-tab')) || buttons[0];
            updateSlider(currentActive, false);
        });

        if (document.fonts?.ready) {
            document.fonts.ready.then(() => {
                const currentActive = buttons.find((b) => b.classList.contains('active-tab')) || buttons[0];
                updateSlider(currentActive, false);
            });
        }
    });
}

initSlidingTabs();

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
