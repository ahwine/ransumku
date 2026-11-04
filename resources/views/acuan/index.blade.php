@extends('layouts.app')
@section('judul', 'Acuan Gizi')
@section('isi')

@php
    $katalogMap = collect(\App\Services\NutrientStandardService::officialStandards())->keyBy('kode');
    $kodeStrain = ['LOHMANN_CLASSIC_1', 'LOHMANN_CLASSIC_2', 'LOHMANN_CLASSIC_3', 'ISA_BROWN'];

    $countVerified = $acuan->filter(fn ($item) => (bool) $item->verified)->count();
    $countUnverified = $acuan->filter(fn ($item) => ! $item->verified)->count();
    $countProduksi = $acuan->filter(fn ($item) => ($item->kategori_fase ?? 'produksi') === 'produksi')->count();
    $countPembesaran = $acuan->filter(fn ($item) => in_array($item->kategori_fase, ['pre-starter', 'starter', 'grower'], true))->count();
    $countStrain = $acuan->filter(fn ($item) => in_array($item->kode, $kodeStrain, true))->count();
@endphp

<div class="space-y-5">
    {{-- 1. Header Katalog --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div>
            <h1 class="text-lg sm:text-xl font-bold text-ink tracking-tight">Acuan Gizi Ayam Petelur</h1>
            <p class="text-xs text-muted mt-0.5">
                Katalog standar nutrisi ayam petelur beserta tahun edisi, sumber dokumen, dan status verifikasi angka.
            </p>
        </div>

        <div class="shrink-0">
            <form method="POST"
                  action="{{ route('acuan.sync') }}"
                  data-confirm="Sinkronkan katalog acuan gizi ke versi {{ $syncMetadata['versi'] }}? Entri katalog bawaan yang sudah dicabut akan dihapus, namun seluruh resep ransum tersimpan tetap aman."
                  data-confirm-title="Konfirmasi Sinkronisasi Katalog"
                  data-confirm-btn="Ya, Sinkronkan">
                @csrf
                <button type="submit" class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full bg-white hover:bg-surface border border-line text-xs font-semibold text-muted hover:text-ink shadow-2xs transition active:scale-95 cursor-pointer" title="Muat ulang katalog standar sesuai versi terbaru">
                    <span class="material-symbols-outlined text-[15px] text-brand">sync</span>
                    <span>Sinkronkan Katalog</span>
                </button>
            </form>
        </div>
    </div>

    @if (! empty($perluSinkron))
        <div class="p-3.5 rounded-2xl bg-amber-50 border border-amber-300 text-amber-900 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs">
            <div class="flex items-start gap-2.5 leading-relaxed">
                <span class="material-symbols-outlined text-[19px] text-amber-700 shrink-0 mt-0.5">sync_problem</span>
                <span>Tersedia pembaruan katalog acuan gizi (versi <strong>{{ $syncMetadata['versi'] }}</strong>). Data kandang Anda tidak diubah otomatis sebelum Anda menyetujui sinkronisasi. Resep ransum yang sudah tersimpan tetap aman.</span>
            </div>
            <form method="POST"
                  action="{{ route('acuan.sync') }}"
                  class="shrink-0"
                  data-confirm="Jalankan sinkronisasi katalog versi {{ $syncMetadata['versi'] }} sekarang? Ransum tersimpan tetap aman."
                  data-confirm-title="Sinkronkan Katalog"
                  data-confirm-btn="Ya, Sinkronkan">
                @csrf
                <button type="submit" class="px-3.5 py-1.5 rounded-full bg-amber-600 hover:bg-amber-700 text-white font-bold text-xs transition cursor-pointer">
                    Sinkronkan Sekarang
                </button>
            </form>
        </div>
    @endif

    {{-- 2. Baris Filter Kategori & Pencarian --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div class="flex items-center gap-1.5 overflow-x-auto pb-1 sm:pb-0 scrollbar-none">
            <button type="button" onclick="filterTabStandar('semua', this)" class="btn-filter-tab shrink-0 px-3.5 py-1.5 rounded-full text-xs font-bold bg-brand text-white shadow-2xs transition active:scale-95 cursor-pointer" data-filter="semua">
                Semua ({{ $acuan->count() }})
            </button>
            <button type="button" onclick="filterTabStandar('terverifikasi', this)" class="btn-filter-tab shrink-0 px-3.5 py-1.5 rounded-full text-xs font-medium bg-white hover:bg-surface text-ink border border-line transition active:scale-95 cursor-pointer" data-filter="terverifikasi">
                Terverifikasi ({{ $countVerified }})
            </button>
            <button type="button" onclick="filterTabStandar('belum_terverifikasi', this)" class="btn-filter-tab shrink-0 px-3.5 py-1.5 rounded-full text-xs font-medium bg-white hover:bg-surface text-ink border border-line transition active:scale-95 cursor-pointer" data-filter="belum_terverifikasi">
                Belum terverifikasi ({{ $countUnverified }})
            </button>
            <button type="button" onclick="filterTabStandar('produksi', this)" class="btn-filter-tab shrink-0 px-3.5 py-1.5 rounded-full text-xs font-medium bg-white hover:bg-surface text-ink border border-line transition active:scale-95 cursor-pointer" data-filter="produksi">
                Masa Bertelur ({{ $countProduksi }})
            </button>
            <button type="button" onclick="filterTabStandar('pembesaran', this)" class="btn-filter-tab shrink-0 px-3.5 py-1.5 rounded-full text-xs font-medium bg-white hover:bg-surface text-ink border border-line transition active:scale-95 cursor-pointer" data-filter="pembesaran">
                Dara & Starter ({{ $countPembesaran }})
            </button>
            <button type="button" onclick="filterTabStandar('strain', this)" class="btn-filter-tab shrink-0 px-3.5 py-1.5 rounded-full text-xs font-medium bg-white hover:bg-surface text-ink border border-line transition active:scale-95 cursor-pointer" data-filter="strain">
                Pedoman Strain ({{ $countStrain }})
            </button>
        </div>

        <div class="relative flex items-center w-full sm:w-64 shrink-0">
            <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-[17px] text-muted pointer-events-none select-none">search</span>
            <input type="text"
                   id="input-cari-standar"
                   oninput="cariKatalogStandar()"
                   placeholder="Cari SNI, strain, atau sumber..."
                   class="w-full h-8.5 pl-8.5 pr-7 bg-white border border-line rounded-full text-xs font-medium text-ink placeholder:text-muted focus:outline-none focus:border-brand focus:ring-1 focus:ring-brand/20 transition shadow-2xs">
            <button type="button" 
                    id="btn-clear-cari-standar"
                    onclick="resetCariStandar()" 
                    class="hidden absolute right-2.5 top-1/2 -translate-y-1/2 text-muted hover:text-ink cursor-pointer p-0.5 rounded-full hover:bg-surface transition" 
                    title="Hapus pencarian">
                <span class="material-symbols-outlined text-[15px]">close</span>
            </button>
        </div>
    </div>

    {{-- 3. Grid Kartu Acuan Standar Gizi --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4" id="grid-katalog-standar">
        @foreach ($acuan as $s)
            @php
                $meta = $katalogMap->get($s->kode);
                $deskripsi = $meta['deskripsi'] ?? null;
                $kategoriFase = $s->kategori_fase ?? 'produksi';
                $isStrain = in_array($s->kode, $kodeStrain, true);
                $sumberTeks = $s->source ?? $s->sumber ?? '—';
                $edisiTeks = $s->edition_year ?? ($s->tahun ? (string) $s->tahun : '—');
                $extraBadges = $s->ringkasanParameterTambahan();
                $isSekunder = $s->isSumberSekunder();
            @endphp
            <div class="kartu-standar bg-white rounded-lg sm:rounded-2xl border {{ $s->verified ? 'border-line' : 'border-amber-200/90' }} p-5 shadow-2xs hover:shadow-xs transition space-y-3.5 flex flex-col justify-between"
                 data-nama="{{ strtolower($s->nama . ' ' . $s->organisasi_sumber . ' ' . $sumberTeks . ' ' . $edisiTeks . ' ' . $kategoriFase . ' ' . ($isSekunder ? 'sumber sekunder ' : '') . ($deskripsi ?? '')) }}"
                 data-kategori-fase="{{ $kategoriFase }}"
                 data-verified="{{ $s->verified ? '1' : '0' }}"
                 data-strain="{{ $isStrain ? '1' : '0' }}">

                <div class="space-y-3">
                    {{-- Header Kartu: Nama, Otoritas & Status Verifikasi --}}
                    <div class="flex items-start justify-between gap-2.5">
                        <div class="min-w-0 flex-1">
                            <h2 class="text-sm sm:text-base font-bold text-ink tracking-tight leading-snug">
                                {{ $s->nama }}
                            </h2>
                            @if ($deskripsi)
                                <p class="text-xs text-muted mt-1 leading-relaxed">
                                    {{ $deskripsi }}
                                </p>
                            @endif
                        </div>

                        <div class="flex flex-col items-end gap-1 shrink-0">
                            <span class="px-2.5 py-0.5 rounded-lg text-[11px] font-bold border {{ $s->badgeClass() }}">
                                {{ $s->labelOtoritas() }}
                            </span>
                            @if ($s->verified)
                                <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-emerald-50 text-emerald-800 border border-emerald-200">
                                    Terverifikasi
                                </span>
                            @else
                                <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-amber-50 text-amber-800 border border-amber-300">
                                    Belum terverifikasi
                                </span>
                                @if ($isSekunder)
                                    <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-sky-50 text-sky-800 border border-sky-200">
                                        Sumber sekunder
                                    </span>
                                @endif
                            @endif
                        </div>
                    </div>

                    {{-- 4 Nilai Nutrisi Utama --}}
                    <div class="grid grid-cols-2 sm:grid-cols-4 divide-y sm:divide-y-0 sm:divide-x divide-line rounded-xl border border-line/70 bg-surface/40 py-2.5 text-center">
                        <div class="p-1.5">
                            <span class="text-[10px] font-semibold text-muted uppercase tracking-wider block">Protein (PK)</span>
                            <span class="text-xs font-bold {{ $s->protein_min !== null ? 'text-ink' : 'text-muted font-medium' }} mt-0.5 block">
                                {{ $s->formatProtein() }}
                            </span>
                        </div>
                        <div class="p-1.5">
                            <span class="text-[10px] font-semibold text-muted uppercase tracking-wider block">Energi (EM)</span>
                            <span class="text-xs font-bold {{ $s->energi_min !== null ? 'text-ink' : 'text-muted font-medium' }} mt-0.5 block">
                                {{ $s->formatEnergi() }}
                            </span>
                        </div>
                        <div class="p-1.5">
                            <span class="text-[10px] font-semibold text-muted uppercase tracking-wider block">Kalsium (Ca)</span>
                            <span class="text-xs font-bold {{ ($s->kalsium_min !== null || $s->kalsium_max !== null) ? 'text-ink' : 'text-muted font-medium' }} mt-0.5 block">
                                {{ $s->formatKalsium() }}
                            </span>
                        </div>
                        <div class="p-1.5">
                            <span class="text-[10px] font-semibold text-muted uppercase tracking-wider block">Fosfor (P)</span>
                            <span class="text-xs font-bold {{ ($s->fosfor_min !== null || $s->fosfor_max !== null) ? 'text-ink' : 'text-muted font-medium' }} mt-0.5 block">
                                {{ $s->formatFosfor() }}
                            </span>
                        </div>
                    </div>

                    {{-- Parameter Tambahan dari Sumber (Asam Amino, Proksimat, Asupan) --}}
                    @if (count($extraBadges) > 0)
                        <div class="flex flex-wrap gap-1.5">
                            @foreach ($extraBadges as $b)
                                <span class="px-2 py-0.5 rounded-md bg-surface text-[11px] font-medium text-ink border border-line/70">
                                    {{ $b }}
                                </span>
                            @endforeach
                        </div>
                    @endif

                    {{-- Informasi Edisi & Sumber Dokumen --}}
                    <div class="rounded-xl bg-surface/60 border border-line/60 px-3 py-2 text-[11px] text-muted space-y-0.5">
                        <div>
                            <span class="font-semibold text-ink">Edisi:</span> {{ $edisiTeks }}
                        </div>
                        <div class="leading-relaxed">
                            <span class="font-semibold text-ink">Sumber:</span> {{ $sumberTeks }}
                        </div>
                    </div>
                </div>

                {{-- Footer Kartu: Serat Kasar, Fase & Aksi Ubah Manual (khusus verified=false) --}}
                <div class="pt-2.5 border-t border-line/60 flex items-center justify-between flex-wrap gap-2 text-xs text-muted">
                    <div class="flex items-center gap-3 flex-wrap">
                        <span>
                            Serat Kasar: <strong class="text-ink font-semibold">{{ $s->formatSerat() }}</strong>
                        </span>
                        <span class="text-[11px] text-muted">
                            Fase: <strong class="text-ink font-medium">{{ $s->labelFase() }}</strong>
                        </span>
                    </div>

                    @if (! $s->verified)
                        <a href="{{ route('acuan.edit', $s) }}" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-amber-50 hover:bg-amber-100 text-amber-900 border border-amber-300 text-[11px] font-bold transition">
                            <span class="material-symbols-outlined text-[13px]">edit</span>
                            <span>Ubah Manual</span>
                        </a>
                    @endif
                </div>
            </div>
        @endforeach
    </div>

    <div id="standar-empty-state" class="hidden bg-white rounded-2xl border border-line text-center py-12 p-4 text-muted space-y-2">
        <span class="material-symbols-outlined text-[36px] text-muted/40 block">search_off</span>
        <p class="font-bold text-ink text-sm">Standar Acuan Tidak Ditemukan</p>
        <p class="text-xs text-muted">Coba ubah kata kunci pencarian atau pilih filter tab lain di atas.</p>
    </div>
</div>

<script>
let activeFilter = 'semua';

function filterTabStandar(fase, el) {
    activeFilter = fase;

    document.querySelectorAll('.btn-filter-tab').forEach(btn => {
        btn.classList.remove('bg-brand', 'text-white', 'font-bold');
        btn.classList.add('bg-white', 'text-ink', 'font-medium', 'border', 'border-line');
    });
    el.classList.remove('bg-white', 'text-ink', 'font-medium', 'border', 'border-line');
    el.classList.add('bg-brand', 'text-white', 'font-bold');

    terapkanFilterDanPencarian();
}

function cariKatalogStandar() {
    const input = document.getElementById('input-cari-standar');
    const btnClear = document.getElementById('btn-clear-cari-standar');
    if (btnClear) {
        btnClear.classList.toggle('hidden', !input || !input.value.trim());
    }
    terapkanFilterDanPencarian();
}

function resetCariStandar() {
    const input = document.getElementById('input-cari-standar');
    if (input) {
        input.value = '';
        input.focus();
    }
    cariKatalogStandar();
}

function terapkanFilterDanPencarian() {
    const q = (document.getElementById('input-cari-standar')?.value || '').toLowerCase().trim();
    const kartuList = document.querySelectorAll('.kartu-standar');
    const fasePembesaran = ['pre-starter', 'starter', 'grower'];
    let visibleCount = 0;

    kartuList.forEach(card => {
        const text = card.getAttribute('data-nama') || '';
        const cardFase = card.getAttribute('data-kategori-fase') || '';
        const isVerified = card.getAttribute('data-verified') === '1';
        const isStrain = card.getAttribute('data-strain') === '1';

        let matchTab = true;
        if (activeFilter === 'terverifikasi') {
            matchTab = isVerified;
        } else if (activeFilter === 'belum_terverifikasi') {
            matchTab = !isVerified;
        } else if (activeFilter === 'produksi') {
            matchTab = cardFase === 'produksi';
        } else if (activeFilter === 'pembesaran') {
            matchTab = fasePembesaran.includes(cardFase);
        } else if (activeFilter === 'strain') {
            matchTab = isStrain;
        }

        const matchSearch = (!q || text.includes(q));

        if (matchTab && matchSearch) {
            card.style.display = 'flex';
            visibleCount++;
        } else {
            card.style.display = 'none';
        }
    });

    const empty = document.getElementById('standar-empty-state');
    if (empty) {
        if (visibleCount === 0) {
            empty.classList.remove('hidden');
        } else {
            empty.classList.add('hidden');
        }
    }
}
</script>
@endsection
