@extends('layouts.app')
@section('judul', 'Tentang & Batasan')
@section('isi')
<h1 class="text-h-xl">Tentang & Batasan</h1>

<div class="card space-y-2">
    <h2 class="text-h-sm">Apa itu RansumKu?</h2>
    <p>RansumKu adalah <strong>alat bantu hitung</strong> untuk peternak ayam petelur skala kecil yang meramu pakan sendiri: menghitung ransum termurah yang memenuhi target gizi, mencatat pakan, telur, dan biaya harian, serta memberi peringatan dini.</p>
</div>

<div class="card space-y-3">
    <h2 class="text-h-sm">Batasan yang perlu Anda tahu</h2>
    <ul class="space-y-3">
        @foreach ([
            ['science', 'Belum diuji pada ayam sungguhan', 'Hasil hitungan belum diuji di kandang. Gunakan sebagai pembanding, bukan satu-satunya pegangan, dan konsultasikan dengan penyuluh atau dokter hewan.'],
            ['payments', 'Harga adalah input Anda', 'Aplikasi tidak memprediksi harga. Biaya yang tampil adalah estimasi dari harga yang Anda isi. Perbarui harga bila berubah.'],
            ['calculate', 'Perhitungan oleh program', 'Komposisi, gizi, dan biaya dihitung dengan program (linear programming), bukan oleh AI. AI hanya menjelaskan hasil.'],
            ['warning', 'Peringatan AI bukan diagnosis', config('ransumku.disclaimer_ai')],
            ['grocery', 'Nilai gizi bahan adalah angka ilustrasi', 'Nilai gizi di data contoh bukan data resmi. Nilai dapat diedit; sesuaikan dengan label kemasan atau tabel komposisi bahan pakan resmi.'],
            ['rule', 'Acuan gizi dapat diedit', 'Target gizi mengikuti acuan yang Anda pilih dan tercatat nama serta tahunnya. Pastikan acuan sesuai fase ayam.'],
            ['savings', 'Potensi hemat, bukan janji', 'Penghematan hanya potensi dan bergantung pada harga serta stok bahan Anda.'],
        ] as [$ikon, $judul, $isi])
            <li class="flex gap-3">
                <span class="w-9 h-9 rounded-xl bg-surface-2 text-brand grid place-items-center shrink-0"><span class="material-symbols-outlined">{{ $ikon }}</span></span>
                <span><strong class="block">{{ $judul }}</strong><span class="text-muted text-sm">{{ $isi }}</span></span>
            </li>
        @endforeach
    </ul>
</div>
@endsection
