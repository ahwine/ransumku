{{-- Modal Menu Mobile (Bottom Sheet / Drawer untuk Akses Menu Tambahan) --}}
<div id="modal-menu-mobile" class="fixed inset-0 z-40 hidden transition-opacity duration-200" role="dialog" aria-modal="true" aria-labelledby="modal-menu-mobile-title">
    {{-- Backdrop --}}
    <div class="fixed inset-0 bg-primary/40 backdrop-blur-xs transition-opacity" onclick="tutupModal('modal-menu-mobile')" aria-hidden="true"></div>

    {{-- Sheet Container (Slides up from bottom on mobile) --}}
    <div class="fixed inset-x-0 bottom-0 sm:static sm:flex sm:items-center sm:justify-center sm:min-h-screen p-0 sm:p-4 z-10 pointer-events-none">
        <div class="w-full max-w-lg bg-card rounded-t-[32px] sm:rounded-3xl border border-line shadow-[0_-10px_35px_rgba(0,36,21,0.12)] p-6 space-y-5 pointer-events-auto max-h-[85vh] overflow-y-auto transform transition-transform duration-300">
            
            {{-- Handle Bar (Mobile Drag Indicator) --}}
            <div class="w-12 h-1.5 rounded-full bg-[#E0EBE4] mx-auto -mt-1 sm:hidden" aria-hidden="true"></div>

            {{-- Header Sheet --}}
            <div class="flex items-center justify-between border-b border-line pb-3">
                <div class="flex items-center gap-2.5">
                    <div class="w-9 h-9 rounded-xl bg-brand/10 text-brand grid place-items-center">
                        <span class="material-symbols-outlined text-[20px]">widgets</span>
                    </div>
                    <div>
                        <h2 id="modal-menu-mobile-title" class="text-sm font-extrabold text-ink">Menu & Pengaturan</h2>
                        <p class="text-[11px] text-muted font-medium">Navigasi fitur lengkap RansumKu</p>
                    </div>
                </div>
                <button type="button" onclick="tutupModal('modal-menu-mobile')" class="w-8 h-8 rounded-full bg-surface hover:bg-surface-2 border border-line text-muted hover:text-ink grid place-items-center transition cursor-pointer" aria-label="Tutup Menu">
                    <span class="material-symbols-outlined text-[18px]">close</span>
                </button>
            </div>


            {{-- Grid Menu Tambahan --}}
            <div class="space-y-2">
                <span class="text-[10px] font-extrabold tracking-wider uppercase text-muted px-1 block">FITUR & MASTER DATA</span>
                
                <div class="grid grid-cols-2 gap-2.5">
                    {{-- 1. Kelola Kandang --}}
                    <a href="{{ route('kandang.index') }}" class="p-3.5 rounded-2xl border transition-all flex flex-col justify-between gap-3 {{ request()->routeIs('kandang.*') ? 'bg-brand/10 border-brand/40 shadow-xs' : 'bg-white hover:bg-surface border-line' }}">
                        <div class="w-9 h-9 rounded-xl {{ request()->routeIs('kandang.*') ? 'bg-brand text-white' : 'bg-surface text-ink' }} grid place-items-center">
                            <span class="material-symbols-outlined text-[20px]">warehouse</span>
                        </div>
                        <div>
                            <span class="text-xs font-bold text-ink block">Kelola Kandang</span>
                            <span class="text-[10px] text-muted line-clamp-1">Populasi & Multi-Kandang</span>
                        </div>
                    </a>

                    {{-- 2. Bahan Pakan --}}
                    <a href="{{ route('bahan.index') }}" class="p-3.5 rounded-2xl border transition-all flex flex-col justify-between gap-3 {{ request()->routeIs('bahan.*') ? 'bg-brand/10 border-brand/40 shadow-xs' : 'bg-white hover:bg-surface border-line' }}">
                        <div class="w-9 h-9 rounded-xl {{ request()->routeIs('bahan.*') ? 'bg-brand text-white' : 'bg-surface text-ink' }} grid place-items-center">
                            <span class="material-symbols-outlined text-[20px]">grain</span>
                        </div>
                        <div>
                            <span class="text-xs font-bold text-ink block">Bahan Pakan</span>
                            <span class="text-[10px] text-muted line-clamp-1">Katalog & Nutrisi Bahan</span>
                        </div>
                    </a>

                    {{-- 3. Acuan Gizi (SNI) --}}
                    <a href="{{ route('acuan.index') }}" class="p-3.5 rounded-2xl border transition-all flex flex-col justify-between gap-3 {{ request()->routeIs('acuan.*') ? 'bg-brand/10 border-brand/40 shadow-xs' : 'bg-white hover:bg-surface border-line' }}">
                        <div class="w-9 h-9 rounded-xl {{ request()->routeIs('acuan.*') ? 'bg-brand text-white' : 'bg-surface text-ink' }} grid place-items-center">
                            <span class="material-symbols-outlined text-[20px]">verified</span>
                        </div>
                        <div>
                            <span class="text-xs font-bold text-ink block">Acuan Gizi (SNI)</span>
                            <span class="text-[10px] text-muted line-clamp-1">Standar Nutrisi Petelur</span>
                        </div>
                    </a>

                    {{-- 4. Tentang RansumKu --}}
                    <a href="{{ route('tentang') }}" class="p-3.5 rounded-2xl border transition-all flex flex-col justify-between gap-3 {{ request()->routeIs('tentang') ? 'bg-brand/10 border-brand/40 shadow-xs' : 'bg-white hover:bg-surface border-line' }}">
                        <div class="w-9 h-9 rounded-xl {{ request()->routeIs('tentang') ? 'bg-brand text-white' : 'bg-surface text-ink' }} grid place-items-center">
                            <span class="material-symbols-outlined text-[20px]">info</span>
                        </div>
                        <div>
                            <span class="text-xs font-bold text-ink block">Tentang Sistem</span>
                            <span class="text-[10px] text-muted line-clamp-1">Panduan Smart Poultry</span>
                        </div>
                    </a>
                </div>
            </div>

        </div>
    </div>
</div>

