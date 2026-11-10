@extends('layouts.app')
@section('judul', 'Kelola Kandang')
@section('isi')

<div class="space-y-5">
    {{-- Header Halaman Bersih & Humanis --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-xl sm:text-2xl font-black text-ink tracking-tight">Kelola Kandang</h1>
            <p class="text-xs text-muted mt-0.5">Pusat kendali unit kandang peternakan. Atur populasi ayam, umur pemeliharaan, dan beralih kandang aktif.</p>
        </div>

        <button id="btn-tambah-kandang-top" type="button" onclick="bukaModal('modal-tambah-kandang')" class="inline-flex items-center gap-1.5 px-5 py-2.5 rounded-full bg-brand hover:bg-brand-dark text-white text-xs font-bold shadow-xs hover:shadow-brand/20 transition active:scale-95 cursor-pointer">
            <span class="material-symbols-outlined text-[18px]">add</span>
            <span>Tambah Kandang</span>
        </button>
    </div>

    {{-- Daftar Kandang Terdaftar --}}
    <div class="space-y-3.5">
        <div class="grid grid-cols-1 md:grid-cols-2 {{ $farms->count() >= 3 ? 'lg:grid-cols-3' : '' }} gap-4">
            @foreach ($farms as $f)
                @php $isActive = ($activeFarm && $activeFarm->id === $f->id); @endphp
                {{-- Card Kandang: Warna Putih Bersih, Proporsional & Tanpa Pill Badges --}}
                <div class="bg-white rounded-2xl border {{ $isActive ? 'border-brand/40 shadow-xs' : 'border-line shadow-2xs' }} hover:shadow-xs transition p-5 flex flex-col justify-between space-y-4">
                    <div class="space-y-3.5">
                        {{-- Baris Atas Card: Nama Kandang & Status Aktif (Teks Murni Tanpa Pill Badge) --}}
                        <div class="flex items-start justify-between gap-2 pb-3 border-b border-line/60">
                            <div class="min-w-0">
                                <h2 class="text-base font-bold text-ink tracking-tight truncate">
                                    {{ $f->nama }}
                                </h2>
                                <p class="text-xs text-muted mt-0.5">
                                    {{ $f->logs_count }} catatan harian · {{ $f->rations_count }} resep ransum
                                </p>
                            </div>

                            @if ($isActive)
                                <span class="text-xs font-bold text-emerald-600 shrink-0">
                                    Kandang Aktif
                                </span>
                            @else
                                <span class="text-xs font-semibold text-muted shrink-0">
                                    Non-Aktif
                                </span>
                            @endif
                        </div>

                        {{-- Grid Metrik Terstruktur (Divided Metrics) --}}
                        <div class="grid grid-cols-3 divide-x divide-line rounded-xl border border-line/70 bg-surface/30 py-2.5 text-center">
                            <div class="px-2">
                                <span class="text-[10px] font-semibold text-muted uppercase tracking-wider block">Populasi</span>
                                <span class="text-xs sm:text-sm font-bold text-ink mt-0.5 block truncate">
                                    {{ number_format($f->jumlah_ayam, 0, ',', '.') }} Ekor
                                </span>
                            </div>
                            <div class="px-2">
                                <span class="text-[10px] font-semibold text-muted uppercase tracking-wider block">Umur Ayam</span>
                                <span class="text-xs sm:text-sm font-bold text-brand mt-0.5 block">
                                    {{ $f->umurMinggu() }} Minggu
                                </span>
                            </div>
                            <div class="px-2">
                                <span class="text-[10px] font-semibold text-muted uppercase tracking-wider block">Sentra</span>
                                <span class="text-xs sm:text-sm font-bold text-ink mt-0.5 block truncate" title="{{ $f->lokasi_kota ?? 'Surabaya' }}">
                                    {{ $f->lokasi_kota ?? 'Surabaya' }}
                                </span>
                            </div>
                        </div>

                        {{-- Metadata Ringkas: Fase Produksi & Bibit (Teks Bersih Tanpa Pill Badges) --}}
                        <div class="flex items-center justify-between gap-2 text-xs text-muted pt-0.5">
                            <span>
                                Fase: <strong class="text-ink font-semibold">{{ $f->faseUmurDetail()['nama'] }}</strong>
                            </span>
                            <span>
                                Bibit: <strong class="text-ink font-semibold">{{ $f->strain_ayam ?? 'Lohmann Brown' }}</strong>
                            </span>
                        </div>
                    </div>

                    {{-- Card Footer / Action Buttons --}}
                    <div class="pt-3 border-t border-line/60 flex items-center justify-between gap-2">
                        <div class="flex items-center gap-2">
                            <button id="btn-edit-kandang-{{ $f->id }}" type="button" onclick="bukaModal('modal-edit-kandang-{{ $f->id }}')" class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full text-xs font-semibold text-ink bg-white hover:bg-surface border border-line transition cursor-pointer shadow-2xs" title="Edit Data Kandang">
                                <span class="material-symbols-outlined text-[15px] text-muted">edit</span>
                                <span>Edit Kandang</span>
                            </button>

                            @if ($farms->count() > 1)
                                <form method="POST" action="{{ route('kandang.destroy', $f) }}" data-confirm="Apakah Anda yakin ingin menghapus kandang '{{ $f->nama }}'?" data-confirm-title="Hapus Kandang" data-confirm-sub="Seluruh rekaman catatan dan formula pakan di kandang ini akan terhapus permanen." data-confirm-btn="Ya, Hapus Kandang">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="inline-flex items-center gap-1 px-3 py-1.5 rounded-full text-xs font-semibold text-rose-600 hover:text-rose-700 bg-white hover:bg-rose-50 border border-rose-200 transition cursor-pointer shadow-2xs" title="Hapus Kandang">
                                        <span class="material-symbols-outlined text-[15px]">delete</span>
                                        <span>Hapus</span>
                                    </button>
                                </form>
                            @else
                                <button type="button" disabled class="inline-flex items-center gap-1 px-3 py-1.5 rounded-full text-xs font-semibold text-muted/60 bg-surface/50 border border-line/60 cursor-not-allowed" title="Minimal harus ada 1 kandang aktif di akun Anda">
                                    <span class="material-symbols-outlined text-[15px]">delete</span>
                                    <span>Hapus</span>
                                </button>
                            @endif
                        </div>

                        <div>
                            @if ($isActive)
                                <a href="{{ route('beranda') }}" class="inline-flex items-center gap-1.5 px-4 py-1.5 rounded-full text-xs font-bold text-white bg-brand hover:bg-brand-dark transition shadow-2xs">
                                    <span>Buka Dashboard</span>
                                    <span class="material-symbols-outlined text-[15px]">arrow_forward</span>
                                </a>
                            @else
                                <form method="POST" action="{{ route('kandang.switch') }}">
                                    @csrf
                                    <input type="hidden" name="farm_id" value="{{ $f->id }}">
                                    <button type="submit" class="inline-flex items-center gap-1.5 px-4 py-1.5 rounded-full text-xs font-bold text-ink bg-surface hover:bg-brand hover:text-white border border-line transition shadow-2xs cursor-pointer">
                                        <span class="material-symbols-outlined text-[15px]">swap_horiz</span>
                                        <span>Aktifkan Kandang</span>
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
