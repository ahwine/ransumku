@extends('layouts.app')
@section('judul', 'Acuan Gizi')
@section('isi')
@use('App\Support\Fmt')

@php
    $katalogMap = collect(\App\Services\NutrientStandardService::officialStandards())->keyBy('kode');
@endphp

<div class="space-y-5">
    {{-- 1. Header Bersih & Minimalis (Tanpa Pill Badge Tambahan) --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div>
            <h1 class="text-lg sm:text-xl font-bold text-ink tracking-tight">Acuan Gizi</h1>
            <p class="text-xs text-muted mt-0.5">
                Katalog baku standar kebutuhan nutrisi ayam petelur bersumber resmi dari SNI (BSN) dan panduan bibit komersial.
            </p>
        </div>

        {{-- Aksi Sinkronisasi Sederhana --}}
        <div class="shrink-0">
            <form method="POST" action="{{ route('acuan.sync') }}">
                @csrf
                <button type="submit" class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full bg-white hover:bg-surface border border-line text-xs font-semibold text-muted hover:text-ink shadow-2xs transition active:scale-95 cursor-pointer" title="Periksa dan sinkronkan standar resmi">
                    <span class="material-symbols-outlined text-[15px] text-brand">sync</span>
                    <span>Sinkronkan Standar</span>
                </button>
            </form>
        </div>
    </div>

    {{-- 2. Baris Filter Kategori & Pencarian --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        {{-- Filter Tab Sederhana --}}
        <div class="flex items-center gap-1.5 overflow-x-auto pb-1 sm:pb-0 scrollbar-none">
            <button type="button" onclick="filterTabStandar('semua', this)" class="btn-filter-tab px-3.5 py-1.5 rounded-full text-xs font-bold bg-brand text-white shadow-2xs transition active:scale-95 cursor-pointer" data-filter="semua">
                Semua ({{ $acuan->count() }})
            </button>
            <button type="button" onclick="filterTabStandar('produksi', this)" class="btn-filter-tab px-3.5 py-1.5 rounded-full text-xs font-medium bg-white hover:bg-surface text-ink border border-line transition active:scale-95 cursor-pointer" data-filter="produksi">
                Masa Bertelur (Layer)
            </button>
            <button type="button" onclick="filterTabStandar('pembesaran', this)" class="btn-filter-tab px-3.5 py-1.5 rounded-full text-xs font-medium bg-white hover:bg-surface text-ink border border-line transition active:scale-95 cursor-pointer" data-filter="pembesaran">
                Pembesaran (Grower & Starter)
            </button>
            <button type="button" onclick="filterTabStandar('strain', this)" class="btn-filter-tab px-3.5 py-1.5 rounded-full text-xs font-medium bg-white hover:bg-surface text-ink border border-line transition active:scale-95 cursor-pointer" data-filter="strain">
                Khusus Strain (Lohmann & Isa)
            </button>
        </div>

        {{-- Input Pencarian --}}
        <div class="relative w-full sm:w-60 shrink-0">
            <span class="material-symbols-outlined absolute left-3 top-2 text-[16px] text-muted pointer-events-none">search</span>
            <input type="text" 
                   id="input-cari-standar" 
                   oninput="cariKatalogStandar(this.value)" 
                   placeholder="Cari acuan gizi..." 
                   class="w-full pl-8 pr-3 py-1.5 bg-white border border-line rounded-full text-xs text-ink placeholder:text-muted focus:outline-none focus:border-brand focus:ring-1 focus:ring-brand/20 transition shadow-2xs">
        </div>
    </div>

    {{-- 3. Grid Kartu Acuan Standar Gizi (Desain Sederhana, Lapang & Modern) --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4" id="grid-katalog-standar">
        @foreach ($acuan as $s)
            @php
                $meta = $katalogMap->get($s->kode);
                $deskripsi = $meta['deskripsi'] ?? $s->sumber;
                $kategoriFase = $s->kategori_fase ?? 'produksi';
                $isStrain = in_array($s->kode, ['LOHMANN_PEAK', 'ISA_BROWN']);
                $faseFilter = ($kategoriFase === 'starter' || $kategoriFase === 'grower') ? 'pembesaran' : 'produksi';
                if ($isStrain) $faseFilter = 'strain';
            @endphp
            <div class="kartu-standar bg-white rounded-2xl border border-line p-5 shadow-2xs hover:shadow-xs transition space-y-4 flex flex-col justify-between"
                 data-nama="{{ strtolower($s->nama . ' ' . $s->organisasi_sumber . ' ' . $s->sumber . ' ' . $kategoriFase) }}"
                 data-fase="{{ $faseFilter }}">
                
                <div class="space-y-3">
                    {{-- Header Kartu: Nama & Badge Otoritas --}}
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0 flex-1">
                            <h2 class="text-sm sm:text-base font-bold text-ink tracking-tight leading-snug">
                                {{ $s->nama }}
                            </h2>
                            <p class="text-xs text-muted mt-1 leading-relaxed">
                                {{ $deskripsi }}
                            </p>
                        </div>

                        {{-- Badge Otoritas --}}
                        <span class="px-2.5 py-1 rounded-lg text-[11px] font-bold border {{ $s->badgeClass() }} shrink-0">
                            {{ $s->organisasi_sumber ?? 'BSN' }}
                        </span>
                    </div>

                    {{-- 4 Nilai Nutrisi Utama: Grid Minimalis & Proporsional --}}
                    <div class="grid grid-cols-2 sm:grid-cols-4 divide-y sm:divide-y-0 sm:divide-x divide-line rounded-xl border border-line/70 bg-surface/40 py-2.5 text-center">
                        <div class="p-1">
                            <span class="text-[10px] font-semibold text-muted uppercase tracking-wider block">Protein (PK)</span>
                            <span class="text-xs sm:text-sm font-bold text-ink mt-0.5 block">
                                Min {{ Fmt::num($s->protein_min, 1) }}%
                            </span>
                        </div>
                        <div class="p-1">
                            <span class="text-[10px] font-semibold text-muted uppercase tracking-wider block">Energi (EM)</span>
                            <span class="text-xs sm:text-sm font-bold text-ink mt-0.5 block truncate">
                                Min {{ Fmt::num($s->energi_min, 0) }} <span class="text-[10px] font-normal text-muted">kkal</span>
                            </span>
                        </div>
                        <div class="p-1">
                            <span class="text-[10px] font-semibold text-muted uppercase tracking-wider block">Kalsium (Ca)</span>
                            <span class="text-xs sm:text-sm font-bold text-ink mt-0.5 block">
                                {{ Fmt::num($s->kalsium_min, 2) }}–{{ Fmt::num($s->kalsium_max, 2) }}%
                            </span>
                        </div>
                        <div class="p-1">
                            <span class="text-[10px] font-semibold text-muted uppercase tracking-wider block">Fosfor (P)</span>
                            <span class="text-xs sm:text-sm font-bold text-ink mt-0.5 block">
                                {{ Fmt::num($s->fosfor_min, 2) }}–{{ Fmt::num($s->fosfor_max, 2) }}%
                            </span>
                        </div>
                    </div>
                </div>

                {{-- Footer Kartu: Serat Kasar & Kategori Fase --}}
                <div class="pt-2.5 border-t border-line/60 flex items-center justify-between text-xs text-muted">
                    <span>
                        Serat Kasar: <strong class="text-ink font-semibold">{{ $s->serat_max !== null ? 'Maks ' . Fmt::num($s->serat_max, 1) . '%' : 'Sesuai Standar Bahan' }}</strong>
                    </span>

                    <span class="text-[11px] text-muted">
                        Fase: <strong class="text-ink font-medium capitalize">{{ $s->kategori_fase === 'produksi' ? 'Masa Bertelur (Layer)' : ucfirst($s->kategori_fase) }}</strong>
                    </span>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Pesan Kosong Saat Filter Tidak Menemukan Hasil --}}
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

    // Update active tab style
    document.querySelectorAll('.btn-filter-tab').forEach(btn => {
        btn.classList.remove('bg-brand', 'text-white', 'font-bold');
        btn.classList.add('bg-white', 'text-ink', 'font-medium', 'border', 'border-line');
    });
    el.classList.remove('bg-white', 'text-ink', 'font-medium', 'border', 'border-line');
    el.classList.add('bg-brand', 'text-white', 'font-bold');

    terapkanFilterDanPencarian();
}

function cariKatalogStandar() {
    terapkanFilterDanPencarian();
}

function terapkanFilterDanPencarian() {
    const q = (document.getElementById('input-cari-standar')?.value || '').toLowerCase().trim();
    const kartuList = document.querySelectorAll('.kartu-standar');
    let visibleCount = 0;

    kartuList.forEach(card => {
        const text = card.getAttribute('data-nama') || '';
        const cardFase = card.getAttribute('data-fase') || '';

        const matchTab = (activeFilter === 'semua' || cardFase === activeFilter || (activeFilter === 'produksi' && cardFase === 'strain'));
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
