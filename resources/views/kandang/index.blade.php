@extends('layouts.app')
@section('judul', 'Kelola Kandang')
@section('isi')

<div class="space-y-5">
    {{-- Header Halaman Bersih & Humanis --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-lg sm:text-xl font-bold text-ink tracking-tight">Kelola Kandang</h1>
            <p class="text-xs text-muted mt-0.5">Pusat kendali multi-kandang peternakan. Atur populasi ayam, fase pemeliharaan, dan beralih kandang aktif.</p>
        </div>

        <button id="btn-tambah-kandang-top" type="button" onclick="bukaModal('modal-tambah-kandang')" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-full bg-brand hover:bg-brand-dark text-white text-xs font-bold shadow-2xs hover:shadow-xs transition active:scale-95 cursor-pointer">
            <span class="material-symbols-outlined text-[17px]">add</span>
            <span>Tambah Kandang</span>
        </button>
    </div>

    {{-- Baris Ringkasan KPI Multi-Kandang (Clean & Minimalis) --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3.5">
        {{-- Kartu 1: Total Unit Kandang --}}
        <div class="card p-4 flex flex-col justify-between space-y-1.5 shadow-2xs hover:shadow-xs transition">
            <span class="text-xs font-bold text-muted uppercase tracking-wider">Unit Kandang</span>
            <div>
                <div class="text-xl sm:text-2xl font-black text-ink">
                    {{ $farms->count() }} <span class="text-xs font-normal text-muted">Kandang Terdaftar</span>
                </div>
                <p class="text-[11px] text-muted mt-0.5">Unit peternakan yang terhubung ke akun</p>
            </div>
        </div>

        {{-- Kartu 2: Total Populasi Terkelola --}}
        <div class="card p-4 flex flex-col justify-between space-y-1.5 shadow-2xs hover:shadow-xs transition">
            <span class="text-xs font-bold text-muted uppercase tracking-wider">Populasi Terkelola</span>
            <div>
                <div class="text-xl sm:text-2xl font-black text-ink">
                    {{ number_format($farms->sum('jumlah_ayam'), 0, ',', '.') }} <span class="text-xs font-normal text-muted">Ekor</span>
                </div>
                <p class="text-[11px] text-muted mt-0.5">Akumulasi populasi ayam dari seluruh kandang</p>
            </div>
        </div>

        {{-- Kartu 3: Kandang Aktif Saat Ini --}}
        <div class="card p-4 flex flex-col justify-between space-y-1.5 shadow-2xs hover:shadow-xs transition">
            <div class="flex items-center justify-between gap-2">
                <span class="text-xs font-bold text-muted uppercase tracking-wider">Kandang Aktif</span>
                <span class="inline-flex items-center text-[11px] font-semibold text-emerald-700 bg-emerald-50 border border-emerald-200 px-2.5 py-0.5 rounded-full">
                    Aktif
                </span>
            </div>
            <div>
                <div class="text-base sm:text-lg font-bold text-ink truncate" title="{{ $activeFarm->nama ?? '-' }}">
                    {{ $activeFarm->nama ?? '-' }}
                </div>
                <p class="text-[11px] text-muted mt-0.5 truncate">
                    Sentra: <span class="text-ink font-medium">{{ $activeFarm->lokasi_kota ?? 'Surabaya' }}</span> · {{ $activeFarm ? $activeFarm->faseUmurDetail()['nama'] : '-' }}
                </p>
            </div>
        </div>
    </div>

    {{-- Daftar Kandang Terdaftar --}}
    <div class="space-y-3.5">
        {{-- Subheader Bersih: Redundansi Total Populasi Dihapus --}}
        <div class="flex items-center justify-between pb-1 border-b border-line/60">
            <span class="text-xs font-bold text-muted uppercase tracking-wider">
                Kandang Terdaftar ({{ $farms->count() }})
            </span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 {{ $farms->count() >= 3 ? 'lg:grid-cols-3' : '' }} gap-4">
            @foreach ($farms as $f)
                @php $isActive = ($activeFarm && $activeFarm->id === $f->id); @endphp
                {{-- Card Kandang: Warna Putih Bersih, Proporsional & Rapi --}}
                <div class="bg-white rounded-2xl border {{ $isActive ? 'border-brand/40 shadow-xs' : 'border-line shadow-2xs' }} hover:shadow-xs transition p-4 sm:p-5 flex flex-col justify-between space-y-3">
                    <div class="space-y-3">
                        {{-- Baris Atas Card: Nama Kandang & Status Aktif --}}
                        <div class="flex items-start justify-between gap-2">
                            <div class="min-w-0">
                                <h2 class="text-base font-bold text-ink tracking-tight truncate">
                                    {{ $f->nama }}
                                </h2>
                                <p class="text-xs text-muted mt-0.5">
                                    {{ $f->logs_count }} catatan harian · {{ $f->rations_count }} formula ransum
                                </p>
                            </div>

                            @if ($isActive)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200 shrink-0">
                                    Kandang Aktif
                                </span>
                            @else
                                <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-surface text-muted border border-line shrink-0">
                                    Non-Aktif
                                </span>
                            @endif
                        </div>

                        {{-- Grid Metrik Terstruktur (Divided Metrics) --}}
                        <div class="grid grid-cols-3 divide-x divide-line rounded-lg border border-line/60 bg-surface/30 py-2 text-center">
                            <div class="px-1.5">
                                <span class="text-[10px] font-semibold text-muted uppercase tracking-wider block">Populasi</span>
                                <span class="text-xs sm:text-sm font-bold text-ink mt-0.5 block truncate">
                                    {{ number_format($f->jumlah_ayam, 0, ',', '.') }} Ekor
                                </span>
                            </div>
                            <div class="px-1.5">
                                <span class="text-[10px] font-semibold text-muted uppercase tracking-wider block">Umur Minggu</span>
                                <span class="text-xs sm:text-sm font-bold text-brand mt-0.5 block">
                                    Mg ke-{{ $f->umurMinggu() }}
                                </span>
                            </div>
                            <div class="px-1.5">
                                <span class="text-[10px] font-semibold text-muted uppercase tracking-wider block">Sentra</span>
                                <span class="text-xs sm:text-sm font-bold text-ink mt-0.5 block truncate" title="{{ $f->lokasi_kota ?? 'Surabaya' }}">
                                    {{ $f->lokasi_kota ?? 'Surabaya' }}
                                </span>
                            </div>
                        </div>

                        {{-- Metadata Ringkas: Fase & Bibit Ayam (Pill Badges) --}}
                        <div class="flex flex-wrap items-center justify-between gap-2 text-xs">
                            <div class="flex items-center gap-1.5 flex-wrap">
                                <span class="px-3 py-0.5 rounded-full text-[11px] font-semibold bg-surface border border-line text-ink">
                                    {{ $f->faseUmurDetail()['nama'] }}
                                </span>
                                <span class="px-3 py-0.5 rounded-full text-[11px] font-medium bg-surface border border-line text-muted">
                                    {{ $f->strain_ayam ?? 'Lohmann Brown' }}
                                </span>
                            </div>
                            @if ($f->tgl_chick_in)
                                <span class="text-[11px] text-muted">
                                    Masuk: {{ $f->tgl_chick_in->isoFormat('D MMM Y') }}
                                </span>
                            @endif
                        </div>
                    </div>

                    {{-- Card Footer / Action Buttons --}}
                    <div class="pt-2.5 border-t border-line flex items-center justify-between gap-2">
                        <div class="flex items-center gap-1.5">
                            {{-- Tombol Buka Modal Edit (Bentuk Pill Badge) --}}
                            <button id="btn-edit-kandang-{{ $f->id }}" type="button" onclick="bukaModal('modal-edit-kandang-{{ $f->id }}')" class="inline-flex items-center gap-1 px-3.5 py-1.5 rounded-full text-xs font-semibold text-ink bg-surface hover:bg-surface-2 border border-line transition cursor-pointer shadow-2xs hover:shadow-xs" title="Edit Data Kandang">
                                <span class="material-symbols-outlined text-[15px] text-muted">edit</span>
                                <span>Edit</span>
                            </button>

                            {{-- Tombol Hapus (Bentuk Pill Badge jika kandang > 1) --}}
                            @if ($farms->count() > 1)
                                <form method="POST" action="{{ route('kandang.destroy', $f) }}" data-confirm="Apakah Anda yakin ingin menghapus kandang '{{ $f->nama }}'?" data-confirm-title="Hapus Kandang" data-confirm-sub="Seluruh rekaman catatan dan formula pakan di kandang ini akan terhapus permanen." data-confirm-btn="Ya, Hapus Kandang">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="inline-flex items-center gap-1 px-3 py-1.5 rounded-full text-xs font-semibold text-rose-600 hover:text-rose-700 bg-rose-50/60 hover:bg-rose-100/70 border border-rose-200/70 transition cursor-pointer shadow-2xs" title="Hapus Kandang">
                                        <span class="material-symbols-outlined text-[15px]">delete</span>
                                        <span>Hapus</span>
                                    </button>
                                </form>
                            @endif
                        </div>

                        <div>
                            @if ($isActive)
                                <a href="{{ route('beranda') }}" class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full text-xs font-bold text-white bg-brand hover:bg-brand-dark transition shadow-2xs">
                                    <span>Buka Dashboard</span>
                                    <span class="material-symbols-outlined text-[15px]">arrow_forward</span>
                                </a>
                            @else
                                <form method="POST" action="{{ route('kandang.switch') }}">
                                    @csrf
                                    <input type="hidden" name="farm_id" value="{{ $f->id }}">
                                    <button type="submit" class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full text-xs font-bold text-ink bg-surface hover:bg-brand hover:text-white border border-line transition shadow-2xs cursor-pointer">
                                        <span class="material-symbols-outlined text-[15px]">swap_horiz</span>
                                        <span>Pilih Kandang</span>
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>

{{-- MODAL TAMBAH KANDANG BARU --}}
<div id="modal-tambah-kandang" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-xs transition-opacity duration-200 hidden opacity-0 pointer-events-none" role="dialog" aria-modal="true">
    <div class="absolute inset-0" onclick="tutupModal('modal-tambah-kandang')"></div>

    <div data-modal-card class="relative z-10 w-full max-w-xl bg-white rounded-2xl p-6 shadow-2xl border border-line transform transition-all duration-200 scale-95 opacity-0 space-y-4 max-h-[92vh] overflow-y-auto">
        {{-- Header Modal Bersih (Ikon Silang Tanpa Badge) --}}
        <div class="flex items-center justify-between pb-3 border-b border-line">
            <div>
                <h3 class="text-sm md:text-base font-bold text-ink">Input Data Kandang Baru</h3>
                <p class="text-xs text-muted mt-0.5">Lengkapi data unit kandang baru. Standar nutrisi & template disiapkan otomatis.</p>
            </div>
            <button type="button" onclick="tutupModal('modal-tambah-kandang')" class="text-muted hover:text-ink transition cursor-pointer p-1" aria-label="Tutup">
                <span class="material-symbols-outlined text-[20px]">close</span>
            </button>
        </div>

        {{-- Form Tambah Kandang --}}
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
                    @include('partials.dropdown-pilih-fase', ['id' => 'fase-tambah', 'value' => old('fase', 'produksi')])
                </div>
            </div>

            <div>
                <label class="lbl">Sentra Lokasi / Kota</label>
                @include('partials.dropdown-pilih-kota', ['id' => 'kota-tambah', 'value' => old('lokasi_kota', 'Surabaya'), 'presetKota' => $presetKota])
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

            {{-- Tombol Batal & Simpan Berbentuk Pill Badge --}}
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

{{-- MODAL EDIT KANDANG (DEDICATED MODAL PER FARM) --}}
@foreach ($farms as $f)
    <div id="modal-edit-kandang-{{ $f->id }}" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-xs transition-opacity duration-200 hidden opacity-0 pointer-events-none" role="dialog" aria-modal="true">
        <div class="absolute inset-0" onclick="tutupModal('modal-edit-kandang-{{ $f->id }}')"></div>

        <div data-modal-card class="relative z-10 w-full max-w-xl bg-white rounded-2xl p-6 shadow-2xl border border-line transform transition-all duration-200 scale-95 opacity-0 space-y-4 max-h-[92vh] overflow-y-auto">
            {{-- Header Modal Bersih (Ikon Silang Tanpa Badge) --}}
            <div class="flex items-center justify-between pb-3 border-b border-line">
                <div>
                    <h3 class="text-sm md:text-base font-bold text-ink">Edit Data: {{ $f->nama }}</h3>
                    <p class="text-xs text-muted mt-0.5">Perbarui informasi populasi, fase pemeliharaan, atau sentra lokasi.</p>
                </div>
                <button type="button" onclick="tutupModal('modal-edit-kandang-{{ $f->id }}')" class="text-muted hover:text-ink transition cursor-pointer p-1" aria-label="Tutup">
                    <span class="material-symbols-outlined text-[20px]">close</span>
                </button>
            </div>

            {{-- Form Edit Kandang --}}
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
                        @include('partials.dropdown-pilih-fase', ['id' => 'fase-edit-' . $f->id, 'value' => $f->fase])
                    </div>
                </div>

                <div>
                    <label class="lbl">Sentra Lokasi / Kota</label>
                    @include('partials.dropdown-pilih-kota', ['id' => 'kota-edit-' . $f->id, 'value' => $f->lokasi_kota ?? 'Surabaya', 'presetKota' => $presetKota])
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

                {{-- Tombol Batal & Simpan Berbentuk Pill Badge --}}
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

<script>
    // Backwards-compatible aliases for opening modals
    function toggleFormTambah() {
        if (typeof window.bukaModal === 'function') {
            window.bukaModal('modal-tambah-kandang');
        }
    }

    function toggleEditKandang(id) {
        if (typeof window.bukaModal === 'function') {
            window.bukaModal('modal-edit-kandang-' + id);
        }
    }
</script>

@endsection
