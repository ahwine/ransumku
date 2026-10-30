{{-- Modal Konfirmasi Universal RansumKu --}}
<div id="modal-konfirmasi-universal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-xs transition-opacity duration-200 hidden opacity-0 pointer-events-none" role="dialog" aria-modal="true">
    {{-- Backdrop click listener --}}
    <div class="absolute inset-0" onclick="tutupModal('modal-konfirmasi-universal')"></div>

    {{-- Card Container --}}
    <div data-modal-card class="relative z-10 w-full max-w-sm md:max-w-md bg-white rounded-3xl p-6 shadow-2xl border border-line transform transition-all duration-200 scale-95 opacity-0 text-center">
        {{-- Icon --}}
        <div id="konfirmasi-icon" class="w-14 h-14 mx-auto rounded-full bg-rose-100 text-danger flex items-center justify-center mb-3.5">
            <span class="material-symbols-outlined text-[28px]">warning</span>
        </div>

        <h3 id="konfirmasi-judul" class="text-h-sm text-ink font-black mb-1.5">
            Konfirmasi Tindakan
        </h3>
        <p id="konfirmasi-pesan" class="text-xs md:text-sm text-muted leading-relaxed mb-1">
            Apakah Anda yakin ingin melanjutkan tindakan ini?
        </p>
        <p id="konfirmasi-subpesan" class="text-tiny text-muted/70 mb-5 hidden"></p>

        {{-- Action Buttons --}}
        <div class="flex items-center gap-3">
            <button type="button" onclick="tutupModal('modal-konfirmasi-universal')" class="btn-outline btn-sm flex-1 min-h-11">
                Batal
            </button>
            <button type="button" id="konfirmasi-btn-submit" onclick="eksekusiKonfirmasi()" class="btn-danger btn-sm flex-1 min-h-11 shadow-sm">
                Ya, Lanjutkan
            </button>
        </div>
    </div>
</div>

