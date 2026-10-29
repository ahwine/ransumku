@extends('layouts.app')
@section('judul', 'Racik Ransum')
@section('isi')
@use('App\Support\Fmt')

<div>
    <h1 class="text-h-xl">Racik Ransum</h1>
    <p class="text-muted">Isi harga dan stok bahan, lalu tekan Hitung Ransum. Program mencari campuran termurah yang memenuhi target gizi.</p>
</div>

<form method="POST" action="{{ route('ransum.store') }}" class="space-y-4 pb-20 lg:pb-0">
    @csrf

    {{-- 1. Bahan --}}
    <section class="card space-y-3">
        <div class="flex items-center justify-between">
            <h2 class="text-h-sm">Bahan</h2>
            <span class="text-xs text-muted">Harga = input Anda</span>
        </div>
        @foreach ($fis as $fi)
            <div class="rounded-xl border border-line p-3 space-y-2 {{ $fi->aktif ? '' : 'bg-surface/60' }}">
                <label class="flex items-center gap-2 font-bold">
                    <input type="checkbox" name="bahan[{{ $fi->id }}][aktif]" value="1" class="w-5 h-5 accent-brand" @checked(old("bahan.{$fi->id}.aktif", $fi->aktif))>
                    <span>{{ $fi->ingredient->nama }}</span>
                    <span class="chip bg-brand/10 text-brand text-[11px] font-semibold">BK {{ Fmt::num($fi->ingredient->bk ?? 88, 0) }}%</span>
                    @if (($fi->max_persen ?? $fi->ingredient->max_persen ?? 100) < 100)
                        <span class="chip bg-amber-soft/60 text-amber-ink text-[11px] font-semibold">Maks {{ Fmt::num($fi->max_persen ?? $fi->ingredient->max_persen, 0) }}%</span>
                    @endif
                    <span class="ml-auto text-tiny font-bold {{ $fi->hargaUsang() ? 'text-danger' : 'text-muted' }}">
                        {{ $fi->harga_updated_at ? 'diperbarui '.$fi->harga_updated_at->diffForHumans() : 'belum pernah diperbarui' }}
                    </span>
                </label>
                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="lbl" for="h{{ $fi->id }}">Harga/kg (Rp)</label>
                        <input id="h{{ $fi->id }}" name="bahan[{{ $fi->id }}][harga]" type="number" min="0" step="50" inputmode="numeric" class="field" value="{{ old("bahan.{$fi->id}.harga", $fi->harga) }}">
                    </div>
                    <div>
                        <label class="lbl" for="s{{ $fi->id }}">Stok (kg, batas maks)</label>
                        <input id="s{{ $fi->id }}" name="bahan[{{ $fi->id }}][stok]" type="number" min="0" step="0.5" inputmode="decimal" class="field" value="{{ old("bahan.{$fi->id}.stok", (float) $fi->stok_kg) }}">
                    </div>
                </div>
                <details class="text-xs text-muted pt-0.5">
                    <summary class="cursor-pointer text-brand hover:underline font-semibold flex items-center gap-1">
                        <span class="material-symbols-outlined text-[15px]">tune</span>
                        <span>Batas Ambang Penggunaan (% Ransum)</span>
                    </summary>
                    <div class="grid grid-cols-2 gap-2 pt-2">
                        <div>
                            <label class="lbl !text-tiny" for="min{{ $fi->id }}">Min Penggunaan (%)</label>
                            <input id="min{{ $fi->id }}" name="bahan[{{ $fi->id }}][min_persen]" type="number" min="0" max="100" step="0.5" class="field !min-h-9 !py-1 text-xs" value="{{ old("bahan.{$fi->id}.min_persen", $fi->min_persen ?? $fi->ingredient->min_persen ?? 0) }}">
                        </div>
                        <div>
                            <label class="lbl !text-tiny" for="max{{ $fi->id }}">Maks Aman (%)</label>
                            <input id="max{{ $fi->id }}" name="bahan[{{ $fi->id }}][max_persen]" type="number" min="0" max="100" step="0.5" class="field !min-h-9 !py-1 text-xs" value="{{ old("bahan.{$fi->id}.max_persen", $fi->max_persen ?? $fi->ingredient->max_persen ?? 100) }}">
                        </div>
                    </div>
                    @if ($fi->ingredient->catatan_keamanan)
                        <p class="text-tiny text-muted mt-1 italic">{{ $fi->ingredient->catatan_keamanan }}</p>
                    @endif
                </details>
            </div>
        @endforeach
        <a href="{{ route('bahan.create') }}" class="btn-outline btn-sm w-full"><span class="material-symbols-outlined">add</span>Tambah bahan</a>
    </section>

    {{-- 2. Jumlah campuran --}}
    <section class="card space-y-3">
        <h2 class="text-h-sm">Jumlah campuran</h2>
        <div class="grid grid-cols-2 gap-1 p-1 rounded-full bg-surface-2">
            @foreach (['kg' => 'Langsung kg', 'hari' => 'Untuk berapa hari'] as $v => $l)
                <label class="cursor-pointer">
                    <input type="radio" name="mode" value="{{ $v }}" class="peer sr-only" @checked(old('mode', 'kg') === $v)>
                    <span class="block text-center py-2.5 rounded-full text-sm font-bold text-muted peer-checked:bg-brand peer-checked:text-white">{{ $l }}</span>
                </label>
            @endforeach
        </div>

        <div data-mode="kg">
            <label class="lbl" for="total_kg">Jumlah campuran (kg)</label>
            <input id="total_kg" name="total_kg" type="number" min="1" step="0.5" inputmode="decimal" class="field" value="{{ old('total_kg', 40) }}">
        </div>

        <div data-mode="hari" class="hidden space-y-3">
            <div class="grid grid-cols-2 gap-2">
                <div>
                    <label class="lbl" for="jumlah_hari">Jumlah hari</label>
                    <input id="jumlah_hari" name="jumlah_hari" type="number" min="1" inputmode="numeric" class="field" value="{{ old('jumlah_hari', 7) }}">
                </div>
                <div>
                    <label class="lbl" for="jumlah_ayam">Jumlah ayam</label>
                    <input id="jumlah_ayam" name="jumlah_ayam" type="number" min="1" inputmode="numeric" class="field" value="{{ old('jumlah_ayam', $farm->jumlah_ayam) }}">
                </div>
            </div>
            <div>
                <label class="lbl" for="konsumsi">Konsumsi per ekor per hari (kg) <span class="tag-est">perkiraan, dapat diubah</span></label>
                <input id="konsumsi" name="konsumsi" type="number" min="0.01" max="1" step="0.005" inputmode="decimal" class="field" value="{{ old('konsumsi', (float) $konsumsi) }}">
            </div>
            <p class="rounded-xl bg-brand/5 p-3 text-sm">Perkiraan total: <strong id="total_hari">0</strong> kg</p>
        </div>
    </section>

    {{-- 3. Acuan gizi --}}
    <section class="card space-y-3">
        <h2 class="text-h-sm">Acuan gizi target</h2>
        <select name="standard_id" id="standard_id" class="field">
            @foreach ($standards as $s)
                <option value="{{ $s->id }}" @selected((int) old('standard_id') === $s->id)>{{ $s->nama }}{{ $s->tahun ? ' ('.$s->tahun.')' : '' }}</option>
            @endforeach
        </select>
        @foreach ($standards as $s)
            <details class="text-sm text-muted">
                <summary class="cursor-pointer font-semibold text-brand">Lihat target: {{ $s->nama }}</summary>
                <ul class="mt-2 space-y-0.5">
                    <li>Protein kasar min {{ Fmt::num($s->protein_min, 2) }}%</li>
                    <li>Kalsium {{ Fmt::num($s->kalsium_min, 2) }}–{{ Fmt::num($s->kalsium_max, 2) }}%</li>
                    <li>Fosfor {{ Fmt::num($s->fosfor_min, 2) }}–{{ Fmt::num($s->fosfor_max, 2) }}%</li>
                    <li>Energi metabolis min {{ Fmt::num($s->energi_min, 0) }} kkal/kg</li>
                    @if ($s->serat_max !== null)<li>Serat kasar maks {{ Fmt::num($s->serat_max, 2) }}%</li>@endif
                </ul>
            </details>
        @endforeach
        <a href="{{ route('acuan.index') }}" class="inline-flex items-center gap-1 text-sm text-brand font-bold hover:underline">
            <span>Kelola acuan gizi</span>
            <span class="material-symbols-outlined text-[15px]">arrow_forward</span>
        </a>
    </section>

    <div class="fixed bottom-16 inset-x-0 z-30 px-4 pb-3 pt-2 bg-gradient-to-t from-canvas via-canvas/95 to-transparent lg:static lg:p-0 lg:bg-none">
        <button id="btn-hitung-ransum" class="btn-primary w-full max-w-lg mx-auto h-14"><span class="material-symbols-outlined">calculate</span>Hitung Ransum</button>
    </div>
</form>
@endsection
