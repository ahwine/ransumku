@extends('layouts.app')
@section('judul', 'Beranda')
@section('isi')
@use('App\Support\Fmt')

<div class="flex items-center justify-between pt-1">
    <div>
        <h1 class="text-h-xl">Halo, {{ \Illuminate\Support\Str::before(auth()->user()->name, ' ') }}</h1>
        <p class="text-muted flex items-center gap-1.5">
            <span class="material-symbols-outlined text-[16px] text-primary">storefront</span>
            {{ $farm->nama }} · <strong class="text-ink">{{ Fmt::num($farm->jumlah_ayam, 0) }} ayam</strong>
        </p>
    </div>
</div>

{{-- 1. Peringatan dini --}}
@foreach ($alerts as $alert)
    @include('partials.alert-card', ['alert' => $alert])
@endforeach

<div class="lg:grid lg:grid-cols-3 lg:gap-4 space-y-4 lg:space-y-0">
    {{-- 2. Tiga angka ringkas --}}
    <div class="card">
        <div class="flex items-center justify-between mb-2">
            <span class="material-symbols-outlined text-brand">payments</span><span class="tag-est">estimasi</span>
        </div>
        <p class="text-lbl text-muted">Biaya ransum terakhir</p>
        <p class="text-money">{{ $ration ? Fmt::rp($ration->biaya_per_kg) : '—' }}<span class="text-lbl text-muted font-normal">/kg</span></p>
        @unless ($ration)<p class="text-xs text-muted mt-1">Belum ada ransum. Tekan Hitung Ransum.</p>@endunless
    </div>
    <div class="card">
        <div class="flex items-center justify-between mb-2">
            <span class="material-symbols-outlined text-amber-ink">scale</span><span class="tag-est">estimasi</span>
        </div>
        <p class="text-lbl text-muted">Biaya pakan per kg telur</p>
        <p class="text-money">{{ $biayaKgTelur ? Fmt::rp($biayaKgTelur) : '—' }}</p>
    </div>
    <div class="card">
        <div class="flex items-center justify-between mb-2">
            <span class="material-symbols-outlined text-amber-ink">egg</span>
            @if ($hariIni && $farm->jumlah_ayam)<span class="chip bg-surface text-brand">{{ Fmt::num($hariIni->telur_butir / $farm->jumlah_ayam * 100, 0) }}% HD</span>@endif
        </div>
        <p class="text-lbl text-muted">Telur {{ $hariIni && $hariIni->tanggal->isToday() ? 'hari ini' : 'catatan terakhir' }}</p>
        <p class="text-money">{{ $hariIni ? Fmt::num($hariIni->telur_butir, 0) : '—' }} <span class="text-lbl text-muted font-normal">butir</span></p>
    </div>
</div>

{{-- 3. Tombol aksi besar --}}
<div class="grid grid-cols-2 gap-3">
    <a id="btn-hitung" href="{{ route('ransum.create') }}" class="btn-primary h-[54px]"><span class="material-symbols-outlined">calculate</span>Hitung Ransum</a>
    <a id="btn-catat" href="{{ route('catatan.index') }}" class="btn-amber h-[54px]"><span class="material-symbols-outlined">edit_note</span>Catat Hari Ini</a>
</div>

{{-- 4. Grafik 7 hari --}}
@if ($logs->count())
<div class="card">
    <h2 class="text-h-sm">Tren 7 Hari Terakhir</h2>
    <p class="text-xs text-muted mb-2">Produksi telur (butir) dan konsumsi pakan (kg)</p>
    @php
        $cfg = [
            'type' => 'bar', 'legend' => true,
            'labels' => $logs->map(fn ($l) => $l->tanggal->format('d/m'))->all(),
            'datasets' => [
                ['type' => 'bar', 'label' => 'Pakan (kg)', 'data' => $logs->pluck('pakan_kg')->map(fn ($v) => (float) $v)->all(), 'backgroundColor' => '#F2B134', 'borderRadius' => 6, 'yAxisID' => 'y'],
                ['type' => 'line', 'label' => 'Telur (butir)', 'data' => $logs->pluck('telur_butir')->all(), 'borderColor' => '#2F6B3A', 'backgroundColor' => '#2F6B3A', 'tension' => .35],
            ],
        ];
    @endphp
    <div class="h-44"><canvas data-chart='@json($cfg)' aria-label="Grafik 7 hari"></canvas></div>
</div>
@endif

{{-- 5. Pengingat harga usang --}}
@if ($hargaUsangHari)
    <a href="{{ route('ransum.create') }}" class="block rounded-2xl p-3.5 bg-amber-soft/40 hover:bg-amber-soft/60">
        <div class="flex items-center gap-3">
            <span class="w-9 h-9 rounded-full bg-amber/25 grid place-items-center text-amber-ink"><span class="material-symbols-outlined">update</span></span>
            <p class="flex-1 leading-snug">Harga bahan terakhir diperbarui <strong class="text-danger-ink">{{ $hargaUsangHari }} hari lalu</strong>.
                <span class="block text-lbl text-amber-ink font-bold">Perbarui harga sekarang</span></p>
            <span class="material-symbols-outlined text-muted">chevron_right</span>
        </div>
    </a>
@endif

<div class="card flex items-center gap-3">
    <span class="material-symbols-outlined text-primary">eco</span>
    <p class="text-lbl text-ink">Biaya yang tampil adalah <strong>estimasi</strong> dari harga yang Anda isi sendiri, bukan prediksi harga pasar.</p>
</div>
@endsection
