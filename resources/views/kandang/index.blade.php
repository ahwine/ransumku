@extends('layouts.app')
@section('judul', 'Kelola Kandang')
@section('isi')

<div class="space-y-6">
    {{-- Header Halaman --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-xl md:text-2xl font-black text-ink tracking-tight flex items-center gap-2">
                <span class="material-symbols-outlined text-brand text-[26px]">cottage</span>
                <span>Kelola Kandang</span>
            </h1>
            <p class="text-xs md:text-sm text-muted mt-0.5">
                Pusat kendali multi-kandang peternakan. Atur populasi ayam, fase pemeliharaan, dan beralih kandang aktif.
            </p>
        </div>

        <div class="flex items-center gap-2.5 self-start sm:self-auto">
            <button id="btn-tambah-kandang-top" type="button" onclick="toggleFormTambah()" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-full text-xs font-bold text-white bg-brand hover:bg-brand-dark shadow-2xs hover:shadow-xs transition-all active:scale-95 cursor-pointer">
                <span class="material-symbols-outlined text-[17px]">add</span>
                <span>Tambah Kandang</span>
            </button>
        </div>
    </div>

    {{-- Form Collapsible Tambah Kandang Baru --}}
    <div id="form-tambah-kandang" class="hidden bg-white rounded-2xl border border-line shadow-2xs p-5 md:p-6 transition-all duration-300 animate-fadeIn">
        <form method="POST" action="{{ route('kandang.store') }}" class="space-y-4">
            @csrf
            <div class="flex items-center justify-between pb-3 border-b border-line">
                <div>
                    <h2 class="text-sm font-extrabold text-ink tracking-tight">Input Data Kandang Baru</h2>
                    <p class="text-tiny text-muted">Lengkapi data unit kandang baru. Standar nutrisi & template bahan pakan disiapkan otomatis.</p>
                </div>
                <button type="button" onclick="toggleFormTambah()" class="w-7 h-7 rounded-full bg-surface hover:bg-surface-2 border border-line text-muted hover:text-ink flex items-center justify-center text-xs cursor-pointer transition">
                    ✕
                </button>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4 text-xs">
                <div>
                    <label class="block font-bold text-ink mb-1.5">Nama Kandang <span class="text-danger">*</span></label>
                    <input type="text" name="nama" class="input w-full text-xs font-semibold" placeholder="Contoh: Kandang B - Pullet" required autocomplete="off">
                </div>
                <div>
                    <label class="block font-bold text-ink mb-1.5">Populasi Awal (Ekor) <span class="text-danger">*</span></label>
                    <input type="number" name="jumlah_ayam" min="1" max="1000000" class="input w-full text-xs font-semibold" placeholder="Contoh: 500" required>
                </div>
                <div>
                    <label class="block font-bold text-ink mb-1.5">Fase Pemeliharaan <span class="text-danger">*</span></label>
                    <select name="fase" class="input w-full text-xs bg-white font-medium" required>
                        <option value="produksi">Produksi (Ayam Bertelur / Layer)</option>
                        <option value="grower">Grower / Pullet (Remaja)</option>
                        <option value="starter">Starter (Fase Awal / DOC)</option>
                    </select>
                </div>
                <div>
                    <label class="block font-bold text-ink mb-1.5">Sentra Lokasi / Kota</label>
                    <select name="lokasi_kota" class="input w-full text-xs bg-white font-medium">
                        @foreach ($presetKota as $k => $p)
                            <option value="{{ $k }}">{{ $p['nama'] }} ({{ $p['provinsi'] ?? 'Indonesia' }})</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="flex justify-end items-center gap-2 pt-2 border-t border-dashed border-line">
                <button type="button" onclick="toggleFormTambah()" class="btn-secondary text-xs">
                    Batal
                </button>
                <button type="submit" class="btn-primary text-xs flex items-center gap-1.5">
                    <span class="material-symbols-outlined text-[16px]">save</span>
                    <span>Simpan & Aktifkan Kandang</span>
                </button>
            </div>
        </form>
    </div>

    {{-- Daftar Kandang Terdaftar --}}
    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <span class="text-xs font-bold text-muted uppercase tracking-wider">
                Kandang Terdaftar ({{ $farms->count() }})
            </span>
            <span class="text-tiny text-muted">
                Total Populasi Terkelola: <strong class="text-ink font-extrabold">{{ number_format($farms->sum('jumlah_ayam'), 0, ',', '.') }} Ekor</strong>
            </span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 {{ $farms->count() >= 3 ? 'lg:grid-cols-3' : '' }} gap-5">
            @foreach ($farms as $f)
                @php $isActive = ($activeFarm && $activeFarm->id === $f->id); @endphp
                <div class="bg-white rounded-2xl border border-line p-6 shadow-2xs hover:shadow-xs transition-all flex flex-col justify-between space-y-5">
                    <div class="space-y-4">
                        {{-- Baris Atas Card: Nama Kandang & Status Dipilih --}}
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <h2 class="text-lg font-extrabold text-ink tracking-tight truncate">
                                    {{ $f->nama }}
                                </h2>
                                <p class="text-tiny text-muted mt-0.5">
                                    {{ $f->logs_count }} hari log data · {{ $f->rations_count }} formula ransum
                                </p>
                            </div>

                            @if ($isActive)
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-surface border border-line text-brand shrink-0">
                                    <span class="material-symbols-outlined text-[15px]">check_circle</span>
                                    <span>Dipilih</span>
                                </span>
                            @endif
                        </div>

                        {{-- Grid Metrik Terstruktur (Divided Metrics Grid Style) --}}
                        <div class="grid grid-cols-3 divide-x divide-line rounded-xl border border-line bg-surface/40 py-3 text-center">
                            <div class="px-2">
                                <span class="text-[11px] font-semibold text-muted uppercase tracking-wider block">Populasi</span>
                                <span class="text-sm md:text-base font-extrabold text-ink mt-0.5 block">
                                    {{ number_format($f->jumlah_ayam, 0, ',', '.') }} Ekor
                                </span>
                            </div>
                            <div class="px-2">
                                <span class="text-[11px] font-semibold text-muted uppercase tracking-wider block">Fase</span>
                                <span class="text-sm md:text-base font-extrabold text-ink mt-0.5 block">
                                    {{ ucfirst($f->fase) }}
                                </span>
                            </div>
                            <div class="px-2">
                                <span class="text-[11px] font-semibold text-muted uppercase tracking-wider block">Sentra</span>
                                <span class="text-sm md:text-base font-extrabold text-ink mt-0.5 block truncate" title="{{ $f->lokasi_kota ?? 'Surabaya' }}">
                                    {{ $f->lokasi_kota ?? 'Surabaya' }}
                                </span>
                            </div>
                        </div>

                        {{-- Form Edit Inline Kandang (Collapsible) --}}
                        <div id="edit-kandang-{{ $f->id }}" class="hidden pt-3 border-t border-dashed border-line bg-surface/50 -mx-6 -mb-3 p-4 rounded-b-2xl animate-fadeIn space-y-3">
                            <form method="POST" action="{{ route('kandang.update', $f) }}" class="space-y-3">
                                @csrf
                                @method('PUT')
                                <div class="flex items-center justify-between pb-1.5 border-b border-line">
                                    <span class="text-tiny font-bold text-ink uppercase tracking-wider flex items-center gap-1">
                                        <span class="material-symbols-outlined text-[14px] text-brand">edit_note</span>
                                        <span>Edit Data Kandang</span>
                                    </span>
                                    <button type="button" onclick="toggleEditKandang({{ $f->id }})" class="text-muted hover:text-ink text-xs">✕</button>
                                </div>

                                <div class="space-y-2.5 text-xs">
                                    <div>
                                        <label class="block font-bold text-ink mb-1">Nama Kandang</label>
                                        <input type="text" name="nama" value="{{ $f->nama }}" class="input w-full text-xs font-semibold" required>
                                    </div>
                                    <div class="grid grid-cols-2 gap-2">
                                        <div>
                                            <label class="block font-bold text-ink mb-1">Populasi (Ekor)</label>
                                            <input type="number" name="jumlah_ayam" value="{{ $f->jumlah_ayam }}" min="1" max="1000000" class="input w-full text-xs" required>
                                        </div>
                                        <div>
                                            <label class="block font-bold text-ink mb-1">Fase</label>
                                            <select name="fase" class="input w-full text-xs bg-white font-medium" required>
                                                <option value="produksi" @selected($f->fase === 'produksi')>Produksi</option>
                                                <option value="grower" @selected($f->fase === 'grower')>Grower</option>
                                                <option value="starter" @selected($f->fase === 'starter')>Starter</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block font-bold text-ink mb-1">Lokasi Sentra</label>
                                        <select name="lokasi_kota" class="input w-full text-xs bg-white font-medium">
                                            @foreach ($presetKota as $k => $p)
                                                <option value="{{ $k }}" @selected(($f->lokasi_kota ?? 'Surabaya') === $k)>{{ $p['nama'] }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                <div class="flex justify-end gap-2 pt-2">
                                    <button type="button" onclick="toggleEditKandang({{ $f->id }})" class="btn-secondary text-tiny">
                                        Batal
                                    </button>
                                    <button type="submit" class="btn-primary text-tiny">
                                        Simpan Perubahan
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    {{-- Card Footer / Action Buttons --}}
                    <div class="pt-4 border-t border-line flex items-center justify-between gap-3">
                        <div class="flex items-center gap-1.5">
                            {{-- Tombol Edit --}}
                            <button id="btn-edit-kandang-{{ $f->id }}" type="button" onclick="toggleEditKandang({{ $f->id }})" class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-semibold text-muted hover:text-ink bg-surface hover:bg-surface-2 border border-line transition cursor-pointer shadow-2xs" title="Edit Data Kandang">
                                <span class="material-symbols-outlined text-[15px]">edit</span>
                                <span>Edit</span>
                            </button>

                            {{-- Tombol Hapus (jika kandang > 1) --}}
                            @if ($farms->count() > 1)
                                <form method="POST" action="{{ route('kandang.destroy', $f) }}" onsubmit="return confirm('Apakah Anda yakin ingin menghapus kandang \'{{ $f->nama }}\'? Seluruh rekaman catatan dan formula pakan di kandang ini akan terhapus.');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-xs font-semibold text-rose-600 hover:text-rose-700 bg-rose-50/50 hover:bg-rose-50 border border-rose-200/60 transition cursor-pointer shadow-2xs" title="Hapus Kandang">
                                        <span class="material-symbols-outlined text-[15px]">delete</span>
                                        <span>Hapus</span>
                                    </button>
                                </form>
                            @endif
                        </div>

                        <div>
                            @if ($isActive)
                                <a href="{{ route('beranda') }}" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-bold text-white bg-brand hover:bg-brand-dark transition shadow-2xs">
                                    <span>Buka Dashboard</span>
                                    <span class="material-symbols-outlined text-[15px]">arrow_forward</span>
                                </a>
                            @else
                                <form method="POST" action="{{ route('kandang.switch') }}">
                                    @csrf
                                    <input type="hidden" name="farm_id" value="{{ $f->id }}">
                                    <button type="submit" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-bold text-ink bg-surface hover:bg-brand hover:text-white border border-line transition shadow-2xs cursor-pointer">
                                        <span class="material-symbols-outlined text-[15px]">swap_horiz</span>
                                        <span>Pilih Kandang</span>
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>

<script>
    function toggleFormTambah() {
        const form = document.getElementById('form-tambah-kandang');
        if (form) {
            form.classList.toggle('hidden');
            if (!form.classList.contains('hidden')) {
                form.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                const inputNama = form.querySelector('input[name="nama"]');
                if (inputNama) inputNama.focus();
            }
        }
    }

    function toggleEditKandang(id) {
        const el = document.getElementById('edit-kandang-' + id);
        if (el) {
            el.classList.toggle('hidden');
        }
    }
</script>

@endsection
