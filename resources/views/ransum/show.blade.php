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

{{-- Peringatan Ambang Batas Aman & Gizi (Fitur 3) --}}
@if (!empty($keamanan['peringatan']))
    <div class="space-y-2">
        @foreach ($keamanan['peringatan'] as $pk)
            <div class="notice {{ $pk['level'] === 'danger' ? 'bg-danger-soft/80 text-danger-ink border border-danger/40' : ($pk['level'] === 'warning' ? 'bg-amber-soft/70 text-amber-ink border border-amber/30' : 'bg-brand/10 text-primary border border-brand/20') }}">
                <span class="material-symbols-outlined text-[20px]">{{ $pk['level'] === 'danger' ? 'gpp_bad' : ($pk['level'] === 'warning' ? 'warning' : 'verified_user') }}</span>
                <div>
                    <span class="font-bold">{{ $pk['judul'] }}:</span> {{ $pk['pesan'] }}
                </div>
            </div>
        @endforeach
    </div>
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
        {{-- Tabel resep: Basis Kering & Basis Segar (Fitur 2) --}}
        <section class="card space-y-3">
            <div class="flex items-center justify-between flex-wrap gap-2">
                <div>
                    <h2 class="text-h-sm">Komposisi Resep</h2>
                    <span class="text-xs text-muted">Bahan Kering (BK) & Segar (As-Fed)</span>
                </div>
                <div class="inline-flex rounded-lg bg-surface-2 p-1 text-xs font-bold" id="tabGroupResep">
                    <button type="button" id="btnTabAsFed" class="px-3 py-1.5 rounded-md transition bg-white shadow text-brand flex items-center gap-1">
                        <span class="material-symbols-outlined text-[15px]">scale</span>
                        <span>Timbangan Segar (As-Fed)</span>
                    </button>
                    <button type="button" id="btnTabBk" class="px-3 py-1.5 rounded-md transition text-muted hover:text-ink">
                        <span>Formulasi Kering (BK)</span>
                    </button>
                </div>
            </div>

            {{-- Visual Bar --}}
            <div class="flex h-4 rounded-full overflow-hidden" aria-hidden="true">
                @foreach ($baris as $i => $b)<span style="width:{{ $b['persen'] }}%;background:{{ $warna[$i % count($warna)] }}"></span>@endforeach
            </div>

            {{-- 1. Tab Timbangan Segar (As-Fed) untuk anak kandang --}}
            <div id="tabAsFedContent" class="space-y-3">
                <div class="rounded-xl bg-brand/5 border border-brand/20 p-2.5 text-xs text-primary flex items-start gap-2">
                    <span class="material-symbols-outlined text-[18px] text-brand shrink-0 mt-0.5">scale</span>
                    <div>
                        <strong>Petunjuk Timbangan Kandang (As-Fed):</strong> Tabel ini memperhitungkan kadar air alami masing-masing bahan. Gunakan angka kolom <strong>Kg Segar</strong> saat menakar pakan di gudang/kandang.
                    </div>
                </div>
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-muted text-lbl">
                            <th class="py-1">Bahan</th>
                            <th class="text-right">BK</th>
                            <th class="text-right">% Segar</th>
                            <th class="text-right font-bold text-brand">Kg Segar</th>
                            <th class="text-right">Biaya</th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach ($asFed['items'] as $i => $af)
                        <tr class="border-t border-line/60">
                            <td class="py-2.5 font-bold">
                                <span class="inline-block w-2.5 h-2.5 rounded-full mr-1.5" style="background:{{ $warna[$i % count($warna)] }}"></span>
                                {{ $af['nama'] }}
                            </td>
                            <td class="text-right text-xs text-muted">{{ Fmt::num($af['bk'], 0) }}%</td>
                            <td class="text-right">{{ Fmt::num($af['persen_as_fed'], 1) }}%</td>
                            <td class="text-right font-bold text-brand">{{ Fmt::num($af['kg_as_fed'], 2) }} kg</td>
                            <td class="text-right">{{ Fmt::rp($af['biaya']) }}</td>
                        </tr>
                    @endforeach
                    <tr class="border-t-2 border-line font-extrabold text-brand">
                        <td class="py-2.5" colspan="2">Total Timbangan Segar</td>
                        <td class="text-right">100%</td>
                        <td class="text-right font-black">{{ Fmt::num($asFed['total_as_fed_kg'], 2) }} kg</td>
                        <td class="text-right">{{ Fmt::rp($asFed['total_biaya']) }}</td>
                    </tr>
                    </tbody>
                </table>
                <div class="flex items-center justify-between text-xs text-muted pt-1">
                    <span>*BK = Bahan Kering (Dry Matter)</span>
                    <span>Biaya riil: <strong class="text-ink">{{ Fmt::rp($asFed['biaya_per_kg_as_fed']) }} / kg segar</strong></span>
                </div>
            </div>

            {{-- 2. Tab Formulasi BK --}}
            <div id="tabBkContent" class="hidden space-y-3">
                <div class="rounded-xl bg-surface border border-line p-2.5 text-xs text-muted flex items-start gap-2">
                    <span class="material-symbols-outlined text-[18px] text-muted shrink-0 mt-0.5">info</span>
                    <div>
                        <strong>Basis 100% Bahan Kering (BK):</strong> Digunakan untuk standardisasi formula gizi laboratorium (NRC / SNI) sebelum dikonversi ke pakan segar.
                    </div>
                </div>
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-muted text-lbl">
                            <th class="py-1">Bahan</th>
                            <th class="text-right">BK</th>
                            <th class="text-right">% BK</th>
                            <th class="text-right">Kg BK</th>
                            <th class="text-right">Biaya</th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach ($baris as $i => $b)
                        <tr class="border-t border-line/60">
                            <td class="py-2.5 font-bold">
                                <span class="inline-block w-2.5 h-2.5 rounded-full mr-1.5" style="background:{{ $warna[$i % count($warna)] }}"></span>
                                {{ $b['nama'] }}
                            </td>
                            <td class="text-right text-xs text-muted">{{ Fmt::num($b['bk'], 0) }}%</td>
                            <td class="text-right">{{ Fmt::num($b['persen'], 1) }}%</td>
                            <td class="text-right font-semibold">{{ Fmt::num($b['kg'], 2) }}</td>
                            <td class="text-right">{{ Fmt::rp($b['biaya']) }}</td>
                        </tr>
                    @endforeach
                    <tr class="border-t-2 border-line font-extrabold">
                        <td class="py-2.5" colspan="2">Total Formulasi</td>
                        <td class="text-right">100%</td>
                        <td class="text-right">{{ Fmt::num($skala, 2) }} kg</td>
                        <td class="text-right">{{ Fmt::rp($baris->sum('biaya')) }}</td>
                    </tr>
                    </tbody>
                </table>
            </div>

            {{-- Form Skala Ulang & Lembar Aduk Mixer --}}
            <div class="pt-2 border-t border-line/70 space-y-3">
                <form method="GET" class="flex items-end gap-2">
                    <div class="flex-1">
                        <label class="lbl" for="kg">Hitung ulang untuk jumlah lain (kg BK)</label>
                        <input id="kg" name="kg" type="number" min="1" step="0.5" value="{{ (float) $skala }}" class="field !min-h-11">
                    </div>
                    <button class="btn-outline btn-sm">Hitung</button>
                </form>

                <details class="text-xs text-muted">
                    <summary class="cursor-pointer font-bold text-brand hover:underline flex items-center gap-1.5 py-1">
                        <span class="material-symbols-outlined text-[18px]">blender</span>
                        <span>Cetak Lembar Aduk Wadah Mixer (Mixing Sheet {{ $mixerKg }} kg Segar)</span>
                    </summary>
                    <div class="mt-2 p-3 rounded-xl bg-surface border border-line space-y-2">
                        <p class="font-bold text-xs text-primary">Takaran per 1 Wadah Mixer (Kapasitas {{ $mixerKg }} kg Segar):</p>
                        <table class="w-full text-xs">
                            <thead>
                                <tr class="text-left text-muted border-b border-line">
                                    <th class="py-1">Bahan</th>
                                    <th class="text-right">Proporsi</th>
                                    <th class="text-right font-bold text-brand">Timbang Riil</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($mixingSheet as $ms)
                                    <tr class="border-b border-line/50">
                                        <td class="py-1 font-semibold">{{ $ms['nama'] }}</td>
                                        <td class="text-right text-muted">{{ Fmt::num($ms['persen_as_fed'], 1) }}%</td>
                                        <td class="text-right font-bold text-brand">{{ Fmt::num($ms['kg_timbang'], 2) }} kg</td>
                                    </tr>
                                @endforeach
                                <tr class="font-extrabold text-primary">
                                    <td class="py-1.5">Total Adukan Mixer</td>
                                    <td class="text-right">100%</td>
                                    <td class="text-right text-brand font-black">{{ Fmt::num($mixerKg, 2) }} kg</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </details>
            </div>
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

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const btnAsFed = document.getElementById('btnTabAsFed');
        const btnBk = document.getElementById('btnTabBk');
        const contentAsFed = document.getElementById('tabAsFedContent');
        const contentBk = document.getElementById('tabBkContent');

        if (btnAsFed && btnBk && contentAsFed && contentBk) {
            btnAsFed.addEventListener('click', () => {
                contentAsFed.classList.remove('hidden');
                contentBk.classList.add('hidden');
                btnAsFed.className = 'px-3 py-1.5 rounded-md transition bg-white shadow text-brand flex items-center gap-1';
                btnBk.className = 'px-3 py-1.5 rounded-md transition text-muted hover:text-ink';
            });

            btnBk.addEventListener('click', () => {
                contentBk.classList.remove('hidden');
                contentAsFed.classList.add('hidden');
                btnBk.className = 'px-3 py-1.5 rounded-md transition bg-white shadow text-brand';
                btnAsFed.className = 'px-3 py-1.5 rounded-md transition text-muted hover:text-ink flex items-center gap-1';
            });
        }
    });
</script>
@endsection
