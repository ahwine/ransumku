@extends('layouts.guest')
@section('judul', 'Masuk')
@section('isi')
<form method="POST" action="{{ url('/masuk') }}" class="card space-y-4">
    @csrf
    <div>
        <label class="lbl" for="email">Email</label>
        <input id="email" name="email" type="email" value="{{ old('email') }}" class="field" autocomplete="email" required autofocus>
    </div>
    <div>
        <label class="lbl" for="password">Kata sandi</label>
        <input id="password" name="password" type="password" class="field" autocomplete="current-password" required>
    </div>
    <label class="flex items-center gap-2 text-sm text-muted"><input type="checkbox" name="ingat" class="w-5 h-5 accent-brand"> Ingat saya</label>
    <button id="btn-masuk" class="btn-primary w-full">Masuk</button>
    <p class="text-center text-sm text-muted">Belum punya akun? <a href="{{ route('daftar') }}" class="text-brand font-bold">Daftar</a></p>
</form>
<p class="text-center text-xs text-muted">Akun demo: demo@ransumku.test / password</p>
@endsection
