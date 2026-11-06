@extends('layouts.app')
@section('judul', 'Stok Pakan Gudang')
@section('isi')
@use('App\Support\Fmt')

<div class="space-y-5">
    {{-- Header Halaman Bersih & Humanis --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-lg sm:text-xl font-bold text-ink tracking-tight">Stok Pakan Gudang</h1>
            <p class="text-xs text-muted mt-0.5">Inventaris pakan kandang aktif, pantau batas aman pemesanan (Safety Stock), dan takaran pakan.</p>
        </div>

        {{-- Tombol Aksi Utama: Tambah Bahan ke Gudang --}}
        <div>
            <button type="button" 
                    onclick="document.getElementById('modalTambahBahan').showModal()"
                    class="inline-flex items-center gap-1.5 px-4 py-2 rounded-full text-xs font-bold text-white bg-brand hover:bg-brand-dark transition shadow-2xs hover:shadow-xs active:scale-95 cursor-pointer">
                <span class="material-symbols-outlined text-[17px]">add</span>
                <span>+ Tambah Bahan ke Gudang</span>
            </button>
        </div>
    </div>

    {{-- Baris 3 KPI Stok Gudang --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3.5">
        {{-- KPI 1: Total Stok Tersimpan --}}
        <div class="card p-4 rounded-xl flex flex-col justify-between space-y-1.5 shadow-2xs">
            <span class="text-xs font-bold text-muted uppercase tracking-wider">Total Fisik Stok</span>
            <div>
                <div class="text-xl sm:text-2xl font-black text-ink">
                    {{ Fmt::num($totalStokKg, 1) }} <span class="text-xs font-normal text-muted">kg Tersedia</span>
                </div>
                <p class="text-[11px] text-muted mt-0.5">Estimasi nilai aset pakan: <strong class="text-ink font-semibold">{{ Fmt::rp($totalNilaiRp) }}</strong></p>
            </div>
        </div>

        {{-- KPI 2: Status Safety Stock --}}
        <div class="card p-4 rounded-xl flex flex-col justify-between space-y-1.5 shadow-2xs">
            <span class="text-xs font-bold text-muted uppercase tracking-wider">Status Safety Stock</span>
            <div>
                @if ($kritisCount > 0)
                    <div class="text-xl sm:text-2xl font-black text-amber-600 flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-[22px]">warning</span>
                        <span>{{ $kritisCount }} Bahan Perlu Pesan</span>
                    </div>
                    <p class="text-[11px] text-amber-800 mt-0.5">Stok tersisa menipis di bawah kebutuhan 3 hari</p>
                @else
                    <div class="text-xl sm:text-2xl font-black text-emerald-700 flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-[22px]">verified</span>
                        <span>Semua Bahan Aman</span>
                    </div>
                    <p class="text-[11px] text-emerald-800 mt-0.5">Persediaan mencukupi siklus pakan kandang</p>
                @endif
            </div>
        </div>

        {{-- KPI 3: Estimasi Kebutuhan Harian --}}
        <div class="card p-4 rounded-xl flex flex-col justify-between space-y-1.5 shadow-2xs">
            <span class="text-xs font-bold text-muted uppercase tracking-wider">Kebutuhan Pakan Harian</span>
            <div>
                <div class="text-xl sm:text-2xl font-black text-ink">
                    {{ Fmt::num($pakanHarianKg, 1) }} <span class="text-xs font-normal text-muted">kg / hari</span>
                </div>
                <p class="text-[11px] text-muted mt-0.5">Untuk {{ number_format($farm->jumlah_ayam, 0, ',', '.') }} ekor ayam petelur aktif</p>
            </div>
        </div>
    </div>

    {{-- Pencarian Cepat Live --}}
    <div class="relative flex items-center">
        <span class="material-symbols-outlined absolute left-4 text-[18px] text-muted pointer-events-none">search</span>
        <input type="text" 
               id="search-stok-live"
               oninput="filterBahanStok(this)"
               placeholder="Cari bahan di gudang (misal: Jagung, Dedak, Konsentrat, Kapur)..." 
               class="w-full pl-11 pr-10 py-2.5 bg-white border border-line rounded-full text-xs sm:text-sm font-medium text-ink placeholder:text-muted focus:outline-none focus:ring-2 focus:ring-brand/15 focus:border-brand shadow-2xs transition"
               autocomplete="off">
    </div>

    {{-- Subheader Gudang --}}
    <div class="space-y-3.5">
        <div class="flex items-center justify-between pb-1 border-b border-line/60">
            <span class="text-xs font-bold text-muted uppercase tracking-wider">
                Daftar Bahan di Gudang ({{ count($stokGudangData) }})
            </span>
            <span class="text-[11px] text-muted hidden sm:inline">Peringatan otomatis muncul bila stok tersisa &le; kebutuhan 3 hari</span>
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
                <div class="bg-white rounded-xl border border-line p-4 sm:p-5 shadow-2xs hover:shadow-xs transition flex flex-col justify-between space-y-3" 
                     data-stok-card 
                     data-stok-nama="{{ strtolower($rawNama) }}">
                    
                    <div class="space-y-3">
                        {{-- Baris Atas: Nama Bahan & Status Safety Stock --}}
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <h2 class="text-base font-bold text-ink tracking-tight truncate">
                                        {{ $rawNama }}
                                    </h2>
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-brand/10 text-brand border border-brand/20 shrink-0">
                                        {{ $kategoriLabel }}
                                    </span>
                                </div>
                                <span class="text-[11px] text-muted block mt-0.5">
                                    Batas aman minimum: <strong class="text-ink font-semibold">{{ Fmt::num($d['safety_buffer_kg'], 1) }} kg</strong>
                                </span>
                            </div>

                            {{-- Status Safety Stock Badge --}}
                            <span class="px-3 py-1 rounded-full text-xs font-bold border shrink-0 {{ $d['badge_class'] }}">
                                {{ $d['status_label'] }}
                            </span>
                        </div>

                        {{-- Strip 3 Metrik Terbagi --}}
                        <div class="grid grid-cols-3 divide-x divide-line rounded-lg border border-line/60 bg-[#f8fbf9] py-2 text-center">
                            <div class="px-1.5">
                                <span class="text-[10px] font-semibold text-muted uppercase tracking-wider block">Stok Gudang</span>
                                <span class="text-xs sm:text-sm font-bold text-ink mt-0.5 block tabular-nums">
                                    {{ Fmt::num($d['stok_kg'], 1) }} <span class="text-[10px] font-normal text-muted">kg</span>
                                </span>
                                @if ($d['stok_kg'] >= 50)
                                    <span class="text-[10px] text-muted block tabular-nums">~{{ $estimasiSak }} sak</span>
                                @endif
                            </div>
                            <div class="px-1.5">
                                <span class="text-[10px] font-semibold text-muted uppercase tracking-wider block">Ketahanan</span>
                                <span class="text-xs sm:text-sm font-bold {{ $d['status'] === 'menipis' ? 'text-amber-600' : ($d['status'] === 'habis' ? 'text-rose-600' : 'text-brand') }} mt-0.5 block tabular-nums">
                                    {{ $d['hari_tersisa'] > 0 ? $d['hari_tersisa'] . ' Hari' : 'Habis' }}
                                </span>
                                <span class="text-[10px] text-muted block">Pakai {{ Fmt::num($d['kebutuhan_harian_kg'], 1) }} kg/hr</span>
                            </div>
                            <div class="px-1.5">
                                <span class="text-[10px] font-semibold text-muted uppercase tracking-wider block">Harga Beli</span>
                                <span class="text-xs sm:text-sm font-bold text-ink mt-0.5 block truncate tabular-nums">
                                    {{ Fmt::rp($d['harga']) }} <span class="text-[10px] font-normal text-muted">/kg</span>
                                </span>
                                <span class="text-[10px] text-muted block">HPP Pakan</span>
                            </div>
                        </div>

                        {{-- Ringkasan Nutrisi Paten Fapet UGM --}}
                        @if ($d['fi']->ingredient)
                            <div class="p-2.5 rounded-lg bg-surface/60 border border-line/60 flex items-center justify-between text-[11px] text-muted">
                                <span>Nutrisi Baku:</span>
                                <div class="flex items-center gap-2 font-medium text-ink">
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
                    <div class="pt-2.5 border-t border-line flex items-center justify-between gap-2">
                        <span class="text-[11px] text-muted truncate">
                            {{ $d['fi']->harga_updated_at ? 'Diperbarui '.$d['fi']->harga_updated_at->diffForHumans() : 'Harga acuan baku' }}
                        </span>
                        
                        <div class="flex items-center gap-1.5 shrink-0">
                            {{-- Tombol Edit / Update Stok --}}
                            <button type="button" 
                                    onclick="bukaModalUpdateStok({{ $d['fi']->id }}, '{{ addslashes($rawNama) }}', {{ (float)$d['stok_kg'] }}, {{ (int)$d['harga'] }})"
                                    class="inline-flex items-center gap-1 px-3 py-1.5 rounded-full text-xs font-bold text-brand hover:text-brand-dark bg-brand/5 hover:bg-brand/10 border border-brand/20 transition cursor-pointer shadow-2xs active:scale-95"
                                    title="Perbarui stok atau harga beli">
                                <span class="material-symbols-outlined text-[15px]">edit</span>
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
                                        class="inline-flex items-center justify-center w-8 h-8 rounded-full text-rose-600 hover:text-rose-700 bg-rose-50 hover:bg-rose-100 border border-rose-200 transition cursor-pointer active:scale-95"
                                        title="Hapus bahan dari gudang">
                                    <span class="material-symbols-outlined text-[16px]">delete</span>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-span-full bg-white rounded-2xl border border-dashed border-amber-300 p-8 sm:p-12 text-center space-y-3">
                    <span class="material-symbols-outlined text-[44px] text-amber-500 block">inventory_2</span>
                    <div class="space-y-1">
                        <h3 class="text-base font-bold text-ink">Gudang Pakan Masih Kosong</h3>
                        <p class="text-xs text-muted max-w-md mx-auto leading-relaxed">
                            Belum ada bahan pakan yang terdaftar di gudang kandang ini. Tambahkan bahan pakan yang Anda miliki (seperti Jagung, Dedak, atau Konsentrat) untuk mulai memantau stok dan meracik ransum.
                        </p>
                    </div>
                    <button type="button" 
                            onclick="document.getElementById('modalTambahBahan').showModal()"
                            class="inline-flex items-center gap-1.5 px-5 py-2.5 rounded-full text-xs font-bold text-white bg-brand hover:bg-brand-dark transition shadow-2xs active:scale-95 cursor-pointer">
                        <span class="material-symbols-outlined text-[18px]">add</span>
                        <span>+ Tambah Bahan Pertama ke Gudang</span>
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
</div>

{{-- ======================================================== --}}
{{-- 1. MODAL TAMBAH BAHAN KE GUDANG (DROPDOWN OTOMATIS)      --}}
{{-- ======================================================== --}}
<dialog id="modalTambahBahan" class="rounded-2xl p-0 backdrop:bg-black/50 backdrop:backdrop-blur-xs shadow-2xl border border-line w-full max-w-lg overflow-hidden">
    <div class="bg-white p-5 sm:p-6 space-y-4">
        {{-- Header Modal --}}
        <div class="flex items-center justify-between pb-3 border-b border-line">
            <div class="flex items-center gap-2.5">
                <div class="w-9 h-9 rounded-xl bg-brand/10 text-brand grid place-items-center shrink-0">
                    <span class="material-symbols-outlined text-[20px]">add_circle</span>
                </div>
                <div>
                    <h3 class="text-sm sm:text-base font-bold text-ink">Tambah Bahan ke Gudang</h3>
                    <p class="text-[11px] text-muted">Pilih bahan pakan resmi, nutrisi terisi otomatis.</p>
                </div>
            </div>
            <button type="button" onclick="document.getElementById('modalTambahBahan').close()" class="p-1.5 rounded-lg text-muted hover:text-ink hover:bg-surface cursor-pointer">
                <span class="material-symbols-outlined text-[18px]">close</span>
            </button>
        </div>

        <form method="POST" action="{{ route('bahan.store') }}" class="space-y-4">
            @csrf

            {{-- 1. Dropdown Pilihan Master Bahan Pakan Petelur --}}
            <div>
                <label class="lbl text-xs font-bold" for="select-master-bahan">Pilih Bahan Pakan Ayam Petelur <span class="text-danger">*</span></label>
                <select id="select-master-bahan" 
                        name="nama_template" 
                        onchange="pilihMasterBahan(this)"
                        class="field !min-h-11 text-xs sm:text-sm font-semibold bg-white cursor-pointer"
                        required>
                    <option value="" disabled selected>-- Pilih Bahan Pakan dari Daftar --</option>
                    @foreach ($masterCatalog as $key => $item)
                        @php $alreadyIn = in_array($item['nama'], $existingNames, true); @endphp
                        <option value="{{ $key }}" 
                                data-kategori="{{ $item['kategori'] }}"
                                data-protein="{{ $item['protein'] }}"
                                data-energi="{{ $item['energi'] }}"
                                data-kalsium="{{ $item['kalsium'] }}"
                                data-fosfor="{{ $item['fosfor'] }}"
                                data-serat="{{ $item['serat'] }}"
                                data-harga="{{ $item['harga_default'] }}"
                                data-deskripsi="{{ $item['deskripsi'] }}"
                                {{ $alreadyIn ? 'class=text-muted' : '' }}>
                            {{ $item['label'] }} ({{ $item['kategori'] }}) {{ $alreadyIn ? '— [Sudah Ada di Gudang]' : '' }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Preview Nutrisi Otomatis Terkunci (Live JS) --}}
            <div id="preview-nutrisi-box" class="hidden p-3.5 rounded-xl bg-emerald-50/70 border border-emerald-200/80 space-y-2">
                <div class="flex items-center justify-between">
                    <span id="preview-kategori" class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800">
                        Kategori
                    </span>
                    <span class="text-[10px] font-bold text-emerald-700 flex items-center gap-1">
                        <span class="material-symbols-outlined text-[13px]">verified</span>
                        <span>Acuan Baku Fapet UGM</span>
                    </span>
                </div>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 text-center text-xs pt-1">
                    <div class="p-1.5 rounded-lg bg-white/80 border border-emerald-100">
                        <span class="text-[10px] text-muted block">Protein (PK)</span>
                        <strong id="preview-protein" class="text-xs font-bold text-ink">0%</strong>
                    </div>
                    <div class="p-1.5 rounded-lg bg-white/80 border border-emerald-100">
                        <span class="text-[10px] text-muted block">Energi (EM)</span>
                        <strong id="preview-energi" class="text-xs font-bold text-ink">0 kkal</strong>
                    </div>
                    <div class="p-1.5 rounded-lg bg-white/80 border border-emerald-100">
                        <span class="text-[10px] text-muted block">Kalsium (Ca)</span>
                        <strong id="preview-kalsium" class="text-xs font-bold text-ink">0%</strong>
                    </div>
                    <div class="p-1.5 rounded-lg bg-white/80 border border-emerald-100">
                        <span class="text-[10px] text-muted block">Fosfor (P)</span>
                        <strong id="preview-fosfor" class="text-xs font-bold text-ink">0%</strong>
                    </div>
                </div>
                <p id="preview-deskripsi" class="text-[11px] text-emerald-900/80 italic pt-1"></p>
            </div>

            {{-- 2. Input Jumlah Stok (Kg) --}}
            <div>
                <label class="lbl text-xs font-bold" for="input-tambah-stok">
                    <span>Jumlah Stok yang Masuk / Dimiliki</span>
                    <span class="text-danger">*</span>
                </label>
                <div class="relative flex items-center">
                    <input type="number" 
                           id="input-tambah-stok" 
                           name="stok_kg" 
                           min="0" 
                           step="0.1" 
                           placeholder="Contoh: 500" 
                           class="field font-semibold text-ink text-sm !pr-10" 
                           required>
                    <span class="absolute right-3.5 text-xs font-bold text-muted pointer-events-none select-none">kg</span>
                </div>
                <p class="text-[11px] text-muted mt-1">Petunjuk: 1 karung sak = ~50 kg (Contoh: 10 sak = 500 kg).</p>
            </div>

            {{-- 3. Input Harga Beli (Rp/kg) --}}
            <div>
                <label class="lbl text-xs font-bold" for="input-tambah-harga">
                    <span>Harga Beli per Kilogram</span>
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
                <p class="text-[11px] text-muted mt-1">Harga beli riil di sentra Anda untuk perhitungan akurat HPP pakan.</p>
            </div>

            {{-- Tombol Aksi Modal --}}
            <div class="flex items-center justify-end gap-2 pt-3 border-t border-line">
                <button type="button" 
                        onclick="document.getElementById('modalTambahBahan').close()" 
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
</dialog>

{{-- ======================================================== --}}
{{-- 2. MODAL EDIT / UPDATE STOK & HARGA GUDANG               --}}
{{-- ======================================================== --}}
<dialog id="modalUpdateStok" class="rounded-2xl p-0 backdrop:bg-black/50 backdrop:backdrop-blur-xs shadow-2xl border border-line w-full max-w-md overflow-hidden">
    <div class="bg-white p-5 sm:p-6 space-y-4">
        <div class="flex items-center justify-between pb-3 border-b border-line">
            <div>
                <h3 class="text-sm sm:text-base font-bold text-ink">Edit Stok & Harga Bahan</h3>
                <p class="text-xs text-muted" id="modal-update-nama">Bahan Pakan</p>
            </div>
            <button type="button" onclick="document.getElementById('modalUpdateStok').close()" class="p-1 rounded-lg text-muted hover:text-ink hover:bg-surface cursor-pointer">
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
                <label class="lbl text-xs font-bold" for="modal-input-harga">Harga Beli Terkini (per kg)</label>
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
                <p class="text-[11px] text-muted mt-1">Harga beli per kilogram untuk perhitungan akurasi HPP pakan.</p>
            </div>

            <div class="flex items-center justify-end gap-2 pt-2 border-t border-line">
                <button type="button" 
                        onclick="document.getElementById('modalUpdateStok').close()" 
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
</dialog>

<script>
function pilihMasterBahan(select) {
    const opt = select.options[select.selectedIndex];
    if (!opt || !opt.value) return;

    const box = document.getElementById('preview-nutrisi-box');
    const kategori = opt.getAttribute('data-kategori') || 'Bahan Pakan';
    const protein = opt.getAttribute('data-protein') || '0';
    const energi = opt.getAttribute('data-energi') || '0';
    const kalsium = opt.getAttribute('data-kalsium') || '0';
    const fosfor = opt.getAttribute('data-fosfor') || '0';
    const harga = opt.getAttribute('data-harga') || '';
    const deskripsi = opt.getAttribute('data-deskripsi') || '';

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

    box.classList.remove('hidden');
}

function bukaModalUpdateStok(id, nama, stok, harga) {
    document.getElementById('modal-update-id').value = id;
    document.getElementById('modal-update-nama').textContent = nama;
    document.getElementById('modal-input-stok').value = stok;
    document.getElementById('modal-input-harga').value = harga;
    document.getElementById('modalUpdateStok').showModal();
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
