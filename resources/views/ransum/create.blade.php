@extends('layouts.app')
@section('judul', 'Racik Ransum')
@section('isi')
@use('App\Support\Fmt')

<div class="flex items-center gap-3">
    <a href="{{ route('ransum.index') }}" 
       class="w-10 h-10 rounded-full bg-white border border-line shadow-2xs hover:bg-surface-2 flex items-center justify-center text-ink transition active:scale-95 shrink-0" 
       title="Kembali ke Koleksi Resep">
        <span class="material-symbols-outlined text-[20px]">arrow_back</span>
    </a>
    <div class="space-y-0.5 min-w-0">
        <h1 class="text-h-lg font-black tracking-tight text-ink truncate">Racik Formulasi Ransum</h1>
        <p class="text-xs text-muted">Sesuaikan harga dan stok bahan di gudang untuk menghasilkan pakan berkualitas dengan biaya terendah.</p>
    </div>
</div>

<form method="POST" action="{{ route('ransum.store') }}" class="mt-4 pb-24 lg:pb-0">
    @csrf

    <div class="lg:grid lg:grid-cols-12 lg:gap-6 items-start space-y-4 lg:space-y-0">
        {{-- KOLOM KIRI: Daftar Bahan Pakan --}}
        <div class="lg:col-span-7 xl:col-span-8 space-y-4">
            <section class="card space-y-3.5 p-4 sm:p-5">
                <div class="flex items-center justify-between flex-wrap gap-2">
                    <div>
                        <h2 class="text-h-sm font-bold text-ink">Bahan Pakan di Gudang</h2>
                        <p class="text-xs text-muted">Centang bahan yang siap digunakan dalam adukan racikan kali ini.</p>
                    </div>
                    <span class="px-3 py-1 rounded-full text-xs font-bold bg-surface-2 text-ink border border-line shadow-2xs">
                        {{ count($fis) }} Bahan Tersedia
                    </span>
                </div>

                {{-- Pencarian Cepat Bahan Racikan --}}
                <div class="relative flex items-center">
                    <span class="material-symbols-outlined absolute left-3.5 text-[18px] text-muted pointer-events-none">search</span>
                    <input type="text"
                           id="search-bahan-racik"
                           oninput="filterBahanRacikan(this)"
                           placeholder="Cari bahan racikan (misal: Jagung, Konsentrat, Dedak)..."
                           class="w-full pl-10 pr-9 py-2.5 bg-surface/50 focus:bg-white border border-line rounded-xl text-xs font-semibold text-ink placeholder:text-muted focus:outline-none focus:ring-1 focus:ring-brand focus:border-brand shadow-2xs transition"
                           autocomplete="off">
                    <button type="button" 
                            id="clear-search-bahan-racik"
                            onclick="clearFilterBahanRacikan()" 
                            class="hidden absolute right-3 text-muted hover:text-ink cursor-pointer p-0.5 rounded-md hover:bg-surface" 
                            title="Hapus pencarian">
                        <span class="material-symbols-outlined text-[16px]">close</span>
                    </button>
                </div>

                {{-- Daftar Kartu Bahan --}}
                <div class="space-y-3">
                    @foreach ($fis as $fi)
                        @php
                            $isAktif = (bool) old("bahan.{$fi->id}.aktif", $fi->aktif);
                        @endphp
                        <div class="rounded-2xl border p-3.5 space-y-3 transition-all duration-200 {{ $isAktif ? 'bg-white border-brand/30 shadow-2xs ring-1 ring-brand/10' : 'bg-surface/40 border-line/60 opacity-60' }}" 
                             data-racik-item 
                             data-bahan-nama="{{ strtolower($fi->ingredient->nama) }}">
                            
                            {{-- Header Kartu Bahan --}}
                            <div class="flex items-center justify-between flex-wrap gap-2">
                                <label class="flex items-center gap-2.5 font-bold cursor-pointer min-w-0">
                                    <input type="checkbox" 
                                           name="bahan[{{ $fi->id }}][aktif]" 
                                           value="1" 
                                           class="w-4 h-4 rounded text-brand focus:ring-brand/20 accent-brand cursor-pointer" 
                                           @checked($isAktif)>
                                    <span class="text-sm font-bold text-ink truncate">{{ $fi->ingredient->nama }}</span>
                                    <span class="px-2 py-0.5 rounded-full text-[11px] font-bold bg-brand/10 text-brand shrink-0">
                                        BK {{ Fmt::num($fi->ingredient->bk ?? 88, 0) }}%
                                    </span>
                                    @if (($fi->max_persen ?? $fi->ingredient->max_persen ?? 100) < 100)
                                        <span class="px-2 py-0.5 rounded-full text-[11px] font-bold bg-amber-soft/80 text-amber-ink border border-amber/20 shrink-0">
                                            Maks {{ Fmt::num($fi->max_persen ?? $fi->ingredient->max_persen, 0) }}%
                                        </span>
                                    @endif
                                </label>
                                <span class="text-[11px] font-semibold {{ $fi->hargaUsang() ? 'text-danger font-bold' : 'text-muted' }}">
                                    {{ $fi->harga_updated_at ? 'diperbarui '.$fi->harga_updated_at->diffForHumans() : 'belum pernah diperbarui' }}
                                </span>
                            </div>

                            {{-- Input Harga & Stok --}}
                            <div class="grid grid-cols-2 gap-3 pt-1">
                                <div>
                                    <label class="lbl text-[11px]" for="h{{ $fi->id }}">Harga Satuan</label>
                                    <div class="relative flex items-center">
                                        <span class="absolute left-3 text-xs font-bold text-muted pointer-events-none select-none">Rp</span>
                                        <input id="h{{ $fi->id }}" 
                                               name="bahan[{{ $fi->id }}][harga]" 
                                               type="number" 
                                               min="0" 
                                               step="50" 
                                               inputmode="numeric" 
                                               class="field !pl-9 font-semibold text-ink" 
                                               value="{{ old("bahan.{$fi->id}.harga", $fi->harga) }}">
                                    </div>
                                </div>
                                <div>
                                    <label class="lbl text-[11px]" for="s{{ $fi->id }}">Batas Stok (Maksimal)</label>
                                    <div class="relative flex items-center">
                                        <input id="s{{ $fi->id }}" 
                                               name="bahan[{{ $fi->id }}][stok]" 
                                               type="number" 
                                               min="0" 
                                               step="0.5" 
                                               inputmode="decimal" 
                                               class="field !pr-10 font-semibold text-ink" 
                                               value="{{ old("bahan.{$fi->id}.stok", (float) $fi->stok_kg) }}">
                                        <span class="absolute right-3 text-xs font-bold text-muted pointer-events-none select-none">kg</span>
                                    </div>
                                </div>
                            </div>

                            {{-- Pengaturan Ambang Batas Penggunaan --}}
                            <details class="text-xs text-muted pt-1 border-t border-line/40 group">
                                <summary class="cursor-pointer text-brand hover:text-brand-dark font-bold flex items-center gap-1.5 select-none py-1">
                                    <span class="material-symbols-outlined text-[16px] group-open:rotate-90 transition-transform">tune</span>
                                    <span>Batas Ambang Penggunaan (% Ransum)</span>
                                </summary>
                                <div class="grid grid-cols-2 gap-3 pt-2">
                                    <div>
                                        <label class="lbl !text-[11px]" for="min{{ $fi->id }}">Min Penggunaan (%)</label>
                                        <div class="relative flex items-center">
                                            <input id="min{{ $fi->id }}" 
                                                   name="bahan[{{ $fi->id }}][min_persen]" 
                                                   type="number" 
                                                   min="0" 
                                                   max="100" 
                                                   step="0.5" 
                                                   class="field !min-h-9 !py-1 text-xs !pr-7 font-semibold" 
                                                   value="{{ old("bahan.{$fi->id}.min_persen", $fi->min_persen ?? $fi->ingredient->min_persen ?? 0) }}">
                                            <span class="absolute right-2.5 text-xs font-bold text-muted pointer-events-none select-none">%</span>
                                        </div>
                                    </div>
                                    <div>
                                        <label class="lbl !text-[11px]" for="max{{ $fi->id }}">Maks Aman (%)</label>
                                        <div class="relative flex items-center">
                                            <input id="max{{ $fi->id }}" 
                                                   name="bahan[{{ $fi->id }}][max_persen]" 
                                                   type="number" 
                                                   min="0" 
                                                   max="100" 
                                                   step="0.5" 
                                                   class="field !min-h-9 !py-1 text-xs !pr-7 font-semibold" 
                                                   value="{{ old("bahan.{$fi->id}.max_persen", $fi->max_persen ?? $fi->ingredient->max_persen ?? 100) }}">
                                            <span class="absolute right-2.5 text-xs font-bold text-muted pointer-events-none select-none">%</span>
                                        </div>
                                    </div>
                                </div>
                                @if ($fi->ingredient->catatan_keamanan)
                                    <div class="mt-2 p-2 rounded-lg bg-surface border border-line text-[11px] text-muted flex items-start gap-1.5">
                                        <span class="material-symbols-outlined text-[15px] text-amber-ink shrink-0 mt-0.5">info</span>
                                        <span class="italic">{{ $fi->ingredient->catatan_keamanan }}</span>
                                    </div>
                                @endif
                            </details>
                        </div>
                    @endforeach
                </div>

                {{-- Live Filter Empty State --}}
                <div id="racik-empty-filter" class="hidden p-6 rounded-2xl border border-line bg-surface/50 text-center text-xs text-muted space-y-1.5">
                    <span class="material-symbols-outlined text-[28px] text-muted/60 block">search_off</span>
                    <p class="font-bold text-ink">Bahan Tidak Ditemukan</p>
                    <p class="text-xs text-muted">Bahan tersebut belum ada di gudang pakan Anda. Tambahkan bahan baru melalui tautan di bawah.</p>
                </div>

                <a href="{{ route('bahan.create') }}" class="w-full py-3 px-4 rounded-full border border-dashed border-brand/40 hover:border-brand bg-brand/5 hover:bg-brand/10 text-brand text-xs font-bold transition-all flex items-center justify-center gap-2">
                    <span class="material-symbols-outlined text-[18px]">add_circle</span>
                    <span>Tambah Master Bahan Pakan Baru</span>
                </a>
            </section>
        </div>

        {{-- KOLOM KANAN: Parameter Optimasi & Action Button --}}
        <div class="lg:col-span-5 xl:col-span-4 space-y-4 lg:sticky lg:top-24">
            {{-- 1. Jumlah Campuran --}}
            <section class="card space-y-3.5 p-4 sm:p-5">
                <div class="flex items-center gap-2">
                    <span class="material-symbols-outlined text-[20px] text-brand">scale</span>
                    <h2 class="text-h-sm font-bold text-ink">Jumlah Campuran</h2>
                </div>

                {{-- Segmented Pill Mode Switcher --}}
                <div class="grid grid-cols-2 gap-1 p-1 rounded-full bg-surface-2 border border-line/60">
                    @foreach (['kg' => 'Langsung kg', 'hari' => 'Berdasarkan hari'] as $v => $l)
                        <label class="cursor-pointer">
                            <input type="radio" name="mode" value="{{ $v }}" class="peer sr-only" @checked(old('mode', 'kg') === $v)>
                            <span class="block text-center py-2 rounded-full text-xs font-bold text-muted transition-all peer-checked:bg-white peer-checked:text-brand peer-checked:shadow-2xs select-none">{{ $l }}</span>
                        </label>
                    @endforeach
                </div>

                {{-- Mode Langsung Kg --}}
                <div data-mode="kg" class="space-y-1.5">
                    <label class="lbl text-[11px]" for="total_kg">Target Volume Campuran (kg)</label>
                    <div class="relative flex items-center">
                        <input id="total_kg" 
                               name="total_kg" 
                               type="number" 
                               min="1" 
                               step="0.5" 
                               inputmode="decimal" 
                               class="field !pr-10 font-bold text-ink" 
                               value="{{ old('total_kg', 40) }}">
                        <span class="absolute right-3.5 text-xs font-bold text-muted pointer-events-none select-none">kg</span>
                    </div>
                </div>

                {{-- Mode Berdasarkan Hari --}}
                <div data-mode="hari" class="hidden space-y-3">
                    <div class="grid grid-cols-2 gap-2.5">
                        <div>
                            <label class="lbl text-[11px]" for="jumlah_hari">Jumlah Hari</label>
                            <div class="relative flex items-center">
                                <input id="jumlah_hari" 
                                       name="jumlah_hari" 
                                       type="number" 
                                       min="1" 
                                       inputmode="numeric" 
                                       class="field !pr-12 font-semibold text-ink" 
                                       value="{{ old('jumlah_hari', 7) }}">
                                <span class="absolute right-3 text-[11px] font-bold text-muted pointer-events-none select-none">hari</span>
                            </div>
                        </div>
                        <div>
                            <label class="lbl text-[11px]" for="jumlah_ayam">Jumlah Ayam</label>
                            <div class="relative flex items-center">
                                <input id="jumlah_ayam" 
                                       name="jumlah_ayam" 
                                       type="number" 
                                       min="1" 
                                       inputmode="numeric" 
                                       class="field !pr-12 font-semibold text-ink" 
                                       value="{{ old('jumlah_ayam', $farm->jumlah_ayam) }}">
                                <span class="absolute right-3 text-[11px] font-bold text-muted pointer-events-none select-none">ekor</span>
                            </div>
                        </div>
                    </div>
                    <div>
                        <label class="lbl text-[11px]" for="konsumsi">
                            <span>Konsumsi / ekor / hari</span>
                            <span class="ml-1.5 px-2 py-0.5 rounded-full text-[10px] font-bold bg-surface-2 text-muted border border-line">estimasi</span>
                        </label>
                        <div class="relative flex items-center">
                            <input id="konsumsi" 
                                   name="konsumsi" 
                                   type="number" 
                                   min="0.01" 
                                   max="1" 
                                   step="0.005" 
                                   inputmode="decimal" 
                                   class="field !pr-12 font-semibold text-ink" 
                                   value="{{ old('konsumsi', (float) $konsumsi) }}">
                            <span class="absolute right-3 text-[11px] font-bold text-muted pointer-events-none select-none">kg</span>
                        </div>
                    </div>
                    <div class="rounded-2xl bg-brand/5 border border-brand/20 p-3 flex items-center justify-between">
                        <span class="text-xs text-ink font-semibold">Perkiraan total:</span>
                        <span class="text-sm font-extrabold text-brand"><strong id="total_hari" class="font-black text-base">0</strong> kg</span>
                    </div>
                </div>
            </section>

            {{-- 2. Acuan Gizi Target --}}
            <section class="card space-y-3.5 p-4 sm:p-5">
                <h2 class="text-h-sm font-bold text-ink">Acuan Gizi Target</h2>

                @include('partials.dropdown-pilih-standar', ['standards' => $standards, 'value' => old('standard_id')])

                <div class="space-y-1.5 pt-1">
                    @foreach ($standards as $s)
                        <details class="text-xs text-muted rounded-xl bg-surface/50 border border-line/60 p-2.5 group">
                            <summary class="cursor-pointer font-bold text-ink hover:text-brand flex items-center justify-between select-none py-0.5">
                                <span>Lihat Rincian Target Nutrisi SNI</span>
                                <span class="material-symbols-outlined text-[16px] text-muted group-open:rotate-180 transition-transform">expand_more</span>
                            </summary>
                            <ul class="mt-2 space-y-1 border-t border-line/50 pt-2 text-[11px]">
                                <li class="flex justify-between"><span>Protein kasar:</span> <strong class="text-ink">min {{ Fmt::num($s->protein_min, 2) }}%</strong></li>
                                <li class="flex justify-between"><span>Kalsium (Ca):</span> <strong class="text-ink">{{ Fmt::num($s->kalsium_min, 2) }}–{{ Fmt::num($s->kalsium_max, 2) }}%</strong></li>
                                <li class="flex justify-between"><span>Fosfor (P):</span> <strong class="text-ink">{{ Fmt::num($s->fosfor_min, 2) }}–{{ Fmt::num($s->fosfor_max, 2) }}%</strong></li>
                                <li class="flex justify-between"><span>Energi Metabolis:</span> <strong class="text-ink">min {{ Fmt::num($s->energi_min, 0) }} kkal/kg</strong></li>
                                @if ($s->serat_max !== null)
                                    <li class="flex justify-between"><span>Serat kasar:</span> <strong class="text-ink">maks {{ Fmt::num($s->serat_max, 2) }}%</strong></li>
                                @endif
                            </ul>
                        </details>
                    @endforeach
                </div>

                <a href="{{ route('acuan.index') }}" class="inline-flex items-center gap-1.5 text-xs text-muted hover:text-ink font-bold hover:underline pt-1">
                    <span>Kelola pustaka acuan gizi</span>
                    <span class="material-symbols-outlined text-[15px]">arrow_forward</span>
                </a>
            </section>

            {{-- 3. Tombol Hitung Ransum (Murni tanpa card / background tambahan) --}}
            <div class="pt-2 space-y-2">
                <button id="btn-hitung-ransum" 
                        type="submit" 
                        class="w-full py-3.5 px-5 rounded-full bg-brand hover:bg-brand-dark text-white font-extrabold text-sm shadow-md hover:shadow-brand/25 active:scale-[0.98] transition-all flex items-center justify-center gap-2 cursor-pointer">
                    <span>Hitung Formula Ransum</span>
                </button>
                <p class="text-center text-[11px] text-muted">
                    Mencari kombinasi bahan termurah yang memenuhi 100% batasan nutrisi.
                </p>
            </div>
        </div>
    </div>
</form>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const checkboxes = document.querySelectorAll('[data-racik-item] input[type="checkbox"]');

        const updateCardState = (cb) => {
            const card = cb.closest('[data-racik-item]');
            if (!card) return;
            if (cb.checked) {
                card.classList.remove('bg-surface/40', 'border-line/60', 'opacity-60');
                card.classList.add('bg-white', 'border-brand/30', 'shadow-2xs', 'ring-1', 'ring-brand/10');
            } else {
                card.classList.add('bg-surface/40', 'border-line/60', 'opacity-60');
                card.classList.remove('bg-white', 'border-brand/30', 'shadow-2xs', 'ring-1', 'ring-brand/10');
            }
        };

        checkboxes.forEach((cb) => {
            cb.addEventListener('change', () => {
                updateCardState(cb);
            });
        });
    });
</script>
@endsection
