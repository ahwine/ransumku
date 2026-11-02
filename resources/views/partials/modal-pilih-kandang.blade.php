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
                                    {{ number_format($f->jumlah_ayam, 0, ',', '.') }} Ekor · Fase {{ ucfirst($f->fase) }} · {{ $kotaDisplay }}, {{ $provDisplay }}
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
                                        {{ number_format($f->jumlah_ayam, 0, ',', '.') }} Ekor · Fase {{ ucfirst($f->fase) }} · {{ $kotaDisplay }}, {{ $provDisplay }}
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
<div id="modal-tambah-kandang" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-xs transition-opacity duration-200 hidden opacity-0 pointer-events-none" role="dialog" aria-modal="true">
    <div class="absolute inset-0" onclick="tutupModal('modal-tambah-kandang')"></div>

    <div data-modal-card class="relative z-10 w-full max-w-xl bg-white rounded-2xl p-6 shadow-2xl border border-line transform transition-all duration-200 scale-95 opacity-0 space-y-4 max-h-[92vh] overflow-y-auto">
        <div class="flex items-center justify-between pb-3 border-b border-line">
            <div>
                <h3 class="text-sm md:text-base font-bold text-ink">Input Data Kandang Baru</h3>
                <p class="text-xs text-muted mt-0.5">Lengkapi data unit kandang baru. Standar nutrisi & template disiapkan otomatis.</p>
            </div>
            <button type="button" onclick="tutupModal('modal-tambah-kandang')" class="text-muted hover:text-ink transition cursor-pointer p-1" aria-label="Tutup">
                <span class="material-symbols-outlined text-[20px]">close</span>
            </button>
        </div>

        <form method="POST" action="{{ route('kandang.store') }}" class="space-y-3.5">
            @csrf
            <div>
                <label class="lbl">Nama Kandang <span class="text-danger">*</span></label>
                <input type="text" name="nama" class="field !min-h-10 text-xs font-medium" placeholder="Contoh: Kandang B - Pullet" required autocomplete="off">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="lbl">Populasi Awal (Ekor) <span class="text-danger">*</span></label>
                    <input type="number" name="jumlah_ayam" min="1" max="1000000" class="field !min-h-10 text-xs font-medium" placeholder="Contoh: 500" required>
                </div>
                <div>
                    <label class="lbl">Fase Pemeliharaan <span class="text-danger">*</span></label>
                    @include('partials.dropdown-pilih-fase', ['id' => 'fase-tambah-global', 'value' => old('fase', 'produksi')])
                </div>
            </div>

            <div>
                <label class="lbl">Sentra Lokasi / Kota</label>
                @include('partials.dropdown-pilih-kota', ['id' => 'kota-tambah-global', 'value' => old('lokasi_kota', 'Surabaya'), 'presetKota' => $presetKota])
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 pt-2 border-t border-dashed border-line">
                <div>
                    <label class="lbl">Tgl Chick-in</label>
                    <input type="date" name="tgl_chick_in" value="{{ now()->subWeeks(10)->toDateString() }}" max="{{ today()->toDateString() }}" class="field !min-h-10 text-xs font-medium">
                </div>
                <div>
                    <label class="lbl">Umur Masuk (Mg)</label>
                    <input type="number" name="umur_awal_minggu" value="18" min="1" max="100" class="field !min-h-10 text-xs font-medium" placeholder="Default: 18">
                </div>
                <div>
                    <label class="lbl">Strain Bibit</label>
                    <input type="text" name="strain_ayam" value="Lohmann Brown" class="field !min-h-10 text-xs font-medium" placeholder="Misal: Lohmann Brown">
                </div>
            </div>

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
    <div id="modal-edit-kandang-{{ $f->id }}" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-xs transition-opacity duration-200 hidden opacity-0 pointer-events-none" role="dialog" aria-modal="true">
        <div class="absolute inset-0" onclick="tutupModal('modal-edit-kandang-{{ $f->id }}')"></div>

        <div data-modal-card class="relative z-10 w-full max-w-xl bg-white rounded-2xl p-6 shadow-2xl border border-line transform transition-all duration-200 scale-95 opacity-0 space-y-4 max-h-[92vh] overflow-y-auto">
            <div class="flex items-center justify-between pb-3 border-b border-line">
                <div>
                    <h3 class="text-sm md:text-base font-bold text-ink">Edit Data: {{ $f->nama }}</h3>
                    <p class="text-xs text-muted mt-0.5">Perbarui informasi populasi, fase pemeliharaan, atau sentra lokasi.</p>
                </div>
                <button type="button" onclick="tutupModal('modal-edit-kandang-{{ $f->id }}')" class="text-muted hover:text-ink transition cursor-pointer p-1" aria-label="Tutup">
                    <span class="material-symbols-outlined text-[20px]">close</span>
                </button>
            </div>

            <form method="POST" action="{{ route('kandang.update', $f) }}" class="space-y-3.5">
                @csrf
                @method('PUT')

                <div>
                    <label class="lbl">Nama Kandang <span class="text-danger">*</span></label>
                    <input type="text" name="nama" value="{{ $f->nama }}" class="field !min-h-10 text-xs font-medium" required autocomplete="off">
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="lbl">Populasi (Ekor) <span class="text-danger">*</span></label>
                        <input type="number" name="jumlah_ayam" value="{{ $f->jumlah_ayam }}" min="1" max="1000000" class="field !min-h-10 text-xs font-medium" required>
                    </div>
                    <div>
                        <label class="lbl">Fase Pemeliharaan <span class="text-danger">*</span></label>
                        @include('partials.dropdown-pilih-fase', ['id' => 'fase-edit-global-' . $f->id, 'value' => $f->fase])
                    </div>
                </div>

                <div>
                    <label class="lbl">Sentra Lokasi / Kota</label>
                    @include('partials.dropdown-pilih-kota', ['id' => 'kota-edit-global-' . $f->id, 'value' => $f->lokasi_kota ?? 'Surabaya', 'presetKota' => $presetKota])
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 pt-2 border-t border-dashed border-line">
                    <div>
                        <label class="lbl">Tgl Chick-in</label>
                        <input type="date" name="tgl_chick_in" value="{{ $f->tgl_chick_in?->toDateString() }}" max="{{ today()->toDateString() }}" class="field !min-h-10 text-xs font-medium">
                    </div>
                    <div>
                        <label class="lbl">Umur Masuk (Mg)</label>
                        <input type="number" name="umur_awal_minggu" value="{{ $f->umur_awal_minggu ?? 18 }}" min="1" max="100" class="field !min-h-10 text-xs font-medium">
                    </div>
                    <div>
                        <label class="lbl">Strain Bibit</label>
                        <input type="text" name="strain_ayam" value="{{ $f->strain_ayam ?? 'Lohmann Brown' }}" class="field !min-h-10 text-xs font-medium">
                    </div>
                </div>

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

