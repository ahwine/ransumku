{{-- ======================================================== --}}
{{-- 1. MODAL PILIH KANDANG AKTIF (MOBILE & DESKTOP GLOBAL)     --}}
{{-- ======================================================== --}}
<div id="modal-pilih-kandang-mobile" class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-0 sm:p-4 bg-black/50 backdrop-blur-xs transition-opacity duration-200 hidden opacity-0 pointer-events-none" role="dialog" aria-modal="true" aria-labelledby="modal-pilih-kandang-title">
    <div class="fixed inset-0" onclick="tutupModal('modal-pilih-kandang-mobile')"></div>

    <div data-modal-card class="relative z-10 w-full max-w-lg bg-white rounded-t-[32px] sm:rounded-3xl border border-line shadow-2xl p-5 sm:p-6 space-y-4 max-h-[85vh] overflow-y-auto transform transition-all duration-200 translate-y-4 sm:translate-y-0 scale-95 opacity-0">
        {{-- Handle Bar Drag Indicator di HP --}}
        <div class="w-12 h-1.5 rounded-full bg-[#E0EBE4] mx-auto -mt-1 sm:hidden" aria-hidden="true"></div>

        {{-- Header Modal --}}
        <div class="flex items-center justify-between pb-3 border-b border-line">
            <div class="flex items-center gap-2.5">
                <div class="w-10 h-10 rounded-2xl bg-brand/10 text-brand grid place-items-center shrink-0">
                    <span class="material-symbols-outlined text-[22px]">warehouse</span>
                </div>
                <div>
                    <h3 id="modal-pilih-kandang-title" class="text-sm sm:text-base font-extrabold text-ink leading-tight">Daftar Kandang</h3>
                    <p class="text-[11px] text-muted font-medium mt-0.5">Pilih unit aktif atau kelola kandang</p>
                </div>
            </div>
            <button type="button" onclick="tutupModal('modal-pilih-kandang-mobile')" class="w-8 h-8 rounded-full bg-surface hover:bg-surface-2 border border-line text-muted hover:text-ink grid place-items-center transition cursor-pointer" aria-label="Tutup">
                <span class="material-symbols-outlined text-[18px]">close</span>
            </button>
        </div>

        {{-- List Kandang --}}
        <div class="space-y-2.5">
            @forelse($userFarms as $f)
                @php
                    $isActive = ($farmNav && $farmNav->id === $f->id);
                    $presetK = \App\Services\WeatherService::PRESET_KOTA[$f->lokasi_kota ?? 'Blitar'] ?? null;
                    $kotaDisplay = $presetK['nama'] ?? ($f->lokasi_kota ?? 'Blitar');
                    $provDisplay = $presetK['provinsi'] ?? 'Jawa Timur';
                @endphp

                <div class="rounded-2xl border p-3 sm:p-3.5 transition-all flex items-center justify-between gap-3 {{ $isActive ? 'bg-brand/5 border-brand/50 shadow-xs ring-1 ring-brand/20' : 'bg-surface/30 hover:bg-white border-line' }}">
                    {{-- Sisi Kiri: Switch Kandang jika belum aktif --}}
                    @if($isActive)
                        <div class="flex-1 min-w-0 flex items-center gap-3">
                            <div class="w-8 h-8 rounded-full bg-brand text-white grid place-items-center shrink-0 shadow-2xs">
                                <span class="material-symbols-outlined text-[18px]">check</span>
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-1.5 flex-wrap">
                                    <span class="text-xs sm:text-sm font-black text-ink truncate">{{ $f->nama }}</span>
                                    <span class="px-2 py-0.5 rounded-full text-[9px] font-extrabold bg-emerald-100 text-emerald-800 border border-emerald-300">Aktif</span>
                                </div>
                                <p class="text-[10px] text-muted font-semibold truncate mt-0.5">
                                    {{ number_format($f->jumlah_ayam, 0, ',', '.') }} Ekor · Mg ke-{{ $f->umurMinggu() }} ({{ $f->faseUmurDetail()['nama'] }}) · {{ $kotaDisplay }}
                                </p>
                            </div>
                        </div>
                    @else
                        <form method="POST" action="{{ route('kandang.switch') }}" class="flex-1 min-w-0 flex items-center cursor-pointer m-0">
                            @csrf
                            <input type="hidden" name="farm_id" value="{{ $f->id }}">
                            <button type="submit" class="w-full text-left flex items-center gap-3 cursor-pointer group">
                                <div class="w-8 h-8 rounded-full bg-surface border border-line text-muted group-hover:text-brand group-hover:border-brand/40 grid place-items-center shrink-0 transition-colors">
                                    <span class="material-symbols-outlined text-[18px]">radio_button_unchecked</span>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <span class="text-xs sm:text-sm font-bold text-ink group-hover:text-brand transition-colors block truncate">{{ $f->nama }}</span>
                                    <p class="text-[10px] text-muted font-medium truncate mt-0.5">
                                        {{ number_format($f->jumlah_ayam, 0, ',', '.') }} Ekor · Mg ke-{{ $f->umurMinggu() }} ({{ $f->faseUmurDetail()['nama'] }}) · {{ $kotaDisplay }}
                                    </p>
                                </div>
                            </button>
                        </form>
                    @endif

                    {{-- Sisi Kanan: Tombol Edit Kandang --}}
                    <button type="button" 
                            onclick="tutupModal('modal-pilih-kandang-mobile'); setTimeout(() => bukaModal('modal-edit-kandang-{{ $f->id }}'), 150);" 
                            class="w-8 h-8 rounded-xl bg-white hover:bg-surface border border-line text-muted hover:text-ink hover:border-brand/40 grid place-items-center transition cursor-pointer active:scale-95 shrink-0 shadow-2xs" 
                            title="Edit Data {{ $f->nama }}"
                            aria-label="Edit Kandang {{ $f->nama }}">
                        <span class="material-symbols-outlined text-[17px] text-brand">edit</span>
                    </button>
                </div>
            @empty
                <div class="p-4 text-center text-xs text-muted">
                    Belum ada kandang terdaftar.
                </div>
            @endforelse
        </div>

        {{-- Tombol Tambah Kandang di Paling Bawah --}}
        <div class="pt-2 border-t border-line/60">
            <button type="button" 
                    onclick="tutupModal('modal-pilih-kandang-mobile'); setTimeout(() => bukaModal('modal-tambah-kandang'), 150);" 
                    class="w-full flex items-center justify-center gap-2 px-4 py-2.5 rounded-full bg-brand hover:bg-brand-dark text-white font-bold text-xs shadow-xs hover:shadow-sm transition active:scale-95 cursor-pointer">
                <span class="material-symbols-outlined text-[17px]">add</span>
                <span>Tambah Kandang Baru</span>
            </button>
        </div>
    </div>
</div>

{{-- ======================================================== --}}
{{-- 2. MODAL TAMBAH KANDANG BARU                             --}}
{{-- ======================================================== --}}
@php
    $daftarStrain = ['Lohmann Brown', 'Isa Brown', 'Hy-Line Brown', 'Novogen Brown', 'H&N Brown Nick', 'Ayam Arab / KUB Petelur'];
    $strainTambah = old('strain_ayam', 'Lohmann Brown');
@endphp
<div id="modal-tambah-kandang" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-xs transition-opacity duration-200 hidden opacity-0 pointer-events-none" role="dialog" aria-modal="true">
    <div class="absolute inset-0" onclick="tutupModal('modal-tambah-kandang')"></div>

    <div data-modal-card class="relative z-10 w-full max-w-lg bg-white rounded-2xl p-6 shadow-2xl border border-line transform transition-all duration-200 scale-95 opacity-0 space-y-4 overflow-visible">
        <div class="flex items-center justify-between pb-3 border-b border-line">
            <div>
                <h3 class="text-sm md:text-base font-bold text-ink">Tambah Unit Kandang Baru</h3>
                <p class="text-xs text-muted mt-0.5">Isi nama kandang, populasi, umur ayam saat ini, dan lokasi kandang.</p>
            </div>
            <button type="button" onclick="tutupModal('modal-tambah-kandang')" class="text-muted hover:text-ink transition cursor-pointer p-1" aria-label="Tutup">
                <span class="material-symbols-outlined text-[20px]">close</span>
            </button>
        </div>

        <form method="POST" action="{{ route('kandang.store') }}" class="space-y-3.5">
            @csrf
            <input type="hidden" name="fase" value="produksi">
            <input type="hidden" name="tgl_chick_in" value="{{ today()->toDateString() }}">

            <div>
                <label class="lbl">Nama Kandang <span class="text-danger">*</span></label>
                <input type="text" name="nama" class="field !min-h-10 text-xs font-medium" placeholder="Contoh: Kandang Flamboyan" required autocomplete="off">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="lbl">Populasi Ayam (Ekor) <span class="text-danger">*</span></label>
                    <input type="number" name="jumlah_ayam" min="1" max="1000000" class="field !min-h-10 text-xs font-medium" placeholder="Contoh: 200" required>
                </div>
                <div>
                    <label class="lbl">Umur Ayam Saat Ini (Minggu) <span class="text-danger">*</span></label>
                    <input type="number" name="umur_awal_minggu" min="1" max="120" class="field !min-h-10 text-xs font-medium" placeholder="Contoh: 20" required>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="lbl">Jenis Bibit (Strain)</label>
                    <div class="relative inline-block w-full text-left custom-dropdown-container z-30" id="strain-tambah-global">
                        <input type="hidden" name="strain_ayam" value="{{ $strainTambah }}" class="dropdown-hidden-input">
                        <button type="button"
                                onclick="toggleCustomDropdown('strain-tambah-global', event)"
                                class="dropdown-trigger flex items-center justify-between w-full min-h-10 px-3.5 py-2 bg-white border border-line rounded-lg text-xs font-medium text-ink shadow-2xs hover:border-brand/40 focus:border-brand focus:ring-2 focus:ring-brand/15 transition cursor-pointer text-left">
                            <span class="font-medium text-xs text-ink truncate dropdown-selected-label">{{ $strainTambah }}</span>
                            <span class="material-symbols-outlined text-[16px] text-muted transition-transform duration-200 dropdown-caret shrink-0">expand_more</span>
                        </button>
                        <div class="dropdown-menu hidden absolute left-0 top-full mt-1.5 w-full rounded-xl bg-white border border-line shadow-2xl p-1.5 z-50 select-none custom-dropdown-menu space-y-0.5">
                            @foreach ($daftarStrain as $st)
                                @php $isStSelected = ($strainTambah === $st); @endphp
                                <button type="button"
                                        data-city-item
                                        onclick="selectCustomDropdownKotaInput('strain-tambah-global', '{{ $st }}', '{{ $st }}', '')"
                                        class="w-full flex items-center justify-between gap-2 px-2.5 py-1.5 rounded-lg text-left text-xs transition-all cursor-pointer group {{ $isStSelected ? 'bg-surface font-bold text-brand' : 'hover:bg-surface/80 text-ink font-medium' }}">
                                    <span class="truncate {{ $isStSelected ? 'text-brand font-bold' : 'text-ink font-medium group-hover:text-brand' }}">{{ $st }}</span>
                                    <span data-active-badge class="{{ $isStSelected ? 'inline-block' : 'hidden' }} text-xs font-bold text-brand shrink-0">Dipilih</span>
                                    <span data-choose-hint class="{{ $isStSelected ? 'hidden' : 'inline-block' }} text-[11px] text-muted opacity-0 group-hover:opacity-100 transition-opacity shrink-0">Pilih</span>
                                </button>
                            @endforeach
                        </div>
                    </div>
                </div>
                <div>
                    <label class="lbl">Sentra Lokasi / Kota</label>
                    @include('partials.dropdown-pilih-kota', ['id' => 'kota-tambah-global', 'value' => old('lokasi_kota', 'Surabaya'), 'presetKota' => $presetKota])
                </div>
            </div>

            <p class="text-[11px] text-muted flex items-center gap-1.5">
                <span class="material-symbols-outlined text-[14px] text-brand shrink-0">info</span>
                <span>Fase produksi menyesuaikan umur ayam secara otomatis.</span>
            </p>

            <div class="flex justify-end items-center gap-2 pt-3 border-t border-line">
                <button type="button" onclick="tutupModal('modal-tambah-kandang')" class="inline-flex items-center justify-center px-4 py-2 rounded-full text-xs font-semibold text-ink bg-white hover:bg-surface border border-line transition shadow-2xs cursor-pointer">
                    Batal
                </button>
                <button type="submit" class="inline-flex items-center justify-center px-5 py-2 rounded-full text-xs font-bold text-white bg-brand hover:bg-brand-dark transition shadow-xs hover:shadow-sm cursor-pointer">
                    Simpan & Aktifkan Kandang
                </button>
            </div>
        </form>
    </div>
</div>

{{-- ======================================================== --}}
{{-- 3. MODAL EDIT KANDANG (DEDICATED PER USER FARM)          --}}
{{-- ======================================================== --}}
@foreach ($userFarms as $f)
    @php
        $strainEdit = $f->strain_ayam ?? 'Lohmann Brown';
    @endphp
    <div id="modal-edit-kandang-{{ $f->id }}" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-xs transition-opacity duration-200 hidden opacity-0 pointer-events-none" role="dialog" aria-modal="true">
        <div class="absolute inset-0" onclick="tutupModal('modal-edit-kandang-{{ $f->id }}')"></div>

        <div data-modal-card class="relative z-10 w-full max-w-lg bg-white rounded-2xl p-6 shadow-2xl border border-line transform transition-all duration-200 scale-95 opacity-0 space-y-4 overflow-visible">
            <div class="flex items-center justify-between pb-3 border-b border-line">
                <div>
                    <h3 class="text-sm md:text-base font-bold text-ink">Edit Data: {{ $f->nama }}</h3>
                    <p class="text-xs text-muted mt-0.5">Perbarui nama kandang, populasi, umur ayam saat ini, atau jenis bibit.</p>
                </div>
                <button type="button" onclick="tutupModal('modal-edit-kandang-{{ $f->id }}')" class="text-muted hover:text-ink transition cursor-pointer p-1" aria-label="Tutup">
                    <span class="material-symbols-outlined text-[20px]">close</span>
                </button>
            </div>

            <form method="POST" action="{{ route('kandang.update', $f) }}" class="space-y-3.5">
                @csrf
                @method('PUT')
                <input type="hidden" name="fase" value="{{ $f->fase ?? 'produksi' }}">
                <input type="hidden" name="tgl_chick_in" value="{{ today()->toDateString() }}">

                <div>
                    <label class="lbl">Nama Kandang <span class="text-danger">*</span></label>
                    <input type="text" name="nama" value="{{ $f->nama }}" class="field !min-h-10 text-xs font-medium" required autocomplete="off">
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="lbl">Populasi Ayam (Ekor) <span class="text-danger">*</span></label>
                        <input type="number" name="jumlah_ayam" value="{{ $f->jumlah_ayam }}" min="1" max="1000000" class="field !min-h-10 text-xs font-medium" required>
                    </div>
                    <div>
                        <label class="lbl">Umur Ayam Saat Ini (Minggu) <span class="text-danger">*</span></label>
                        <input type="number" name="umur_awal_minggu" value="{{ $f->umurMinggu() }}" min="1" max="120" class="field !min-h-10 text-xs font-medium" required>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="lbl">Jenis Bibit (Strain)</label>
                        <div class="relative inline-block w-full text-left custom-dropdown-container z-30" id="strain-edit-{{ $f->id }}">
                            <input type="hidden" name="strain_ayam" value="{{ $strainEdit }}" class="dropdown-hidden-input">
                            <button type="button"
                                    onclick="toggleCustomDropdown('strain-edit-{{ $f->id }}', event)"
                                    class="dropdown-trigger flex items-center justify-between w-full min-h-10 px-3.5 py-2 bg-white border border-line rounded-lg text-xs font-medium text-ink shadow-2xs hover:border-brand/40 focus:border-brand focus:ring-2 focus:ring-brand/15 transition cursor-pointer text-left">
                                <span class="font-medium text-xs text-ink truncate dropdown-selected-label">{{ $strainEdit }}</span>
                                <span class="material-symbols-outlined text-[16px] text-muted transition-transform duration-200 dropdown-caret shrink-0">expand_more</span>
                            </button>
                            <div class="dropdown-menu hidden absolute left-0 top-full mt-1.5 w-full rounded-xl bg-white border border-line shadow-2xl p-1.5 z-50 select-none custom-dropdown-menu space-y-0.5">
                                @foreach ($daftarStrain as $st)
                                    @php $isStSelected = ($strainEdit === $st); @endphp
                                    <button type="button"
                                            data-city-item
                                            onclick="selectCustomDropdownKotaInput('strain-edit-{{ $f->id }}', '{{ $st }}', '{{ $st }}', '')"
                                            class="w-full flex items-center justify-between gap-2 px-2.5 py-1.5 rounded-lg text-left text-xs transition-all cursor-pointer group {{ $isStSelected ? 'bg-surface font-bold text-brand' : 'hover:bg-surface/80 text-ink font-medium' }}">
                                        <span class="truncate {{ $isStSelected ? 'text-brand font-bold' : 'text-ink font-medium group-hover:text-brand' }}">{{ $st }}</span>
                                        <span data-active-badge class="{{ $isStSelected ? 'inline-block' : 'hidden' }} text-xs font-bold text-brand shrink-0">Dipilih</span>
                                        <span data-choose-hint class="{{ $isStSelected ? 'hidden' : 'inline-block' }} text-[11px] text-muted opacity-0 group-hover:opacity-100 transition-opacity shrink-0">Pilih</span>
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    <div>
                        <label class="lbl">Sentra Lokasi / Kota</label>
                        @php
                            $presetKotaItem = $presetKota[$f->lokasi_kota ?? 'Blitar'] ?? null;
                            $namaKotaLocked = $presetKotaItem['nama'] ?? ($f->lokasi_kota ?? 'Blitar');
                            $provKotaLocked = $presetKotaItem['provinsi'] ?? 'Jawa Timur';
                        @endphp
                        <div class="field !min-h-10 text-xs font-medium text-muted bg-surface border border-line cursor-not-allowed flex items-center justify-between px-3">
                            <span class="truncate">{{ $namaKotaLocked }} ({{ $provKotaLocked }})</span>
                            <span class="material-symbols-outlined text-[14px] text-muted shrink-0">lock</span>
                        </div>
                        <input type="hidden" name="lokasi_kota" value="{{ $f->lokasi_kota }}">
                    </div>
                </div>

                <p class="text-[11px] text-muted flex items-center gap-1.5">
                    <span class="material-symbols-outlined text-[14px] text-brand shrink-0">info</span>
                    <span>Fase saat ini: <strong class="text-brand">{{ $f->faseUmurDetail()['nama'] }} ({{ $f->umurMinggu() }} Minggu)</strong></span>
                </p>

                <div class="flex justify-end items-center gap-2 pt-3 border-t border-line">
                    <button type="button" onclick="tutupModal('modal-edit-kandang-{{ $f->id }}')" class="inline-flex items-center justify-center px-4 py-2 rounded-full text-xs font-semibold text-ink bg-white hover:bg-surface border border-line transition shadow-2xs cursor-pointer">
                        Batal
                    </button>
                    <button type="submit" class="inline-flex items-center justify-center px-5 py-2 rounded-full text-xs font-bold text-white bg-brand hover:bg-brand-dark transition shadow-xs hover:shadow-sm cursor-pointer">
                        Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>
@endforeach

