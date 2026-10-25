@extends('layouts.guest')
@section('judul', 'Daftar')
@section('isi')
<form method="POST" action="{{ url('/daftar') }}" class="card space-y-4">
    @csrf
    <div>
        <label class="lbl" for="name">Nama</label>
        <input id="name" name="name" value="{{ old('name') }}" class="field" autocomplete="name" required autofocus>
    </div>
    <div>
        <label class="lbl" for="email">Email</label>
        <input id="email" name="email" type="email" value="{{ old('email') }}" class="field" autocomplete="email" required>
    </div>
    <div>
        <label class="lbl" for="password">Kata sandi (min. 8 karakter)</label>
        <input id="password" name="password" type="password" class="field" autocomplete="new-password" required>
    </div>
    <div>
        <label class="lbl" for="password_confirmation">Konfirmasi kata sandi</label>
        <input id="password_confirmation" name="password_confirmation" type="password" class="field" autocomplete="new-password" required>
    </div>
    <button id="btn-daftar" class="btn-primary w-full">Buat Akun</button>
    <p class="text-center text-sm text-muted">Sudah punya akun? <a href="{{ route('masuk') }}" class="text-brand font-bold">Masuk</a></p>
</form>
@endsection
