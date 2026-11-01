@use('App\Support\Fmt')
@if (count($belanja))
    <div class="rounded-2xl p-4 border bg-amber-soft/40 border-amber/30 text-amber-ink transition-all mt-3">
        <div class="flex items-center justify-between pb-2 mb-2 border-b border-amber/20">
            <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-[18px] text-amber-ink">shopping_cart</span>
                <span class="font-bold text-xs uppercase tracking-wider text-amber-ink">Rekomendasi Belanja Tambahan</span>
            </div>
            <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-amber-ink/10 text-amber-ink">
                {{ count($belanja) }} Item Kurang
            </span>
        </div>
        <ul class="text-xs space-y-1.5 divide-y divide-amber/15">
            @foreach ($belanja as $b)
                <li class="flex items-center justify-between pt-1.5 first:pt-0">
                    <div class="flex items-center gap-1.5">
                        <span class="w-1.5 h-1.5 rounded-full bg-amber-ink/60"></span>
                        <span class="font-semibold">{{ $b['nama'] }}</span>
                        <span class="text-[11px] text-amber-ink/80">(kurang {{ Fmt::num($b['kekurangan_kg'], 2) }} kg)</span>
                    </div>
                    <span class="tabular-nums font-bold text-ink">{{ Fmt::rp($b['biaya']) }}</span>
                </li>
            @endforeach
        </ul>
        <p class="text-[11px] text-amber-ink/80 mt-2.5 pt-2 border-t border-amber/20 flex items-center gap-1">
            <span class="material-symbols-outlined text-[14px]">info</span>
            <span>Belanja tambahan opsional untuk formulasi tanpa batas stok gudang.</span>
        </p>
    </div>
@endif
