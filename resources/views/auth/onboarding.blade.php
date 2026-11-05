@extends('layouts.guest')
@section('judul', 'Data Kandang')
@section('isi')
<form method="POST" action="{{ url('/onboarding') }}" class="card space-y-4">
    @csrf
    <h2 class="text-h-md">Data Kandang</h2>
    <p class="text-sm text-muted">Data ini bisa diubah kapan saja.</p>
    <div>
        <label class="lbl" for="nama">Nama kandang</label>
        <input id="nama" name="nama" value="{{ old('nama') }}" class="field" placeholder="Contoh: Kandang Maju Jaya" required>
    </div>
    <div>
        <label class="lbl" for="jumlah_ayam">Jumlah ayam</label>
        <input id="jumlah_ayam" name="jumlah_ayam" type="number" min="1" inputmode="numeric" value="{{ old('jumlah_ayam', 200) }}" class="field text-center text-h-md" required>
    </div>
    <div class="grid grid-cols-2 gap-3">
        <div>
            <label class="lbl" for="umur_awal_minggu">Umur Masuk (Mg)</label>
            <input id="umur_awal_minggu" name="umur_awal_minggu" type="number" min="1" max="120" value="{{ old('umur_awal_minggu', 18) }}" class="field font-semibold text-center" required>
        </div>
        <div>
            <label class="lbl" for="strain_ayam">Strain / Jenis Bibit</label>
            <input id="strain_ayam" name="strain_ayam" value="{{ old('strain_ayam', 'Lohmann Brown') }}" class="field font-semibold text-center">
        </div>
    </div>
    <input type="hidden" name="fase" value="produksi">
    <p class="text-[11px] text-muted text-center">Sub-fase produksi ayam petelur akan terdeteksi otomatis dari umur ayam.</p>
    <button id="btn-onboarding" class="btn-primary w-full">Lanjut</button>
</form>
@endsection
