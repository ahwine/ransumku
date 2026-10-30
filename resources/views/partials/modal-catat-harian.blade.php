{{-- Modal Popup Catat Data Harian Cepat --}}
<div id="modal-catat-harian" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-xs transition-opacity duration-200 hidden opacity-0 pointer-events-none" role="dialog" aria-modal="true">
    <div class="absolute inset-0" onclick="tutupModal('modal-catat-harian')"></div>

    <div data-modal-card class="relative z-10 w-full max-w-lg bg-white rounded-3xl p-6 shadow-2xl border border-line transform transition-all duration-200 scale-95 opacity-0 space-y-4 max-h-[90vh] overflow-y-auto">
        {{-- Header Modal --}}
        <div class="flex items-center justify-between pb-3 border-b border-line">
            <div class="flex items-center gap-2.5">
                <div class="w-10 h-10 rounded-full bg-brand/10 text-brand flex items-center justify-center">
                    <span class="material-symbols-outlined text-[22px]">edit_note</span>
                </div>
                <div>
                    <h3 class="text-h-sm text-ink font-bold leading-tight">Catat Data Hari Ini</h3>
                    <p class="text-tiny text-muted">Input cepat 1-2 menit untuk buku kandang</p>
                </div>
            </div>
            <button type="button" onclick="tutupModal('modal-catat-harian')" class="w-8 h-8 rounded-full bg-surface hover:bg-surface-2 text-muted hover:text-ink grid place-items-center transition" aria-label="Tutup">
                <span class="material-symbols-outlined text-[18px]">close</span>
            </button>
        </div>

        {{-- Form Catat Harian --}}
        <form method="POST" action="{{ route('catatan.store') }}" class="space-y-3.5">
            @csrf
            <div>
                <label class="lbl" for="modal_tanggal">Tanggal Catatan</label>
                <input id="modal_tanggal" name="tanggal" type="date" value="{{ today()->toDateString() }}" max="{{ today()->toDateString() }}" class="field !min-h-11 text-sm font-semibold" required>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="lbl" for="modal_pakan_kg">Pakan Terpakai (kg)</label>
                    <input id="modal_pakan_kg" name="pakan_kg" type="number" step="0.1" min="0" class="field !min-h-11 text-base font-bold text-ink" placeholder="Misal: 22.0" required>
                </div>
                <div>
                    <label class="lbl" for="modal_telur_butir">Jumlah Telur (butir)</label>
                    <input id="modal_telur_butir" name="telur_butir" type="number" min="0" class="field !min-h-11 text-base font-bold text-ink" placeholder="Misal: 170" required>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="lbl" for="modal_telur_kg">Bobot Telur Total (kg, opsional)</label>
                    <input id="modal_telur_kg" name="telur_kg" type="number" step="0.1" min="0" class="field !min-h-11 text-sm" placeholder="Contoh: 10.5">
                    <span class="text-[10px] text-muted block mt-0.5">Otomatis ~62g/butir jika kosong</span>
                </div>
                <div>
                    <label class="lbl" for="modal_ayam_mati">Ayam Mati (ekor)</label>
                    <input id="modal_ayam_mati" name="ayam_mati" type="number" min="0" class="field !min-h-11 text-sm font-bold" value="0">
                </div>
            </div>

            <div>
                <label class="lbl" for="modal_catatan">Catatan Suhu / Gejala (Opsional)</label>
                <textarea id="modal_catatan" name="catatan" rows="2" class="field !min-h-[56px] py-2 text-xs" placeholder="Contoh: Suhu siang panas 33°C, ayam minum banyak"></textarea>
            </div>

            <div class="pt-2 flex items-center justify-between gap-3">
                <a href="{{ route('catatan.index') }}" class="text-xs font-semibold text-brand hover:underline flex items-center gap-1">
                    <span>Lihat Riwayat Lengkap</span>
                    <span class="material-symbols-outlined text-[14px]">arrow_forward</span>
                </a>
                <div class="flex items-center gap-2">
                    <button type="button" onclick="tutupModal('modal-catat-harian')" class="btn-outline btn-sm !min-h-10 !px-4 text-xs">
                        Batal
                    </button>
                    <button type="submit" class="btn-primary btn-sm !min-h-10 !px-5 text-xs font-bold shadow-sm">
                        <span class="material-symbols-outlined text-[16px]">save</span>
                        <span>Simpan Catatan</span>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

