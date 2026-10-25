@use('App\Support\Fmt')
<div class="rounded-xl p-3 {{ count($belanja) ? 'bg-amber-soft/40' : 'bg-brand/5' }}">
    @if (count($belanja))
        <p class="font-bold mb-1 flex items-center gap-1.5"><span class="material-symbols-outlined text-amber-ink">shopping_cart</span>Daftar belanja</p>
        <ul class="text-sm space-y-1">
            @foreach ($belanja as $b)
                <li class="flex justify-between"><span>{{ $b['nama'] }}: kurang {{ Fmt::num($b['kekurangan_kg'], 2) }} kg</span><span class="font-semibold">{{ Fmt::rp($b['biaya']) }} <span class="tag-est">estimasi</span></span></li>
            @endforeach
        </ul>
        <p class="text-xs text-muted mt-1">Belanja tambahan untuk resep ideal tanpa batas stok (opsional).</p>
    @else
        <p class="text-sm flex items-center gap-1.5"><span class="material-symbols-outlined text-brand">check_circle</span>Stok cukup. Tidak perlu belanja.</p>
    @endif
</div>
