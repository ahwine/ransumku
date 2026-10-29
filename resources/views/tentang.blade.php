@extends('layouts.app')
@section('judul', 'Tentang RansumKu')
@section('isi')

<div class="space-y-6">
    {{-- 1. Hero Showcase Section --}}
    <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-brand-dark via-primary to-emerald-950 text-white p-6 sm:p-10 shadow-lg border border-brand/30">
        {{-- Pola Hiasan Latar Geometris --}}
        <div class="absolute -right-12 -top-12 w-64 h-64 rounded-full bg-emerald-500/10 blur-3xl pointer-events-none"></div>
        <div class="absolute right-1/4 -bottom-12 w-80 h-80 rounded-full bg-lime/10 blur-3xl pointer-events-none"></div>

        <div class="relative z-10 space-y-4 max-w-3xl">
            <div class="flex items-center gap-2 flex-wrap">
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-extrabold uppercase tracking-wider bg-lime/20 text-lime border border-lime/30 shadow-2xs">
                    <span class="w-2 h-2 rounded-full bg-lime animate-pulse"></span>
                    Smart Poultry Farm System
                </span>
                <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-[11px] font-bold text-slate-300 bg-white/10 border border-white/15">
                    <span class="material-symbols-outlined text-[14px]">school</span>
                    Universitas Gadjah Mada
                </span>
                <span class="px-2.5 py-1 rounded-full text-[11px] font-mono font-bold bg-white/5 text-slate-300 border border-white/10">
                    v2.4.0 Stable
                </span>
            </div>

            <div class="flex items-center gap-4">
                <img src="{{ asset('images/logo.png') }}" alt="Logo RansumKu" class="w-16 h-16 sm:w-20 sm:h-20 object-contain rounded-2xl bg-white/10 p-1.5 shadow-md border border-white/20 shrink-0" />
                <div>
                    <h1 class="text-2xl sm:text-4xl font-black tracking-tight text-white leading-tight">
                        RansumKu
                    </h1>
                    <p class="text-xs sm:text-sm font-semibold text-lime tracking-wide uppercase mt-0.5">
                        Platform Formulasi Nutrisi Presisi & Buku Kandang Ayam Petelur
                    </p>
                </div>
            </div>

            <p class="text-slate-200 text-sm sm:text-base leading-relaxed pt-1">
                RansumKu adalah sistem komprehensif yang dirancang untuk menjembatani sains nutrisi unggas dengan operasional nyata peternak ayam petelur (<em>layer</em>) skala mandiri dan komersial. Mengintegrasikan algoritma formulasi biaya terendah (<em>Least-Cost Formulation</em>) berbasis standar SNI, pencatatan harian buku kandang, hingga deteksi dini anomali kawanan.
            </p>

            {{-- Metric Strip Ringkas --}}
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 pt-2">
                <div class="p-3 rounded-2xl bg-white/10 backdrop-blur-md border border-white/15">
                    <span class="text-[11px] text-slate-300 block font-medium">Metode Formulasi</span>
                    <strong class="text-sm sm:text-base font-extrabold text-white block mt-0.5">Linear Simplex</strong>
                    <span class="text-[10px] text-lime font-semibold">100% Eksak Terukur</span>
                </div>
                <div class="p-3 rounded-2xl bg-white/10 backdrop-blur-md border border-white/15">
                    <span class="text-[11px] text-slate-300 block font-medium">Standar Acuan</span>
                    <strong class="text-sm sm:text-base font-extrabold text-white block mt-0.5">SNI Unggas</strong>
                    <span class="text-[10px] text-lime font-semibold">Starter · Grower · Layer</span>
                </div>
                <div class="p-3 rounded-2xl bg-white/10 backdrop-blur-md border border-white/15">
                    <span class="text-[11px] text-slate-300 block font-medium">Audit Produksi</span>
                    <strong class="text-sm sm:text-base font-extrabold text-white block mt-0.5">HDP % & FCR</strong>
                    <span class="text-[10px] text-lime font-semibold">Otomasi Rekapitulasi</span>
                </div>
                <div class="p-3 rounded-2xl bg-white/10 backdrop-blur-md border border-white/15">
                    <span class="text-[11px] text-slate-300 block font-medium">Output Legalitas</span>
                    <strong class="text-sm sm:text-base font-extrabold text-white block mt-0.5">Laporan A4</strong>
                    <span class="text-[10px] text-lime font-semibold">Siap Cetak & TTD</span>
                </div>
            </div>
        </div>
    </div>

    {{-- 2. 4 Pilar Fungsional RansumKu --}}
    <div class="space-y-3.5">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-h-md text-ink">4 Pilar Fungsional Sistem</h2>
                <p class="text-xs sm:text-sm text-muted">Instrumen terintegrasi untuk mengoptimalkan efisiensi pakan dan stabilitas margin peternak.</p>
            </div>
            <span class="hidden sm:inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-brand/10 text-brand">
                <span class="material-symbols-outlined text-[16px]">widgets</span>
                Modul Terpadu
            </span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            {{-- Pilar 1: Peracik Ransum --}}
            <div class="card space-y-3 p-5 transition duration-200 hover:shadow-md hover:border-brand/40 group">
                <div class="flex items-start justify-between">
                    <div class="w-12 h-12 rounded-2xl bg-emerald-100 text-brand flex items-center justify-center font-bold shadow-2xs group-hover:scale-105 transition-transform">
                        <span class="material-symbols-outlined text-[26px]">calculate</span>
                    </div>
                    <span class="px-2.5 py-1 rounded-full text-[11px] font-extrabold bg-emerald-50 text-emerald-800 border border-emerald-200">
                        Linear Programming
                    </span>
                </div>
                <div>
                    <h3 class="text-h-sm text-ink group-hover:text-brand transition-colors">Peracik Ransum Presisi</h3>
                    <p class="text-xs text-muted mt-1 leading-relaxed">
                        Memanfaatkan solver optimasi matematika untuk menghitung kombinasi bahan pakan nabati (jagung, bekatul, konsentrat, premix) dengan biaya total paling minimum (<em>least-cost</em>) yang tetap memenuhi batas ambang nutrisi SNI.
                    </p>
                </div>
                <div class="pt-2 border-t border-line/60 flex items-center justify-between text-xs">
                    <span class="text-muted">Target Nutrisi: <strong>PK, EM, Ca, P, SK</strong></span>
                    <a href="{{ route('ransum.index') }}" class="font-bold text-brand hover:underline flex items-center gap-0.5">
                        Buka Modul <span class="material-symbols-outlined text-[14px]">arrow_forward</span>
                    </a>
                </div>
            </div>

            {{-- Pilar 2: Buku Kandang --}}
            <div class="card space-y-3 p-5 transition duration-200 hover:shadow-md hover:border-brand/40 group">
                <div class="flex items-start justify-between">
                    <div class="w-12 h-12 rounded-2xl bg-amber-100 text-amber-900 flex items-center justify-center font-bold shadow-2xs group-hover:scale-105 transition-transform">
                        <span class="material-symbols-outlined text-[26px]">description</span>
                    </div>
                    <span class="px-2.5 py-1 rounded-full text-[11px] font-extrabold bg-amber-50 text-amber-900 border border-amber-200">
                        Smart Log & Audit A4
                    </span>
                </div>
                <div>
                    <h3 class="text-h-sm text-ink group-hover:text-brand transition-colors">Buku Kandang & Performa Harian</h3>
                    <p class="text-xs text-muted mt-1 leading-relaxed">
                        Pencatatan harian yang menghitung otomatis persentase Hen-Day Production (HDP %), konsumsi gram pakan per ekor, efisiensi pakan terhadap telur (FCR), grafik visual progresif, dan lembar laporan resmi format kertas A4 siap cetak.
                    </p>
                </div>
                <div class="pt-2 border-t border-line/60 flex items-center justify-between text-xs">
                    <span class="text-muted">Fitur Unggulan: <strong>Cetak PDF/A4 & Ekspor CSV</strong></span>
                    <a href="{{ route('catatan.index') }}" class="font-bold text-brand hover:underline flex items-center gap-0.5">
                        Buka Modul <span class="material-symbols-outlined text-[14px]">arrow_forward</span>
                    </a>
                </div>
            </div>

            {{-- Pilar 3: Asisten Cerdas --}}
            <div class="card space-y-3 p-5 transition duration-200 hover:shadow-md hover:border-brand/40 group">
                <div class="flex items-start justify-between">
                    <div class="w-12 h-12 rounded-2xl bg-teal-100 text-teal-900 flex items-center justify-center font-bold shadow-2xs group-hover:scale-105 transition-transform">
                        <span class="material-symbols-outlined text-[26px]">smart_toy</span>
                    </div>
                    <span class="px-2.5 py-1 rounded-full text-[11px] font-extrabold bg-teal-50 text-teal-900 border border-teal-200">
                        Early Warning System
                    </span>
                </div>
                <div>
                    <h3 class="text-h-sm text-ink group-hover:text-brand transition-colors">Asisten Cerdas & Deteksi Anomali</h3>
                    <p class="text-xs text-muted mt-1 leading-relaxed">
                        Sistem pendukung keputusan (<em>Decision Support System</em>) berbasis evaluasi statistik yang mendeteksi dini penurunan produksi telur mendadak, kenaikan pakan tidak wajar, serta lonjakan mortalitas untuk mencegah kerugian kawanan.
                    </p>
                </div>
                <div class="pt-2 border-t border-line/60 flex items-center justify-between text-xs">
                    <span class="text-muted">Fungsi: <strong>Pencegahan Wabah & Konsultasi</strong></span>
                    <a href="{{ route('asisten.index') }}" class="font-bold text-brand hover:underline flex items-center gap-0.5">
                        Buka Modul <span class="material-symbols-outlined text-[14px]">arrow_forward</span>
                    </a>
                </div>
            </div>

            {{-- Pilar 4: Inventori & Manajemen Bahan --}}
            <div class="card space-y-3 p-5 transition duration-200 hover:shadow-md hover:border-brand/40 group">
                <div class="flex items-start justify-between">
                    <div class="w-12 h-12 rounded-2xl bg-blue-100 text-blue-900 flex items-center justify-center font-bold shadow-2xs group-hover:scale-105 transition-transform">
                        <span class="material-symbols-outlined text-[26px]">inventory_2</span>
                    </div>
                    <span class="px-2.5 py-1 rounded-full text-[11px] font-extrabold bg-blue-50 text-blue-900 border border-blue-200">
                        Cost Variance & Stock
                    </span>
                </div>
                <div>
                    <h3 class="text-h-sm text-ink group-hover:text-brand transition-colors">Kontrol Stok & Transparansi Biaya</h3>
                    <p class="text-xs text-muted mt-1 leading-relaxed">
                        Pelacak selisih antara harga estimasi racikan formula dengan transaksi riil saat pembelian di toko bahan pakan. Mendukung penambahan otomatis stok gudang aktif untuk menjaga kepastian ketersediaan ransum.
                    </p>
                </div>
                <div class="pt-2 border-t border-line/60 flex items-center justify-between text-xs">
                    <span class="text-muted">Sinkronisasi: <strong>Stok Gudang & Harga Riil</strong></span>
                    <a href="{{ route('bahan.index') }}" class="font-bold text-brand hover:underline flex items-center gap-0.5">
                        Buka Modul <span class="material-symbols-outlined text-[14px]">arrow_forward</span>
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- 3. Alur Siklus Kerja Peternak (Operational Workflow) --}}
    <div class="card space-y-4 p-5 sm:p-6 bg-gradient-to-r from-surface via-white to-surface border border-line">
        <div class="flex items-center gap-2">
            <span class="material-symbols-outlined text-brand text-[22px]">alt_route</span>
            <div>
                <h2 class="text-h-sm text-ink">Alur Operasional Terintegrasi</h2>
                <p class="text-xs text-muted">Siklus kerja harian peternak dalam memaksimalkan produktivitas ayam petelur.</p>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-4 gap-3 relative">
            <div class="p-3.5 rounded-2xl bg-white border border-line space-y-1.5 shadow-2xs">
                <span class="w-6 h-6 rounded-full bg-brand text-white text-xs font-black flex items-center justify-center">1</span>
                <strong class="text-xs text-ink block font-bold">Input Bahan & SNI</strong>
                <p class="text-[11px] text-muted leading-snug">Daftarkan harga pasar bahan pakan dan tentukan fase ayam (starter, grower, layer).</p>
            </div>
            <div class="p-3.5 rounded-2xl bg-white border border-line space-y-1.5 shadow-2xs">
                <span class="w-6 h-6 rounded-full bg-brand text-white text-xs font-black flex items-center justify-center">2</span>
                <strong class="text-xs text-ink block font-bold">Optimasi Ransum</strong>
                <p class="text-[11px] text-muted leading-snug">Sistem menghitung persentase campuran termurah yang memenuhi standar gizi SNI.</p>
            </div>
            <div class="p-3.5 rounded-2xl bg-white border border-line space-y-1.5 shadow-2xs">
                <span class="w-6 h-6 rounded-full bg-brand text-white text-xs font-black flex items-center justify-center">3</span>
                <strong class="text-xs text-ink block font-bold">Pencatatan Harian</strong>
                <p class="text-[11px] text-muted leading-snug">Input konsumsi pakan dan butir telur sore hari dalam waktu 1-2 menit via Buku Kandang.</p>
            </div>
            <div class="p-3.5 rounded-2xl bg-white border border-line space-y-1.5 shadow-2xs">
                <span class="w-6 h-6 rounded-full bg-brand text-white text-xs font-black flex items-center justify-center">4</span>
                <strong class="text-xs text-ink block font-bold">Audit & Laporan A4</strong>
                <p class="text-[11px] text-muted leading-snug">Evaluasi grafik FCR, deteksi anomali, dan cetak dokumen laporan resmi berkala.</p>
            </div>
        </div>
    </div>

    {{-- 4. Prinsip Integritas Ilmiah & Transparansi Batasan --}}
    <div class="space-y-3.5">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-h-md text-ink">Prinsip Integritas Ilmiah & Batasan Sistem</h2>
                <p class="text-xs sm:text-sm text-muted">Transparansi komitmen kami dalam menjaga kepatuhan sains peternakan dan keselamatan ternak.</p>
            </div>
            <span class="hidden sm:inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-amber-500/10 text-amber-ink">
                <span class="material-symbols-outlined text-[16px]">verified</span>
                Transparansi Sains
            </span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3.5">
            {{-- Poin 1 --}}
            <div class="card p-4 space-y-2 bg-white hover:border-slate-300 transition shadow-2xs">
                <div class="flex items-center gap-2.5 text-brand">
                    <span class="w-8 h-8 rounded-xl bg-brand/10 flex items-center justify-center shrink-0">
                        <span class="material-symbols-outlined text-[19px]">science</span>
                    </span>
                    <h3 class="font-bold text-xs sm:text-sm text-ink">Uji Adaptasi Lapangan (In-Vivo)</h3>
                </div>
                <p class="text-xs text-muted leading-relaxed">
                    Hasil hitungan ransum adalah formulasi matematis berdasarkan tabel nutrisi acuan laboratorium. Sebelum diterapkan menyeluruh pada kandang, peternak dianjurkan melakukan masa transisi pergantian pakan bertahap (3-5 hari) pada sekat uji coba.
                </p>
            </div>

            {{-- Poin 2 --}}
            <div class="card p-4 space-y-2 bg-white hover:border-slate-300 transition shadow-2xs">
                <div class="flex items-center gap-2.5 text-emerald-800">
                    <span class="w-8 h-8 rounded-xl bg-emerald-100 flex items-center justify-center shrink-0">
                        <span class="material-symbols-outlined text-[19px]">functions</span>
                    </span>
                    <h3 class="font-bold text-xs sm:text-sm text-ink">Metode Eksak (Bukan Halusinasi AI)</h3>
                </div>
                <p class="text-xs text-muted leading-relaxed">
                    Penentuan komposisi pakan diselesaikan 100% menggunakan metode Linear Programming (Simplex) murni, bukan teks generatif AI. Perhitungan dijamin deterministik, eksak, dan dapat diaudit secara matematis.
                </p>
            </div>

            {{-- Poin 3 --}}
            <div class="card p-4 space-y-2 bg-white hover:border-slate-300 transition shadow-2xs">
                <div class="flex items-center gap-2.5 text-primary">
                    <span class="w-8 h-8 rounded-xl bg-surface-2 flex items-center justify-center shrink-0">
                        <span class="material-symbols-outlined text-[19px]">payments</span>
                    </span>
                    <h3 class="font-bold text-xs sm:text-sm text-ink">Harga Input Berbasis Riil</h3>
                </div>
                <p class="text-xs text-muted leading-relaxed">
                    Sistem tidak melakukan spekulasi harga pasar pakan secara otomatis. Seluruh perhitungan biaya per kilogram pakan, estimasi biaya harian, dan margin laba berasal dari angka harga riil yang Anda masukkan di menu bahan.
                </p>
            </div>

            {{-- Poin 4 --}}
            <div class="card p-4 space-y-2 bg-white hover:border-slate-300 transition shadow-2xs">
                <div class="flex items-center gap-2.5 text-amber-800">
                    <span class="w-8 h-8 rounded-xl bg-amber-100 flex items-center justify-center shrink-0">
                        <span class="material-symbols-outlined text-[19px]">health_and_safety</span>
                    </span>
                    <h3 class="font-bold text-xs sm:text-sm text-ink">AI Sebagai Pendukung Keputusan</h3>
                </div>
                <p class="text-xs text-muted leading-relaxed">
                    {{ config('ransumku.disclaimer_ai', 'Peringatan dini dan saran asisten bertindak sebagai sistem pendukung keputusan (Decision Support System), bukan diagnosis klinis mutlak. Untuk penanganan penyakit menular atau kasus kritis, selalu konsultasikan dengan dokter hewan atau penyuluh peternakan.') }}
                </p>
            </div>

            {{-- Poin 5 --}}
            <div class="card p-4 space-y-2 bg-white hover:border-slate-300 transition shadow-2xs">
                <div class="flex items-center gap-2.5 text-rose-800">
                    <span class="w-8 h-8 rounded-xl bg-rose-100 flex items-center justify-center shrink-0">
                        <span class="material-symbols-outlined text-[19px]">compost</span>
                    </span>
                    <h3 class="font-bold text-xs sm:text-sm text-ink">Variabilitas Mutu Bahan Lokal</h3>
                </div>
                <p class="text-xs text-muted leading-relaxed">
                    Kandungan nutrisi bahan baku lokal (seperti bekatul dan jagung) dapat berbeda menurut kadar air dan sumber penggilingan. Anda dapat menyesuaikan profil gizi bahan di aplikasi sesuai hasil uji proksimat atau label kemasan resmi.
                </p>
            </div>

            {{-- Poin 6 --}}
            <div class="card p-4 space-y-2 bg-white hover:border-slate-300 transition shadow-2xs">
                <div class="flex items-center gap-2.5 text-teal-800">
                    <span class="w-8 h-8 rounded-xl bg-teal-100 flex items-center justify-center shrink-0">
                        <span class="material-symbols-outlined text-[19px]">fact_check</span>
                    </span>
                    <h3 class="font-bold text-xs sm:text-sm text-ink">Fleksibilitas Standar Nutrisi SNI</h3>
                </div>
                <p class="text-xs text-muted leading-relaxed">
                    Target nutrisi mengikuti acuan standar yang dapat Anda pilih dan edit sesuai strain genetik ayam (Lohmann, Isa Brown, Hy-Line) serta iklim mikro kandang Anda agar hasil produksi telur optimal.
                </p>
            </div>
        </div>
    </div>

    {{-- 5. Informasi Institusi, Teknologi & Quick CTA --}}
    <div class="card p-6 bg-surface-2/60 border border-line space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-line">
            <div class="space-y-1">
                <span class="text-[11px] font-black uppercase tracking-wider text-brand block">Inisiatif Kolaborasi & Riset</span>
                <h3 class="text-h-sm text-ink">Laboratorium Smart Poultry & Nutrisi Ternak</h3>
                <p class="text-xs text-muted">Universitas Gadjah Mada · DI Yogyakarta, Indonesia</p>
            </div>
            <div class="flex items-center gap-2 text-xs font-semibold text-slate-700 bg-white px-3.5 py-2 rounded-xl border border-line shadow-2xs self-start sm:self-auto">
                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                <span>PHP 8.4 · Laravel 11 · TailwindCSS · Chart.js</span>
            </div>
        </div>

        <div class="flex flex-wrap items-center justify-between gap-3 pt-1">
            <p class="text-xs text-muted">
                Didedikasikan untuk kemandirian pakan dan kemakmuran peternak ayam petelur rakyat di seluruh Indonesia.
            </p>
            <div class="flex items-center gap-2 flex-wrap">
                <a href="{{ route('ransum.index') }}" class="btn-primary btn-sm !py-2 !px-3 text-xs font-bold flex items-center gap-1.5 shadow-2xs">
                    <span class="material-symbols-outlined text-[16px]">calculate</span>
                    Peracik Ransum
                </a>
                <a href="{{ route('catatan.index') }}" class="btn-outline btn-sm !py-2 !px-3 text-xs font-bold flex items-center gap-1.5 shadow-2xs">
                    <span class="material-symbols-outlined text-[16px]">description</span>
                    Buku Kandang
                </a>
                <a href="{{ route('asisten.index') }}" class="btn-outline btn-sm !py-2 !px-3 text-xs font-bold flex items-center gap-1.5 shadow-2xs">
                    <span class="material-symbols-outlined text-[16px]">smart_toy</span>
                    Asisten Cerdas
                </a>
            </div>
        </div>
    </div>
</div>

@endsection
