@extends('layouts.app')
@section('judul', 'Acuan Gizi')
@section('isi')
@use('App\Support\Fmt')

<div class="flex items-center justify-between">
    <div>
        <h1 class="text-h-xl">Acuan Gizi</h1>
        <p class="text-muted text-sm">Standar nutrisi target kebutuhan ayam petelur</p>
    </div>
    <a href="{{ route('acuan.create') }}" class="btn-primary btn-sm">
        <span class="material-symbols-outlined">add</span>Tambah
    </a>
</div>

<div class="space-y-3">
    @foreach ($acuan as $s)
        <div class="card space-y-2">
            <div class="flex items-start justify-between">
                <div>
                    <h2 class="text-h-sm text-ink">{{ $s->nama }}</h2>
                    <p class="text-xs text-muted">{{ $s->tahun ? 'Tahun: '.$s->tahun.' · ' : '' }}Sumber: {{ $s->sumber ?: 'Standar mandiri' }}</p>
                </div>
                <div class="flex items-center gap-2">
                    <a href="{{ route('acuan.edit', $s) }}" class="btn-outline btn-sm !min-h-9 !px-3" aria-label="Ubah">
                        <span class="material-symbols-outlined text-[18px]">edit</span>
                    </a>
                    @if ($acuan->count() > 1)
                        <form method="POST" action="{{ route('acuan.destroy', $s) }}" data-confirm="Hapus acuan standar gizi '{{ $s->nama }}'?" data-confirm-title="Hapus Acuan Gizi" data-confirm-btn="Ya, Hapus Acuan">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm !min-h-9 !px-3 border border-line text-danger hover:bg-danger/5" aria-label="Hapus">
                                <span class="material-symbols-outlined text-[18px]">delete</span>
                            </button>
                        </form>
                    @endif
                </div>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 text-xs pt-1">
                <div class="p-2 rounded-lg bg-surface">
                    <span class="block text-muted">Protein Kasar</span>
                    <strong class="text-sm">Min {{ Fmt::num($s->protein_min, 1) }}%</strong>
                </div>
                <div class="p-2 rounded-lg bg-surface">
                    <span class="block text-muted">Energi Metabolis</span>
                    <strong class="text-sm">Min {{ Fmt::num($s->energi_min, 0) }} kkal</strong>
                </div>
                <div class="p-2 rounded-lg bg-surface">
                    <span class="block text-muted">Kalsium</span>
                    <strong class="text-sm">{{ Fmt::num($s->kalsium_min, 2) }} – {{ Fmt::num($s->kalsium_max, 2) }}%</strong>
                </div>
                <div class="p-2 rounded-lg bg-surface">
                    <span class="block text-muted">Fosfor Tersedia</span>
                    <strong class="text-sm">{{ Fmt::num($s->fosfor_min, 2) }} – {{ Fmt::num($s->fosfor_max, 2) }}%</strong>
                </div>
            </div>

            @if ($s->serat_max !== null)
                <div class="text-xs text-muted pt-1">
                    Batas serat kasar: <strong>Maks {{ Fmt::num($s->serat_max, 1) }}%</strong>
                </div>
            @endif
        </div>
    @endforeach
</div>
@endsection
