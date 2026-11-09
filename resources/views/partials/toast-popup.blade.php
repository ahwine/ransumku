{{-- Floating Toast Notification Popup Universal (Putih Bersih & Profesional) --}}
<div id="toast-container" class="fixed top-3 sm:top-5 inset-x-3 sm:inset-x-auto sm:right-6 lg:right-8 z-[99999] flex flex-col gap-2.5 max-w-none sm:max-w-sm w-auto sm:w-full pointer-events-none transition-all duration-300">
    @if (session('ok'))
        <div id="toast-notification" data-toast class="pointer-events-auto bg-white rounded-xl border border-line shadow-lg shadow-ink/8 p-3.5 sm:p-4 flex items-start gap-3 transform transition-all duration-300 translate-y-0 opacity-100" role="status">
            <div class="w-8 h-8 rounded-lg bg-emerald-50 text-brand flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined icon-fill text-[19px]">check_circle</span>
            </div>
            <div class="flex-1 pr-1 min-w-0">
                <p class="text-xs sm:text-sm font-bold text-ink truncate">Berhasil</p>
                <p class="text-[11px] sm:text-xs text-muted mt-0.5 leading-relaxed break-words">{{ session('ok') }}</p>
            </div>
            <button type="button" onclick="this.closest('[data-toast]').remove()" class="text-muted hover:text-ink cursor-pointer w-7 h-7 rounded-lg flex items-center justify-center hover:bg-surface active:scale-90 transition shrink-0" aria-label="Tutup">
                <span class="material-symbols-outlined text-[16px]">close</span>
            </button>
        </div>
    @endif

    @if (session('error'))
        <div id="toast-error" data-toast class="pointer-events-auto bg-white rounded-xl border border-line shadow-lg shadow-ink/8 p-3.5 sm:p-4 flex items-start gap-3 transform transition-all duration-300 translate-y-0 opacity-100" role="alert">
            <div class="w-8 h-8 rounded-lg bg-rose-50 text-rose-600 flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined icon-fill text-[19px]">error</span>
            </div>
            <div class="flex-1 pr-1 min-w-0">
                <p class="text-xs sm:text-sm font-bold text-ink truncate">Terdapat Kendala</p>
                <p class="text-[11px] sm:text-xs text-muted mt-0.5 leading-relaxed break-words">{{ session('error') }}</p>
            </div>
            <button type="button" onclick="this.closest('[data-toast]').remove()" class="text-muted hover:text-ink cursor-pointer w-7 h-7 rounded-lg flex items-center justify-center hover:bg-surface active:scale-90 transition shrink-0" aria-label="Tutup">
                <span class="material-symbols-outlined text-[16px]">close</span>
            </button>
        </div>
    @endif

    @if (session('pesan') || session('info') || session('status'))
        <div id="toast-info" data-toast class="pointer-events-auto bg-white rounded-xl border border-line shadow-lg shadow-ink/8 p-3.5 sm:p-4 flex items-start gap-3 transform transition-all duration-300 translate-y-0 opacity-100" role="status">
            <div class="w-8 h-8 rounded-lg bg-surface text-brand flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined icon-fill text-[19px]">info</span>
            </div>
            <div class="flex-1 pr-1 min-w-0">
                <p class="text-xs sm:text-sm font-bold text-ink truncate">Informasi</p>
                <p class="text-[11px] sm:text-xs text-muted mt-0.5 leading-relaxed break-words">{{ session('pesan') ?? session('info') ?? session('status') }}</p>
            </div>
            <button type="button" onclick="this.closest('[data-toast]').remove()" class="text-muted hover:text-ink cursor-pointer w-7 h-7 rounded-lg flex items-center justify-center hover:bg-surface active:scale-90 transition shrink-0" aria-label="Tutup">
                <span class="material-symbols-outlined text-[16px]">close</span>
            </button>
        </div>
    @endif

    @if (isset($errors) && $errors->any())
        <div id="toast-errors" data-toast class="pointer-events-auto bg-white rounded-xl border border-line shadow-lg shadow-ink/8 p-3.5 sm:p-4 flex items-start gap-3 transform transition-all duration-300 translate-y-0 opacity-100" role="alert">
            <div class="w-8 h-8 rounded-lg bg-rose-50 text-rose-600 flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined icon-fill text-[19px]">error</span>
            </div>
            <div class="flex-1 pr-1 min-w-0">
                <p class="text-xs sm:text-sm font-bold text-ink truncate">Periksa Kembali Input</p>
                <ul class="text-[11px] sm:text-xs text-muted mt-0.5 list-disc ml-3.5 space-y-0.5">
                    @foreach ($errors->all() as $e)
                        <li>{{ $e }}</li>
                    @endforeach
                </ul>
            </div>
            <button type="button" onclick="this.closest('[data-toast]').remove()" class="text-muted hover:text-ink cursor-pointer w-7 h-7 rounded-lg flex items-center justify-center hover:bg-surface active:scale-90 transition shrink-0" aria-label="Tutup">
                <span class="material-symbols-outlined text-[16px]">close</span>
            </button>
        </div>
    @endif
</div>
