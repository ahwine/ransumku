@extends('layouts.app')
@section('judul', 'Acuan Gizi')
@section('isi')
@use('App\Support\Fmt')

<div class="space-y-5">
    {{-- Header Halaman Bersih & Humanis --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-lg sm:text-xl font-bold text-ink tracking-tight">Acuan Gizi</h1>
            <p class="text-xs text-muted mt-0.5">Standar target nutrisi kebutuhan ayam petelur untuk formula ransum berimbang.</p>
        </div>

        <a href="{{ route('acuan.create') }}" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-full bg-brand hover:bg-brand-dark text-white text-xs font-bold shadow-2xs hover:shadow-xs transition active:scale-95 cursor-pointer">
            <span class="material-symbols-outlined text-[17px]">add</span>
            <span>Tambah Acuan</span>
        </a>
    </div>

    {{-- Baris Ringkasan KPI Acuan Gizi (Cockpit Minimalis & Proporsional) --}}
    @php
        $totalAcuan = $acuan->count();
        $avgProtein = $totalAcuan > 0 ? $acuan->avg('protein_min') : 0;
        $avgEnergi = $totalAcuan > 0 ? $acuan->avg('energi_min') : 0;
    @endphp
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3.5">
        {{-- Kartu 1: Standar Terdaftar --}}
        <div class="card p-4 flex flex-col justify-between space-y-1.5 shadow-2xs hover:shadow-xs transition">
            <span class="text-[11px] font-semibold text-muted uppercase tracking-wider">Standar Terdaftar</span>
            <div>
                <div class="text-base sm:text-lg font-bold text-ink">
                    {{ $totalAcuan }} <span class="text-xs font-normal text-muted">Acuan Gizi</span>
                </div>
                <p class="text-[11px] text-muted mt-0.5">Pedoman acuan nutrisi yang aktif digunakan</p>
            </div>
        </div>

        {{-- Kartu 2: Target Protein Minimum Rata-rata --}}
        <div class="card p-4 flex flex-col justify-between space-y-1.5 shadow-2xs hover:shadow-xs transition">
            <span class="text-[11px] font-semibold text-muted uppercase tracking-wider">Target Protein (PK)</span>
            <div>
                <div class="text-base sm:text-lg font-bold text-ink">
                    Min {{ Fmt::num($avgProtein, 1) }}%
                </div>
                <p class="text-[11px] text-muted mt-0.5">Rata-rata ambang protein kasar minimal</p>
            </div>
        </div>

        {{-- Kartu 3: Target Energi Metabolis Rata-rata --}}
        <div class="card p-4 flex flex-col justify-between space-y-1.5 shadow-2xs hover:shadow-xs transition">
            <span class="text-[11px] font-semibold text-muted uppercase tracking-wider">Target Energi (EM)</span>
            <div>
                <div class="text-base sm:text-lg font-bold text-ink">
                    Min {{ Fmt::num($avgEnergi, 0) }} <span class="text-xs font-normal text-muted">kkal/kg</span>
                </div>
                <p class="text-[11px] text-muted mt-0.5">Rata-rata ambang energi metabolis minimal</p>
            </div>
        </div>
    </div>

    {{-- Subheader Bersih --}}
    <div class="space-y-3.5">
        <div class="flex items-center justify-between pb-1 border-b border-line/60">
            <span class="text-xs font-bold text-muted uppercase tracking-wider">
                Daftar Acuan Gizi ({{ $totalAcuan }})
            </span>
        </div>

        {{-- Grid Kartu Acuan Gizi --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            @forelse ($acuan as $s)
                <div class="bg-white rounded-2xl border border-line p-4 sm:p-5 shadow-2xs hover:shadow-xs transition flex flex-col justify-between space-y-3">
                    <div class="space-y-3">
                        {{-- Baris Atas: Nama Acuan & Pill Tags --}}
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <h2 class="text-base font-bold text-ink tracking-tight truncate">
                                    {{ $s->nama }}
                                </h2>
                                <div class="flex items-center gap-1.5 flex-wrap mt-1">
                                    @if ($s->tahun)
                                        <span class="px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                            Tahun {{ $s->tahun }}
                                        </span>
                                    @endif
                                    @if ($s->sumber)
                                        <span class="px-2.5 py-0.5 rounded-full text-[11px] font-medium bg-surface text-muted border border-line truncate max-w-[320px]" title="{{ $s->sumber }}">
                                            {{ $s->sumber }}
                                        </span>
                                    @else
                                        <span class="px-2.5 py-0.5 rounded-full text-[11px] font-medium bg-surface text-muted border border-line">
                                            Standar Mandiri
                                        </span>
                                    @endif
                                    @if ($s->serat_max !== null)
                                        <span class="px-2.5 py-0.5 rounded-full text-[11px] font-medium bg-surface text-muted border border-line">
                                            Serat Maks {{ Fmt::num($s->serat_max, 1) }}%
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        {{-- Strip 4 Metrik Terbagi (Divided Metric Strip) --}}
                        <div class="grid grid-cols-2 sm:grid-cols-4 divide-x divide-line rounded-lg border border-line/60 bg-surface/30 py-2 text-center">
                            <div class="px-1">
                                <span class="text-[10px] font-semibold text-muted uppercase tracking-wider block">Protein (PK)</span>
                                <span class="text-xs sm:text-sm font-bold text-ink mt-0.5 block">
                                    Min {{ Fmt::num($s->protein_min, 1) }}%
                                </span>
                            </div>
                            <div class="px-1">
                                <span class="text-[10px] font-semibold text-muted uppercase tracking-wider block">Energi (EM)</span>
                                <span class="text-xs sm:text-sm font-bold text-ink mt-0.5 block truncate">
                                    Min {{ Fmt::num($s->energi_min, 0) }} <span class="text-[10px] font-normal text-muted">kkal</span>
                                </span>
                            </div>
                            <div class="px-1">
                                <span class="text-[10px] font-semibold text-muted uppercase tracking-wider block">Kalsium (Ca)</span>
                                <span class="text-xs sm:text-sm font-bold text-ink mt-0.5 block">
                                    {{ Fmt::num($s->kalsium_min, 2) }}–{{ Fmt::num($s->kalsium_max, 2) }}%
                                </span>
                            </div>
                            <div class="px-1">
                                <span class="text-[10px] font-semibold text-muted uppercase tracking-wider block">Fosfor (P)</span>
                                <span class="text-xs sm:text-sm font-bold text-ink mt-0.5 block">
                                    {{ Fmt::num($s->fosfor_min, 2) }}–{{ Fmt::num($s->fosfor_max, 2) }}%
                                </span>
                            </div>
                        </div>
                    </div>

                    {{-- Baris Bawah: Info & Tombol Aksi Pill --}}
                    <div class="pt-2.5 border-t border-line flex items-center justify-between gap-2">
                        <div class="min-w-0 pr-2">
                            @if ($s->serat_max !== null)
                                <span class="text-[11px] text-muted truncate block">
                                    Batas aman serat pencernaan ayam: maks {{ Fmt::num($s->serat_max, 1) }}%
                                </span>
                            @else
                                <span class="text-[11px] text-muted truncate block">
                                    Standar nutrisi siap diterapkan pada formula ransum
                                </span>
                            @endif
                        </div>

                        <div class="flex items-center gap-1.5 shrink-0">
                            {{-- Tombol Edit Berbentuk Pill Badge --}}
                            <a href="{{ route('acuan.edit', $s) }}" class="inline-flex items-center gap-1 px-3.5 py-1.5 rounded-full text-xs font-semibold text-ink bg-surface hover:bg-surface-2 border border-line transition cursor-pointer shadow-2xs hover:shadow-xs" aria-label="Ubah Acuan Gizi">
                                <span class="material-symbols-outlined text-[15px] text-muted">edit</span>
                                <span>Edit</span>
                            </a>

                            {{-- Tombol Hapus Berbentuk Pill Badge (Hanya jika > 1) --}}
                            @if ($totalAcuan > 1)
                                <form method="POST" action="{{ route('acuan.destroy', $s) }}" data-confirm="Apakah Anda yakin ingin menghapus acuan standar gizi '{{ $s->nama }}'?" data-confirm-title="Hapus Acuan Gizi" data-confirm-btn="Ya, Hapus Acuan">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="inline-flex items-center gap-1 px-3 py-1.5 rounded-full text-xs font-semibold text-rose-600 hover:text-rose-700 bg-rose-50/60 hover:bg-rose-100/70 border border-rose-200/70 transition cursor-pointer shadow-2xs" aria-label="Hapus Acuan Gizi">
                                        <span class="material-symbols-outlined text-[15px]">delete</span>
                                        <span>Hapus</span>
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-span-full bg-white rounded-2xl border border-line text-center py-10 text-muted space-y-2">
                    <span class="material-symbols-outlined text-[36px] text-muted/50 block">fact_check</span>
                    <p class="font-bold text-ink text-sm">Belum Ada Acuan Gizi</p>
                    <p class="text-xs text-muted">Klik tombol "Tambah Acuan" di atas untuk menambahkan standar acuan nutrisi baru.</p>
                </div>
            @endforelse
        </div>
    </div>
</div>
@endsection

