import { Chart, registerables } from 'chart.js';

Chart.register(...registerables);
Chart.defaults.font.family = "'Plus Jakarta Sans', sans-serif";
Chart.defaults.color = '#6E6B65';

// Grafik dibuat dari <canvas data-chart='{...}'> agar Blade cukup mengirim JSON.
document.querySelectorAll('canvas[data-chart]').forEach((el) => {
    const cfg = JSON.parse(el.dataset.chart);
    new Chart(el, {
        type: cfg.type,
        data: { labels: cfg.labels, datasets: cfg.datasets },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: cfg.legend ?? false, position: 'bottom' } },
            scales: { x: { grid: { display: false } }, y: { beginAtZero: cfg.zero ?? true, grid: { color: '#f0eded' } } },
        },
    });
});

// Tab sederhana: <button data-tab="id"> menampilkan <section data-panel="id">
document.querySelectorAll('[data-tab]').forEach((btn) => {
    btn.addEventListener('click', () => {
        const group = btn.closest('[data-tabs]');
        group.querySelectorAll('[data-tab]').forEach((b) => b.classList.toggle('active-tab', b === btn));
        group.querySelectorAll('[data-panel]').forEach((p) => p.classList.toggle('hidden', p.dataset.panel !== btn.dataset.tab));
    });
});

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
