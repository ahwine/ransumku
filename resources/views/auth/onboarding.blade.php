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
    <fieldset>
        <legend class="lbl">Fase ayam</legend>
        <div class="grid grid-cols-3 gap-2">
            @foreach (['starter' => 'Starter', 'grower' => 'Grower', 'produksi' => 'Produksi'] as $v => $l)
                <label class="cursor-pointer">
                    <input type="radio" name="fase" value="{{ $v }}" class="peer sr-only" @checked(old('fase', 'produksi') === $v)>
                    <span class="block text-center py-3 rounded-xl border-2 border-line font-bold peer-checked:border-brand peer-checked:bg-brand/5 peer-checked:text-brand">{{ $l }}</span>
                </label>
            @endforeach
        </div>
    </fieldset>
    <button id="btn-onboarding" class="btn-primary w-full">Lanjut</button>
</form>
@endsection
