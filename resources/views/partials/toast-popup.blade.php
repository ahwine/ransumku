{{-- Floating Toast Notification Popup --}}
@if (session('ok'))
    <div id="toast-notification" data-toast class="fixed top-20 right-4 lg:right-8 z-50 max-w-sm w-full bg-white/95 backdrop-blur-md rounded-2xl border border-emerald-200/90 shadow-2xl p-4 flex items-start gap-3 transform transition-all duration-300 translate-y-0 opacity-100" role="status">
        <div class="w-9 h-9 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center shrink-0">
            <span class="material-symbols-outlined icon-fill text-[20px]">check_circle</span>
        </div>
        <div class="flex-1 pr-1">
            <p class="text-xs font-bold text-ink">Berhasil</p>
            <p class="text-xs text-muted mt-0.5 leading-relaxed">{{ session('ok') }}</p>
        </div>
        <button type="button" onclick="this.closest('#toast-notification').remove()" class="text-muted hover:text-ink cursor-pointer p-1 rounded-lg hover:bg-surface transition" aria-label="Tutup">
            <span class="material-symbols-outlined text-[16px]">close</span>
        </button>
    </div>
@endif

@if (isset($errors) && $errors->any())
    <div id="toast-error" data-toast class="fixed top-20 right-4 lg:right-8 z-50 max-w-sm w-full bg-white/95 backdrop-blur-md rounded-2xl border border-rose-200/90 shadow-2xl p-4 flex items-start gap-3 transform transition-all duration-300 translate-y-0 opacity-100" role="alert">
        <div class="w-9 h-9 rounded-full bg-rose-100 text-danger flex items-center justify-center shrink-0">
            <span class="material-symbols-outlined icon-fill text-[20px]">error</span>
        </div>
        <div class="flex-1 pr-1">
            <p class="text-xs font-bold text-danger">Terdapat Kendala</p>
            <ul class="text-xs text-muted mt-0.5 list-disc ml-3 space-y-0.5">
                @foreach ($errors->all() as $e)
                    <li>{{ $e }}</li>
                @endforeach
            </ul>
        </div>
        <button type="button" onclick="this.closest('#toast-error').remove()" class="text-muted hover:text-ink cursor-pointer p-1 rounded-lg hover:bg-surface transition" aria-label="Tutup">
            <span class="material-symbols-outlined text-[16px]">close</span>
        </button>
    </div>
@endif

