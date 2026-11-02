@php
    $id = $idDropdown ?? ('notif-dropdown-' . uniqid());
    $isMobile = $isMobile ?? false;
    $unreadCount = $unreadAlertsCount ?? 0;
    $alerts = $modalAlerts ?? collect();
    $waspadaCount = $alerts->where('level', 'waspada')->count();
@endphp

<div class="relative inline-block text-left custom-dropdown-container z-50" id="{{ $id }}">
    @if($isMobile)
        {{-- Mobile Animated Expanding Capsule Button (Dynamic Island Style) --}}
        <button type="button" 
                id="btn-capsule-notif"
                onclick="toggleNotifikasiMobile(event)" 
                class="dropdown-trigger header-capsule-btn" 
                title="Pusat Peringatan & Notifikasi Kandang"
                aria-haspopup="true"
                aria-expanded="false"
                aria-label="Buka Notifikasi">
            {{-- Icon Wrapper: Always exact 36px x 36px --}}
            <span class="capsule-icon-wrap">
                <span class="relative inline-flex items-center justify-center">
                    <span class="material-symbols-outlined text-[19px] leading-none select-none">notifications</span>
                    
                    {{-- Dot Merah Notifikasi di Samping Kanan Atas Lonceng --}}
                    <span data-notif-dot 
                          class="{{ $unreadCount > 0 ? 'inline-block' : 'hidden' }} absolute -top-0.5 -right-0.5 w-2.5 h-2.5 rounded-full bg-rose-600 ring-2 ring-white pointer-events-none"
                          style="box-shadow: 0 0 0 2px #ffffff; pointer-events: none;"></span>
                </span>
            </span>

            {{-- Expanding Label Wrapper --}}
            <span class="capsule-label-wrap">
                <span class="capsule-label-text">Notifikasi</span>
            </span>
        </button>
    @else
        {{-- Desktop Standard Button --}}
        <button type="button" 
                onclick="toggleCustomDropdown('{{ $id }}', event)" 
                class="dropdown-trigger p-2 rounded-lg text-ink hover:bg-surface hover:text-brand transition flex items-center justify-center cursor-pointer focus:outline-none group active:scale-95" 
                title="Pusat Peringatan & Notifikasi Kandang"
                aria-haspopup="true"
                aria-expanded="false"
                aria-label="Buka Notifikasi">
            <span class="relative inline-flex">
                <span class="material-symbols-outlined text-[22px] transition-transform group-hover:scale-105 select-none leading-none">notifications</span>
                
                {{-- Dot Merah Notifikasi di Samping Kanan Atas Lonceng --}}
                <span data-notif-dot 
                      class="{{ $unreadCount > 0 ? 'inline-block' : 'hidden' }} absolute top-0 right-0 w-2.5 h-2.5 rounded-full bg-rose-600 ring-2 ring-white pointer-events-none"
                      style="position: absolute; top: 0px; right: 0px; width: 10px; height: 10px; border-radius: 9999px; background-color: #e11d48; box-shadow: 0 0 0 2px #ffffff; pointer-events: none;"></span>
            </span>
        </button>
    @endif

    {{-- Popover Panel Dropdown (Tepat di Bawah Ikon Lonceng, Tanpa Blur) --}}
    <div class="dropdown-menu hidden {{ $isMobile ? 'fixed inset-x-3.5 top-[4.25rem] max-w-md mx-auto' : 'absolute right-0 top-full mt-2 w-80 sm:w-96 max-w-[calc(100vw-1.5rem)]' }} rounded-2xl bg-white border border-line shadow-2xl p-0 z-50 select-none custom-dropdown-menu">
        
        {{-- Header Popover Panel --}}
        <div class="p-3.5 border-b border-line/60 bg-surface/30">
            <div class="flex items-center justify-between gap-2 mb-2.5">
                <div class="flex items-center gap-2">
                    <h3 class="font-bold text-sm text-ink">Notifikasi</h3>
                    <span data-notif-modal-badge class="text-[10px] font-bold px-2 py-0.5 rounded-full {{ $unreadCount > 0 ? 'bg-rose-100 text-rose-700 border border-rose-200' : 'bg-emerald-100 text-emerald-700 border border-emerald-200' }}">
                        {{ $unreadCount > 0 ? $unreadCount . ' Baru' : 'Semua Terbaca' }}
                    </span>
                </div>

                {{-- Tandai Semua Dibaca --}}
                <button type="button" 
                        id="btn-mark-all-read"
                        onclick="tandaiSemuaNotifikasiDibaca()"
                        class="{{ $unreadCount > 0 ? 'flex' : 'hidden' }} items-center gap-1 text-[11px] font-bold text-brand hover:text-brand-dark px-2 py-0.5 rounded-lg hover:bg-surface transition cursor-pointer"
                        title="Tandai semua notifikasi sudah dibaca">
                    <span class="material-symbols-outlined text-[14px]">done_all</span>
                    <span>Tandai Dibaca</span>
                </button>
            </div>

            {{-- Filter Tabs (All, Unread, Waspada) --}}
            <div class="flex items-center gap-1 overflow-x-auto no-scrollbar text-[11px]">
                <button type="button" 
                        data-notif-tab-btn="all"
                        onclick="filterNotifTab('all', '{{ $id }}', this)"
                        class="px-2.5 py-1 rounded-full font-bold cursor-pointer transition bg-brand text-white shadow-2xs">
                    Semua <span class="ml-0.5 opacity-90 font-bold">({{ $alerts->count() }})</span>
                </button>
                <button type="button" 
                        data-notif-tab-btn="unread"
                        onclick="filterNotifTab('unread', '{{ $id }}', this)"
                        class="px-2.5 py-1 rounded-full font-semibold cursor-pointer transition bg-surface text-muted hover:text-ink hover:bg-surface-2 border border-line/60">
                    Belum Dibaca <span class="ml-0.5 opacity-90 font-bold" data-tab-unread-count>({{ $unreadCount }})</span>
                </button>
                @if ($waspadaCount > 0)
                    <button type="button" 
                            data-notif-tab-btn="waspada"
                            onclick="filterNotifTab('waspada', '{{ $id }}', this)"
                            class="px-2.5 py-1 rounded-full font-semibold cursor-pointer transition bg-surface text-muted hover:text-ink hover:bg-surface-2 border border-line/60">
                        Kritis <span class="ml-0.5 opacity-90 font-bold">({{ $waspadaCount }})</span>
                    </button>
                @endif
            </div>
        </div>

        {{-- Body: Daftar Notifikasi --}}
        <div class="max-h-[55vh] sm:max-h-[340px] overflow-y-auto custom-dropdown-scroll divide-y divide-line/40" data-notif-list>
            @forelse ($alerts as $alert)
                @php
                    $isUnread = !$alert->dibaca;
                    $jenisLabel = match($alert->jenis) {
                        'pakan_naik_telur_turun' => 'Anomali Pakan & Telur',
                        'lonjakan_kematian' => 'Lonjakan Kematian Ayam',
                        'biaya_per_kg_telur_naik' => 'Kenaikan Biaya Pakan Telur',
                        'cuaca_ekstrem', 'heat_stress' => 'Peringatan Heat Stress',
                        default => ucwords(str_replace('_', ' ', $alert->jenis))
                    };
                    $iconName = match($alert->jenis) {
                        'pakan_naik_telur_turun' => 'trending_down',
                        'lonjakan_kematian' => 'warning',
                        'biaya_per_kg_telur_naik' => 'payments',
                        'cuaca_ekstrem', 'heat_stress' => 'thermostat',
                        default => ($alert->level === 'waspada' ? 'error' : 'info')
                    };
                    $iconColor = $alert->level === 'waspada' ? 'bg-rose-50 text-rose-600 border border-rose-200/70' : 'bg-amber-50 text-amber-600 border border-amber-200/70';
                @endphp

                <div data-notif-item
                     data-alert-id="{{ $alert->id }}"
                     data-is-unread="{{ $isUnread ? 'true' : 'false' }}"
                     data-level="{{ $alert->level }}"
                     onclick="tandaiSatuNotifikasiDibaca({{ $alert->id }}, this)"
                     class="p-3 transition-colors cursor-pointer flex items-start gap-3 group {{ $isUnread ? 'bg-surface/60 hover:bg-surface' : 'bg-white hover:bg-surface/50 opacity-80' }}">
                    
                    {{-- Squircle Icon Kategori --}}
                    <div class="w-8 h-8 rounded-xl flex items-center justify-center shrink-0 {{ $iconColor }}">
                        <span class="material-symbols-outlined text-[17px]">{{ $iconName }}</span>
                    </div>

                    {{-- Isi Notifikasi --}}
                    <div class="flex-1 min-w-0 pr-1">
                        <div class="flex items-center justify-between gap-1.5">
                            <span class="font-bold text-xs text-ink truncate {{ $isUnread ? 'text-brand-dark' : '' }}">{{ $jenisLabel }}</span>
                            <span class="text-[10px] text-muted shrink-0 font-medium">
                                {{ $alert->created_at ? $alert->created_at->diffForHumans() : ($alert->tanggal_data ? $alert->tanggal_data->translatedFormat('d M') : 'Terkini') }}
                            </span>
                        </div>
                        <p class="text-[11.5px] text-ink/80 mt-0.5 leading-relaxed line-clamp-2">
                            {{ $alert->pesan }}
                        </p>
                    </div>

                    {{-- Dot Merah Belum Dibaca (Akan hilang seketika saat diklik) --}}
                    <div class="shrink-0 flex items-center justify-center self-center pl-0.5">
                        <span data-notif-item-dot class="{{ $isUnread ? 'inline-block' : 'hidden' }} w-2.5 h-2.5 rounded-full bg-rose-600 ring-2 ring-white shadow-2xs animate-pulse"></span>
                    </div>
                </div>
            @empty
                {{-- Empty State --}}
                <div data-notif-empty class="py-8 px-4 text-center space-y-2">
                    <div class="w-11 h-11 rounded-2xl bg-emerald-50 text-emerald-600 border border-emerald-200/60 flex items-center justify-center mx-auto shadow-2xs">
                        <span class="material-symbols-outlined text-[24px]">check_circle</span>
                    </div>
                    <div class="space-y-0.5">
                        <p class="text-xs font-bold text-ink">Semua Kondisi Normal</p>
                        <p class="text-tiny text-muted max-w-xs mx-auto">Tidak ada notifikasi anomali atau peringatan kandang saat ini.</p>
                    </div>
                </div>
            @endforelse

            {{-- Empty State untuk filter tab --}}
            <div data-notif-empty-filter class="hidden py-8 px-4 text-center space-y-1 text-muted text-xs">
                <span class="material-symbols-outlined text-[24px] text-muted/60 block">notifications_off</span>
                <p class="font-bold text-ink text-xs">Tidak Ada Pesan</p>
                <p class="text-tiny">Tidak ada pesan untuk kategori yang dipilih.</p>
            </div>
        </div>

        {{-- Footer Panel Dropdown: Tombol Selengkapnya ke Peringatan Dini --}}
        <div class="px-3.5 py-2.5 border-t border-line/60 bg-surface/30 flex items-center justify-between">
            <span class="text-[10px] text-muted">RansumKu Early Warning</span>
            
            <a href="{{ route('asisten.index', ['tab' => 'peringatan']) }}" 
               class="inline-flex items-center gap-1.5 text-xs font-bold text-brand hover:text-brand-dark px-3 py-1.5 rounded-lg hover:bg-brand-soft/70 transition">
                <span>Selengkapnya</span>
                <span class="material-symbols-outlined text-[15px]">arrow_forward</span>
            </a>
        </div>
    </div>
</div>

