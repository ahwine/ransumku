@extends('layouts.app')
@section('judul', 'Stok Pakan Gudang')
@section('isi')
@use('App\Support\Fmt')

<div class="space-y-4">
    {{-- Header Halaman --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-lg sm:text-xl font-bold text-ink tracking-tight">Stok Pakan Gudang</h1>
            <p class="text-xs text-muted mt-0.5">Kelola persediaan fisik bahan baku pakan dan harga satuan di gudang kandang aktif.</p>
        </div>

        {{-- Tombol Aksi Utama: Tambah Bahan ke Gudang --}}
        <div>
            <button type="button" 
                    onclick="bukaModal('modalTambahBahan')"
                    class="inline-flex items-center gap-1.5 px-4 py-2 rounded-full text-xs font-bold text-white bg-brand hover:bg-brand-dark transition shadow-2xs hover:shadow-xs active:scale-95 cursor-pointer">
                <span class="material-symbols-outlined text-[17px]">add</span>
                <span>Tambah Bahan ke Gudang</span>
            </button>
        </div>
    </div>

    {{-- Pencarian Cepat Live --}}
    <div class="relative flex items-center">
        <span class="material-symbols-outlined absolute left-4 text-[18px] text-muted pointer-events-none">search</span>
        <input type="text" 
               id="search-stok-live"
               oninput="filterBahanStok(this)"
               placeholder="Cari bahan di gudang (misal: Jagung, Dedak, Konsentrat, Kapur)..." 
               class="w-full pl-11 pr-10 py-2.5 bg-white border border-line rounded-xl text-xs sm:text-sm font-medium text-ink placeholder:text-muted focus:outline-none focus:ring-2 focus:ring-brand/15 focus:border-brand shadow-2xs transition"
               autocomplete="off">
    </div>

    {{-- Grid Kartu Bahan Stok Gudang --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        @forelse ($stokGudangData as $d)
            @php
                $rawNama = $d['fi']->ingredient->nama ?? '';
                $catEntry = $masterCatalog[$rawNama] ?? null;
                $kategoriLabel = $catEntry['kategori'] ?? 'Bahan Baku Pakan';
                $estimasiSak = $d['stok_kg'] > 0 ? round($d['stok_kg'] / 50, 1) : 0;
            @endphp
            <div class="bg-white rounded-xl border border-line p-4 sm:p-5 shadow-2xs hover:shadow-xs transition flex flex-col justify-between space-y-3.5" 
                 data-stok-card 
                 data-stok-nama="{{ strtolower($rawNama) }}">
                
                <div class="space-y-3">
                    {{-- Baris Atas: Nama Bahan & Status Teks Bersih --}}
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <h2 class="text-base font-bold text-ink tracking-tight truncate">
                                {{ $rawNama }}
                            </h2>
                            <span class="text-xs text-muted block mt-0.5">
                                {{ $kategoriLabel }}
                            </span>
                        </div>

                        {{-- Status Teks Bersih Tanpa Pill Badge --}}
                        <span class="text-xs font-bold shrink-0 {{ $d['text_class'] }}">
                            {{ $d['status_label'] }}
                        </span>
                    </div>

                    {{-- 2 Kolom Data Faktual: Stok di Gudang & Harga Satuan --}}
                    <div class="grid grid-cols-2 divide-x divide-line rounded-lg border border-line/70 bg-[#f8fbf9] py-2.5 text-center">
                        <div class="px-2">
                            <span class="text-[10px] font-semibold text-muted uppercase tracking-wider block">Stok di Gudang</span>
                            <span class="text-sm font-bold text-ink mt-0.5 block tabular-nums">
                                {{ Fmt::num($d['stok_kg'], 1) }} <span class="text-xs font-normal text-muted">kg</span>
                                @if ($d['stok_kg'] >= 50)
                                    <span class="text-[11px] font-normal text-muted">({{ $estimasiSak }} sak)</span>
                                @endif
                            </span>
                        </div>
                        <div class="px-2">
                            <span class="text-[10px] font-semibold text-muted uppercase tracking-wider block">Harga Satuan</span>
                            <span class="text-sm font-bold text-ink mt-0.5 block truncate tabular-nums">
                                {{ Fmt::rp($d['harga']) }} <span class="text-xs font-normal text-muted">/kg</span>
                            </span>
                        </div>
                    </div>

                    {{-- Ringkasan Nutrisi Baku (Termasuk BK) --}}
                    @if ($d['fi']->ingredient)
                        <div class="p-2.5 rounded-lg bg-surface/60 border border-line/60 flex flex-wrap items-center justify-between gap-2 text-[11px] text-muted">
                            <span>Kandungan Nutrisi:</span>
                            <div class="flex items-center gap-2 font-medium text-ink">
                                <span>BK: <strong>{{ Fmt::num($d['fi']->ingredient->bk ?? 88, 0) }}%</strong></span>
                                <span>•</span>
                                <span>PK: <strong>{{ Fmt::num($d['fi']->ingredient->protein, 1) }}%</strong></span>
                                <span>•</span>
                                <span>EM: <strong>{{ number_format($d['fi']->ingredient->energi, 0, ',', '.') }} kkal</strong></span>
                                <span>•</span>
                                <span>Ca: <strong>{{ Fmt::num($d['fi']->ingredient->kalsium, 2) }}%</strong></span>
                            </div>
                        </div>
                    @endif
                </div>

                {{-- Footer Aksi CRUD --}}
                <div class="pt-3 border-t border-line flex items-center justify-between gap-2">
                    <span class="text-[11px] text-muted truncate">
                        {{ $d['fi']->harga_updated_at ? 'Diperbarui '.$d['fi']->harga_updated_at->diffForHumans() : 'Harga acuan baku' }}
                    </span>
                    
                    <div class="flex items-center gap-2 shrink-0">
                        {{-- Tombol Edit / Update Stok --}}
                        <button type="button" 
                                onclick="bukaModalUpdateStok({{ $d['fi']->id }}, '{{ addslashes($rawNama) }}', {{ (float)$d['stok_kg'] }}, {{ (int)$d['harga'] }})"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold text-ink bg-white hover:bg-surface border border-line transition cursor-pointer shadow-2xs active:scale-95"
                                title="Perbarui stok atau harga satuan">
                            <span class="material-symbols-outlined text-[15px] text-muted">edit</span>
                            <span>Edit</span>
                        </button>

                        {{-- Tombol Hapus dari Gudang --}}
                        <form method="POST" 
                              action="{{ route('bahan.gudang.destroy', $d['fi']) }}"
                              data-confirm="Hapus {{ $rawNama }} dari gudang pakan Anda? Bahan ini tidak akan muncul lagi di peracik ransum."
                              data-confirm-title="Hapus Bahan dari Gudang"
                              data-confirm-btn="Ya, Hapus Bahan">
                            @csrf
                            @method('DELETE')
                            <button type="submit" 
                                    class="inline-flex items-center gap-1 px-3 py-1.5 rounded-full text-xs font-semibold text-rose-600 bg-white hover:bg-rose-50 border border-rose-200 transition cursor-pointer shadow-2xs active:scale-95"
                                    title="Hapus bahan dari gudang">
                                <span class="material-symbols-outlined text-[15px]">delete</span>
                                <span>Hapus</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-full bg-white rounded-2xl border border-line p-8 sm:p-12 text-center space-y-3">
                <span class="material-symbols-outlined text-[40px] text-muted block">inventory_2</span>
                <div class="space-y-1">
                    <h3 class="text-base font-bold text-ink">Gudang Pakan Masih Kosong</h3>
                    <p class="text-xs text-muted max-w-md mx-auto leading-relaxed">
                        Belum ada bahan pakan yang terdaftar di gudang kandang ini. Tambahkan bahan pakan yang Anda miliki (seperti Jagung, Dedak, atau Konsentrat) untuk mulai memantau stok dan meracik ransum.
                    </p>
                </div>
                <button type="button" 
                        onclick="bukaModal('modalTambahBahan')"
                        class="inline-flex items-center gap-1.5 px-5 py-2.5 rounded-full text-xs font-bold text-white bg-brand hover:bg-brand-dark transition shadow-2xs active:scale-95 cursor-pointer">
                    <span class="material-symbols-outlined text-[18px]">add</span>
                    <span>Tambah Bahan Pertama ke Gudang</span>
                </button>
            </div>
        @endforelse

        {{-- Empty Search Filter --}}
        <div id="stok-empty-filter" class="hidden col-span-full bg-white rounded-xl border border-line text-center py-10 text-muted space-y-1">
            <span class="material-symbols-outlined text-[36px] text-muted/50 mb-1 block">search_off</span>
            <p class="font-bold text-ink text-sm">Bahan Gudang Tidak Ditemukan</p>
            <p class="text-xs text-muted">Tidak ada bahan pakan di gudang yang sesuai dengan kata kunci pencarian Anda.</p>
        </div>
    </div>
</div>

{{-- ======================================================== --}}
{{-- 1. MODAL TAMBAH BAHAN KE GUDANG (CENTERED & UI DROPDOWN) --}}
{{-- ======================================================== --}}
<div id="modalTambahBahan" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-xs transition-opacity duration-200 hidden opacity-0 pointer-events-none" role="dialog" aria-modal="true">
    <div class="absolute inset-0" onclick="tutupModal('modalTambahBahan')"></div>
    <div data-modal-card class="relative z-10 w-full max-w-lg bg-white rounded-2xl p-5 sm:p-6 shadow-2xl border border-line transform transition-all duration-200 scale-95 opacity-0 space-y-4 max-h-[92vh] overflow-y-auto">
        {{-- Header Modal Bersih Tanpa Ikon Badge --}}
        <div class="flex items-center justify-between pb-3 border-b border-line">
            <div>
                <h3 class="text-sm sm:text-base font-bold text-ink">Tambah Bahan ke Gudang</h3>
                <p class="text-xs text-muted">Pilih bahan pakan resmi, kandungan nutrisi terisi otomatis.</p>
            </div>
            <button type="button" onclick="tutupModal('modalTambahBahan')" class="p-1.5 rounded-lg text-muted hover:text-ink hover:bg-surface cursor-pointer">
                <span class="material-symbols-outlined text-[18px]">close</span>
            </button>
        </div>

        <form method="POST" action="{{ route('bahan.store') }}" class="space-y-4">
            @csrf

            {{-- 1. Custom UI Dropdown Bahan Pakan --}}
            <div class="space-y-1.5">
                <label class="lbl text-xs font-bold" for="input-nama-template">
                    <span>Pilih Bahan Pakan Ayam Petelur</span>
                    <span class="text-danger">*</span>
                </label>

                <div class="relative w-full text-left custom-dropdown-container z-30" id="dropdown-master-bahan">
                    <input type="hidden" name="nama_template" id="input-nama-template" value="" class="dropdown-hidden-input" required>

                    {{-- Trigger Button UI --}}
                    <button type="button" 
                            onclick="toggleCustomDropdown('dropdown-master-bahan', event)" 
                            class="dropdown-trigger w-full min-h-11 px-3.5 py-2.5 bg-white border border-line rounded-xl text-xs sm:text-sm font-semibold text-ink shadow-2xs hover:border-brand/40 focus:border-brand focus:ring-2 focus:ring-brand/15 transition cursor-pointer flex items-center justify-between"
                            aria-haspopup="true"
                            aria-expanded="false">
                        <div class="flex items-center gap-2.5 min-w-0 pr-1">
                            <span class="material-symbols-outlined text-[18px] text-muted dropdown-selected-icon">inventory_2</span>
                            <span class="truncate dropdown-selected-label text-muted" id="label-selected-bahan">Pilih Bahan Pakan...</span>
                        </div>
                        <span class="material-symbols-outlined text-[18px] text-muted transition-transform duration-200 dropdown-caret shrink-0">expand_more</span>
                    </button>

                    {{-- Dropdown Card Menu --}}
                    <div class="dropdown-menu hidden absolute left-0 right-0 top-full mt-1.5 rounded-2xl bg-white border border-line shadow-2xl p-2 z-50 select-none custom-dropdown-menu flex flex-col">
                        {{-- Search Input inside Dropdown --}}
                        <div class="p-1.5 border-b border-line/60">
                            <div class="relative flex items-center">
                                <span class="material-symbols-outlined absolute left-2.5 text-[16px] text-muted pointer-events-none">search</span>
                                <input type="text"
                                       data-dropdown-search
                                       oninput="filterMasterBahanDropdown(this)"
                                       placeholder="Cari bahan (mis: Jagung, Dedak)..."
                                       class="w-full pl-8 pr-3 py-1.5 bg-surface/60 border border-line rounded-lg text-xs text-ink placeholder:text-muted focus:outline-none focus:ring-1 focus:ring-brand focus:border-brand transition"
                                       autocomplete="off">
                            </div>
                        </div>

                        {{-- List Pilihan Bahan Pakan (Bahan yang belum di gudang tampil di atas, yang sudah di gudang terkunci) --}}
                        <div class="overflow-y-auto space-y-1 p-1 max-h-56" id="list-master-bahan">
                            @php
                                $sortedCatalog = collect($masterCatalog)->sortBy(fn ($item) => in_array($item['nama'], $existingNames, true) ? 1 : 0);
                            @endphp
                            @foreach ($sortedCatalog as $key => $item)
                                @php $alreadyIn = in_array($item['nama'], $existingNames, true); @endphp
                                <button type="button"
                                        data-master-item
                                        data-key="{{ $key }}"
                                        data-nama="{{ strtolower($item['label'] ?? $item['nama']) }}"
                                        data-label="{{ $item['label'] ?? $item['nama'] }}"
                                        data-kategori="{{ $item['kategori'] ?? '' }}"
                                        data-protein="{{ $item['protein'] ?? 0 }}"
                                        data-energi="{{ $item['energi'] ?? 0 }}"
                                        data-kalsium="{{ $item['kalsium'] ?? 0 }}"
                                        data-fosfor="{{ $item['fosfor'] ?? 0 }}"
                                        data-serat="{{ $item['serat'] ?? 0 }}"
                                        data-harga="{{ $item['harga_default'] ?? 0 }}"
                                        data-deskripsi="{{ $item['deskripsi'] ?? $item['catatan_keamanan'] ?? '' }}"
                                        @if ($alreadyIn)
                                            disabled
                                            title="Bahan ini sudah ada di gudang Anda"
                                        @else
                                            onclick="pilihMasterBahanUi(this)"
                                        @endif
                                        class="w-full flex items-center justify-between px-3 py-2 rounded-xl text-left text-xs transition-all {{ $alreadyIn ? 'opacity-45 bg-surface/50 cursor-not-allowed select-none' : 'cursor-pointer group hover:bg-surface' }}">
                                    <div class="min-w-0 pr-2">
                                        <span class="font-bold text-xs {{ $alreadyIn ? 'text-muted' : 'text-ink group-hover:text-brand' }} block truncate">
                                            {{ $item['label'] ?? $item['nama'] }}
                                        </span>
                                        <span class="text-[10px] text-muted block truncate">
                                            BK {{ $item['bk'] ?? 88 }}% • PK {{ $item['protein'] }}% • EM {{ $item['energi'] }} kkal
                                        </span>
                                    </div>
                                    @if ($alreadyIn)
                                        <span class="text-[10px] font-semibold text-muted shrink-0">
                                            Sudah di Gudang
                                        </span>
                                    @else
                                        <span class="text-[10px] font-bold text-brand opacity-0 group-hover:opacity-100 transition-opacity shrink-0">
                                            Pilih
                                        </span>
                                    @endif
                                </button>
                            @endforeach
                            <div id="master-item-empty" class="hidden p-4 text-center text-xs text-muted">
                                Tidak ada bahan pakan yang cocok.
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Preview Nutrisi Otomatis Terkunci (Live JS) --}}
            <div id="preview-nutrisi-box" class="hidden p-3.5 rounded-xl bg-surface/60 border border-line space-y-2">
                <div class="flex items-center justify-between gap-2">
                    <span id="preview-kategori" class="text-xs font-bold text-ink">
                        Kategori
                    </span>
                    <span class="text-[11px] font-medium text-muted">
                        Sumber: Hartadi dkk. (UGM Press) &amp; NRC (1994)
                    </span>
                </div>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 text-center text-xs pt-1">
                    <div class="p-1.5 rounded-lg bg-white border border-line/70">
                        <span class="text-[10px] text-muted block">Protein (PK)</span>
                        <strong id="preview-protein" class="text-xs font-bold text-ink">0%</strong>
                    </div>
                    <div class="p-1.5 rounded-lg bg-white border border-line/70">
                        <span class="text-[10px] text-muted block">Energi (EM)</span>
                        <strong id="preview-energi" class="text-xs font-bold text-ink">0 kkal</strong>
                    </div>
                    <div class="p-1.5 rounded-lg bg-white border border-line/70">
                        <span class="text-[10px] text-muted block">Kalsium (Ca)</span>
                        <strong id="preview-kalsium" class="text-xs font-bold text-ink">0%</strong>
                    </div>
                    <div class="p-1.5 rounded-lg bg-white border border-line/70">
                        <span class="text-[10px] text-muted block">Fosfor (P)</span>
                        <strong id="preview-fosfor" class="text-xs font-bold text-ink">0%</strong>
                    </div>
                </div>
                <p id="preview-deskripsi" class="text-[11px] text-muted pt-0.5"></p>
            </div>

            {{-- 2. Input Jumlah Stok (Kg) --}}
            <div>
                <label class="lbl text-xs font-bold" for="input-tambah-stok">
                    <span>Stok Fisik di Gudang</span>
                    <span class="text-danger">*</span>
                </label>
                <div class="relative flex items-center">
                    <input type="number" 
                           id="input-tambah-stok" 
                           name="stok_kg" 
                           min="0" 
                           step="0.1" 
                           placeholder="Contoh: 50" 
                           class="field font-semibold text-ink text-sm !pr-10" 
                           required>
                    <span class="absolute right-3.5 text-xs font-bold text-muted pointer-events-none select-none">kg</span>
                </div>
                <p class="text-[11px] text-muted mt-1">Petunjuk: 1 karung sak = ~50 kg.</p>
            </div>

            {{-- 3. Input Harga Satuan (Rp/kg) --}}
            <div>
                <label class="lbl text-xs font-bold" for="input-tambah-harga">
                    <span>Harga Satuan (per kg)</span>
                    <span class="text-danger">*</span>
                </label>
                <div class="relative flex items-center">
                    <span class="absolute left-3.5 text-xs font-bold text-muted pointer-events-none select-none">Rp</span>
                    <input type="number" 
                           id="input-tambah-harga" 
                           name="harga" 
                           min="0" 
                           step="50" 
                           placeholder="Contoh: 6500" 
                           class="field !pl-10 font-semibold text-ink text-sm" 
                           required>
                    <span class="absolute right-3.5 text-xs font-normal text-muted pointer-events-none select-none">/ kg</span>
                </div>
                <p class="text-[11px] text-muted mt-1">Harga bahan baku per kilogram untuk perhitungan biaya ransum.</p>
            </div>

            {{-- Tombol Aksi Modal --}}
            <div class="flex items-center justify-end gap-2 pt-3 border-t border-line">
                <button type="button" 
                        onclick="tutupModal('modalTambahBahan')" 
                        class="px-4 py-2 rounded-full text-xs font-semibold text-ink bg-white hover:bg-surface border border-line transition shadow-2xs cursor-pointer">
                    Batal
                </button>
                <button type="submit" 
                        class="px-5 py-2 rounded-full text-xs font-bold text-white bg-brand hover:bg-brand-dark transition shadow-xs hover:shadow-sm cursor-pointer">
                    Tambahkan ke Gudang
                </button>
            </div>
        </form>
    </div>
</div>

{{-- ======================================================== --}}
{{-- 2. MODAL EDIT / UPDATE STOK & HARGA GUDANG (CENTERED)    --}}
{{-- ======================================================== --}}
<div id="modalUpdateStok" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-xs transition-opacity duration-200 hidden opacity-0 pointer-events-none" role="dialog" aria-modal="true">
    <div class="absolute inset-0" onclick="tutupModal('modalUpdateStok')"></div>
    <div data-modal-card class="relative z-10 w-full max-w-md bg-white rounded-2xl p-5 sm:p-6 shadow-2xl border border-line transform transition-all duration-200 scale-95 opacity-0 space-y-4 max-h-[92vh] overflow-y-auto">
        <div class="flex items-center justify-between pb-3 border-b border-line">
            <div>
                <h3 class="text-sm sm:text-base font-bold text-ink">Edit Stok & Harga Satuan</h3>
                <p class="text-xs text-muted" id="modal-update-nama">Bahan Pakan</p>
            </div>
            <button type="button" onclick="tutupModal('modalUpdateStok')" class="p-1 rounded-lg text-muted hover:text-ink hover:bg-surface cursor-pointer">
                <span class="material-symbols-outlined text-[18px]">close</span>
            </button>
        </div>

        <form method="POST" action="{{ route('bahan.stok.update') }}" class="space-y-4">
            @csrf
            <input type="hidden" id="modal-update-id" name="farm_ingredient_id" value="">

            <div>
                <label class="lbl text-xs font-bold" for="modal-input-stok">Stok Fisik di Gudang</label>
                <div class="relative flex items-center">
                    <input type="number" 
                           id="modal-input-stok" 
                           name="stok_kg" 
                           min="0" 
                           step="0.1" 
                           inputmode="decimal" 
                           class="field font-semibold text-ink text-sm !pr-10" 
                           required>
                    <span class="absolute right-3.5 text-xs font-bold text-muted pointer-events-none select-none">kg</span>
                </div>
                <p class="text-[11px] text-muted mt-1">Perbarui jumlah kilogram persediaan nyata di gudang.</p>
            </div>

            <div>
                <label class="lbl text-xs font-bold" for="modal-input-harga">Harga Satuan (per kg)</label>
                <div class="relative flex items-center">
                    <span class="absolute left-3.5 text-xs font-bold text-muted pointer-events-none select-none">Rp</span>
                    <input type="number" 
                           id="modal-input-harga" 
                           name="harga" 
                           min="0" 
                           step="50" 
                           inputmode="numeric" 
                           class="field !pl-10 font-semibold text-ink text-sm">
                    <span class="absolute right-3.5 text-xs font-normal text-muted pointer-events-none select-none">/ kg</span>
                </div>
                <p class="text-[11px] text-muted mt-1">Harga bahan baku per kilogram untuk perhitungan biaya ransum.</p>
            </div>

            <div class="flex items-center justify-end gap-2 pt-2 border-t border-line">
                <button type="button" 
                        onclick="tutupModal('modalUpdateStok')" 
                        class="px-4 py-2 rounded-full text-xs font-semibold text-ink bg-white hover:bg-surface border border-line transition shadow-2xs cursor-pointer">
                    Batal
                </button>
                <button type="submit" 
                        class="px-5 py-2 rounded-full text-xs font-bold text-white bg-brand hover:bg-brand-dark transition shadow-xs hover:shadow-sm cursor-pointer">
                    Simpan Perubahan
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function pilihMasterBahanUi(btn) {
    const key = btn.getAttribute('data-key');
    const label = btn.getAttribute('data-label');
    const kategori = btn.getAttribute('data-kategori') || 'Bahan Pakan';
    const protein = btn.getAttribute('data-protein') || '0';
    const energi = btn.getAttribute('data-energi') || '0';
    const kalsium = btn.getAttribute('data-kalsium') || '0';
    const fosfor = btn.getAttribute('data-fosfor') || '0';
    const harga = btn.getAttribute('data-harga') || '';
    const deskripsi = btn.getAttribute('data-deskripsi') || '';

    // Set hidden input value
    document.getElementById('input-nama-template').value = key;

    // Update trigger UI
    const labelEl = document.getElementById('label-selected-bahan');
    if (labelEl) {
        labelEl.textContent = label;
        labelEl.classList.remove('text-muted');
        labelEl.classList.add('text-ink', 'font-bold');
    }

    const triggerIcon = document.querySelector('#dropdown-master-bahan .dropdown-selected-icon');
    if (triggerIcon) {
        triggerIcon.classList.remove('text-muted');
        triggerIcon.classList.add('text-brand');
    }

    // Close dropdown
    const container = document.getElementById('dropdown-master-bahan');
    if (container) {
        const menu = container.querySelector('.dropdown-menu');
        const caret = container.querySelector('.dropdown-caret');
        const trigger = container.querySelector('.dropdown-trigger');
        if (menu) menu.classList.add('hidden');
        if (caret) caret.classList.remove('rotate-180');
        if (trigger) trigger.setAttribute('aria-expanded', 'false');
    }

    // Update live nutrition preview box
    document.getElementById('preview-kategori').textContent = kategori;
    document.getElementById('preview-protein').textContent = protein + '%';
    document.getElementById('preview-energi').textContent = Number(energi).toLocaleString('id-ID') + ' kkal';
    document.getElementById('preview-kalsium').textContent = kalsium + '%';
    document.getElementById('preview-fosfor').textContent = fosfor + '%';
    document.getElementById('preview-deskripsi').textContent = deskripsi;

    const hargaInput = document.getElementById('input-tambah-harga');
    if (hargaInput && !hargaInput.value) {
        hargaInput.value = harga;
    }

    const box = document.getElementById('preview-nutrisi-box');
    if (box) {
        box.classList.remove('hidden');
    }
}

function filterMasterBahanDropdown(input) {
    const query = input.value.toLowerCase().trim();
    const items = document.querySelectorAll('#list-master-bahan [data-master-item]');
    const emptyEl = document.getElementById('master-item-empty');
    let visibleCount = 0;

    items.forEach(item => {
        const nama = item.getAttribute('data-nama') || '';
        const match = query === '' || nama.includes(query);
        item.style.display = match ? 'flex' : 'none';
        if (match) visibleCount++;
    });

    if (emptyEl) {
        emptyEl.classList.toggle('hidden', visibleCount > 0);
    }
}

function bukaModalUpdateStok(id, nama, stok, harga) {
    document.getElementById('modal-update-id').value = id;
    document.getElementById('modal-update-nama').textContent = nama;
    document.getElementById('modal-input-stok').value = stok;
    document.getElementById('modal-input-harga').value = harga;
    bukaModal('modalUpdateStok');
}

function filterBahanStok(input) {
    const query = input.value.toLowerCase().trim();
    const cards = document.querySelectorAll('[data-stok-card]');
    const emptyState = document.getElementById('stok-empty-filter');
    let visibleCount = 0;

    cards.forEach(card => {
        const nama = card.getAttribute('data-stok-nama') || '';
        const match = query === '' || nama.includes(query);
        card.style.display = match ? '' : 'none';
        if (match) visibleCount++;
    });

    if (emptyState) {
        emptyState.classList.toggle('hidden', visibleCount > 0);
    }
}
</script>
@endsection
