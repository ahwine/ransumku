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
