@extends('layouts.app')
@section('judul', 'Buku Kandang')
@section('isi')
@use('App\Support\Fmt')
@use('Illuminate\Support\Str')

<div class="flex items-center justify-between">
    <div>
        <h1 class="text-h-xl">Buku Kandang</h1>
        <p class="text-muted text-sm">Catatan harian, grafik performa, dan efisiensi pakan · <span class="font-bold text-ink">{{ $farm->nama }}</span> ({{ $jumlahAyam }} ayam)</p>
    </div>
</div>

{{-- Tabs --}}
@php
    $tabAktif = request()->query('tab', 'form');
@endphp

<div data-tabs class="space-y-4">
    <div data-tab-nav class="tab-segmented-track relative grid grid-cols-3 gap-1 p-1.5 rounded-full text-center text-xs sm:text-sm font-bold select-none w-full">
        <div data-tab-slider class="tab-slider" aria-hidden="true"></div>
        <button type="button" data-tab="form" class="relative z-10 py-2.5 px-3 rounded-full transition-colors duration-200 cursor-pointer outline-none focus:outline-none focus:ring-0 select-none {{ $tabAktif === 'form' ? 'active-tab bg-white shadow-xs text-ink font-bold' : 'text-muted hover:text-ink font-semibold' }}">
            Catat Harian
        </button>
        <button type="button" data-tab="dashboard" class="relative z-10 py-2.5 px-3 rounded-full transition-colors duration-200 cursor-pointer outline-none focus:outline-none focus:ring-0 select-none {{ $tabAktif === 'dashboard' ? 'active-tab bg-white shadow-xs text-ink font-bold' : 'text-muted hover:text-ink font-semibold' }}">
            Dashboard
        </button>
        <button type="button" data-tab="beli" class="relative z-10 py-2.5 px-3 rounded-full transition-colors duration-200 cursor-pointer outline-none focus:outline-none focus:ring-0 select-none {{ $tabAktif === 'beli' ? 'active-tab bg-white shadow-xs text-ink font-bold' : 'text-muted hover:text-ink font-semibold' }}">
            Belanja Pakan
        </button>
    </div>

    {{-- Panel 1: Form Catatan Harian Cerdas --}}
    <section data-panel="form" class="{{ $tabAktif === 'form' ? '' : 'hidden' }} space-y-4">
        <form method="POST" action="{{ route('catatan.store') }}" class="card space-y-3.5">
            @csrf
            <div class="flex items-center justify-between pb-1 border-b border-line/60">
                <div class="flex items-center gap-2">
                    <h2 class="text-h-sm text-ink">Catat Data Hari Ini</h2>
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-bold bg-brand/10 text-brand">
                        <span class="w-1.5 h-1.5 rounded-full bg-brand"></span>
                        Smart Form
                    </span>
                </div>
                <span class="text-xs text-muted flex items-center gap-1">
                    <span class="material-symbols-outlined text-[16px] text-brand">timer</span>
                    Cepat 1-2 menit
                </span>
            </div>

            {{-- Banner Mode Edit (Muncul jika tanggal yang dipilih sudah ada catatan) --}}
            <div id="mode-status-banner" class="{{ $isEdit ? '' : 'hidden' }} py-2 px-3 rounded-xl bg-amber-50 border border-amber-300 text-xs text-amber-950 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="material-symbols-outlined text-[18px] text-amber-700">edit_calendar</span>
                    <span><strong>Mode Edit:</strong> Catatan tanggal <span id="mode-status-date" class="font-bold underline">{{ $form->tanggal->translatedFormat('d M Y') }}</span> sudah ada. Mengisi form ini akan memperbarui data.</span>
                </div>
                <a href="{{ route('catatan.index', ['tab' => 'form']) }}" class="text-[11px] font-bold text-amber-900 hover:text-amber-700 underline">Reset Hari Ini</a>
            </div>

            {{-- Quick Fill dari Catatan Kemarin --}}
            @if ($yesterdayLog)
            <div class="flex flex-wrap items-center justify-between gap-2 bg-surface-2/70 p-2.5 rounded-xl border border-line/60">
                <div class="text-xs text-muted">
                    <span class="font-bold text-ink">Catatan Kemarin ({{ $yesterdayLog->tanggal->translatedFormat('d M') }}):</span>
                    {{ Fmt::num($yesterdayLog->pakan_kg, 1) }} kg pakan · {{ Fmt::num($yesterdayLog->telur_butir, 0) }} btr
                </div>
                <button type="button" id="btn-quick-fill"
                        data-pakan="{{ (float) $yesterdayLog->pakan_kg }}"
                        data-telur="{{ (int) $yesterdayLog->telur_butir }}"
                        data-berat="{{ $yesterdayLog->telur_kg !== null ? (float) $yesterdayLog->telur_kg : '' }}"
                        class="btn-outline btn-sm !py-1 !px-2.5 text-xs flex items-center gap-1 font-bold text-brand hover:bg-brand/10 border-brand/30">
                    <span class="material-symbols-outlined text-[15px] text-amber-500 icon-fill">bolt</span>
                    Salin Angka Kemarin
                </button>
            </div>
            @endif

            <div>
                <label class="lbl" for="tanggal">Tanggal Pencatatan</label>
                <input id="tanggal" name="tanggal" type="date" value="{{ old('tanggal', $form->tanggal->toDateString()) }}" max="{{ today()->toDateString() }}" class="field font-semibold" required>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="lbl" for="pakan_kg">Pakan Terpakai (kg)</label>
                    <input id="pakan_kg" name="pakan_kg" type="number" step="0.1" min="0" class="field text-h-sm font-black text-ink" value="{{ old('pakan_kg', (float) $form->pakan_kg) }}" required placeholder="0">
                </div>
                <div>
                    <label class="lbl" for="telur_butir">Jumlah Telur (butir)</label>
                    <input id="telur_butir" name="telur_butir" type="number" min="0" class="field text-h-sm font-black text-ink" value="{{ old('telur_butir', $form->telur_butir) }}" required placeholder="0">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <div class="flex items-center justify-between">
                        <label class="lbl" for="telur_kg">Berat Telur (kg, opsional)</label>
                        <button type="button" id="btn-calc-egg-weight" class="text-[11px] text-brand hover:underline font-bold flex items-center gap-0.5 cursor-pointer" title="Hitung otomatis dari jumlah butir">
                            <span class="material-symbols-outlined text-[13px]">auto_fix_high</span>
                            Hitung ~62g/btr
                        </button>
                    </div>
                    <input id="telur_kg" name="telur_kg" type="number" step="0.1" min="0" class="field" value="{{ old('telur_kg', $form->telur_kg) }}" placeholder="Contoh: 10.5">
                    <p class="text-[11px] text-muted mt-0.5">Jika kosong, diestimasi 62g/butir</p>
                </div>
                <div>
                    <label class="lbl" for="ayam_mati">Ayam Mati (ekor)</label>
                    <input id="ayam_mati" name="ayam_mati" type="number" min="0" class="field" value="{{ old('ayam_mati', $form->ayam_mati ?? 0) }}" placeholder="0">
                </div>
            </div>

            <div>
                <label class="lbl" for="biaya_lain">Biaya Lain-lain (Rp, opsional)</label>
                <input id="biaya_lain" name="biaya_lain" type="number" min="0" step="500" class="field" value="{{ old('biaya_lain', $form->biaya_lain ?? 0) }}" placeholder="Misal: vitamin, obat, sekam, listrik">
            </div>

            <div>
                <label class="lbl" for="catatan">Catatan Kondisi / Gejala</label>
                <textarea id="catatan" name="catatan" rows="2" class="field !min-h-[56px] py-2" placeholder="Contoh: Cuaca panas terik, ayam minum lebih banyak dari biasanya">{{ old('catatan', $form->catatan) }}</textarea>
            </div>

            {{-- Live Calculation Preview Card (Otomatis & Realtime) --}}
            <div id="live-preview-box" class="p-3.5 rounded-2xl bg-gradient-to-br from-emerald-50/60 via-white to-teal-50/40 border border-brand/20 space-y-2.5 shadow-xs">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-brand text-[18px]">query_stats</span>
                        <span class="text-xs font-bold text-brand-dark tracking-wide uppercase">Pratinjau Performa Harian</span>
                    </div>
                    <span class="text-[11px] text-muted">Populasi: <strong class="text-ink">{{ $jumlahAyam }} ekor</strong></span>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 text-center">
                    {{-- Hen-Day % --}}
                    <div class="p-2 rounded-xl bg-white/90 border border-line/60">
                        <span class="text-[11px] text-muted block">Hen-Day (HDP)</span>
                        <strong id="preview-hdp-val" class="text-base text-ink block font-black">0.0%</strong>
                        <span id="preview-hdp-badge" class="inline-block mt-0.5 px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-muted">Menunggu</span>
                    </div>

                    {{-- Konsumsi/Ekor --}}
                    <div class="p-2 rounded-xl bg-white/90 border border-line/60">
                        <span class="text-[11px] text-muted block">Pakan / Ekor</span>
                        <strong id="preview-feed-val" class="text-base text-ink block font-black">0 <span class="text-xs font-normal">gr</span></strong>
                        <span id="preview-feed-badge" class="inline-block mt-0.5 px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-muted">Std: 110g</span>
                    </div>

                    {{-- FCR --}}
                    <div class="p-2 rounded-xl bg-white/90 border border-line/60">
                        <span class="text-[11px] text-muted block">FCR Instan</span>
                        <strong id="preview-fcr-val" class="text-base text-ink block font-black">0.00</strong>
                        <span id="preview-fcr-badge" class="inline-block mt-0.5 px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-muted">Efisiensi</span>
                    </div>

                    {{-- Biaya Pakan --}}
                    <div class="p-2 rounded-xl bg-white/90 border border-line/60">
                        <span class="text-[11px] text-muted block">Biaya Pakan <span class="tag-est">est</span></span>
                        <strong id="preview-cost-val" class="text-base text-ink block font-black">Rp 0</strong>
                        <span class="inline-block mt-0.5 text-[10px] text-muted font-medium">@ {{ Fmt::rp($harga) }}/kg</span>
                    </div>
                </div>

                {{-- Alert Box Dinamis --}}
                <div id="preview-alert-box" class="hidden p-2 rounded-xl text-xs font-semibold flex items-center gap-2">
                    <span id="preview-alert-icon" class="material-symbols-outlined text-[18px]"></span>
                    <span id="preview-alert-text"></span>
                </div>
            </div>

            <button class="btn-primary w-full shadow-sm hover:shadow-md transition">
                <span class="material-symbols-outlined">save</span>
                <span id="btn-submit-text">{{ $isEdit ? 'Perbarui Catatan Harian' : 'Simpan Catatan Harian' }}</span>
            </button>
        </form>

        {{-- Riwayat Catatan Terakhir --}}
        <div class="card space-y-2.5">
            <div class="flex items-center justify-between pb-1 border-b border-line/60">
                <h2 class="text-h-sm">10 Catatan Terakhir</h2>
                <button type="button" id="btn-export-csv" class="text-xs text-brand font-bold flex items-center gap-1 hover:underline cursor-pointer">
                    <span class="material-symbols-outlined text-[16px]">download</span>
                    Unduh CSV
                </button>
            </div>

            <div class="divide-y divide-line/60 text-sm">
                @forelse ($riwayat as $r)
                    @php
                        $itemHdp = $jumlahAyam > 0 ? round(($r->telur_butir / $jumlahAyam) * 100, 1) : 0;
                        $itemGr = $jumlahAyam > 0 ? round(($r->pakan_kg * 1000) / $jumlahAyam, 1) : 0;
                    @endphp
                    <div class="py-2.5 flex items-center justify-between gap-3">
                        <div class="space-y-0.5 min-w-0">
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="font-bold text-ink">{{ $r->tanggal->translatedFormat('d M Y') }}</span>
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $itemHdp >= 80 ? 'bg-emerald-100 text-emerald-800' : ($itemHdp >= 65 ? 'bg-teal-100 text-teal-800' : 'bg-amber-100 text-amber-800') }}">
                                    HDP {{ $itemHdp }}%
                                </span>
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-surface-2 text-muted">
                                    {{ $itemGr }} g/ekor
                                </span>
                                @if ($r->ayam_mati > 0)
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-danger/10 text-danger">
                                        Mati {{ $r->ayam_mati }}
                                    </span>
                                @endif
                            </div>
                            <p class="text-xs text-muted truncate">
                                Pakan: {{ Fmt::num($r->pakan_kg, 1) }} kg · Telur: {{ Fmt::num($r->telur_butir, 0) }} btr ({{ Fmt::num($r->telurKgEfektif(), 1) }} kg)
                                @if ($r->catatan) · <span class="italic text-ink">"{{ Str::limit($r->catatan, 35) }}"</span> @endif
                            </p>
                        </div>
                        <div class="flex items-center gap-1 shrink-0">
                            <a href="{{ route('catatan.index', ['tanggal' => $r->tanggal->toDateString(), 'tab' => 'form']) }}" class="btn-outline btn-sm !min-h-8 !px-2.5 text-xs font-semibold">
                                Edit
                            </a>
                            <form method="POST" action="{{ route('catatan.destroy', $r) }}" onsubmit="return confirm('Hapus catatan tanggal ini?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm !min-h-8 !px-2 text-danger border border-line hover:bg-danger-soft">
                                    <span class="material-symbols-outlined text-[16px]">delete</span>
                                </button>
                            </form>
                        </div>
                    </div>
                @empty
                    <p class="text-xs text-muted py-3">Belum ada catatan.</p>
                @endforelse
            </div>
        </div>
    </section>

    {{-- Panel 2: Dashboard & Laporan --}}
    <section data-panel="dashboard" class="{{ $tabAktif === 'dashboard' ? '' : 'hidden' }} space-y-4">
        {{-- Filter Rentang & Aksi Ekspor --}}
        <div class="flex flex-wrap items-center justify-between gap-2">
            <div class="flex items-center gap-2">
                <span class="text-lbl text-muted">Rentang grafik:</span>
                <div class="flex gap-1 bg-surface-2 p-1 rounded-full text-xs font-bold">
                    <a href="{{ route('catatan.index', ['tab' => 'dashboard', 'rentang' => 'minggu']) }}" class="px-3 py-1.5 rounded-full {{ $rentang === 7 ? 'bg-brand text-white shadow-xs' : 'text-muted' }}">7 Hari</a>
                    <a href="{{ route('catatan.index', ['tab' => 'dashboard', 'rentang' => 'bulan']) }}" class="px-3 py-1.5 rounded-full {{ $rentang === 30 ? 'bg-brand text-white shadow-xs' : 'text-muted' }}">30 Hari</a>
                </div>
            </div>
            <button type="button" id="btn-copy-wa" class="btn-outline btn-sm text-xs font-bold text-emerald-800 border-emerald-300 hover:bg-emerald-50 flex items-center gap-1.5">
                <span class="material-symbols-outlined text-[16px] text-emerald-600">share</span>
                Salin Ringkasan ke WhatsApp
            </button>
        </div>

        {{-- Laporan Mingguan Ringkas --}}
        <div class="card space-y-3 bg-brand/5 border-brand/20">
            <div class="flex items-center justify-between">
                <h2 class="text-h-sm text-primary flex items-center gap-1.5">
                    <span class="material-symbols-outlined icon-fill">analytics</span>
                    Laporan Ringkas 7 Hari
                </h2>
                <span class="chip bg-brand/10 text-brand">vs 7 hari sebelumnya</span>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 text-center text-xs">
                <div class="p-2.5 rounded-xl bg-white border border-line/60">
                    <span class="text-muted block">Total Telur</span>
                    <strong class="text-base text-ink">{{ Fmt::num($laporan['ini']['telur'], 0) }} btr</strong>
                    <span class="text-[11px] block mt-0.5 {{ $laporan['ini']['telur'] >= $laporan['lalu']['telur'] ? 'text-brand font-bold' : 'text-danger font-bold' }}">
                        {{ $laporan['lalu']['telur'] ? ($laporan['ini']['telur'] >= $laporan['lalu']['telur'] ? '+' : '').round(($laporan['ini']['telur'] - $laporan['lalu']['telur']) / $laporan['lalu']['telur'] * 100).'%' : '-' }}
                    </span>
                </div>
                <div class="p-2.5 rounded-xl bg-white border border-line/60">
                    <span class="text-muted block">Konsumsi Pakan</span>
                    <strong class="text-base text-ink">{{ Fmt::num($laporan['ini']['pakan'], 1) }} kg</strong>
                    <span class="text-[11px] block mt-0.5 text-muted">
                        {{ $laporan['ini']['hari'] ? round($laporan['ini']['pakan'] / $laporan['ini']['hari'], 1) : 0 }} kg/hari
                    </span>
                </div>
                <div class="p-2.5 rounded-xl bg-white border border-line/60">
                    <span class="text-muted block">Biaya Pakan <span class="tag-est">est</span></span>
                    <strong class="text-base text-ink">{{ Fmt::rp($laporan['ini']['biaya_pakan']) }}</strong>
                    <span class="text-[11px] block mt-0.5 text-muted">Rp {{ number_format($harga, 0, ',', '.') }}/kg</span>
                </div>
                <div class="p-2.5 rounded-xl bg-white border border-line/60">
                    <span class="text-muted block">Biaya/kg Telur <span class="tag-est">est</span></span>
                    <strong class="text-base text-ink">{{ Fmt::rp($laporan['ini']['biaya_kg_telur']) }}</strong>
                    <span class="text-[11px] block mt-0.5 text-muted">Rasio {{ Fmt::num($laporan['ini']['rasio'], 0) }} g/butir</span>
                </div>
            </div>
        </div>

        {{-- 4 Grafik Chart.js --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="card space-y-2">
                <div class="flex items-center justify-between">
                    <h3 class="font-bold text-sm">Konsumsi Pakan (kg)</h3>
                    <span class="material-symbols-outlined text-amber-ink text-[20px]">inventory</span>
                </div>
                <div class="h-48">
                    <canvas data-chart='@json($grafik['pakan'])' aria-label="Grafik pakan"></canvas>
                </div>
            </div>

            <div class="card space-y-2">
                <div class="flex items-center justify-between">
                    <h3 class="font-bold text-sm">Produksi Telur (butir)</h3>
                    <span class="material-symbols-outlined text-brand text-[20px]">egg</span>
                </div>
                <div class="h-48">
                    <canvas data-chart='@json($grafik['telur'])' aria-label="Grafik telur"></canvas>
                </div>
            </div>

            <div class="card space-y-2">
                <div class="flex items-center justify-between">
                    <h3 class="font-bold text-sm">Biaya Pakan Harian <span class="tag-est">estimasi</span></h3>
                    <span class="material-symbols-outlined text-primary text-[20px]">payments</span>
                </div>
                <div class="h-48">
                    <canvas data-chart='@json($grafik['biaya'])' aria-label="Grafik biaya pakan"></canvas>
                </div>
            </div>

            <div class="card space-y-2">
                <div class="flex items-center justify-between">
                    <h3 class="font-bold text-sm">Efisiensi: Biaya per Kg Telur <span class="tag-est">estimasi</span></h3>
                    <span class="material-symbols-outlined text-danger text-[20px]">monitoring</span>
                </div>
                <div class="h-48">
                    <canvas data-chart='@json($grafik['efisiensi'])' aria-label="Grafik efisiensi"></canvas>
                </div>
            </div>
        </div>
    </section>

    {{-- Panel 3: Pembelian & Selisih Estimasi vs Kenyataan --}}
    <section data-panel="beli" class="{{ $tabAktif === 'beli' ? '' : 'hidden' }} space-y-4">
        {{-- Ringkasan Selisih --}}
        <div class="card space-y-2 {{ $selisihTotal > 0 ? 'bg-danger-soft/40 border-danger/30' : 'bg-brand/5 border-brand/20' }}">
            <div class="flex items-center justify-between">
                <h2 class="text-h-sm flex items-center gap-1.5">
                    <span class="material-symbols-outlined icon-fill">compare_arrows</span>
                    Selisih Estimasi vs Biaya Nyata
                </h2>
                <span class="chip {{ $selisihTotal > 0 ? 'bg-danger text-white' : 'bg-brand text-white' }}">
                    {{ $selisihTotal > 0 ? 'Lebih Mahal' : 'Lebih Hemat' }}
                </span>
            </div>
            <p class="text-xs text-muted">
                Bandingkan harga yang Anda gunakan saat menyusun ransum dengan harga nyata saat belanja bahan di toko.
            </p>
            <p class="text-money {{ $selisihTotal > 0 ? 'text-danger-ink' : 'text-brand' }}">
                {{ $selisihTotal > 0 ? '+' : '' }}{{ Fmt::rp($selisihTotal) }}
            </p>
        </div>

        {{-- Form Catat Pembelian --}}
        <form method="POST" action="{{ route('pembelian.store') }}" class="card space-y-3">
            @csrf
            <h2 class="text-h-sm">Catat Pembelian Bahan</h2>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="lbl" for="ingredient_id">Bahan Pakan</label>
                    <select id="ingredient_id" name="ingredient_id" class="field" required>
                        @foreach ($bahan as $b)
                            <option value="{{ $b->id }}">{{ $b->nama }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="lbl" for="beli_tanggal">Tanggal Beli</label>
                    <input id="beli_tanggal" name="tanggal" type="date" value="{{ today()->toDateString() }}" max="{{ today()->toDateString() }}" class="field" required>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="lbl" for="kg">Jumlah Beli (kg)</label>
                    <input id="kg" name="kg" type="number" step="0.5" min="0.5" class="field" placeholder="Contoh: 50" required>
                </div>
                <div>
                    <label class="lbl" for="harga_nyata">Harga Nyata Toko (Rp/kg)</label>
                    <input id="harga_nyata" name="harga_nyata" type="number" step="50" min="0" class="field" placeholder="Contoh: 6700" required>
                </div>
            </div>

            {{-- Checkbox auto update stock --}}
            <label class="flex items-center gap-2.5 p-2 rounded-xl bg-surface-2 border border-line/60 cursor-pointer">
                <input type="checkbox" name="tambah_stok" value="1" checked class="w-4 h-4 rounded text-brand focus:ring-brand">
                <span class="text-xs text-ink font-medium">Otomatis tambahkan jumlah kg ini ke stok gudang pakan aktif</span>
            </label>

            <button class="btn-amber w-full">
                <span class="material-symbols-outlined">receipt_long</span>Catat Belanja Bahan
            </button>
        </form>

        {{-- Riwayat Pembelian --}}
        <div class="card space-y-2">
            <h2 class="text-h-sm">Riwayat Belanja Bahan</h2>
            <div class="divide-y divide-line/60 text-sm">
                @forelse ($beli as $p)
                    @php $sel = ($p->harga_nyata - $p->harga_estimasi) * $p->kg; @endphp
                    <div class="py-2.5 flex items-center justify-between">
                        <div>
                            <span class="font-bold text-ink">{{ $p->ingredient->nama }}</span>
                            <span class="text-xs text-muted ml-1">({{ $p->tanggal->translatedFormat('d M Y') }})</span>
                            <p class="text-xs text-muted">
                                {{ Fmt::num($p->kg, 1) }} kg · Nyata: {{ Fmt::rp($p->harga_nyata) }}/kg (Est: {{ Fmt::rp($p->harga_estimasi) }}/kg)
                            </p>
                        </div>
                        <div class="text-right">
                            <span class="font-bold text-sm {{ $sel > 0 ? 'text-danger' : 'text-brand' }}">
                                {{ $sel > 0 ? '+' : '' }}{{ Fmt::rp($sel) }}
                            </span>
                        </div>
                    </div>
                @empty
                    <p class="text-xs text-muted py-3">Belum ada catatan belanja.</p>
                @endforelse
            </div>
        </div>
    </section>
</div>

{{-- Skrip Interaktivitas Smart Buku Kandang --}}
<script>
document.addEventListener('DOMContentLoaded', function () {
    const jumlahAyam = {{ $jumlahAyam }};
    const hargaPakanPerKg = {{ $harga }};
    const existingLogs = @json($existingLogs);

    const inputTanggal = document.getElementById('tanggal');
    const inputPakan = document.getElementById('pakan_kg');
    const inputTelur = document.getElementById('telur_butir');
    const inputBerat = document.getElementById('telur_kg');
    const inputMati = document.getElementById('ayam_mati');
    const inputBiayaLain = document.getElementById('biaya_lain');
    const inputCatatan = document.getElementById('catatan');

    const previewHdpVal = document.getElementById('preview-hdp-val');
    const previewHdpBadge = document.getElementById('preview-hdp-badge');
    const previewFeedVal = document.getElementById('preview-feed-val');
    const previewFeedBadge = document.getElementById('preview-feed-badge');
    const previewFcrVal = document.getElementById('preview-fcr-val');
    const previewFcrBadge = document.getElementById('preview-fcr-badge');
    const previewCostVal = document.getElementById('preview-cost-val');
    const previewAlertBox = document.getElementById('preview-alert-box');
    const previewAlertIcon = document.getElementById('preview-alert-icon');
    const previewAlertText = document.getElementById('preview-alert-text');
    const modeStatusBanner = document.getElementById('mode-status-banner');
    const modeStatusDate = document.getElementById('mode-status-date');
    const btnSubmitText = document.getElementById('btn-submit-text');

    function updateLivePreview() {
        const pakan = parseFloat(inputPakan.value) || 0;
        const butir = parseInt(inputTelur.value) || 0;
        let berat = parseFloat(inputBerat.value);
        if (isNaN(berat) || berat <= 0) {
            berat = butir * 0.062;
        }
        const mati = parseInt(inputMati ? inputMati.value : 0) || 0;

        // 1. HDP %
        const hdp = jumlahAyam > 0 ? (butir / jumlahAyam) * 100 : 0;
        previewHdpVal.textContent = hdp > 0 ? hdp.toFixed(1) + '%' : '0.0%';
        if (butir === 0) {
            previewHdpBadge.className = 'inline-block mt-0.5 px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-muted';
            previewHdpBadge.textContent = 'Menunggu data';
        } else if (hdp >= 85) {
            previewHdpBadge.className = 'inline-block mt-0.5 px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800';
            previewHdpBadge.textContent = 'Prima / Sangat Baik';
        } else if (hdp >= 70) {
            previewHdpBadge.className = 'inline-block mt-0.5 px-2 py-0.5 rounded-full text-[10px] font-bold bg-teal-100 text-teal-800';
            previewHdpBadge.textContent = 'Normal';
        } else {
            previewHdpBadge.className = 'inline-block mt-0.5 px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800';
            previewHdpBadge.textContent = 'Perhatian (<70%)';
        }

        // 2. Konsumsi gr/ekor/hari
        const grPerEkor = (jumlahAyam > 0 && pakan > 0) ? (pakan * 1000) / jumlahAyam : 0;
        previewFeedVal.innerHTML = `${Math.round(grPerEkor)} <span class="text-xs font-normal">gr</span>`;
        if (pakan === 0) {
            previewFeedBadge.className = 'inline-block mt-0.5 px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-muted';
            previewFeedBadge.textContent = 'Std: 110g';
        } else if (grPerEkor >= 105 && grPerEkor <= 125) {
            previewFeedBadge.className = 'inline-block mt-0.5 px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800';
            previewFeedBadge.textContent = 'Ideal (110-125g)';
        } else if (grPerEkor < 105) {
            previewFeedBadge.className = 'inline-block mt-0.5 px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800';
            previewFeedBadge.textContent = 'Rendah (<105g)';
        } else {
            previewFeedBadge.className = 'inline-block mt-0.5 px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-100 text-rose-800';
            previewFeedBadge.textContent = 'Boros (>125g)';
        }

        // 3. FCR Instan
        const fcr = (berat > 0 && pakan > 0) ? (pakan / berat) : 0;
        previewFcrVal.textContent = fcr > 0 ? fcr.toFixed(2) : '0.00';
        if (fcr === 0) {
            previewFcrBadge.className = 'inline-block mt-0.5 px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-muted';
            previewFcrBadge.textContent = 'Efisiensi';
        } else if (fcr <= 2.15) {
            previewFcrBadge.className = 'inline-block mt-0.5 px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800';
            previewFcrBadge.textContent = 'Sangat Efisien';
        } else if (fcr <= 2.45) {
            previewFcrBadge.className = 'inline-block mt-0.5 px-2 py-0.5 rounded-full text-[10px] font-bold bg-teal-100 text-teal-800';
            previewFcrBadge.textContent = 'Normal';
        } else {
            previewFcrBadge.className = 'inline-block mt-0.5 px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-100 text-rose-800';
            previewFcrBadge.textContent = 'Tinggi (>2.45)';
        }

        // 4. Biaya Pakan Hari Ini
        const totalBiaya = pakan * hargaPakanPerKg;
        previewCostVal.textContent = 'Rp ' + Math.round(totalBiaya).toLocaleString('id-ID');

        // Dynamic Alert Message
        if (mati >= 5) {
            previewAlertBox.className = 'p-2 rounded-xl text-xs font-semibold flex items-center gap-2 bg-rose-100 text-rose-900 border border-rose-300';
            previewAlertIcon.textContent = 'warning';
            previewAlertText.textContent = `Perhatian: Ada ${mati} ekor ayam mati tercatat hari ini. Pantau sirkulasi dan gejala penyakit!`;
            previewAlertBox.classList.remove('hidden');
        } else if (hdp > 0 && hdp < 65) {
            previewAlertBox.className = 'p-2 rounded-xl text-xs font-semibold flex items-center gap-2 bg-amber-100 text-amber-900 border border-amber-300';
            previewAlertIcon.textContent = 'info';
            previewAlertText.textContent = `Produksi telur rendah (${hdp.toFixed(1)}%). Cek kecukupan nutrisi pakan atau potensi stres suhu.`;
            previewAlertBox.classList.remove('hidden');
        } else if (hdp >= 88 && fcr > 0 && fcr <= 2.15) {
            previewAlertBox.className = 'p-2 rounded-xl text-xs font-semibold flex items-center gap-2 bg-emerald-100 text-emerald-900 border border-emerald-300';
            previewAlertIcon.textContent = 'verified';
            previewAlertText.textContent = `Luar biasa! Performa kandang hari ini sangat prima (HDP ${hdp.toFixed(1)}%, FCR ${fcr.toFixed(2)}).`;
            previewAlertBox.classList.remove('hidden');
        } else {
            previewAlertBox.classList.add('hidden');
        }
    }

    [inputPakan, inputTelur, inputBerat, inputMati].forEach(el => {
        if (el) {
            el.addEventListener('input', updateLivePreview);
        }
    });

    // Button: Hitung ~62g/butir
    const btnCalcEgg = document.getElementById('btn-calc-egg-weight');
    if (btnCalcEgg && inputTelur && inputBerat) {
        btnCalcEgg.addEventListener('click', function () {
            const butir = parseInt(inputTelur.value) || 0;
            if (butir > 0) {
                const estKg = (butir * 0.062).toFixed(1);
                inputBerat.value = estKg;
                updateLivePreview();
            } else {
                alert('Silakan isi jumlah butir telur terlebih dahulu.');
                inputTelur.focus();
            }
        });
    }

    // Button: Quick fill from yesterday
    const btnQuickFill = document.getElementById('btn-quick-fill');
    if (btnQuickFill) {
        btnQuickFill.addEventListener('click', function () {
            const pakan = this.dataset.pakan;
            const telur = this.dataset.telur;
            const berat = this.dataset.berat;
            if (pakan) inputPakan.value = pakan;
            if (telur) inputTelur.value = telur;
            if (berat) inputBerat.value = berat;
            updateLivePreview();
        });
    }

    // Detect date changes and switch edit mode
    if (inputTanggal) {
        inputTanggal.addEventListener('change', function () {
            const tgl = this.value;
            if (existingLogs && existingLogs[tgl]) {
                const log = existingLogs[tgl];
                if (modeStatusBanner) {
                    modeStatusBanner.classList.remove('hidden');
                    if (modeStatusDate) modeStatusDate.textContent = tgl;
                }
                if (btnSubmitText) btnSubmitText.textContent = 'Perbarui Catatan Harian';
                inputPakan.value = log.pakan_kg;
                inputTelur.value = log.telur_butir;
                inputBerat.value = log.telur_kg !== null ? log.telur_kg : '';
                if (inputMati) inputMati.value = log.ayam_mati;
                if (inputBiayaLain) inputBiayaLain.value = log.biaya_lain;
                if (inputCatatan) inputCatatan.value = log.catatan;
            } else {
                if (modeStatusBanner) modeStatusBanner.classList.add('hidden');
                if (btnSubmitText) btnSubmitText.textContent = 'Simpan Catatan Harian';
            }
            updateLivePreview();
        });
    }

    // Salin Laporan Mingguan ke WhatsApp
    const btnCopyWa = document.getElementById('btn-copy-wa');
    if (btnCopyWa) {
        btnCopyWa.addEventListener('click', function () {
            const text = `📊 *LAPORAN PERFORMA KANDANG - RANSUMKU*\n`
                + `🏠 Kandang: {{ $farm->nama }}\n`
                + `🗓️ Periode: 7 Hari Terakhir\n`
                + `-----------------------------------\n`
                + `🥚 *Total Telur*: {{ Fmt::num($laporan['ini']['telur'], 0) }} butir\n`
                + `🌾 *Konsumsi Pakan*: {{ Fmt::num($laporan['ini']['pakan'], 1) }} kg ({{ $laporan['ini']['hari'] ? round($laporan['ini']['pakan'] / $laporan['ini']['hari'], 1) : 0 }} kg/hari)\n`
                + `💰 *Est. Biaya Pakan*: {{ Fmt::rp($laporan['ini']['biaya_pakan']) }}\n`
                + `⚖️ *Est. Biaya / Kg Telur*: {{ Fmt::rp($laporan['ini']['biaya_kg_telur']) }}\n`
                + `🐔 *Ayam Mati*: {{ $laporan['ini']['mati'] }} ekor\n`
                + `-----------------------------------\n`
                + `_Dicatat otomatis via RansumKu Smart Poultry Farm_`;

            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(text).then(() => {
                    alert('Laporan mingguan berhasil disalin ke clipboard! Silakan paste (Ctrl+V) ke chat WhatsApp.');
                }).catch(() => {
                    copyViaTextarea(text);
                });
            } else {
                copyViaTextarea(text);
            }
        });
    }

    function copyViaTextarea(text) {
        const ta = document.createElement('textarea');
        ta.value = text;
        ta.style.position = 'fixed';
        ta.style.opacity = '0';
        document.body.appendChild(ta);
        ta.select();
        try {
            document.execCommand('copy');
            alert('Laporan mingguan berhasil disalin ke clipboard! Silakan paste (Ctrl+V) ke chat WhatsApp.');
        } catch (e) {
            alert('Gagal menyalin teks laporan.');
        }
        document.body.removeChild(ta);
    }

    // Unduh CSV Catatan Riwayat
    const btnExportCsv = document.getElementById('btn-export-csv');
    if (btnExportCsv) {
        btnExportCsv.addEventListener('click', function () {
            const rows = [
                ["Tanggal", "Pakan (kg)", "Telur (butir)", "Telur (kg)", "Ayam Mati", "Biaya Lain (Rp)", "Catatan"]
            ];
            @foreach ($riwayat as $r)
            rows.push([
                "{{ $r->tanggal->toDateString() }}",
                "{{ $r->pakan_kg }}",
                "{{ $r->telur_butir }}",
                "{{ $r->telurKgEfektif() }}",
                "{{ $r->ayam_mati }}",
                "{{ $r->biaya_lain }}",
                "{{ str_replace(['\r', '\n', '"'], ['', '', ''], $r->catatan ?? '') }}"
            ]);
            @endforeach

            let csvContent = "data:text/csv;charset=utf-8," + rows.map(e => e.join(",")).join("\n");
            let encodedUri = encodeURI(csvContent);
            let link = document.createElement("a");
            link.setAttribute("href", encodedUri);
            link.setAttribute("download", "buku_kandang_{{ Str::slug($farm->nama) }}_" + new Date().toISOString().slice(0,10) + ".csv");
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        });
    }

    // Jalankan kalkulasi awal saat halaman dimuat
    updateLivePreview();
});
</script>
@endsection
