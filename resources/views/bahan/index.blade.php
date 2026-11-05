@extends('layouts.app')
@section('judul', 'Bahan Pakan & Stok Gudang')
@section('isi')
@use('App\Support\Fmt')

<div class="space-y-5">
    {{-- Header Halaman Bersih & Humanis --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-lg sm:text-xl font-bold text-ink tracking-tight">Bahan Pakan & Stok Gudang</h1>
            <p class="text-xs text-muted mt-0.5">Inventaris stok pakan kandang aktif, deteksi batas aman pemesanan (Safety Stock), dan katalog nutrisi baku Fapet UGM.</p>
        </div>

        {{-- Switcher Pill Tabs --}}
        <div class="inline-flex rounded-full bg-surface-2 p-1 text-xs font-bold border border-line/60 shadow-2xs">
            <button type="button" 
                    id="btn-tab-stok"
                    onclick="switchTabBahan('stok')"
                    class="px-4 py-2 rounded-full transition {{ $tab === 'stok' ? 'bg-white shadow-2xs text-brand font-bold' : 'text-muted hover:text-ink font-semibold' }} flex items-center gap-1.5 cursor-pointer select-none">
                <span class="material-symbols-outlined text-[16px]">inventory_2</span>
                <span>Stok Gudang</span>
                @if ($kritisCount > 0)
                    <span class="px-1.5 py-0.2 rounded-full text-[10px] font-black bg-amber-500 text-white leading-tight">
                        {{ $kritisCount }}
                    </span>
                @endif
            </button>
            <button type="button" 
                    id="btn-tab-katalog"
                    onclick="switchTabBahan('katalog')"
                    class="px-4 py-2 rounded-full transition {{ $tab === 'katalog' ? 'bg-white shadow-2xs text-brand font-bold' : 'text-muted hover:text-ink font-semibold' }} flex items-center gap-1.5 cursor-pointer select-none">
                <span class="material-symbols-outlined text-[16px]">menu_book</span>
                <span>Nutrisi Baku Resmi</span>
            </button>
        </div>
    </div>

    {{-- ======================================================== --}}
    {{-- TAB 1: STOK GUDANG PAKAN & SAFETY STOCK                   --}}
    {{-- ======================================================== --}}
    <div id="panel-tab-stok" class="{{ $tab === 'stok' ? '' : 'hidden' }} space-y-5">
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
                    <p class="text-[11px] text-muted mt-0.5">Untuk {{ number_format($farm->jumlah_ayam, 0, ',', '.') }} ekor ayam di kandang ini</p>
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
                    <div class="bg-white rounded-xl border border-line p-4 sm:p-5 shadow-2xs hover:shadow-xs transition flex flex-col justify-between space-y-3" 
                         data-stok-card 
                         data-stok-nama="{{ strtolower($d['fi']->ingredient->nama ?? '') }}">
                        
                        <div class="space-y-3">
                            {{-- Baris Atas: Nama Bahan & Status Safety Stock --}}
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <div class="flex items-center gap-2">
                                        <h2 class="text-base font-bold text-ink tracking-tight truncate">
                                            {{ $d['fi']->ingredient->nama }}
                                        </h2>
                                        @if ($d['fi']->ingredient->bk)
                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-[#f8fbf9] text-muted border border-line shrink-0">
                                                BK {{ Fmt::num($d['fi']->ingredient->bk, 0) }}%
                                            </span>
                                        @endif
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
                                </div>
                                <div class="px-1.5">
                                    <span class="text-[10px] font-semibold text-muted uppercase tracking-wider block">Ketahanan</span>
                                    <span class="text-xs sm:text-sm font-bold {{ $d['status'] === 'menipis' ? 'text-amber-600' : ($d['status'] === 'habis' ? 'text-rose-600' : 'text-brand') }} mt-0.5 block tabular-nums">
                                        {{ $d['hari_tersisa'] > 0 ? $d['hari_tersisa'] . ' Hari' : 'Habis' }}
                                    </span>
                                </div>
                                <div class="px-1.5">
                                    <span class="text-[10px] font-semibold text-muted uppercase tracking-wider block">Harga Beli</span>
                                    <span class="text-xs sm:text-sm font-bold text-ink mt-0.5 block truncate tabular-nums">
                                        {{ Fmt::rp($d['harga']) }} <span class="text-[10px] font-normal text-muted">/kg</span>
                                    </span>
                                </div>
                            </div>
                        </div>

                        {{-- Footer Aksi Update Stok Cepat --}}
                        <div class="pt-2.5 border-t border-line flex items-center justify-between gap-2">
                            <span class="text-[11px] text-muted">
                                {{ $d['fi']->harga_updated_at ? 'Diperbarui '.$d['fi']->harga_updated_at->diffForHumans() : 'Harga acuan baku' }}
                            </span>
                            <button type="button" 
                                    onclick="bukaModalUpdateStok({{ $d['fi']->id }}, '{{ addslashes($d['fi']->ingredient->nama) }}', {{ (float)$d['stok_kg'] }}, {{ (int)$d['harga'] }})"
                                    class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full text-xs font-bold text-brand hover:text-brand-dark bg-brand/5 hover:bg-brand/10 border border-brand/20 transition cursor-pointer shadow-2xs active:scale-95">
                                <span class="material-symbols-outlined text-[15px]">edit</span>
                                <span>Update Stok</span>
                            </button>
                        </div>
                    </div>
                @empty
                    <div class="col-span-full bg-white rounded-xl border border-line text-center py-10 text-muted space-y-2">
                        <span class="material-symbols-outlined text-[36px] text-muted/50 block">inventory_2</span>
                        <p class="font-bold text-ink text-sm">Gudang Pakan Masih Kosong</p>
                        <p class="text-xs text-muted">Belum ada bahan pakan yang terhubung ke kandang ini.</p>
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
    {{-- TAB 2: KATALOG NUTRISI BAKU (PUSTAKA FAPET UGM)           --}}
    {{-- ======================================================== --}}
    <div id="panel-tab-katalog" class="{{ $tab === 'katalog' ? '' : 'hidden' }} space-y-5">
        @php
            $totalBahan = $bahan->count();
            $avgHarga = $totalBahan > 0 ? $bahan->avg('harga_default') : 0;
            $minProtein = $totalBahan > 0 ? $bahan->min('protein') : 0;
            $maxProtein = $totalBahan > 0 ? $bahan->max('protein') : 0;
        @endphp

        {{-- Baris Ringkasan KPI Bahan Pakan (Bersih & Minimalis) --}}
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3.5">
            {{-- Kartu 1: Total Bahan Tersimpan --}}
            <div class="card p-4 rounded-xl flex flex-col justify-between space-y-1.5 shadow-2xs">
                <span class="text-xs font-bold text-muted uppercase tracking-wider">Katalog Baku</span>
                <div>
                    <div class="text-xl sm:text-2xl font-black text-ink">
                        {{ $totalBahan }} <span class="text-xs font-normal text-muted">Bahan Baku Resmi</span>
                    </div>
                    <p class="text-[11px] text-muted mt-0.5">Standar resmi nutrisi Fapet UGM & Balitnak</p>
                </div>
            </div>

            {{-- Kartu 2: Estimasi Rata-rata Harga Acuan --}}
            <div class="card p-4 rounded-xl flex flex-col justify-between space-y-1.5 shadow-2xs">
                <span class="text-xs font-bold text-muted uppercase tracking-wider">Rata-rata Harga Pasar</span>
                <div>
                    <div class="text-xl sm:text-2xl font-black text-ink">
                        {{ Fmt::rp($avgHarga) }} <span class="text-xs font-normal text-muted">/ kg</span>
                    </div>
                    <p class="text-[11px] text-muted mt-0.5">Estimasi rata-rata harga pasar bahan baku</p>
                </div>
            </div>

            {{-- Kartu 3: Profil Protein Rata-rata --}}
            <div class="card p-4 rounded-xl flex flex-col justify-between space-y-1.5 shadow-2xs">
                <span class="text-xs font-bold text-muted uppercase tracking-wider">Rentang Protein</span>
                <div>
                    <div class="text-xl sm:text-2xl font-black text-ink">
                        {{ Fmt::num($minProtein, 0) }}% - {{ Fmt::num($maxProtein, 0) }}% <span class="text-xs font-normal text-muted">Protein Kasar</span>
                    </div>
                    <p class="text-[11px] text-muted mt-0.5">Dari bahan sumber energi hingga konsentrat protein</p>
                </div>
            </div>
        </div>

        {{-- Pencarian Cepat Real-Time Terintegrasi --}}
        <div class="relative flex items-center">
            <span class="material-symbols-outlined absolute left-4 text-[18px] text-muted pointer-events-none">search</span>
            <input type="text" 
                   id="search-bahan-live"
                   oninput="filterBahanKatalog(this)"
                   placeholder="Cari nama bahan pakan (misal: Jagung, Dedak, Konsentrat, Bungkil, Kapur)..." 
                   class="w-full pl-11 pr-10 py-2.5 bg-white border border-line rounded-full text-xs sm:text-sm font-medium text-ink placeholder:text-muted focus:outline-none focus:ring-2 focus:ring-brand/15 focus:border-brand shadow-2xs transition"
                   autocomplete="off">
        </div>

        {{-- Subheader Bersih --}}
        <div class="space-y-3.5">
            <div class="flex items-center justify-between pb-1 border-b border-line/60">
                <span class="text-xs font-bold text-muted uppercase tracking-wider">
                    Katalog Bahan Pakan Baku ({{ $totalBahan }})
                </span>
                <span class="text-[11px] text-muted hidden sm:inline">Kandungan nutrisi baku terstandarisasi untuk perhitungan formulasi ransum otomatis</span>
            </div>

            {{-- Grid 2 Kolom Bahan Pakan --}}
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                @forelse ($bahan as $b)
                    <div class="bg-white rounded-xl border border-line p-4 sm:p-5 shadow-2xs hover:shadow-xs transition flex flex-col justify-between space-y-3" data-bahan-card data-bahan-nama="{{ strtolower($b->nama) }}">
                        <div class="space-y-3">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <h2 class="text-base font-bold text-ink tracking-tight truncate">
                                        {{ $b->nama }}
                                    </h2>
                                    <div class="flex items-center gap-1.5 flex-wrap mt-1">
                                        @if ($b->max_persen && $b->max_persen < 100)
                                            <span class="px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-amber-50 text-amber-800 border border-amber-200">
                                                Maks: {{ Fmt::num($b->max_persen, 0) }}%
                                            </span>
                                        @endif
                                        @if ($b->bk)
                                            <span class="px-2.5 py-0.5 rounded-full text-[11px] font-medium bg-[#f8fbf9] text-muted border border-line">
                                                BK {{ Fmt::num($b->bk, 1) }}%
                                            </span>
                                        @endif
                                        <span class="px-2.5 py-0.5 rounded-full text-[11px] font-medium bg-[#f8fbf9] text-muted border border-line truncate max-w-[170px]" title="{{ $b->sumber ?: 'Fapet UGM & Balitnak' }}">
                                            {{ $b->sumber ?: 'Fapet UGM & Balitnak' }}
                                        </span>
                                    </div>
                                </div>

                                <div class="text-right shrink-0">
                                    <span class="text-[10px] font-semibold text-muted uppercase tracking-wider block">Harga Acuan</span>
                                    <span class="text-sm sm:text-base font-bold text-ink block">
                                        {{ Fmt::rp($b->harga_default) }} <span class="text-xs font-normal text-muted">/ kg</span>
                                    </span>
                                </div>
                            </div>

                            <div class="grid grid-cols-5 divide-x divide-line rounded-lg border border-line/60 bg-[#f8fbf9] py-2 text-center">
                                <div class="px-1">
                                    <span class="text-[10px] font-semibold text-muted uppercase tracking-wider block">Protein</span>
                                    <span class="text-xs sm:text-sm font-bold text-ink mt-0.5 block">
                                        {{ Fmt::num($b->protein, 1) }}%
                                    </span>
                                </div>
                                <div class="px-1">
                                    <span class="text-[10px] font-semibold text-muted uppercase tracking-wider block">Energi</span>
                                    <span class="text-xs sm:text-sm font-bold text-ink mt-0.5 block truncate">
                                        {{ Fmt::num($b->energi, 0) }} <span class="text-[10px] font-normal text-muted">kkal</span>
                                    </span>
                                </div>
                                <div class="px-1">
                                    <span class="text-[10px] font-semibold text-muted uppercase tracking-wider block">Kalsium</span>
                                    <span class="text-xs sm:text-sm font-bold text-ink mt-0.5 block">
                                        {{ Fmt::num($b->kalsium, 2) }}%
                                    </span>
                                </div>
                                <div class="px-1">
                                    <span class="text-[10px] font-semibold text-muted uppercase tracking-wider block">Fosfor</span>
                                    <span class="text-xs sm:text-sm font-bold text-ink mt-0.5 block">
                                        {{ Fmt::num($b->fosfor, 2) }}%
                                    </span>
                                </div>
                                <div class="px-1">
                                    <span class="text-[10px] font-semibold text-muted uppercase tracking-wider block">Serat</span>
                                    <span class="text-xs sm:text-sm font-bold text-ink mt-0.5 block">
                                        {{ Fmt::num($b->serat, 1) }}%
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="pt-2.5 border-t border-line">
                            @if ($b->catatan_keamanan)
                                <span class="text-[11px] text-amber-800 font-medium truncate block" title="{{ $b->catatan_keamanan }}">
                                    {{ $b->catatan_keamanan }}
                                </span>
                            @elseif ($b->catatan && !str_contains(strtolower($b->catatan), 'sesuaikan dengan label'))
                                <span class="text-[11px] text-muted truncate block" title="{{ $b->catatan }}">
                                    {{ $b->catatan }}
                                </span>
                            @else
                                <span class="text-[11px] text-muted">Bahan baku baku siap digunakan pada peracik ransum</span>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="col-span-full bg-white rounded-xl border border-line text-center py-10 text-muted space-y-2">
                        <span class="material-symbols-outlined text-[36px] text-muted/50 block">inventory_2</span>
                        <p class="font-bold text-ink text-sm">Belum Ada Bahan Pakan</p>
                        <p class="text-xs text-muted">Katalog bahan pakan sedang dimuat.</p>
                    </div>
                @endforelse

                <div id="bahan-empty-filter" class="hidden col-span-full bg-white rounded-xl border border-line text-center py-10 text-muted space-y-1">
                    <span class="material-symbols-outlined text-[36px] text-muted/50 mb-1 block">search_off</span>
                    <p class="font-bold text-ink text-sm">Bahan Tidak Ditemukan</p>
                    <p class="text-xs text-muted">Tidak ada bahan pakan yang sesuai dengan kata kunci pencarian Anda.</p>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Modal Update Stok Bahan Gudang --}}
<dialog id="modalUpdateStok" class="modal p-0 rounded-2xl backdrop:bg-black/50 backdrop:backdrop-blur-xs shadow-2xl border border-line max-w-md w-full m-auto">
    <div class="p-5 sm:p-6 space-y-4 bg-white rounded-2xl">
        <div class="flex items-center justify-between pb-3 border-b border-line">
            <div>
                <h3 class="text-base font-bold text-ink">Update Stok Gudang</h3>
                <p class="text-xs text-muted mt-0.5">Bahan: <strong id="modal-update-nama" class="text-brand font-bold"></strong></p>
            </div>
            <button type="button" 
                    onclick="document.getElementById('modalUpdateStok').close()" 
                    class="w-8 h-8 rounded-full bg-surface-2 hover:bg-surface text-muted hover:text-ink flex items-center justify-center transition cursor-pointer">
                <span class="material-symbols-outlined text-[18px]">close</span>
            </button>
        </div>

        <form method="POST" action="{{ route('bahan.stok.update') }}" class="space-y-4">
            @csrf
            <input type="hidden" name="farm_ingredient_id" id="modal-update-id">

            <div>
                <label class="lbl text-xs" for="modal-input-stok">Jumlah Stok di Gudang</label>
                <div class="relative flex items-center">
                    <input type="number" 
                           id="modal-input-stok" 
                           name="stok_kg" 
                           min="0" 
                           max="100000" 
                           step="0.5" 
                           inputmode="decimal" 
                           class="field !pr-10 font-bold text-ink text-sm" 
                           required>
                    <span class="absolute right-3.5 text-xs font-bold text-muted pointer-events-none select-none">kg</span>
                </div>
                <p class="text-[11px] text-muted mt-1">Masukkan total berat stok bahan fisik yang saat ini ada di gudang.</p>
            </div>

            <div>
                <label class="lbl text-xs" for="modal-input-harga">Harga Beli Satuan (Terkini)</label>
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
                    Simpan Stok
                </button>
            </div>
        </form>
    </div>
</dialog>

<script>
function switchTabBahan(tab) {
    const panelStok = document.getElementById('panel-tab-stok');
    const panelKatalog = document.getElementById('panel-tab-katalog');
    const btnStok = document.getElementById('btn-tab-stok');
    const btnKatalog = document.getElementById('btn-tab-katalog');

    if (tab === 'stok') {
        panelStok.classList.remove('hidden');
        panelKatalog.classList.add('hidden');
        btnStok.className = 'px-4 py-2 rounded-full transition bg-white shadow-2xs text-brand font-bold flex items-center gap-1.5 cursor-pointer select-none';
        btnKatalog.className = 'px-4 py-2 rounded-full transition text-muted hover:text-ink font-semibold flex items-center gap-1.5 cursor-pointer select-none';
    } else {
        panelStok.classList.add('hidden');
        panelKatalog.classList.remove('hidden');
        btnKatalog.className = 'px-4 py-2 rounded-full transition bg-white shadow-2xs text-brand font-bold flex items-center gap-1.5 cursor-pointer select-none';
        btnStok.className = 'px-4 py-2 rounded-full transition text-muted hover:text-ink font-semibold flex items-center gap-1.5 cursor-pointer select-none';
    }

    try {
        const url = new URL(window.location);
        url.searchParams.set('tab', tab);
        window.history.replaceState({}, '', url);
    } catch(e) {}
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

function filterBahanKatalog(input) {
    const query = input.value.toLowerCase().trim();
    const cards = document.querySelectorAll('[data-bahan-card]');
    const emptyState = document.getElementById('bahan-empty-filter');
    let visibleCount = 0;

    cards.forEach(card => {
        const nama = card.getAttribute('data-bahan-nama') || '';
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
