@extends('layouts.app')
@section('judul', 'Riwayat Ransum')
@section('isi')
@use('App\Support\Fmt')
<div class="flex items-center justify-between">
    <h1 class="text-h-xl">Riwayat Ransum</h1>
    <a href="{{ route('ransum.create') }}" class="btn-primary btn-sm"><span class="material-symbols-outlined">add</span>Baru</a>
</div>

@forelse ($rations as $r)
    <div class="card space-y-2">
        <div class="flex items-center justify-between">
            <p class="font-bold">{{ $r->created_at->translatedFormat('d M Y, H:i') }}</p>
            <span class="chip {{ $r->layak() ? 'bg-brand/10 text-brand' : 'bg-danger-soft text-danger-ink' }}">{{ $r->layak() ? 'Layak' : 'Belum layak' }}</span>
        </div>
        <p class="text-sm text-muted">{{ Fmt::num($r->total_kg, 1) }} kg @if($r->layak())· {{ Fmt::rp($r->biaya_per_kg) }}/kg <span class="tag-est">estimasi</span>@endif</p>
        @if ($r->layak())
            <div class="flex h-2.5 rounded-full overflow-hidden">
                @foreach ($r->items as $i => $it)<span style="width:{{ $it->persen }}%;background:{{ ['#2F6B3A', '#F2B134', '#145224', '#D64545', '#7e5700', '#96d69a'][$i % 6] }}"></span>@endforeach
            </div>
            <p class="text-xs text-muted">{{ $r->items->map(fn ($i) => $i->nama.' '.Fmt::num($i->persen, 0).'%')->implode(' · ') }}</p>
        @endif
        <div class="flex gap-2">
            <a href="{{ route('ransum.show', $r) }}" class="btn-outline btn-sm flex-1">Lihat / pakai lagi</a>
            <form method="POST" action="{{ route('ransum.destroy', $r) }}" onsubmit="return confirm('Hapus resep ini?')">@csrf @method('DELETE')
                <button class="btn btn-sm border-2 border-line text-muted" aria-label="Hapus"><span class="material-symbols-outlined">delete</span></button></form>
        </div>
    </div>
@empty
    <div class="card text-center py-10 space-y-3">
        <span class="material-symbols-outlined text-[48px] text-muted">calculate</span>
        <p class="text-muted">Belum ada resep tersimpan.</p>
        <a href="{{ route('ransum.create') }}" class="btn-primary">Hitung Ransum</a>
    </div>
@endforelse
@endsection
