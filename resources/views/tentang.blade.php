@extends('layouts.app')
@section('judul', 'Tentang RansumKu')
@section('isi')

<div class="space-y-6">
    {{-- 1. Kartu Pengenalan Utama ("Apa itu RansumKu?") --}}
    <div class="bg-white rounded-lg sm:rounded-2xl border border-line p-6 md:p-7 shadow-2xs">
        <div class="flex flex-col sm:flex-row items-start sm:items-center gap-5">
            <img src="{{ asset('images/logo.png') }}" alt="Logo RansumKu" class="w-14 h-14 sm:w-16 sm:h-16 object-contain shrink-0" />

            <div class="space-y-1.5 flex-1 min-w-0">
                <h2 class="text-lg sm:text-xl font-bold text-ink tracking-tight">
                    RansumKu · Platform Formulasi Pakan & Buku Kandang Digital
                </h2>

                <p class="text-xs text-muted leading-relaxed max-w-4xl">
                    <strong>RansumKu</strong> adalah aplikasi berbasis web yang dirancang khusus untuk peternak ayam petelur rakyat dan mandiri. RansumKu membantu peternak menghitung racikan pakan sendiri yang hemat biaya tanpa mengorbankan standar nutrisi, sekaligus mencatat hasil produksi telur dan performa harian kandang secara otomatis, rapi, dan transparan.
                </p>
            </div>
        </div>
    </div>

    {{-- 2. Mengapa RansumKu Dibuat? (Tujuan Utama) --}}
    <div class="space-y-3.5">
        <div class="flex items-center justify-between pb-1 border-b border-line/60">
            <span class="text-xs font-bold text-muted uppercase tracking-wider">
                Tujuan & Manfaat Utama
            </span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            {{-- Tujuan 1: Efisiensi Biaya --}}
            <div class="bg-white rounded-lg sm:rounded-2xl border border-line p-5 shadow-2xs flex flex-col justify-between space-y-3">
                <div class="space-y-2">
                    <h3 class="text-sm font-bold text-ink">
                        <span class="text-brand mr-1.5">1.</span>Menghemat Biaya Pakan Harian
                    </h3>
                    <p class="text-xs text-muted leading-relaxed">
                        Biaya pakan menyerap 70–80% dari total modal operasional peternakan. RansumKu membantu Anda meracik formula pakan sendiri dari bahan lokal (jagung, bekatul, konsentrat) dengan biaya per kilogram paling rendah.
                    </p>
                </div>
                <div class="pt-2 border-t border-line/60">
                    <span class="text-[11px] font-semibold text-emerald-700">Fokus: Efisiensi Biaya & Margin Laba</span>
                </div>
            </div>

            {{-- Tujuan 2: Menjamin Kualitas Nutrisi --}}
            <div class="bg-white rounded-lg sm:rounded-2xl border border-line p-5 shadow-2xs flex flex-col justify-between space-y-3">
                <div class="space-y-2">
                    <h3 class="text-sm font-bold text-ink">
                        <span class="text-brand mr-1.5">2.</span>Memastikan Nutrisi Sesuai Standar
                    </h3>
                    <p class="text-xs text-muted leading-relaxed">
                        Pakan murah tidak boleh membuat produksi telur anjlok. RansumKu memastikan setiap racikan memenuhi batas nutrisi protein, energi, kalsium, dan fosfor sesuai Standar Nasional Indonesia (SNI Unggas).
                    </p>
                </div>
                <div class="pt-2 border-t border-line/60">
                    <span class="text-[11px] font-semibold text-emerald-700">Fokus: Produksi Telur Stabil & Kerabang Kokoh</span>
                </div>
            </div>

            {{-- Tujuan 3: Rekap Digital Otomatis --}}
            <div class="bg-white rounded-lg sm:rounded-2xl border border-line p-5 shadow-2xs flex flex-col justify-between space-y-3">
                <div class="space-y-2">
                    <h3 class="text-sm font-bold text-ink">
                        <span class="text-brand mr-1.5">3.</span>Pencatatan Kandang Tanpa Kertas
                    </h3>
                    <p class="text-xs text-muted leading-relaxed">
                        Tinggalkan buku tulis kertas yang mudah rusak atau hilang. Cukup masukkan butir telur dan pakan harian, sistem otomatis menghitung persentase bertelur (HDP %), FCR, dan riwayat kandang.
                    </p>
                </div>
                <div class="pt-2 border-t border-line/60">
                    <span class="text-[11px] font-semibold text-emerald-700">Fokus: Otomasi Laporan & Evaluasi Cepat</span>
                </div>
            </div>
        </div>
    </div>

    {{-- 3. Fitur & Fungsi Utama Platform --}}
    <div class="space-y-3.5">
        <div class="flex items-center justify-between pb-1 border-b border-line/60">
            <span class="text-xs font-bold text-muted uppercase tracking-wider">
                Fitur & Fungsi Utama
            </span>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            {{-- Fitur 1: Peracik Ransum --}}
            <div class="bg-white rounded-lg sm:rounded-2xl border border-line p-5 shadow-2xs flex flex-col justify-between space-y-3">
                <div class="space-y-2.5">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="material-symbols-outlined text-[18px] text-brand shrink-0">calculate</span>
                            <h3 class="text-base font-bold text-ink">Peracik Ransum Presisi</h3>
                        </div>
                        <span class="px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                            Linear Simplex
                        </span>
                    </div>
                    <p class="text-xs text-muted leading-relaxed">
                        Alat hitung optimasi pakan berbasis metode matematis <strong>Linear Simplex</strong>. Cukup pilih bahan pakan yang Anda miliki di gudang, sistem akan menghitung persentase takaran campuran paling hemat yang memenuhi ambang gizi SNI.
                    </p>
                    <div class="text-[11px] text-muted">
                        Target Nutrisi: <span class="font-medium text-ink">Protein Kasar, Energi Metabolis, Kalsium, Fosfor, Serat Kasar</span>
                    </div>
                </div>
                <div class="pt-2.5 border-t border-line flex items-center justify-between">
                    <span class="text-xs text-muted">Solusi pakan hemat mandiri</span>
                    <a href="{{ route('ransum.index') }}" class="inline-flex items-center gap-1 px-3.5 py-1.5 rounded-full text-xs font-semibold text-ink bg-surface hover:bg-surface-2 border border-line transition cursor-pointer shadow-2xs">
                        <span>Buka Peracik Ransum</span>
                        <span class="material-symbols-outlined text-[15px]">arrow_forward</span>
                    </a>
                </div>
            </div>

            {{-- Fitur 2: Buku Kandang --}}
            <div class="bg-white rounded-lg sm:rounded-2xl border border-line p-5 shadow-2xs flex flex-col justify-between space-y-3">
                <div class="space-y-2.5">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="material-symbols-outlined text-[18px] text-amber-700 shrink-0">menu_book</span>
                            <h3 class="text-base font-bold text-ink">Buku Kandang & Performa Harian</h3>
                        </div>
                        <span class="px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-amber-50 text-amber-800 border border-amber-200">
                            Pencatatan & Laporan
                        </span>
                    </div>
                    <p class="text-xs text-muted leading-relaxed">
                        Catat panen telur harian (butir dan kilogram), pakan yang dihabiskan, dan kematian ayam dalam 1–2 menit tiap sore. Sistem menghitung otomatis persentase bertelur (HDP %) serta menyediakan fitur cetak lembar laporan resmi A4.
                    </p>
                    <div class="text-[11px] text-muted">
                        Indikator Utama: <span class="font-medium text-ink">Hen-Day Production (HDP %), Gram Pakan / Ekor, FCR, Mortalitas</span>
                    </div>
                </div>
                <div class="pt-2.5 border-t border-line flex items-center justify-between">
                    <span class="text-xs text-muted">Siap cetak laporan resmi</span>
                    <a href="{{ route('catatan.index') }}" class="inline-flex items-center gap-1 px-3.5 py-1.5 rounded-full text-xs font-semibold text-ink bg-surface hover:bg-surface-2 border border-line transition cursor-pointer shadow-2xs">
                        <span>Buka Buku Kandang</span>
                        <span class="material-symbols-outlined text-[15px]">arrow_forward</span>
                    </a>
                </div>
            </div>

            {{-- Fitur 3: Kontrol Stok & Manajemen Bahan --}}
            <div class="bg-white rounded-lg sm:rounded-2xl border border-line p-5 shadow-2xs flex flex-col justify-between space-y-3">
                <div class="space-y-2.5">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="material-symbols-outlined text-[18px] text-muted shrink-0">inventory_2</span>
                            <h3 class="text-base font-bold text-ink">Kontrol Stok & Manajemen Bahan</h3>
                        </div>
                        <span class="px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-surface text-muted border border-line">
                            Inventori & Gudang
                        </span>
                    </div>
                    <p class="text-xs text-muted leading-relaxed">
                        Modul <strong>Kontrol Stok</strong> membantu memantau sisa stok bahan baku di gudang pakan aktif, membandingkan estimasi harga belanja dengan transaksi riil di toko, serta mengelola katalog harga pasar bahan pakan peternakan.
                    </p>
                    <div class="text-[11px] text-muted">
                        Sinkronisasi: <span class="font-medium text-ink">Stok Bahan Gudang, Harga Pasar Riil, Ambang Batas Inklusi</span>
                    </div>
                </div>
                <div class="pt-2.5 border-t border-line flex items-center justify-between">
                    <span class="text-xs text-muted">Pengawasan stok gudang</span>
                    <a href="{{ route('bahan.index') }}" class="inline-flex items-center gap-1 px-3.5 py-1.5 rounded-full text-xs font-semibold text-ink bg-surface hover:bg-surface-2 border border-line transition cursor-pointer shadow-2xs">
                        <span>Buka Bahan Pakan</span>
                        <span class="material-symbols-outlined text-[15px]">arrow_forward</span>
                    </a>
                </div>
            </div>

            {{-- Fitur 4: Asisten Cerdas --}}
            <div class="bg-white rounded-lg sm:rounded-2xl border border-line p-5 shadow-2xs flex flex-col justify-between space-y-3">
                <div class="space-y-2.5">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="material-symbols-outlined text-[18px] text-brand shrink-0">psychology</span>
                            <h3 class="text-base font-bold text-ink">Asisten Cerdas & Deteksi Dini</h3>
                        </div>
                        <span class="px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                            Peringatan Dini
                        </span>
                    </div>
                    <p class="text-xs text-muted leading-relaxed">
                        Sistem pendamping yang mengevaluasi tren catatan kandang Anda. Asisten akan memunculkan notifikasi peringatan jika produksi telur mendadak turun, konsumsi pakan tidak wajar, atau ada potensi stres panas pada ayam.
                    </p>
                    <div class="text-[11px] text-muted">
                        Fungsi: <span class="font-medium text-ink">Deteksi Anomali Produksi, Peringatan Stres Panas, Saran Praktis</span>
                    </div>
                </div>
                <div class="pt-2.5 border-t border-line flex items-center justify-between">
                    <span class="text-xs text-muted">Pencegahan kerugian kawanan</span>
                    <a href="{{ route('asisten.index') }}" class="inline-flex items-center gap-1 px-3.5 py-1.5 rounded-full text-xs font-semibold text-ink bg-surface hover:bg-surface-2 border border-line transition cursor-pointer shadow-2xs">
                        <span>Buka Asisten Cerdas</span>
                        <span class="material-symbols-outlined text-[15px]">arrow_forward</span>
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- 4. Panduan Praktis Cara Menggunakan RansumKu --}}
    <div class="space-y-3.5">
        <div class="flex items-center justify-between pb-1 border-b border-line/60">
            <span class="text-xs font-bold text-muted uppercase tracking-wider">
                Panduan Cara Menggunakan RansumKu
            </span>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            {{-- Langkah 1 --}}
            <div class="bg-white rounded-lg sm:rounded-2xl border border-line p-5 shadow-2xs space-y-2 flex flex-col justify-between">
                <div class="space-y-2">
                    <h3 class="text-xs font-bold text-ink">
                        <span class="text-brand mr-1.5">1.</span>Atur Data Kandang & Bahan
                    </h3>
                    <p class="text-xs text-muted leading-relaxed">
                        Buka menu <strong>Kelola Kandang</strong> untuk memasukkan nama kandang dan populasi ayam. Lalu periksa menu <strong>Bahan Pakan</strong> untuk memperbarui harga beli lokal saat ini.
                    </p>
                </div>
                <div class="pt-2 border-t border-line/60">
                    <span class="text-[11px] font-semibold text-muted">Langkah 1: Setup Awal</span>
                </div>
            </div>

            {{-- Langkah 2 --}}
            <div class="bg-white rounded-lg sm:rounded-2xl border border-line p-5 shadow-2xs space-y-2 flex flex-col justify-between">
                <div class="space-y-2">
                    <h3 class="text-xs font-bold text-ink">
                        <span class="text-brand mr-1.5">2.</span>Racik Ransum Hemat
                    </h3>
                    <p class="text-xs text-muted leading-relaxed">
                        Buka menu <strong>Peracik Ransum</strong>, centang bahan yang tersedia, lalu klik hitung. Sistem otomatis mencari persentase takaran campuran paling hemat yang memenuhi gizi SNI.
                    </p>
                </div>
                <div class="pt-2 border-t border-line/60">
                    <span class="text-[11px] font-semibold text-muted">Langkah 2: Optimasi Pakan</span>
                </div>
            </div>

            {{-- Langkah 3 --}}
            <div class="bg-white rounded-lg sm:rounded-2xl border border-line p-5 shadow-2xs space-y-2 flex flex-col justify-between">
                <div class="space-y-2">
                    <h3 class="text-xs font-bold text-ink">
                        <span class="text-brand mr-1.5">3.</span>Catat Telur Tiap Sore
                    </h3>
                    <p class="text-xs text-muted leading-relaxed">
                        Tiap sore setelah panen, buka menu <strong>Buku Kandang</strong>. Masukkan butir telur, total kg telur, pakan dihabiskan, dan kematian ayam (cukup 1–2 menit).
                    </p>
                </div>
                <div class="pt-2 border-t border-line/60">
                    <span class="text-[11px] font-semibold text-muted">Langkah 3: Rutinitas Harian</span>
                </div>
            </div>

            {{-- Langkah 4 --}}
            <div class="bg-white rounded-lg sm:rounded-2xl border border-line p-5 shadow-2xs space-y-2 flex flex-col justify-between">
                <div class="space-y-2">
                    <h3 class="text-xs font-bold text-ink">
                        <span class="text-brand mr-1.5">4.</span>Pantau & Cetak Laporan
                    </h3>
                    <p class="text-xs text-muted leading-relaxed">
                        Pantau grafik persentase bertelur (HDP %) di <strong>Dashboard</strong>. Bila butuh rekapan resmi untuk evaluasi usaha atau pemodal, cetak langsung laporan format A4.
                    </p>
                </div>
                <div class="pt-2 border-t border-line/60">
                    <span class="text-[11px] font-semibold text-muted">Langkah 4: Evaluasi Hasil</span>
                </div>
            </div>
        </div>
    </div>

    {{-- 5. Tanya Jawab Umum (FAQ Praktis Peternak - Tab Buka Tutup / Accordion) --}}
    <div class="space-y-3">
        <div class="flex items-center justify-between pb-1 border-b border-line/60">
            <span class="text-xs font-bold text-muted uppercase tracking-wider">
                Pertanyaan yang Sering Diajukan (FAQ)
            </span>
        </div>

        <div class="space-y-2.5">
            {{-- FAQ 1 --}}
            <details class="group bg-white rounded-lg sm:rounded-2xl border border-line p-4 sm:px-5 sm:py-4 shadow-2xs hover:border-brand/40 transition-all">
                <summary class="flex items-center justify-between cursor-pointer list-none select-none text-ink [&::-webkit-details-marker]:hidden">
                    <div class="flex items-center gap-2.5 pr-4">
                        <span class="w-5 h-5 rounded-full bg-brand/10 text-brand text-xs font-bold flex items-center justify-center shrink-0">?</span>
                        <span class="text-xs sm:text-sm font-bold text-ink">Apakah RansumKu bisa digunakan untuk pakan jadi pabrik?</span>
                    </div>
                    <span class="material-symbols-outlined text-[18px] text-muted transition-transform duration-200 group-open:rotate-180 shrink-0">expand_more</span>
                </summary>
                <div class="pt-3 mt-3 border-t border-line/60 text-xs text-muted leading-relaxed pl-7 sm:pl-8">
                    Bisa. Anda dapat memasukkan pakan pabrikan pada menu <strong>Bahan Pakan</strong> untuk menghitung biaya harian dan memantau efisiensi pakan (FCR) serta persentase bertelur ayam di Buku Kandang.
                </div>
            </details>

            {{-- FAQ 2 --}}
            <details class="group bg-white rounded-lg sm:rounded-2xl border border-line p-4 sm:px-5 sm:py-4 shadow-2xs hover:border-brand/40 transition-all">
                <summary class="flex items-center justify-between cursor-pointer list-none select-none text-ink [&::-webkit-details-marker]:hidden">
                    <div class="flex items-center gap-2.5 pr-4">
                        <span class="w-5 h-5 rounded-full bg-brand/10 text-brand text-xs font-bold flex items-center justify-center shrink-0">?</span>
                        <span class="text-xs sm:text-sm font-bold text-ink">Apakah aplikasi ini bisa diakses lewat handphone?</span>
                    </div>
                    <span class="material-symbols-outlined text-[18px] text-muted transition-transform duration-200 group-open:rotate-180 shrink-0">expand_more</span>
                </summary>
                <div class="pt-3 mt-3 border-t border-line/60 text-xs text-muted leading-relaxed pl-7 sm:pl-8">
                    Bisa. Tampilan RansumKu sepenuhnya responsif dan ringan, sehingga sangat nyaman dibuka melalui browser Google Chrome di HP maupun laptop langsung dari area kandang.
                </div>
            </details>

            {{-- FAQ 3 --}}
            <details class="group bg-white rounded-lg sm:rounded-2xl border border-line p-4 sm:px-5 sm:py-4 shadow-2xs hover:border-brand/40 transition-all">
                <summary class="flex items-center justify-between cursor-pointer list-none select-none text-ink [&::-webkit-details-marker]:hidden">
                    <div class="flex items-center gap-2.5 pr-4">
                        <span class="w-5 h-5 rounded-full bg-brand/10 text-brand text-xs font-bold flex items-center justify-center shrink-0">?</span>
                        <span class="text-xs sm:text-sm font-bold text-ink">Bagaimana jika harga jagung atau konsentrat di pasar berubah?</span>
                    </div>
                    <span class="material-symbols-outlined text-[18px] text-muted transition-transform duration-200 group-open:rotate-180 shrink-0">expand_more</span>
                </summary>
                <div class="pt-3 mt-3 border-t border-line/60 text-xs text-muted leading-relaxed pl-7 sm:pl-8">
                    Cukup perbarui angka harga di menu <strong>Bahan Pakan</strong>. Setelah itu buka kembali formulasi ransum Anda, dan sistem akan langsung menghitung ulang biaya termurah berdasarkan harga pasar terbaru.
                </div>
            </details>

            {{-- FAQ 4 --}}
            <details class="group bg-white rounded-lg sm:rounded-2xl border border-line p-4 sm:px-5 sm:py-4 shadow-2xs hover:border-brand/40 transition-all">
                <summary class="flex items-center justify-between cursor-pointer list-none select-none text-ink [&::-webkit-details-marker]:hidden">
                    <div class="flex items-center gap-2.5 pr-4">
                        <span class="w-5 h-5 rounded-full bg-brand/10 text-brand text-xs font-bold flex items-center justify-center shrink-0">?</span>
                        <span class="text-xs sm:text-sm font-bold text-ink">Apakah formula pakan baru bisa langsung diberikan sekaligus?</span>
                    </div>
                    <span class="material-symbols-outlined text-[18px] text-muted transition-transform duration-200 group-open:rotate-180 shrink-0">expand_more</span>
                </summary>
                <div class="pt-3 mt-3 border-t border-line/60 text-xs text-muted leading-relaxed pl-7 sm:pl-8">
                    Sebaiknya lakukan masa transisi penggantian pakan bertahap selama 3–5 hari dengan mencampurkan pakan baru sedikit demi sedikit agar saluran pencernaan ayam tidak kaget dan tidak memicu stres.
                </div>
            </details>
        </div>
    </div>

    {{-- 6. Footer Ringkas & Elegan --}}
    <div class="text-center py-4 text-xs text-muted border-t border-line/60">
        <span>RansumKu &copy; {{ date('Y') }} · Platform Formulasi Pakan & Buku Kandang Ayam Petelur · Universitas Gadjah Mada</span>
    </div>
</div>

@endsection
