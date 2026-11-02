{{-- Floating Toast Notification Popup Universal (Mobile-Responsive, Floating Island Style) --}}
<div id="toast-container" class="fixed top-3 sm:top-5 inset-x-3 sm:inset-x-auto sm:right-6 lg:right-8 z-[99999] flex flex-col gap-2.5 max-w-none sm:max-w-md w-auto sm:w-full pointer-events-none transition-all duration-300">
    @if (session('ok'))
        <div id="toast-notification" data-toast class="pointer-events-auto bg-white/95 dark:bg-slate-900/95 backdrop-blur-xl rounded-2xl border border-emerald-300/80 dark:border-emerald-700/80 shadow-2xl shadow-emerald-950/10 p-3.5 sm:p-4 flex items-start gap-3 transform transition-all duration-300 translate-y-0 opacity-100" role="status">
            <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl bg-emerald-100 dark:bg-emerald-900/50 text-emerald-700 dark:text-emerald-300 flex items-center justify-center shrink-0 shadow-2xs">
                <span class="material-symbols-outlined icon-fill text-[20px] sm:text-[22px]">check_circle</span>
            </div>
            <div class="flex-1 pr-1 min-w-0">
                <p class="text-xs sm:text-sm font-bold text-ink dark:text-white truncate">Berhasil</p>
                <p class="text-[11px] sm:text-xs text-muted dark:text-slate-300 mt-0.5 leading-relaxed break-words">{{ session('ok') }}</p>
            </div>
            <button type="button" onclick="this.closest('[data-toast]').remove()" class="text-muted hover:text-ink dark:text-slate-400 dark:hover:text-white cursor-pointer w-8 h-8 rounded-lg flex items-center justify-center hover:bg-surface dark:hover:bg-slate-800 active:scale-90 transition shrink-0" aria-label="Tutup">
                <span class="material-symbols-outlined text-[18px]">close</span>
            </button>
        </div>
    @endif

    @if (session('error'))
        <div id="toast-error" data-toast class="pointer-events-auto bg-white/95 dark:bg-slate-900/95 backdrop-blur-xl rounded-2xl border border-rose-300/80 dark:border-rose-700/80 shadow-2xl shadow-rose-950/10 p-3.5 sm:p-4 flex items-start gap-3 transform transition-all duration-300 translate-y-0 opacity-100" role="alert">
            <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl bg-rose-100 dark:bg-rose-900/50 text-danger flex items-center justify-center shrink-0 shadow-2xs">
                <span class="material-symbols-outlined icon-fill text-[20px] sm:text-[22px]">error</span>
            </div>
            <div class="flex-1 pr-1 min-w-0">
                <p class="text-xs sm:text-sm font-bold text-danger truncate">Terdapat Kendala</p>
                <p class="text-[11px] sm:text-xs text-muted dark:text-slate-300 mt-0.5 leading-relaxed break-words">{{ session('error') }}</p>
            </div>
            <button type="button" onclick="this.closest('[data-toast]').remove()" class="text-muted hover:text-ink dark:text-slate-400 dark:hover:text-white cursor-pointer w-8 h-8 rounded-lg flex items-center justify-center hover:bg-surface dark:hover:bg-slate-800 active:scale-90 transition shrink-0" aria-label="Tutup">
                <span class="material-symbols-outlined text-[18px]">close</span>
            </button>
        </div>
    @endif

    @if (session('pesan') || session('info') || session('status'))
        <div id="toast-info" data-toast class="pointer-events-auto bg-white/95 dark:bg-slate-900/95 backdrop-blur-xl rounded-2xl border border-sky-300/80 dark:border-sky-700/80 shadow-2xl shadow-sky-950/10 p-3.5 sm:p-4 flex items-start gap-3 transform transition-all duration-300 translate-y-0 opacity-100" role="status">
            <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl bg-sky-100 dark:bg-sky-900/50 text-sky-700 dark:text-sky-300 flex items-center justify-center shrink-0 shadow-2xs">
                <span class="material-symbols-outlined icon-fill text-[20px] sm:text-[22px]">info</span>
            </div>
            <div class="flex-1 pr-1 min-w-0">
                <p class="text-xs sm:text-sm font-bold text-ink dark:text-white truncate">Informasi</p>
                <p class="text-[11px] sm:text-xs text-muted dark:text-slate-300 mt-0.5 leading-relaxed break-words">{{ session('pesan') ?? session('info') ?? session('status') }}</p>
            </div>
            <button type="button" onclick="this.closest('[data-toast]').remove()" class="text-muted hover:text-ink dark:text-slate-400 dark:hover:text-white cursor-pointer w-8 h-8 rounded-lg flex items-center justify-center hover:bg-surface dark:hover:bg-slate-800 active:scale-90 transition shrink-0" aria-label="Tutup">
                <span class="material-symbols-outlined text-[18px]">close</span>
            </button>
        </div>
    @endif

    @if (isset($errors) && $errors->any())
        <div id="toast-errors" data-toast class="pointer-events-auto bg-white/95 dark:bg-slate-900/95 backdrop-blur-xl rounded-2xl border border-rose-300/80 dark:border-rose-700/80 shadow-2xl shadow-rose-950/10 p-3.5 sm:p-4 flex items-start gap-3 transform transition-all duration-300 translate-y-0 opacity-100" role="alert">
            <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl bg-rose-100 dark:bg-rose-900/50 text-danger flex items-center justify-center shrink-0 shadow-2xs">
                <span class="material-symbols-outlined icon-fill text-[20px] sm:text-[22px]">error</span>
            </div>
            <div class="flex-1 pr-1 min-w-0">
                <p class="text-xs sm:text-sm font-bold text-danger truncate">Terdapat Kendala</p>
                <ul class="text-[11px] sm:text-xs text-muted dark:text-slate-300 mt-0.5 list-disc ml-3.5 space-y-0.5">
                    @foreach ($errors->all() as $e)
                        <li>{{ $e }}</li>
                    @endforeach
                </ul>
            </div>
            <button type="button" onclick="this.closest('[data-toast]').remove()" class="text-muted hover:text-ink dark:text-slate-400 dark:hover:text-white cursor-pointer w-8 h-8 rounded-lg flex items-center justify-center hover:bg-surface dark:hover:bg-slate-800 active:scale-90 transition shrink-0" aria-label="Tutup">
                <span class="material-symbols-outlined text-[18px]">close</span>
            </button>
        </div>
    @endif
</div>
