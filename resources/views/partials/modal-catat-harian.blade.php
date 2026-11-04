{{-- Modal Popup Catat Data Harian Cepat --}}
<div id="modal-catat-harian" class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-4 bg-black/50 backdrop-blur-xs transition-opacity duration-200 hidden opacity-0 pointer-events-none" role="dialog" aria-modal="true">
    <div class="absolute inset-0" onclick="tutupModal('modal-catat-harian')"></div>

    <div data-modal-card class="relative z-10 w-full max-w-lg bg-white rounded-xl sm:rounded-2xl p-4 sm:p-6 shadow-2xl border border-line transform transition-all duration-200 scale-95 opacity-0 space-y-4 max-h-[92vh] overflow-y-auto">
        {{-- Header Modal --}}
        <div class="flex items-center justify-between pb-3 border-b border-line/60">
            <div class="flex items-center gap-2.5 min-w-0">
                <div class="w-10 h-10 rounded-xl bg-[#e2f0e8] text-[#116a3a] flex items-center justify-center shrink-0">
                    <span class="material-symbols-outlined text-[22px]">edit_note</span>
                </div>
                <div class="min-w-0">
                    <h3 class="text-base font-extrabold text-[#0f3d26] leading-tight truncate">Catat Data Hari Ini</h3>
                    <p class="text-xs text-muted font-medium mt-0.5 truncate">Input cepat 1-2 menit untuk buku kandang</p>
                </div>
            </div>
            <button type="button" onclick="tutupModal('modal-catat-harian')" class="w-8 h-8 rounded-full bg-surface hover:bg-surface-2 text-muted hover:text-ink flex items-center justify-center transition shrink-0 cursor-pointer" aria-label="Tutup">
                <span class="material-symbols-outlined text-[18px]">close</span>
            </button>
        </div>

        {{-- Form Catat Harian --}}
        <form method="POST" action="{{ route('catatan.store') }}" class="space-y-3.5">
            @csrf
            <div>
                <label class="block text-xs font-bold text-ink mb-1.5" for="modal_tanggal">Tanggal Pencatatan</label>
                <div class="relative cursor-pointer">
                    <input id="modal_tanggal" name="tanggal" type="date" value="{{ today()->toDateString() }}" max="{{ today()->toDateString() }}" class="w-full h-11 rounded-xl bg-white px-3.5 pr-10 text-sm font-semibold text-ink border border-line focus:border-brand focus:ring-1 focus:ring-brand outline-none cursor-pointer" required>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-ink mb-1.5" for="modal_pakan_kg">Pakan Terpakai</label>
                    <div class="relative">
                        <input id="modal_pakan_kg" name="pakan_kg" type="number" step="0.1" min="0" class="w-full h-11 rounded-xl bg-white px-3.5 pr-10 text-sm font-bold text-ink border border-line focus:border-brand focus:ring-1 focus:ring-brand outline-none" placeholder="0" required>
                        <span class="absolute right-3.5 top-1/2 -translate-y-1/2 text-xs font-semibold text-muted pointer-events-none select-none">kg</span>
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-bold text-ink mb-1.5" for="modal_telur_butir">Jumlah Telur</label>
                    <div class="relative">
                        <input id="modal_telur_butir" name="telur_butir" type="number" min="0" class="w-full h-11 rounded-xl bg-white px-3.5 pr-12 text-sm font-bold text-ink border border-line focus:border-brand focus:ring-1 focus:ring-brand outline-none" placeholder="0" required>
                        <span class="absolute right-3.5 top-1/2 -translate-y-1/2 text-xs font-semibold text-muted pointer-events-none select-none">butir</span>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <div class="flex items-center justify-between gap-1 mb-1.5">
                        <label class="block text-xs font-bold text-ink whitespace-nowrap shrink-0" for="modal_telur_kg">Bobot Telur</label>
                        <button type="button" id="modal-btn-calc-egg" class="text-[11px] font-bold text-[#116a3a] hover:underline flex items-center gap-0.5 cursor-pointer whitespace-nowrap shrink-0" title="Hitung estimasi: butir × 62 gram">
                            <span class="material-symbols-outlined text-[13px]">auto_fix_high</span>
                            <span>Otomatis</span>
                        </button>
                    </div>
                    <div class="relative">
                        <input id="modal_telur_kg" name="telur_kg" type="number" step="0.1" min="0" class="w-full h-11 rounded-xl bg-white px-3.5 pr-10 text-sm font-semibold text-ink border border-line focus:border-brand focus:ring-1 focus:ring-brand outline-none placeholder:text-muted/50" placeholder="~62g/btr">
                        <span class="absolute right-3.5 top-1/2 -translate-y-1/2 text-xs font-semibold text-muted pointer-events-none select-none">kg</span>
                    </div>
                    <span class="text-[10px] text-muted block mt-1 truncate">Opsional (otomatis ~62g)</span>
                </div>
                <div>
                    <div class="flex items-center justify-between gap-1 mb-1.5">
                        <label class="block text-xs font-bold text-ink whitespace-nowrap shrink-0" for="modal_ayam_mati">Ayam Mati</label>
                        <span class="text-[11px] text-muted font-normal whitespace-nowrap shrink-0">Harian</span>
                    </div>
                    <div class="relative">
                        <input id="modal_ayam_mati" name="ayam_mati" type="number" min="0" class="w-full h-11 rounded-xl bg-white px-3.5 pr-12 text-sm font-bold text-ink border border-line focus:border-brand focus:ring-1 focus:ring-brand outline-none" value="0">
                        <span class="absolute right-3.5 top-1/2 -translate-y-1/2 text-xs font-semibold text-muted pointer-events-none select-none">ekor</span>
                    </div>
                    <span class="text-[10px] text-muted block mt-1 truncate">Mortalitas harian</span>
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-ink mb-1.5" for="modal_catatan">
                    Catatan Suhu / Gejala <span class="font-normal text-muted">(Opsional)</span>
                </label>
                <textarea id="modal_catatan" name="catatan" rows="2" class="w-full rounded-xl bg-white p-3 text-xs font-medium text-ink border border-line focus:border-brand focus:ring-1 focus:ring-brand outline-none placeholder:text-muted/50" placeholder="Contoh: Suhu siang panas 33°C, ayam minum banyak..."></textarea>
            </div>

            {{-- Mini Live Indicator Strip --}}
            <div id="modal-live-strip" class="p-3 rounded-xl bg-[#f4f8f5] border border-line/60 grid grid-cols-3 divide-x divide-line/60 text-center">
                <div class="px-1">
                    <span class="text-[10px] text-muted font-bold uppercase tracking-wider block">HEN-DAY (HDP)</span>
                    <strong id="modal-live-hdp" class="text-sm sm:text-base font-black text-ink block mt-0.5">0.0%</strong>
                </div>
                <div class="px-1">
                    <span class="text-[10px] text-muted font-bold uppercase tracking-wider block">PAKAN / EKOR</span>
                    <strong id="modal-live-pakan-ekor" class="text-sm sm:text-base font-black text-ink block mt-0.5">0 gr</strong>
                </div>
                <div class="px-1">
                    <span class="text-[10px] text-muted font-bold uppercase tracking-wider block">FCR INSTAN</span>
                    <strong id="modal-live-fcr" class="text-sm sm:text-base font-black text-ink block mt-0.5">0.00</strong>
                </div>
            </div>

            {{-- Action Buttons --}}
            <div class="pt-2">
                <div class="grid grid-cols-2 gap-2.5">
                    <button type="button" onclick="tutupModal('modal-catat-harian')" class="w-full py-2.5 px-4 rounded-xl text-xs font-bold text-ink hover:text-ink/80 bg-surface hover:bg-surface-2 border border-line transition active:scale-98 cursor-pointer flex items-center justify-center">
                        Batal
                    </button>
                    <button type="submit" class="w-full py-2.5 px-4 rounded-xl text-xs font-bold text-white bg-brand hover:bg-brand-dark shadow-xs transition active:scale-98 cursor-pointer flex items-center justify-center gap-1.5">
                        <span class="material-symbols-outlined text-[16px]">save</span>
                        <span>Simpan Catatan</span>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const populasi = {{ (int) ($farm->jumlah_ayam ?? 200) }};
    const inputPakan = document.getElementById('modal_pakan_kg');
    const inputTelur = document.getElementById('modal_telur_butir');
    const inputKg = document.getElementById('modal_telur_kg');
    const btnCalcEgg = document.getElementById('modal-btn-calc-egg');
    const liveHdp = document.getElementById('modal-live-hdp');
    const livePakan = document.getElementById('modal-live-pakan-ekor');
    const liveFcr = document.getElementById('modal-live-fcr');

    function updateModalPreview() {
        if (!inputPakan || !inputTelur || !liveHdp) return;
        const pakan = parseFloat(inputPakan.value) || 0;
        const telur = parseInt(inputTelur.value) || 0;
        const kg = parseFloat(inputKg && inputKg.value ? inputKg.value : 0) || (telur * 0.062);

        // HDP
        const hdp = populasi > 0 && telur > 0 ? ((telur / populasi) * 100).toFixed(1) : '0.0';
        liveHdp.textContent = hdp + '%';
        liveHdp.className = parseFloat(hdp) >= 80 ? 'text-sm sm:text-base font-black text-emerald-600 block mt-0.5' : 'text-sm sm:text-base font-black text-ink block mt-0.5';

        // Pakan per ekor
        const grEkor = populasi > 0 && pakan > 0 ? Math.round((pakan * 1000) / populasi) : 0;
        livePakan.textContent = grEkor + ' gr';

        // FCR
        const fcr = kg > 0 && pakan > 0 ? (pakan / kg).toFixed(2) : '0.00';
        liveFcr.textContent = fcr;
        liveFcr.className = (parseFloat(fcr) > 0 && parseFloat(fcr) <= 2.2) ? 'text-sm sm:text-base font-black text-emerald-600 block mt-0.5' : 'text-sm sm:text-base font-black text-ink block mt-0.5';
    }

    if (btnCalcEgg && inputTelur && inputKg) {
        btnCalcEgg.addEventListener('click', function () {
            const butir = parseInt(inputTelur.value) || 0;
            if (butir > 0) {
                inputKg.value = (butir * 0.062).toFixed(1);
                updateModalPreview();
            } else {
                if (typeof window.showToast === 'function') {
                    window.showToast('Isi butir telur terlebih dahulu', 'warning', 'Perhatian');
                }
                inputTelur.focus();
            }
        });
    }

    [inputPakan, inputTelur, inputKg].forEach(el => {
        if (el) el.addEventListener('input', updateModalPreview);
    });
});
</script>
