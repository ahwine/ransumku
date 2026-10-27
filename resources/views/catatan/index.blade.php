@extends('layouts.app')
@section('judul', 'Buku Kandang')
@section('isi')
@use('App\Support\Fmt')

<div class="flex items-center justify-between">
    <div>
        <h1 class="text-h-xl">Buku Kandang</h1>
        <p class="text-muted text-sm">Catatan harian, grafik performa, dan efisiensi pakan</p>
    </div>
</div>

{{-- Tabs --}}
@php
    $tabAktif = request()->query('tab', 'form');
@endphp

<div data-tabs class="space-y-4">
    <div data-tab-nav class="relative grid grid-cols-3 gap-1 p-1 rounded-full bg-surface-2 text-center text-sm font-bold select-none">
        <div data-tab-slider class="tab-slider" aria-hidden="true"></div>
        <button type="button" data-tab="form" class="relative z-10 py-2.5 rounded-full transition-colors duration-200 cursor-pointer {{ $tabAktif === 'form' ? 'active-tab bg-brand text-white' : 'text-muted hover:text-ink' }}">
            Catat Harian
        </button>
        <button type="button" data-tab="dashboard" class="relative z-10 py-2.5 rounded-full transition-colors duration-200 cursor-pointer {{ $tabAktif === 'dashboard' ? 'active-tab bg-brand text-white' : 'text-muted hover:text-ink' }}">
            Dashboard
        </button>
        <button type="button" data-tab="beli" class="relative z-10 py-2.5 rounded-full transition-colors duration-200 cursor-pointer {{ $tabAktif === 'beli' ? 'active-tab bg-brand text-white' : 'text-muted hover:text-ink' }}">
            Belanja Pakan
        </button>
    </div>

    {{-- Panel 1: Form Catatan Harian --}}
    <section data-panel="form" class="{{ $tabAktif === 'form' ? '' : 'hidden' }} space-y-4">
        <form method="POST" action="{{ route('catatan.store') }}" class="card space-y-3">
            @csrf
            <div class="flex items-center justify-between pb-1 border-b border-line/60">
                <h2 class="text-h-sm text-ink">Catat Data Hari Ini</h2>
                <span class="text-xs text-muted flex items-center gap-1">
                    <span class="material-symbols-outlined text-[16px] text-brand">timer</span>
                    Cepat 1-2 menit
                </span>
            </div>

            <div>
                <label class="lbl" for="tanggal">Tanggal</label>
                <input id="tanggal" name="tanggal" type="date" value="{{ old('tanggal', $form->tanggal->toDateString()) }}" max="{{ today()->toDateString() }}" class="field" required>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="lbl" for="pakan_kg">Pakan Terpakai (kg)</label>
                    <input id="pakan_kg" name="pakan_kg" type="number" step="0.1" min="0" class="field text-h-sm font-bold" value="{{ old('pakan_kg', (float) $form->pakan_kg) }}" required>
                </div>
                <div>
                    <label class="lbl" for="telur_butir">Jumlah Telur (butir)</label>
                    <input id="telur_butir" name="telur_butir" type="number" min="0" class="field text-h-sm font-bold" value="{{ old('telur_butir', $form->telur_butir) }}" required>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="lbl" for="telur_kg">Berat Telur (kg, opsional)</label>
                    <input id="telur_kg" name="telur_kg" type="number" step="0.1" min="0" class="field" value="{{ old('telur_kg', $form->telur_kg) }}" placeholder="Contoh: 10.5">
                    <p class="text-[11px] text-muted mt-0.5">Jika kosong, diestimasi 62g/butir</p>
                </div>
                <div>
                    <label class="lbl" for="ayam_mati">Ayam Mati (ekor)</label>
                    <input id="ayam_mati" name="ayam_mati" type="number" min="0" class="field" value="{{ old('ayam_mati', $form->ayam_mati ?? 0) }}">
                </div>
            </div>

            <div>
                <label class="lbl" for="biaya_lain">Biaya Lain-lain (Rp, opsional)</label>
                <input id="biaya_lain" name="biaya_lain" type="number" min="0" step="500" class="field" value="{{ old('biaya_lain', $form->biaya_lain ?? 0) }}" placeholder="Misal: vitamin, obat, sekam">
            </div>

            <div>
                <label class="lbl" for="catatan">Catatan Kondisi / Gejala</label>
                <textarea id="catatan" name="catatan" rows="2" class="field !min-h-[60px] py-2" placeholder="Contoh: Cuaca panas, ayam banyak minum">{{ old('catatan', $form->catatan) }}</textarea>
            </div>

            <button class="btn-primary w-full">
                <span class="material-symbols-outlined">save</span>Simpan Catatan
            </button>
        </form>

        {{-- Riwayat Catatan Terakhir --}}
        <div class="card space-y-2">
            <h2 class="text-h-sm">10 Catatan Terakhir</h2>
            <div class="divide-y divide-line/60 text-sm">
                @forelse ($riwayat as $r)
                    <div class="py-2.5 flex items-center justify-between">
                        <div>
                            <span class="font-bold text-ink">{{ $r->tanggal->translatedFormat('d M Y') }}</span>
                            <p class="text-xs text-muted">
                                Pakan: {{ Fmt::num($r->pakan_kg, 1) }} kg · Telur: {{ Fmt::num($r->telur_butir, 0) }} btr ({{ Fmt::num($r->telurKgEfektif(), 1) }} kg)
                                @if ($r->ayam_mati > 0) · <span class="text-danger font-bold">Mati: {{ $r->ayam_mati }}</span> @endif
                            </p>
                        </div>
                        <div class="flex items-center gap-1">
                            <a href="{{ route('catatan.index', ['tanggal' => $r->tanggal->toDateString(), 'tab' => 'form']) }}" class="btn-outline btn-sm !min-h-8 !px-2.5 text-xs">
                                Edit
                            </a>
                            <form method="POST" action="{{ route('catatan.destroy', $r) }}" onsubmit="return confirm('Hapus catatan tanggal ini?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm !min-h-8 !px-2 text-danger border border-line">
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
        {{-- Filter Rentang --}}
        <div class="flex items-center justify-between">
            <span class="text-lbl text-muted">Rentang grafik:</span>
            <div class="flex gap-1 bg-surface-2 p-1 rounded-full text-xs font-bold">
                <a href="{{ route('catatan.index', ['tab' => 'dashboard', 'rentang' => 'minggu']) }}" class="px-3 py-1.5 rounded-full {{ $rentang === 7 ? 'bg-brand text-white' : 'text-muted' }}">7 Hari</a>
                <a href="{{ route('catatan.index', ['tab' => 'dashboard', 'rentang' => 'bulan']) }}" class="px-3 py-1.5 rounded-full {{ $rentang === 30 ? 'bg-brand text-white' : 'text-muted' }}">30 Hari</a>
            </div>
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
                    <span class="text-[11px] block mt-0.5 {{ $laporan['ini']['telur'] >= $laporan['lalu']['telur'] ? 'text-brand' : 'text-danger' }}">
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
@endsection
