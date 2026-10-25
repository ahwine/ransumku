@extends('layouts.app')
@section('judul', 'Lainnya')
@section('isi')
<h1 class="text-h-xl">Lainnya</h1>

<div class="card !p-0 divide-y divide-line/70 overflow-hidden">
    @foreach ([
        ['bahan.index', 'grocery', 'Data Bahan', 'Nilai gizi & harga default'],
        ['acuan.index', 'rule', 'Acuan Gizi', 'Target kebutuhan gizi ayam'],
        ['ransum.index', 'history', 'Riwayat Ransum', 'Resep yang pernah dihitung'],
        ['tentang', 'info', 'Tentang & Batasan', 'Cara kerja dan keterbatasan alat'],
    ] as [$rute, $ikon, $judul, $ket])
        <a href="{{ route($rute) }}" class="flex items-center gap-3 p-4 hover:bg-surface">
            <span class="w-10 h-10 rounded-xl bg-surface-2 text-brand grid place-items-center"><span class="material-symbols-outlined">{{ $ikon }}</span></span>
            <span class="flex-1"><span class="block font-bold">{{ $judul }}</span><span class="block text-lbl text-muted">{{ $ket }}</span></span>
            <span class="material-symbols-outlined text-muted">chevron_right</span>
        </a>
    @endforeach
</div>

<div class="card space-y-3">
    <h2 class="text-h-sm">Kandang</h2>
    <p class="text-sm">{{ $farm->nama }} · {{ number_format($farm->jumlah_ayam, 0, ',', '.') }} ayam · fase {{ $farm->fase }}</p>
    <h2 class="text-h-sm pt-2">Pengaturan demo</h2>
    <p class="text-sm text-muted">Mengisi ulang akan <strong>menghapus</strong> catatan, ransum, dan pembelian kandang ini, lalu mengganti dengan data contoh (200 ayam, 14 hari catatan dengan 3 hari terakhir sengaja anomali).</p>
    <form method="POST" action="{{ route('demo.isi') }}" onsubmit="return confirm('Data kandang ini akan diganti dengan data demo. Lanjutkan?')">
        @csrf
        <button id="btn-demo" class="btn-outline w-full"><span class="material-symbols-outlined">restart_alt</span>Isi data demo</button>
    </form>
    <form method="POST" action="{{ route('keluar') }}">
        @csrf
        <button class="btn-danger w-full"><span class="material-symbols-outlined">logout</span>Keluar</button>
    </form>
</div>
@endsection
