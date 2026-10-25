@extends('layouts.app')
@section('judul', 'Hasil Ransum')
@section('isi')
@use('App\Support\Fmt')
@php
    $warna = ['#2F6B3A', '#F2B134', '#145224', '#D64545', '#7e5700', '#96d69a', '#b22b2e'];
@endphp

<div class="flex items-center gap-2">
    <a href="{{ route('ransum.index') }}" class="w-10 h-10 grid place-items-center rounded-full bg-white border border-line" aria-label="Kembali"><span class="material-symbols-outlined">arrow_back</span></a>
    <div>
        <h1 class="text-h-lg leading-tight">Hasil Ransum</h1>
        <p class="text-lbl text-muted">{{ $ration->created_at->translatedFormat('d M Y, H:i') }} · {{ $ration->standard?->nama ?? 'Acuan dihapus' }}</p>
    </div>
</div>

@if ($hargaUsang)
    <div class="notice bg-amber-soft/50 text-amber-ink"><span class="material-symbols-outlined">update</span>
        <span>Ada harga bahan yang belum diperbarui lebih dari 7 hari. <a href="{{ route('ransum.create') }}" class="font-bold underline">Perbarui harga</a> lalu hitung ulang.</span></div>
@endif

@if ($ration->layak())
    {{-- Ringkasan biaya --}}
    <div class="rounded-2xl p-5 bg-brand text-white" style="box-shadow:0 10px 25px -4px rgba(47,107,58,.3)">
        <div class="flex items-center justify-between">
            <p class="text-lbl uppercase tracking-wide opacity-90">Total biaya</p><span class="chip bg-white/20">estimasi</span>
        </div>
        <p class="text-h-xl !text-[34px]">{{ Fmt::rp($ration->total_biaya) }}</p>
        <p class="opacity-90">{{ Fmt::rp($ration->biaya_per_kg) }} per kg <span class="chip bg-white/20 ml-1">estimasi</span> · {{ Fmt::num($ration->total_kg, 1) }} kg{{ $ration->jumlah_hari ? ' untuk '.$ration->jumlah_hari.' hari' : '' }}</p>
    </div>

    <div class="lg:grid lg:grid-cols-2 lg:gap-4 space-y-4 lg:space-y-0">
        {{-- Tabel resep --}}
        <section class="card space-y-3">
            <div class="flex items-center justify-between">
                <h2 class="text-h-sm">Komposisi Resep</h2>
                <span class="text-xs text-muted">Disimpan dalam persen</span>
            </div>
            <div class="flex h-4 rounded-full overflow-hidden" aria-hidden="true">
                @foreach ($baris as $i => $b)<span style="width:{{ $b['persen'] }}%;background:{{ $warna[$i % count($warna)] }}"></span>@endforeach
            </div>
            <table class="w-full text-sm">
                <thead><tr class="text-left text-muted text-lbl"><th class="py-1">Bahan</th><th class="text-right">%</th><th class="text-right">Kg</th><th class="text-right">Biaya</th></tr></thead>
                <tbody>
                @foreach ($baris as $i => $b)
                    <tr class="border-t border-line/60">
                        <td class="py-2.5 font-bold"><span class="inline-block w-2.5 h-2.5 rounded-full mr-1.5" style="background:{{ $warna[$i % count($warna)] }}"></span>{{ $b['nama'] }}</td>
                        <td class="text-right">{{ Fmt::num($b['persen'], 1) }}</td>
                        <td class="text-right font-semibold">{{ Fmt::num($b['kg'], 2) }}</td>
                        <td class="text-right">{{ Fmt::rp($b['biaya']) }}</td>
                    </tr>
                @endforeach
                <tr class="border-t-2 border-line font-extrabold"><td class="py-2.5">Total</td><td class="text-right">100</td><td class="text-right">{{ Fmt::num($skala, 2) }}</td><td class="text-right">{{ Fmt::rp($baris->sum('biaya')) }}</td></tr>
                </tbody>
            </table>
            <form method="GET" class="flex items-end gap-2 pt-1">
                <div class="flex-1">
                    <label class="lbl" for="kg">Pakai resep ini untuk jumlah lain (kg)</label>
                    <input id="kg" name="kg" type="number" min="1" step="0.5" value="{{ (float) $skala }}" class="field !min-h-11">
                </div>
                <button class="btn-outline btn-sm">Hitung</button>
            </form>
        </section>

        <div class="space-y-4">
            {{-- Cek gizi --}}
            <section class="card space-y-1">
                <h2 class="text-h-sm mb-1">Cek Gizi</h2>
                @foreach ($ration->hasil['gizi'] ?? [] as $g)
                    <div class="flex items-center gap-3 py-2 border-t border-line/60 first:border-0">
                        <span class="material-symbols-outlined icon-fill {{ $g['ok'] ? 'text-brand' : 'text-danger' }}" aria-label="{{ $g['ok'] ? 'Terpenuhi' : 'Belum terpenuhi' }}">{{ $g['ok'] ? 'check_circle' : 'cancel' }}</span>
                        <div class="flex-1"><p class="font-bold">{{ $g['label'] }}</p><p class="text-lbl text-muted">Target {{ $g['target'] }}</p></div>
                        <p class="font-extrabold {{ $g['ok'] ? '' : 'text-danger' }}">{{ Fmt::num($g['nilai'], 2) }}{{ $g['satuan'] }}</p>
                    </div>
                @endforeach
            </section>

            {{-- Sisa stok & belanja --}}
            <section class="card space-y-2">
                <h2 class="text-h-sm">Sisa Stok & Daftar Belanja</h2>
                <ul class="text-sm space-y-1">
                    @foreach ($ration->hasil['sisa_stok'] ?? [] as $s)
                        <li class="flex justify-between"><span>{{ $s['nama'] }}</span><span class="font-semibold">sisa {{ Fmt::num($s['sisa_kg'], 2) }} kg</span></li>
                    @endforeach
                </ul>
                @include('ransum._belanja', ['belanja' => $ration->hasil['belanja'] ?? []])
            </section>
        </div>
    </div>
@else
    {{-- Tidak layak: pesan ramah, bukan error mentah --}}
    <section class="card space-y-3 border-danger/30">
        <div class="flex items-center gap-2"><span class="material-symbols-outlined icon-fill text-danger text-[28px]">sentiment_neutral</span><h2 class="text-h-md">Ransum belum bisa disusun</h2></div>
        <ul class="space-y-2">
            @foreach ($ration->pesan ?? [] as $p)
                <li class="notice bg-danger-soft/50 text-danger-ink"><span class="material-symbols-outlined text-[18px]">priority_high</span><span>{{ $p }}</span></li>
            @endforeach
        </ul>
        <div class="notice bg-brand/5 text-primary"><span class="material-symbols-outlined">lightbulb</span>
            <span><strong>Saran:</strong> tambah bahan (misalnya sumber protein atau kalsium), tambah stok, atau kurangi jumlah campuran. Lalu tekan Hitung Ulang.</span></div>
        @if (!empty($ration->hasil['ideal']))
            <div>
                <h3 class="font-bold mb-1">Jika Anda menambah stok/bahan, resep yang mungkin:</h3>
                <ul class="text-sm space-y-1">
                    @foreach ($ration->hasil['ideal']['items'] as $it)
                        <li class="flex justify-between"><span>{{ $it['nama'] }} ({{ Fmt::num($it['persen'], 1) }}%)</span><span class="font-semibold">{{ Fmt::num($it['kg'], 2) }} kg</span></li>
                    @endforeach
                </ul>
                <p class="text-xs text-muted mt-1">Estimasi biaya {{ Fmt::rp($ration->hasil['ideal']['total_biaya']) }} bila semua bahan tersedia.</p>
            </div>
            @include('ransum._belanja', ['belanja' => $ration->hasil['belanja'] ?? []])
        @endif
        <div class="grid grid-cols-2 gap-2">
            <a href="{{ route('ransum.create') }}" class="btn-outline btn-sm">Ubah bahan & stok</a>
            <a href="{{ route('acuan.index') }}" class="btn-outline btn-sm">Ubah acuan gizi</a>
        </div>
    </section>
@endif

<p class="text-xs text-muted flex gap-1.5"><span class="material-symbols-outlined text-[16px]">info</span>Potensi hemat bergantung pada harga dan stok Anda. Ini bukan klaim pasti, dan biaya hanya estimasi.</p>

{{-- Penjelasan AI --}}
@if ($ration->penjelasan)
    <section id="penjelasan" class="card space-y-2 bg-brand/5">
        <h2 class="text-h-sm flex items-center gap-2"><span class="material-symbols-outlined icon-fill text-brand">auto_awesome</span>Penjelasan</h2>
        <p class="whitespace-pre-line">{{ $ration->penjelasan }}</p>
        <p class="text-xs text-muted">Penjelasan memakai angka dari program. Angka tidak dihitung oleh AI.</p>
    </section>
@endif

<div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
    <form method="POST" action="{{ route('ransum.jelaskan', $ration) }}">@csrf
        <button id="btn-jelaskan" class="btn-amber w-full"><span class="material-symbols-outlined">auto_awesome</span>Jelaskan hasil ini</button></form>
    <form method="POST" action="{{ route('ransum.hitung-ulang', $ration) }}">@csrf
        <button id="btn-hitung-ulang" class="btn-primary w-full"><span class="material-symbols-outlined">refresh</span>Hitung Ulang</button></form>
    <a href="{{ route('ransum.create') }}" class="btn-outline w-full"><span class="material-symbols-outlined">edit</span>Ubah harga/stok</a>
</div>
@endsection
